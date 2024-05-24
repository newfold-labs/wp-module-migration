<?php

use NewfoldLabs\WP\Module\Data\Listeners\Listener;


class Wonder_start extends Listener {
    /**
	 * Register the hooks for the listener
	 *
	 * @return void
	 */
	public function register_hooks() {
        add_action( 'pre_update_option_instawp_last_migration_details', array( $this, 'on_update_instawp_last_migration_details' ), 10, 1 );
	}

    /**
	 * triggers events
	 *
	 * @param array $new_option status of migration
	 */
	public function on_update_instawp_last_migration_details( $new_option ) {
		$value_updated = $new_option['status'];
		if ( 'completed' === $value_updated ) {
			$event = array(
				// 'category' => 'wonder_start',
				'action'   => 'migration_completed',
				'data'     => array(),
			);
			// EventService::send( $event );
			$this->push('migration_completed', array());
		} elseif ( 'failed' === $value_updated ) {
			$event = array(
				'category' => 'wonder_start',
				'action'   => 'migration_failed',
				'data'     => array(),
			);
			// EventService::send( $event );
			$this->push('migration_failed', array());
		}
		return $new_option;
	}
}
