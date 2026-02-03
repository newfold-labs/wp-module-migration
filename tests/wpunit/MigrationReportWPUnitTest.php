<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Reports\MigrationReport;

/**
 * MigrationReport wpunit tests.
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\Reports\MigrationReport
 */
class MigrationReportWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Slug constant has expected value.
	 *
	 * @return void
	 */
	public function test_slug_constant() {
		$this->assertSame( 'nfd-migration', MigrationReport::$slug );
	}
}
