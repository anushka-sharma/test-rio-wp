<?php
/**
 * Module Publish
 *
 * @since 1.0.0
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */

if ( version_compare( WPGLOBUS_PLUS_VERSION, '1.1.20', '>=' ) ) {
	require_once 'class-wpglobus-plus-publish2.php';
} else {
	require_once 'class-wpglobus-plus-publish.php';
}
$WPGlobusPlus_Publish = new WPGlobusPlus_Publish();
