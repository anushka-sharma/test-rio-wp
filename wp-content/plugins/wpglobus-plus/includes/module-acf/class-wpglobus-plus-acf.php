<?php
/**
 * Class WPGlobusPlus_Acf
 * @since 1.0.0
 */

if ( ! class_exists( 'WPGlobusPlus_Acf' ) ) :

	class WPGlobusPlus_Acf {

		/**
		 * Constructor
		 */
		public function __construct() {

			$enabled_pages = array(
				'post.php',
				'post-new.php'
			);

			if ( WPGlobus_WP::is_pagenow( $enabled_pages ) ) :

				add_filter( 'acf/fields/wysiwyg/toolbars', array( $this, 'add_buttons' ), 1 );
				add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ), 1 );
				add_action( 'admin_print_scripts', array( $this, 'admin_scripts' ) );

			endif;
			
			/**
			 * @since 1.1.55
			 */
			add_action( 'wp_ajax_' . __CLASS__ . '_process_ajax', array( $this, 'on__process_ajax' ) );
			
		}

		/**
		 * Process ajax.
		 * @since 1.1.55
		 */
		public function on__process_ajax(){
			
			$order = $_POST['order'];
			$post_id = (int) $order['postID'];
			
			$order['hasTranslations'] = false;
			
			if ( $post_id > 0 ) {
				
				global $wpdb;
				
				$sql = $wpdb->prepare( "SELECT meta_key FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_value = '%s'", $post_id, $order['meta_value'] );
				$col = $wpdb->get_col( $sql );

				if ( ! empty($col[0]) ) {
					
					$meta_key = $col[0];
					
					if ( '_' == $meta_key[0] ) {
	
						$meta_key = substr( $meta_key, 1 );

						$sql = $wpdb->prepare( "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '%s'", $post_id, $meta_key );
						$col = $wpdb->get_col( $sql );
						
						if ( ! empty($col[0]) ) { 
							if ( WPGlobus_Core::has_translations($col[0]) ) {
								$order['hasTranslations'] = true;
							}
						}
						
					}
					
				}
				
			}

			wp_die( json_encode( $order ) );

		}
		
		/**
		 * Add language buttons to toolbars
		 *
		 * @since 1.0.0
		 *
		 * @param array $buttons
		 * @return array
		 */
		public function add_buttons( $buttons ){

			$buttons['Full'][1][] = 'wpglobus_plus_acf_separator';
			$buttons['Basic'][1][] = 'wpglobus_plus_acf_separator';

			foreach( WPGlobus::Config()->enabled_languages as $language ) {
				$buttons['Full'][1][]  = 'wpglobus_plus_acf_button_' . $language;
				$buttons['Basic'][1][] = 'wpglobus_plus_acf_button_' . $language;
			}

			return $buttons;
		}

		/**
		 * Declare script for new buttons
		 *
		 * @since 1.0.0
		 *
		 * @param array $plugin_array
		 * @return array
		 */
		public function mce_external_plugins( $plugin_array ) {
			$plugin_array['wpglobus_plus_acf_separator'] =
				WPGlobusPlus_Asset::url_js( 'wpglobus-plus-acf' );
			foreach( WPGlobus::Config()->enabled_languages as $language ) {
				$plugin_array['wpglobus_plus_acf_button_' . $language] =
					WPGlobusPlus_Asset::url_js( 'wpglobus-plus-acf' );
			}

			return $plugin_array;
		}

		/**
		 * Admin print scripts
		 *
		 * @since 1.1.11
		 *
		 * @return void
		 */
		public function admin_scripts() {

			wp_register_script(
				'wpglobus-plus-acf-init',
				WPGlobusPlus_Asset::url_js( 'wpglobus-plus-acf-init' ),
				array( 'jquery' ),
				WPGLOBUS_PLUS_VERSION,
				true
			);
			wp_enqueue_script( 'wpglobus-plus-acf-init' );
			wp_localize_script(
				'wpglobus-plus-acf-init',
				'WPGlobusPlusAcf',
				array(
					'wpglobus_plus_version'  => WPGLOBUS_PLUS_VERSION,
					'removeEmptyP' => apply_filters(
						/**
						 * Filter to remove empty p from ACF wysiwyg editor.
						 * Returning boolean.
						 * @since 1.1.11
						 *
						 * @param boolean False.
						 */
						'wpglobus_plus_acf_remove_empty_p',
						false
					)
				)
			);
			
			/**
			 * The support `table` field.
			 * @since 1.1.55
			 */
			global $post;
			
			$table_warning 		    = __( 'Load the page in the %sBuilder mode%s to work with a multilingual table.', 'wpglobus-plus' );
			$table_warning 		    = sprintf($table_warning, '<a href="https://wpglobus.com/documentation/multilingual-advanced-custom-fields-table-field/" target="_blank">', '</a>');
			$table_has_translations = __( 'This table already has multilingual data. To preserve it, reload the page in the %sBuilder mode%s.', 'wpglobus-plus' );
			$table_has_translations = sprintf($table_has_translations, '<a href="https://wpglobus.com/documentation/multilingual-advanced-custom-fields-table-field/" target="_blank">', '</a>');
			
			$l10n = array();
			$l10n['tableWarning'] = '<span>' . $table_warning . '</span>';
			$l10n['tableHasTranslations'] = '<span>' . $table_has_translations . '</span>';
			
			wp_register_script(
				'wpglobus-plus-acf-table',
				WPGlobusPlus_Asset::url_js( 'wpglobus-plus-acf-table' ),
				array( 'jquery' ),
				WPGLOBUS_PLUS_VERSION,
				true
			);
			wp_enqueue_script( 'wpglobus-plus-acf-table' );
			wp_localize_script(
				'wpglobus-plus-acf-table',
				'WPGlobusPlusAcfTable',
				array(
					'wpglobus_plus_version' => WPGLOBUS_PLUS_VERSION,
					'postID' 				=> $post->ID,
					'process_ajax' 			=> __CLASS__ . '_process_ajax',
					'l10n' => $l10n
				)
			);
		}

	}	// end class WPGlobusPlus_Acf

endif;
