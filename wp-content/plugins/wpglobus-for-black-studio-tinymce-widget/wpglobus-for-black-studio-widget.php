<?php
/**
 * Plugin Name: WPGlobus for Black Studio TinyMCE Widget
 * Plugin URI: https://github.com/WPGlobus/wpglobus-for-black-studio-tinymce-widget
 * Description: WPGlobus add-on for Black Studio TinyMCE Widget
 * Text Domain: 
 * Domain Path: 
 * Version: 1.2.0
 * Author: WPGlobus
 * Author URI: http://www.wpglobus.com/
 * Network: false
 * License: GPL2
 * Credits: Alex Gor (alexgff) and Gregory Karpinsky (tivnet)
 * Copyright 2015-2016 WPGlobus
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists('Black_Studio_TinyMCE_Plugin') ) {

	add_action( 'plugins_loaded', 'wpglobus_black_studio_widget_load' );
	function wpglobus_black_studio_widget_load() {

		// Main WPGlobus plugin is required.
		if ( ! defined( 'WPGLOBUS_VERSION' ) ) {
			return;
		}
		
		define( 'WPGLOBUS_BS_WIDGET_VERSION', '1.2.0' );

		require_once( 'includes/class-wpglobus-for-black-studio-widget.php' );

		$WPGlobus_BSWidget = new WPGlobus_BSWidget();
			
	}
	
}