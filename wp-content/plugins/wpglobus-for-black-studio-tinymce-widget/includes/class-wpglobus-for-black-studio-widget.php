<?php
/**
 * Class WPGlobus_BSWidget
 * @since 1.0.0
 */
if ( ! class_exists( 'WPGlobus_BSWidget' ) ) :
	
	class WPGlobus_BSWidget {
	
		/**
		 * @var bool $_SCRIPT_DEBUG Internal representation of the define('SCRIPT_DEBUG')
		 */
		protected static $_SCRIPT_DEBUG = false;

		/**
		 * @var string $_SCRIPT_SUFFIX Whether to use minimized or full versions of JS and CSS.
		 */
		protected static $_SCRIPT_SUFFIX = '.min';
		
		/**
		 * string $button Prefix for language button.
		 */		
		var $button	 = 'wpglobus_bs_widget_button_';

		/**
		 * string $button_separator To separate language buttons from others.
		 */		
		var $button_separator = 'wpglobus_bs_widget_separator';
		
		/**
		 * Constructor
		 */
		public function __construct() {
	
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				self::$_SCRIPT_DEBUG  = true;
				self::$_SCRIPT_SUFFIX = '';
			}
			
			$enabled_pages = array(
				'widgets.php',
				'customize.php'
			);

			if ( WPGlobus_WP::is_pagenow( $enabled_pages ) ) :
			
				add_filter( 'mce_buttons', array( $this, 'add_buttons' ), 1 );
				add_filter( 'mce_external_plugins', array( $this, 'mce_external_plugins' ), 1 );
			
				add_action( 'admin_print_scripts', array( $this, 'on_admin_scripts' ) );			
				add_action( 'admin_head', array( $this, 'on_admin_head' ) );			
			
			endif;
			
		}	

		/**
		 * Add CSS
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		function on_admin_head(){ ?>
<style type="text/css">
i.mce-i-wpglobus-bs-separator:before {
	content: "\f319";
    font: normal 20px/1 'dashicons';
    padding: 0;
	color:#00f;
    vertical-align: top;
    speak: none;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    margin-left: -2px;
    padding-right: 2px;
}	
</style>
		<?php
		}
		
		/**
		 * Enqueue scripts
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		function on_admin_scripts(){
			
			wp_register_script(
				'wpglobus-black-studio-widget-init',
				plugin_dir_url( __FILE__ ) . 'js/wpglobus-for-black-studio-widget-init.js', //empty file
				array(),
				WPGLOBUS_BS_WIDGET_VERSION,
				true
			);
			wp_enqueue_script( 'wpglobus-black-studio-widget-init' );
			wp_localize_script(
				'wpglobus-black-studio-widget-init',
				'WPGlobusBSWidget',
				array(
					'data' => array(
						'version' => WPGLOBUS_BS_WIDGET_VERSION,
						'button_separator' => $this->button_separator,
						'text_separator' => '',
						'icon' => 'wpglobus-bs-separator',
						'button' => $this->button,
						'button_class'   => 'wpglobus_bs_widget_button_',
						'button_classes' => 'widget btn wpglobus_bs_widget_button',
						'pagenow'		 => WPGlobus_WP::pagenow()
					)	
				)
			);
			
		}
		
		/**
		 * Add language buttons to toolbars
		 *
		 * @since 1.0.0
		 *
		 * @param array $buttons
		 * @return array
		 */
		function add_buttons( $buttons ){
			
			$buttons[] = $this->button_separator;
			
			foreach( WPGlobus::Config()->enabled_languages as $language ) {
				$buttons[] = $this->button . $language;
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
		function mce_external_plugins( $plugin_array ) {
			
			$plugin_array[ $this->button_separator ] = plugin_dir_url( __FILE__ ) . 'js/wpglobus-for-black-studio-widget'.self::$_SCRIPT_SUFFIX.'.js';
			foreach( WPGlobus::Config()->enabled_languages as $language ) {
				$plugin_array[ $this->button . $language ] = plugin_dir_url( __FILE__ ) . 'js/wpglobus-for-black-studio-widget'.self::$_SCRIPT_SUFFIX.'.js';
			}
			return $plugin_array;

		}
	}	// end class WPGlobus_BSWidget
	
endif;