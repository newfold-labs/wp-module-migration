<?php

namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\Module\Migration\RestApi\MigrateController;

/**
 * MigrateController wpunit tests.
 *
 * @coversDefaultClass \NewfoldLabs\WP\Module\Migration\RestApi\MigrateController
 */
class MigrateControllerWPUnitTest extends \lucatume\WPBrowser\TestCase\WPTestCase {

	/**
	 * Controller has expected namespace and rest_base.
	 *
	 * @return void
	 */
	public function test_controller_namespace_and_rest_base() {
		$controller  = new MigrateController();
		$reflection  = new \ReflectionClass( $controller );
		$namespace   = $reflection->getProperty( 'namespace' );
		$namespace->setAccessible( true );
		$rest_base   = $reflection->getProperty( 'rest_base' );
		$rest_base->setAccessible( true );
		$this->assertSame( 'newfold-migration/v1', $namespace->getValue( $controller ) );
		$this->assertSame( '/migrate', $rest_base->getValue( $controller ) );
	}

	/**
	 * Rest_is_authorized_admin returns false when user not logged in.
	 *
	 * @return void
	 */
	public function test_rest_is_authorized_admin_returns_false_when_not_logged_in() {
		wp_set_current_user( 0 );
		$this->assertFalse( MigrateController::rest_is_authorized_admin() );
	}

	/**
	 * Rest_is_authorized_admin returns true when admin is logged in.
	 *
	 * @return void
	 */
	public function test_rest_is_authorized_admin_returns_true_for_admin() {
		$user_id = static::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$this->assertTrue( MigrateController::rest_is_authorized_admin() );
	}

	/**
	 * Register_routes registers newfold-migration REST endpoints on rest_api_init.
	 *
	 * @return void
	 */
	public function test_register_routes_registers_rest_endpoints() {
		$controller = new MigrateController();
		add_action( 'rest_api_init', array( $controller, 'register_routes' ) );
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
