<?php
namespace NewfoldLabs\WP\Module\Migration\Listeners;

use NewfoldLabs\WP\Module\Data\Listeners\Listener;
use NewfoldLabs\WP\Module\Migration\Services\Tracker;

/**
 * Monitors InstaWp options update
 */
class InstaWpOptionsUpdates extends Listener {
	/**
	 * Register the hooks for the listener
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'pre_update_option_instawp_last_migration_details', array( $this, 'on_update_instawp_last_migration_details' ), 10, 2 );
		add_filter( 'pre_update_option_instawp_migration_details', array( $this, 'on_update_instawp_migration_details' ), 10, 2 );
	}

	/**
	 * Triggers events
	 *
	 * @param array $new_option status of migration
	 * @param array $old_value previous status of migration
	 */
	public function on_update_instawp_last_migration_details( $new_option, $old_value ) {
		if ( $old_value !== $new_option ) {
			$tracker       = new Tracker();
			$value_updated = $new_option['status'];

			if ( 'completed' === $value_updated ) {
				$tracker->update_track( array( 'LastMigrationDetails' => array( 'status' => 'completed' ) ) );
				$this->push( 'migration_completed', wp_json_encode( $tracker->get_track_content() ) );
			} elseif ( 'failed' === $value_updated ) {
				$tracker->update_track( array( 'LastMigrationDetails' => array( 'status' => 'failed' ) ) );
				$this->push( 'migration_failed', wp_json_encode( $tracker->get_track_content() ) );
			} elseif ( 'aborted' === $value_updated ) {
				$tracker->update_track( array( 'LastMigrationDetails' => array( 'status' => 'aborted' ) ) );
				$this->push( 'migration_aborted', wp_json_encode( $tracker->get_track_content() ) );
			}
		}

		return $new_option;
	}

	/**
	 * Listen instaWp option update to intercept the Push step and track it
	 *
	 * @param array $new_option status of migration
	 * @param array $old_value previous status of migration
	 * @return array
	 */
	public function on_update_instawp_migration_details( $new_option, $old_value ) {
		if ( $old_value !== $new_option ) {
			$tracker = new Tracker();
			$mode    = isset( $new_option['mode'] ) ? $new_option['mode'] : '';
			$status  = isset( $new_option['status'] ) ? $new_option['status'] : '';
			if ( 'push' === $mode && 'initiated' === $status ) {
				$tracker->update_track( array( 'PushingStep' => array( 'status' => 'running' ) ) );
			}
		}
		return $new_option;
	}
}
