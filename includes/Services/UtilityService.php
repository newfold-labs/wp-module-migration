<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

/**
 * Utility Service
 */
class UtilityService {
	/**
	 * Get the api key from worker
	 */
	public static function get_insta_api_key() {
		$insta_cf_worker = NFD_PROXY_ACCESS_WORKER . '/get/token?access_token=BH_MIGRATION_API_KEY';
		$insta_cf_data   = wp_remote_get(
			$insta_cf_worker,
			array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Accept'        => 'application/json',
					'PHP_VERSION'   => PHP_VERSION,
					'migration_key' => true,
					'site_url'      => get_option( 'siteurl', '' ),
				),
			)
		);
		$insta_response  = json_decode( wp_remote_retrieve_body( $insta_cf_data ) );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		return $insta_response ? base64_decode( $insta_response->data ) : '';
	}
}
