<?php

namespace NewfoldLabs\WP\Module\Migration\Steps;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;

/**
 * Get Speed Index by PageSpeed api for url.
 *
 * @package wp-module-migration
 */
class PageSpeed extends AbstractStep {
	/**
	 * URL to get speed index.
	 *
	 * @var string $url
	 */
	protected $url = '';
	/**
	 * Construct. Init basic parameters.
	 *
	 * @param string $url url to get speed index.
	 * @param string $type type of the step.
	 */
	public function __construct( $url, $type = 'source' ) {
		$step_slug = 'PageSpeed_' . $type;
		$this->set_step_slug( $step_slug );
		$this->set_max_retries( 1 );
		$this->url = $url;
		$this->set_status( $this->statuses['running'] );
		$this->run();
	}

	/**
	 * Execute the step.
	 *
	 * @return void
	 */
	protected function run() {
	}
}
