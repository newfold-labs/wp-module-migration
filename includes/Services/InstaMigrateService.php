<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use InstaWP\Connect\Helpers\Helper;
use InstaWP\Connect\Helpers\Installer;
use NewfoldLabs\WP\Module\Migration\Services\UtilityService;

/**
 * Class InstaMigrateService
 */
class InstaMigrateService {

	/**
	 * InstaWP Connect plugin slug used for installing the instaWP plugin once
	 *
	 * @var $connect_plugin_slug
	 */
	private $connect_plugin_slug = 'instawp-connect';

	/**
	 * InstaWP Connect plugin API key used for connecting the instaWP plugin
	 *
	 * @var $insta_api_key
	 */
	private $insta_api_key = '';
	/**
	 * Set required api keys for insta to initiate the migration
	 */
	public function __construct() {
			$this->insta_api_key = UtilityService::get_insta_api_key( BRAND_PLUGIN );
	}

	/**
	 * Install InstaWP plugin
	 */
	public function install_instawp_connect() {
		if ( ! function_exists( 'get_plugins' ) || ! function_exists( 'get_mu_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		// Install and activate the plugin
		if ( ! is_plugin_active( sprintf( '%1$s/%1$s.php', $this->connect_plugin_slug ) ) ) {
			$params    = array(
				array(
					'slug'     => 'instawp-connect',
					'type'     => 'plugin',
					'activate' => true,
				),
			);
			$installer = new Installer( $params );
			$response  = $installer->start();
		}

		// Connect the website with InstaWP server
		if ( empty( Helper::get_api_key() ) ) {
			$api_key          = Helper::get_api_key( false, $this->insta_api_key );
			$connect_response = Helper::instawp_generate_api_key( $api_key );

			if ( ! $connect_response ) {
				return new \WP_Error(
					'Bad request',
					esc_html__( 'Website could not connect successfully.' ),
					array( 'status' => 400 )
				);
			}
		}
		// Ready to start the migration
		if ( function_exists( 'instawp' ) ) {
			// Check if there is a connect ID
			if ( empty( Helper::get_connect_id() ) ) {
				return new \WP_Error( 'Bad request', esc_html__( 'Connect plugin is installed but no connect ID.' ), array( 'status' => 400 ) );
			}

			return wp_send_json_success(
				array(
					'message'      => esc_html__( 'Connect plugin is installed and ready to start the migration.' ),
					'response'     => true,
					'redirect_url' => esc_url( NFD_MIGRATION_PROXY_WORKER . '/' . INSTAWP_MIGRATE_ENDPOINT . '?d_id=' . Helper::get_connect_uuid() ),
				)
			);
		}

		return new \WP_Error(
			'Bad request',
			esc_html__( 'Migration might be finished.' ),
			array( 'status' => 400 )
		);
	}
}
