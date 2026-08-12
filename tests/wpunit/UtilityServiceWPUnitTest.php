<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Services\UtilityService;

/**
 * UtilityService wpunit tests.
 */
class UtilityServiceWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Cancelled status maps to aborted.
	 */
	public function test_normalize_migration_status_maps_cancelled_status() {
		$this->assertSame( 'aborted', UtilityService::normalize_migration_status( 'cancelled' ) );
		$this->assertSame( 'aborted', UtilityService::normalize_migration_status( 'canceled' ) );
	}

	/**
	 * Successful status maps to completed.
	 */
	public function test_normalize_migration_status_maps_successful_status() {
		$this->assertSame( 'completed', UtilityService::normalize_migration_status( 'successful' ) );
	}

	/**
	 * Non-terminal statuses are unchanged.
	 */
	public function test_normalize_migration_status_preserves_in_progress_status() {
		$this->assertSame( 'in_progress', UtilityService::normalize_migration_status( 'in_progress' ) );
		$this->assertSame( 'failed', UtilityService::normalize_migration_status( 'failed' ) );
	}

	/**
	 * Empty migrate group UUID returns empty enrichment without API calls.
	 *
	 * @return void
	 */
	public function test_get_migration_enrichment_returns_empty_for_empty_uuid() {
		$this->assertSame( array(), UtilityService::get_migration_enrichment( '' ) );
	}
}
