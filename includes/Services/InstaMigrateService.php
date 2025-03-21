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
		$this->tracker->reset();
	}

	/**
	 * Get Insta Wp api key, Install InstaWP plugin and connect to it
	 */
	public function run() {

		$instawp_get_key_step = new GetInstaWpApiKey();
		$this->tracker->update_track( $instawp_get_key_step );
		if ( ! $instawp_get_key_step->failed() ) {
			$this->insta_api_key = $instawp_get_key_step->get_data( 'insta_api_key' );
		} else {
			return new \WP_Error(
				'Bad request',
				esc_html__( 'Cannot get api key.', 'wp-module-migration' ),
				array( 'status' => 400 )
			);
		}

		$install_activate = new InstallActivateInstaWp();
		$this->tracker->update_track( $install_activate );
		if ( ! $install_activate->failed() ) {
			$connectToInstaWp = new ConnectToInstaWp( $this->insta_api_key );
			$this->tracker->update_track( $connectToInstaWp );
			if ( ! $connectToInstaWp->failed() ) {
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
}
