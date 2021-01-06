<?php
/**
 * File class WPGlobusPlus_Taxonomies.
 *
 * @version beta7
 * @since 1.1.50
 */

if ( ! class_exists( 'WPGlobusPlus_Taxonomies' ) ) :

	/**
	 * Class WPGlobusPlus_Taxonomies.
	 */
	class WPGlobusPlus_Taxonomies {

		/**
		 * REDIRECT constant.
		 */
		const REDIRECT_PAGE = 'wpglobus-plus-redirect';

		/**
		 * TAB constant.
		 */
		const TAB = 'taxonomies';

		/**
		 * var string Current module ID (in terms of WPGlobus Plus).
		 */
		protected $module = '';

		/**
		 * @var string To get/put options table.
		 */
		public $option_key = '_wpglobus_plus_taxonomies';

		/**
		 * @var array Extra languages.
		 */
		protected $extra_languages;

		/**
		 * @var boolean.
		 */
		protected $register_activation = false;

		/**
		 * @var boolean.
		 */
		protected $register_deactivation = false;

		/**
		 * @var string Current section.
		 */
		protected $current_section = '';

		/**
		 * @var array Options from wp_options table.
		 */
		public $opts = array();

		/**
		 * @var array Cache.
		 */
		protected $cache = array();
		
		/**
		 * @var array Cache.
		 * @since 1.2.4
		 */		
		protected $cache_post_link = array();

		/**
		 * @var string Content for sections.
		 */
		protected $content = '';

		/**
		 * @var string Option page key WPGlobusPlus.
		 */
		protected $options_page_key;

		/**
		 * @var array Of terms that have multilingual slug.
		 * $term_id => original slug
		 */
		protected $terms = array();

		/**
		 * @var boolean
		 */
		protected $do_rewrite_rules = false;

		/**
		 * @var array Disabled entities.
		 * reserved for next version.
		 */
		public $disabled_entities = array();
		
		public $hidden_types = array( 'post', 'page' );
		
		protected $ids = null;

		/** @var  array */
		protected $wordpress_taxonomies;

		/** @var  array */
		protected $accordance;
		
		/**
		 * @var WP_Term objects.
		 * @since 1.2.4
		 */
		protected $category = null;

		/**
		 * Constructor.
		 *
		 * @param WPGlobusPlus $wpg_plus
		 */
		public function __construct( $wpg_plus ) {

			$this->module           = $wpg_plus->get_current_module();
			$this->options_page_key = $wpg_plus->get_options_page_key();

			if ( ! empty( $_GET['page'] ) && self::REDIRECT_PAGE === $_GET['page'] && ! empty( $_GET['redirect-to'] ) ) {
				/**
				 * Make redirect from dummy page.
				 */
				$args = $_GET;
				$page = $args['redirect-to'];
				unset( $args['page'], $args['redirect-to'] );
				$args = array_merge( array( 'page' => $page ), $args );
				$url  = add_query_arg( $args, admin_url( 'admin.php' ) );
				wp_redirect( $url );
				exit;
			}

			$this->opts = get_option( $this->option_key );

			/**
			 * @see wp-includes\class-wp-taxonomy.php
			 */
			add_filter( 'register_taxonomy_args', array( $this, 'filter__register_taxonomy_args' ), 5, 3 );

			/**
			 * @see wp-includes\class-wp-post-type.php
			 */
			add_filter( 'register_post_type_args', array( $this, 'filter__register_post_type_args' ), 5, 2 );

			/**
			 * Set extra languages.
			 */
			$this->extra_languages = WPGlobus::Config()->enabled_languages;
			unset( $this->extra_languages[0] );

			/**
			 * Init action.
			 */
			add_action( 'init', array( $this, 'on__init' ), 5 );

			$this->disabled_entities = array(
				'page',					// Default post type.
				'dc_commission', 		// WC Marketplace
				'wcmp_vendor_notice', 	// WC Marketplace
				'wcmp_university', 		// WC Marketplace
				'wcmp_vendorrequest', 	// WC Marketplace
				'wc_appointment' 		// WooCommerce Appointments
			);
			
			if ( is_admin() ) {

				$this->set_sections();

				/**
				 * Check section.
				 */
				if ( empty( $_GET['section'] ) ) {
					$this->current_section = 'general';
				} else {
					$this->current_section = $_GET['section'];
				}


				$this->accordance['post_tag'] = 'tag';

				$this->disabled_taxonomies         = array();
				$this->disabled_taxonomies['post'] = array( 'post_format' );

				/**
				 * @scope admin
				 */
				add_action( 'admin_menu', array( $this, 'on__admin_menu' ) );

				/**
				 * Enqueue the CSS & JS scripts.
				 * @scope admin
				 */
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

				/**
				 * @scope admin
				 */
				//add_action( 'registered_post_type', array( $this, 'on__registered_post_type' ), 10, 2 );

				/**
				 * @scope admin
				 */
				add_filter( 'wpglobus_localize_data', array( $this, 'filter__localize_data' ), 10, 2 );

				/**
				 * @scope admin
				 */
				add_action( 'wp_ajax_' . __CLASS__ . '_process_ajax', array(
					$this,
					'on__process_ajax'
				) );

				/**
				 * @scope admin
				 */
				add_filter( 'wpglobus_enabled_pages', array( $this, 'filter__enabled_pages' ) );

				/**
				 * @scope admin
				 */
				add_filter( 'wpglobus_plus_get_sample_permalink', array( $this, 'filter__sample_permalink' ), 10, 3 );


				if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {

					if (
						! empty( $_POST['order']['active_status'] )
						&& $_POST['order']['active_status']
						&& ! empty( $_POST['order']['moduleData']['register_activation'] )
						&& 'true' === $_POST['order']['moduleData']['register_activation']
					) {
						$this->activate();
					}

					if (
						empty( $_POST['order']['active_status'] )
						&& ! empty( $_POST['order']['moduleData']['register_deactivation'] )
						&& 'true' === $_POST['order']['moduleData']['register_deactivation']
					) {
						$this->deactivate();
					}

				}

			} else {
				
				/**
				 * @scope front
				 */
				$this->hidden_types = array_merge( $this->hidden_types, WPGlobus_Post_Types::hidden_types() );
				
				/**
				 * @scope front
				 */
				add_action( 'parse_query', array($this, 'on__parse_query'), 1 );
				 
				/**
				 * @scope front
				 */
				add_filter( 'wpglobus_nav_menu_objects', array( $this, 'filter__wpglobus_nav_menu_objects' ), 5 );

				/**
				 * @scope front
				 */
				//add_filter( 'wpglobus_pre_localize_current_url', array( $this, 'filter__after_localize_current_url' ), 10, 2 );
				add_filter( 'wpglobus_after_localize_current_url', array(
					$this,
					'filter__after_localize_current_url_2'
				), 10, 2 );
				
				/**
				 * Filter the permalink for a post with a custom post type.	
				 *
				 * @scope front
				 * @since 1.1.46
				 */				
				add_filter( 'post_type_link', array( $this, 'filter__post_type_link' ), 1, 4 );
				
				/**
				 * Filter the permalink for a post.
				 *
				 * @scope front
				 * @since 1.2.4
				 * @see wp-includes\link-template.php
				 */
				add_filter( 'post_link', array( $this, 'filter__post_link' ), 1, 3 );
				
				/**
				 * Filter the category that gets used in the %category% permalink token.
				 *
				 * @scope front
				 * @since 1.2.4
				 * @see wp-includes\link-template.php
				 */
				add_filter( 'post_link_category', array( $this, 'filter__post_link_category' ), 1, 3 );
				
				/***************
				 ***************/
				/**
				 * @scope front
				 */
				//add_filter( 'wpglobus_hreflang_tag', array( $this, 'filter__hreflang_tag' ), 5 );

				/**
				 * @scope front
				 */
				//add_filter( 'wp_list_categories', array( $this, 'filter__wp_list_categories' ), 5, 2 );

			}

		}
		
		/**
		 * Filter the permalink for a post with a custom post type.
		 *
		 * @since 1.1.46
		 *
		 * @param string      $post_link The post's permalink.
		 * @param int|WP_Post $post      The post in question.
		 * @param bool        $leavename Defaults to false. Whether to keep post name.
		 * @param bool        $sample    Defaults to false. Is it a sample permalink..
		 *
		 * @return string
		 */		
		public function filter__post_type_link(
			$post_link, $post, $leavename,
			/** @noinspection PhpUnusedParameterInspection */
			$sample
		) {
			
			if ( $leavename ) {
				/**
				 * Do something only when $leavename is false.
				 * *
				 * Whether to keep the post name. When set to true, a structural link will be returned, rather than the actual URI.
				 * @see get_post_permalink()
				 */
				 return $post_link;
			}
			
			if ( WPGlobus::Config()->language == WPGlobus::Config()->default_language ) {
				return $post_link;
			}
			
			$_permalink = urldecode( $post_link );
			
			/**
			 * May be called many times on one page. Let's cache.
			 */
			static $_cache;
			if ( isset( $_cache[ $_permalink ] ) ) {
				return $_cache[ $_permalink ];
			}

			$_new_slug = $this->get_post_type_slug( $post->post_type, WPGlobus::Config()->language );

			if ( ! empty($_new_slug) ) {
				$_new_permalink = str_replace(
					'/'.$this->get_post_type_slug( $post->post_type, WPGlobus::Config()->default_language ),
					'/'.$_new_slug,
					$_permalink
				);
			}
			else {
				return $post_link;
			}
			
			$_cache[ $_permalink ] = $_new_permalink;

			return  $_new_permalink;
			
		}

		/**
		 * @see wp-includes\class-wp-query.php
		 */
		public function on__parse_query( $q ) {

			if ( is_category() ) :
				
				if ( ! empty( $q->query['category_name'] ) ) {
					
					$q->query['wpglobus_category_name'] = '';
					
					if ( WPGlobus::Config()->default_language == WPGlobus::Config()->language ) {

						$q->query['wpglobus_category_name'] = $q->query['category_name'];
						$q->query['wpglobus_category_base'] = 'category';
						
					} else {
						
						$category_name = urldecode($_SERVER['REQUEST_URI']);

						if ( ! empty( $q->query['paged'] ) ) {
							/**
							 * Remove paged tail, like `/ru/категория/тест/page/2/`.
							 * @since 1.1.56
							 */
							$category_name = explode( '/page/', $category_name );
							$category_name = $category_name[0] . '/';
						} else {
							if ( ! empty($_SERVER['QUERY_STRING']) ) {
								$category_name = str_replace( 
									array( '/?'.$_SERVER['QUERY_STRING'], '?'.$_SERVER['QUERY_STRING'] ),
									'',
									$category_name 
								);
							}
						}						
								
						global $wp_taxonomies;
						
						if ( $wp_taxonomies['category']->rewrite['slug'] == '.' ) {
							/**
							 * Special case to exclude category_base from URLs
							 * with Custom Structure "/%category%/%postname%/".
							 */							
							$category_base = '';
							$srch = '/' . WPGlobus::Config()->language . '/';
						
						} else {
							$category_base 	= $this->get_taxonomy_slug('category', WPGlobus::Config()->language);
							$srch = '/' . WPGlobus::Config()->language . '/' . $category_base . '/';
						}
							
						$category_name = str_replace( $srch, '', $category_name );
						$category_name = untrailingslashit($category_name);
						
						$q->query['wpglobus_category_name'] = $category_name;
						$q->query['wpglobus_category_base'] = $category_base;
					
					}
					
				}	
			
			endif;
			
			return;			
			
		}
		
		/**
		 * Register post type.
		 * @see wp-includes\class-wp-post-type.php
		 *
		 * @param array  $args
		 * @param string $name
		 *
		 * @return mixed
		 */
		public function filter__register_post_type_args( $args, $name ) {
			
			if ( ! $this->is_post_type_enabled( $name, $args ) ) {
				return $args;
			}

			if ( 'page' === $name ) {
				return $args;
			}

			if ( 'post' === $name ) {

				$args['wpglobus']['menu_parent_slug'] = 'edit.php';
				$args['wpglobus']['slug_source']      = '';

			} else if ( ! empty( $this->opts['post_type'][ $name ] ) ) {
		
				$args['wpglobus']['menu_parent_slug'] = 'edit.php?post_type=' . $name;
				$args['wpglobus']['slug_source']      = $this->opts['post_type'][ $name ];
			
			}

			foreach ( WPGlobus::Config()->enabled_languages as $language ) {
				if ( $language === WPGlobus::Config()->default_language ) {
					$args['wpglobus']['slug'][ $language ] = empty( $args['rewrite']['slug'] ) ? $name : $args['rewrite']['slug'];
					continue;
				}
				if ( ! empty( $args['wpglobus']['slug_source'] ) ) {
					$args['wpglobus']['slug'][ $language ] = WPGlobus_Core::text_filter( $args['wpglobus']['slug_source'], $language );
				}
			}

			return $args;
		}

		/**
		 * Register taxonomy.
		 *
		 * @param array  $args
		 * @param string $name
		 * @param        $object_type
		 *
		 * @return mixed
		 */
		public function filter__register_taxonomy_args(
			$args, $name, /** @noinspection PhpUnusedParameterInspection */
			$object_type
		) {

			if ( in_array( $name, array('post_tag', 'nav_menu', 'link_category', 'post_format') ) ) {
				return $args;
			}

			/**	
			if (
				! empty( $args['public'] )
				&& ! empty( $args['show_ui'] )
				&& ! empty( $this->opts['taxonomy'][ $name ] )
			) // */
			/**
			 * Removed checking $args['public']. 
			 * @since 1.3.3
			 */
			if ( ! empty( $args['show_ui'] ) && ! empty( $this->opts['taxonomy'][ $name ] ) )
			{
				/**
				 * @since 1.2.5
				 * @since 1.2.6
				 */
				$args['wpglobus']['slug_source'] = '';
				if ( ! empty( $this->opts['taxonomy'][$name]['slug'] ) ) {
					$args['wpglobus']['slug_source'] = $this->opts['taxonomy'][$name]['slug'];
				}
				
				foreach ( WPGlobus::Config()->enabled_languages as $language ) {
					if ( $language === WPGlobus::Config()->default_language ) {
						$args['wpglobus']['slug'][ $language ] = empty( $args['rewrite']['slug'] ) ? $name : $args['rewrite']['slug'];
						continue;
					}
					if ( ! empty( $args['wpglobus']['slug_source'] ) ) {
						$args['wpglobus']['slug'][ $language ] = WPGlobus_Core::text_filter( $args['wpglobus']['slug_source'], $language );
					}
				}

				/**
				if ( empty( $this->opts['taxonomy'][ $name ]['term_slug'] ) ) {
					$args['wpglobus']['term_slug'] = array();
				} else {
					$args['wpglobus']['term_slug'] = $this->terms;
				}
				// */
				
			}

			return $args;
		}

		/**
		 * Add submenu.
		 *
		 * @scope admin
		 * @return void
		 */
		public function on__admin_menu() {

			/** @global array $wp_post_types */
			global $wp_post_types;

			foreach ( $wp_post_types as $post_type => $obj ) {
				if ( empty( $obj->wpglobus['menu_parent_slug'] ) ) {
					continue;
				}
				/* @noinspection PhpUnusedLocalVariableInspection */
				$admin_submenu = add_submenu_page(
					$obj->wpglobus['menu_parent_slug'],
					/** Page title */
					__( 'Taxonomies', 'wpglobus-plus' ),
					/** Menu title */
					'<span class="dashicons dashicons-translation" style="vertical-align:middle"></span>&nbsp;' .
					__( 'Taxonomies', 'wpglobus-plus' ),
					'administrator',
					self::REDIRECT_PAGE . '&redirect-to=' . $this->options_page_key . '&tab=' . self::TAB,
					array( $this, 'test' )
				);
			}

		}

		/**
		 * Callback to localize navigation menu.
		 *
		 * @scope front
		 *
		 * @param array $sorted_menu_items
		 *
		 * @return mixed
		 */
		public function filter__wpglobus_nav_menu_objects( $sorted_menu_items ) {

			if ( WPGlobus::Config()->default_language === WPGlobus::Config()->language ) {
				return $sorted_menu_items;
			}
			
			foreach ( $sorted_menu_items as $key => $item ) {

				if ( 'post_type' === $item->type ) {

					if ( in_array( $item->object, array('post', 'page') ) ) {
						/**
						* Don't need filter post, page.
						*/
						continue;
					}

					$new_url = urldecode( $sorted_menu_items[ $key ]->url );
					
					$_new_slug = $this->get_post_type_slug( $item->object, WPGlobus::Config()->language );
					
					// @since 1.1.47 fix with trailing slash.
					if ( ! empty($_new_slug) ) {
						$new_url = str_replace(
							'/'.$this->get_post_type_slug( $item->object ).'/',
							'/'.$_new_slug.'/',
							$new_url
						);
						
						$sorted_menu_items[ $key ]->url = $new_url;
					}
					
				} else if ( 'taxonomy' === $item->type ) {
					/**
					 * Category, tag, custom taxonomy.
					 * e.g. from site/en/категория/новости to site/en/category/news/.
					 */
					$new_url = urldecode( $sorted_menu_items[ $key ]->url );
						
					$_default_taxonomy_slug = $this->get_taxonomy_slug( $item->object );
					$_extra_taxonomy_slug = $this->get_taxonomy_slug( $item->object, WPGlobus::Config()->language );

					if ( ! empty( $_extra_taxonomy_slug ) ) {	
						$new_url = str_replace(
							$this->get_taxonomy_slug( $item->object ),
							$this->get_taxonomy_slug( $item->object, WPGlobus::Config()->language ),
							$new_url
						);
					}

					if ( ! empty( $this->terms[ $item->object_id ] )
					     &&
					     ! empty( $this->opts[ $item->type ][ $item->object ]['term_slug'][ 'term_id_' . $item->object_id ] )
					) {

						$new_url = str_replace(
							$this->terms[ $item->object_id ],
							WPGlobus_Core::text_filter( $this->opts[ $item->type ][ $item->object ]['term_slug'][ 'term_id_' . $item->object_id ], WPGlobus::Config()->language ),
							$new_url
						);

					}

					$sorted_menu_items[ $key ]->url = $new_url;

				} else if ( 'custom' === $item->type ) {

					/**
					 * Here we can have various links.
					 * e.g. link to tag 'site/tag/hot/'
					 * e.g. link to custom post type post 'site/service/pizza-delivering/
					 *
					 * @todo more investigation.
					 */
					 
					$new_url = urldecode( $sorted_menu_items[ $key ]->url );
					
					if ( 'custom' ==  $item->object ) {
				
						/**
						 * We have custom WP_Post object but url may point to CPT.
						 */

						global $wp_post_types;
						
						foreach( $wp_post_types as $post_type=>$post_type_object ) {
							
							if ( in_array( $post_type, $this->hidden_types ) ) {
								continue;
							}
							
							$_default_name = $post_type_object->name;
							
							/**
							 * Fix trying to access array offset on value of type boolean.
							 * @since 1.3.1
							 */
							if ( false === $post_type_object->rewrite || empty( $post_type_object->rewrite['slug'] ) ) {
								$_rewrite_slug = $post_type_object->name;
							} else {
								$_rewrite_slug = $post_type_object->rewrite['slug'];
							}

							if ( false !== strpos( $item->url, '/'.$_rewrite_slug ) ) {
								
								$new_url = str_replace(
									'/'.$this->get_post_type_slug( $_default_name ),
									'/'.$this->get_post_type_slug( $_default_name, WPGlobus::Config()->language ),
									$new_url
								);
								
								$sorted_menu_items[ $key ]->url = $new_url;
								
							}

						}
						
					} else {
						
						$new_url = str_replace(
							$this->get_post_type_slug( $item->object ),
							$this->get_post_type_slug( $item->object, WPGlobus::Config()->language ),
							$new_url
						);

						$sorted_menu_items[ $key ]->url = $new_url;
						
					}

				}

			}
			
			return $sorted_menu_items;
		}

		/**
		 * Get post type slug from global $wp_post_types.
		 *
		 * @param string $post_type
		 * @param string $language
		 *
		 * @return string
		 */
		public function get_post_type_slug( $post_type = '', $language = '' ) {

			if ( '' === $post_type ) {
				return '';
			}

			global $wp_post_types;

			if ( empty( $wp_post_types[ $post_type ] ) ) {
				return '';
			}

			if ( '' === $language ) {
				$language = WPGlobus::Config()->default_language;
			}

			if ( empty( $wp_post_types[ $post_type ]->wpglobus['slug'][ $language ] ) ) {
				/**
				 * Return slug for default language.
				 */
				/**
				 * Fix trying to access array offset on value of type boolean.
				 * @since 1.3.1
				 *
				 * Fix trying to get property of non-object.
				 * Remove leading slash '/'.
				 * @since 1.3.2
				 */
				if ( false === $wp_post_types[$post_type]->rewrite || empty( $wp_post_types[$post_type]->rewrite['slug'] ) ) {
					return $post_type_object->name;
				}			 

				if ( 0 == strpos( $wp_post_types[$post_type]->rewrite['slug'], '/' ) ) {
					/**
					 * Woocommerce has leading slash `[slug] => /product`.
					 * We must return slug without slash.
					 */
					return str_replace( '/', '', $wp_post_types[$post_type]->rewrite['slug'] );
				}
				
				return $wp_post_types[$post_type]->rewrite['slug'];
			}

			/**
			 * Slug for default language may has leading slash, e.g. post type "product" (from WooCommerce) has rewrite slug "/product". 
			 */
			$slug = str_replace( '/', '', $wp_post_types[ $post_type ]->wpglobus['slug'][ $language ] );
			
			return $slug;

		}
		
		/**
		 * Get taxonomy slug from global $wp_taxonomies.
		 *
		 * @param string $taxonomy
		 * @param string $language
		 *
		 * @return string
		 */
		public function get_taxonomy_slug( $taxonomy = '', $language = '' ) {

			if ( '' === $taxonomy ) {
				return '';
			}

			global $wp_taxonomies;

			if ( empty( $wp_taxonomies[ $taxonomy ] ) ) {
				return '';
			}

			if ( '' === $language || $language === WPGlobus::Config()->default_language ) {
				/**
				 * return slug for WPGlobus::Config()->default_language.
				 */
				return $wp_taxonomies[ $taxonomy ]->rewrite['slug'];
			}
			
			if ( empty( $wp_taxonomies[ $taxonomy ]->wpglobus['slug'][ $language ] ) ) {
				/**
				 * Return slug for default language.
				 */
				return $wp_taxonomies[ $taxonomy ]->rewrite['slug'];
			}

			return $wp_taxonomies[ $taxonomy ]->wpglobus['slug'][ $language ];

		}

		/**
		 * Check for enabled post type.
		 *
		 * @since 1.2.9 Using WPGlobus::Config()->disabled_entities array.
		 *
		 * @param string   			$post_type
		 * @param stdClass || array $obj
		 *
		 * @return bool
		 */
		public function is_post_type_enabled( $post_type, $obj ) {

			$_enable = true;
			
			if ( is_object($obj) ) {
				/**
				 * @scope admin.
				 */
				if ( ! isset( $obj->public ) || ! $obj->public ) {
					$_enable = false;
				}
			} else if ( is_array($obj) ) {
				/**
				 * @scope front.
				 */
				if ( ! isset( $obj['public'] ) ||  ! $obj['public'] ) {
					$_enable = false;
				}				
			}

			if ( ! $_enable ) {
				return false;
			}

			$disabled_entities = array_merge( 
				$this->disabled_entities, 
				WPGlobus::Config()->disabled_entities
			);

			if ( in_array( $post_type, $disabled_entities, true ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Check for enabled taxonomy.
		 *
		 * @param string   $taxonomy Unused.
		 * @param stdClass $taxonomy_data
		 * @param string   $post_type
		 *
		 * @return bool
		 */
		public function is_taxonomy_enabled(
			/* @noinspection PhpUnusedParameterInspection */
			$taxonomy, $taxonomy_data, $post_type
		) {

			if ( ! in_array( $post_type, $taxonomy_data->object_type, true ) ) {
				return false;
			}

			if ( $taxonomy_data->public && $taxonomy_data->show_ui ) {
				return true;
			}

			return false;
		}

		/**
		 * Generate content for specified section.
		 *
		 * @param string $section
		 */
		public function get_content( $section ) {
			if ( method_exists( $this, 'get_content_' . $section ) ) {
				$this->{'get_content_' . $section}();
			}
		}

		/**
		 * Generate content for section 'general'.
		 */
		public function get_content_general() {

			$opts = get_option( $this->option_key );

			/** @global array $wp_post_types */
			/** @global array $wp_taxonomies */
			global $wp_post_types, $wp_taxonomies;
			
			ob_start();
			?>
			<ul>
				<?php foreach ( $wp_post_types as $post_type => $post_type_obj ) {
					if ( ! $this->is_post_type_enabled( $post_type, $post_type_obj ) ) {
						continue;
					}
					?>
					<li data-post-type="<?php echo $post_type; ?>" class="tab-post-type-<?php echo $post_type; ?>">
						<a id="post-type-<?php echo $post_type; ?>" href="#tab-<?php echo $post_type; ?>"><?php echo $post_type_obj->labels->singular_name; ?></a>
					</li>
				<?php } ?>
			</ul>

			<p style="margin-bottom:10px;font-weight: bold;">
				<?php esc_html_e( 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' ); ?>
			</p>
			<?php
			$slug_label = __( '%s slug', 'wpglobus-plus' );
			$separator  = '<span style="display:inline-block; width: 3em;"></span>';
			foreach ( $wp_post_types as $post_type => $post_type_obj ) {

				if ( ! $this->is_post_type_enabled( $post_type, $post_type_obj ) ) {
					continue;
				}
				
				$enable_taxonomies = true;
				if ( defined('WOOCOMMERCE_VERSION') ) {
					/**
					 * Don't provide here the support for Woocommerce taxonomies by default.
					 */
					if ( in_array($post_type, array('product')) ) {
						/**
						 * Filter to enable/disable the handling of taxonomies.
						 *
						 * The dynamic portion of the filter name, `$post_type`, refers to the post type.
						 *
						 * @since 1.2.8
						 *
						 * @param false Value by default.
						 */
						$enable_taxonomies = apply_filters( "wpglobus_plus_handling_{$post_type}_taxonomies", false );
					}
				}
				
				/**
				 * Filter to enable/disable the handling of taxonomies.
				 *
				 * @since 1.2.8
				 *
				 * @param boolean $enable_taxonomies Enable/Disable the handling of taxonomies.
				 * @param string  $post_type  		 The post type slug.
				 */				
				$enable_taxonomies = apply_filters( 'wpglobus_plus_handling_taxonomies', $enable_taxonomies, $post_type );
				?>
				<div id="tab-<?php echo esc_attr( $post_type ); ?>" class="post-type-tab">
					<h3><?php
						esc_html_e( 'Post Type label:', 'wpglobus-plus' );
						?>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $post_type ) ); ?>" target="_blank"><?php echo esc_html( $post_type_obj->labels->singular_name ); ?></a>
					</h3>
					<?php if ( 'post' !== $post_type ) { ?>
						<div style="clear:both;">
							<h4><?php
								esc_html_e( 'Post Type name:', 'wpglobus-plus' );
								echo $separator;
								echo esc_html( $post_type );
								?></h4>
							<?php
							/**
							 * Post type slug for default language.
							 */
							$post_type_default = $post_type;
							/**
							 * Rewrite for Woocommerce product.
							 *   [rewrite] => Array
							 *		(
							 *			[slug] => /produkt
							 *			[with_front] => 
							 *			[feeds] => 1
							 *			[pages] => 1
							 *			[ep_mask] => 1
							 *		)
							 */
							//if ( ! empty( $post_type_obj->rewrite ) && $post_type_obj->rewrite['with_front'] ) {
							if ( ! empty( $post_type_obj->rewrite ) ) {
								$post_type_default = str_replace( '/', '', $post_type_obj->rewrite['slug'] );
							}
							?>
							<div style="clear:both;height: 35px;">
								<div style="float:left;width:150px;padding-top:4px;" class="language-name">
									<?php
									echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ WPGlobus::Config()->default_language ] ) ); ?>
								</div>
								<div style="float:left">
									<input disabled
										   type="text"
										   size="30"
										   value="<?php echo esc_attr( $post_type_default ); ?>"
										   class="wpglobus-translatable"
										   data-language="<?php echo esc_attr( WPGlobus::Config()->default_language ); ?>"
										   title="" />
									<span 
										class="wpglobus-plus-taxonomies-help dashicons dashicons-editor-help"
										title="<?php esc_attr_e( 'To edit the slug for the default language, please use the Custom Rewrite Slug parameter in the settings of the plugin that created this Custom Post Type.', 'wpglobus-plus' ); ?>" style="height:10px;width:10px;">
									</span>
								</div>
							</div>
							<?php
							$source = '';
							if ( ! empty( $opts['post_type'][ $post_type ] ) ) {
								$source = $opts['post_type'][ $post_type ];
							}
							/**
							 * Post type slug for extra languages.
							 */
							foreach ( $this->extra_languages as $language ) :
								$slug = '';
								if ( ! empty( $source ) ) {
									$slug = WPGlobus_Core::extract_text( $opts['post_type'][ $post_type ], $language );
								}
								?>
								<div style="clear:both;height: 35px;">
									<div style="float:left;width:150px;padding-top:4px;" class="language-name">
										<?php echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ $language ] ) ); ?>
									</div>
									<div style="float:left;">
										<input type="text"
											   size="30"
											   value="<?php echo esc_attr( $slug ); ?>"
											   class="wpglobus-translatable wpglobus-plus-taxonomy-field"
											   data-language="<?php echo esc_attr( $language ); ?>"
											   data-post-type="<?php echo esc_attr( $post_type ); ?>"
											   data-source-id="#wpglobus-source-<?php echo esc_attr( $post_type ); ?>" title="" />
									</div>
								</div>
							<?php endforeach; ?>
							<?php
							/**
							 * Hidden field for post type.
							 */
							?>
							<input type="text"
								   id="wpglobus-source-<?php echo esc_attr( $post_type ); ?>"
								   value="<?php echo esc_attr( $source ); ?>"
								   size="80"
								   class="wpglobus-taxonomy-source hidden"
								   data-post-type="<?php echo esc_attr( $post_type ); ?>" title="" />
						</div>
					<?php } ?>
					<?php
					if ( $enable_taxonomies ) : ?>
						<div class="taxonomies" style="clear:both;">
							<?php
							/**
							 * Taxonomy.
							 * @global array $wp_taxonomies .
							 */
							foreach ( $wp_taxonomies as $taxonomy => $taxonomy_data ) {

								if ( ! $this->is_taxonomy_enabled( $taxonomy, $taxonomy_data, $post_type ) ) {
									continue;
								}

								/**
								 * Get terms.
								 */
								$terms = get_terms( array(
									'taxonomy'   => $taxonomy,
									'hide_empty' => false,
								) );

								$real_taxonomy_slug = $taxonomy_data->query_var;

								if ( ! empty( $taxonomy_data->rewrite['slug'] ) ) {
									$real_taxonomy_slug = $taxonomy_data->rewrite['slug'];
								}
								?>
								<div class="taxonomy-box" style="border:1px #000 solid;border-radius:20px;margin-bottom:10px;padding:0 10px 20px 10px;">
									<h4><?php
										esc_html_e( 'Taxonomy', 'wpglobus-plus' );
										echo $separator;
										?>
										<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy ) ); ?>"><?php echo esc_html( $taxonomy ); ?></a>
									</h4>
									<?php
									/**
									 * Post type slug for default language.
									 */
									$help_title = '';
									if ( in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
										$help_title = __( 'To edit the slug for &#8220;%s&#8221 in the default language, please visit the Settings->Permalinks page.', 'wpglobus-plus' );
										$help_title = sprintf( $help_title, $taxonomy );
									}
									$is_dot = false;
									if ( $taxonomy == 'category' && $real_taxonomy_slug == '.' ) {
										$is_dot = true;
									}
									?>
									<div style="height: 35px;">
										<div style="float:left;width:150px;padding-top:4px;" class="language-name">
											<?php echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ WPGlobus::Config()->default_language ] ) ); ?>
										</div>
										<div style="float:left;">
											<input disabled type="text"
												   size="30"
												   value="<?php echo esc_attr( $real_taxonomy_slug ); ?>"
												   class="wpglobus-translatable"
												   data-language="<?php echo esc_attr( WPGlobus::Config()->default_language ); ?>" title="" />
											<?php if ( $help_title ) { ?>
												<span class="dashicons dashicons-editor-help wpglobus-plus-taxonomies-help" title="<?php echo esc_attr( $help_title ); ?>" style="height:10px;width:10px;"></span>
											<?php } ?>
										</div>
									</div>
									<?php
									$source = '';
									if ( ! empty( $opts['taxonomy'][ $taxonomy ]['slug'] ) ) {
										$source = $opts['taxonomy'][ $taxonomy ]['slug'];
									}
									foreach ( $this->extra_languages as $language ) :
										$slug = '';
										if ( ! empty( $source ) ) {
											$slug = WPGlobus_Core::extract_text( $source, $language );
										}
										$disabled = '';
										if ( $is_dot ) {
											$slug = '.';
											$disabled = 'disabled';
										}
										?>
										<div style="height:35px;">
											<div style="float:left;width:150px;padding-top:4px;" class="language-name">
												<?php echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ $language ] ) ); ?>
											</div>
											<div style="float:left;">
												<input type="text" <?php echo $disabled; ?> 
													   size="30"
													   value="<?php echo esc_attr( $slug ); ?>"
													   class="wpglobus-translatable wpglobus-plus-taxonomy-field"
													   data-language="<?php echo esc_attr( $language ); ?>"
													   data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
													   data-source-id="#wpglobus-source-<?php echo esc_attr( $taxonomy ); ?>" title="" />
											</div>
										</div>
									<?php endforeach; ?>
									<?php
									/**
									 * Hidden field for taxonomy.
									 */
									?>
									<input type="text"
										   id="wpglobus-source-<?php echo esc_attr( $taxonomy ); ?>"
										   value="<?php echo esc_attr( $source ); ?>"
										   size="80"
										   class="wpglobus-taxonomy-source hidden"
										   data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" title="" />

									<input type="button"
										   style="width:80%;cursor:pointer;"
										   class="wpglobus-plus-taxonomies-toggle"
										   value="<?php echo esc_attr(
											   sprintf( __( 'Terms of %s (%d items total) :', 'wpglobus-plus' ),
												   $taxonomy, count( $terms ) ) ); ?>"
										   data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" />

									<div id="wpglobus-plus-taxonomies-<?php echo esc_attr( $taxonomy ); ?>-terms-box" class="wpglobus-plus-taxonomies-terms-box">
										<input type="checkbox"
											   class="wpglobus-plus-taxonomies-hide-empty-terms"
											   id="wpglobus-plus-taxonomies-hide-empty-<?php echo esc_attr( $taxonomy ); ?>"
											   data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" title="" /><?php esc_html_e( 'Hide empty terms.', 'wpglobus-plus' ); ?>
										<?php
										/**
										 * Taxonomy terms.
										 */
										foreach ( $terms as $term ) : ?>
											<a id="<?php echo esc_attr( $taxonomy . '_' . $term->term_id ); ?>" name="<?php echo esc_attr( $taxonomy . '_' . $term->term_id ); ?>"></a>
											<ul id="taxonomy-term-<?php echo esc_attr( $term->term_id ); ?>"
												class="wpglobus-plus-taxonomies-term"
												data-term-slug="<?php echo esc_attr( urldecode( $term->slug ) ); ?>"
												data-term-name="<?php echo esc_attr( $term->name ); ?>">

												<li style="padding-left:100px;height:30px;">
													<?php esc_html_e( 'Term name', 'wpglobus' ); ?>:
													<a href="<?php echo esc_url( admin_url( 'term.php?taxonomy=' . $taxonomy . '&tag_ID=' . $term->term_id . '&post_type=' . $post_type ) ); ?>"><strong><?php echo esc_attr( $term->name ); ?></strong></a>
												</li>
												<li style="height:30px;">
													<div style="float:left;width:150px;padding-top:4px;" class="language-name">
														<?php echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ WPGlobus::Config()->default_language ] ) ); ?>
													</div>
													<div style="float:left;">
														<input disabled type="text"
															   size="30"
															   value="<?php echo esc_attr( urldecode( $term->slug ) ); ?>"
															   class="wpglobus-translatable"
															   data-language="<?php echo esc_attr( WPGlobus::Config()->default_language ); ?>" title="" />
													</div>
												</li>
												<?php
												$source = '';
												if ( ! empty( $opts['taxonomy'][ $taxonomy ]['term_slug'][ 'term_id_' . $term->term_id ] ) ) {
													$source = $opts['taxonomy'][ $taxonomy ]['term_slug'][ 'term_id_' . $term->term_id ];
												}

												foreach ( $this->extra_languages as $language ) :
													$_slug = '';
													if ( ! empty( $source ) ) {
														$_slug = WPGlobus_Core::extract_text( $source, $language );
													}
													?>
													<li style="height:30px;">
														<div style="float:left;width:150px;padding-top:4px;" class="language-name">
															<?php echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ $language ] ) ); ?>
														</div>
														<div style="float:left;">
															<input type="text"
																   size="30"
																   value="<?php echo esc_attr( $_slug ); ?>"
																   class="wpglobus-translatable wpglobus-plus-taxonomy-field"
																   data-language="<?php echo esc_attr( $language ); ?>"
																   data-term="<?php echo esc_attr( $taxonomy ); ?>"
																   data-source-id="#wpglobus-source-term-id-<?php echo esc_attr( $term->term_id ); ?>" title="" />
														</div>
													</li>
												<?php endforeach; // $language ?>
												<?php
												/**
												 * Hidden field for taxonomy term.
												 */
												?>
												<input type="text"
													   id="wpglobus-source-term-id-<?php echo esc_attr( $term->term_id ); ?>"
													   value="<?php echo esc_attr( $source ); ?>"
													   class="wpglobus-taxonomy-term-source wpglobus-plus-taxonomy-field hidden"
													   size="80"
													   data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
													   data-term-id="<?php echo esc_attr( $term->term_id ); ?>"
													   data-term-slug="<?php echo esc_attr( urldecode( $term->slug ) ); ?>" title="" />
											</ul>
										<?php endforeach; // $terms ?>
									</div><!-- .wpglobus-plus-taxonomies-terms-box -->
								</div><!-- .taxonomy-box -->
							<?php } ?>
						</div><!-- .taxonomies -->
						<?php
					endif; /** enable_taxonomies */
					?>
					<div style="">
						<input id="post-type-<?php echo esc_attr( $post_type ); ?>-save"
							   type="button"
							   class="button button-primary post-type-save"
							   value="<?php esc_attr_e( 'Save' ); ?>"
							   data-post-type="<?php echo esc_attr( $post_type ); ?>"
							   data-tab-id="#tab-<?php echo esc_attr( $post_type ); ?>" />
						<span style="float:left;" class="spinner"></span>
					</div>
				</div>

				<?php
			} // $wp_post_types

			$this->content = ob_get_clean();
		}

		/**
		 * Generate content for section 'option'.
		 */
		public function get_content_option() {
			ob_start();
			echo '<h3>' . esc_html( sprintf( __( 'Key: %s', 'wpglobus-plus' ), $this->option_key ) ) . '</h3>';
			$opts = get_option( $this->option_key );
			if ( $opts ) {
				echo '<pre>' . esc_html( print_r( $opts, true ) ) . '</pre>';
			} else {
				esc_html_e( 'Not found in the options table.', 'wpglobus-plus' );
			}
			$this->content = ob_get_clean();
		}

		/**
		 * Generate content for section 'debug'.
		 */
		public function get_content_debug() {

			/** @global array $wp_post_types */
			/** @global array $wp_taxonomies */
			global $wp_post_types, $wp_taxonomies;

			ob_start();
			foreach ( $wp_taxonomies as $taxonomy => $data ) :
				if ( empty( $data->wpglobus ) ) {
					continue;
				}
				$_taxonomy_before = esc_html__( 'Taxonomy', 'wpglobus-plus' );
				?>
				<h4 data-id="<?php echo $taxonomy; ?>" class="wpglobus-plus-taxonomies-title"><?php echo sprintf( '%1s: <a href="#">%2s</a>', $_taxonomy_before, $taxonomy ); ?></h4>
				<div style="" class="wpglobus-plus-taxonomies-debug-data wpglobus-plus-taxonomies-debug-data-<?php echo $taxonomy; ?> hidden">
					<pre>
						<?php echo esc_html( print_r( $data, true ) ); ?>
					</pre>
				</div>
				<?php
			endforeach;

			foreach ( $wp_post_types as $post_type => $data ) :
				if ( ! $this->is_post_type_enabled( $post_type, $data ) ) {
					continue;
				}
				$_post_type_before = esc_html__( 'Post type', 'wpglobus-plus' );
				?>
				<h4 data-id="<?php echo $post_type; ?>" class="wpglobus-plus-taxonomies-title"><?php echo sprintf( '%1s: <a href="#">%2s</a>', $_post_type_before, $post_type ); ?></h4>
				<div style="" class="wpglobus-plus-taxonomies-debug-data wpglobus-plus-taxonomies-debug-data-<?php echo $post_type; ?> hidden">
					<pre>
						<?php echo esc_html( print_r( $data, true ) ); ?>
					</pre>
				</div>
				<?php
			endforeach;
			$this->content = ob_get_clean();
		}

		/**
		 * Generate content for section 'rewrite_rules'.
		 */
		public function get_content_rewrite_rules() {
			
			$extra_languages = WPGlobus::Config()->enabled_languages;
			unset($extra_languages[0]);
			
			$show = array('post_type', 'taxonomy');
			
			ob_start();	
			?>
			<h3>Rewrite rules</h3>
			<?php
			$opts  = get_option($this->option_key);
			$rules = get_option('rewrite_rules');

			if ( empty($opts) ) {
				esc_html_e( 'Module options is empty.', 'wpglobus-plus' );
				$this->content = ob_get_clean();
				return;
			}
			
			foreach( $opts as $option_key=>$option_data ) {
				if ( ! in_array($option_key, $show ) ) {
					continue;
				}
				echo '<h3>'.$option_key.'</h3>';

				switch ( $option_key ) :
					case 'post_type' :
					
						foreach( $option_data as $post_type=>$multilingual_slug ) :	?>
							<div style="margin-left:20px;">		<?php
								echo '<h4>'.$post_type.'</h4>';   	?>
								<div style="margin-left:20px;">	<?php
								
									echo '<b>Language: '.WPGlobus::Config()->default_language.'</b><br />';   	?>
									<div style="margin-left:20px;">	<?php		
										foreach( $rules as $rule_key=>$rule ) {
											if ( false !== strpos( $rule_key,  $post_type ) && false !== strpos( $rule, 'post_type' ) )  {
												echo $rule_key . '  =>  <b>' . $rule . '</b><br />';
											}	
										}	?>
									</div>	<?php	
									echo '<hr />';
									
									foreach( $extra_languages as $language ) :
										echo '<b>Language: '.$language.'</b><br />';   	?>
										<div style="margin-left:20px;">	<?php						
											foreach( $rules as $rule_key=>$rule ) {
												$_slug = WPGlobus_Core::text_filter( $multilingual_slug, $language, WPGlobus::RETURN_EMPTY );
												if ( ! empty($_slug) ) {
													if ( false !== strpos( $rule_key,  $_slug ) && false !== strpos( $rule, 'post_type' ) )  {
														echo $rule_key . '  =>  <b>' . $rule . '</b><br />';
													}										
												}
											}	?>
										</div>	<?php	
										echo '<hr />';
									endforeach;	?>
								</div>		
							</div>		<?php
						endforeach;
						
						break;
							
					case 'taxonomy' :
					
						foreach( $option_data as $taxonomy=>$taxonomy_data ) :
							
							$search_taxonomy_left = $search_taxonomy_right = $taxonomy;
							
							if ( $taxonomy == 'post_tag' ) {
								$search_taxonomy_left	 = 'tag';
								$search_taxonomy_right 	 = 'tag';
							} else if( $taxonomy == 'category' ) {
								$search_taxonomy_left 	 = 'category';	
								$search_taxonomy_right 	 = 'category_name';	
							}

							?>
							<div style="margin-left:20px;">		<?php
								echo '<h4>'.$taxonomy.'</h4>';		?>
								<div style="margin-left:20px;">	<?php
								
									echo '<b>Language: '.WPGlobus::Config()->default_language.'</b><br />';   	?>
									<div style="margin-left:20px;">	<?php
										$indx = 0;
										foreach( $rules as $rule_key=>$rule ) {
											// if ( false !== strpos( $rule_key,  $search_taxonomy . '/' ) && false !== strpos( $rule, $search_taxonomy ) )  {
											if ( false !== strpos( $rule_key,  $search_taxonomy_left . '/' ) && false !== strpos( $rule, $search_taxonomy_right ) )  {
												echo $indx+1 . ') ' . $rule_key . '  =>   <b>' . $rule . '</b><br />';
												$indx++;
											}	
										}	?>
									</div>	<?php
									echo '<hr />';								
									foreach( $extra_languages as $language ) :
										$indx = 0;
										echo '<b>Language: '.$language.'</b><br />';   	?>
										<div style="margin-left:20px;">	<?php	
											if ( ! empty($taxonomy_data['slug']) ) {
											
												foreach( $rules as $rule_key=>$rule ) {
													$_slug = WPGlobus_Core::text_filter( $taxonomy_data['slug'], $language, WPGlobus::RETURN_EMPTY );
													if ( ! empty($_slug) ) {
														if ( false !== strpos( $rule_key,  $_slug . '/' ) )  {
															echo $indx+1 . ') ' . $rule_key . '  =>   <b>' . $rule . '</b><br />';
															$indx++;
														}
													}
												}
												echo '============<br />';
											
											};	
											$i=0;
											
											if ( ! empty($taxonomy_data['term_slug']) ) {
											
												foreach( $taxonomy_data['term_slug'] as $_term_id=>$_term_slug ) :
													foreach( $rules as $rule_key=>$rule ) :
														$_slug = WPGlobus_Core::text_filter( $_term_slug, $language, WPGlobus::RETURN_EMPTY );
														if ( ! empty($_slug) ) {
															if ( 0 === strpos( $rule_key,  $_slug . '/' ) )  {
																echo $rule_key . '  =>  <b>' . $rule . '</b><br />';
															}
														}
														$i++;
													endforeach;
													$i++;
												endforeach;	
												echo '======== iterations: '.$i.' ====<br />';
												
											};	
											?>
										</div>	<?php	
									echo '<hr />';	
									endforeach;
									?>
								</div>	
							</div>		<?php
							
						endforeach;
				
						break;
					default:	
				endswitch;
				
			}
			
			$this->content = ob_get_clean();			

		}
		
		/**
		 * Enqueue admin JS scripts.
		 *
		 * @scope admin
		 *
		 * @param string $hook_page The current admin page.
		 */
		public function enqueue_scripts( $hook_page ) {

			global $pagenow;

			$data       = array();
			$depends_on = array();

			if ( 'admin_page_' . $this->options_page_key === $hook_page && ! empty( $_GET['tab'] ) && self::TAB === $_GET['tab'] ) {
				
				/**
				 * Taxonomies tab on WPGlobus options page.
				 */
				 
				$this->get_content( $this->current_section );

				$data = array(
					'version'        => WPGLOBUS_PLUS_VERSION,
					'pagenow'        => $pagenow,
					'hook_page'      => $hook_page,
					'setContentId'   => '#tabs-' . esc_attr( self::TAB ),
					'contentHtml'    => $this->content,
					'currentSection' => $this->current_section,
					'$_GET'          => $_GET,
					'process_ajax'   => __CLASS__ . '_process_ajax',
					'module'         => $this->module
				);

				$depends_on = array(
					'jquery-ui-tooltip'
				);

			} else if ( 'term.php' === $pagenow ) {

				global $tag;

				if ( ! empty( $tag->taxonomy ) && ! empty( $tag->term_id ) ) :

					$data = array(
						'version'  => WPGLOBUS_PLUS_VERSION,
						'pagenow'  => $pagenow,
						'taxonomy' => $tag->taxonomy,
						'slug'     => $tag->slug,
						'module'   => $this->module
					);

				endif;

			} else if ( 'post.php' === $pagenow ) {

				if ( 'gutenberg' == WPGlobus::Config()->builder->get_id() ) {
					
					// @todo Gutenberg support.
				
				} else {
									
					global $typenow, $wp_post_types;

					if ( in_array( $typenow, array('post', 'page') ) ) {
						// @todo maybe add filter to enable/disable custom post types.
					} else {
					
						if ( ! empty($this->opts['post_type'][$typenow]) ) {
							$_opts[$typenow] = $this->opts['post_type'][$typenow];							
						} else {
							$_opts[$typenow] = array();
						}
						
						$post_type  = array();
						if ( ! empty($this->opts['post_type']) ) {
							foreach( $this->opts['post_type'] as $_post_type=>$_value) {
								if ( $typenow == $_post_type ) {
									foreach( WPGlobus::Config()->enabled_languages as $_language ) {
										if ( $_language == WPGlobus::Config()->default_language ) {
											if ( empty( $wp_post_types[$typenow]->rewrite['slug'] ) ) {
												$post_type[$_post_type][$_language] = $typenow;
											} else {
												$post_type[$_post_type][$_language] = str_replace( '/', '', $wp_post_types[$typenow]->rewrite['slug'] );
											}
										} else {
											$post_type[$_post_type][$_language] = WPGlobus_Core::text_filter( $_value, $_language, WPGlobus::RETURN_EMPTY );
										}
									}
								}
							}
						}
						
						$data = array(
							'version'  		=> WPGLOBUS_PLUS_VERSION,
							'pagenow'  		=> $pagenow,
							'typenow'  		=> $typenow,
							'hook_page' 	=> $hook_page,
							'module'   		=> $this->module,
							'_GET'			=> $_GET,
							'opts'			=> $_opts,
							'post_type' 	=> $post_type,
							'builderID' 	=> empty( WPGlobus::Config()->builder->get_id() ) ? 'false' : WPGlobus::Config()->builder->get_id()
						);
					
					}
				}
				
			} else if ( 'options-permalink.php' === $pagenow ) {

				/**
				 * Reserved for a future version.
				 */
				return;

//				$_source = __( 'Сейчас мультиязычные ярлыки доступны только для просмотра.<br />Для редактирования перейдите по %s ссылке %s.', 'wpglobus-plus' );
//				$_link   = add_query_arg(
//					array(
//						'page'      => $this->options_page_key,
//						'post_type' => 'post',
//						'tab'       => self::TAB
//					),
//					admin_url( 'admin.php' )
//				);
//
//				$m_slug      = array();
//				$form_footer = array();
//
//				/** @global array $wp_taxonomies */
//				global $wp_taxonomies;
//
//				foreach ( $wp_taxonomies as $tax => $taxonomy_object ) {
//
//					if ( ! in_array( $tax, array( 'category', 'post_tag' ), true ) ) {
//						continue;
//					}
//
//					$_s = array();
//					foreach ( WPGlobus::Config()->enabled_languages as $language ) :
//						if ( $language === WPGlobus::Config()->default_language ) {
//							$_s[ $language ] = $taxonomy_object->wpglobus['slug'][ $language ];
//							continue;
//						}
//						$_tmp = '';
//
//						if ( ! empty( $this->opts['taxonomy'][ $tax ] ) ) {
//							$_tmp = WPGlobus_Core::text_filter( $this->opts['taxonomy'][ $tax ]['slug'], $language, WPGlobus::RETURN_EMPTY );
//						}
//						if ( empty( $_tmp ) ) {
//							$_tmp = $slug;
//						}
//						$_s[ $language ] = $_tmp;
//					endforeach;
//					$m_slug[ $tax ] = WPGlobus_Utils::build_multilingual_string( $_s );
//
//					$html                = sprintf( $_source, '<a href="' . esc_url( $_link ) . '">', '</a>' );
//					$form_footer[ $tax ] = $html;
//				}
//
//				$data = array(
//					'version'          => WPGLOBUS_PLUS_VERSION,
//					'pagenow'          => $pagenow,
//					'module'           => $this->module,
//					'multilingualSlug' => $m_slug,
//					'formFooter'       => $form_footer
//				);

			}

			if ( ! empty( $data ) ) :

				$js_file = 'wpglobus-plus-taxonomies';
				if ( 'post.php' === $pagenow ) {
					$js_file = 'wpglobus-plus-taxonomies-post-php';
				}
				
				wp_register_script(
					'wpglobus-plus-taxonomies',
					WPGlobusPlus_Asset::url_js( $js_file ),
					$depends_on,
					WPGLOBUS_PLUS_VERSION,
					true
				);
				wp_enqueue_script( 'wpglobus-plus-taxonomies' );
				wp_localize_script(
					'wpglobus-plus-taxonomies',
					'WPGlobusPlusTaxonomies',
					$data
				);

			endif;

		}

		/**
		 * Create sections.
		 */
		public function set_sections() {

			if ( empty( $_GET['tab'] ) ) {
				return;
			}

			if ( self::TAB !== $_GET['tab'] ) {
				return;
			}

			if ( ! class_exists( 'WPGlobusPlus_Sections' ) ) {
				/* @noinspection PhpIncludeInspection */
				require_once WPGlobusPlus::$PLUGIN_DIR_PATH . 'includes/admin/class-wpglobus-plus-sections.php';
			}

			$general_id 		= 'general';
			$test_id    		= 'debug';
			$option_id  		= 'option';
			$rewrite_rules_id  	= 'rewrite_rules';

			$url_general = add_query_arg(
				array(
					'page'    => WPGlobusPlus::WPGLOBUS_PLUS_OPTIONS_PAGE,
					'tab'     => self::TAB,
					'section' => $general_id
				),
				admin_url( 'admin.php' )
			);

			$url_test = add_query_arg(
				array(
					'page'    => WPGlobusPlus::WPGLOBUS_PLUS_OPTIONS_PAGE,
					'tab'     => self::TAB,
					'section' => $test_id
				),
				admin_url( 'admin.php' )
			);

			$url_option = add_query_arg(
				array(
					'page'    => WPGlobusPlus::WPGLOBUS_PLUS_OPTIONS_PAGE,
					'tab'     => self::TAB,
					'section' => $option_id
				),
				admin_url( 'admin.php' )
			);
			
			$url_rewrite_rules = add_query_arg(
				array(
					'page'    => WPGlobusPlus::WPGLOBUS_PLUS_OPTIONS_PAGE,
					'tab'     => self::TAB,
					'section' => $rewrite_rules_id
				),
				admin_url( 'admin.php' )
			);			

			$args = array(
				'tab'      => self::TAB,
				'sections' => array(
					$general_id => array(
						'caption' => __( 'General', 'wpglobus-plus' ),
						'link'    => $url_general
					),
					$test_id    => array(
						'caption' => __( 'Debug Mode', 'wpglobus-plus' ),
						'link'    => $url_test
					),
					$option_id  => array(
						'caption' => __( 'Options', 'wpglobus-plus' ),
						'link'    => $url_option
					),
					$rewrite_rules_id => array(
						'caption' => __( 'Rewrite rules', 'wpglobus-plus' ),
						'link'    => $url_rewrite_rules					
					)
				)
			);

			new WPGlobusPlus_Sections( $args );
		}

		/**
		 * Init action.
		 */
		public function on__init($init_start = false) {
		
			if ( $this->register_deactivation ) {
				flush_rewrite_rules();
				return;
			}
			
			/*
			global $pagenow;
			if ( 'options-permalink.php' == $pagenow ) {
				if ( ! empty( $_GET['settings-updated'] ) && $_GET['settings-updated'] ) {
				}					
			} // */
			
			/**
			 * Reload the options since they could be updated via AJAX.
			 */
			$this->opts = get_option( $this->option_key );

			if ( empty( $this->opts ) ) {
				return;
			}
			
			/** @global wpdb $wpdb */
			global $wpdb;

			/** @var array[][] $opts */
			$opts = $this->opts;

			if ( ! empty( $opts['taxonomy_terms_id'] ) ) {

				$terms_id = $opts['taxonomy_terms_id'];
				
				$sql = $wpdb->prepare( "SELECT term_id, slug FROM {$wpdb->terms} WHERE term_id IN ( %s )", $terms_id );
				$sql = str_replace( "'", '', $sql);
				
				/** @var array $data */
				$data = $wpdb->get_results( $sql, ARRAY_A );
				
				foreach ( $data as $k => $_term ) {
					$this->terms[ $_term['term_id'] ] = urldecode( $_term['slug'] );
				}

			}

			/**
			 * CPTs.
			 *
			 * [service/?$] => index.php?post_type=service
			 * [service/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?post_type=service&feed=$matches[1]
			 * [service/(feed|rdf|rss|rss2|atom)/?$] => index.php?post_type=service&feed=$matches[1]
			 * [service/page/([0-9]{1,})/?$] => index.php?post_type=service&paged=$matches[1]
			 *
			 * http://www.site.com/service/pizza-delivery/
			 * http://www.site.com/ru/сервис/доставка-пиццы/
			 * 
			 * Example (order is important):
			 * add_rewrite_rule('сервис/?$', 'index.php?post_type=service', 'top' ); 									 // first page.
			 * add_rewrite_rule('сервис/page/([0-9]{1,})/?$', 'index.php?post_type=service&paged=$matches[1]', 'top' );	 // pagination.
			 * add_rewrite_rule('сервис/(.+?)/?$', 'index.php?service=$matches[1]', 'top' );							 // single page. 
			 */
			if ( ! empty( $opts['post_type'] ) ) :
				foreach ( $opts['post_type'] as $post_type => $value ) :

					foreach ( $this->extra_languages as $language ) {
						$slug = WPGlobus_Core::extract_text( $value, $language );
						if ( ! empty( $slug ) ) {
							add_rewrite_rule( $slug . '/?$', 'index.php?post_type=' . $post_type, 'top' );
							add_rewrite_rule( $slug . '/page/([0-9]{1,})/?$', 'index.php?post_type=' . $post_type . '&paged=$matches[1]', 'top' );
							add_rewrite_rule( $slug . '/(.+?)/?$', 'index.php?' . $post_type . '=$matches[1]', 'top' );
						}
					}

				endforeach;
			endif;

			/**
			 * Tags.
			 *
			 * [tag/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?tag=$matches[1]&feed=$matches[2]
			 * [tag/([^/]+)/(feed|rdf|rss|rss2|atom)/?$] => index.php?tag=$matches[1]&feed=$matches[2]
			 * [tag/([^/]+)/embed/?$] => index.php?tag=$matches[1]&embed=true
			 * [tag/([^/]+)/page/?([0-9]{1,})/?$] => index.php?tag=$matches[1]&paged=$matches[2]
			 *
			 * http://www.site.com/tag/test-tag/
			 * [tag/([^/]+)/?$] => index.php?tag=$matches[1]
			 *
			 * http://www.site.com/ru/тэг/test-tag/
			 * add_rewrite_rule('тэг/([^/]+)/?$', 'index.php?tag=$matches[1]', 'top' );
			 *
			 * http://www.site.com/ru/тэг/тестовый-тэг/
			 * add_rewrite_rule('тэг/тестовый-тэг/?$', 'index.php?tag=video', 'top' );  ??
			 */
			if ( ! empty( $opts['taxonomy']['post_tag'] ) ) :

				if ( empty( $opts['taxonomy']['post_tag']['term_slug'] ) ) {

					foreach ( $this->extra_languages as $language ) {
						$_slug = WPGlobus_Core::extract_text( $opts['taxonomy']['post_tag']['slug'], $language );
						if ( ! empty( $_slug ) ) {
							/**
							 * http://www.site.com/ru/тег/test-tag/page/2/
							 */
							add_rewrite_rule( $_slug . '/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?tag=$matches[1]&paged=$matches[2]', 'top' );
							/**
							 * http://www.site.com/ru/тег/test-tag
							 */
							add_rewrite_rule( $_slug . '/([^/]+)/?$', 'index.php?tag=$matches[1]', 'top' );
						}
					}

				} else {

					/** @noinspection ForeachSourceInspection */
					foreach ( $opts['taxonomy']['post_tag']['term_slug'] as $key => $multilingual_slug ) {

						$slug_id = (int) str_replace( 'term_id_', '', $key );

						if ( 0 === $slug_id ) {
							continue;
						}

						$slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->terms} WHERE term_id = '%s'", $slug_id ) );
						$slug = urldecode( $slug );

						foreach ( $this->extra_languages as $language ) {

							$_slug = '';
							if ( ! empty( $opts['taxonomy']['post_tag']['slug'] ) ) {
								$_slug = WPGlobus_Core::extract_text( $opts['taxonomy']['post_tag']['slug'], $language );
							}
							$_term_slug = WPGlobus_Core::extract_text( $multilingual_slug, $language );

							if ( empty( $_slug ) && ! empty( $_term_slug ) ) {
								/**
								 * e.g. http://www.site.com/de/tag/yoast-german/
								 */
								add_rewrite_rule( 'tag/' . $_term_slug . '/?$', 'index.php?tag=' . $slug, 'top' );
								/**
								 * e.g. http://www.site.com/de/tag/yoast-german/page/2/
								 */
								add_rewrite_rule( 'tag/' . $_term_slug . '/page/?([0-9]{1,})/?$', 'index.php?tag=' . $slug . '&paged=$matches[1]', 'top' );
							}
							if ( ! empty( $_slug ) && empty( $_term_slug ) ) {
								/**
								 * e.g. http://www.site.com/es/etiqueta/yoast/
								 */
								add_rewrite_rule( $_slug . '/([^/]+)/?$', 'index.php?tag=$matches[1]', 'bottom' );
								/**
								 * e.g. http://www.site.com/es/etiqueta/yoast/page/2/
								 */
								add_rewrite_rule( $_slug . '/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?tag=$matches[1]&paged=$matches[2]', 'bottom' );
							}
							if ( ! empty( $_slug ) && ! empty( $_term_slug ) ) {
								/**
								 * e.g. http://www.site.com/es/etiqueta/yoast-spanish/
								 */
								add_rewrite_rule( $_slug . '/' . $_term_slug . '/?$', 'index.php?tag=' . $slug, 'top' );
								/**
								 * e.g. http://www.site.com/es/etiqueta/yoast-spanish/page/2/
								 */
								add_rewrite_rule( $_slug . '/' . $_term_slug . '/page/?([0-9]{1,})/?$', 'index.php?tag=' . $slug . '&paged=$matches[1]', 'top' );
							}
						}
					}

					if ( ! empty( $opts['taxonomy']['post_tag']['slug'] ) ) {
						foreach ( $this->extra_languages as $language ) :

							$_slug = WPGlobus_Core::extract_text( $opts['taxonomy']['post_tag']['slug'], $language );

							if ( ! empty( $_slug ) ) {
								/**
								 * e.g. http://www.site.com/ru/тег/test-tag
								 */
								add_rewrite_rule( $_slug . '/([^/]+)/?$', 'index.php?tag=$matches[1]' );
							}

						endforeach;
					}

				}

				unset( $opts['taxonomy']['post_tag'] );

			endif;

			/**
			 * Categories.
			 *
			 * [category/(.+?)/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?category_name=$matches[1]&feed=$matches[2]
			 * [category/(.+?)/(feed|rdf|rss|rss2|atom)/?$] => index.php?category_name=$matches[1]&feed=$matches[2]
			 * [category/(.+?)/embed/?$] => index.php?category_name=$matches[1]&embed=true
			 * [category/(.+?)/page/?([0-9]{1,})/?$] => index.php?category_name=$matches[1]&paged=$matches[2]
			 * [category/(.+?)/?$] => index.php?category_name=$matches[1]
			 *
			 * http://www.site.com/category/video/
			 *
			 * http://www.site.com/ru/категория/video/
			 * add_rewrite_rule('категория/(.+?)/?$', 'index.php?category_name=$matches[1]', 'top' );
			 *
			 * http://www.site.com/ru/категория/видео/
			 * add_rewrite_rule('категория/видео/?$', 'index.php?&category_name=video', 'top' );
			 */
			
			global $wp_taxonomies;

			$is_dot = false;
											
			/**
			 * @since 1.2.5
			 */
			$extra_rewrite_rules = array();
			
			if ( $wp_taxonomies['category']->rewrite['slug'] == '.' ) :
				
				$is_dot = true;
				
				$_list = $this->get_hierarchical_tax_list('category');
				
				/**
				 * Generate unique term ID list.
				 */
				$list = array();
				foreach( $_list as $key=>$term_ids ) {
					foreach( $term_ids as $_key=>$_term_id ) {
						if ( ! in_array( $_term_id, $list ) ) {
							$list[] = $_term_id;
						}
					}
				}
					
				/**
				 * e.g. add_rewrite_rule( 'музыка/?$', 'index.php?category_name=live-music', 'top' );
				 */
				foreach ( $list as $term_id ) :
					
					if ( ! empty( $opts['taxonomy']['category']['term_slug']['term_id_'.$term_id] ) ) :
						foreach ( $this->extra_languages as $language ) {
							$_slug = WPGlobus_Core::text_filter( $opts['taxonomy']['category']['term_slug']['term_id_'.$term_id], $language, WPGlobus::RETURN_EMPTY );
							if ( ! empty( $_slug ) ) {
								// @todo @since 1.2.5
								add_rewrite_rule( $_slug . '/?$', 'index.php?category_name=live-music', 'top' );
							}
						}
					endif;
					
				endforeach;
				
			else:
			 
				if ( ! empty( $opts['taxonomy']['category'] ) ) :

					if ( empty( $opts['taxonomy']['category']['term_slug'] ) ) {

						foreach ( $this->extra_languages as $language ) {

							$_slug = WPGlobus_Core::text_filter( $opts['taxonomy']['category']['slug'], $language, WPGlobus::RETURN_EMPTY );

							if ( ! empty( $_slug ) ) {

								/**
								 * http://www.site.com/ru/категория/news/page/2/
								 */
								add_rewrite_rule( $_slug . '/(.+?)/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[1]&paged=$matches[2]', 'top' );

								/**
								 * http://www.site.com/ru/категория/news/
								 */
								add_rewrite_rule( $_slug . '/(.+?)/?$', 'index.php?category_name=$matches[1]', 'top' );

							}

						}

					} else {
						
						$category_ids = array();
						
						/** @noinspection ForeachSourceInspection */
						foreach ( $opts['taxonomy']['category']['term_slug'] as $key => $multilingual_slug ) :

							$slug_id = (int) str_replace( 'term_id_', '', $key );

							if ( 0 === $slug_id ) {
								continue;
							}

							$category_ids[$slug_id] = array();
							
							$slug = $wpdb->get_var( "SELECT slug FROM $wpdb->terms WHERE term_id=" . $slug_id );
							$slug = urldecode( $slug );
							
							$category_ids[$slug_id][WPGlobus::Config()->default_language]['taxonomy_slug'] 	= 'category';
							$category_ids[$slug_id][WPGlobus::Config()->default_language]['term_slug'] 	 	= $slug;
							
							foreach ( $this->extra_languages as $language ) :
							
								/**
								 * @since 1.2.5
								 */		
								if ( empty( $opts['taxonomy']['category']['slug'] ) ) {
									$_slug = get_option('category_base');
								} else {
									$_slug = WPGlobus_Core::extract_text( $opts['taxonomy']['category']['slug'], $language );
								}
								
								$_term_slug = WPGlobus_Core::extract_text( $multilingual_slug, $language );

								$category_ids[$slug_id][$language]['taxonomy_slug'] 	= $_slug;
								
								if ( empty($_term_slug) ) {
									$category_ids[$slug_id][$language]['term_slug'] = $slug;
								} else {
									$category_ids[$slug_id][$language]['term_slug'] = $_term_slug;
								}
								
								
								if ( empty( $_slug ) && ! empty( $_term_slug ) ) {
									/**
									 * e.g. http://www.site.com/es/category/nuevas/page/2/
									 */
									add_rewrite_rule( 'category/' . $_term_slug . '/page/?([0-9]{1,})/?$', 'index.php?category_name=' . $slug . '&paged=$matches[1]', 'top' );

									/**
									 * e.g. http://www.site.com/es/category/nuevas/
									 */
									add_rewrite_rule( 'category/' . $_term_slug . '/?$', 'index.php?category_name=' . $slug, 'top' );
									
								}

								if ( ! empty( $_slug ) && ! empty( $_term_slug ) ) {
									
									/**
									 * e.g. http://www.site.com/ru/категория/новости/page/2/
									 */
									add_rewrite_rule( $_slug . '/' . $_term_slug . '/page/?([0-9]{1,})/?$', 'index.php?category_name=' . $slug . '&paged=$matches[1]', 'top' );
									/**
									 * @since 1.2.5
									 */
									$extra_rewrite_rules[ $_term_slug . '/page/?([0-9]{1,})/?$'] = 'index.php?category_name=' . $slug . '&paged=$matches[1]';
									
									/**
									 * e.g. http://www.site.com/ru/категория/новости/
									 */
									add_rewrite_rule( $_slug . '/' . $_term_slug . '/?$', 'index.php?category_name=' . $slug, 'top' );
									/**
									 * @since 1.2.5
									 */
									$extra_rewrite_rules[$_term_slug . '/?$'] = 'index.php?category_name=' . $slug;
									
									/**
									 * Rule for case when 'category' was removed from URL by 3rd party add-on.
									 * For example @see Yoast SEO with 'Remove the categories prefix' option.
									 * on http://yoursite/wp-admin/admin.php?page=wpseo_titles#top#taxonomies
									 * @since 1.1.53 
									 * 
									 * @todo remove after testing @since 1.2.5
									 */
									//add_rewrite_rule( $_term_slug . '/?$', 'index.php?category_name=' . $slug, 'top' );
									
								}
								
								if ( ! empty( $_slug ) && empty( $_term_slug ) ) {
									/**
									 * e.g. http://www.site.com/de/kategorie/news/page/2/
									 */
									add_rewrite_rule( $_slug . '/(.+?)/page/?([0-9]{1,})/?$', 'index.php?category_name=$matches[1]&paged=$matches[2]', 'bottom' );
									/**
									 * @since 1.2.6
									 */
									//$extra_rewrite_rules[ $slug . '/page/?([0-9]{1,})/?$' ] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
									
									/**
									 * e.g. http://www.site.com/de/kategorie/news/
									 */
									add_rewrite_rule( $_slug . '/(.+?)/?$', 'index.php?category_name=$matches[1]', 'bottom' );
									/**
									 * @since 1.2.6
									 */
									//$extra_rewrite_rules[ $slug . '/(.+?)/?$' ] = 'index.php?category_name=$matches[1]';									
								}							
								
							endforeach;
						endforeach;
						
						/**
						 * Get hierarchical taxonomy list.
						 */
						$list = $this->get_hierarchical_tax_list();

						if ( ! empty( $list ) ) {
							
							$redirect_source = 'index.php?category_name=';
							
							foreach( $list as $item ) :
							
								$category_name = '';
								
								foreach ( $this->extra_languages as $language ) :
								
									$regex = '';
									
									foreach( $item as $key=>$slug_id ) :
										if ( $key == 0 ) {
											$regex .= $category_ids[$slug_id][$language]['taxonomy_slug'] . '/';
											/**
											 * @since 1.2.5
											 */	
											$_taxonomy_slug = $category_ids[$slug_id][$language]['taxonomy_slug'] . '/';
										}
										if ( ! empty($category_ids[$slug_id][$language]) ) {
											$regex .= $category_ids[$slug_id][$language]['term_slug'] . '/';
										} else {
											// 
										}
									endforeach;
									
									if ( empty($category_ids[$slug_id]) ) {
										// @todo
									} else {
										$category_name 	= $category_ids[$slug_id][WPGlobus::Config()->default_language]['term_slug'];
										$redirect 		= $redirect_source . $category_name;
										
										add_rewrite_rule($regex.'page/?([0-9]{1,})/?$', $redirect.'&paged=$matches[1]', 'top');
										add_rewrite_rule($regex.'?$', $redirect, 'top' );
										
										/**
										 * @since 1.2.5
										 */	
										$_rule_key = str_replace($_taxonomy_slug, '', $regex);
										$extra_rewrite_rules[$_rule_key.'page/?([0-9]{1,})/?$'] = $redirect.'&paged=$matches[1]';
										$extra_rewrite_rules[$_rule_key.'?$'] = $redirect;
										
									}
									
								endforeach;

							endforeach;
						}

					}

				endif;
			
			endif; // $is_dot

			/**
			 * Rules for case when 'category' was removed from URL by 3rd party add-on.
			 * For example
			 * 1. Yoast SEO with 'Remove the categories prefix' option.
			 * on http://yoursite/wp-admin/admin.php?page=wpseo_titles#top#taxonomies
			 * 2. Remove Category URL
			 * https://wordpress.org/plugins/remove-category-url/			 
			 * 
			 * @since 1.2.5
			 * @todo may be need check settings of Yoast SEO or `Remove Category URL` status before add extra rules.
			 */			
			if ( ! empty($extra_rewrite_rules) ) {
				foreach( $extra_rewrite_rules as $_k=>$_v ) {
					add_rewrite_rule( $_k, $_v, 'top' );
				}
			}
			
			if ( ! empty( $opts['taxonomy']['category'] ) ) {
				unset( $opts['taxonomy']['category'] );
			}
			
			/**
			 * Custom taxonomies.
			 *
			 * [type_of_service/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?type_of_service=$matches[1]&feed=$matches[2]
			 * [type_of_service/([^/]+)/(feed|rdf|rss|rss2|atom)/?$] => index.php?type_of_service=$matches[1]&feed=$matches[2]
			 * [type_of_service/([^/]+)/embed/?$] => index.php?type_of_service=$matches[1]&embed=true
			 * [type_of_service/([^/]+)/page/?([0-9]{1,})/?$] => index.php?type_of_service=$matches[1]&paged=$matches[2]
			 * [type_of_service/([^/]+)/?$] => index.php?type_of_service=$matches[1]
			 *
			 * http://www.site.com/type_of_service/delivering/
			 *
			 * http://www.site.com/тип-сервиса/delivering/
			 * add_rewrite_rule( 'тип-сервиса/(.+?)/?$', 'index.php?type_of_service=$matches[1]', 'top' );
			 *
			 * http://www.site.com/тип-сервиса/доставка/
			 * add_rewrite_rule( 'тип-сервиса/доставка/?$', 'index.php?&type_of_service=delivery', 'top' );
			 */
			 
			if ( ! empty( $opts['taxonomy'] ) ) :

				foreach ( $opts['taxonomy'] as $taxonomy => $data ) :

					if ( empty( $data['term_slug'] ) ) {

						foreach ( $this->extra_languages as $language ) {
							$_slug = WPGlobus_Core::extract_text( $data['slug'], $language );
							if ( ! empty( $_slug ) ) {
								add_rewrite_rule( $_slug . '/(.+?)/?$', 'index.php?' . $taxonomy . '=$matches[1]', 'top' );
							}
						}

					} else {

						/** @noinspection ForeachSourceInspection */
						foreach ( $data['term_slug'] as $key => $multilingual_slug ) {

							$slug_id = (int) str_replace( 'term_id_', '', $key );

							if ( 0 === $slug_id ) {
								continue;
							}

							$slug = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->terms} WHERE term_id = '%s'", $slug_id ) );
							$slug = urldecode( $slug );

							foreach ( $this->extra_languages as $language ) {

								$_slug      = WPGlobus_Core::extract_text( $data['slug'], $language );
								$_term_slug = WPGlobus_Core::extract_text( $multilingual_slug, $language );

								if ( empty( $_slug ) && ! empty( $_term_slug ) ) {
									/**
									 * e.g. http://www.site.com/de/type_of_service/liefern/page/2/
									 */
									add_rewrite_rule( $taxonomy . '/' . $_term_slug . '/page/?([0-9]{1,})/?$', 'index.php?' . $taxonomy . '=' . $slug . '&paged=$matches[1]', 'top' );

									/**
									 * e.g. http://www.site.com/de/type_of_service/liefern/
									 */
									add_rewrite_rule( $taxonomy . '/' . $_term_slug . '/?$', 'index.php?&' . $taxonomy . '=' . $slug, 'top' );
								}
								if ( ! empty( $_slug ) && empty( $_term_slug ) ) {
									
									/**
									 * For custom taxonomy empty $_term_slug produces the 404 page,
									 * so let to use defualt value.
									 */
									$_term_slug = $slug;
									
									/**
									 * e.g. http://www.site.com/ru/тип-сервиса/delivering/page/2/
									 */
									//add_rewrite_rule( $_slug . '/([^/]+)/page/?([0-9]{1,})/?$', 'index.php?' . $taxonomy . '=$matches[1]&paged=$matches[2]', 'bottom' );

									/**
									 * e.g. http://www.site.com/ru/тип-сервиса/delivering/
									 */
									//add_rewrite_rule( $_slug . '/([^/]+)/?$', 'index.php?' . $taxonomy . '=$matches[1]', 'bottom' );
								}
								if ( ! empty( $_slug ) && ! empty( $_term_slug ) ) {
									/**
									 * e.g. http://www.site.com/ru/тип-сервиса/доставка/page/2/
									 */
									add_rewrite_rule( $_slug . '/' . $_term_slug . '/page/?([0-9]{1,})/?$', 'index.php?' . $taxonomy . '=' . $slug . '&paged=$matches[1]', 'top' );

									/**
									 * e.g. http://www.site.com/ru/тип-сервиса/доставка/
									 */
									add_rewrite_rule( $_slug . '/' . $_term_slug . '/?$', 'index.php?&' . $taxonomy . '=' . $slug, 'top' );
								}
							}
						}

					}
				endforeach;
			endif;

			if ( $this->do_rewrite_rules ) {
				flush_rewrite_rules();
			}

			if ( $this->register_activation ) {
				flush_rewrite_rules();
			}

		}

		/**
		 * Callback.
		 * @scope front
		 *
		 * @param string $url
		 * @param string $language
		 *
		 * @return mixed|string
		 */
		public function filter__after_localize_current_url( $url = '', $language = '' ) {

			if ( is_404() ) {
				return $url;
			}

			if ( empty( $this->opts ) ) {
				return $url;
			}

			if ( empty( $this->opts['taxonomy'] ) ) {
				return $url;
			}

			/**
			 * Cache to speed-up processing.
			 */
			if ( isset( $this->cache[ $language ][ $url ] ) ) {
				return $this->cache[ $language ][ $url ];
			}
			if ( ! isset( $this->cache[ $language ] ) ) {
				$this->cache[ $language ] = array();
			}

			global $wp_query, $post;

			$is_cpt = false;

			$multilingual_taxonomy_slug = '';
			$multilingual_term_slug     = '';
			$taxonomy_slug              = '';
			$term_slug                  = '';
			$current_taxonomy_slug      = '';
			$current_term_slug          = '';

			if ( is_singular() ) {

				$disabled_entities   = $this->disabled_entities;
				$disabled_entities[] = 'post';
				$disabled_entities[] = 'page';

				if ( in_array( $post->post_type, $disabled_entities, true ) ) {
					return $url;
				}

				if ( ! empty( $this->opts['post_type'][ $post->post_type ] ) ) {
					$multilingual_term_slug = $this->opts['post_type'][ $post->post_type ];
				}

				$is_cpt = true;

			} else if ( is_tax() ) {

				$taxonomy 		= $wp_query->query_vars['taxonomy'];
				$taxonomy_slug 	= $this->get_taxonomy_slug( $taxonomy );

				$term_slug = urldecode( $wp_query->query_vars[ $taxonomy ] );
				$term_id   = $wp_query->queried_object_id;

				if ( ! empty( $this->opts['taxonomy'][ $taxonomy ]['slug'] ) ) {
					$multilingual_taxonomy_slug = $this->opts['taxonomy'][ $taxonomy ]['slug'];
				}

				if ( ! empty( $this->opts['taxonomy'][ $taxonomy ]['term_slug'][ 'term_id_' . $term_id ] ) ) {
					$multilingual_term_slug = $this->opts['taxonomy'][ $taxonomy ]['term_slug'][ 'term_id_' . $term_id ];
				}

			} else if ( is_tag() ) {

				$taxonomy_slug = $this->get_taxonomy_slug( 'post_tag' );
				$term_slug     = urldecode( $wp_query->query_vars['tag'] );
				$term_id       = $wp_query->queried_object_id;

				if ( ! empty( $this->opts['taxonomy']['post_tag']['slug'] ) ) {
					$multilingual_taxonomy_slug = $this->opts['taxonomy']['post_tag']['slug'];
				}

				if ( ! empty( $this->opts['taxonomy']['post_tag']['term_slug'][ 'term_id_' . $term_id ] ) ) {
					$multilingual_term_slug = $this->opts['taxonomy']['post_tag']['term_slug'][ 'term_id_' . $term_id ];
				}

			} else if ( is_category() ) {

				$taxonomy_slug = $this->get_taxonomy_slug( 'category' );
				$term_slug     = urldecode( $wp_query->query_vars['category_name'] );
				$term_id       = $wp_query->queried_object_id;

				if ( ! empty( $this->opts['taxonomy']['category']['slug'] ) ) {
					$multilingual_taxonomy_slug = $this->opts['taxonomy']['category']['slug'];
				}

				if ( ! empty( $this->opts['taxonomy']['category']['term_slug'][ 'term_id_' . $term_id ] ) ) {
					$multilingual_term_slug = $this->opts['taxonomy']['category']['term_slug'][ 'term_id_' . $term_id ];
				}

			} else {
				/**
				 * Something other.
				 */
				return $url;
			}

			if ( ! $is_cpt ) {

				$current_taxonomy_slug = WPGlobus_Core::text_filter( $multilingual_taxonomy_slug, WPGlobus::Config()->language, WPGLobus::RETURN_EMPTY );
				if ( empty( $current_taxonomy_slug ) ) {
					$current_taxonomy_slug = $taxonomy_slug;
				}

				$current_term_slug = WPGlobus_Core::text_filter( $multilingual_term_slug, WPGlobus::Config()->language, WPGLobus::RETURN_EMPTY );
				if ( empty( $current_term_slug ) ) {
					$current_term_slug = $term_slug;
				}

			}

			$_term_slug = WPGlobus_Core::text_filter( $multilingual_term_slug, $language, WPGLobus::RETURN_EMPTY );

			$new_url = urldecode( $url );

			if ( $is_cpt ) {

				if ( ! empty( $_term_slug ) ) :

					$new_url = str_replace(
						'/' . $this->get_post_type_slug( $post->post_type ) . '/',
						'/' . $_term_slug . '/',
						$new_url
					);

				endif;

			} else {

				$_taxonomy_slug = WPGlobus_Core::text_filter( $multilingual_taxonomy_slug, $language, WPGLobus::RETURN_EMPTY );

				if ( empty( $_taxonomy_slug ) ) {
					$_taxonomy_slug = $taxonomy_slug;
				}

				if ( empty( $_term_slug ) ) {
					$_term_slug = $term_slug;
				}

				$new_url = str_replace(
					'/' . $current_taxonomy_slug . '/' . $current_term_slug . '/',
					'/' . $_taxonomy_slug . '/' . $_term_slug . '/',
					$new_url
				);

			}

			/**
			 * Cache it.
			 */
			$this->cache[ $language ][ $url ]     = $new_url;
			$this->cache[ $language ]['hreflang'] = $new_url;

			return $new_url;

		}

		/**
		 * Localize data.
		 *
		 * @param array  $data
		 * @param string $page_action
		 *
		 * @return mixed
		 */
		public function filter__localize_data( $data, $page_action ) {

			if ( 'taxonomy-edit' === $page_action ) {

				global $tag;

				$url                               = add_query_arg(
					array(
						'page'      => $this->options_page_key,
						'tab'       => self::TAB,
						'post_type' => $_GET['post_type'],
						'taxonomy'  => $tag->taxonomy,
					),
					admin_url( 'admin.php' )
				);
				$data['multilingualSlug']['title'] = '<div class=""><a href="' . esc_url( $url . '#' . $tag->taxonomy . '_' . $tag->term_id ) . '">' . esc_html__( 'Multilingual slug', 'wpglobus-plus' ) . '</a></div>';

			}

			return $data;

		}

		/**
		 * Enable page to load scripts and styles.
		 * @scope admin
		 *
		 * @param array $enabled_pages
		 *
		 * @return array
		 */
		public function filter__enabled_pages( $enabled_pages ) {

			if ( ! empty( $_GET['page'] ) && $this->options_page_key === $_GET['page']
			     && ! empty( $_GET['tab'] ) && self::TAB === $_GET['tab']
			) {
				$enabled_pages[] = 'admin.php';
			}

			global $pagenow;

			if ( 'options-permalink.php' === $pagenow ) {
				$enabled_pages[] = 'options-permalink.php';
			}

			return $enabled_pages;
		}

		/**
		 * Filter permalink.
		 *
		 * We need to set correct multilingual slug for extra languages.
		 * @scope admin
		 *
		 * @param string  $permalink
		 * @param WP_Post $post
		 * @param string  $language
		 *
		 * @return string
		 */
		public function filter__sample_permalink( $permalink, $post, $language ) {

			static $disabled_entities;

			if ( null === $disabled_entities ) {
				$disabled_entities   = $this->disabled_entities;
				$disabled_entities[] = 'post';
				$disabled_entities[] = 'page';
			}

			if ( in_array( $post->post_type, $disabled_entities, true ) ) {
				return $permalink;
			}

			if ( empty( $this->opts['post_type'][ $post->post_type ] ) ) {
				return $permalink;
			}

			$_slug = WPGlobus_Core::extract_text( $this->opts['post_type'][ $post->post_type ], $language );

			if ( empty( $_slug ) ) {
				return $permalink;
			}

			global $wp_post_types;

			static $home_url, $search;

			if ( null === $home_url ) :

				$home_url = trailingslashit( home_url() );

				if ( ! empty( $wp_post_types[ $post->post_type ]->rewrite['slug'] ) ) {
					$search = $home_url . $wp_post_types[ $post->post_type ]->rewrite['slug'];
				} else {
					$search = $home_url . $post->post_type;
				}

			endif;

			$permalink[0] = str_replace( $search, $home_url . $_slug, $permalink[0] );

			return $permalink;

		}

		/**
		 * Process ajax.
		 */
		public function on__process_ajax() {

			$ajax_return = array();

			$order = $_POST['order'];

			switch ( $order['action'] ) :
				case 'taxonomies-save':

					/** @var array[] $order_data */
					$order_data = $order['data'];
					/** @var array[] $opts */
					$opts = get_option( $this->option_key );

					if ( ! empty( $order_data['post_type'] ) ) {
						$opts['post_type'][ $order['post_type'] ] = $order_data['post_type'][ $order['post_type'] ];
					}

					if ( ! empty( $order_data['taxonomy'] ) ) {
						/**
						 * @var string  $tax
						 * @var array[] $data
						 */
						foreach ( $order_data['taxonomy'] as $tax => $data ) {
							$opts['taxonomy'][ $tax ] = $data;
						}
					}

					/**
					 * Handle post types.
					 */
					if ( ! empty( $opts['post_type'] ) ) :
						foreach ( $opts['post_type'] as $key => $multilingual_slug ) {
							if ( empty( $multilingual_slug ) ) {
								unset( $opts['post_type'][ $key ] );
							}
							// TODO Unset within foreach?
							/** @noinspection NotOptimalIfConditionsInspection */
							if ( empty( $opts['post_type'] ) ) {
								unset( $opts['post_type'] );
							}
						}
					endif;
					
					/**
					 * Handle taxonomies.
					 */
					if ( ! empty( $opts['taxonomy'] ) ) : 
						foreach ( $opts['taxonomy'] as $key => $data ) {

							// TODO ???
							if ( $key === 'post_tag' ) {
							}

							if ( empty( $data['slug'] ) ) {
								unset( $opts['taxonomy'][ $key ]['slug'] );
							}

							if ( ! empty( $data['term_slug'] ) ) :
								foreach ( $data['term_slug'] as $term_slug => $multilingual_slug ) :
									if ( empty( $multilingual_slug ) ) {
										unset( $opts['taxonomy'][ $key ]['term_slug'][ $term_slug ] );
									}
									if ( empty( $opts['taxonomy'][ $key ]['term_slug'] ) ) {
										unset( $opts['taxonomy'][ $key ]['term_slug'] );
									}
								endforeach;
							endif;
							if ( empty( $opts['taxonomy'][ $key ] ) ) {
								unset( $opts['taxonomy'][ $key ] );
							}
						}
					endif;
					
					/**
					 * Save or delete option.
					 */
					if ( empty( $opts ) ) {
						$ajax_return['result'] = delete_option( $this->option_key );
					} else {
						
						/**
						 * Gathering string of used term IDs.
						 */
						$terms_id         = array();
						$opts['terms_id'] = array();

						if ( ! empty( $opts['taxonomy'] ) ) {
							foreach ( $opts['taxonomy'] as $taxonomy => $data ) {

								if ( empty( $data['term_slug'] ) ) {
									continue;
								}
								foreach ( $data['term_slug'] as $key => $_slug ) {

									$_id = (int) str_replace( 'term_id_', '', $key );
									if ( 0 !== $_id ) {
										$terms_id[] = $_id;
									}

								}
							}
							$opts['taxonomy_terms_id'] = implode( ',', $terms_id );
						}

						$ajax_return['result'] = update_option( $this->option_key, $opts, false );

					}

					$this->do_rewrite_rules = true;
					$this->on__init(true);

					break;
			endswitch;

			$ajax_return['order'] = $order;

			wp_send_json_success( $ajax_return );

		}

		/**
		 * Deactivation of the module.
		 */
		public function deactivate() {
			$this->register_deactivation = true;
		}

		/**
		 * Activation of the module.
		 */
		public function activate() {
			$this->register_activation = true;
		}

		
		/**
		 * Callback.
		 * @scope front
		 *
		 * @param string $url
		 * @param string $language
		 *
		 * @return mixed|string
		 */
		public function filter__after_localize_current_url_2( $url = '', $language = '' ) {

			if ( is_404() ) {
				return $url;
			}

			if ( empty( $this->opts ) ) {
				return $url;
			}
			
			/**
			 * @todo remove after testing.
			 * @since 1.1.54
			 */
			//if ( empty( $this->opts['taxonomy'] ) ) {
				//return $url;
			//}

			/**
			 * Cache to speed-up processing.
			 */
			if ( isset( $this->cache[ $language ][ $url ] ) ) {
				return $this->cache[ $language ][ $url ];
			}
			if ( ! isset( $this->cache[ $language ] ) ) {
				$this->cache[ $language ] = array();
			}

			global $wp_query, $post;

			$is_cpt = false;

			$multilingual_taxonomy_slug = '';
			$multilingual_term_slug     = '';
			$taxonomy_slug              = '';
			$term_slug                  = '';
			$current_taxonomy_slug      = '';
			$current_term_slug          = '';
			
			if ( is_attachment() ) {
				
				/**
				 * Don't do anything with attachment now.
				 */
				return $url;
				
			} else if ( is_singular() || ( $wp_query->is_post_type_archive && is_archive() ) ) {
			
				// @since 1.2.4
				$_url = $this->handle_permalink_structure($url);
				if ( $_url ) {
					return $_url;	
				}
			
				$disabled_entities   = $this->disabled_entities;
				$disabled_entities[] = 'post';
				$disabled_entities[] = 'page';

				if ( in_array( $post->post_type, $disabled_entities, true ) ) {
					return $url;
				}

				if ( ! empty( $this->opts['post_type'][ $post->post_type ] ) ) {
					$multilingual_term_slug = $this->opts['post_type'][ $post->post_type ];
				}
				
				/**
				 * Get term slug in current language.
				 *
				 * @since beta-4.
				 */
				$term_slug = $this->get_post_type_slug($wp_query->query['post_type'], WPGlobus::Config()->language);
				
				$is_cpt = true;

			} else if ( is_tax() ) {

				$taxonomy 		= $wp_query->query_vars['taxonomy'];
				$taxonomy_slug 	= $this->get_taxonomy_slug( $taxonomy );

				$term_slug = urldecode( $wp_query->query_vars[ $taxonomy ] );
				$term_id   = $wp_query->queried_object_id;

				if ( ! empty( $this->opts['taxonomy'][ $taxonomy ]['slug'] ) ) {
					$multilingual_taxonomy_slug = $this->opts['taxonomy'][ $taxonomy ]['slug'];
				}

				if ( ! empty( $this->opts['taxonomy'][ $taxonomy ]['term_slug'][ 'term_id_' . $term_id ] ) ) {
					$multilingual_term_slug = $this->opts['taxonomy'][ $taxonomy ]['term_slug'][ 'term_id_' . $term_id ];
				}

			} else if ( is_tag() ) {

				//$taxonomy_slug 	= $this->wordpress_taxonomies['post_tag']['slug'];
				$taxonomy_slug = $this->get_taxonomy_slug( 'post_tag' );
				$term_slug     = urldecode( $wp_query->query_vars['tag'] );
				$term_id       = $wp_query->queried_object_id;

				if ( ! empty( $this->opts['taxonomy']['post_tag']['slug'] ) ) {
					$multilingual_taxonomy_slug = $this->opts['taxonomy']['post_tag']['slug'];
				}

				if ( ! empty( $this->opts['taxonomy']['post_tag']['term_slug'][ 'term_id_' . $term_id ] ) ) {
					$multilingual_term_slug = $this->opts['taxonomy']['post_tag']['term_slug'][ 'term_id_' . $term_id ];
				}

			} else if ( is_category() ) {
				
				/**
				 * var $category_name is array of nested categories for default language.
				 * e.g. 
				 * 		site/category/live-music/ 					  => array([0] => live-music)
				 * 		site/category/live-music/hard-rock/			  => array([0] => live-music, [1] => hard-rock)
				 * 		site/category/live-music/hard-rock/metallica/ => array([0] => live-music, [1] => hard-rock, [2] => metallica)
				 * 		site/category/live-music/metallica/ 		  => array([0] => live-music, [1] => metallica)
				 * 		site/category/metallica/ 		  			  => array([0] => metallica)
				 *
				 * If we are using Custom Structure 'http://site/%category%/%postname%/' and '.' (dot) for Category base 
				 * to exclude /category/ from URL  then
				 * 		site/live-music/ 						=> array([0] => live-music)
				 *		site/live-music/hard-rock/				=> 404 error
				 *		site/live-music/hard-rock/metallica/	=> 404 error
				 */
				$current_category_names = explode('/', $wp_query->query['category_name']);
				
				if ( ! empty($wp_query->query['wpglobus_category_name']) ) {
					$current_category_names = explode('/', $wp_query->query['wpglobus_category_name']);
				}
				
				$taxonomy_slug = $this->get_taxonomy_slug('category');

				if ( '.' == $taxonomy_slug ) {
					/**
					 * Special case to exclude category_base from URLs
					 * with Custom Structure "/%category%/%postname%/".
					 * @todo test it.
					 */
					$taxonomy_slug = $current_taxonomy_slug = '';
					
				} else if( false === strpos( urldecode($url), '/'.$wp_query->query['wpglobus_category_base'].'/' ) ) {
					/**
					 * Case when 'category' was removed from URL by 3rd party add-on.
					 * For example @see Yoast SEO with 'Remove the categories prefix' option.
					 * on http://yoursite/wp-admin/admin.php?page=wpseo_titles#top#taxonomies
					 * @since 1.1.53 
					 */
					$taxonomy_slug = $current_taxonomy_slug = '';

				} else {
					
					$current_taxonomy_slug = $taxonomy_slug;
					
					if ( ! empty( $wp_query->query['wpglobus_category_base'] ) ) {
						$current_taxonomy_slug = $wp_query->query['wpglobus_category_base'];
					}					 
					 
				}
				
				$multilingual_taxonomy_slug = array();
				$multilingual_term_slug 	= array();
				
				foreach( $current_category_names as $key=>$term_slug ) :
					/**
					 * We can get $wp_query->query['wpglobus_category_name'] with language code, e.g. '/ru/новости'
					 * @see explode('/', $wp_query->query['wpglobus_category_name']) above in the code.
					 * So, we need to remove empty element and unneeded language code.
					 * @since 1.1.53
					 */ 
					if ( empty($term_slug) ) {
						unset($current_category_names[$key]);
						continue;
					} else if ( in_array($term_slug, WPGlobus::Config()->enabled_languages) ) {
						unset($current_category_names[$key]);
						continue;
					}
			
					$term_id = $this->get_term_id_by_slug($term_slug);
					
					if ( 0 !== (int) $term_id ) {

						if ( ! empty( $this->opts['taxonomy']['category']['term_slug']['term_id_'.$term_id] ) ) {
							$multilingual_term_slug[$term_slug] = 
								WPGlobus_Core::text_filter( $this->opts['taxonomy']['category']['term_slug']['term_id_'.$term_id], $language, WPGLobus::RETURN_EMPTY );
						}
						
						if ( empty($multilingual_term_slug[$term_slug]) ) {
							$multilingual_term_slug[$term_slug]	= $this->get_term_slug($term_id);
						}

					}
					
				endforeach;
				
				if ( ! empty( $taxonomy_slug ) ) {
					
					/**
					 * @since 1.2.6
					 */
					$multilingual_taxonomy_slug[0] = '';
					if ( ! empty($this->opts['taxonomy']['category']['slug']) ) {
						$multilingual_taxonomy_slug[0] = 
							WPGlobus_Core::text_filter( $this->opts['taxonomy']['category']['slug'], $language, WPGLobus::RETURN_EMPTY );
					}							
					
					if ( empty( $multilingual_taxonomy_slug[0] ) ) {
						$multilingual_taxonomy_slug[0] = $taxonomy_slug;
					}
					
					$multilingual_taxonomy_slug[0] = '/'.$multilingual_taxonomy_slug[0];
				
				}
				
				if ( empty($taxonomy_slug) ) {
					$new_category_names = $multilingual_term_slug;
				} else {
					array_unshift( $current_category_names, '/'.$current_taxonomy_slug );		
					$new_category_names 	= array_merge( $multilingual_taxonomy_slug, $multilingual_term_slug );					
				}

			} else {
				/**
				 * Something other.
				 */
				return $url;
			}

			if ( ! $is_cpt ) {

				$current_taxonomy_slug = WPGlobus_Core::text_filter( $multilingual_taxonomy_slug, WPGlobus::Config()->language, WPGLobus::RETURN_EMPTY );
				if ( empty( $current_taxonomy_slug ) ) {
					$current_taxonomy_slug = $taxonomy_slug;
				}

				$current_term_slug = WPGlobus_Core::text_filter( $multilingual_term_slug, WPGlobus::Config()->language, WPGLobus::RETURN_EMPTY );
				if ( empty( $current_term_slug ) ) {
					$current_term_slug = $term_slug;
				}

			} else {

				/*
				$current_term_slug = WPGlobus_Core::text_filter( $multilingual_term_slug, WPGlobus::Config()->language, WPGLobus::RETURN_EMPTY );
				if ( empty( $current_term_slug ) ) {
					$current_term_slug = $term_slug;
				}
				// */
				
				if ( empty( $current_term_slug ) ) {
					$current_term_slug = $term_slug;
				}				
				
			}

			$_term_slug = WPGlobus_Core::text_filter( $multilingual_term_slug, $language, WPGLobus::RETURN_EMPTY );

			$new_url = urldecode( $url );

			if ( $is_cpt ) {
			
				if ( empty( $_term_slug ) ) {
					$_term_slug = $this->get_post_type_slug($post->post_type);
				}
				
				if ( empty( $_term_slug ) ) {
					$_term_slug = $term_slug;
				}

				if ( ! empty($current_term_slug) && ! empty($_term_slug) ) {
					
					$_current_term_slug = $current_term_slug;
					
					if ( '/' != $_current_term_slug[0] ) {
						/**
						 * @todo add doc about rewrite slug like '/product'.
						 */
						$_current_term_slug = '/' . $_current_term_slug;
					}
					
					$new_url = str_replace(
						$_current_term_slug . '/',
						'/' . $_term_slug . '/',
						$new_url
					); 
					
				} else {
					/**
					 * @todo add doc about incorrect case.
					 */
				}				
				
			} else if ( is_category() )  {
			
				$new_url = str_replace(
					implode('/', $current_category_names),
					implode('/', $new_category_names),
					$new_url
				);
				
			} else {

				$_taxonomy_slug = WPGlobus_Core::text_filter( $multilingual_taxonomy_slug, $language, WPGLobus::RETURN_EMPTY );

				if ( empty( $_taxonomy_slug ) ) {
					$_taxonomy_slug = $taxonomy_slug;
				}

				if ( empty( $_term_slug ) ) {
					$_term_slug = $term_slug;
				}

				$new_url = str_replace(
					'/' . $current_taxonomy_slug . '/' . $current_term_slug . '/',
					'/' . $_taxonomy_slug . '/' . $_term_slug . '/',
					$new_url
				);

			}

			/**
			 * Cache it.
			 */
			$this->cache[ $language ][ $url ]     = $new_url;
			$this->cache[ $language ]['hreflang'] = $new_url;

			return $new_url;

		}		
	
		/**
		 * Get hierarchical taxonomy list.
		 */
		public function get_hierarchical_tax_list($tax='category') {
			
			static $list = null;
			
			if ( ! is_null($list) ) {
				return $list;
			}
			
			global $wpdb;
			
			$query = "SELECT * FROM $wpdb->term_taxonomy WHERE taxonomy LIKE '$tax' AND parent > 0";
			$_terms = $wpdb->get_results($query);

			if ( empty($_terms) ) {
				return;
			}
			
			$_list = $parents = array();
			
			$_terms1 = $_terms;
			
			/**
			 * Extract parent (level 0) categories.
			 */
			foreach( $_terms as $_term ) :
				$may_be_parent = $_term->parent;
				$is_parent = true;
				foreach( $_terms1 as $_term1 ) :
					if ( $_term1->term_id == $may_be_parent ) {
						$is_parent = false;
						break;
					}
				endforeach;
				if ( $is_parent ) {
					$parents[] = $may_be_parent;
				}
			endforeach;

			/**
			 * Build tree of categories.
			 */
			foreach( $parents as $parent_key=>$parent_id ) :
				$_list[$parent_key][] = $parent_id;
				$_terms1 = $_terms;
				
				$process = true;	
				$j = 0;
				while ($process) :
					$found = false; 
					foreach( $_terms1 as $_term_key1=>$_term1 ) :
						if ( $_term1->parent == $parent_id ) {
							$found = true;
							$_list[$parent_key][] = $_term1->term_id;
							$parent_id  = $_term1->term_id;
							unset( $_terms[$_term_key1] );
							break;
						}
					endforeach;
					if ( ! $found ) {
						$process = false;
					}
				
					$j++;
					if ( $j > 200 ) {
						$process = false;
					}
					
				endwhile;
				
			endforeach;
			
			/**
			 * If array of categories is still not empty then add new branches.
			 */
			if ( ! empty($_terms) ) {
				
				$process = true;
				$j = 0;
				while ($process) :
					foreach( $_terms as $_key=>$_term ) :
						foreach( $_list as $_list_key=>$_list_terms ) :

							if ( in_array( $_term->parent, $_list_terms ) ) {
								
								$position = array_search( $_term->parent, $_list_terms );
								$size = count($_list_terms);
								
								if ( false === $position ) {
									//
									break 2;
								} else if ( $position + 1 ==  $size ) {
									$_list[$_list_key][] = $_term->term_id;
									unset($_terms[$_key]);
									break 2;									
								} else {
									$new_list_element = array_slice($_list_terms, 0, $position+1);
									$new_list_element[] = $_term->term_id;
									$_list[] = $new_list_element;
									unset($_terms[$_key]);
									break 2;									
								}
							}
							
						endforeach;
					endforeach;
					
					if ( empty($_terms) ) {
						$process = false;
					}
					$j++;
					if ( $j > 100 ) {
						$process = false;
					}
					
				endwhile;
			}
			
			/**
			 * 
			 */
			$list = array();
			foreach( $_list as $_list_key=>$_list_terms ) :
				
				$list[] = $_list_terms;
				
				$i = count($_list_terms);
				if ( $i < 2 ) {
					continue;
				}
				for( $i; $i > 1; $i-- ) {
					array_pop($_list_terms);
					if ( ! in_array($_list_terms, $list) ) {
						$list[] = $_list_terms;
					}
				}
				
			endforeach;
			
			unset($_list);

			/**
			 * Don't remove. For testing purposes.
			 */
			// error_log(print_r($list, true));
			
			return $list;
		
		}
	
		/**
		 * Get term slug.
		 */
		public function get_term_slug($term_id, $taxonomy='category') {
			
			static $terms = null;

			if ( ! is_null($terms) ) {
				if ( ! empty($terms[$term_id]) ) {
					return $terms[$term_id]->slug;
				}
			}
			
			$terms[$term_id] = get_term($term_id, $taxonomy);
			return $terms[$term_id]->slug;
			
		}
		
		/**
		 * Get term id by term slug.
		 */
		public function get_term_id_by_slug($term_slug, $tax = 'category') {
	
			static $terms = null;

			if ( ! is_null($terms) ) {
				if ( ! empty($terms[$tax][$term_slug]) ) {
					return $terms[$tax][$term_slug];
				}
			}

			if ( empty($term_slug) ) {
				return false;
			}
			
			$terms[$tax][$term_slug] = false;
			
			if ( WPGlobus::Config()->language == WPGlobus::Config()->default_language ) {
				$term 						= get_term_by('slug', $term_slug, $tax);
				$terms[$tax][$term_slug] 	= $term->term_id;
			} else {

				foreach( $this->opts['taxonomy'][$tax]['term_slug'] as $_term_id=>$_multilingual_slug ) {
					if ( false !== strpos($_multilingual_slug, $term_slug) ) {
						$terms[$tax][$term_slug] = str_replace('term_id_', '', $_term_id);
						break;
					}
				}

				if ( ! $terms[$tax][$term_slug] ) {
					/**
					 * Try get term id for default language.
					 */
					$term = get_term_by('slug', $term_slug, $tax);
					if ( $term instanceof WP_Term ) {
						$terms[$tax][$term_slug] = $term->term_id;
					}						
				}
				
			}
			
			return $terms[$tax][$term_slug];
		}
		
		/**
		 * Get parent taxonomy by slug.
		 */
		public function get_parent_by_slug($term_slug, $wp_query) {
			
			static $cats = null;
			
			if ( ! is_null($cats) ) {
				return $cats[$term_slug];
			}

			$cats = array();
			
			/**
			 * Get object of last element.
			 */
			$term = $wp_query->queried_object;			
			
			$process = true;
			while ($process) :
				$cat = get_category($term->parent);
				$cats[$cat->slug] = $cat->term_id;
				if ( $cat->parent == 0 ) {
					$process = false;
				} else {
					$term = $cat;
				}
			endwhile;
			
			return $cats[$term_slug];
			
		}		

		
		/**
		 * Filters the category that gets used in the %category% permalink token.
		 * Only applies to posts with post_type of 'post'.
		 *
		 * @see wp-includes\link-template.php
		 * @since 1.2.4
		 *
		 * @param WP_Term  $cat  The category to use in the permalink.
		 * @param array    $cats Array of all categories (WP_Term objects) associated with the post.
		 * @param WP_Post  $post The post in question.
		 *
		 * @return WP_Term objects
		 */
		public function filter__post_link_category( $cat, $cats, $post ) {
			
			if ( WPGlobus::Config()->language === WPGlobus::Config()->default_language ) {
				return $cat;
			}			

			if ( 'post' != $post->post_type ) {
				return $cat;
			}
			
			if ( ! is_null($this->category) ) {
				return $cat;
			}
			
			/**
			 * @todo may need to check out the case with category parents.
			 * @see code below 'post_link_category' filter in wp-includes\link-template.php
			 */
			$this->category = $cat;

			return $cat;

		}

		/**
		 * Filters the permalink for a post.
		 *
		 * Only applies to posts with post_type of 'post'.
		 *
		 * @since 1.2.4
		 *
		 * @param string  $permalink The post's permalink.
		 * @param WP_Post $post      The post in question.
		 * @param bool    $leavename Whether to keep the post name.
		 *
		 * @return string
		 */		
		public function filter__post_link( $permalink, $post, $leavename ) {
			
			if ( WPGlobus::Config()->language === WPGlobus::Config()->default_language ) {
				return $permalink;
			}
	
			if ( is_null($this->category) ) {
				/**
				 * Return early because filter `post_link_category` was not fired.
				 */
				return $permalink;
			}
			
			$language = WPGlobus::Config()->language;
			
			/**
			 * May be called many times on one page. Let's cache.
			 * @see using $cache_post_link in `handle_permalink_structure` function.
			 */
			if ( isset( $this->cache_post_link[$language][$permalink] ) ) {
				return $this->cache_post_link[$language][$permalink];
			}
			
			if ( 'post' != $post->post_type ) {
				return $permalink;
			}
			
			$_term_id = 'term_id_' . $this->category->term_id;

			if ( empty( $this->opts['taxonomy']['category']['term_slug'][$_term_id] ) ) {
				return $permalink;
			}
			
			$_extra_cat_slug = WPGlobus_Core::text_filter(
				$this->opts['taxonomy']['category']['term_slug'][$_term_id], 
				$language, 
				WPGlobus::RETURN_EMPTY
			);

			if ( empty( $_extra_cat_slug ) ) {
				return $permalink;
			}
			
			$_cat_slug = '/' . $this->category->slug . '/';
			$_new_permalink = $permalink;
			
			if ( false !== strpos($permalink, $_cat_slug ) ) {
	
				$_new_permalink = str_replace( 
					$_cat_slug, 
					'/' . urlencode($_extra_cat_slug) . '/', 
					$permalink 
				);

			}
			
			$this->cache_post_link[$language][$permalink] = $_new_permalink;
			
			foreach( WPGlobus::Config()->enabled_languages as $_language ) {
				if ( $language == $_language ) {
					continue;
				}
				/**
				 * Here we have permalink in current language let's localize it for others languages for correct handling in navigation menu.
				 */
				$_permalink = WPGlobus_Utils::localize_url($_new_permalink, $_language);
				$this->cache_post_link[$_language][$_permalink] = WPGlobus_Utils::localize_url($permalink, $_language);
			}
			
			return $_new_permalink;
		}
		
		/**
		 * Check permalink structure, restore correct URL and handle it.
		 *
		 * @since 1.2.4
		 *
		 * @param string $url URL to handle.
		 *
		 * @return string|bool
		 */
		protected function handle_permalink_structure($url) {

			if ( false === strpos( get_option( 'permalink_structure' ), '%category%' ) ) {
				return false;
			}
			
			/** @global WP_Post $post */ 
			global $post; 
			
			if ( 'post' != $post->post_type ) {
				return false;
			}
			
			/**
			 * May be called many times on one page. Let's cache.
			 */
			static $_cache;
			if ( isset( $_cache[$url] ) ) {
				return $_cache[$url];
			}
			
			$language = WPGlobus_Utils::extract_language_from_url($url);

			if ( empty($language) ) {
				/**
				 * Default language.
				 */
				// @todo testing point.
				if ( ! empty($this->cache_post_link[WPGlobus::Config()->default_language][$url]) ) {
					$_cache[$url] = $this->cache_post_link[WPGlobus::Config()->default_language][$url];
					return $this->cache_post_link[WPGlobus::Config()->default_language][$url];
				}
				
				$_cache[$url] = $url;
				return $url;
			}
			
			if ( is_null($this->category) ) {
				
				$cats = get_the_category( $post->ID );
				
				if ( $cats ) {
					$cats = wp_list_sort(
						$cats,
						array(
							'term_id' => 'ASC',
						)
					);
				}
				$this->category = $cats[0];
			}
	
			$_url = $url;
			if ( ! empty($this->cache_post_link[$language][$url]) ) {
				$_url = $this->cache_post_link[$language][$url];
			}			

			$_term_id = 'term_id_' . $this->category->term_id;

			if ( empty( $this->opts['taxonomy']['category']['term_slug'][$_term_id] ) ) {
				$_cache[$url] = $_url;
				return $_url;
			}
			
			$_extra_cat_slug = WPGlobus_Core::text_filter($this->opts['taxonomy']['category']['term_slug'][$_term_id], $language, WPGlobus::RETURN_EMPTY);

			if ( empty( $_extra_cat_slug ) ) {
				// @todo testing point.
				$_cache[$url] = $_url;
				return $_url;
			}

			$_cat_slug = '/' . $this->category->slug . '/';
			
			$_url = str_replace( 
				$_cat_slug, 
				'/' . $_extra_cat_slug . '/', 
				$_url 
			);
			
			$_cache[$url] = $_url;
			// @todo testing point.

			return $_url;
		}
		
		/**************************************************/
		/**************************************************/
		/**************************************************/
		/**************************************************/
		/**************************************************/

		/**
		 * @todo work with widget
		 *
		 * @param $output
		 * @param $args
		 *
		 * @return mixed
		 */
		public function filter__wp_list_categories(
			$output, /* @noinspection PhpUnusedParameterInspection */
			$args
		) {

			if ( empty( $this->opts ) ) {
				return $output;
			}

			return $output;
		}

		/**
		 * Generate hreflangs.
		 *
		 * @todo  check hreflang tag.
		 * @scope front
		 *
		 * @param $hreflangs
		 *
		 * @return mixed
		 */
		public function filter__hreflang_tag( $hreflangs ) {

			return $hreflangs;

			if ( is_404() ) {
				return $hreflangs;
			}

			if ( is_home() || is_front_page() ) {
				return $hreflangs;
			}

			if ( empty( $this->opts ) ) {
				return $hreflangs;
			}

			return $hreflangs;


			global $post, $wp_query;

			$current_language = WPGlobus::Config()->language;

			$is_cpt = false;

			$multilingual_taxonomy_slug = '';
			$multilingual_term_slug     = '';

			if ( is_singular() ) {

				$disabled_entities   = $this->disabled_entities;
				$disabled_entities[] = 'post';
				$disabled_entities[] = 'page';

				if ( in_array( $post->post_type, $disabled_entities, true ) ) {
					return $hreflangs;
				}

				$multilingual_term_slug = $this->opts['post_type'][ $post->post_type ];

				$is_cpt = true;

			} else if ( is_tax() ) {

				$taxonomy_slug = $wp_query->query_vars['taxonomy'];
				$term_slug     = $wp_query->query_vars[ $taxonomy_slug ];

				$multilingual_taxonomy_slug = $this->opts['taxonomy'][ $taxonomy_slug ]['slug'];

				if ( ! empty( $this->opts['taxonomy'][ $taxonomy_slug ]['term_slug'][ $term_slug ] ) ) {
					$multilingual_term_slug = $this->opts['taxonomy'][ $taxonomy_slug ]['term_slug'][ $term_slug ];
				}

			} else if ( is_tag() ) {

				$taxonomy_slug = $this->wordpress_taxonomies['post_tag'];
				$term_slug     = urldecode( $wp_query->query_vars['tag'] );
				$term_id       = $wp_query->queried_object_id;

				if ( ! empty( $this->opts['taxonomy']['post_tag']['slug'] ) ) {
					$multilingual_taxonomy_slug = $this->opts['taxonomy']['post_tag']['slug'];
				}

				if ( ! empty( $this->opts['taxonomy']['post_tag']['term_slug'][ 'term_id_' . $term_id ] ) ) {
					$multilingual_term_slug = $this->opts['taxonomy']['post_tag']['term_slug'][ 'term_id_' . $term_id ];
				}

			} else if ( is_category() ) {

				$taxonomy_slug = $this->wordpress_taxonomies['category'];
				$term_slug     = urldecode( $wp_query->query_vars['category_name'] );
				$term_id       = $wp_query->queried_object_id;

				$multilingual_taxonomy_slug = $this->opts['taxonomy']['category']['slug'];

				if ( ! empty( $this->opts['taxonomy']['category']['term_slug'][ 'term_id_' . $term_id ] ) ) {
					$multilingual_term_slug = $this->opts['taxonomy']['category']['term_slug'][ 'term_id_' . $term_id ];
				}

			} else {
				/**
				 * Something other.
				 */
				return $hreflangs;
			}

			if ( ! $is_cpt ) {
				$current_taxonomy_slug = WPGlobus_Core::text_filter( $multilingual_taxonomy_slug, $current_language, WPGLobus::RETURN_EMPTY );
				if ( empty( $current_taxonomy_slug ) ) {
					$current_taxonomy_slug = $taxonomy_slug;
				}

				$current_term_slug = WPGlobus_Core::text_filter( $multilingual_term_slug, $current_language, WPGLobus::RETURN_EMPTY );

				if ( empty( $current_term_slug ) ) {
					$current_term_slug = $term_slug;
				}
			}

			$home_url = trailingslashit( WPGlobus_Utils::localize_url( home_url(), WPGlobus::Config()->default_language ) );

			foreach ( WPGlobus::Config()->enabled_languages as $language ) :

				if ( empty( $hreflangs[ $language ] ) ) {
					continue;
				}

				if ( $language === WPGlobus::Config()->default_language ) {
					$language_code = '';
				} else {
					$language_code = $language . '/';
				}

				$_term_slug = WPGlobus_Core::text_filter( $multilingual_term_slug, $language, WPGLobus::RETURN_EMPTY );

				if ( $is_cpt ) {

					if ( empty( $_term_slug ) ) {
						continue;
					}

					$hreflangs[ $language ] = str_replace(
						$home_url . $language_code . $post->post_type,
						$home_url . $language_code . $_term_slug,
						$hreflangs[ $language ]
					);

				} else {

					$_taxonomy_slug = WPGlobus_Core::text_filter( $multilingual_taxonomy_slug, $language, WPGLobus::RETURN_EMPTY );

					if ( empty( $_taxonomy_slug ) ) {
						$_taxonomy_slug = $taxonomy_slug;
					}

					if ( empty( $_term_slug ) ) {
						$_term_slug = $term_slug;
					}

					$hreflangs[ $language ] = urldecode( $hreflangs[ $language ] );

					$hreflangs[ $language ] = str_replace(
						$home_url . $language_code . $current_taxonomy_slug . '/' . $current_term_slug,
						$home_url . $language_code . $_taxonomy_slug . '/' . $_term_slug,
						$hreflangs[ $language ]
					);

				}

			endforeach;

			return $hreflangs;

		}
	
	}

endif;
