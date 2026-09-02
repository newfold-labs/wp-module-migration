<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;

/**
 * Class to track migrations steps.
 *
 * @package wp-module-migration
 */
class Tracker {
	/**
	 * Option name for tracking data.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'nfd_migration_tracking';

	/**
	 * Get the current step status.
	 *
	 * @return array
	 */
	public function get_track_content() {
		return get_option( self::OPTION_NAME, array() );
	}

	/**
	 * Update the tracking data with the current step status
	 *
	 * @param AbstractStep $step the step to update.
	 * @return bool
	 */
	public function update_track( AbstractStep $step ) {
		$updated       = false;
		$track_content = $this->get_track_content();
		if ( $step && is_array( $track_content ) ) {
			$datas         = array(
				$step->get_step_slug() => array(
					'status'  => $step->get_status(),
					'intents' => $step->get_retry_count() + 1,
					'message' => $step->get_response()['message'] ?? '',
					'data'    => $this->sanitize_step_data( $step->get_data() ),
					'time'    => current_time( 'mysql', 1 ),
				),
			);
			$updated_track = array_replace( $track_content, $datas );
			$updated       = update_option( self::OPTION_NAME, $updated_track );
		}

		return $updated;
	}

	/**
	 * Remove the tracking data
	 *
	 * @return bool
	 */
	public function delete_track() {
		return delete_option( self::OPTION_NAME );
	}

	/**
	 * Reset the tracking data to an empty array to start from fresh.
	 *
	 * @return bool
	 */
	public function reset() {
		return update_option( self::OPTION_NAME, array() );
	}

	/**
	 * Strip sensitive migration URL details before persisting tracking data.
	 *
	 * @param array $data Step data array.
	 * @return array
	 */
	private function sanitize_step_data( array $data ) {
		if ( empty( $data['migration_url'] ) || ! is_string( $data['migration_url'] ) ) {
			return $data;
		}

		$url_parts = wp_parse_url( $data['migration_url'] );
		if ( empty( $url_parts['host'] ) ) {
			unset( $data['migration_url'] );
			return $data;
		}

		$data['migration_url'] = $url_parts['host'] . ( $url_parts['path'] ?? '/' );

		return $data;
	}
}
