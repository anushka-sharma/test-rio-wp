<?php
/**
 * Module Publish
 *
 * @since 1.0.0
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */

/**
 * If new file with WPGlobusPlus_Publish class will be added, then rewrite $_class_publish_file variable.
 * @see wpglobus-plus\includes\module-wpseo\class-wpglobus-plus-yoastseo30.php
 */ 
if ( version_compare( WPGLOBUS_PLUS_VERSION, '1.1.20', '>=' ) ) {
	require_once 'class-wpglobus-plus-publish2.php';
} else {
	require_once 'class-wpglobus-plus-publish.php';
}
$WPGlobusPlus_Publish = new WPGlobusPlus_Publish();
