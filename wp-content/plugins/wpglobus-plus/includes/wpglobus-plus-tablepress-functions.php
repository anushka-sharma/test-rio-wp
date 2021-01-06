<?php

/**
 * Render table for translate at admin
 * We need use filter here because it loads too late in class WPGlobusPlus_TablePress
 * @since 1.1.1
 * @see WPGlobusPlus_TablePress::render_data filter tablepress_table_raw_render_data at class-wpglobus-plus-tablepress.php
 *
 * @param Array $table
 * @return array
 */
function wpglobus_plus_tablepress_render_data( $table ) {
	
	if ( ! is_admin() ) {
		return $table;
	}	
	
	if ( class_exists( 'WPGlobus' ) ) :

		if ( ! empty( $table['name'] ) ) {
			$table['name'] = WPGlobus_Core::text_filter( $table['name'], WPGlobus::Config()->language ); 	
		}
		
		if ( ! empty( $table['description'] ) ) {
			$table['description'] = WPGlobus_Core::text_filter( $table['description'], WPGlobus::Config()->language ); 	
		}	
		
		if ( ! empty( $table['data'] ) ) {
		
			foreach( $table['data'] as $row_key=>$row ) {
					
				foreach( $row as $key=>$value ) {	
					$table['data'][$row_key][$key] = WPGlobus_Core::text_filter( $value, WPGlobus::Config()->language ); 	
				}
			}	
		
		}
	
	endif;
	
	return $table;	

}

add_filter( 'tablepress_table_raw_render_data', 'wpglobus_plus_tablepress_render_data' );

# --- EOF
