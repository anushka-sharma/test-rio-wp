<?php
/**
 * File: wpglobus-plus-wpseo-functions.php
 *
 * @package WPGlobus-Plus
 */
/**
 * Activate plus access.
 * @since 1.2.0
 *
 * @param boolean $value
 * @return boolean|string
 */
if ( is_admin() ) { 
	function filter__wpglobus_yoastseo_plus_access($value) {
		
		/**
		 * @see wpglobus-plus\includes\wpglobus-plus-main.php
		 */
		$opts = (array) get_option( 'wpglobus_plus_options' );

		/**
		 * Options have not been saved yet.
		 * @since 1.2.3
		 */
		if ( ! isset( $opts['wpseo']['active_status'] ) ) {
			/**
			 * Active by default.
			 */
			return 'active';
		}

		if ( ! empty( $opts['wpseo']['active_status'] ) &&  $opts['wpseo']['active_status'] ) {
			return 'active';
		}

		return 'inactive';

	}
	add_filter( 'wpglobus_yoastseo_plus_access', 'filter__wpglobus_yoastseo_plus_access' );
}