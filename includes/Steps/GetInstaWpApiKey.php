<?php

namespace NewfoldLabs\WP\Module\Migration\Steps;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;
use NewfoldLabs\WP\Module\Data\Helpers\Encryption;
use NewfoldLabs\WP\Module\Migration\Services\UtilityService;
use NewfoldLabs\WP\Module\Migration\Services\Tracker;

/**
 * Get InstaWp api key step.
 *
 * @package wp-module-migration
 */
class GetInstaWpApiKey extends AbstractStep {
	/**
	 * InstaWP Connect plugin API key used for connecting the instaWP plugin
	 *
	 * @var $insta_api_key
	 */
	private $insta_api_key = '';

	/**
	 * Encryption instance
	 *
	 * @var NewfoldLabs\WP\Module\Data\Helpers\Encryption instance
	 */
	protected $encrypter;

	/**
	 * Construct. Init basic parameters.
	 *
 	 * @param Tracker $tracker
	 */
	public function __construct( Tracker $tracker ) {
		$this->set_step_slug( 'GetInstaWpApiKey' );
		$this->set_max_retries( 2 );
		$this->encrypter = new Encryption();
		$this->set_tracker( $tracker );
	}

	/**
	 * Execute the step.
	 *
	 * @return void
	 */
	protected function run() {
		$this->tracker->update_track( array( $this->get_step_slug() => array( 'status' => 'running' ) ) );
		$this->insta_api_key = $this->encrypter->decrypt( get_option( 'newfold_insta_api_key', false ) );
		if ( ! $this->insta_api_key ) {
			$this->insta_api_key = UtilityService::get_insta_api_key( BRAND_PLUGIN );
			if ( $this->insta_api_key ) {
				update_option( 'newfold_insta_api_key', $this->encrypter->encrypt( $this->insta_api_key ) );
				$this->success();
			} else {
				$this->retry();
				$this->set_response(
					array(
						'message' => esc_html__( 'Cannot get Api key.', 'wp-module-migration' ),
					),
				);
			}
		} else {
			$this->success();
		}
	}

	/**
	 * Get the InstaWP API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		$this->run();
		$message = isset( $this->get_response()['message'] ) ? $this->get_response()['message'] : '';
		$current = array(
			$this->get_step_slug() => array(
				'status'  => $this->get_status(),
				'intents' => $this->get_retry_count() + 1,
				'message' => $message,
				'data'    => '',
			),
		);
		$this->tracker->update_track( $current );
		return $this->insta_api_key;
	}
}
