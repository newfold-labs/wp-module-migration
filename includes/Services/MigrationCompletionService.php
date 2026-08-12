<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use NewfoldLabs\WP\Module\Migration\Steps\Push;
use NewfoldLabs\WP\Module\Migration\Steps\LastStep;

/**
 * Shared post-migration completion handling for v3 and v4 option updates.
 */
class MigrationCompletionService {

	/**
	 * Tracker class instance.
	 *
	 * @var Tracker
	 */
	private $tracker;

	/**
	 * MigrationCompletionService constructor.
	 *
	 * @param Tracker|null $tracker Optional tracker instance.
	 */
	public function __construct( ?Tracker $tracker = null ) {
		$this->tracker = $tracker ?? new Tracker();
	}

	/**
	 * Process a terminal migration status and schedule post-migration work.
	 *
	 * @param string $migration_status   completed, failed, or aborted.
	 * @param string $migrate_group_uuid Migration group UUID.
	 * @param array  $context            Optional enrichment and source URL overrides.
	 * @return bool Whether terminal handling was started.
	 */
	public function process_terminal_status( $migration_status, $migrate_group_uuid, array $context = array() ) {
		self::reconcile_stale_status_sent_flag();

		if ( get_option( 'nfd_migration_status_sent', false ) ) {
			return false;
		}

		$migration_status = UtilityService::normalize_migration_status( $migration_status );

		if ( ! in_array( $migration_status, array( 'completed', 'failed', 'aborted' ), true ) ) {
			return false;
		}

		$response = isset( $context['enrichment'] ) && is_array( $context['enrichment'] )
			? $context['enrichment']
			: array();

		if ( empty( $response ) && ! empty( $migrate_group_uuid ) ) {
			$fetched = UtilityService::get_migration_enrichment( $migrate_group_uuid );
			if ( is_array( $fetched ) && ! empty( $fetched ) ) {
				$response = $fetched;
			}
		}

		$source_site_url = isset( $context['source_site_url'] ) ? (string) $context['source_site_url'] : '';
		if ( empty( $source_site_url ) && isset( $response['data']['source_site_url'] ) ) {
			$source_site_url = $response['data']['source_site_url'];
		}

		$can_process = ! empty( $response )
			|| ! empty( $source_site_url )
			|| in_array( $migration_status, array( 'failed', 'aborted' ), true );

		if ( ! $can_process ) {
			return false;
		}

		$push = new Push();
		$push->set_status( $push->statuses[ $migration_status ] );
		$this->tracker->update_track( $push );

		if ( ! empty( $source_site_url ) ) {
			if ( ! wp_next_scheduled( 'nfd_migration_source_hosting_info' ) ) {
				wp_schedule_single_event( time() + 60, 'nfd_migration_source_hosting_info', array( 'source_site_url' => $source_site_url ) );
			}
			if ( 'completed' === $migration_status ) {
				if ( ! wp_next_scheduled( 'nfd_migration_page_speed_source' ) ) {
					wp_schedule_single_event( time() + 90, 'nfd_migration_page_speed_source', array( 'source_site_url' => $source_site_url ) );
				}
				if ( ! wp_next_scheduled( 'nfd_migration_page_speed_destination' ) ) {
					wp_schedule_single_event(
						time() + 120,
						'nfd_migration_page_speed_destination',
						array(
							'source_site_url'    => $source_site_url,
							'migrate_group_uuid' => $migrate_group_uuid,
							'status'             => $migration_status,
						)
					);
				}
			}
		}

		if ( 'completed' === $migration_status ) {
			$migration_complete = new LastStep();
			$migration_complete->set_status( $migration_complete->statuses['completed'] );
			$this->tracker->update_track( $migration_complete );

			if ( empty( $source_site_url ) ) {
				$this->send_completed_events( $migrate_group_uuid, $source_site_url, $migration_status );
			}

			return true;
		}

		$migration_complete = new LastStep();
		$migration_complete->set_status( $migration_complete->statuses[ $migration_status ] );
		$this->tracker->update_track( $migration_complete );

		if ( 'failed' === $migration_status ) {
			EventService::send_application_event(
				'migration_failed',
				$this->tracker->get_track_content()
			);
		} else {
			EventService::send_application_event(
				'migration_aborted',
				array_merge(
					$this->tracker->get_track_content(),
					array(
						'migration_uuid' => $migrate_group_uuid,
					)
				)
			);
		}

		$this->tracker->delete_track();
		update_option( 'nfd_migration_status_sent', true );

		return true;
	}

	/**
	 * Send migration_completed and migration_successful events.
	 *
	 * @param string $migrate_group_uuid Migration UUID.
	 * @param string $source_site_url    Source site URL.
	 * @param string $migration_status   Raw migration status.
	 * @return void
	 */
	public function send_completed_events( $migrate_group_uuid, $source_site_url, $migration_status = 'completed' ) {
		self::reconcile_stale_status_sent_flag();

		if ( get_option( 'nfd_migration_status_sent', false ) ) {
			return;
		}

		EventService::send_application_event(
			'migration_completed',
			array_merge(
				array(
					'migration_uuid' => $migrate_group_uuid,
				),
				$this->tracker->get_track_content()
			)
		);

		$tracked_datas           = $this->tracker->get_track_content();
		$isp                     = $tracked_datas['SourceHostingInfo']['data']['SourceHostingData']['isp'] ?? 'N/A';
		$as                      = $tracked_datas['SourceHostingInfo']['data']['SourceHostingData']['as'] ?? 'N/A';
		$source_speed_index      = $tracked_datas['PageSpeed_source']['data']['speedIndex'] ?? '0';
		$source_speed_index      = str_replace( ' s', '', $source_speed_index );
		$destination_speed_index = $tracked_datas['PageSpeed_destination']['data']['speedIndex'] ?? 0;
		$destination_speed_index = str_replace( ' s', '', $destination_speed_index );
		$status                  = 'completed' === $migration_status ? 'successful' : $migration_status;
		$migration_infos         = array(
			'migration_uuid'         => $migrate_group_uuid,
			'status'                 => $status,
			'origin_url'             => $source_site_url,
			'origin_isp'             => $isp,
			'origin_as'              => $as,
			'origin_page_speed'      => $source_speed_index,
			'destination_page_speed' => $destination_speed_index,
		);

		EventService::send_application_event( "migration_$status", $migration_infos );
		update_option( 'nfd_migration_status_sent', true );
		$this->tracker->delete_track();
	}

	/**
	 * Clear a stale status-sent flag when a new migration is in progress.
	 *
	 * @return bool Whether the flag was cleared.
	 */
	public static function reconcile_stale_status_sent_flag() {
		if ( ! get_option( 'nfd_migration_status_sent', false ) ) {
			return false;
		}

		$details = get_option( 'instawp_last_migration_details', array() );
		if ( ! is_array( $details ) || empty( $details['migrate_group_uuid'] ) ) {
			return false;
		}

		$status = isset( $details['status'] ) ? UtilityService::normalize_migration_status( $details['status'] ) : '';
		if ( ! in_array( $status, array( 'completed', 'failed', 'aborted' ), true ) ) {
			delete_option( 'nfd_migration_status_sent' );
			return true;
		}

		return false;
	}
}
