<?php
/**
 * Support of Yoast SEO 3.*
 * @package WPGlobus Plus
 * @since   1.1.5
 */

if ( ! class_exists( 'WPGlobusPlusYoastSeo' ) ) :

	/**
	 * Class WPGlobusPlusYoastSeo
	 * @since 1.1.5
	 */
	class WPGlobusPlusYoastSeo {

		protected static $_wpseo_canonical_meta_key = false;
		
		protected static $_wpseo_canonical_input_id = false;
		
		/**
		 * Array of post data.
		 * @since 1.1.18
		 *
		 * @var array
		 */
		public static $postarr = array();

		public static function controller() {

			if ( is_admin() ) {

				/**
				 * @since 1.1.42
				 */
				if ( version_compare( WPSEO_VERSION, '7.7', '>=' ) ) {
					/**
					 * @see wpglobus\includes\wpglobus-yoastseo.php
					 */
					return;
				}
			
				if ( ! WPGlobus_WP::is_doing_ajax() ) {

					self::set_yoast_wpseo_canonical();
					
					add_action( 'admin_print_scripts', array(
						__CLASS__,
						'action__admin_print_scripts'
					), 99 );

					/**
					 * @since 1.1.18
					 */
					add_filter( 'wpglobus_save_post_data', array(
						__CLASS__,
						'filter__wpglobus_save_post_data'
					), 11, 3 );

					/**
					 * Fix after sanitize post meta by yoast
					 * @since 1.1.18
					 */
					add_filter( 'wpseo_sanitize_post_meta__yoast_wpseo_focuskw', array(
						__CLASS__,
						'filter__wpseo_sanitize_post_meta'
					), 10, 4 );

					/**
					 * @since 1.1.41
					 */
					add_filter( 'update_post_metadata', array(
						__CLASS__,
						'filter__update_post_meta'
					), 999, 5 );
					
					/**
					 * @since 1.1.41
					 */
					add_filter( 'delete_post_metadata', array(
						__CLASS__,
						'filter__delete_post_metadata'
					), 5, 5 );
					
				}

			} else {
				
				/**
				 * @scope front
				 */

				/**
				 * Filter URL entry before it gets added to the sitemap.
				 * @since 1.1.48
				 */
				add_filter( 'wpseo_sitemap_entry', array( __CLASS__, 'filter__sitemap_entry' ), 5, 3 );
				 
				/**
				 * Filter meta keywords.
				 * @since 1.8.8
				 */
				remove_filter( 'wpseo_metakeywords', array( 'WPGlobus_YoastSEO', 'filter__metakeywords' ), 0 );
				
				add_filter( 'wpseo_metakeywords', array( __CLASS__, 'filter__metakeywords' ), 0 );
				
				/**
				 * @since 1.1.41
				 */
				add_filter( 'wpseo_opengraph_url', array( __CLASS__, 'filter__wpseo_opengraph_url' ), 5 );
			}

		}

		/**
		 * Filter URL entry before it gets added to the sitemap.
		 * 
		 * @since 1.1.48
		 * 
		 * @param array  $url  		Array of URL parts.
		 * @param string $url_type  URL type.
		 * @param object $post 		Data object for the URL.		
         *
		 * @return array || string	
		 */
		public static function filter__sitemap_entry( $url, $url_type, $post ) {

			static $status = null;
			
			if ( is_null( $status) ) {
				
				$_run = false;
				
				if ( class_exists('WPGlobusPlus_Publish') ) {

					$_run = true;

				} else {
				
					$_class_publish_file = WPGlobusPlus::$PLUGIN_DIR_PATH . '/includes/publish/class-wpglobus-plus-publish2.php';
					
					if ( file_exists( $_class_publish_file ) ) {
						require_once( $_class_publish_file );
						$_run = true;
					}
				}
				
				if ( $_run ) {
					$WPGlobusPlus_Publish = new WPGlobusPlus_Publish();
					$status = $WPGlobusPlus_Publish->get_language_status( WPGlobus::Config()->language );
				}
				
			}
			
			if ( ! is_null($status) && isset( $status[ $post->ID ] ) ) {
				$url = '';
			}
			
			return $url;
			
		}
		
		/**
		 * Set Canonical URL data.
		 *
		 * @scope admin
		 * @since 1.1.41
		 */
		public static function set_yoast_wpseo_canonical() {
			
			self::$_wpseo_canonical_meta_key	= '_yoast_wpseo_canonical';
			self::$_wpseo_canonical_input_id 	= 'yoast_wpseo_canonical';
		}
		
		/**
		 * Filter Yoast post meta keywords.
		 *
		 * @scope front
		 * @since 1.8.8
		 *
		 * @param string $keywords Multilingual keywords.
		 *
		 * @return string.
		 */
		public static function filter__metakeywords( $keywords ) {
			return WPGlobus_Core::text_filter($keywords, WPGlobus::Config()->language, WPGlobus::RETURN_EMPTY);
		}		
		
		/**
		 * Filter to validate the yoast seo post meta values.
		 *
		 * @see   wordpress-seo\inc\class-wpseo-meta.php
		 * @since 1.1.18
		 *
		 * @param  string $clean      Validated meta value.
		 * @param  mixed  $meta_value The new value.
		 * @param  array  $field_def  Field definitions.
		 * @param  string $meta_key   The full meta key (including prefix).
		 *
		 * @return string                Validated meta value
		 */
		public static function filter__wpseo_sanitize_post_meta(
			$clean,
			/** @noinspection PhpUnusedParameterInspection */
			$meta_value,
			/** @noinspection PhpUnusedParameterInspection */
			$field_def,
			/** @noinspection PhpUnusedParameterInspection */
			$meta_key
		) {

			if ( ! empty( self::$postarr['yoast_wpseo_focuskw'] ) &&  WPGlobus_Core::has_translations( self::$postarr['yoast_wpseo_focuskw'] ) ) {
				$clean = self::$postarr['yoast_wpseo_focuskw'];
			}

			return $clean;

		}

		/**
		 * Filter before save post.
		 *
		 * @see   class-wpglobus.php
		 * @since 1.1.18
		 *
		 * @param  array $data    Validated meta value.
		 * @param  array $postarr The new value.
		 * @param  bool  $devmode Developer's mode.
		 *
		 * @return array
		 */
		public static function filter__wpglobus_save_post_data(
			$data, $postarr,
			/** @noinspection PhpUnusedParameterInspection */
			$devmode
		) {
			$postarr['yoast_wpseo_focuskw'] = $postarr['yoast_wpseo_focuskw_text_input'];
			self::$postarr                  = $postarr;

			return $data;
		}

		/**
		 * Enqueue JS for YoastSEO support.
		 * @since 1.1.5
		 */
		public static function action__admin_print_scripts() {

			if ( 'off' === WPGlobus::Config()->toggle ) {
				return;
			}

			/**
			 * Filter for using canonical URL field. Return false to disable field handling.
			 *
			 * Returning string.
			 * @since 1.1.41
			 *
			 * @param value True by default.
			 */	
			$canonical_url = apply_filters( 'wpglobus_plus_wpseo_canonical_url', self::$_wpseo_canonical_input_id );
			
			if ( WPGlobus_WP::is_pagenow( array( 'post.php', 'post-new.php' ) ) ) {

				$scr_version = '30';
				if ( version_compare( WPSEO_VERSION, '3.3.0', '>=' ) ) {
					$scr_version = '33';
				}

				wp_register_script(
					'wpglobus-plus-yoastseo',
					WPGlobusPlus_Asset::url_js( 'wpglobus-plus-yoastseo' . $scr_version ),
					array( 'jquery', 'wpglobus-admin' ),
					WPGLOBUS_PLUS_VERSION,
					true
				);
				wp_enqueue_script( 'wpglobus-plus-yoastseo' );
				wp_localize_script(
					'wpglobus-plus-yoastseo',
					'WPGlobusPlusYoastSeo',
					array(
						'wpglobus_plus_version' => WPGLOBUS_PLUS_VERSION,
						'wpseo_version'         => WPSEO_VERSION,
						'canonicalUrl'			=> $canonical_url
					)
				);

			}

		}

		/**
		 * @see wordpress-seo\frontend\class-opengraph.php
		 *
		 * @scope front
		 * @since 1.1.41
		 */		
		public static function filter__wpseo_opengraph_url( $url ) {

			if ( WPGlobus_Core::has_translations($url) ) {
				$url = WPGlobus_Core::text_filter( $url, WPGlobus::Config()->language );
			}
			
			return $url;
		
		}
		
		/**
		 * Filter delete post meta.
		 *
		 * @scope admin
		 * @since 1.1.41
		 */		
		public static function filter__delete_post_metadata( $check, $object_id, $meta_key, $meta_value, $delete_all ) {
			if ( $meta_key != self::$_wpseo_canonical_meta_key ) {
				return null;
			}
			return true;
		}
	
		/**
		 * Filter update post meta.
		 *
		 * @scope admin
		 * @since 1.1.41
		 */
		public static function filter__update_post_meta( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
			
			if ( $meta_key != self::$_wpseo_canonical_meta_key ) {
				return null;
			}
			
			$_value = $meta_value;
			
			if ( empty( $_value ) ) {
				
				if ( empty( $_POST[ self::$_wpseo_canonical_input_id ] ) ) {
					return null;
				}
				
				$_value = $_POST[ self::$_wpseo_canonical_input_id ];
				
			}

			if ( ! WPGlobus_Core::has_translations( $_value ) ) {
				return null;
			}
			
			global $wpdb;
			
			$result   = false;
			$_meta_id = null;
			
			$_meta_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id FROM $wpdb->postmeta WHERE meta_key = %s AND post_id = %d LIMIT 1;",
					$meta_key, $object_id
				)
			);
			
			$query = false;
			
			if ( is_null($_meta_id)) {
			
				if ( ! empty($_value) ) {
					
					$query = $wpdb->prepare( 
						"
							INSERT INTO $wpdb->postmeta
							( post_id, meta_key, meta_value )
							VALUES ( %d, %s, %s )
						", 
						$object_id, 
						$meta_key, 
						$_value 
					);

				}
				
			} else {

				if ( ! empty($_value) ) {
					
					$query = $wpdb->prepare( 
						"
							UPDATE $wpdb->postmeta 
							SET meta_value = %s
							WHERE meta_id = %d
						",
						$_value, $_meta_id
					);
					
				}
				
			}
			
			if ( ! $query ) {
				return null;
			}
			
			$result = $wpdb->query( $query );
			
			if ( $result ) {
				return true;
			}
			
			return null;
			
		}		

	}

endif;

/* EOF */
