<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use InstaWP\Connect\Helpers\Helper;
use NewfoldLabs\WP\Module\Migration\Steps\GetInstaWpApiKey;
use NewfoldLabs\WP\Module\Migration\Steps\InstallActivateInstaWp;

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
		$install_activate = new InstallActivateInstaWp();
		$installed_activated = $install_activate->install();

		if ( 'success' === $installed_activated ) {
			// Connect the website with InstaWP server
			if ( empty( Helper::get_api_key() ) || empty( Helper::get_connect_id() ) ) {
				$api_key          = Helper::get_api_key( false, $this->insta_api_key );
				$connect_response = Helper::instawp_generate_api_key( $api_key, '', false );
				error_log( print_r( $connect_response, true ) );
				if ( ! $connect_response ) {
					return new \WP_Error(
						'Bad request',
						esc_html__( 'Website could not connect successfully.' ),
						array( 'status' => 400 )
					);
				}
			}
			error_log( 'stoppednow' );
			die;
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
						return new \WP_Error( 'Bad request', esc_html__( 'Connect plugin is installed but no connect ID.' ), array( 'status' => 400 ) );
					}
				}
	
				return array(
					'message'      => esc_html__( 'Connect plugin is installed and ready to start the migration.' ),
					'response'     => true,
					'redirect_url' => esc_url( NFD_MIGRATION_PROXY_WORKER . '/' . INSTAWP_MIGRATE_ENDPOINT . '?d_id=' . Helper::get_connect_uuid() ),
				);
			}

		}

		return new \WP_Error(
			'Bad request',
			esc_html__( 'Migration might be finished.' ),
			array( 'status' => 400 )
		);
	}
}
