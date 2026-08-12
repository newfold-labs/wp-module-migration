<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Services\MigrationCompletionService;

/**
 * MigrationCompletionService wpunit tests.
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\Services\MigrationCompletionService
 */
class MigrationCompletionServiceWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * HTTP mock for EventService requests.
	 *
	 * @var callable|null
	 */
	private $http_filter_callback;

	/**
	 * Define module constants and mock outbound HTTP.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! defined( 'NFD_MIGRATION_PROXY_WORKER' ) ) {
			define( 'NFD_MIGRATION_PROXY_WORKER', 'https://migrate.example.com' );
		}

		if ( ! defined( 'BRAND_PLUGIN' ) ) {
			define( 'BRAND_PLUGIN', 'bluehost' );
		}

		$this->http_filter_callback = function ( $preempt, $args, $url ) {
			return array(
				'headers'  => array(),
				'body'     => '{}',
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => null,
			);
		};

		add_filter( 'pre_http_request', $this->http_filter_callback, 10, 3 );
	}

	/**
	 * Clear migration-related options after each test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		if ( $this->http_filter_callback ) {
			remove_filter( 'pre_http_request', $this->http_filter_callback, 10 );
		}

		delete_option( 'nfd_migration_status_sent' );
		delete_option( 'instawp_last_migration_details' );
		delete_option( 'nfd_migration_tracking' );
		parent::tearDown();
	}

	/**
	 * Stale status-sent flag is cleared when a new migration is in progress.
	 *
	 * @return void
	 */
	public function test_reconcile_clears_stale_flag_when_migration_in_progress() {
		update_option( 'nfd_migration_status_sent', true );
		update_option(
			'instawp_last_migration_details',
			array(
				'migrate_group_uuid' => 'test-uuid',
				'status'             => 'in_progress',
			)
		);

		$this->assertTrue( MigrationCompletionService::reconcile_stale_status_sent_flag() );
		$this->assertFalse( get_option( 'nfd_migration_status_sent' ) );
	}

	/**
	 * Status-sent flag is kept when the option already has a terminal status.
	 *
	 * @return void
	 */
	public function test_reconcile_keeps_flag_when_terminal_status_in_option() {
		update_option( 'nfd_migration_status_sent', true );
		update_option(
			'instawp_last_migration_details',
			array(
				'migrate_group_uuid' => 'test-uuid',
				'status'             => 'completed',
			)
		);

		$this->assertFalse( MigrationCompletionService::reconcile_stale_status_sent_flag() );
		$this->assertTrue( get_option( 'nfd_migration_status_sent' ) );
	}

	/**
	 * Non-terminal statuses are not processed.
	 *
	 * @return void
	 */
	public function test_process_terminal_status_returns_false_for_in_progress() {
		$completion = new MigrationCompletionService();
		$this->assertFalse( $completion->process_terminal_status( 'in_progress', 'test-uuid', array() ) );
	}

	/**
	 * Terminal handling is skipped when completion events were already sent.
	 *
	 * @return void
	 */
	public function test_process_terminal_status_returns_false_when_already_sent() {
		update_option( 'nfd_migration_status_sent', true );
		$completion = new MigrationCompletionService();
		$this->assertFalse( $completion->process_terminal_status( 'failed', 'test-uuid', array() ) );
	}

	/**
	 * Cancelled option status is normalized to aborted for terminal handling.
	 *
	 * @return void
	 */
	public function test_process_terminal_status_normalizes_cancelled_to_aborted() {
		$completion = new MigrationCompletionService();
		$this->assertTrue(
			$completion->process_terminal_status(
				'cancelled',
				'test-uuid',
				array(
					'enrichment' => array(),
				)
			)
		);
		$this->assertTrue( get_option( 'nfd_migration_status_sent' ) );
	}
}
