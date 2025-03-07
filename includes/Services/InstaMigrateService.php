<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use InstaWP\Connect\Helpers\Helper;
use NewfoldLabs\WP\Module\Migration\Steps\GetInstaWpApiKey;
use NewfoldLabs\WP\Module\Migration\Steps\InstallActivateInstaWp;
use NewfoldLabs\WP\Module\Migration\Steps\ConnectToInstaWp;
use NewfoldLabs\WP\Module\Migration\Services\Tracker;
/**
 * Class InstaMigrateService
 */
class InstaMigrateService {

	/**
	 * InstaWP Connect plugin API key used for connecting the instaWP plugin
	 *
	 * @var $insta_api_key
	 */
	private $insta_api_key = '';

	/**
	 * Tracker class instance.
	 *
	 * @var Tracker $tracker
	 */
	private $tracker;

	/**
	 * Set required api keys for insta to initiate the migration
	 */
	public function __construct() {
		$this->tracker = new Tracker();
		$this->tracker->reset_track_file();
		$instawp_get_key_step = new GetInstaWpApiKey( $this->tracker );
		$instawp_get_key_step->set_status( 'running' );
		$this->insta_api_key = $instawp_get_key_step->get_api_key();

		add_filter( 'pre_update_option_instawp_migration_details', array( $this, 'on_update_instawp_migration_details' ), 10, 2 );
	}

	/**
	 * Install InstaWP plugin
	 */
	public function install_instawp_connect() {
		$install_activate = new InstallActivateInstaWp( $this->tracker );
		$install_activate->set_status( 'running' );
		$installed_activated = $install_activate->install();

		if ( 'success' === $installed_activated ) {
			// Connect the website with InstaWP server
			$connectToInstaWp = new ConnectToInstaWp( $this->insta_api_key, $this->tracker );
			$connectToInstaWp->set_status( 'running' );
			$connected = $connectToInstaWp->connect();
			if ( 'success' === $connected ) {
				return array(
					'message'      => esc_html__( 'Connect plugin is installed and ready to start the migration.', 'wp-module-migration' ),
					'response'     => true,
					'redirect_url' => esc_url( NFD_MIGRATION_PROXY_WORKER . '/' . INSTAWP_MIGRATE_ENDPOINT . '?d_id=' . Helper::get_connect_uuid() ),
				);
			} else {
				return new \WP_Error(
					'Bad request',
					esc_html__( 'Website could not connect successfully.', 'wp-module-migration' ),
					array( 'status' => 400 )
				);
			}
		} else {
			return new \WP_Error(
				'Error',
				esc_html__( 'Migration service could not be started.', 'wp-module-migration' ),
				array( 'status' => 400 )
			);
		}
	}
	/**
	 * Listen instaWp option update to intercept the Push step and track it
	 *
	 * @param array $new_option status of migration
	 * @param array $old_value previous status of migration
	 * @return array
	 */
	public function on_update_instawp_migration_details( $new_option, $old_value ) {
		if ( $old_value !== $new_option ) {
			$mode   = isset( $new_option['mode'] ) ? $new_option['mode'] : '';
			$status = isset( $new_option['status'] ) ? $new_option['status'] : '';
			if ( 'push' === $mode && 'initiated' === $status ) {
				$this->tracker->update_track( array( 'pushingStep' => array( 'status' => 'running' ) ) );
			}
		}
		return $new_option;
	}
}
