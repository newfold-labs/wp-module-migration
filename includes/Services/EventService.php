<?php

namespace NewfoldLabs\WP\Module\Migration\Services;

/**
 * Class for handling analytics events.
 */
class EventService {

	/**
	 * Sends a Hiive Event to the data module API.
	 *
	 * @param array $event The event to send.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function send( $event ) {
		$event_data_request = new \WP_REST_Request(
			\WP_REST_Server::CREATABLE,
			NFD_MODULE_DATA_EVENTS_API
		);
		$event_data_request->set_body_params( $event );

		$response = rest_do_request( $event_data_request );
		if ( $response->is_error() ) {
			return $response->as_error();
		}

		return $response;
	}
}
