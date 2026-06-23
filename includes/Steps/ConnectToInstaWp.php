<?php

namespace NewfoldLabs\WP\Module\Migration\Steps;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;

/**
 * Connection to InstaWp step.
 *
 * @package wp-module-migration
 */
class ConnectToInstaWp extends AbstractStep {
	/**
	 * InstaWP Connect plugin API key used for connecting the instaWP plugin
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
	 * @param string $insta_api_key instawp api key.
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
				if ( ! $this->retry() ) {
					$this->set_response(
						array(
							'message' => esc_html__( 'Migration utility is missing.', 'wp-module-migration' ),
						),
					);
				}
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
			if ( ! $this->retry() ) {
				$error_message = empty( $migration_request['message'] )
					? esc_html__( 'Website could not connect successfully.', 'wp-module-migration' )
					: esc_html( wp_strip_all_tags( (string) $migration_request['message'] ) );

				$this->set_response(
					array(
						'message' => $error_message,
					),
				);
			}
		} else {
			$migration_url = $migration_request['data']['migration_url'] ?? '';
			if ( empty( $migration_url ) || ! filter_var( $migration_url, FILTER_VALIDATE_URL ) ) {
				if ( ! $this->retry() ) {
					$this->set_response(
						array(
							'message' => esc_html__( 'Migration URL could not be generated.', 'wp-module-migration' ),
						),
					);
				}
				return;
			}

			$this->set_data( 'migration_url', $migration_url );
			$this->success();
		}
	}
}
