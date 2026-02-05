<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Data\Constants;
use NewfoldLabs\WP\Module\Migration\Helpers\Permissions;
use NewfoldLabs\WP\Module\Migration\RestApi\MigrateController;

/**
 * Module loading wpunit tests.
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\Migration
 */
class ModuleLoadingWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Verify core module classes exist.
	 *
	 * @return void
	 */
	public function test_module_classes_load() {
		$this->assertTrue( class_exists( Migration::class ) );
		$this->assertTrue( class_exists( Constants::class ) );
		$this->assertTrue( class_exists( Permissions::class ) );
		$this->assertTrue( class_exists( MigrateController::class ) );
	}

	/**
	 * Verify Migration handle constant.
	 *
	 * @return void
	 */
	public function test_migration_handle_constant() {
		$this->assertSame( 'nfd-migration', Migration::$handle );
	}

	/**
	 * Verify WordPress factory is available.
	 *
	 * @return void
	 */
	public function test_wordpress_factory_available() {
		$this->assertTrue( function_exists( 'get_option' ) );
		$this->assertNotEmpty( get_option( 'blogname' ) );
	}
}
