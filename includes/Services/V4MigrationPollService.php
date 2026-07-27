<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

/**
 * Polls the migration portal for v4 completion status.
 */
class V4MigrationPollService {

	/**
	 * Option storing the active v4 portal poll state.
	 */
	const OPTION_NAME = 'nfd_migration_v4_poll';

	/**
	 * Cron hook for portal state polling.
	 */
	const CRON_HOOK = 'nfd_migration_v4_portal_poll';

	/**
	 * Uploads subdirectory for state files that survive DB import.
	 */
	const STATE_DIR = 'nfd-migration';

	/**
	 * Poll state file name.
	 */
	const STATE_FILE = 'v4-poll-state.json';

	/**
	 * Option storing the active v4 migration session.
	 */
	const SESSION_OPTION = 'nfd_migration_v4_session';

	/**
	 * Session file name.
	 */
	const SESSION_FILE = 'v4-session.json';

	/**
	 * Delay before the first portal poll.
	 */
	const INITIAL_POLL_DELAY = 30;

	/**
	 * Poll interval after the first tick.
	 */
	const POLL_INTERVAL = 60;

	/**
	 * Minimum seconds between portal API calls.
	 */
	const MIN_POLL_GAP = 30;

	/**
	 * Stop polling after three days.
	 */
	const MAX_POLL_AGE = 259200;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_action( self::CRON_HOOK, array( $this, 'poll_portal_state' ) );
		add_action( 'rest_api_init', array( $this, 'maybe_restore_poll_state' ), 1 );
		add_action( 'rest_api_init', array( $this, 'maybe_poll_on_request' ), 2 );
		add_action( 'init', array( $this, 'maybe_restore_poll_state' ), 10 );
		add_action( 'init', array( $this, 'maybe_poll_on_request' ), 20 );
	}

	/**
	 * Clear a stale status-sent flag left in wp_options by a DB import mid-migration.
	 *
	 * @return bool Whether the flag was cleared.
	 */
	public static function reconcile_stale_status_sent_flag() {
		if ( ! get_option( 'nfd_migration_status_sent', false ) ) {
			return false;
		}

		if ( ! self::has_persisted_v4_migration() ) {
			return false;
		}

		delete_option( 'nfd_migration_status_sent' );

		return true;
	}

	/**
	 * Whether poll/session files or options indicate an in-flight v4 migration.
	 *
	 * @return bool
	 */
	public static function has_persisted_v4_migration() {
		$session = self::get_session();
		if ( ! empty( $session['migration_token'] ) ) {
			return true;
		}

		$service = new self();
		return ! empty( $service->read_poll_state_file() );
	}

	/**
	 * Whether a v4 migration session is active on this site.
	 *
	 * @return bool
	 */
	public static function has_active_session() {
		$session = self::get_session();
		return ! empty( $session['migration_token'] );
	}

	/**
	 * Whether post-migration work belongs to the active v4 session.
	 *
	 * @param string $migrate_group_uuid Migration group UUID from cron or v3 option.
	 * @param string $source_site_url    Optional source site URL.
	 * @return bool
	 */
	public static function session_matches( $migrate_group_uuid, $source_site_url = '' ) {
		$session = self::get_session();
		if ( empty( $session['migration_token'] ) ) {
			return true;
		}

		if ( ! empty( $migrate_group_uuid ) && ! empty( $session['migrate_group_uuid'] ) ) {
			return $migrate_group_uuid === $session['migrate_group_uuid'];
		}

		if ( ! empty( $source_site_url ) && ! empty( $session['source_site_url'] ) ) {
			return untrailingslashit( $source_site_url ) === untrailingslashit( $session['source_site_url'] );
		}

		return false;
	}

	/**
	 * Restore poll state from disk when DB import wiped wp_options.
	 *
	 * @return void
	 */
	public function maybe_restore_poll_state() {
		self::reconcile_stale_status_sent_flag();

		if ( get_option( 'nfd_migration_status_sent', false ) ) {
			$this->stop_polling();
			self::end_session();
			return;
		}

		$session = self::get_session();
		if ( empty( $session['migration_token'] ) ) {
			$file_session = self::read_session_file();
			if ( ! empty( $file_session['migration_token'] ) ) {
				update_option( self::SESSION_OPTION, $file_session, false );
				$session = $file_session;
			}
		}

		$poll_state = get_option( self::OPTION_NAME, array() );
		if ( is_array( $poll_state ) && ! empty( $poll_state['migration_token'] ) ) {
			return;
		}

		$file_state = $this->read_poll_state_file();

		if ( empty( $file_state['migration_token'] ) && ! empty( $session['migration_token'] ) ) {
			$file_state = array(
				'migration_token' => $session['migration_token'],
				'started_at'      => isset( $session['started_at'] ) ? (int) $session['started_at'] : time(),
				'next_poll_at'    => time(),
				'last_poll_at'    => 0,
			);
			$this->save_poll_state( $file_state );
			$this->schedule_next_poll( 0 );
			$this->spawn_cron();
			return;
		}

		if ( empty( $file_state['migration_token'] ) ) {
			return;
		}

		update_option( self::OPTION_NAME, $file_state, false );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			$next_poll_at = isset( $file_state['next_poll_at'] ) ? (int) $file_state['next_poll_at'] : 0;
			$delay        = $next_poll_at > time() ? ( $next_poll_at - time() ) : 0;
			$this->schedule_next_poll( $delay );
			$this->spawn_cron();
		}
	}

	/**
	 * Poll on any WP request when the next poll time has passed.
	 *
	 * @return void
	 */
	public function maybe_poll_on_request() {
		self::reconcile_stale_status_sent_flag();

		if ( get_option( 'nfd_migration_status_sent', false ) ) {
			$this->stop_polling();
			self::end_session();
			return;
		}

		$poll_state = $this->get_poll_state();
		if ( empty( $poll_state['migration_token'] ) ) {
			return;
		}

		$next_poll_at = isset( $poll_state['next_poll_at'] ) ? (int) $poll_state['next_poll_at'] : 0;
		$last_poll_at = isset( $poll_state['last_poll_at'] ) ? (int) $poll_state['last_poll_at'] : 0;

		if ( $next_poll_at > 0 && time() < $next_poll_at ) {
			return;
		}

		if ( $last_poll_at > 0 && ( time() - $last_poll_at ) < self::MIN_POLL_GAP ) {
			return;
		}

		$this->poll_portal_state();
	}

	/**
	 * Begin polling portal state for a v4 migration token.
	 *
	 * @param string $migration_token Portal token from /start?t=...
	 * @return void
	 */
	public function start_polling( $migration_token ) {
		$migration_token = sanitize_text_field( (string) $migration_token );
		if ( empty( $migration_token ) ) {
			return;
		}

		$this->clear_scheduled_polls();
		$this->clear_post_migration_cron_jobs();
		delete_option( 'nfd_migration_status_sent' );

		self::begin_session( $migration_token );

		$this->save_poll_state(
			array(
				'migration_token' => $migration_token,
				'started_at'      => time(),
				'next_poll_at'    => time() + self::INITIAL_POLL_DELAY,
				'last_poll_at'    => 0,
			)
		);

		$this->schedule_next_poll( self::INITIAL_POLL_DELAY );
		$this->spawn_cron();
	}

	/**
	 * Poll portal state and trigger completion handling when terminal.
	 *
	 * @return void
	 */
	public function poll_portal_state() {
		self::reconcile_stale_status_sent_flag();

		$poll_state = $this->get_poll_state();
		if ( empty( $poll_state['migration_token'] ) ) {
			$this->stop_polling();
			return;
		}

		if ( get_option( 'nfd_migration_status_sent', false ) ) {
			$this->stop_polling();
			self::end_session();
			return;
		}

		$started_at = isset( $poll_state['started_at'] ) ? (int) $poll_state['started_at'] : 0;
		if ( $started_at > 0 && ( time() - $started_at ) > self::MAX_POLL_AGE ) {
			$this->stop_polling();
			return;
		}

		$poll_state['last_poll_at'] = time();
		$this->save_poll_state( $poll_state );

		$portal_response = UtilityService::get_portal_migration_state( $poll_state['migration_token'] );
		$parsed          = UtilityService::parse_portal_migration_state( $portal_response );

		self::update_session_from_parsed( $parsed );

		if ( empty( $parsed['status'] ) || ! UtilityService::is_terminal_portal_status( $parsed['status'] ) ) {
			$this->schedule_next_poll();
			$this->spawn_cron();
			return;
		}

		$migrate_group_uuid = ! empty( $parsed['migrate_group_uuid'] )
			? $parsed['migrate_group_uuid']
			: $poll_state['migration_token'];

		$completion = new MigrationCompletionService();
		$processed  = $completion->process_terminal_status(
			$parsed['status'],
			$migrate_group_uuid,
			array(
				'source_site_url' => $parsed['source_site_url'],
			)
		);

		if ( $processed || in_array( $parsed['status'], array( 'failed', 'aborted' ), true ) ) {
			$this->stop_polling();
			return;
		}

		$this->schedule_next_poll();
		$this->spawn_cron();
	}

	/**
	 * Start a v4 migration session tied to a portal token.
	 *
	 * @param string $migration_token Portal token from /start?t=...
	 * @return void
	 */
	public static function begin_session( $migration_token ) {
		$migration_token = sanitize_text_field( (string) $migration_token );
		if ( empty( $migration_token ) ) {
			return;
		}

		$session = array(
			'migration_token'    => $migration_token,
			'migrate_group_uuid' => '',
			'source_site_url'    => '',
			'started_at'         => time(),
		);

		update_option( self::SESSION_OPTION, $session, false );
		self::write_session_file( $session );
	}

	/**
	 * Update session identifiers learned from portal polling.
	 *
	 * @param array $parsed Parsed portal state.
	 * @return void
	 */
	public static function update_session_from_parsed( array $parsed ) {
		if ( empty( $parsed ) ) {
			return;
		}

		$session = self::get_session();
		if ( empty( $session['migration_token'] ) ) {
			return;
		}

		if ( ! empty( $parsed['migrate_group_uuid'] ) ) {
			$session['migrate_group_uuid'] = $parsed['migrate_group_uuid'];
		}

		if ( ! empty( $parsed['source_site_url'] ) ) {
			$session['source_site_url'] = $parsed['source_site_url'];
		}

		update_option( self::SESSION_OPTION, $session, false );
		self::write_session_file( $session );
	}

	/**
	 * End the v4 migration session after terminal events are sent.
	 *
	 * @return void
	 */
	public static function end_session() {
		delete_option( self::SESSION_OPTION );
		$file_path = self::get_session_file_path();
		if ( $file_path && file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}
	}

	/**
	 * Read the active v4 session from options or disk.
	 *
	 * @return array
	 */
	public static function get_session() {
		$session = get_option( self::SESSION_OPTION, array() );
		if ( is_array( $session ) && ! empty( $session['migration_token'] ) ) {
			return $session;
		}

		return self::read_session_file();
	}

	/**
	 * Schedule the next single poll event.
	 *
	 * @param int|null $delay Seconds until the next poll.
	 * @return void
	 */
	private function schedule_next_poll( $delay = null ) {
		$delay = null !== $delay ? (int) $delay : self::POLL_INTERVAL;
		$next  = time() + $delay;
		$state = $this->get_poll_state();

		if ( ! empty( $state ) ) {
			$state['next_poll_at'] = $next;
			$this->save_poll_state( $state );
		}

		$this->clear_scheduled_polls();
		wp_schedule_single_event( $next, self::CRON_HOOK );
	}

	/**
	 * Nudge wp-cron so chained polls run without waiting for front-end traffic.
	 *
	 * @return void
	 */
	private function spawn_cron() {
		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) {
			return;
		}

		$cron_url = add_query_arg(
			'doing_wp_cron',
			sprintf( '%.22F', microtime( true ) ),
			site_url( 'wp-cron.php' )
		);

		wp_remote_post(
			$cron_url,
			array(
				'timeout'   => 0.01,
				'blocking'  => false,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
			)
		);
	}

	/**
	 * Clear poll state and unschedule cron.
	 *
	 * @return void
	 */
	public function stop_polling() {
		$this->delete_poll_state();
		$this->clear_scheduled_polls();
	}

	/**
	 * Remove all scheduled poll events.
	 *
	 * @return void
	 */
	private function clear_scheduled_polls() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
		}
	}

	/**
	 * Remove stale post-migration cron jobs from prior migrations or imported DBs.
	 *
	 * @return void
	 */
	private function clear_post_migration_cron_jobs() {
		wp_clear_scheduled_hook( 'nfd_migration_page_speed_source' );
		wp_clear_scheduled_hook( 'nfd_migration_page_speed_destination' );
		wp_clear_scheduled_hook( 'nfd_migration_source_hosting_info' );
	}

	/**
	 * Read poll state from the option, falling back to the on-disk copy.
	 *
	 * @return array
	 */
	private function get_poll_state() {
		$poll_state = get_option( self::OPTION_NAME, array() );
		if ( is_array( $poll_state ) && ! empty( $poll_state['migration_token'] ) ) {
			return $poll_state;
		}

		$file_state = $this->read_poll_state_file();
		if ( ! empty( $file_state['migration_token'] ) ) {
			return $file_state;
		}

		return array();
	}

	/**
	 * Persist poll state to wp_options and a file that survives DB import.
	 *
	 * @param array $state Poll state payload.
	 * @return void
	 */
	private function save_poll_state( array $state ) {
		update_option( self::OPTION_NAME, $state, false );
		$this->write_poll_state_file( $state );
	}

	/**
	 * Remove poll state from wp_options and disk.
	 *
	 * @return void
	 */
	private function delete_poll_state() {
		delete_option( self::OPTION_NAME );
		$file_path = $this->get_poll_state_file_path();
		if ( $file_path && file_exists( $file_path ) ) {
			wp_delete_file( $file_path );
		}
	}

	/**
	 * Absolute path to the poll state file.
	 *
	 * @return string
	 */
	private function get_poll_state_file_path() {
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return '';
		}

		return trailingslashit( $upload_dir['basedir'] ) . self::STATE_DIR . '/' . self::STATE_FILE;
	}

	/**
	 * Read poll state from disk.
	 *
	 * @return array
	 */
	private function read_poll_state_file() {
		$file_path = $this->get_poll_state_file_path();
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			return array();
		}

		$contents = file_get_contents( $file_path );
		if ( empty( $contents ) ) {
			return array();
		}

		$state = json_decode( $contents, true );
		if ( ! is_array( $state ) || empty( $state['migration_token'] ) ) {
			return array();
		}

		return $state;
	}

	/**
	 * Write poll state to disk.
	 *
	 * @param array $state Poll state payload.
	 * @return void
	 */
	private function write_poll_state_file( array $state ) {
		$file_path = $this->get_poll_state_file_path();
		if ( empty( $file_path ) ) {
			return;
		}

		$directory = dirname( $file_path );
		if ( ! wp_mkdir_p( $directory ) ) {
			return;
		}

		$encoded = wp_json_encode( $state );
		if ( false === $encoded ) {
			return;
		}

		file_put_contents( $file_path, $encoded, LOCK_EX );
	}

	/**
	 * Absolute path to the session file.
	 *
	 * @return string
	 */
	private static function get_session_file_path() {
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return '';
		}

		return trailingslashit( $upload_dir['basedir'] ) . self::STATE_DIR . '/' . self::SESSION_FILE;
	}

	/**
	 * Read session from disk.
	 *
	 * @return array
	 */
	private static function read_session_file() {
		$file_path = self::get_session_file_path();
		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			return array();
		}

		$contents = file_get_contents( $file_path );
		if ( empty( $contents ) ) {
			return array();
		}

		$session = json_decode( $contents, true );
		if ( ! is_array( $session ) || empty( $session['migration_token'] ) ) {
			return array();
		}

		return $session;
	}

	/**
	 * Write session to disk.
	 *
	 * @param array $session Session payload.
	 * @return void
	 */
	private static function write_session_file( array $session ) {
		$file_path = self::get_session_file_path();
		if ( empty( $file_path ) ) {
			return;
		}

		$directory = dirname( $file_path );
		if ( ! wp_mkdir_p( $directory ) ) {
			return;
		}

		$encoded = wp_json_encode( $session );
		if ( false === $encoded ) {
			return;
		}

		file_put_contents( $file_path, $encoded, LOCK_EX );
	}
}
