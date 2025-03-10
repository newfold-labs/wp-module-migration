<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use NewfoldLabs\WP\Module\Migration\Services\Tracker;

/**
 * Class to add a page report to see the tracking informations.
 *
 * @package wp-module-migration
 */
class UIReport {
	/**
	 * The tracker instance.
	 *
	 * @var Tracker
	 */
	protected $tracker;

	/**
	 * UIReport constructor.
	 */
	public function __construct() {
		$this->tracker = new Tracker();
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Add the report page to the admin menu.
	 *
	 * @return void
	 */
	public function add_page() {
		$hook = add_submenu_page(
			'nfd-migration',
			__( 'Migration Report', 'wp-module-migration' ),
			'',
			'manage_options',
			'nfd-migration',
			array( $this, 'get_report_page' ),
		);

		remove_menu_page( $hook );
	}

	/**
	 * Get the report page callback.
	 *
	 * @return void
	 */
	public function get_report_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Migration Report', 'wp-module-migration' ); ?></h1>
			<div class="nfd-migration-report">
				<?php
				$report_content = $this->get_report_content();
				if ( ! empty( $report_content ) ) {
					echo '<ul>';
					foreach ( $report_content as $step => $values ) {
						echo '<li>';
						echo '<h3>' . esc_html( $step ) . '</h3>';
						foreach ( $values as $key => $value ) {
							if ( ! empty( $value ) ) {
								echo '<ul>';
								echo '<li><strong>' . esc_html( $key ) . '</strong>: ' . esc_html( $value ) . '</li>';
								echo '</ul>';
							}
						}
						echo '</li>';
					}
					echo '</ul>';
				} else {
					echo '<p>' . esc_html__( 'No report available.', 'wp-module-migration' ) . '</p>';
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get the report content.
	 *
	 * @return array
	 */
	private function get_report_content() {
		return $this->tracker->get_track_content();
	}
}