<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\Services\InstaMigrateService;

/**
 * InstaMigrateService wpunit tests.
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\Services\InstaMigrateService
 */
class InstaMigrateServiceWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Ensure proxy constants exist for URL rewrite tests.
	 *
	 * @return void
	 */
	private function ensure_proxy_constants() {
		if ( ! defined( 'NFD_MIGRATION_PROXY_WORKER' ) ) {
			define( 'NFD_MIGRATION_PROXY_WORKER', 'https://migrate.bluehost.com' );
		}
		if ( ! defined( 'INSTAWP_MIGRATE_ENDPOINT' ) ) {
			define( 'INSTAWP_MIGRATE_ENDPOINT', 'migrate/bluehost' );
		}
	}

	/**
	 * Invoke the private redirect-url normalizer.
	 *
	 * @param string $migration_url InstaWP migration URL.
	 * @return string
	 */
	private function normalize_redirect_url( $migration_url ) {
		$this->ensure_proxy_constants();

		$service    = new InstaMigrateService();
		$reflection = new \ReflectionClass( $service );
		$method     = $reflection->getMethod( 'normalize_migration_redirect_url' );
		$method->setAccessible( true );

		return $method->invoke( $service, $migration_url );
	}

	/**
	 * V4 URLs swap the host and prefix the brand proxy path.
	 *
	 * @return void
	 */
	public function test_v4_redirect_prefixes_brand_path_and_keeps_query() {
		$normalized = $this->normalize_redirect_url( 'https://migrate.instawp.io/start?t=secret-token' );

		$this->assertSame(
			untrailingslashit( NFD_MIGRATION_PROXY_WORKER ) . '/' . trim( INSTAWP_MIGRATE_ENDPOINT, '/' ) . '/start?t=secret-token',
			$normalized
		);
	}

	/**
	 * Already-prefixed v4 paths are not doubled.
	 *
	 * @return void
	 */
	public function test_v4_redirect_does_not_double_brand_path_prefix() {
		$prefix     = trim( INSTAWP_MIGRATE_ENDPOINT, '/' );
		$normalized = $this->normalize_redirect_url( 'https://migrate.instawp.io/' . $prefix . '/start?t=token' );

		$this->assertSame(
			untrailingslashit( NFD_MIGRATION_PROXY_WORKER ) . '/' . $prefix . '/start?t=token',
			$normalized
		);
	}

	/**
	 * Empty prefix filter keeps the original path after the host swap.
	 *
	 * @return void
	 */
	public function test_v4_redirect_can_disable_brand_path_prefix() {
		add_filter( 'nfd_migration_brand_proxy_path_prefix', '__return_empty_string' );

		$normalized = $this->normalize_redirect_url( 'https://migrate.instawp.io/start?t=token' );

		remove_filter( 'nfd_migration_brand_proxy_path_prefix', '__return_empty_string' );

		$this->assertSame(
			untrailingslashit( NFD_MIGRATION_PROXY_WORKER ) . '/start?t=token',
			$normalized
		);
	}

	/**
	 * Non-InstaWP hosts are left unchanged.
	 *
	 * @return void
	 */
	public function test_unrelated_host_is_not_rewritten() {
		$url = 'https://example.com/start?t=token';
		$this->assertSame( $url, $this->normalize_redirect_url( $url ) );
	}

	/**
	 * V3 app.instawp.io URLs still rebuild with g_id, locale, and the brand path.
	 *
	 * @return void
	 */
	public function test_v3_redirect_rebuilds_brand_proxy_url() {
		$this->ensure_proxy_constants();
		update_option( 'instawp_api_options', array( 'group_uuid' => '2cdd0e1e-20ec-45a1-8a3a-96748d36bac8' ) );

		$normalized = $this->normalize_redirect_url( 'https://app.instawp.io/migrate' );

		delete_option( 'instawp_api_options' );

		$this->assertSame(
			sprintf(
				'%s/%s?g_id=%s&locale=%s',
				untrailingslashit( NFD_MIGRATION_PROXY_WORKER ),
				INSTAWP_MIGRATE_ENDPOINT,
				rawurlencode( '2cdd0e1e-20ec-45a1-8a3a-96748d36bac8' ),
				rawurlencode( get_locale() )
			),
			$normalized
		);
	}
}
