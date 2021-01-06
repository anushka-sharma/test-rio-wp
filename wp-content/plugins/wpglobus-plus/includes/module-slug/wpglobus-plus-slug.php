<?php
/**
 * Module Slug
 *
 * @since 1.0.0
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */
 
if ( WPGlobus::O()->vendors_scripts['ACF'] ) {
	require_once( "class-wpglobus-plus-slug-acf.php" );	
	$WPGlobusPlusSlug_Acf = new WPGlobusPlusSlug_Acf();
}	
require_once( "class-wpglobus-plus-slug.php" );	
$WPGlobusPlus_Slug = new WPGlobusPlus_Slug();

