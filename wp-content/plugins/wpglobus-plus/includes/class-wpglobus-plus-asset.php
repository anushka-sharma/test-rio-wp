<?php
/**
 * Assets
 *
 * @package WPGlobusPlus
 */


/**
 * Asset management.
 */
class WPGlobusPlus_Asset {

	/**
	 * URL to the JS script.
	 *
	 * @param string $script_name [optional] The script name without extension.
	 *                            If not passed, will return the JS root URL.
	 *
	 * @return string The URL.
	 * @since 1.1.38
	 */
	public static function url_js( $script_name = '' ) {
		$url = WPGlobusPlus::$PLUGIN_DIR_URL . 'assets/js';
		if ( $script_name ) {
			$url .= '/' . $script_name . WPGlobus::SCRIPT_SUFFIX() . '.js';
		}

		return $url;
	}

	/**
	 * URL to the CSS sheet.
	 *
	 * @param string $sheet_name  [optional] The script name without extension.
	 *                            If not passed, will return the CSS root URL.
	 *
	 * @return string The URL.
	 * @since 1.1.38
	 */
	public static function url_css( $sheet_name = '' ) {
		$url = WPGlobusPlus::$PLUGIN_DIR_URL . 'assets/css';
		if ( $sheet_name ) {
			$url .= '/' . $sheet_name . '.css';
		}

		return $url;
	}
}
