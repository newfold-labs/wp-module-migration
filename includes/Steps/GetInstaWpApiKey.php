<?php

namespace NewfoldLabs\WP\Module\Migration\Steps;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;
use NewfoldLabs\WP\Module\Data\Helpers\Encryption;
use NewfoldLabs\WP\Module\Migration\Services\UtilityService;

/**
 * Get InstaWp api key step.
 *
 * @package wp-module-migration
 */
class GetInstaWpApiKey extends AbstractStep {
	/**
	 * InstaWP migration vendor API key.
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
	 * Whether the key came from the saved option instead of a fresh fetch.
	 *
	 * @var bool
	 */
	private $from_cache = false;

	/**
	 * Construct. Init basic parameters.
	 */
	public function __construct() {
		$this->set_step_slug( 'GetInstaWpApiKey' );
		$this->set_max_retries( 2 );
		$this->encrypter = new Encryption();
		$this->set_status( $this->statuses['running'] );
		$this->run();
	}

	/**
	 * Execute the step.
	 *
	 * @return void
	 */
	protected function run() {
		$this->insta_api_key = $this->encrypter->decrypt( get_option( 'newfold_insta_api_key', false ) );
		$this->from_cache    = ! empty( $this->insta_api_key );
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
	public function get_insta_api_key() {
		return $this->insta_api_key;
	}

	/**
	 * Whether the key came from the saved option.
	 *
	 * @return bool
	 */
	public function is_from_cache() {
		return $this->from_cache;
	}

	/**
	 * Fetch a fresh key and save it, keeping the saved one if the fetch fails.
	 *
	 * @return string The fresh key, or an empty string on failure.
	 */
	public function refresh_insta_api_key() {
		$api_key = UtilityService::get_insta_api_key( BRAND_PLUGIN );
		if ( empty( $api_key ) ) {
			return '';
		}

		update_option( 'newfold_insta_api_key', $this->encrypter->encrypt( $api_key ) );
		$this->insta_api_key = $api_key;
		$this->from_cache    = false;

		return $api_key;
	}
}
