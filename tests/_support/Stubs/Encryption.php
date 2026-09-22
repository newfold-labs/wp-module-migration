<?php
/**
 * Pass-through stand-in for wp-module-data's Encryption helper, which is not a dev dependency.
 *
 * @package wp-module-migration
 */

namespace NewfoldLabs\WP\Module\Data\Helpers;

/**
 * Encryption stub.
 */
class Encryption {

	/**
	 * Return the value unchanged.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	public function encrypt( $value ) {
		return $value;
	}

	/**
	 * Return the value unchanged, or false when empty.
	 *
	 * @param string $cipher Stored value.
	 * @return string|false
	 */
	public function decrypt( $cipher ) {
		return empty( $cipher ) ? false : $cipher;
	}
}
