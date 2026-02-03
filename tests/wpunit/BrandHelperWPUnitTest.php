<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Helpers\BrandHelper;

/**
 * BrandHelper wpunit tests.
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\Helpers\BrandHelper
 */
class BrandHelperWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Define brand whitelist constant for tests if not already set by module bootstrap.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if ( ! defined( 'NFD_MIGRATION_BRAND_WHITELIST' ) ) {
			define( 'NFD_MIGRATION_BRAND_WHITELIST', array( 'bluehost', 'hostgator' ) );
		}
	}

	/**
	 * Is_whitelisted returns true for bluehost.
	 *
	 * @return void
	 */
	public function test_is_whitelisted_returns_true_for_bluehost() {
		$this->assertTrue( BrandHelper::is_whitelisted( 'bluehost' ) );
	}

	/**
	 * Is_whitelisted returns true for hostgator.
	 *
	 * @return void
	 */
	public function test_is_whitelisted_returns_true_for_hostgator() {
		$this->assertTrue( BrandHelper::is_whitelisted( 'hostgator' ) );
	}

	/**
	 * Is_whitelisted returns false for unknown brand.
	 *
	 * @return void
	 */
	public function test_is_whitelisted_returns_false_for_unknown_brand() {
		$this->assertFalse( BrandHelper::is_whitelisted( 'unknown-brand' ) );
	}
}
