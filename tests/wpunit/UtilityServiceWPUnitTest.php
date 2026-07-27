<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Services\UtilityService;

/**
 * UtilityService wpunit tests.
 */
class UtilityServiceWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Portal cancelled status maps to aborted.
	 */
	public function test_parse_portal_migration_state_maps_cancelled_status() {
		$parsed = UtilityService::parse_portal_migration_state(
			array(
				'data' => array(
					'status'       => 'cancelled',
					'migration_id' => '81aabb5c-8fe7-480b-be8f-ff3cd53c2143',
					'source_url'   => 'https://example.com/source',
				),
			)
		);

		$this->assertSame( 'aborted', $parsed['status'] );
		$this->assertSame( '81aabb5c-8fe7-480b-be8f-ff3cd53c2143', $parsed['migrate_group_uuid'] );
		$this->assertSame( 'https://example.com/source', $parsed['source_site_url'] );
	}

	/**
	 * Portal successful status maps to completed.
	 */
	public function test_parse_portal_migration_state_maps_successful_status() {
		$parsed = UtilityService::parse_portal_migration_state(
			array(
				'data' => array(
					'status' => 'successful',
				),
			)
		);

		$this->assertSame( 'completed', $parsed['status'] );
	}

	/**
	 * Terminal portal statuses are recognized.
	 */
	public function test_is_terminal_portal_status() {
		$this->assertTrue( UtilityService::is_terminal_portal_status( 'completed' ) );
		$this->assertTrue( UtilityService::is_terminal_portal_status( 'failed' ) );
		$this->assertTrue( UtilityService::is_terminal_portal_status( 'aborted' ) );
		$this->assertFalse( UtilityService::is_terminal_portal_status( 'in_progress' ) );
	}
}
