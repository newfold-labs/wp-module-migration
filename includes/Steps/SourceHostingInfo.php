<?php

namespace NewfoldLabs\WP\Module\Migration\Steps;

use NewfoldLabs\WP\Module\Migration\Steps\AbstractStep;
use InstaWP\Connect\Helpers\Helper;

/**
 * Connection to InstaWp step.
 *
 * @package wp-module-migration
 */
class SourceHostingInfo extends AbstractStep {

	/**
	 * Source host url.
	 *
	 * @var string Source host url.
	 */
	private $source_host_url;

	/**
	 * Source hosting details.
	 *
	 * @var array Source hosting details.
	 */
	private $hosting_info;

	/**
	 * Construct. Init basic parameters.
	 *
	 * @param string $source_host_url Source host url.
	 */
	public function __construct( $source_host_url ) {
		$this->source_host_url = $source_host_url;
		$this->set_step_slug( 'SourceHostingInfo' );
		$this->run();
	}

	/**
	 * Execute the step.
	 *
	 * @return void
	 */
	protected function run() {

		if ( ! empty( $this->source_host_url ) ) {

			$plain_domain = $this->get_plain_domain( $this->source_host_url );

			$this->hosting_info = $this->get_domain_informations( $plain_domain );

			if ( is_array( $this->hosting_info ) && isset( $this->hosting_info['status'] ) && 'success' === $this->hosting_info['status'] ) {
				$this->success();
			} else {
				$this->set_response( array( 'message' => 'Source hosting details not retrieved correctly' ) );
				$this->failure();
			}
		}
	}

	/**
	 * Set the step as successful and store the API key.
	 *
	 * @return void
	 */
	protected function success() {
		parent::success();

		$this->set_data( 'SourceHostingData', $this->hosting_info );
	}

	/**
	 * Get IP address from domain.
	 *
	 * @param string $domain Domain url.
	 * @return string The IP Address.
	 */
	public function get_ip_from_domain( string $domain ): string {
		$domain = $this->get_plain_domain( $domain );

		// Initialize cURL session.
		$ch = curl_init();

		// Set cURL options.
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_URL            => "https://cloudflare-dns.com/dns-query?name=$domain&type=A",
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HTTPHEADER     => array(
					'Accept: application/dns-json',
				),
			)
		);

		// Execute the request.
		$response = curl_exec( $ch );
		$data     = json_decode( $response, true );

		curl_close( $ch );

		// Get the first IP address from the answer section.
		return $data['Answer'][0]['data'] ?? '';
	}

	/**
	 * Get the informations from an IP address.
	 *
	 * @param string $ip The IP address to get the informations from.
	 * @return array The informations.
	 */
	public function get_infos_from_ip( string $ip ): array {
		// Initialize cURL session.
		$ch = curl_init();
		// Set cURL options.
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_URL            => "http://ip-api.com/json/$ip",
				CURLOPT_RETURNTRANSFER => true,
			)
		);

		// Execute the request.
		$response = curl_exec( $ch );
		$data     = json_decode( $response, true );

		$fields_to_unset = array(
			'country',
			'countryCode',
			'region',
			'regionName',
			'city',
			'zip',
			'lat',
			'lon',
			'timezone',
			'org',
			'query',
		);
		foreach ( $fields_to_unset as $field ) {
			unset( $data[ $field ] );
		}

		curl_close( $ch );

		if ( 'success' === $data['status'] ) {
			return $data;
		}

		return array();
	}
	/**
	 * Get the domain informations from a domain
	 *
	 * @param string $domain The domain to get the informations from.
	 * @return array The informations.
	 */
	public function get_domain_informations( string $domain ): array {
		$ip = $this->get_ip_from_domain( $domain );

		if ( ! empty( $ip ) ) {
			$data = $this->get_infos_from_ip( $ip );
			if ( ! empty( $data ) ) {
				$data['domain'] = $this->get_plain_domain( $domain );
				return $data;
			}
		}

		return array();
	}

	/**
	 * Get the plain domain from a domain.
	 *
	 * @param string $domain The domain to get the plain domain from.
	 * @return string The plain domain.
	 */
	public function get_plain_domain( string $domain ): string {
		$parsed = parse_url( $domain );
		return $parsed['host'] ?? preg_replace( '#^https?://|/.*$#', '', $domain );
	}
}
