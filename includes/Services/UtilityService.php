<?php
namespace NewfoldLabs\WP\Module\Migration\Services;

/**
 * Utility Service
 */
class UtilityService {
	/**
	 * Get the api key from worker
	 *
	 * @param string $brand name of the brand
	 */
	public static function get_insta_api_key( $brand ) {
		$insta_cf_worker = NFD_MIGRATION_PROXY_WORKER . '/token?brand=' . $brand;
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

	/**
	 * Get v3 migration status and source url from InstaWP API.
	 *
	 * @param string $migrate_group_uuid Migration group id from instawp_last_migration_details.
	 * @return array
	 */
	public static function get_migration_data( $migrate_group_uuid ) {
		return self::fetch_insta_migration_status(
			'migrates-v3/status/' . $migrate_group_uuid,
			$migrate_group_uuid
		);
	}

	/**
	 * Get v4 migration details from InstaWP API.
	 *
	 * @param string $migrate_group_uuid Migration group id from instawp_last_migration_details.
	 * @return array
	 */
	public static function get_v4_migration_data( $migrate_group_uuid ) {
		return self::fetch_insta_migration_status(
			'migrate-v4/' . $migrate_group_uuid,
			$migrate_group_uuid
		);
	}

	/**
	 * Fetch migration enrichment from InstaWP (v4 first, then v3).
	 *
	 * @param string $migrate_group_uuid Migration group UUID.
	 * @return array
	 */
	public static function get_migration_enrichment( $migrate_group_uuid ) {
		$response = self::get_v4_migration_data( $migrate_group_uuid );
		if ( is_array( $response ) && ! empty( $response ) ) {
			return $response;
		}

		return self::get_migration_data( $migrate_group_uuid );
	}

	/**
	 * Normalize migration status values from options or InstaWP APIs.
	 *
	 * @param string $status Raw migration status.
	 * @return string
	 */
	public static function normalize_migration_status( $status ) {
		$status = strtolower( sanitize_text_field( (string) $status ) );

		if ( 'successful' === $status ) {
			return 'completed';
		}

		if ( in_array( $status, array( 'cancelled', 'canceled' ), true ) ) {
			return 'aborted';
		}

		return $status;
	}

	/**
	 * Fetch migration details from app.instawp.io.
	 *
	 * @param string $path               API path after /api/v2/.
	 * @param string $migrate_group_uuid Migration group UUID.
	 * @return array
	 */
	private static function fetch_insta_migration_status( $path, $migrate_group_uuid ) {
		if ( empty( $migrate_group_uuid ) ) {
			return array();
		}

		$token = self::get_insta_api_key( BRAND_PLUGIN );
		if ( empty( $token ) ) {
			return array();
		}

		$response = wp_remote_get(
			'https://app.instawp.io/api/v2/' . ltrim( $path, '/' ),
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $token,
				),
			)
		);

		if ( wp_remote_retrieve_response_code( $response ) === 200 && ! is_wp_error( $response ) ) {
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			return is_array( $data ) ? $data : array();
		}

		return array();
	}
}
