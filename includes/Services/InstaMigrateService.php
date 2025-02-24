<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use InstaWP\Connect\Helpers\Helper;
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
		$instawp_get_key_step = new GetInstaWpApiKey();
		$this->insta_api_key = $instawp_get_key_step->get_api_key();
	}

	/**
	 * Install InstaWP plugin
	 */
	public function install_instawp_connect() {
		$install_activate    = new InstallActivateInstaWp();
		$installed_activated = $install_activate->install();

		if ( 'success' === $installed_activated ) {
			// Connect the website with InstaWP server
			$connectToInstaWp = new ConnectToInstaWp( $this->insta_api_key );
			$connected        = $connectToInstaWp->connect();
			if ( 'success' === $connected ) {
				error_log( 'Ready to start migration' );
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
			// I have a doubt if we would show that the connection plugin cannot be installed, maybe we could mask with an error message like "Connect service could not be initialized."
			// if we use this we can remove the following return error
			return new \WP_Error(
				'Bad request',
				esc_html__( 'Connect plugin could not be installed.', 'wp-module-migration' ),
				array( 'status' => 400 )
			);
		}

		// I am not sure about this, maybe we can remove it and use the wp_error of the else above
		return new \WP_Error(
			'Bad request',
			esc_html__( 'Migration might be finished.' ),
			array( 'status' => 400 )
		);
	}
}
