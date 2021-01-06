<?php
/**
 * Class WPGlobusPlus_Publish
 * @since 1.0.0
 */

if ( ! class_exists( 'WPGlobusPlus_Publish' ) ) :

	/**
	 * Class WPGlobusPlus_Publish
	 */
	class WPGlobusPlus_Publish {

		/**
		 * Meta key for languages post statuses
		 */
		const LANGUAGE_POST_STATUS = '_wpglobus_plus_post_status';

		/**
		 * Option key for wp_options table
		 */
		const LANGUAGE_STATUS = 'wpglobus_plus_language_status';

		/**
		 * @var bool $_SCRIPT_DEBUG Internal representation of the define('SCRIPT_DEBUG')
		 */
		protected static $_SCRIPT_DEBUG = false;

		/**
		 * @var string $_SCRIPT_SUFFIX Whether to use minimized or full versions of JS and CSS.
		 */
		protected static $_SCRIPT_SUFFIX = '.min';

		/**
		 * @var array of enabled statuses.
		 */
		protected static $statuses = array( 'publish', 'pending', 'draft' );

		/**
		 * @var array of post statuses.
		 */
		public $post_status = array();

		/**
		 * Constructor
		 */
		public function __construct() {

			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				self::$_SCRIPT_DEBUG  = true;
				self::$_SCRIPT_SUFFIX = '';
			}

			if ( is_admin() ) {

				add_action( 'wp_ajax_' . __CLASS__ . '_process_ajax', array(
					$this,
					'on_process_ajax'
				) );

				add_action( 'post_submitbox_misc_actions', array(
					$this,
					'on_add_pub_section'
				) );

				add_action( 'admin_print_scripts', array(
					$this,
					'on_admin_scripts'
				) );

				add_filter( 'wpglobus_manage_language_item', array(
					$this,
					'on_manage_column'
				), 10, 3 );

				/** @since 1.1.3 */
				add_action( 'admin_menu', array( $this, 'set_draft_menu' ) );

			} else {

				add_action( 'pre_get_posts', array(
					$this,
					'on_pre_get_posts'
				), 0 );

				add_filter( 'wpglobus_extra_languages', array(
					$this,
					'on_extra_languages'
				), 10, 2 );

				/**
				 * Change the_content filter for plus version
				 * @todo 2015-10-09 This breaks WPG-WC and probably others because it checks for disabled post types on front. Besides, we need to check for the non-publish at the main query and go 404.
				 */
//				remove_filter( 'the_content', array( 'WPGlobus_Filters', 'filter__text' ), 0 );
//				add_filter( 'the_content', array(
//					$this,
//					'filter__text_content'
//				), 0 );

				add_filter( 'template_include', array(
					$this,
					'on_template_include'
				) );

				add_filter( 'wpglobus_hreflang_tag', array(
					$this,
					'on_hreflang'
				) );

				add_filter( 'get_pages', array(
					$this,
					'on_get_pages'
				), 10, 2 );

			}

		}

		/**
		 * Filter get_pages
		 *
		 * @see   filter wpglobus_hreflang_tag
		 * @since 1.0.0
		 *
		 * @param array $pages array of WP_Post objects
		 * @param array $r     UNUSED
		 *
		 * @return array
		 */
		public function on_get_pages(
			$pages, /** @noinspection PhpUnusedParameterInspection */
			$r
		) {

			$posts_id = $this->get_language_status();

			foreach ( $pages as $key => $page ) {
				/** @var WP_Post $page */
				if ( in_array( $page->ID, $posts_id, true ) ) {
					unset( $pages[ $key ] );
				}
			}

			return $pages;

		}

		/**
		 * Filter hreflang tag
		 *
		 * @see   filter wpglobus_hreflang_tag
		 * @since 1.0.0
		 *
		 * @param array $hreflangs
		 *
		 * @return array
		 */
		public function on_hreflang( $hreflangs ) {

			if ( is_singular() ) {

				global $post;

				$post_status = get_post_meta( $post->ID, self::LANGUAGE_POST_STATUS, true );

				if ( ! empty( $post_status ) ) :
					foreach ( $post_status as $language => $status ) {
						if ( ! empty( $hreflangs[ $language ] ) && in_array( $status, array(
								'draft',
								'pending'
							), true )
						) {
							unset( $hreflangs[ $language ] );
						}
					}
				endif;
			}

			return $hreflangs;

		}

		/**
		 * Add language status to flag
		 *
		 * @see   filter wpglobus_manage_language_column
		 * @since 1.0.0
		 *
		 * @param string  $output
		 * @param WP_Post $post
		 * @param string  $language
		 *
		 * @return string
		 */
		public function on_manage_column( $output, $post, $language ) {

			$post_status = get_post_meta( $post->ID, self::LANGUAGE_POST_STATUS, true );

			if ( ! empty( $post_status[ $language ] ) && $post_status[ $language ] !== 'publish' ) :
				$output .= ' - <strong>' . $post_status[ $language ] . '</strong>';
			endif;

			return $output;

		}

		/**
		 * Check language draft status for set 404 page
		 *
		 * @see   filter template_include
		 * @since 1.0.0
		 *
		 * @param string $template
		 *
		 * @return string
		 */
		public function on_template_include( $template ) {

			global $wp_query;

			if ( is_singular() ) :

				$status = $this->get_language_status();
				if ( ! empty( $status ) ) {
					$post = get_post();
					if ( in_array( $post->ID, $status ) ) {
						$wp_query->set_404();

						return get_404_template();
					}
				}

			endif;

			return $template;

		}

		/**
		 * Set query
		 *
		 * @see   WP_Query::get_posts
		 * @since 1.0.0
		 *
		 * @param WP_Query $obj The WP_Query instance (passed by reference).
		 */
		public function on_pre_get_posts( $obj ) {

			if ( is_admin() ) {
				return;
			}

			if ( $obj->is_archive || $obj->is_home || $obj->is_single ) {

				$status = $this->get_language_status();
				if ( ! empty( $status ) ) {
					$obj->set( 'post__not_in', $status );
				}

			}

		}

		/**
		 * Get language status
		 *
		 * @since 1.0.0
		 *
		 * @return array
		 */
		public function get_language_status() {

			/** @var array $opt */
			$opt = get_option( self::LANGUAGE_STATUS );

			if ( ! empty( $opt[ WPGlobus::Config()->language ] ) ) {
				return $opt[ WPGlobus::Config()->language ];
			}

			return array();

		}

		/**
		 * Set post language status
		 *
		 * @since 1.0.0
		 *
		 * @param array $post_status
		 *
		 * @return void
		 */
		public function config( Array $post_status = array() ) {

			$post = get_post();

			if ( empty( $post ) ) {
				return;
			}

			if ( ! count( $post_status ) ) {

				if ( $post->ID && ( is_single() || is_page() ) ) {

					$_post_status = get_post_meta( $post->ID, self::LANGUAGE_POST_STATUS, true );

					$statuses = array();

					foreach ( WPGlobus::Config()->open_languages as $language ) :

						if ( ! count( $_post_status ) ) {
							$statuses[ $language ] = 'publish';
						} elseif ( isset( $_post_status[ $language ] ) ) {
							$statuses[ $language ] = $_post_status[ $language ];
						} else {
							$statuses[ $language ] = 'publish';
						}

					endforeach;

					$this->post_status = array(
						'post_id'     => $post->ID,
						'post_status' => $statuses
					);

				}

			} else {

				$this->post_status = array(
					'post_id'     => $post->ID,
					'post_status' => $post_status
				);

			}

		}

		/**
		 * Set enabled languages
		 *
		 * @see   filter wpglobus_extra_languages
		 * @since 1.0.0
		 *
		 * @param array  $languages
		 * @param string $current_language
		 *
		 * @return array
		 */
		public function on_extra_languages(
			$languages, /** @noinspection PhpUnusedParameterInspection */
			$current_language
		) {
			$enabled_languages = $languages;

			if ( empty( $this->post_status ) ) {
				$this->config();
			}

			if ( is_single() || is_page() ) {


				foreach ( $languages as $key => $l ) :

					/**
					 * Exclude post with draft status for default language too
					 */
					/*
					if ( $l === WPGlobus::Config()->default_language ) {
						continue;
					}
					// */

					if ( isset( $this->post_status[ 'post_status' ][ $l ] ) && 'draft' === $this->post_status[ 'post_status' ][ $l ] ) {
						unset( $enabled_languages[ $key ] );
					}

				endforeach;

			}

			return $enabled_languages;

		}

		/**
		 * Callback for content
		 *
		 * @see   filter the_content
		 * @since 1.0.0
		 *
		 * @param string $text
		 *
		 * @return string
		 */
		public function filter__text_content( $text ) {

			$post = get_post();

			if ( WPGlobus::O()->disabled_entity( $post->post_type ) ) {
				return $text;
			}

			$wpglobus_post_status = get_post_meta( $post->ID, self::LANGUAGE_POST_STATUS, true );

			$status = 'publish';
			if ( ! empty( $wpglobus_post_status[ WPGlobus::Config()->language ] ) ) {
				$status = $wpglobus_post_status[ WPGlobus::Config()->language ];
			}

			switch ( $status ) :
				case 'draft' :
					//
					if ( WPGlobus::Config()->language === WPGlobus::Config()->default_language ) {
						$s = __( 'This post has been marked as Draft.', 'wpglobus-plus' );
					} else {
						$s = sprintf( __( 'This post is available in the main language (%s) only.', 'wpglobus-plus' ), WPGlobus::Config()->en_language_name[ WPGlobus::Config()->default_language ] );
					}

					return $s;
					break;
				case 'pending' :
					// TODO We should never come here.
					return esc_html( __( 'Please, visit later...', 'wpglobus-plus' ) );
					break;
			endswitch;

			return WPGlobus_Core::text_filter(
				$text,
				WPGlobus::Config()->language,
				null,
				WPGlobus::Config()->default_language
			);

		}

		/**
		 * Add section to Publish metabox
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function on_add_pub_section() {

			if ( 'off' === WPGlobus::Config()->toggle ) {
				return;
			}

			$post = get_post();

			if ( WPGlobus::O()->disabled_entity( $post->post_type ) ) {
				return;
			}

			$language_status = get_post_meta( $post->ID, self::LANGUAGE_POST_STATUS, true );

			$menu_element = '<div id="wpglobus-pub-{{language}}" class="wpglobus-pub-status hidden">
						Post status in <span style="text-decoration:underline;">{{language_name}}</span> : 
						<ul>';
			foreach ( self::$statuses as $status ) {
				$class = 'wpglobus-status-' . $status;
				$menu_element .= '<li data-status="' . $status . '" data-language="{{language}}" class="' . $class . '"><span class="wpglobus-checkmark">&nbsp;</span>' . $status . '<span class="wpglobus-spinner">&nbsp;</span></li>';
			}
			$menu_element .= '
						</ul>
					</div>';


			$wpglobus_pub_raw = $menu = '';

			foreach ( WPGlobus::Config()->open_languages as $language ) {

				$class = ' wpglobus-status-publish';
				if ( isset( $language_status[ $language ] ) ) {
					$class = ' wpglobus-status-' . $language_status[ $language ];
				}

				$status = isset( $language_status[ $language ] ) ? $language_status[ $language ] : 'publish';
				$wpglobus_pub_raw .= '<span class="wpglobus-pub-language wpglobus-pub-selector-' . $language . $class . '" 
							data-language="' . $language . '" 
							data-status="' . $status . '">' . $language . '</span>';
				$menu .= str_replace(
					array( '{{language}}', '{{language_name}}' ),
					array( $language, WPGlobus::Config()->en_language_name[ $language ] ),
					$menu_element
				);

			}
			?>
			<div class="misc-pub-section wpglobus-pub wpglobus-switch">
				<span id="wpglobus-pub-raw">&nbsp;&nbsp;<?php _e( 'Status', 'wpglobus-plus' ); ?>:&nbsp;&nbsp;<strong><?php echo $wpglobus_pub_raw; ?></strong></span>
				<span id="wpglobus-status-box"><?php echo $menu; ?></span>
			</div>
			<?php
		}

		/**
		 * Enqueue admin scripts
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function on_admin_scripts() {

			$post = get_post();

			if ( WPGlobus_WP::is_pagenow( array( 'post.php', 'post-new.php' ) ) ) :

				wp_register_script(
					'wpglobus-plus-publish',
					WPGlobusPlus_Asset::url_js( 'wpglobus-plus-publish' ),
					array( 'jquery' ),
					WPGLOBUS_PLUS_VERSION,
					true
				);
				wp_enqueue_script( 'wpglobus-plus-publish' );
				wp_localize_script(
					'wpglobus-plus-publish',
					'WPGlobusPlusPublish',
					array(
						'data' => array(
							'version'      => WPGLOBUS_PLUS_VERSION,
							'post_id'      => $post->ID,
							'statuses'     => self::$statuses,
							'process_ajax' => __CLASS__ . '_process_ajax'
						)
					)
				);

			endif;

		}

		/**
		 * Handle ajax process
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function on_process_ajax() {
			$ajax_return = array();

			$order = $_POST['order'];

			switch ( $order['action'] ) :
				case 'set_status':
					$status                       = get_post_meta( $order['post_id'], self::LANGUAGE_POST_STATUS, true );
					$status[ $order['language'] ] = $order['status'];
					$ajax_return['action']        = $order['action'];
					if ( update_post_meta( $order['post_id'], self::LANGUAGE_POST_STATUS, $status ) ) {

						/** @var array $ls */
						$ls = get_option( self::LANGUAGE_STATUS );
						if ( 'draft' === $order['status'] ) {

							$ls[ $order['language'] ][ $order['post_id'] ] = $order['post_id'];
							update_option( self::LANGUAGE_STATUS, $ls, false );

						} else {

							if ( ! empty( $ls[ $order['language'] ] ) && isset( $ls[ $order['language'] ][ $order['post_id'] ] ) ) {

								unset( $ls[ $order['language'] ][ $order['post_id'] ] );
								update_option( self::LANGUAGE_STATUS, $ls, false );

							}

						}

						$ajax_return['result'] = 'ok';
						$ajax_return['status'] = $order['status'];
					} else {
						$ajax_return['result'] = 'error';
					}
					break;
			endswitch;

			echo json_encode( $ajax_return );
			die();

		}

		/**
		 * Hidden admin menu to call @see WPGlobusPlus_Publish::set_draft_callback
		 * @since 1.1.3
		 */
		public function set_draft_menu() {
			add_submenu_page( null,
				'WPG SET DRAFT', 'SET DRAFT',
				'manage_options',
				'wpglobus-set-draft',
				array(
					$this,
					'set_draft_callback'
				) );
		}

		/**
		 * Hidden admin page to set draft status to specific post type / language
		 * @url   .../wp-admin/admin.php?page=wpglobus-set-draft&lang=fr&post_type=product
		 *
		 * @since 1.1.3
		 */
		public function set_draft_callback() {
			?>
			<div class="wrap">
				<h1>WPGlobus :: <?php esc_html_e( 'Set Draft', 'wpglobus-plus' ); ?></h1>
				<hr />
				<?php

				$ok_to_process = true;

				// Check for required parameters
				if ( empty( $_GET['lang'] ) || empty( $_GET['post_type'] ) ) {
					esc_html_e( 'URL format', 'wpglobus-plus' );
					echo ": &lang=...&post_type=...";

					$ok_to_process = false;
				}

				// Check if language is one of the enabled
				$language = $_GET['lang'];
				if ( ! WPGlobus_Utils::is_enabled( $language ) ) {
					esc_html_e( 'Unknown language', 'wpglobus-plus' );
					echo ': ' . esc_html( $language );

					$ok_to_process = false;
				}

				$post_type = $_GET['post_type'];

				if ( in_array( $post_type, WPGlobus::Config()->disabled_entities, true ) ) {
					esc_html_e( 'Disabled post type', 'wpglobus-plus' );
					echo ': <strong>' . esc_html( $post_type ) . '</strong>';

					$ok_to_process = false;
				}

				if ( $ok_to_process ) {

					echo '<h2>';
					echo esc_html( sprintf(
						__( 'Setting as "draft" all records with post type "%1$s" for language "%2$s"',
							'wpglobus-plus' ),
						$post_type, $language
					) );
					echo '</h2>';
					echo '<hr/>';

					// Get all posts with the specified type
					$posts = get_posts( array(
						'numberposts' => - 1,
						'post_type'   => $post_type,
						'orderby'     => 'ID',
						'order'       => 'ASC'
					) );

					// Loop through the posts
					foreach ( $posts as $post ) {

						// Print the post title and link to edit
						printf(
							'<a href="%s">%s</a> : %s : ',
							admin_url( '/post.php?post=' . $post->ID . '&action=edit' ),
							$post->ID,
							esc_html( apply_filters( 'the_title', $post->post_title ) )
						);

						// Array of post statuses per languages is stored in the post meta
						$post_status = get_post_meta( $post->ID, self::LANGUAGE_POST_STATUS, true );

						if ( ! isset( $post_status[ $language ] ) ) {
							// There was no status for this language..we'll set it
							$post_status[ $language ] = 'draft';
							if ( update_post_meta( $post->ID, self::LANGUAGE_POST_STATUS, $post_status ) ) {
								esc_html_e( 'Successfully set to Draft', 'wpglobus-plus' );
							} else {
								esc_html_e( 'Setting to Draft FAILED!', 'wpglobus-plus' );
							}
						} else {
							// Status already set..we do not change it
							echo esc_html( sprintf(
									__( 'Already set to "%s". Not changing.', 'wpglobus-plus' ),
									$post_status[ $language ] )
							);
						}

						echo '<br/>';
					}

					if ( count( $posts ) === 0 ) {
						esc_html_e( 'No records found.', 'wpglobus-plus' );
					} else {
						echo '<br/>';
						esc_html_e( 'Done.', 'wpglobus-plus' );
					}
				}

				?>
				<hr />
				<a href="<?php echo esc_url( admin_url( 'admin.php' ) . '?page=' . WPGlobusPlus::WPGLOBUS_PLUS_OPTIONS_PAGE . '&tab=publish' ); ?>" class="button button-primary">
					<?php esc_html_e( 'Back to WPGlobus Plus', 'wpglobus-plus' ); ?>
				</a>
			</div>
			<?php
		}
	} // class

endif; // class_exists

# --- EOF
