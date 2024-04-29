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
		add_action( 'deleted_plugin', array( $this, 'delete_plugin' ), 10, 1 );
		add_action( 'pre_update_option_instawp_last_migration_details', array( $this, 'on_update_instawp_last_migration_details' ), 10, 1 );
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

		/**
		 * Updates showMigrationSteps option based on instawp_last_migration_details
		 *
		 * @param string $file path of plugin installed
		 */
	public function delete_plugin( $file ) {
		$migrationDetails     = (array) get_option( 'instawp_last_migration_details', array() );
		$isMigrationCompleted = $migrationDetails['status'];
		if ( 'instawp-connect/instawp-connect.php' === $file ) {
			if ( 'completed' === $isMigrationCompleted ) {
				$event = array(
					'category' => 'wonder_start',
					'action'   => 'migration_completed',
					'data'     => array(),
				);
				EventService::send( $event );
			} else {
				$event = array(
					'category' => 'wonder_start',
					'action'   => 'migration_failed',
					'data'     => array(),
				);
				EventService::send( $event );
			}
		}
	}

	/**
	 * Updates showMigrationSteps option based on instawp_last_migration_details
	 *
	 * @param array $new_option status of migration
	 */
	public function on_update_instawp_last_migration_details( $new_option ) {
		$value_updated = $new_option['status'];
		if ( 'completed' === $value_updated ) {
			update_option( 'showMigrationSteps', true );
		}
		return $new_option;
	}
}
