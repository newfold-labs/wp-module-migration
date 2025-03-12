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
			$tracker          = new Tracker();
			$value_updated    = $new_option['status'];
			$track_content    = $tracker->get_track_content();
			$send_final_event = false;

			if ( 'completed' === $value_updated ) {
				$this->push( 'migration_completed', array() );
				$tracker->update_track( array( 'LastMigrationDetails' => array( 'status' => 'completed' ) ) );
				$send_final_event = true;
			} elseif ( 'failed' === $value_updated ) {
				$this->push( 'migration_failed', array() );
				$tracker->update_track( array( 'LastMigrationDetails' => array( 'status' => 'failed' ) ) );
				$send_final_event = true;
			} elseif ( 'aborted' === $value_updated ) {
				$this->push( 'migration_aborted', array() );
				$tracker->update_track( array( 'LastMigrationDetails' => array( 'status' => 'aborted' ) ) );
				$send_final_event = true;
			}

			if ( $send_final_event && ! empty( $track_content ) ) {
				$this->push( 'migration_completed', wp_json_encode( $track_content ) );
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
