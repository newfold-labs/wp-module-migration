<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use InstaWP\Connect\Helpers\Helper;
use InstaWP\Connect\Helpers\Installer;

/**
 * InstaWP migrate service
 */
class InstaMigrateService {

	/**
	 * InstaWP Api key to connect with it
	 *
	 * @var $api_key
	 */
	private $api_key;
	/**
	 * InstaWP Api URL
	 *
	 * @var $api_url
	 */
	private $api_url;
	/**
	 * Connect id fetching from instaWP once connection is successful
	 *
	 * @var $connect_id
	 */
	private $connect_id;
	/**
	 * Connect uuid fetching from instaWP once connection is successful
	 *
	 * @var $connect_uuid
	 */
	private $connect_uuid;
	/**
	 * InstaWP Connect plugin slug used for installing the instaWP plugin once
	 *
	 * @var $connect_plugin_slug
	 */
	private $connect_plugin_slug = 'instawp-connect';
	/**
	 * Redirect url once website is connected to start migration
	 *
	 * @var $redirect_url
	 */
	private $redirect_url;

	function __construct() {

		Helper::set_api_domain( INSTAWP_API_DOMAIN );
			$this->api_key      = Helper::get_api_key( false, INSTAWP_API_KEY );
			$this->api_url      = Helper::get_api_domain();
			$this->connect_id   = Helper::get_connect_id();
			$this->connect_uuid = Helper::get_connect_uuid();
			$this->redirect_url = esc_url( $this->api_url . '/' . INSTAWP_MIGRATE_ENDPOINT . '?d_id=' . $this->connect_uuid );
	}
	/**
	 * Install InstaWP plugin
	 */
	public function InstallInstaWpConnect() {
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

			$connect_response = Helper::instawp_generate_api_key( $this->api_key );

			if ( ! $connect_response ) {
				return new \WP_Error(
					'Bad request',
					esc_html__( 'Website could not connect successfully.' ),
					array( 'status' => 400 )
				);
			}
		}

		// Ready to start the migration
		if ( function_exists( 'instawp' ) && ! empty( $this->connect_id ) ) {
			return wp_send_json_success(
				array(
					'message'      => esc_html__( 'Ready to start migration.' ),
					'response'     => true,
					'redirect_url' => $this->redirect_url,
				)
			);
		}

		return new \WP_Error(
			'Bad request',
			esc_html__( 'Migration might be finished.' ),
			array( 'status' => 400 )
		);
	}

	/**
	 * Updates the values in db
	 *
	 * @param string $key keyname
	 * @param string $value  value
	 */
	private function set_api_data( $key, $value ) {

		$api_options = get_option( 'instawp_api_options', array() );

		if ( ! is_array( $api_options ) || empty( $api_options ) ) {
			$api_options = array();
		}

		$api_options[ $key ] = $value;

		return update_option( 'instawp_api_options', $api_options );
	}
	/**
	 * Gets the values from db
	 *
	 * @param string $key keyname
	 */
	private function get_api_data( $key = 'api_key' ) {

		$api_options = get_option( 'instawp_api_options', array() );
		$value       = '';

		if ( ( ! is_array( $api_options ) || empty( $api_options ) ) && 'api_key' !== $key && 'api_url' !== $key ) {
			return $value;
		}

		if ( isset( $api_options[ $key ] ) ) {
			$value = $api_options[ $key ];
		}

		// Check api_key && ENV
		if ( 'api_key' === $key && empty( $value ) ) {
			$env_file = ABSPATH . '.env';

			if ( file_exists( $env_file ) && is_readable( $env_file ) ) {
				$env_data = parse_ini_file( ABSPATH . '.env' );
				$value    = isset( $env_data['INSTAWP_API_KEY'] ) ? sanitize_text_field( $env_data['INSTAWP_API_KEY'] ) : $value;
			}
		}

		// Check api_key && constant
		if ( 'api_key' === $key && empty( $value ) ) {
			$value = defined( 'INSTAWP_API_KEY' ) ? INSTAWP_API_KEY : $value;
		}

		// Check api_url && constant
		if ( 'api_url' === $key ) {
			$value = defined( 'INSTAWP_ENVIRONMENT' ) ? 'https://' . INSTAWP_ENVIRONMENT . '.instawp.io' : $value;
			$value = empty( $value ) ? 'https://app.instawp.io' : $value;

			$this->set_api_data( 'api_url', $value );
		}

		return $value;
	}
}
