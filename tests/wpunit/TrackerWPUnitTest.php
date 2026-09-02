<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Services\Tracker;

/**
 * Tracker wpunit tests.
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\Services\Tracker
 */
class TrackerWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Migration URL query tokens are not persisted in tracking data.
	 *
	 * @return void
	 */
	public function test_sanitize_step_data_strips_migration_url_query_token() {
		$tracker    = new Tracker();
		$reflection = new \ReflectionClass( $tracker );
		$method     = $reflection->getMethod( 'sanitize_step_data' );
		$method->setAccessible( true );

		$sanitized = $method->invoke(
			$tracker,
			array(
				'migration_url' => 'https://migrate.example.com/start?t=secret-token',
			)
		);

		$this->assertSame( 'migrate.example.com/start', $sanitized['migration_url'] );
	}
}
