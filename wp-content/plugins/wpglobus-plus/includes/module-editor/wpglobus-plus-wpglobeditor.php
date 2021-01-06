<?php
/**
 * Module WPGlobus Editor
 *
 * @since 1.1.0
 *
 * @package WPGlobus Plus
 * @subpackage Administration
 */

/**
 * @see include_once( 'class-wpglobus-plus-wpglobeditor.php' ) in wpglobus-plus-main.php
 */

if ( ! class_exists('WPGlobus_Editor') ) :

	class WPGlobus_Editor {

		/**
		 * TAB constant.
		 */
		const TAB = 'wpglobeditor';
		
		/**
		 * @var string Current section.
		 */
		protected $current_section = '';
		
		public $option_key = 'wpglobus_plus_wpglobeditor';

		public $opts = array();

		/**
		 * Constructor.
		 */
		public function __construct() {
			
			/**
			 * Check section.
			 */
			if ( empty( $_GET['section'] ) ) {
				$this->current_section = 'general';
			} else {
				$this->current_section = $_GET['section'];
			}
			
			if ( is_admin() ) {
				if ( $this->set_sections() ) {
					$this->get_content($this->current_section);
				}
			}
			
			$_pages = array(
				'wpglobus_language_edit',
				WPGlobusPlus::WPGLOBUS_PLUS_OPTIONS_PAGE,
				// @since 1.1.58 @todo maybe add filter to extend array.
			);
			if ( ! empty( $_GET['page'] ) && in_array($_GET['page'], $_pages ) )
			{
				/**
				 * Don't run at $_pages.
				 */
				return;
			}

			$this->opts = get_option( $this->option_key );

			add_filter( 'wpglobus_enabled_pages', array( $this, 'enable_pages' ) );
			add_filter( 'admin_print_scripts', array( $this, 'on_admin_scripts' ) );

		}

		/**
		 * Enqueue admin scripts.
		 *
		 * @since 1.1.0
		 * @return void
		 */
		public function on_admin_scripts() {

			global $pagenow;
			
			$page = empty( $_GET['page'] ) ? '' : WPGlobus_Utils::safe_get('page');
			
			$elements = array();
			
			/**
			 * First add list of elements from $pagenow...
			 */
			if ( ! empty( $this->opts['page_list'][$pagenow] ) ) {
				$elements = $this->opts['page_list'][$pagenow];
			}
			
			/**
			 * ...then merge list of elements from $page.
			 */			
			if ( ! empty($page) && ! empty( $this->opts[ 'page_list' ][ $page ] ) ) {
				$elements = array_merge( $elements, $this->opts[ 'page_list' ][ $page ] );
			}

			if ( empty( $elements ) ) {
				return;
			}

			$elements = array_unique($elements);
			
			/**
			 * Module WPGlobus Editor.
			 */
			wp_register_script(
				'wpglobus-plus-wpglobeditor',
				WPGlobusPlus_Asset::url_js( 'wpglobus-plus-wpglobeditor' ),
				array( 'jquery', 'wpglobus-admin' ),
				WPGLOBUS_PLUS_VERSION,
				true
			);
			wp_enqueue_script( 'wpglobus-plus-wpglobeditor' );

			wp_localize_script(
				'wpglobus-plus-wpglobeditor',
				'WPGlobusPlusEditor',
				array(
					'version'      => WPGLOBUS_PLUS_VERSION,
					'mode'	       => 'ueditor',
					'process_ajax' => __CLASS__ . '_process_ajax',
					'module'	   => 'wpglobeditor',
					'pagenow'	   => $pagenow,
					'page'		   => $page,
					'elements'	   => $elements
				)
			);

		}
		
		/**
		 * Enable pages for loading WPGlobus scripts/styles.
		 */
		public function enable_pages( $pages ) {

			if ( ! empty( $this->opts['page_list'] ) ) {

				foreach( $this->opts['page_list'] as $page=>$elements ) {

					$pages[] = $page;

				}

			}

			return $pages;

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
			/**
			 * for content @see include_once( 'class-wpglobus-plus-wpglobeditor.php' ) in wpglobus-plus-main.php
			 */
		}
		
		/**
		 * Generate content for section 'debug'.
		 */
		public function get_content_debug() {}				
		
		/**
		 * Create sections.
		 */
		public function set_sections() {

			if ( empty( $_GET['tab'] ) ) {
				return false;
			}

			if ( self::TAB !== $_GET['tab'] ) {
				return false;
			}

			if ( ! class_exists( 'WPGlobusPlus_Sections' ) ) {
				/* @noinspection PhpIncludeInspection */
				require_once WPGlobusPlus::$PLUGIN_DIR_PATH . 'includes/admin/class-wpglobus-plus-sections.php';
			}

			$general_id 		= 'general';
			$test_id    		= 'debug';
			//$option_id  		= 'option';

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

			$args = array(
				'tab'      => self::TAB,
				'sections' => array(
					$general_id => array(
						'caption' => __( 'General', 'wpglobus-plus' ),
						'link'    => $url_general
					),
					//$test_id    => array(
						//'caption' => __( 'Debug Mode', 'wpglobus-plus' ),
						//'link'    => $url_test
					//)
				)
			);

			new WPGlobusPlus_Sections( $args );
			
			return true;
			
		}		

	}

	$WPGlobus_Editor = new WPGlobus_Editor();

endif;
