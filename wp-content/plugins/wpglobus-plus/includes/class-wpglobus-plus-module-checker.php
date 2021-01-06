<?php
/**
 * Module Checker
 *
 * @package WPGlobus-Plus
 * @since   1.1.37
 */

/**
 * Class WPGlobus_Plus_Module_Checker
 */
class WPGlobus_Plus_Module_Checker {

	/**
	 * List of modules.
	 *
	 * @var string[][]
	 */
	protected $modules = array();

	/**
	 * Getter.
	 *
	 * @return string[][]
	 */
	public function get_modules() {
		return $this->modules;
	}

	/**
	 * Text for the "Module disabled" warning.
	 *
	 * @var string
	 * @internal
	 */
	protected $warning_module_disabled = '';

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->warning_module_disabled =
			// Translators: %1$s - plugin name (eg ACF); %2$s - plugin version (eg 1.2.3).
			esc_html_x(
				'To use this module, you need to install and activate the %1$s plugin version %2$s or later.',
				'List of modules',
				'wpglobus-plus'
			);

		$this->check_modules();
	}

	/**
	 * Check all modules.
	 */
	protected function check_modules() {
		$this->check_acf();
		$this->check_publish();
		$this->check_taxonomies();
		$this->check_slug();
		$this->check_menu();
		$this->check_menu_settings();
		$this->check_wpglobeditor();
		$this->check_tinymce();
		$this->check_tablepress();
		$this->check_wpseo();
	}

	/**
	 * HTML tag: link to settings.
	 *
	 * @param string $url The settings URL for this module.
	 *
	 * @return string
	 */
	protected static function get_link_to_settings( $url ) {
		return '<div class="wp-ui-text-highlight">'
			   . '<a class="dashicons-before dashicons-admin-settings"'
			   . ' style="margin-left: 1.5em;"'
			   . ' href="' . esc_url( $url ) . '"> '
			   . esc_html__( 'Settings', 'wpglobus' )
			   . '</a></div>';
	}

	/**
	 * HTML tag: disabled module notice.
	 *
	 * @param string $plugin_name    Plugin name.
	 * @param string $plugin_version Plugin version.
	 *
	 * @return string
	 */
	protected function get_disabled_notice( $plugin_name, $plugin_version ) {
		return '<div class="wp-ui-text-notification dashicons-before dashicons-lock" style="margin-left: 1.5em;"> '
			   . sprintf( $this->warning_module_disabled, $plugin_name, $plugin_version ) . '</div>';

	}

	/**
	 * Module ACF.
	 */
	protected function check_acf() {
		$this->modules['acf'] = array(
			'caption'           => esc_html_x( 'ACF Plus', 'List of modules', 'wpglobus-plus' ),
			'checkbox_disabled' => false,
			'subfolder'         => 'module-acf',
		);

		$url_help = WPGlobus_Utils::url_wpglobus_site() . 'documentation/multilingual-advanced-custom-fields-table-field';

		// ACF is active and at least version 4.4.3 (WYSIWYG bugs before).
		$this->modules['acf']['desc'] = '' .
										'<ul style="margin:5px 0 0 30px;">' .
										'<li>' .
										esc_html_x( 'Multilingual WYSIWYG Advanced Custom Fields', 'List of modules', 'wpglobus-plus' ) .
										'</li>';

		$this->modules['acf']['desc'] .= '' .
										 '<li style="margin-bottom: 0;">' .
										 esc_html_x( 'Multilingual Advanced Custom Fields: Table Field', 'List of modules', 'wpglobus-plus' ) .
										 ' ' .
										 '<a href="' . esc_url( $url_help ) . '" target="_blank"' .
										 ' title="More information">' .
										 '<i class="dashicons dashicons-editor-help"' .
										 ' style="font-size: inherit"></i></a>' .
										 '</li>' .
										 '</ul>';
		/* @noinspection PhpUndefinedFunctionInspection */
		if ( ! class_exists( 'acf' ) || version_compare( acf()->settings['version'], '4.4.3', '<' ) ) {
			$this->modules['acf']['desc'] .=
				'<div class="wp-ui-text-notification dashicons-before dashicons-lock" style="margin-left: 1.5em;"> '
				. sprintf( $this->warning_module_disabled, 'ACF', '4.4.3' ) . '</div>';

			$this->modules['acf']['checkbox_disabled'] = true;
		}

	}

	/**
	 * Module Publish.
	 */
	protected function check_publish() {
		$this->modules['publish'] = array(
			'caption'   => esc_html_x( 'Publish', 'List of modules', 'wpglobus-plus' ),
			'desc'      => esc_html_x( 'Publish only the completed translations', 'List of modules', 'wpglobus-plus' ),
			'subfolder' => 'publish',
		);
	}

	/**
	 * Module Multilingual Taxonomies and CPTs.
	 *
	 * Note: taxonomies-beta
	 *
	 * @since    1.1.33
	 * @requires WPGLOBUS_VERSION @todo add version
	 */
	protected function check_taxonomies() {
		if ( version_compare( WPGLOBUS_VERSION, '1.8.1', '>=' ) ) :

			/**
			 * @since beta6 (WPGlobus Plus v.1.1.46) module is accessible without predefined constant.
			 */
			if ( defined( 'WPGLOBUS_PLUS_MODULE_TAXONOMIES' ) && WPGLOBUS_PLUS_MODULE_TAXONOMIES || true ) {
				$module                   = 'taxonomies';
				$this->modules[ $module ] = array(
					'caption' => esc_html_x( 'Taxonomies', 'List of modules', 'wpglobus-plus' ),
					'desc'    => esc_html_x( 'Multilingual Taxonomies and CPTs', 'List of modules', 'wpglobus-plus' ),
				);

				if ( version_compare( PHP_VERSION, WPGLOBUS_PLUS_TAXONOMIES_PHP_VERSION, '>=' ) ) {

					/**
					 * Beta version.
					 */
					$version = '9';
					if ( defined( 'WPGLOBUS_PLUS_MODULE_TAXONOMIES_V8' ) && WPGLOBUS_PLUS_MODULE_TAXONOMIES_V8 ) {
						$version = '8';
					} else if ( defined( 'WPGLOBUS_PLUS_MODULE_TAXONOMIES_V7' ) && WPGLOBUS_PLUS_MODULE_TAXONOMIES_V7 ) {
						$version = '7';
					}
					
					$url_help = WPGlobus_Utils::url_wpglobus_site()
								. 'product/wpglobus-plus/#taxonomies';
								
					/**
					 * @since 1.2.0
					 */
					$url_report = add_query_arg(
						array(
							'page' => WPGlobus::PAGE_WPGLOBUS_HELPDESK,
							'subject' => urlencode('Bug report: Taxonomies')
						),
						admin_url( 'admin.php' )
					);			

					$this->modules[ $module ]['desc'] .= ' -- <span class="wp-ui-notification">Beta version '
														 . esc_html( $version ) . '.</span>'
														 . ' '
														 . '<a href="' . esc_url( $url_help ) . '" target="_blank"'
														 . ' title="More information">'
														 . '<i class="dashicons dashicons-editor-help"'
														 . ' style="font-size: inherit"></i></a>'
														 . '<a href="' . esc_url($url_report) . '" target="_blank"'
														 . ' title="Bug report">'
														 . '<i class="dashicons dashicons-megaphone"'
														 . ' style="font-size: inherit"></i></a>';

					$url = add_query_arg(
						array(
							'page' => 'wpglobus-plus-options',
							'tab'  => $module,
						),
						admin_url( 'admin.php' )
					);

					$this->modules[ $module ]['subtitle'] = self::get_link_to_settings( $url );

					$this->modules[ $module ]['register_activation']   = true;
					$this->modules[ $module ]['register_deactivation'] = true;
					$this->modules[ $module ]['subfolder']             = 'module-' . $module;

				} else {

					/**
					 * Incorrect PHP version.
					 */
					$this->modules[ $module ]['desc'] .= '' .
														 '<ul style="margin:5px 0 0 30px;">' .
														 '<li><span class="wp-ui-notification">' .
														 esc_html_x( 'Current PHP version', 'List of modules', 'wpglobus-plus' ) . ': ' . PHP_VERSION .
														 '</span></li>' .
														 '<li><span class="wp-ui-notification">' .
														 esc_html_x( 'WPGlobus requires PHP version 5.6 or higher to use the Taxonomies module.', 'List of modules', 'wpglobus-plus' ) .
														 '</span></li>' .
														 '</ul>';

					$this->modules[ $module ]['subfolder'] = 'module-' . $module;
				}
			}
		endif;
	}

	/**
	 * Module Slug.
	 */
	protected function check_slug() {
		$this->modules['slug'] = array(
			'caption'   => esc_html_x( 'Slug', 'List of modules', 'wpglobus-plus' ),
			'desc'      => esc_html_x( 'Translate post/page URLs', 'List of modules', 'wpglobus-plus' ),
			'subfolder' => 'module-slug',
		);

	}

	/**
	 * Module Menu.
	 * @since 1.2.7 Move to subfolder.
	 * @requires WPGLOBUS_VERSION 2.2.29 
	 */
	protected function check_menu() {
		$module = 'menu';
		$this->modules['menu'] = array(
			'caption' 	=> esc_html_x( 'Switcher Menu', 'List of modules', 'wpglobus-plus' ),
			'desc'    	=> esc_html_x( 'Customize the Language Switcher Menu layout', 'List of modules', 'wpglobus-plus' ),
			'subfolder' => 'module-'.$module,
		);
		if ( version_compare( WPGLOBUS_VERSION, '2.2.29', '>=' ) ) :
			$url = add_query_arg(
				array(
					'page' => 'wpglobus-plus-menu',
				),
				admin_url( 'admin.php' )
			);

			$this->modules[ $module ]['subtitle'] = self::get_link_to_settings( $url );		
		endif;
	}

	/**
	 * Module Menu Settings.
	 *
	 * @since    1.1.17
	 * @since    1.1.49 Move to subfolder.
	 * @requires WPGLOBUS_VERSION 1.5.8
	 */
	protected function check_menu_settings() {
		if ( version_compare( WPGLOBUS_VERSION, '1.5.8', '>=' ) ) :
			$module                   = 'menu-settings';
			$this->modules[ $module ] = array(
				'caption'   => esc_html_x( 'Menu Settings', 'List of modules', 'wpglobus-plus' ),
				'desc'      => esc_html_x( 'Associate different menus with different languages', 'List of modules', 'wpglobus-plus' ),
				'subfolder' => 'module-' . $module,
			);

			$url = add_query_arg(
				array(
					'page' => 'wpglobus-plus-menu-settings',
				),
				admin_url( 'admin.php' )
			);

			$this->modules[ $module ]['subtitle'] = self::get_link_to_settings( $url );

		endif;
	}

	/**
	 * Module WPGlobus Universal Multilingual Editor.
	 */
	protected function check_wpglobeditor() {
		$module                   = 'wpglobeditor';
		$this->modules[ $module ] = array(
			'caption'      => esc_html_x( 'Editor', 'List of modules', 'wpglobus-plus' ),
			'desc'         => esc_html_x( 'Universal Multilingual Editor', 'List of modules', 'wpglobus-plus' ),
			'subfolder'    => 'module-editor',
			'front-module' => 'front.php',
		);

		$url = add_query_arg(
			array(
				'page' => 'wpglobus-plus-options',
				'tab'  => $module,
			),
			admin_url( 'admin.php' )
		);

		$this->modules[ $module ]['subtitle'] = self::get_link_to_settings( $url );

		/**
		 * // @todo WIP.
		 * $is_module_active = true;
		 * if ( ! empty( $this->options['wpglobeditor'] ) ) {
		 * if ( isset( $this->options['wpglobeditor']['active_status'] ) && empty( $this->options['wpglobeditor']['active_status'] ) ) {
		 * $is_module_active = false;
		 * error_log(print_r('HERE  is_module_active : ', true));
		 *
		 * }
		 * }    */

		if ( ! is_admin() && ! empty( $this->modules[ $module ]['front-module'] ) ) {
			$file = dirname( __FILE__ ) . '/' . $this->modules[ $module ]['subfolder'] . '/' . $this->modules[ $module ]['front-module'];
			if ( file_exists( $file ) ) {
				/** @noinspection PhpIncludeInspection */
				require_once $file;
			}
		}
	}

	/**
	 * Module TinyMCE: WYSIWYG Editor.
	 *
	 * @since    1.1.25
	 * @requires WPGLOBUS_VERSION 1.7.5
	 */
	protected function check_tinymce() {
		if ( version_compare( WPGLOBUS_VERSION, '1.7.5', '>=' ) ) :
			$module                   = 'tinymce';
			$this->modules[ $module ] = array(
				'caption'   => esc_html_x( 'TinyMCE', 'List of modules', 'wpglobus-plus' ),
				'desc'      => esc_html_x( 'WYSIWYG Editor', 'List of modules', 'wpglobus-plus' ),
				'subfolder' => 'module-tinymce',
			);

			$url = add_query_arg(
				array(
					'page' => 'wpglobus-plus-options',
					'tab'  => $module,
				),
				admin_url( 'admin.php' )
			);

			$this->modules[ $module ]['subtitle'] = self::get_link_to_settings( $url );

		endif;
	}

	/**
	 * Module TablePress
	 */
	protected function check_tablepress() {
		$module = 'tablepress';

		$this->modules[ $module ]         = array(
			'caption'           => esc_html_x( 'TablePress', 'List of modules', 'wpglobus-plus' ),
			'checkbox_disabled' => false,
		);
		$this->modules[ $module ]['desc'] = esc_html_x( 'Multilingual TablePress', 'List of modules', 'wpglobus-plus' );

		/* @noinspection PhpUndefinedClassInspection */
		if ( ! class_exists( 'TablePress' ) || version_compare( TablePress::version, '1.6.1', '<' ) ) {
			$this->modules[ $module ]['subtitle'] = $this->get_disabled_notice( 'TablePress', '1.6.1' );

			$this->modules[ $module ]['checkbox_disabled'] = true;
		}
	}

	/**
	 * Module WP-SEO (Yoast).
	 *
	 * @since 1.1.48 added settings page.
	 * @since 1.2.0 updated description.
	 */
	protected function check_wpseo() {
		$module = 'wpseo';
		
		/**
		 * @since 1.2.9
		 * @see wordpress-seo-premium\premium\premium.php
		 */
		$wpseo_premium = false;
		if ( class_exists( 'WPSEO_Premium' ) ) {
			$wpseo_premium = true;
		}
		
		/**
		 * @since 1.2.0
		 */
		$url_report = add_query_arg(
			array(
				'page' => WPGlobus::PAGE_WPGLOBUS_HELPDESK,
				'subject' => urlencode('Bug report: Yoast SEO Premium')
			),
			admin_url( 'admin.php' )
		);

		$this->modules[ $module ] = array(
			'caption'           => esc_html_x( 'Yoast SEO Plus', 'List of modules', 'wpglobus-plus' ),
			'checkbox_disabled' => false,
			'subfolder'         => 'module-wpseo',
		);
		/**
		 * Yoast is active and version at least 2.3.4.
		 * That's when we started; do not want to support old bugs.
		 */
		$this->modules[ $module ]['desc'] =
											'<ul style="margin:5px 0 0 30px;">' .
											 '<li>' .
												esc_html_x( 'Multilingual Focus Keywords and Page Analysis', 'List of modules', 'wpglobus-plus' ) . 
											 '</li>';
		$this->modules[ $module ]['desc'] .= '<li>' .
												sprintf(
													esc_html_x( 'Yoast SEO Premium support is in %1sBeta stage%2s', 'List of modules', 'wpglobus-plus' ),
													'<span class="wp-ui-notification">',			
													'</span>'			
												) .
											    '{{bug-report}}' .
											 '</li>' .
											'</ul>';	
		
		/* @noinspection PhpInternalEntityUsedInspection */
		if ( ! defined( 'WPSEO_VERSION' ) || version_compare( WPSEO_VERSION, '2.3.4', '<' ) ) {
			$this->modules[ $module ]['subtitle'] = $this->get_disabled_notice( 'Yoast SEO', '2.3.4' );

			$this->modules[ $module ]['checkbox_disabled'] = true;
			
			$this->modules[ $module ]['desc'] = str_replace( '{{bug-report}}', '', $this->modules[ $module ]['desc'] );
			
		} else {

			if ( $wpseo_premium ) {
				$this->modules[ $module ]['desc'] = str_replace( 
														'{{bug-report}}',
														'<a href="' . esc_url($url_report) . '" target="_blank" title="Bug report"><i class="dashicons dashicons-megaphone" style="font-size: inherit"></i></a>',
														$this->modules[ $module ]['desc']
													);
			} else {
				$this->modules[ $module ]['desc'] = str_replace( '{{bug-report}}', '', $this->modules[ $module ]['desc'] );
			}

			$url = add_query_arg(
				array(
					'page' => 'wpglobus-plus-options',
					'tab'  => $module,
				),
				admin_url( 'admin.php' )
			);

			$this->modules[ $module ]['subtitle'] = self::get_link_to_settings( $url );

		}
	}
}
