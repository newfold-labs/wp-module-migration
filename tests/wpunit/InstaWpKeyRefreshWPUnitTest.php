<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Services\InstaMigrateService;

if ( ! class_exists( '\NewfoldLabs\WP\Module\Data\Helpers\Encryption' ) ) {
	require_once dirname( __DIR__ ) . '/_support/Stubs/Encryption.php';
}

/**
 * End-to-end checks that a rejected saved InstaWP key is refreshed only on a 401.
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\Services\InstaMigrateService
 */
class InstaWpKeyRefreshWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	const ENGINE_URL = 'https://app.instawp.io/api/v2/migrate-v4/engine';

	/**
	 * Requests made during the test, as [ url, bearer key ].
	 *
	 * @var array
	 */
	private $requests = array();

	/**
	 * Engine response code per bearer key.
	 *
	 * @var array
	 */
	private $engine_codes = array();

	/**
	 * Key the worker hands out.
	 *
	 * @var string
	 */
	private $worker_key = '';

	/**
	 * Set up constants and the HTTP mock.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		if ( ! defined( 'NFD_MIGRATION_PROXY_WORKER' ) ) {
			define( 'NFD_MIGRATION_PROXY_WORKER', 'https://migrate.bluehost.com' );
		}
		if ( ! defined( 'BRAND_PLUGIN' ) ) {
			define( 'BRAND_PLUGIN', 'bluehost' );
		}

		$this->requests = array();
		add_filter( 'pre_http_request', array( $this, 'mock_http' ), 10, 3 );
	}

	/**
	 * Remove the HTTP mock.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'mock_http' ), 10 );
		delete_option( 'newfold_insta_api_key' );
		parent::tearDown();
	}

	/**
	 * Answer worker and InstaWP requests, firing http_api_debug like core does.
	 *
	 * @param false|array $pre  Short-circuit value.
	 * @param array       $args Request args.
	 * @param string      $url  Request URL.
	 * @return array
	 */
	public function mock_http( $pre, $args, $url ) {
		$auth             = $args['headers']['Authorization'] ?? '';
		$key              = 0 === strpos( $auth, 'Bearer ' ) ? substr( $auth, 7 ) : '';
		$this->requests[] = array( strtok( $url, '?' ), $key );

		$code = 200;
		$body = '{}';
		if ( 0 === strpos( $url, NFD_MIGRATION_PROXY_WORKER . '/token' ) ) {
			$body = wp_json_encode( array( 'data' => base64_encode( $this->worker_key ) ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		} elseif ( 0 === strpos( $url, self::ENGINE_URL ) ) {
			$code = $this->engine_codes[ $key ] ?? 500;
			$body = 401 === $code ? '{"message":"Unauthenticated."}' : '{"status":false,"message":"Server error"}';
		}

		$response = array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => $code,
				'message' => '',
			),
			'cookies'  => array(),
			'filename' => null,
		);
		do_action( 'http_api_debug', $response, 'response', 'WpOrg\Requests\Requests', $args, $url );

		return $response;
	}

	/**
	 * Count requests to a URL, optionally for one bearer key.
	 *
	 * @param string      $url Request URL without query.
	 * @param string|null $key Bearer key.
	 * @return int
	 */
	private function count_requests( $url, $key = null ) {
		return count(
			array_filter(
				$this->requests,
				function ( $request ) use ( $url, $key ) {
					return $request[0] === $url && ( null === $key || $request[1] === $key );
				}
			)
		);
	}

	/**
	 * Worker token request count.
	 *
	 * @return int
	 */
	private function token_requests() {
		return $this->count_requests( NFD_MIGRATION_PROXY_WORKER . '/token' );
	}

	/**
	 * A 401 on the saved key fetches one new key and retries connect with it.
	 *
	 * @return void
	 */
	public function test_saved_key_rejected_with_401_is_refreshed_once() {
		update_option( 'newfold_insta_api_key', 'stale-key' );
		$this->worker_key   = 'fresh-key';
		$this->engine_codes = array( 'stale-key' => 401 );

		( new InstaMigrateService() )->run();

		$this->assertSame( 1, $this->token_requests() );
		$this->assertSame( 1, $this->count_requests( self::ENGINE_URL, 'stale-key' ), 'No same-key retry after a 401.' );
		$this->assertGreaterThan( 0, $this->count_requests( self::ENGINE_URL, 'fresh-key' ) );
		$this->assertSame( 'fresh-key', get_option( 'newfold_insta_api_key' ) );
	}

	/**
	 * Non-auth failures never call the worker and keep the saved key.
	 *
	 * @return void
	 */
	public function test_non_auth_failure_does_not_refresh_key() {
		update_option( 'newfold_insta_api_key', 'saved-key' );
		$this->worker_key   = 'other-key';
		$this->engine_codes = array( 'saved-key' => 500 );

		( new InstaMigrateService() )->run();

		$this->assertSame( 0, $this->token_requests() );
		$this->assertSame( 2, $this->count_requests( self::ENGINE_URL, 'saved-key' ), 'Non-auth failures keep the normal retry.' );
		$this->assertSame( 'saved-key', get_option( 'newfold_insta_api_key' ) );
	}

	/**
	 * When the new key is also rejected, there is no second refresh.
	 *
	 * @return void
	 */
	public function test_rejected_fresh_key_is_not_refreshed_again() {
		update_option( 'newfold_insta_api_key', 'stale-key' );
		$this->worker_key   = 'fresh-key';
		$this->engine_codes = array(
			'stale-key' => 401,
			'fresh-key' => 401,
		);

		$result = ( new InstaMigrateService() )->run();

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 1, $this->token_requests() );
		$this->assertSame( 1, $this->count_requests( self::ENGINE_URL, 'fresh-key' ) );
	}

	/**
	 * No retry when the worker returns the key that was just rejected.
	 *
	 * @return void
	 */
	public function test_unchanged_key_from_worker_skips_retry() {
		update_option( 'newfold_insta_api_key', 'same-key' );
		$this->worker_key   = 'same-key';
		$this->engine_codes = array( 'same-key' => 401 );

		( new InstaMigrateService() )->run();

		$this->assertSame( 1, $this->token_requests() );
		$this->assertSame( 1, $this->count_requests( self::ENGINE_URL, 'same-key' ) );
	}

	/**
	 * A key fetched fresh in this run is not fetched again after a 401.
	 *
	 * @return void
	 */
	public function test_freshly_fetched_key_is_not_refetched() {
		delete_option( 'newfold_insta_api_key' );
		$this->worker_key   = 'fresh-key';
		$this->engine_codes = array( 'fresh-key' => 401 );

		( new InstaMigrateService() )->run();

		$this->assertSame( 1, $this->token_requests() );
		$this->assertSame( 1, $this->count_requests( self::ENGINE_URL, 'fresh-key' ) );
	}

	/**
	 * A 401 from a host other than the InstaWP API does not count as a rejected key.
	 *
	 * @return void
	 */
	public function test_401_from_other_host_is_ignored() {
		update_option( 'newfold_insta_api_key', 'saved-key' );
		$this->worker_key   = 'other-key';
		$this->engine_codes = array( 'saved-key' => 500 );

		$other_host_401 = function ( $pre ) {
			do_action(
				'http_api_debug',
				array( 'response' => array( 'code' => 401 ) ),
				'response',
				'WpOrg\Requests\Requests',
				array(),
				'https://downloads.wordpress.org/plugin/instamigrate.zip'
			);
			return $pre;
		};
		add_filter( 'pre_http_request', $other_host_401, 5 );

		( new InstaMigrateService() )->run();

		remove_filter( 'pre_http_request', $other_host_401, 5 );

		$this->assertSame( 0, $this->token_requests() );
		$this->assertSame( 'saved-key', get_option( 'newfold_insta_api_key' ) );
	}

	/**
	 * A 401 from the InstaWP API for some other credential does not count as ours.
	 *
	 * @return void
	 */
	public function test_401_for_another_credential_is_ignored() {
		update_option( 'newfold_insta_api_key', 'saved-key' );
		$this->worker_key   = 'other-key';
		$this->engine_codes = array( 'saved-key' => 500 );

		$foreign_401 = function ( $pre ) {
			do_action(
				'http_api_debug',
				array( 'response' => array( 'code' => 401 ) ),
				'response',
				'WpOrg\Requests\Requests',
				array( 'headers' => array( 'Authorization' => 'Bearer someone-elses-key' ) ),
				self::ENGINE_URL
			);
			return $pre;
		};
		add_filter( 'pre_http_request', $foreign_401, 5 );

		( new InstaMigrateService() )->run();

		remove_filter( 'pre_http_request', $foreign_401, 5 );

		$this->assertSame( 0, $this->token_requests() );
		$this->assertSame( 2, $this->count_requests( self::ENGINE_URL, 'saved-key' ) );
	}

	/**
	 * Third-party code firing the hook with fewer args during connect does not fatal.
	 *
	 * @return void
	 */
	public function test_short_http_api_debug_call_does_not_fatal() {
		update_option( 'newfold_insta_api_key', 'saved-key' );
		$this->engine_codes = array( 'saved-key' => 500 );

		$error      = null;
		$short_call = function ( $pre ) use ( &$error ) {
			try {
				do_action( 'http_api_debug', array() );
			} catch ( \Throwable $e ) {
				$error = $e;
			}
			return $pre;
		};
		add_filter( 'pre_http_request', $short_call, 5 );

		( new InstaMigrateService() )->run();

		remove_filter( 'pre_http_request', $short_call, 5 );

		$this->assertNull( $error, $error ? $error->getMessage() : '' );
	}

	/**
	 * The listener is detached once connect returns, so later requests are untouched.
	 *
	 * @return void
	 */
	public function test_listener_is_removed_after_connect() {
		update_option( 'newfold_insta_api_key', 'saved-key' );
		$this->engine_codes = array( 'saved-key' => 500 );
		$before             = has_action( 'http_api_debug' );

		( new InstaMigrateService() )->run();

		$this->assertSame( $before, has_action( 'http_api_debug' ) );
	}
}
