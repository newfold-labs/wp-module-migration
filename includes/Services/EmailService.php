<?php

namespace NewfoldLabs\WP\Module\Migration\Services;

/**
 * Class for handling the sending of Emails.
 */
class EmailService {
	/**
	 * The settings for the email.
	 *
	 * @var array
	 */
	protected $settings = array(
		'to'      => '',
		'subject' => '',
		'body'    => '',
		'header'  => '',
	);
	/**
	 * Send an email.
	 *
	 * @param string $settings array of settings.
	 */
	public function __construct( $settings = array() ) {
		if ( ! empty( $settings ) && is_array( $settings ) ) {
			foreach ( $settings as $key => $value ) {
				$method = 'set_' . $key;
				if ( is_callable( array( $this, $method ) ) ) {
					$this->$method( $value );
				}
			}
		}
	}
	/**
	 * Set the email recipient.
	 *
	 * @param array $to the email recipient.
	 */
	public function set_to( $to ) {
		$this->settings['to'] = $to;
	}
	/**
	 * Set the email subject.
	 *
	 * @param string $subject the email subject.
	 */
	public function set_subject( $subject ) {
		$this->settings['subject'] = $subject;
	}
	/**
	 * Set the email body.
	 *
	 * @param string $body the email body.
	 */
	public function set_body( $body ) {
		$this->settings['body'] = $body;
	}
	/**
	 * Set the email header.
	 *
	 * @param string $header the email header.
	 */
	public function set_header( $header ) {
		$this->settings['header'] = $header;
	}
	/**
	 * Send the email.
	 *
	 * @return bool True if the email was sent, false otherwise.
	 */
	public function send() {
		$to      = $this->settings['to'];
		$subject = $this->settings['subject'];
		$body    = $this->settings['body'];
		$header  = $this->settings['header'];

		$sent = false;
		if ( ! empty( $to ) && ! empty( $subject ) && ! empty( $body ) ) {
			$sent = wp_mail( $to, $subject, $body, $header );
		}

		return $sent;
	}
}
