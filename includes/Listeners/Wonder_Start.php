<?php
namespace NewfoldLabs\WP\Module\Migration\Listeners;

use NewfoldLabs\WP\Module\Data\Listeners\Listener;

/**
 * Monitors generic Wonder_Start events
 */
class Wonder_Start extends Listener {
	/**
	 * Register the hooks for the listener
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'pre_update_option_instawp_last_migration_details', array( $this, 'on_update_instawp_last_migration_details' ) );
	}

	/**
	 * Triggers events
	 *
	 * @param array $new_option status of migration
	 */
	public function on_update_instawp_last_migration_details( $new_option ) {
		$value_updated = $new_option['status'];
		if ( 'completed' === $value_updated ) {
			$this->push( 'migration_completed', array() );
		} elseif ( 'failed' === $value_updated ) {
			$this->push( 'migration_failed', array() );
		}
		return $new_option;
	}
}
