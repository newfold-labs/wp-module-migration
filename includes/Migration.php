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

	/*
	* Container loaded from the brand plugin.
	*
	* @var Container
	*/
	protected $container;
	protected $instaService;

	/**
	 * Array map of API controllers.
	 *
	 * @var array
	 */
	protected $controllers = array(
		'NewfoldLabs\\WP\\Module\\Migration\\RestApi\\MigrateController',
	);

	public function __construct( Container $container ) {
		$this->container    = $container;
		$this->instaService = new InstaMigrateService();

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		add_action( 'pre_update_option_nfd_migrate_site', array( $this, 'on_update_nfd_migrate_site' ) );
	}

	public function register_routes() {
		foreach ( $this->controllers as $controller ) {
			$rest_api = new $controller();
			$rest_api->register_routes();
		}
	}

	public function on_update_nfd_migrate_site() {
		$response = $this->instaService->InstallInstaWpConnect();
	}
	
}
