<?php
namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\ModuleLoader\Container;
use NewfoldLabs\WP\Module\Migration\Services\InstaMigrateService;

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
	 * Option settings
	 *
	 * @var array
	 */
	protected $options = array(
		'nfd_migrate_site' => 'boolean',
	);

	/**
	 * Migration constructor.
	 *
	 * @param Container $container Container loaded from the brand plugin.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;

		add_filter(
			'newfold_data_listeners',
			function ( $listeners ) {
				$listeners[] = '\\NewfoldLabs\\WP\\Module\\Migration\\Listeners\\Wonder_Start';
				return $listeners;
			}
		);

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'pre_update_option_nfd_migrate_site', array( $this, 'on_update_nfd_migrate_site' ) );
		add_action( 'pre_update_option_instawp_last_migration_details', array( $this, 'on_update_instawp_last_migration_details' ), 10, 1 );
		add_action( 'admin_init', array( $this, 'register_wp_migration_tool' ) );
	}

	/**
	 * Registering the rest routes
	 */
	public function register_routes() {
		foreach ( $this->controllers as $controller ) {
			$rest_api = new $controller();
			$rest_api->register_routes();
		}
		self::register_settings();
	}

	/**
	 * Triggers on instawp connect installation
	 *
	 * @param boolean $option status of migration
	 */
	public function on_update_nfd_migrate_site( $option ) {
		$this->insta_service = new InstaMigrateService();
		$this->insta_service->install_instawp_connect();
		return $option;
	}

	/**
	 * Updates nfd_show_migration_steps option based on instawp_last_migration_details
	 *
	 * @param array $new_option status of migration
	 */
	public function on_update_instawp_last_migration_details( $new_option ) {
		$value_updated = $new_option['status'];
		if ( 'completed' === $value_updated ) {
			update_option( 'nfd_show_migration_steps', true );
		}
		return $new_option;
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		foreach ( $this->options as $option => $type ) {
			\register_setting(
				'general',
				$option,
				array(
					'show_in_rest' => true,
					'type'         => $type,
					'description'  => __( 'NFD migration Options', 'wp-module-migration' ),
				)
			);
		}
	}

	/**
	 * Register WordPress Migration Tool to imports.
	 */
	public function register_wp_migration_tool() {			
		register_importer( 
			'wordpress_migration_site_importer',
			__( 'WordPress Migration Tool', 'wp-module-migration' ),
			__( 'Migrate an existing WordPress site to this WordPress instance. This tool will make a copy of an existing site and automatically import it into this WordPress instance <strong>This will overwrite all the content.</strong>', 'wp-module-migration' ), 
			array( $this, 'wordpress_migration_tool' ) );
	}

	public function wordpress_migration_tool() {
		error_log("welcome");
	}
}
