<?php

namespace NewfoldLabs\WP\Module\Migration\Steps;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;

/**
 * Requests a migration URL from InstaWP migration utilities.
 *
 * This step runs synchronously inside REST and option-update hooks. The utility may
 * perform multiple outbound requests (engine lookup, plugin install, migration request)
 * with per-request timeouts up to several minutes.
 *
 * @package wp-module-migration
 */
class ConnectToInstaWp extends AbstractStep {
	/**
	 * InstaWP migration vendor API key.
	 *
	 * @var $insta_api_key
	 */
	private $insta_api_key = '';

	/**
	 * The current brand/plugin identifier, used when sending the white-label slug to InstaWP.
	 * Defaults to 'bluehost' if the BRAND_PLUGIN constant is not defined.
	 *
	 * @var string
	 */
	public $brand = '';

	/**
	 * Construct. Init basic parameters.
	 *
	 * @param string $insta_api_key InstaWP migration vendor API key.
	 */
	public function __construct( $insta_api_key ) {
		$this->set_step_slug( 'ConnectToInstaWp' );
		$this->set_max_retries( 2 );
		$this->insta_api_key = $insta_api_key;
		if ( defined( 'BRAND_PLUGIN' ) ) {
			$this->brand = BRAND_PLUGIN;
		} elseif (
		defined( 'NFD_MIGRATION_BRAND_WHITELIST' )
		&& is_array( NFD_MIGRATION_BRAND_WHITELIST )
		&& ! empty( NFD_MIGRATION_BRAND_WHITELIST )
		) {
			$whitelist   = NFD_MIGRATION_BRAND_WHITELIST;
			$this->brand = $whitelist[0];
		} else {
			$this->brand = 'bluehost';
		}
		$this->set_status( $this->statuses['running'] );
		$this->run();
	}

	/**
	 * Execute the step.
	 *
	 * @return void
	 */
	protected function run() {
		if ( ! class_exists( '\IWP_Migration_Utils' ) ) {
			$utility_path = dirname( __DIR__, 2 ) . '/utils/iwp-migration-utils.php';
			if ( ! file_exists( $utility_path ) ) {
				$this->failure();
				$this->set_response(
					array(
						'message' => esc_html__( 'Migration utility is missing.', 'wp-module-migration' ),
					)
				);
				return;
			}

			require_once $utility_path;
		}

		$migration_request = \IWP_Migration_Utils::instaMigrateRequest(
			$this->insta_api_key,
			$this->brand,
			get_locale()
		);

		if ( ! is_array( $migration_request ) || empty( $migration_request['success'] ) ) {
			$this->retry();
			if ( $this->failed() ) {
				$this->log_upstream_error( $migration_request['message'] ?? '' );
				$this->set_response(
					array(
						'message'    => esc_html__( 'Website could not connect successfully.', 'wp-module-migration' ),
						'error_code' => $this->get_upstream_error_code( $migration_request['message'] ?? '' ),
					)
				);
			}
			return;
		}

		$migration_url = $migration_request['data']['migration_url'] ?? '';
		if ( empty( $migration_url ) || ! filter_var( $migration_url, FILTER_VALIDATE_URL ) ) {
			$this->retry();
			if ( $this->failed() ) {
				$this->set_response(
					array(
						'message' => esc_html__( 'Migration URL could not be generated.', 'wp-module-migration' ),
					)
				);
			}
			return;
		}

		$this->set_data( 'migration_url', $migration_url );
		$this->set_response(
			array(
				'message' => $this->sanitize_success_message( $migration_request['message'] ?? '' ),
			)
		);
		$this->success();
	}

	/**
	 * Log upstream InstaWP error details without exposing them to end users.
	 *
	 * @param mixed $message Upstream error message.
	 * @return void
	 */
	private function log_upstream_error( $message ) {
		$message = wp_strip_all_tags( (string) $message );
		if ( empty( $message ) ) {
			return;
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( 'wp-module-migration: InstaWP connect failed: ' . $message );
	}

	/**
	 * Build a short non-PII error code for telemetry from an upstream message.
	 *
	 * @param mixed $message Upstream error message.
	 * @return string
	 */
	private function get_upstream_error_code( $message ) {
		$message = wp_strip_all_tags( (string) $message );
		if ( empty( $message ) ) {
			return '';
		}

		return substr( hash( 'sha256', $message ), 0, 8 );
	}

	/**
	 * Sanitize a user-facing success message from the utility response.
	 *
	 * @param mixed $message Upstream success message.
	 * @return string
	 */
	private function sanitize_success_message( $message ) {
		$message = wp_strip_all_tags( (string) $message );
		if ( empty( $message ) ) {
			return esc_html__( 'Ready to start the migration.', 'wp-module-migration' );
		}

		return esc_html( $message );
	}
}
