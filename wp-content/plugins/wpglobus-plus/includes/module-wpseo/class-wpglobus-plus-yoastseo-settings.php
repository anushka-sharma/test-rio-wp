<?php
/**
 * @package WPGlobus Plus
 * @module wpseo: Yoast SEO Plus
 *
 * @since 1.1.48
 */

/**
 * Class WPGlobusPlus_YoastSEO_Settings.
 */
if ( ! class_exists( 'WPGlobusPlus_YoastSEO_Settings' ) ) :

	/**
	 * Class WPGlobusPlus_YoastSEO_Settings
	 */
	class WPGlobusPlus_YoastSEO_Settings {

		const WPSEO_OPTION_KEY = 'wpseo';
		
		/**
		 * Instance.
		 */
		protected static $instance;

		protected static $options;

		/**
		 * Get instance.
		 */
		public static function get_instance(){
			if( null === self::$instance ){
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		public function __construct() {

			self::$options = get_option(self::WPSEO_OPTION_KEY);
			
			self::message();
			self::settings();
			
		}
		
		protected static function settings() {
			
			if ( ! defined( 'WPSEO_VERSION' ) ) {
				return;
			}
			
			if ( ! self::$options['enable_xml_sitemap'] ) {
				return;
			}
			
			$_opts  = '<ul>';
			
			foreach( WPGlobus::Config()->enabled_languages as $language ) {
				$_url = home_url('/') . 'sitemap_index.xml';
				if ( $language != WPGlobus::Config()->default_language ) {
					$_url = WPGlobus_Utils::localize_url( $_url, $language );
				}
				$_opts .= '<li>' .
						  esc_html__( 'Sitemap for', 'wpglobus-plus' ) .
						  ' ' . WPGlobus::Config()->en_language_name[ $language ] . ': <a href="' . $_url . '" target="_blank">' . $_url . '</a></li>';
			}
			
			$_opts .= '</ul>';
			// sitemap_index.xml
			echo $_opts;
		
		}

		protected static function message() {

			if ( defined( 'WPSEO_VERSION' ) ) {
			
				$_message = esc_html__( 'XML sitemaps that Yoast SEO generates are', 'wpglobus-plus' );

				if ( self::$options['enable_xml_sitemap'] ) {
					$_message .= ' ' . esc_html_x( 'enabled', 'yoastseo-settings', 'wpglobus-plus' ) . '.';
				} else {
					$_url = add_query_arg( array( 'page' => 'wpseo_dashboard#top#features' ), admin_url( 'admin.php' ) );

					$_message .= ' ' . esc_html_x( 'disabled', 'yoastseo-settings', 'wpglobus-plus' ) . '. ';

					$_message_2 = esc_html__( 'To enable, visit the %1$sYoast SEO Features page%2$s.', 'wpglobus-plus' );

					$_message .= sprintf( $_message_2, '<a href="' . $_url . '">', '</a>' );
				}
				
			} else {
				$_message = esc_html__( 'To use this module, you need to install and activate the Yoast SEO plugin version 2.3.4 or later.', 'wpglobus-plus' );
			}	?>
			<p style="margin-bottom:10px;font-weight: bold;">
				<?php
				echo $_message; // phpcs:ignore WordPress.XSS
				?>
			</p>
			<?php
		}
		
	}
	
endif;
