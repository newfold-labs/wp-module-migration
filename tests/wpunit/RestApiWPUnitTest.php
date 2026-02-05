<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\RestApi\RestApi;

/**
 * RestApi wpunit tests.
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\RestApi\RestApi
 */
class RestApiWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Rest_api_init registers newfold-migration REST routes.
	 *
	 * @return void
	 */
	public function test_rest_api_init_registers_migration_routes() {
		new RestApi();
		do_action( 'rest_api_init' );
		$server = rest_get_server();
		$routes = $server->get_routes();
		$found  = array_filter(
			array_keys( $routes ),
			function ( $route ) {
				return strpos( $route, 'newfold-migration' ) !== false;
			}
		);
		$this->assertNotEmpty( $found, 'Expected newfold-migration routes to be registered' );
	}
}
