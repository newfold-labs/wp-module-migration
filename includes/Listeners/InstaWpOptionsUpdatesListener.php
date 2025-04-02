<?php
namespace NewfoldLabs\WP\Module\Migration\Listeners;

use NewfoldLabs\WP\Module\Migration\Data\Events;
use NewfoldLabs\WP\Module\Migration\Services\EventService;
use NewfoldLabs\WP\Module\Migration\Services\UtilityService;
use NewfoldLabs\WP\Module\Migration\Services\Tracker;
use NewfoldLabs\WP\Module\Migration\Steps\Push;
use NewfoldLabs\WP\Module\Migration\Steps\LastStep;

/**
 * Monitors InstaWp options update
 */
class InstaWpOptionsUpdatesListener {
	/**
	 * Tracker class instance.
	 *
	 * @var Tracker $tracker
	 */
	public $tracker;

	/**
	 * InstaWpOptionsUpdatesListener constructor.
	 */
	public function __construct() {
		$this->register_hooks();
	}
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
	 * Push event with tracking file content.
	 *
	 * @param string $action action/key for the event.
	 * @param array  $data   data to be sent with the event.
	 * @return WP_REST_Response|WP_Error
	 */
	public function push( $action, $data ) {
		return EventService::send(
			array(
				'category' => Events::get_category()[0],
				'action'   => $action,
				'data'     => $data,
			)
		);
	}

	/**
	 * Triggers events
	 *
	 * @param array $new_value status of migration
	 * @param array $old_value previous status of migration
	 */
	public function on_update_instawp_last_migration_details( $new_value, $old_value ) {
		if ( $old_value !== $new_value ) {
			$migrate_group_uuid = isset( $new_value['migrate_group_uuid'] ) ? $new_value['migrate_group_uuid'] : '';
			if ( ! empty( $migrate_group_uuid ) ) {
				$token = UtilityService::get_insta_api_key( BRAND_PLUGIN );
				if ( $token && $migrate_group_uuid ) {
					$response = wp_remote_get(
						'https://app.instawp.io/api/v2/migrates-v3/status/' . $migrate_group_uuid,
						array(
							'headers' => array(
								'Authorization' => 'Bearer ' . $token,
							),
						)
					);

					if ( wp_remote_retrieve_response_code( $response ) === 200 && ! is_wp_error( $response ) ) {
						$body = wp_remote_retrieve_body( $response );
						$data = json_decode( $body, true );
						if ( $data && is_array( $data ) && isset( $data['status'] ) && $data['status'] ) {
							$migration_status = $data['data']['status'];

							if ( 'completed' === $migration_status || 'failed' === $migration_status || 'aborted' === $migration_status ) {
								$push = new Push();
								$push->set_status( $push->statuses['completed'] );
								$this->tracker->update_track( $push );
							}
							if ( 'completed' === $migration_status ) {
								$migration_complete = new LastStep();
								$migration_complete->set_status( $migration_complete->statuses['completed'] );
								$this->tracker->update_track( $migration_complete );
								$this->push( 'migration_completed', $this->tracker->get_track_content() );
							} elseif ( 'failed' === $migration_status ) {
								$migration_complete = new LastStep();
								$migration_complete->set_status( $migration_complete->statuses['failed'] );
								$this->tracker->update_track( $migration_complete );
								$this->push( 'migration_failed', $this->tracker->get_track_content() );
							} elseif ( 'aborted' === $migration_status ) {
								$migration_complete = new LastStep();
								$migration_complete->set_status( $migration_complete->statuses['aborted'] );
								$this->tracker->update_track( $migration_complete );
								$this->push( 'migration_aborted', $this->tracker->get_track_content() );
							}
						}
					}
				}
			}
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
			$mode   = isset( $new_value['mode'] ) ? $new_value['mode'] : '';
			$status = isset( $new_value['status'] ) ? $new_value['status'] : '';
			if ( 'push' === $mode && 'initiated' === $status ) {
				$push = new Push();
				$this->tracker->update_track( $push );
			}
		}
		return $new_value;
	}
}
