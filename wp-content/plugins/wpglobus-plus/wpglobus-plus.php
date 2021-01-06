<?php
/**
 * File: wpglobus-plus.php
 *
 * @package   WPGlobus-Plus
 * @author    WPGlobus
 * @category  Extension
 * @copyright Copyright 2014-2019 The WPGlobus Team: Alex Gor (alexgff) and Gregory Karpinsky (tivnet)
 */

/**
 * Plugin Name: WPGlobus Plus
 * Plugin URI: https://wpglobus.com/product/wpglobus-plus/
 * Description: Extend functionality of the <a href="https://wordpress.org/plugins/wpglobus/">WPGlobus Multilingual Plugin</a>.
 * Text Domain: wpglobus-plus
 * Domain Path: /languages/
 * Version: 1.3.11
 * Author: WPGlobus
 * Author URI: https://wpglobus.com/
 * License: GPL-3.0-or-later
 * License URI: https://spdx.org/licenses/GPL-3.0-or-later.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/includes/wpglobus-plus-tablepress-functions.php';
require_once dirname( __FILE__ ) . '/includes/module-acf/wpglobus-plus-acf-functions.php';
require_once dirname( __FILE__ ) . '/includes/module-wpseo/wpglobus-plus-wpseo-functions.php';
require_once dirname( __FILE__ ) . '/includes/module-editor/wpglobus-plus-wpglobeditor-functions.php';

add_action( 'plugins_loaded', 'wpglobus_plus_load', 11 );

/**
 * Load after the Main WPGlobus plugin.
 */
function wpglobus_plus_load() {

	// Main WPGlobus plugin is required.
	if ( ! defined( 'WPGLOBUS_VERSION' ) ) {
		add_action( 'admin_notices', 'wpglobus_plus_core_plugin_required' );

		return;
	}

	if ( 'off' === WPGlobus::Config()->toggle ) {
		return;
	}

	define( 'WPGLOBUS_PLUS_VERSION', '1.3.11' );
	/**
	 * @since 1.1.55
	 */
	define( 'WPGLOBUS_PLUS_TAXONOMIES_PHP_VERSION', '5.6.0' );

	require_once dirname( __FILE__ ) . '/includes/wpglobus-plus-main.php';
	WPGlobusPlus::$PLUGIN_DIR_PATH = plugin_dir_path( __FILE__ );
	WPGlobusPlus::$PLUGIN_DIR_URL  = plugin_dir_url( __FILE__ );

	require_once dirname( __FILE__ ) . '/includes/class-wpglobus-plus-asset.php';

	// Load translations.
	load_plugin_textdomain( 'wpglobus-plus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	if ( is_admin() ) {

		global $wpdb;

		$load_modules = true;

		if ( WPGlobus_WP::is_pagenow( array( 'post.php' ) ) ) :

			/**
			 * Check for disabled post type.
			 */
			$post_id = WPGlobus_Utils::safe_get( 'post' );

			if ( empty( $post_id ) ) {

				$load_modules = true;

			} else {

				$post_type = $wpdb->get_col( $wpdb->prepare( "SELECT post_type FROM $wpdb->posts WHERE ID = %d ", $post_id ) );

				if ( ! empty( $post_type ) ) {
					$post_type = $post_type[0];
				}

				$load_modules = true;
				if ( ! empty( $post_type ) && WPGlobus::O()->disabled_entity( $post_type ) ) {
					$load_modules = false;
				}
			}
		endif;

		if ( ! $load_modules ) {
			return;
		}
	}

	/**
	 * Load modules.
	 * Modules can be switched off in the WPGlobus Plus admin panel.
	 */
	require_once dirname( __FILE__ ) . '/includes/class-wpglobus-plus-module-checker.php';
	$checker      = new WPGlobus_Plus_Module_Checker();
	$plus_modules = $checker->get_modules();

	// Not a global.
	$wpg_plus = new WPGlobusPlus( $plus_modules );

	$is_ajax = false;
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX
		 && ! empty( $_POST['action'] ) // WPCS: Input var ok.
		 && 'WPGlobusPlus_process_ajax' === $_POST['action'] ) { // WPCS: Input var ok.
		$is_ajax = true;
	}

	foreach ( $plus_modules as $module => $option ) {
		$load = true;

		if ( isset( $wpg_plus->options[ $module ]['active_status'] ) &&
			 ! $wpg_plus->options[ $module ]['active_status']
		) {
			$load = false;
		}

		$ajax_data = array();
		if ( isset( $_POST['order'] ) ) {
			$ajax_data = wp_unslash( $_POST['order'] ); // WPCS: Input var ok, sanitization ok.
		}

		if ( $is_ajax && isset( $ajax_data['module'] ) && $module === $ajax_data['module'] ) {

			if (
				( ! empty( $ajax_data['moduleData']['register_activation'] ) && 'true' === $ajax_data['moduleData']['register_activation'] )
				||
				( ! empty( $ajax_data['moduleData']['register_deactivation'] ) && 'true' === $ajax_data['moduleData']['register_deactivation'] )
			) {
				$load = true;
			}
		}

		/**
		 * Filter to enable/disable loading of module.
		 *
		 * @since 1.1.21
		 *
		 * @param bool $load Enable module loading by default.
		 */
		$load = apply_filters( 'wpglobus_plus_' . $module . '_start', $load );

		if ( $load ) {

			$wpg_plus->set_current_module( $module );

			if ( empty( $option['subfolder'] ) ) {
				/* @noinspection PhpIncludeInspection */
				require_once dirname( __FILE__ ) . '/includes/wpglobus-plus-' . $module . '.php';
			} else {
				/* @noinspection PhpIncludeInspection */
				require_once dirname( __FILE__ ) . '/includes/' . $option['subfolder'] . '/wpglobus-plus-' . $module . '.php';
			}
		}
	}
}

/**
 * Setup updater.
 *
 * @since    1.1.19
 * @requires WPGLOBUS_VERSION 1.5.9
 */
function wpglobus_plus__setup_updater() {
	/* @noinspection PhpUndefinedClassInspection */
	new TIVWP_Updater( array(
		'plugin_file' => __FILE__,
		'product_id'  => 'WPGlobus Plus',
		'url_product' => 'https://wpglobus.com/product/wpglobus-plus/',
	) );
}

add_action( 'tivwp_updater_factory', 'wpglobus_plus__setup_updater' );

/**
 * Display an admin notice in WordPress admin area.
 *
 * @since 1.1.31
 */
function wpglobus_plus_core_plugin_required() {
	echo '<div class="notice error"><p>';

	printf(
		// Translators: %1$s - this plugin name. %2$s - the required plugin name.
		esc_html__( 'The %1$s will not function unless the core plugin, %2$s, is activated.', 'wpglobus-plus' ),
		'<strong>WPGlobus Plus</strong>',
		'<strong>WPGlobus</strong>'
	);

	echo '</p></div>';
}
