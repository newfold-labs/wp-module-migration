<?php

namespace NewfoldLabs\WP\Module\Migration\Steps;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;
use InstaWP\Connect\Helpers\Helper;

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
	 * Construct. Init basic parameters.
	 *
	 * @param string $insta_api_key instawp api key.
	 */
	public function __construct( $insta_api_key ) {
		$this->set_step_slug( 'ConnectToInstaWp' );
		$this->set_max_retries( 2 );
		$this->insta_api_key = $insta_api_key;
		$this->set_status( $this->statuses['running'] );
		$this->run();
	}

	/**
	 * Execute the step.
	 *
	 * @return void
	 */
	protected function run() {
		if ( empty( Helper::get_api_key() ) || empty( Helper::get_connect_id() ) ) {
			$api_key          = Helper::get_api_key( false, $this->insta_api_key );
			$connect_response = Helper::instawp_generate_api_key( $api_key, '', false );
			if ( ! $connect_response ) {
				delete_option( 'instawp_api_key' );
				if ( ! $this->retry() ) {
					$this->set_response(
						array(
							'message' => esc_html__( 'Website could not connect successfully.', 'wp-module-migration' ),
						),
					);
				}
			} else {
				$this->success();
			}
		} else {
			$this->success();
		}
	}

	/**
	 * Set the step as successful and store the API key.
	 *
	 * @return void
	 */
	protected function success() {
		parent::success();

		$this->set_data( 'ApiKey', Helper::get_api_key() );
	}
}
