<?php
namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\ModuleLoader\Container;
use NewfoldLabs\WP\Module\Migration\Services\InstaMigrateService;
use NewfoldLabs\WP\Module\Migration\Services\EventService;

/**
 * Class Migration
 *
 * @package NewfoldLabs\WP\Module\Migration
 */
class Migration {
	/**
	 * Container loaded from the brand plugin.
	 *
	 * @var Container
	 */
	protected $container;

	/**
	 * To create insta service instance
	 *
	 * @var insta_service
	 */
	protected $insta_service;

	/**
	 * Array map of API controllers.
	 *
	 * @var array
	 */
	protected $controllers = array(
		'NewfoldLabs\\WP\\Module\\Migration\\RestApi\\MigrateController',
	);

	/**
	 * Migration constructor.
	 *
	 * @param Container $container Container loaded from the brand plugin.
	 */
	public function __construct( Container $container ) {
		$this->container     = $container;
		$this->insta_service = new InstaMigrateService();

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'pre_update_option_nfd_migrate_site', array( $this, 'on_update_nfd_migrate_site' ) );
		add_action( 'deleted_plugin', array( $this, 'delete_plugin' ), 10, 2 );
	}

	/**
	 * Registering the rest routes
	 */
	public function register_routes() {
		foreach ( $this->controllers as $controller ) {
			$rest_api = new $controller();
			$rest_api->register_routes();
		}
	}

	/**
	 * Triggers on instawp connect installation
	 */
	public function on_update_nfd_migrate_site() {
		$response = $this->insta_service->install_instawp_connect();
	}

	public function delete_plugin( $file, $deleted ) {
		$migrationDetails         = (array) get_option( 'instawp_last_migration_details', array() );
		$isMigrationCompleted = $migrationDetails['status'];
		if ( 'instawp-connect/instawp-connect.php' === $file ) {
			if (  $isMigrationCompleted === 'completed')
			{
				$event = [
					"category" => "wonder_start",
					"action" => "migration_completed",
					"data" => []
			];
				EventService::send( $event );
			} else {
				$event = [
					"category" => "wonder_start",
					"action" => "migration_failed",
					"data" => []
			];
				EventService::send( $event );
			}
			
	 }
	}
}
