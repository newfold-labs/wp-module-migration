<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use InstaWP\Connect\Helpers\Helper;
use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;
use NewfoldLabs\WP\Module\Migration\Steps\GetInstaWpApiKey;
use NewfoldLabs\WP\Module\Migration\Steps\InstallActivateInstaWp;
use NewfoldLabs\WP\Module\Migration\Steps\ConnectToInstaWp;
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
	 * Retry count
	 *
	 * @var int $count
	 */
	private $count = 0;

	/**
	 * Set required api keys for insta to initiate the migration
	 */
	public function __construct() {
		update_option( AbstractStep::get_tracking_option_name(), array() );
		$instawp_get_key_step = new GetInstaWpApiKey();
		$instawp_get_key_step->set_status( 'running' );
		$this->insta_api_key  = $instawp_get_key_step->get_api_key();
	}

	/**
	 * Install InstaWP plugin
	 */
	public function install_instawp_connect() {
		$install_activate = new InstallActivateInstaWp();
		$install_activate->set_status( 'running' );
		$installed_activated = $install_activate->install();

		if ( 'success' === $installed_activated ) {
			// Connect the website with InstaWP server
			$connectToInstaWp = new ConnectToInstaWp( $this->insta_api_key );
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
					'Error',
					esc_html__( 'Migration service could not be started.', 'wp-module-migration' ),
					array( 'status' => 400 )
				);
			}
		}
		// Ready to start the migration
		if ( function_exists( 'instawp' ) ) {
			// Check if there is a connect ID
			if ( empty( Helper::get_connect_id() ) ) {
				if ( $this->count < 3 ) {
					++$this->count;
					delete_option( 'instawp_api_options' ); // delete the connection to plugin and website
					sleep( 1 );
					self::install_instawp_connect();
				} else {
					return new \WP_Error( 'Bad request', esc_html__( 'Connect plugin is installed but no connect ID.', 'wp-module-migration' ), array( 'status' => 400 ) );
				}
			}

			return array(
				'message'      => esc_html__( 'Connect plugin is installed and ready to start the migration.', 'wp-module-migration' ),
				'response'     => true,
				'redirect_url' => esc_url( NFD_MIGRATION_PROXY_WORKER . '/' . INSTAWP_MIGRATE_ENDPOINT . '?d_id=' . Helper::get_connect_uuid() ),
			);
		}

		return new \WP_Error(
			'Bad request',
			esc_html__( 'Migration might be finished.', 'wp-module-migration' ),
			array( 'status' => 400 )
		);
	}
}
