<?php

namespace NewfoldLabs\WP\Module\Migration\RestApi;

use NewfoldLabs\WP\Module\Migration\Services\InstaMigrateService;


class MigrateController {


	protected $namespace = 'newfold-migration/v1';

	protected $rest_base = '/migrate';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/connect',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'connectInstawp' ),
					'permission_callback' => null,
				),
			)
		);
	}

	public function connectInstawp() {
		$instaService = new InstaMigrateService();
		$response     = $instaService->InstallInstaWpConnect();

		return $response;
	}
}
