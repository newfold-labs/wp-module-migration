<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

use NewFoldLabs\WP\Module\SSO\SSO_REST_Controller;
/**
 * Migration SSO service
 */
class MigrationSSO {

	/**
	 * Get SSO link
	 */
	public static function get_magic_login_url() {
		$request  = new SSO_REST_Controller();
		$response = $request->get_item( new \WP_REST_Request() );
		return ( $response );
	}
}
