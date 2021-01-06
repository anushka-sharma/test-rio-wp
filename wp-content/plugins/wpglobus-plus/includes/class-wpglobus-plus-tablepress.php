<?php
/**
 * Class WPGlobusPlus_TablePress
 *
 * @since 1.1.1
 */

if ( ! class_exists( 'WPGlobusPlus_TablePress' ) ) :

	/**
	 * Class WPGlobus_For_TablePress
	 */
	class WPGlobusPlus_TablePress {

		/**
		 * Constructor
		 */
		public function __construct() {

			if ( is_admin() ) {

				add_action( 'admin_print_scripts', array(
					$this,
					'on_admin_scripts'
				) );

				add_action( 'admin_print_styles', array(
					$this,
					'on_admin_styles'
				) );

				add_filter( 'wpglobus_enabled_pages', array(
					$this,
					'enable_pages'
				) );

			} else {

				add_filter( 'tablepress_table_raw_render_data', array(
					$this,
					'render_data'
				) );

			}

		}

		/**
		 * Enable pages to load WPGlobus scripts and styles
		 *
		 * @param Array $pages
		 * @return array
		 */
		public function enable_pages( $pages ) {
			$pages[] = 'tablepress';

			return $pages;
		}

		/**
		 * Enqueue admin styles
		 */
		public function on_admin_styles() {

			if ( ! WPGlobus_WP::is_pagenow( 'admin.php' ) ) {
				return;
			}

			if ( ! empty( $_GET['page'] ) && 'tablepress' !== $_GET['page'] ) {
				return;
			}

			wp_enqueue_style(
				'wpglobus-plus-tablepress',
				WPGlobusPlus_Asset::url_css( 'wpglobus-plus-tablepress' ),
				array(),
				WPGLOBUS_PLUS_VERSION,
				'all'
			);

		}

		/**
		 * Enqueue admin scripts
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function on_admin_scripts() {

			if ( ! WPGlobus_WP::is_pagenow( 'admin.php' ) ) {
				return;
			}

			if ( ! empty( $_GET['page'] ) && 'tablepress' !== $_GET['page'] ) {
				return;
			}

			$action = 'tablepress-all-tables';

			if ( ! empty( $_GET['action'] ) && 'edit' === $_GET['action'] ) {
				$action = 'tablepress-edit';
			}

			wp_enqueue_script(
				'wpglobus-plus-tablepress',
				WPGlobusPlus_Asset::url_js( 'wpglobus-plus-tablepress' ),
				array( 'jquery' ),
				WPGLOBUS_PLUS_VERSION,
				true
			);

			wp_localize_script(
				'wpglobus-plus-tablepress',
				'WPGlobusPlusTablePress',
				array(
					'version'          => WPGLOBUS_PLUS_VERSION,
					'wpglobus_version' => WPGLOBUS_VERSION,
					'action'           => $action
				)
			);

		}

		/**
		 * Render table for translate at front-end
		 *
		 * @param Array $table
		 * @return array
		 */
		public function render_data( $table ) {

			if ( ! empty( $table['name'] ) ) {
				$table['name'] = WPGlobus_Core::text_filter( $table['name'], WPGlobus::Config()->language );
			}

			if ( ! empty( $table['description'] ) ) {
				$table['description'] =
					WPGlobus_Core::text_filter( $table['description'], WPGlobus::Config()->language );
			}

			if ( ! empty( $table['data'] ) ) {

				foreach ( $table['data'] as $row_key => $row ) {

					foreach ( $row as $key => $value ) {
						$table['data'][ $row_key ][ $key ] =
							WPGlobus_Core::text_filter( $value, WPGlobus::Config()->language );
					}
				}

			}

			return $table;
		}

	} // class

endif;

# --- EOF
