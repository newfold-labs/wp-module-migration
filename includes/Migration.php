<?php
namespace NewfoldLabs\WP\Module\Migration;

use NewfoldLabs\WP\ModuleLoader\Container;

/**
 * Class Migration
 *
 * @package NewfoldLabs\WP\Module\Migration
 */
class Migration
{
/*
   * Container loaded from the brand plugin.
   *
   * @var Container
   */
  protected $container;

  /**
   * Array map of API controllers.
   *
   * @var array
   */
  protected $controllers = array(
    'NewfoldLabs\\WP\\Module\\Migration\\RestApi\\MigrateController',
  );

  public function __construct(Container $container)
  {
    $this->container = $container;
    add_action('rest_api_init', array($this, 'register_routes'));
  }

  public function register_routes()
  {
    foreach ($this->controllers as $controller) {
      $rest_api = new $controller();
      $rest_api->register_routes();
    }
  }
}
