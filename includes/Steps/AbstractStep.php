<?php
namespace NewfoldLabs\WP\Module\Migration\Steps;

/**
 * Abstract class representing a step in the migration process.
 *
 * This class provides a base implementation for all migration steps.
 * It should be extended by specific step classes to define the actual
 * step logic.
 *
 * @package wp-module-migration
 */
abstract class AbstractStep {
	/**
	 * The actual retry count, it will increment on each retry.
	 *
	 * @var int $retry_count
	 */
	private $retry_count = 0;

	/**
	 * The maximum retries possible.
	 *
	 * @var int $max_retries
	 */
	private $max_retries = 0;

	/**
	 * Status of the current step, it could be success, failed, ending
	 *
	 * @var string $status
	 */
	private $status;

	/**
	 * The current step slug.
	 *
	 * @var string $step_slug
	 */
	private $step_slug = '';

	/**
	 * Run the main code for.
	 */
	protected function run() {}

	/**
	 * Set the step status as successful & reset the retry count to 0 and print success log.
	 */
	protected function success() {
		$this->set_status( 'success' );
		$this->set_retry_count( 0 );
		$intent = $this->get_retry_count() + 1;
		$this->log( 'Operation Completed with Success on intent ' . $intent );
	}
	/**
	 * Set the step status as failed & reset the retry count to 0 and print failed log.
	 */
	protected function failure() {
		$this->set_status( 'failed' );
		$this->set_retry_count( 0 );
		$this->log( 'Failed with ' . $this->get_max_retries() . ' intents' );
	}

	/**
	 * Retry the run method.
	 *
	 * @return bool;
	 */
	protected function retry() {
		$count = $this->retry_count + 1;
		if ( $count > $this->get_max_retries() ) {
			$this->failure();
			return false;
		}

		sleep( 1 );

		$this->set_retry_count( $count );

		$this->log( 'Intent ' . $count );

		$this->run();
	}

	/**
	 * Print log
	 *
	 * @param mixed $msg the message to print.
	 */
	protected function log( $msg ) {
		error_log( '----------------------------' );
		error_log( '[' . $this->step_slug . ']' );
		error_log( print_r( $msg, true ) );
		error_log( '----------------------------' );
	}


	/**
	 * Set the current step slug
	 *
	 * @param string $slug the retry count value.
	 */
	protected function set_step_slug( string $slug ) {
		$this->step_slug = empty( $slug ) ? 'generic' : $slug;
	}

	/**
	 * Set the max retry value
	 *
	 * @param int $max the max number of retries.
	 */
	protected function set_max_retries( int $max ) {
		$this->max_retries = $max < 0 ? 0 : $max;
	}

	/**
	 * Set the retry value
	 *
	 * @param int $retry_count the retry count value.
	 */
	protected function set_retry_count( int $retry_count ) {
		$this->retry_count = $retry_count > $this->max_retries ? $this->max_retries : $retry_count;
	}
	/**
	 * Get the actual retry count
	 *
	 * @return int
	 */
	protected function get_retry_count() {
		return (int) $this->retry_count;
	}
	/**
	 * Get the max retries count
	 *
	 * @return int
	 */
	protected function get_max_retries() {
		return (int) $this->max_retries;
	}
	/**
	 * Get the status
	 *
	 * @return int
	 */
	protected function get_status() {
		return $this->status;
	}
	/**
	 * Set the status
	 *
	 * @param string $status the status;
	 */
	protected function set_status( $status ) {
		$this->status = $status;
	}
}
