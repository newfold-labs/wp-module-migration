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
		if ( $container->plugin()->id === 'bluehost' ) {
			add_action( 'load-import.php', array( $this, 'register_wp_migration_tool' ) ); // Adds WordPress Migration tool to imports list
			add_action( 'admin_enqueue_scripts', array( $this, 'set_import_tools' ) );
		}
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
			'site_migration_wordpress_importer',
			__( 'WordPress Migration Tool', 'wp-module-migration' ),
			__( 'Migrate an existing WordPress site to this WordPress instance. This tool will make a copy of an existing site and automatically import it into this WordPress instance <strong>This will overwrite all the content.</strong>', 'wp-module-migration' ),
			array( $this, 'wordpress_migration_tool' )
		);
	}

	/**
	 * Initiates the Migration service redirects it the instawp screen
	 */
	public function wordpress_migration_tool() {
		$this->insta_service = new InstaMigrateService();
		$response            = $this->insta_service->install_instawp_connect();
		if ( ! is_wp_error( $response ) ) {
			wp_redirect( $response['redirect_url'] );
		} else {
			wp_safe_redirect( admin_url( 'import.php' ) );
		}
		die();
	}

	/**
	 * Changes the text WordPress to WordPress content in import page
	 */
	public function set_import_tools() {
		\wp_enqueue_script( 'nfd_migration_tool', NFD_MIGRATION_PLUGIN_URL . 'vendor/newfold-labs/wp-module-migration/includes/import-tools-changes.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_style( 'nfd_migration_tool', NFD_MIGRATION_PLUGIN_URL . 'vendor/newfold-labs/wp-module-migration/includes/styles.css', array(), '1.0', 'all' );
		$migration_data = array(
			'migration_title'       => __( 'Preparing your site', 'wp-module-migration' ),
			'migration_description' => __( 'Please wait a few seconds while we get your new account ready to import your existing WordPress site.', 'wp-module-migration' ),
			'wordpress_title'       => __( 'WordPress Content', 'wp-module-migration' ),
		);
		wp_localize_script( 'nfd_migration_tool', 'migration', $migration_data );
	}
}
