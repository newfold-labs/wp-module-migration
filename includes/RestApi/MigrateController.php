<?php

namespace NewfoldLabs\WP\Module\Migration\RestApi;

use NewfoldLabs\WP\Module\Migration\Services\InstaMigrateService;


class MigrateController
{

  protected $namespace = 'newfold-migration/v1';

  protected $rest_base = '/migrate';

  public function register_routes()
  {
    register_rest_route(
      $this->namespace,
      $this->rest_base . '/connect',
      array(
        array(
          'methods' => \WP_REST_Server::READABLE,
          'callback' => array($this, 'connectInstawp'),
          'permission_callback' => null
        ),
      )
    );

    register_rest_route(
      $this->namespace,
      $this->rest_base . '/get-url',
      array(
        array(
          'methods' => \WP_REST_Server::READABLE,
          'callback' => array($this, 'getRedirectUrl'),
          'permission_callback' => null
        ),
      )
    );
  }

  public function connectInstawp()
  {
    $instaService = new InstaMigrateService();

    $instaService->InstallInstaWpConnect();
  }

  public function getRedirectUrl()
  {
    $site_url = get_option('siteurl', false);
    if($site_url){
      $redirect_url = $site_url . '/wp-admin/index.php?page=nfd-onboarding#/sitegen/step/migration';
      return new \WP_REST_Response(
        array(
          'status' => 'success',
          'url' => $redirect_url
        ),
        200
      );
    }

    return new \WP_Error(
      'Bad request',
      'failed to get the site url, please try again',
      array('status' => 400)
  );
  }
}
