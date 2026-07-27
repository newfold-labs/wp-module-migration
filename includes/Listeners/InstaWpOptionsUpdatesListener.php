<?php
namespace NewfoldLabs\WP\Module\Migration\Listeners;

use NewfoldLabs\WP\Module\Migration\Services\UtilityService;
use NewfoldLabs\WP\Module\Migration\Services\Tracker;
use NewfoldLabs\WP\Module\Migration\Services\MigrationCompletionService;
use NewfoldLabs\WP\Module\Migration\Services\V4MigrationPollService;
use NewfoldLabs\WP\Module\Migration\Steps\Push;
use NewfoldLabs\WP\Module\Migration\Steps\PageSpeed;
use NewfoldLabs\WP\Module\Migration\Steps\SourceHostingInfo;

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
	 * Shared completion handler.
	 *
	 * @var MigrationCompletionService
	 */
	public $completion;

	/**
	 * InstaWpOptionsUpdatesListener constructor.
	 */
	public function __construct() {
		$this->tracker    = new Tracker();
		$this->completion = new MigrationCompletionService( $this->tracker );
		$this->register_hooks();
	}

	/**
	 * Register the hooks for the listener
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'pre_update_option_instawp_last_migration_details', array( $this, 'on_update_instawp_last_migration_details' ), 10, 2 );
		add_filter( 'pre_update_option_instawp_migration_details', array( $this, 'on_update_instawp_migration_details' ), 10, 2 );
		add_action( 'nfd_migration_page_speed_source', array( $this, 'page_speed_source' ), 10 );
		add_action( 'nfd_migration_page_speed_destination', array( $this, 'page_speed_destination' ), 10, 3 );
		add_action( 'nfd_migration_source_hosting_info', array( $this, 'source_hosting_info' ), 10 );
	}

	/**
	 * Triggers events
	 *
	 * @param array $new_value status of migration
	 * @param array $old_value previous status of migration
	 * @return array
	 */
	public function on_update_instawp_last_migration_details( $new_value, $old_value ) {
		if ( $old_value !== $new_value && ! get_option( 'nfd_migration_status_sent', false ) ) {
			$migrate_group_uuid = isset( $new_value['migrate_group_uuid'] ) ? $new_value['migrate_group_uuid'] : '';
			if ( V4MigrationPollService::has_active_session() ) {
				return $new_value;
			}
			if ( ! empty( $migrate_group_uuid ) ) {
				$response = UtilityService::get_migration_data( $migrate_group_uuid );

				if ( $response && is_array( $response ) ) {
					$migration_status = isset( $new_value['status'] ) ? $new_value['status'] : '';
					$this->completion->process_terminal_status(
						$migration_status,
						$migrate_group_uuid,
						array(
							'enrichment' => $response,
						)
					);
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
	 * Get source site hosting informations.
	 *
	 * @param string $source_site_url source site url.
	 * @return void
	 */
	public function source_hosting_info( $source_site_url ) {
		if ( ! V4MigrationPollService::session_matches( '', $source_site_url ) ) {
			return;
		}

		$source_hosting_info = new SourceHostingInfo( $source_site_url );
		$this->tracker->update_track( $source_hosting_info );

		if ( ! $source_hosting_info->failed() ) {
			$source_hosting_info->set_status( $source_hosting_info->statuses['completed'] );
		}

		$this->tracker->update_track( $source_hosting_info );
	}

	/**
	 * Track page speed for source site.
	 *
	 * @param string $source_site_url source site url.
	 * @return void
	 */
	public function page_speed_source( $source_site_url ) {
		if ( ! V4MigrationPollService::session_matches( '', $source_site_url ) ) {
			return;
		}

		$source_url_pagespeed = new PageSpeed( $source_site_url, 'source' );
		if ( ! $source_url_pagespeed->failed() ) {
			$source_url_pagespeed->set_status( $source_url_pagespeed->statuses['completed'] );
		}

		$this->tracker->update_track( $source_url_pagespeed );
	}

	/**
	 * Track page speed for destination site and send completion events.
	 *
	 * @param string $source_site_url    source site url.
	 * @param string $migrate_group_uuid migrate group uuid.
	 * @param string $status             status of migration.
	 * @return void
	 */
	public function page_speed_destination( $source_site_url, $migrate_group_uuid, $status ) {
		if ( ! V4MigrationPollService::session_matches( $migrate_group_uuid, $source_site_url ) ) {
			return;
		}

		try {
			$source_url_pagespeed = new PageSpeed( site_url(), 'destination' );
			if ( ! $source_url_pagespeed->failed() ) {
				$source_url_pagespeed->set_status( $source_url_pagespeed->statuses['completed'] );
			}

			$this->tracker->update_track( $source_url_pagespeed );
		} finally {
			$this->completion->send_completed_events( $migrate_group_uuid, $source_site_url, $status );
		}
	}
}
