<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use NewfoldLabs\WP\Module\Migration\Steps\GetInstaWpApiKey;
use NewfoldLabs\WP\Module\Migration\Steps\ConnectToInstaWp;
use NewfoldLabs\WP\Module\Migration\Services\Tracker;
/**
 * Class InstaMigrateService
 */
class InstaMigrateService {

	/**
	 * InstaWP migration vendor API key.
	 *
	 * @var string $insta_api_key
	 */
	private $insta_api_key = '';

	/**
	 * Tracker class instance.
	 *
	 * @var Tracker $tracker
	 */
	private $tracker;

	/**
	 * Set required API keys for insta to initiate the migration
	 */
	public function __construct() {
		$this->tracker = new Tracker();
		$this->tracker->reset();
	}

	/**
	 * Get InstaWP API key and request a migration URL.
	 */
	public function run() {

		delete_option( 'nfd_migration_status_sent' );

		$instawp_get_key_step = new GetInstaWpApiKey();
		EventService::send_application_event(
			'migration_get_vendor_api_key',
			array(
				'status' => $instawp_get_key_step->get_status(),
			)
		);
		$this->tracker->update_track( $instawp_get_key_step );
		if ( ! $instawp_get_key_step->failed() ) {
			$this->insta_api_key = $instawp_get_key_step->get_insta_api_key();
		} else {
			return new \WP_Error(
				'Bad request',
				esc_html__( 'Cannot get api key.', 'wp-module-migration' ),
				array( 'status' => 400 )
			);
		}

		$connect_to_instawp = new ConnectToInstaWp( $this->insta_api_key );
		$this->tracker->update_track( $connect_to_instawp );
		EventService::send_application_event(
			'migration_vendor_plugin_connect',
			$this->get_connect_event_payload( $connect_to_instawp )
		);

		if ( ! $connect_to_instawp->failed() ) {
			$migration_url = $connect_to_instawp->get_data( 'migration_url' );
			if ( empty( $migration_url ) ) {
				return new \WP_Error(
					'bad_request',
					esc_html__( 'Migration URL could not be generated.', 'wp-module-migration' ),
					array( 'status' => 400 )
				);
			}

			$migration_url = $this->normalize_migration_redirect_url( $migration_url );
			$redirect_url  = apply_filters( 'nfd_migration_redirect_url', apply_filters( 'nfd_build_url', $migration_url ) );

			return array(
				'message'      => $this->get_step_message(
					$connect_to_instawp,
					esc_html__( 'Ready to start the migration.', 'wp-module-migration' )
				),
				'response'     => true,
				'redirect_url' => esc_url_raw( $redirect_url ),
			);
		}

		return new \WP_Error(
			'bad_request',
			$this->get_step_message(
				$connect_to_instawp,
				esc_html__( 'Website could not connect successfully.', 'wp-module-migration' )
			),
			array( 'status' => 400 )
		);
	}

	/**
	 * Rewrite InstaWP-hosted migration URLs to the brand proxy worker host.
	 *
	 * v3 returns app.instawp.io URLs; rebuild the legacy proxy URL with g_id and locale.
	 * v4 returns migrate.instawp.io (e.g. /start?t=...); swap the host and keep path/query.
	 *
	 * @param string $migration_url Migration URL returned by InstaWP utilities.
	 * @return string
	 */
	private function normalize_migration_redirect_url( $migration_url ) {
		if ( ! defined( 'NFD_MIGRATION_PROXY_WORKER' ) || empty( $migration_url ) ) {
			return $migration_url;
		}

		$proxy_parts = wp_parse_url( NFD_MIGRATION_PROXY_WORKER );
		$url_parts   = wp_parse_url( $migration_url );

		if ( empty( $proxy_parts['host'] ) || empty( $url_parts['host'] ) ) {
			return $migration_url;
		}

		if ( $url_parts['host'] === $proxy_parts['host'] ) {
			return $migration_url;
		}

		$v3_hosts = apply_filters(
			'nfd_migration_instawp_v3_redirect_hosts',
			array( 'app.instawp.io' )
		);

		if ( in_array( $url_parts['host'], $v3_hosts, true ) ) {
			$v3_redirect_url = $this->build_v3_proxy_redirect_url();
			if ( ! empty( $v3_redirect_url ) ) {
				return $v3_redirect_url;
			}
		}

		$instawp_hosts = apply_filters(
			'nfd_migration_instawp_redirect_hosts',
			array( 'migrate.instawp.io', 'app.instawp.io' )
		);

		if ( ! in_array( $url_parts['host'], $instawp_hosts, true ) ) {
			return $migration_url;
		}

		return $this->rewrite_url_host( $migration_url, $proxy_parts );
	}

