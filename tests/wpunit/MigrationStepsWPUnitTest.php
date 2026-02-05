<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Steps\LastStep;

/**
 * Migration Steps wpunit tests (covers AbstractStep statuses).
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\Steps\AbstractStep
 */
class MigrationStepsWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Step has expected statuses from AbstractStep.
	 *
	 * @return void
	 */
	public function test_step_has_expected_statuses() {
		$step = new LastStep();
		$this->assertSame( 'running', $step->statuses['running'] );
		$this->assertSame( 'completed', $step->statuses['completed'] );
		$this->assertSame( 'failed', $step->statuses['failed'] );
		$this->assertSame( 'aborted', $step->statuses['aborted'] );
	}
}
