<?php

namespace NewfoldLabs\WP\Module\Migration\Steps;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;
use InstaWP\Connect\Helpers\Helper;
use NewfoldLabs\WP\Module\Migration\Steps\GetInstaWpApiKey;

class ConnectToInstaWp extends AbstractStep {
	/**
	 * Construct. Init basic parameters.
	 */
	public function __construct() {
		$this->set_step_slug( 'ConnectToInstaWp' );
		$this->set_max_retries( 2 );
	}

	/**
	 * Execute the step.
	 *
	 * @return void
	 */
	protected function run(  ) {
		$instawp_get_key_step = new GetInstaWpApiKey();
		$insta_api_key = $instawp_get_key_step->get_api_key();

		if ( empty( Helper::get_api_key() ) || empty( Helper::get_connect_id() ) ) {
			$api_key          = Helper::get_api_key( false, $insta_api_key );
			$connect_response = Helper::instawp_generate_api_key( $api_key, '', false );
			if ( ! $connect_response ) {
				delete_option( 'instawp_api_key' );
				$this->retry();
			} else {
				$this->success();
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
	public function connect() {
		$this->run();
		return $this->get_status();
	}
}
