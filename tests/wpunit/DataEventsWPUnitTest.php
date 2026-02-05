<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Data\Events;

/**
 * Data Events wpunit tests.
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\Data\Events
 */
class DataEventsWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Get_valid_actions returns array with expected migration actions.
	 *
	 * @return void
	 */
	public function test_get_valid_actions_returns_expected_actions() {
		$actions = Events::get_valid_actions();
		$this->assertArrayHasKey( 'migration_completed', $actions );
		$this->assertArrayHasKey( 'migration_failed', $actions );
		$this->assertArrayHasKey( 'migration_initiated', $actions );
		$this->assertTrue( $actions['migration_completed'] );
	}

	/**
	 * Get_category returns array with expected categories.
	 *
	 * @return void
	 */
	public function test_get_category_returns_expected_categories() {
		$categories = Events::get_category();
		$this->assertContains( 'wonder_start', $categories );
		$this->assertContains( 'wp_migration', $categories );
	}
}
