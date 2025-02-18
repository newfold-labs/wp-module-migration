<?php

namespace NewfoldLabs\WP\Module\Migration\Steps;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;
use NewfoldLabs\WP\Module\Data\Helpers\Encryption;
use NewfoldLabs\WP\Module\Migration\Services\UtilityService;

class GetInstaWpApiKey extends AbstractStep {
	/**
	 * InstaWP Connect plugin API key used for connecting the instaWP plugin
	 *
	 * @var $insta_api_key
	 */
	private $insta_api_key = '';

	/**
	 * 
	 * $var NewfoldLabs\WP\Module\Data\Helpers\Encryption instance
	 */
	protected $encrypter;

	/**
	 * Construct. Init basic parameters.
	 */
	public function __construct() {
		$this->set_step_slug( 'GetInstaWpApiKey' );
		$this->set_max_retries( 2 );
		$this->encrypter = new Encryption();
	}

	/**
	 * Execute the step.
	 *
	 * @return void
	 */
	protected function run() {
		$this->insta_api_key = $this->encrypter->decrypt( get_option( 'newfold_insta_api_key', false ) );
		if ( ! $this->insta_api_key ) {
			$this->insta_api_key = UtilityService::get_insta_api_key( BRAND_PLUGIN );
			//$this->insta_api_key = false; //TODO: this is only for testing the failing
			if ( $this->insta_api_key ) {
				update_option( 'newfold_insta_api_key', $this->encrypter->encrypt( $this->insta_api_key ) );
				$this->success();
			} else {
				$this->retry();
			}
		}
	}

	/**
	 * Get the InstaWP API key.
	 *
	 * @return string
	 */
	public function get_api_key() {
		$this->run();
		return $this->insta_api_key;
	}
}
