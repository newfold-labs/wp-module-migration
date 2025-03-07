<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

/**
 * Class to track migrations steps.
 *
 * @package wp-module-migration
 */
class Tracker {
	/**
	 * Name of the option to track the migration steps.
	 *
	 * @var string $file_name.
	 */
	protected $file_name = '.nfd-migration-tracking';
	/**
	 * Path to the tracker file.
	 *
	 * @var string $path.
	 */
	protected $path = ABSPATH;
	/**
	 * Get the current step status.
	 *
	 * @return array
	 */
	private function get_track_content() {
		global $wp_filesystem;

		// Make sure that the above variable is properly setup.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		$file_path = $this->get_full_path();

		if ( $wp_filesystem->exists( $file_path ) ) {
			$track_content = $wp_filesystem->get_contents( $file_path );
			$track_content = json_decode( $track_content, true );

			if ( ! is_array( $track_content ) ) {
				$track_content = array();
			}
		} else {
			$track_content = array();
		}

		return $track_content;
	}

	/**
	 * Update the tracking file with the current step status
	 *
	 * @param array $step the step to update.
	 * @return bool
	 */
	public function update_track( $step = array() ) {
		global $wp_filesystem;

		// Make sure that the above variable is properly setup.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		$updated       = false;
		$track_content = $this->get_track_content();
		if ( count( $step ) > 0 ) {
			$currentKey                  = array_key_first( $step );
			$step[ $currentKey ]['time'] = current_time( 'mysql', 1 );
			$updated_track               = array_replace( $track_content, $step );
			$updated                     = $wp_filesystem->put_contents( $this->get_full_path(), wp_json_encode( $updated_track ) );
		}

		return $updated;
	}

	/**
	 * Remove the tracking file
	 *
	 * @return bool
	 */
	public function delete_track() {
		global $wp_filesystem;

		// Make sure that the above variable is properly setup.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();
		$deleted = false;
		if ( $wp_filesystem->exists( $this->get_full_path() ) ) {
			$deleted = wp_delete_file( $this->get_full_path() );
		}

		return $deleted;
	}

	/**
	 * Reset the tracking file to an empty array to start from fresh.
	 *
	 * @return bool
	 */
	public function reset_track_file() {
		global $wp_filesystem;

		// Make sure that the above variable is properly setup.
		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		$path  = $this->get_full_path();
		$reset = false;
		if ( $wp_filesystem->exists( $path ) && $wp_filesystem->is_writable( $path ) ) {
			$reset = $wp_filesystem->put_contents( $path, wp_json_encode( array() ) );
		}

		return $reset;
	}

	/**
	 * Set the tracker file path.
	 *
	 * @param string $path the path to the tracker file.
	 * @return void
	 */
	public function set_path( $path ) {
		$path       = ! empty( $path ) ? $path : ABSPATH;
		$this->path = $path;
	}
	/**
	 * Set the tracker file name.
	 *
	 * @param string $file_name the name of the tracker file.
	 * @return void
	 */
	public function set_file_name( $file_name ) {
		$file_name       = ! empty( $file_name ) ? $file_name : '.nfd-migration-tracking';
		$this->file_name = $file_name;
	}

	/**
	 * Get the tracker file path.
	 *
	 * @return string
	 */
	private function get_full_path() {
		return $this->path . $this->file_name;
	}
}
