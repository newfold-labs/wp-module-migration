<?php

namespace NewfoldLabs\WP\Module\Migration\Steps;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;
use InstaWP\Connect\Helpers\Installer;

/**
 * Install and activate InstaWp step.
 *
 * @package wp-module-migration
 */
class InstallActivateInstaWp extends AbstractStep {

	/**
	 * InstaWP Connect plugin slug used for installing the instaWP plugin once
	 *
	 * @var $connect_plugin_slug
	 */
	private $connect_plugin_slug = 'instawp-connect';

	/**
	 * Construct. Init basic parameters.
	 */
	public function __construct() {
		$this->set_step_slug( 'InstallInstaWp' );
		$this->set_max_retries( 2 );
	}

	/**
	 * Execute the step.
	 *
	 * @return void
	 */
	protected function run() {
		$this->track_step( $this->get_step_slug(), 'running' );

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

			if ( $response[0]['success'] && function_exists( 'instawp' ) ) {
				$this->success();
			} else {
				$this->retry();
				$message = $response[0]['message'] ? $response[0]['message'] : __( 'Failed to install and activate the plugin', 'wp-module-migration' );
				$this->set_response( array( 'message' => $message ) );
			}
		} else {
			$this->success();
		}
	}

	/**
	 * Install InstaWP API key.
	 *
	 * @return string
	 */
	public function install() {
		$this->run();
		$message = isset( $this->get_response()['message'] ) ? $this->get_response()['message'] : '';
		$this->track_step( $this->get_step_slug(), $this->get_status(), $message );
		return $this->get_status();
	}
}
