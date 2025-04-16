<?php
namespace NewfoldLabs\WP\Module\Migration\Listeners;

use NewfoldLabs\WP\Module\Migration\Data\Events;
use NewfoldLabs\WP\Module\Migration\Services\EventService;
use NewfoldLabs\WP\Module\Migration\Services\UtilityService;
use NewfoldLabs\WP\Module\Migration\Services\Tracker;
use NewfoldLabs\WP\Module\Migration\Steps\Push;
use NewfoldLabs\WP\Module\Migration\Steps\PageSpeed;
use NewfoldLabs\WP\Module\Migration\Steps\LastStep;
use NewfoldLabs\WP\Module\Migration\Steps\SourceHostingInfo;
use PhpParser\Node\Stmt\TryCatch;

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
		add_action( 'nfd_migration_page_speed_source', array( $this, 'page_speed_source' ), 10 );
		add_action( 'nfd_migration_page_speed_destination', array( $this, 'page_speed_destination' ), 10 );
	}
	/**
	 * Push event with tracking file content.
	 *
	 * @param string $action action/key for the event.
	 * @param array  $data   data to be sent with the event.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function push( $action, $data ) {
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
				$response = UtilityService::get_migration_data( $migrate_group_uuid );

				if ( $response && is_array( $response ) && isset( $response['status'] ) && $response['status'] ) {
					$migration_status = $response['data']['status'];

					if ( 'completed' === $migration_status || 'failed' === $migration_status || 'aborted' === $migration_status ) {
						$push                = new Push();
						$source_hosting_info = new SourceHostingInfo( $response['data']['source_site_url'] );
						$push->set_status( $push->statuses[ $migration_status ] );
						$this->tracker->update_track( $push );
						$this->tracker->update_track( $source_hosting_info );

						if ( isset( $response['data']['source_site_url'] ) ) {
							$source_site_url = $response['data']['source_site_url'];
							if ( ! wp_next_scheduled( 'nfd_migration_page_speed_source' ) ) {
								wp_schedule_single_event( time() + 60, 'nfd_migration_page_speed_source', array( 'source_site_url' => $source_site_url ) );
							}
							if ( ! wp_next_scheduled( 'nfd_migration_page_speed_destination' ) ) {
								wp_schedule_single_event( time() + 120, 'nfd_migration_page_speed_destination' );
							}
						}
					}

					if ( 'completed' === $migration_status ) {
						$migration_complete = new LastStep();
						$migration_complete->set_status( $migration_complete->statuses['completed'] );
						$this->tracker->update_track( $migration_complete );
						$this::push( 'migration_completed', $this->tracker->get_track_content() );
					} elseif ( 'failed' === $migration_status ) {
						$migration_complete = new LastStep();
						$migration_complete->set_status( $migration_complete->statuses['failed'] );
						$this->tracker->update_track( $migration_complete );
						$this::push( 'migration_failed', $this->tracker->get_track_content() );
					} elseif ( 'aborted' === $migration_status ) {
						$migration_complete = new LastStep();
						$migration_complete->set_status( $migration_complete->statuses['aborted'] );
						$this->tracker->update_track( $migration_complete );
						$this::push( 'migration_aborted', $this->tracker->get_track_content() );
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
	/**
	 * Track page speed for source site.
	 *
	 * @param string $source_site_url source site url.
	 * @return void
	 */
	public function page_speed_source( $source_site_url ) {
		$source_url_pagespeed = new PageSpeed( $source_site_url, 'source' );
		if ( ! $source_url_pagespeed->failed() ) {
			$source_url_pagespeed->set_status( $source_url_pagespeed->statuses['completed'] );
		}

		$this->tracker->update_track( $source_url_pagespeed );
	}
	/**
	 * Track page speed for source site.
	 *
	 * @return void
	 */
	public function page_speed_destination() {
		try {
			$source_url_pagespeed = new PageSpeed( site_url(), 'destination' );
			if ( ! $source_url_pagespeed->failed() ) {
				$source_url_pagespeed->set_status( $source_url_pagespeed->statuses['completed'] );
			}

			$this->tracker->update_track( $source_url_pagespeed );
		} finally {
			$tracker_content     = $this->tracker->get_track_content();
			$pagespeed_for_event = array();

			if ( isset( $tracker_content['PageSpeed_source'] ) ) {
				$pagespeed_for_event['PageSpeed_source'] = $tracker_content['PageSpeed_source'];
			}
			if ( isset( $tracker_content['PageSpeed_destination'] ) ) {
				$pagespeed_for_event['PageSpeed_destination'] = $tracker_content['PageSpeed_destination'];
			}

			if ( ! empty( $pagespeed_for_event ) ) {
				self::push( 'migration_complete', $pagespeed_for_event );
			}
		}
	}
}
