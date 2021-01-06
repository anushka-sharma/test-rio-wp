<?php
/**
 * Module Multilingual Taxonomies & CPTs.
 *
 * Version beta-9.
 * @since 1.3.7
 
 * Version beta-8.
 * @since 1.3.4
 *
 * Version beta-7.
 * @since 1.1.50
 *
 * @since 1.1.33
 *
 * @package WPGlobus Plus
 */
 
if ( version_compare(PHP_VERSION, WPGLOBUS_PLUS_TAXONOMIES_PHP_VERSION, '>=' ) ) {
	/**
	 * @see `check_taxonomies` function in wpglobus-plus\includes\class-wpglobus-plus-module-checker.php
	 * @see `options_page` function in wpglobus-plus\includes\wpglobus-plus-main.php
	 */
	if( defined('WPGLOBUS_PLUS_MODULE_TAXONOMIES_V7') && WPGLOBUS_PLUS_MODULE_TAXONOMIES_V7 ) {
		require_once( "class-wpglobus-plus-taxonomies-7.php" );	
	} else if( defined('WPGLOBUS_PLUS_MODULE_TAXONOMIES_V8') && WPGLOBUS_PLUS_MODULE_TAXONOMIES_V8 ) {
		require_once( "class-wpglobus-plus-taxonomies-8.php" );	
	} else {
		require_once( "class-wpglobus-plus-taxonomies-9.php" );	
	}
	new WPGlobusPlus_Taxonomies( $wpg_plus );
}

# --- EOF