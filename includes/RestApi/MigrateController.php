<?php

namespace NewfoldLabs\WP\Module\Migration\RestApi;

use NewfoldLabs\WP\Module\Migration\Services\InstaMigrateService;

/**
 * Class MigrateController
 */
class MigrateController {

	/**
	 * REST namespace
	 *
	 * @var string
	 */
	protected $namespace = 'newfold-migration/v1';

	/**
	 * REST base
	 *
	 * @var string
	 */
	protected $rest_base = '/migrate';

	/**
	 * Registers rest routes for MigrateController.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/connect',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'connectInstawp' ),
					'permission_callback' => array( $this, 'rest_is_authorized_admin' ),
				),
			)
		);
	}

	/**
	 * Initiates the connnection with instawp plugin
	 *
	 * @return array
	 */
	public function connectInstawp() {
		$instaService = new InstaMigrateService();
		$response     = $instaService->InstallInstaWpConnect();

		return $response;
	}

	/**
	 * Confirm REST API caller has ADMIN user capabilities.
	 *
	 * @return boolean
	 */
	public static function rest_is_authorized_admin() {
			$admin = 'manage_options';
			return \is_user_logged_in() && \current_user_can( $admin );
	}
}
