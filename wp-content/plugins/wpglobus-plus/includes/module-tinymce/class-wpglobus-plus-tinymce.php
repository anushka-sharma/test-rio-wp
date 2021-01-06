<?php
/**
 * Support of TinyMCE WYSIWYG Editors.
 *
 * @package WPGlobus Plus
 * @module  TinyMCE: WYSIWYG Editor
 */

if ( ! class_exists( 'WPGlobus_TinyMCE' ) ) :

	/**
	 * Class WPGlobus_TinyMCE.
	 *
	 * @since 1.1.25
	 */
	class WPGlobus_TinyMCE {

		/**
		 * Instance.
		 */
		protected static $instance;

		/**
		 * Exclude by DOM id. For example, `fusion_builder_editor`.
		 *
		 * @var string[]
		 */
		protected $excluded_editors = array();

		/**
		 * Exclude by mask. For example, `acf-field-acf_editor-(some numbers here are omitted)`.
		 *
		 * @var string[]
		 */
		protected $excluded_mask = array();

		/**
		 * List of enabled elements on page.
		 * @since 1.1.27
		 */
		protected $enabled_elements;

		protected $option_key = 'wpglobus_plus_module_tinymce';

		/** @var array[]  */
		protected $options = array();

		/**
		 * Get instance.
		 *
		 * @since 1.1.25
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {

			add_action( 'admin_init', array( $this, 'init' ) );

			if ( is_admin() ) :

				add_filter( 'wpglobus_localize_custom_data', array( $this, 'filter__custom_data' ), 10, 3 );

				/**
				 * Add filters for the minimal editor configuration (teeny=true).
				 * @see   https://codex.wordpress.org/Function_Reference/wp_editor
				 * @since 1.1.27
				 */
				add_filter( 'teeny_mce_buttons', array( $this, 'filter__mce_buttons' ), 10, 2 );
				add_filter( 'teeny_mce_plugins', array( $this, 'filter__teeny_mce_plugins' ) );

				/**
				 * Add filters for the full editor configuration (teeny=false).
				 * @see https://codex.wordpress.org/Function_Reference/wp_editor
				 */
				add_filter( 'mce_buttons', array( $this, 'filter__mce_buttons' ), 10, 2 );
				add_filter( 'mce_external_plugins', array( $this, 'filter__mce_external_plugins' ) );

				add_action( 'admin_print_scripts', array( $this, 'admin_print_scripts' ) );
				add_filter( 'wpglobus_enabled_pages', array( $this, 'enable_pages' ) );

			endif;

		}

		/**
		 * @since 1.1.27
		 *
		 * @param string[] $plugins
		 *
		 * @return array
		 */
		public function filter__teeny_mce_plugins( $plugins ) {

			if ( empty( $this->enabled_elements ) ) {
				return $plugins;
			}

			$plugins[] = 'wpglobus_globe';
			foreach ( WPGlobus::Config()->enabled_languages as $language ) {
				$plugins[] = 'wpglobus_language_button_' . $language;
			}

			return $plugins;
		}

		/**
		 * @since 1.1.25
		 *
		 * @param string[] $plugins
		 *
		 * @return string[]
		 */
		public function filter__mce_external_plugins( $plugins ) {

			if ( empty( $this->enabled_elements ) ) {
				return $plugins;
			}

			$plugins['wpglobus_globe'] = WPGlobusPlus_Asset::url_js( 'wpglobus-plus-tinymce' );

			foreach ( WPGlobus::Config()->enabled_languages as $language ) {
				$plugins[ 'wpglobus_language_button_' . $language ] = WPGlobusPlus_Asset::url_js( 'wpglobus-plus-tinymce' );
			}

			return $plugins;
		}

		/**
		 * @since 1.1.25
		 */
		public function get_option_key() {
			return $this->option_key;
		}

		/**
		 * @since 1.1.25
		 *
		 * @param string[] $pages
		 *
		 * @return string[]
		 */
		public function enable_pages( $pages ) {

			/* @noinspection NotOptimalIfConditionsInspection */
			if (
				WPGlobus_WP::is_pagenow( 'admin.php' )
				&& ! empty( $_GET['page'] ) && 'wpglobus-plus-options' === $_GET['page']
				&& ! empty( $_GET['tab'] ) && 'tinymce' === $_GET['tab']
			) {
				/**
				 * Load scripts for WPGlobus Plus settings tab of module TinyMCE: WYSIWYG Editor.
				 */
				$pages[] = 'wpglobus-plus-options';

				return $pages;
			}

			if ( ! empty( $this->options ) && ! empty( $this->options['page_list'] ) ) {

				foreach ( $this->options['page_list'] as $_page => $_elements ) {
					$pages[] = $_page;
				}

			}

			return $pages;
		}

		/**
		 * @since 1.1.25
		 */
		public function admin_print_scripts() {
			/**
			 * Module WPGlobus Plus TinyMCE: WYSIWYG Editor.
			 */
			global $pagenow;

			$return = false;

			if ( empty( $this->enabled_elements ) ) {
				$return = true;
			}

			/* @noinspection NotOptimalIfConditionsInspection */
			if (
				WPGlobus_WP::is_pagenow( 'admin.php' )
				&& ! empty( $_GET['page'] ) && 'wpglobus-plus-options' === $_GET['page']
				&& ! empty( $_GET['tab'] ) && 'tinymce' === $_GET['tab']
			) {
				$return = false;
			}

			if ( $return ) {
				return;
			}

			$i18n            = array();
			$i18n['warning'] = __( 'You need to set name, #id or .class to save content.', 'wpglobus-plus' );
			$i18n['warning'] .= ' ';
			$i18n['warning'] .= __( 'Click Red Globe to open TinyMCE settings page.', 'wpglobus-plus' );

			wp_register_script(
				'wpglobus-plus-tinymce',
				WPGlobusPlus_Asset::url_js( 'wpglobus-plus-tinymce' ),
				array( 'jquery', 'wpglobus-admin' ),
				WPGLOBUS_PLUS_VERSION,
				true
			);
			wp_enqueue_script( 'wpglobus-plus-tinymce' );

			wp_localize_script(
				'wpglobus-plus-tinymce',
				'WPGlobusPlusTinyMCE',
				array(
					'version'      => WPGLOBUS_PLUS_VERSION,
					'process_ajax' => 'WPGlobusPlus_process_ajax',
					'module'       => 'tinymce',
					'pagenow'      => $pagenow,
					'page'         => empty( $_GET['page'] ) ? '' : $_GET['page'],
					'tab'          => empty( $_GET['tab'] ) ? '' : $_GET['tab'],
					'excluded'     => $this->excluded_editors,
					'excludedMask' => $this->excluded_mask,
					'i18n'         => $i18n,
					'settings'     => array(
						'option_key' => $this->option_key,
						'page'       => add_query_arg(
							array(
								'page' => WPGlobusPlus::WPGLOBUS_PLUS_OPTIONS_PAGE,
								'tab'  => 'tinymce'
							),
							admin_url( 'admin.php' )
						)
					)
				)
			);
		}

		/**
		 * @since 1.1.25
		 *
		 * @param array  $page_data_values An array with custom data or null.
		 * @param string $page_data_key    Data key. @since 1.3.0
		 * @param string $page_action      Page. @since 1.5.0
		 *
		 * @return array
		 */
		public function filter__custom_data(
			$page_data_values, /** @noinspection PhpUnusedParameterInspection */
			$page_data_key, $page_action
		) {

			$page_list = array();
			if ( ! empty( $this->options ) && ! empty( $this->options['page_list'] ) ) {
				$page_list = $this->options['page_list'];
			}

			$debug_mode = WPGlobus::SCRIPT_SUFFIX();
			if ( empty( $debug_mode ) ) {
				$debug_mode = 'true';
			} else {
				$debug_mode = 'false';
			}

			$page_data_values['pagenow']        = $page_action;
			$page_data_values['submitElements'] = $page_list;
			$page_data_values['debugMode']      = $debug_mode;

			return $page_data_values;

		}

		/**
		 * @since 1.1.25
		 *
		 * @param array  $buttons   First-row list of buttons.
		 * @param string $editor_id Unique editor identifier, e.g. 'content'.
		 *
		 * @return array
		 */
		public function filter__mce_buttons( $buttons, $editor_id ) {

			if ( $this->is_excluded( $editor_id ) ) {
				return $buttons;
			}

			if ( empty( $this->enabled_elements ) ) {
				return $buttons;
			}

			$buttons[] = 'wpglobus_globe';

			foreach ( WPGlobus::Config()->enabled_languages as $language ) {
				$buttons[] = 'wpglobus_language_button_' . $language;
			}

			return $buttons;

		}

		/**
		 * @param string $editor_id Unique editor identifier, e.g. 'content'.
		 *
		 * @since 1.1.25
		 * @return bool
		 */
		private function is_excluded( $editor_id ) {
			if ( in_array( $editor_id, $this->excluded_editors ) ) {
				return true;
			}
			if ( in_array( $editor_id, $this->excluded_mask ) ) {
				return true;
			}

			return false;
		}

		/**
		 * @since 1.1.25
		 */
		public function init() {

			$this->options = get_option( $this->option_key );

			foreach ( WPGlobus::Config()->enabled_languages as $language ) {
				if ( $language === WPGlobus::Config()->default_language ) {
					$this->excluded_editors[] = 'content';
				} else {
					$this->excluded_editors[] = 'content_' . $language;
				}
			}

			if ( defined( 'WOOCOMMERCE_WPGLOBUS_VERSION' ) ) {
				/**
				 * Exclude Product Short Description editors.
				 * @see   woocommerce-wpglobus\includes\class-wpglobus-wc.php
				 *
				 * @since 1.1.28
				 */
				foreach ( WPGlobus::Config()->enabled_languages as $language ) :
					$this->excluded_editors[] = 'wpglobus_excerpt_' . $language;
				endforeach;
			}

			if ( $this->is_post_pagenow() ) {
				$this->excluded_editors[] = 'acf_content';
				$this->excluded_editors[] = 'acf_settings';
			}
			
			/**
			 * Exclude editor 'wpb_tinymce_content'.
			 *
			 * @see   text block with WYSIWYG editor of WPBakery Visual Composer.
			 * @since 1.1.32
			 */
			$this->excluded_editors[] = 'wpb_tinymce_content';

			/**
			 * Do not run on the Avada's Fusion Builder's elements.
			 *
			 * @since 1.1.32
			 */
			$this->excluded_editors[] = 'fusion_builder_editor';

			/**
			 * Add excluded mask ACF.
			 * e.g. acf-field-acf_editor-586f47c3d    ACF
			 * e.g. acf-editor-586f470889fcf        ACF Pro
			 */
			$this->excluded_mask[] = 'acf-field-acf_editor';
			if ( $this->is_post_pagenow() ) {
				$this->excluded_mask[] = 'acf-editor-';
			}

			/**
			 * Add excluded mask Black Studio TinyMCE Widget.
			 * e.g. widget-black-studio-tinymce-11-text
			 * e.g. black-studio-tinymce-widget
			 */
			$this->excluded_mask[] = 'black-studio-';

			/**
			 * Handling options.
			 */
			global $pagenow;

			$elements = array();
			$page     = '';
			if ( ! empty( $this->options['page_list'][ $pagenow ] ) ) {
				$elements = $this->options['page_list'][ $pagenow ];
				$page     = $pagenow;
			} else if ( ! empty( $_GET['page'] ) && ! empty( $this->options['page_list'][ $_GET['page'] ] ) ) {
				$elements = $this->options['page_list'][ $_GET['page'] ];
				$page     = $_GET['page'];
			}

			if ( 'post.php' === $pagenow ) :

				/**
				 * Let's add elements by default for post.php page because we know them on this page.
				 */
				$default_elements = array( 'publish', 'save' );
				foreach ( $default_elements as $elem ) {
					if ( ! in_array( $elem, $elements, true ) ) {
						$elements[] = $elem;
					}
				}

				$page = $pagenow;

			endif;

			if ( ! empty( $page ) ) :

				/**
				 * Filter enabled elements.
				 * Returning array.
				 * @since 1.1.27
				 *
				 * @param array  $elements List of elements.
				 * @param string $page     Current admin page.
				 */
				$this->enabled_elements = apply_filters( 'wpglobus_plus_tinymce_enabled_elements', $elements, $page );

				if ( ! empty( $this->enabled_elements ) ) {
					/**
					 * Removes duplicate values from an array.
					 */
					$this->enabled_elements = array_unique( $this->enabled_elements );
				}

				$this->options['page_list'][ $page ] = $this->enabled_elements;

			endif;

		}
		
		/**
		 * @since 1.1.39
		 */
		public function is_post_pagenow() {
			if ( WPGlobus_WP::is_pagenow( array('post.php', 'post-new.php') ) ) {
				return true;
			}
			return false;
		}

	}

endif;