	/**
	 * Build the v3 brand-proxy redirect URL (legacy connect flow).
	 *
	 * @return string
	 */
	private function build_v3_proxy_redirect_url() {
		if ( ! defined( 'INSTAWP_MIGRATE_ENDPOINT' ) ) {
			return '';
		}

		$group_id = $this->get_migration_group_id();
		if ( empty( $group_id ) ) {
			return '';
		}

		return sprintf(
			'%s/%s?g_id=%s&locale=%s',
			untrailingslashit( NFD_MIGRATION_PROXY_WORKER ),
			INSTAWP_MIGRATE_ENDPOINT,
			rawurlencode( $group_id ),
			rawurlencode( get_locale() )
		);
	}

	/**
	 * Read the migration group id saved during the v3 connect step.
	 *
	 * @return string
	 */
	private function get_migration_group_id() {
		if ( class_exists( '\IWP_Migration_Utils' ) ) {
			$group_id = \IWP_Migration_Utils::get_mig_gid();
			if ( ! empty( $group_id ) ) {
				return $group_id;
			}
		}

		$api_options = get_option( 'instawp_api_options', array() );
		if ( is_array( $api_options ) && ! empty( $api_options['group_uuid'] ) ) {
			return $api_options['group_uuid'];
		}

		return '';
	}

	/**
	 * Replace a URL host with the brand migration proxy host.
	 *
	 * @param string $url         Original URL.
	 * @param array  $proxy_parts Parsed brand proxy URL parts.
	 * @return string
	 */
	private function rewrite_url_host( $url, array $proxy_parts ) {
		$url_parts = wp_parse_url( $url );

		if ( empty( $url_parts['host'] ) ) {
			return $url;
		}

		$url_parts['scheme'] = $proxy_parts['scheme'] ?? 'https';
		$url_parts['host']   = $proxy_parts['host'];

		if ( isset( $proxy_parts['port'] ) ) {
			$url_parts['port'] = $proxy_parts['port'];
		} else {
			unset( $url_parts['port'] );
		}

		return $this->build_url_from_parts( $url_parts );
	}

	/**
	 * Build a URL from parsed parts.
	 *
	 * @param array $parts Parsed URL components.
	 * @return string
	 */
	private function build_url_from_parts( array $parts ) {
		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
		$host   = $parts['host'] ?? '';
		$port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
		$user   = $parts['user'] ?? '';
		$pass   = $parts['pass'] ?? '';
		$auth   = '';

		if ( $user ) {
			$auth = $user . ( '' !== $pass ? ':' . $pass : '' ) . '@';
		}

		$path     = $parts['path'] ?? '';
		$query    = isset( $parts['query'] ) ? '?' . $parts['query'] : '';
		$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';

		return $scheme . $auth . $host . $port . $path . $query . $fragment;
	}

	/**
	 * Build telemetry payload for the connect step event.
	 *
	 * @param ConnectToInstaWp $connect_to_instawp Connect step instance.
	 * @return array
	 */
	private function get_connect_event_payload( ConnectToInstaWp $connect_to_instawp ) {
		$payload = array(
			'status' => $connect_to_instawp->get_status(),
		);

		$error_code = $connect_to_instawp->get_response()['error_code'] ?? '';
		if ( ! empty( $error_code ) ) {
			$payload['error_code'] = $error_code;
		}

		return $payload;
	}

	/**
	 * Read a sanitized step message when available.
	 *
	 * @param ConnectToInstaWp $step           Step instance.
	 * @param string           $fallback_message Default message.
	 * @return string
	 */
	private function get_step_message( ConnectToInstaWp $step, $fallback_message ) {
		$message = $step->get_response()['message'] ?? '';
		return ! empty( $message ) ? $message : $fallback_message;
	}
}
