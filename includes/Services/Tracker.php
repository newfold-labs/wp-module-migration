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
					'data'    => $step->get_data(),
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
}
