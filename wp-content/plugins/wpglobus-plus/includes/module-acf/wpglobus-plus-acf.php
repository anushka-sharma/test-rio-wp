<?php
/**
 * Module Acf
 *
 * @since 1.0.0
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */
 
if ( WPGlobus::O()->vendors_scripts['ACF'] || WPGlobus::O()->vendors_scripts['ACFPRO'] ) {
	require_once( "class-wpglobus-plus-acf.php" );	
	$WPGlobusPlus_Acf = new WPGlobusPlus_Acf();
}	
