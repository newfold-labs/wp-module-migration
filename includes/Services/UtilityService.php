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
	 * Get migration status and source url by instaWp api
	 *
	 * @param string $migrate_group_uuid migration group id (it is stored in instawp_last_migration_details option).
	 * @return array
	 */
	public static function get_migration_data( $migrate_group_uuid ) {
		if ( ! empty( $migrate_group_uuid ) ) {
			$token = self::get_insta_api_key( BRAND_PLUGIN );
			if ( $token ) {
				$response = wp_remote_get(
					'https://app.instawp.io/api/v2/migrates-v3/status/' . $migrate_group_uuid,
					array(
						'headers' => array(
							'Authorization' => 'Bearer ' . $token,
						),
					)
				);
				if ( wp_remote_retrieve_response_code( $response ) === 200 && ! is_wp_error( $response ) ) {
					$body = wp_remote_retrieve_body( $response );
					return json_decode( $body, true );
				}
			}
		}

		return array();
	}

	/**
	 * Default InstaWP migration portal host for v4 status polling.
	 */
	const PORTAL_STATE_ORIGIN = 'https://migrate.instawp.io';

	/**
	 * Fetch v4 portal migration state from the InstaWP migration portal.
	 *
	 * @param string $migration_token Portal token from /start?t=...
	 * @return array
	 */
	public static function get_portal_migration_state( $migration_token ) {
		if ( empty( $migration_token ) ) {
			return array();
		}

		$base_url = apply_filters(
			'nfd_migration_portal_state_url',
			untrailingslashit( self::PORTAL_STATE_ORIGIN ) . '/api/portal/state',
			$migration_token
		);

		$url = add_query_arg( 't', $migration_token, $base_url );

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status_code ) {
			return array();
		}

		return is_array( $body ) ? $body : array();
	}

	/**
	 * Normalize portal state payload into migration status fields.
	 *
	 * @param array $portal_response Portal API response body.
	 * @return array
	 */
	public static function parse_portal_migration_state( array $portal_response ) {
		$data = isset( $portal_response['data'] ) && is_array( $portal_response['data'] )
			? $portal_response['data']
			: $portal_response;

		if ( ! is_array( $data ) ) {
			return array();
		}

		$state = $data;
		if ( isset( $data['state'] ) && is_array( $data['state'] ) ) {
			$state = $data['state'];
		}

		$status = self::read_portal_state_value( $state, array( 'status', 'migration_status', 'migration_state' ) );
		$status = self::normalize_portal_status( $status );

		return array(
			'status'             => $status,
			'migrate_group_uuid' => self::read_portal_state_value(
				$state,
				array( 'migrate_group_uuid', 'migration_id', 'migration_uuid', 'group_uuid', 'uuid' )
			),
			'source_site_url'    => self::read_portal_state_value(
				$state,
				array( 'source_site_url', 'source_url', 'origin_url' )
			),
		);
	}

	/**
	 * Map portal API statuses to migration module terminal statuses.
	 *
	 * @param string $status Raw portal status.
	 * @return string
	 */
	public static function normalize_portal_status( $status ) {
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
	 * Whether a normalized portal status should end polling.
	 *
	 * @param string $status Normalized portal status.
	 * @return bool
	 */
	public static function is_terminal_portal_status( $status ) {
		return in_array( $status, array( 'completed', 'failed', 'aborted' ), true );
	}

	/**
	 * Read the first non-empty string value from a portal state array.
	 *
	 * @param array $state Candidate state array.
	 * @param array $keys    Keys to check in order.
	 * @return string
	 */
	private static function read_portal_state_value( array $state, array $keys ) {
		foreach ( $keys as $key ) {
			if ( ! empty( $state[ $key ] ) && is_string( $state[ $key ] ) ) {
				return sanitize_text_field( $state[ $key ] );
			}
		}

		return '';
	}
}
