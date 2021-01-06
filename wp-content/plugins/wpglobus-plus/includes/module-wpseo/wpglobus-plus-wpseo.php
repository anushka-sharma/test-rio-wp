<?php
/**
 * Module WPSEO & YoastSEO
 *
 * @since 1.0.0
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */

if ( defined( 'WPSEO_VERSION' ) ) {

	/** @see \WPGlobus::__construct */
	WPGlobus::O()->vendors_scripts['WPSEO'] = true;
	
	$src_version = version_compare( WPSEO_VERSION, '3.0.0', '>' ) ? '30' : '';
	switch ( $src_version ) :
		case '30' :
			require_once( "class-wpglobus-plus-yoastseo{$src_version}.php" );
			WPGlobusPlusYoastSeo::controller();
		break;
		default:
			$src_version = version_compare( WPSEO_VERSION, '2.4', '<' ) ? '23' : '';
			if ( ! empty( $src_version ) ) {
				require_once( "class-wpglobus-plus-wpseo{$src_version}.php" );
			}
			global $WPGlobus_WPSEO_Metabox;
			if ( class_exists('WPGlobus_WPSEO_Metabox') ) {
				WPSEO_Options::get_instance();
				$WPGlobus_WPSEO_Metabox = new WPGlobus_WPSEO_Metabox();
			}
		break;
	endswitch;
	
}	