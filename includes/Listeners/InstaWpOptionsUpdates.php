<?php
namespace NewfoldLabs\WP\Module\Migration\Listeners;

use NewfoldLabs\WP\Module\Data\Listeners\Listener;
use NewfoldLabs\WP\Module\Migration\Services\Tracker;
use NewfoldLabs\WP\Module\Migration\Steps\Push;
use NewfoldLabs\WP\Module\Migration\Steps\LastStep;

/**
 * Monitors InstaWp options update
 */
class InstaWpOptionsUpdates extends Listener {
	/**
	 * Tracker class instance.
	 *
	 * @var Tracker $tracker
	 */
	public $tracker;
	/**
	 * Register the hooks for the listener
	 *
	 * @return void
	 */
	public function register_hooks() {
		$this->tracker = new Tracker();
		add_filter( 'pre_update_option_instawp_last_migration_details', array( $this, 'on_update_instawp_last_migration_details' ), 10, 2 );
		add_filter( 'pre_update_option_instawp_migration_details', array( $this, 'on_update_instawp_migration_details' ), 10, 2 );
	}

	/**
	 * Triggers events
	 *
	 * @param array $new_value status of migration
	 * @param array $old_value previous status of migration
	 */
	public function on_update_instawp_last_migration_details( $new_value, $old_value ) {
		if ( $old_value !== $new_value ) {
			$value_updated = $new_value['status'];

			if ( 'completed' === $value_updated ) {
				$migration_complete = new LastStep();
				$migration_complete->set_status( $migration_complete->statuses['completed'] );
				$this->tracker->update_track( $migration_complete );
				$this->push( 'migration_completed', wp_json_encode( $this->tracker->get_track_content() ) );
			} elseif ( 'failed' === $value_updated ) {
				$migration_complete = new LastStep();
				$migration_complete->set_status( $migration_complete->statuses['failed'] );
				$this->tracker->update_track( $migration_complete );
				$this->push( 'migration_failed', wp_json_encode( $this->tracker->get_track_content() ) );
			} elseif ( 'aborted' === $value_updated ) {
				$migration_complete = new LastStep();
				$migration_complete->set_status( $migration_complete->statuses['aborted'] );
				$this->tracker->update_track( $migration_complete );
				$this->push( 'migration_aborted', wp_json_encode( $this->tracker->get_track_content() ) );
			}

			$push = new Push();
			$push->set_status( $push->statuses['completed'] );
			$this->tracker->update_track( $push );
		}

		return $new_value;
	}

	/**
	 * Listen instaWp option update to intercept the Push step and track it
	 *
	 * @param array $new_value status of migration
	 * @param array $old_value previous status of migration
	 * @return array
	 */
	public function on_update_instawp_migration_details( $new_value, $old_value ) {
		if ( $old_value !== $new_value ) {
			$mode    = isset( $new_value['mode'] ) ? $new_value['mode'] : '';
			$status  = isset( $new_value['status'] ) ? $new_value['status'] : '';
			if ( 'push' === $mode && 'initiated' === $status ) {
				$push = new Push();
				$this->tracker->update_track( $push );
			}
		}
		return $new_value;
	}
}
