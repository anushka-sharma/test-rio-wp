<?php
/**
 * Main Module
 *
 * @since      1.0.0
 * @package    WPGlobus Plus
 * @subpackage Administration
 */

/**
 * Class WPGlobusPlus
 */
class WPGlobusPlus {

	/**
	 * All options page
	 */
	const WPGLOBUS_PLUS_OPTIONS_PAGE = 'wpglobus-plus-options';

	/**
	 * Initialized at plugin loader
	 *
	 * @var string
	 */
	public static $PLUGIN_DIR_URL = '';

	/**
	 * Initialized at plugin loader
	 *
	 * @var string
	 */
	public static $PLUGIN_DIR_PATH = '';

	/** @var string[] List of modules */
	public $modules = array();

	/** @var string Key for the `options` table */
	public $option_key = 'wpglobus_plus_options';

	/** @var array Options */
	public $options = array();

	/**
	 * Module publish.
	 */
	public $publish_action_links = array();

	/**
	 * Unused.
	 * @todo remove.
	 */
	protected $current_module = '';
	
	/**
	 * Tabs.
	 * @var array
	 */ 
	protected $tabs = array();
	
	/**
	 * Current tab.
	 * @var string
	 */ 	
	protected $tab = '';
	
	/**
	 * Default tab.
	 * @var string
	 */ 	
	protected $default_tab = 'modules';

	/**
	 * @param string[][] $modules
	 */
	public function __construct( $modules ) {
								  
		$this->publish_action_links['single-action'] = add_query_arg(
			array(
				'page' => 'wpglobus-set-draft',
				'action' => 'single-action',
				'lang' => '{{language}}',
				'post_type' => '{{post_type}}'
			),
			admin_url( 'admin.php' )
		);		

		$this->publish_action_links['bulk-actions'] = add_query_arg(
			array(
				'page' => 'wpglobus-set-draft',
				'action' => 'bulk-actions',
				'post_id' => '{{post_id}}',
				'lang' => '{{language}}'
			),
			admin_url( 'admin.php' )
		);	
		
		$this->modules = $modules;
		
		/**
		 * Set tabs.
		 */
		$this->set_tabs();

		/**
		 * Set current tab.
		 */		
		$this->tab = $this->default_tab;
		if ( ! empty( $_GET['tab'] ) ) {	
			$_tab = $_GET['tab']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended	
			if ( array_key_exists( $_tab, $this->get_tabs() ) ) {
				$this->tab = $_tab;
			} else {
				$this->tab = $this->default_tab;
			}
		}

		$this->options = (array) get_option( $this->option_key );

		add_action( 'wp_ajax_' . __CLASS__ . '_process_ajax', array( $this, 'on_process_ajax' ) );

		add_action( 'admin_print_scripts', array( $this, 'on_admin_scripts' ) );

		add_action( 'admin_print_styles', array( $this, 'on_admin_styles' ) );

		add_action( 'admin_menu', array( $this, 'on_admin_menu' ) );

		add_action( 'wpglobus_customize_register', array( $this, 'customize_register' ) );

		add_action( 'wpglobus_customize_data', array( $this, 'customize_data' ) );

		add_filter(
			'plugin_action_links_' . dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/wpglobus-plus.php',
			array(
				$this,
				'filter__plugin_action_links',
			)
		);

	}

	public function set_current_module( $module ) {
		$this->current_module = $module;
	}

	public function get_current_module() {
		return $this->current_module;
	}

	/**
	 * Register data for customizer
	 *
	 * @since 1.1.9
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function customize_data( $data ) {
		$data['sections']['wpglobus_plus_section'] = 'wpglobus_plus_section';

		$data['settings']['wpglobus_plus_section']['wpglobus_customize_plus_selector_menu_style']['type'] = 'select';

		$data['settings']['wpglobus_plus_section']['wpglobus_customize_plus_selector_menu_style']['option'] = 'switcher_menu_style';

		return $data;
	}

	/**
	 * Add settings for customizer
	 *
	 * @param WP_Customize_Manager $wp_customize
	 *
	 * @since 1.1.9
	 */
	public function customize_register( $wp_customize ) {

		/**
		 * SECTION: WPGlobusPlus
		 */
		$wp_customize->add_section(
			'wpglobus_plus_section',
			array(
				'title'    => __( 'WPGlobus Plus', 'wpglobus' ),
				'priority' => 100,
				'panel'    => 'wpglobus_settings_panel',
			)
		);

		/**
		 * Check for Switcher Menu: Customize the Language Switcher Menu layout
		 */
		$load = true;

		if ( isset( $this->options['menu']['active_status'] ) &&
			 ! $this->options['menu']['active_status']
		) {
			$load = false;
		}

		if ( $load ) :

			/** WPGlobus::Config()->extended_options[ 'switcher_menu_style' ] => wpglobus_customize_plus_selector_menu_style */
			if ( empty( WPGlobus::Config()->extended_options['switcher_menu_style'] ) ) {
				delete_option( 'wpglobus_customize_plus_selector_menu_style' );
			} else {
				update_option( 'wpglobus_customize_plus_selector_menu_style', WPGlobus::Config()->extended_options['switcher_menu_style'] );

			}

			/** Language Selector Menu Style */
			$wp_customize->add_setting(
				'wpglobus_customize_plus_selector_menu_style',
				array(
					'type'       => 'option',
					'capability' => 'manage_options',
					'transport'  => 'postMessage',
				)
			);
			$wp_customize->add_control(
				'wpglobus_customize_plus_selector_menu_style',
				array(
					'settings'    => 'wpglobus_customize_plus_selector_menu_style',
					'label'       => __( 'Language Selector Menu Style', 'wpglobus-plus' ),
					'section'     => 'wpglobus_plus_section',
					'type'        => 'select',
					'priority'    => 10,
					'description' => __( 'Drop-down languages menu or Flat (in one line)', 'wpglobus-plus' ),
					'choices'     => array(
						''         => __( 'Do not change', 'wpglobus-plus' ),
						'dropdown' => __( 'Drop-down (vertical)', 'wpglobus-plus' ),
						'flat'     => __( 'Flat (horizontal)', 'wpglobus-plus' ),
					),
				)
			);

		endif;

		if ( class_exists( 'WPGlobusPlus_Menu_Settings' ) ) :

			/**
			 * WPGlobusPlus link to menu settings page
			 *
			 * @since 1.1.17
			 */
			$wp_customize->add_setting(
				'wpglobus_customize_plus_menu_settings_link',
				array(
					'type'       => 'option',
					'capability' => 'manage_options',
					'transport'  => 'postMessage',
				)
			);

			$link_text = __( 'Go to WPGlobus Menu Settings page', 'wpglobus-plus' );
			$link_text = '<div style="text-decoration:underline;">' . esc_html( $link_text ) . '</div>';

			$wp_customize->add_control(
				new WPGlobusLink(
					$wp_customize,
					'wpglobus_customize_plus_menu_settings_link',
					array(
						'settings' => 'wpglobus_customize_plus_menu_settings_link',
						'title'    => __( 'WPGlobus Menu Settings', 'wpglobus-plus' ),
						'section'  => 'wpglobus_plus_section',
						'priority' => 20,
						'href'     => admin_url() . 'admin.php?page=' . WPGlobusPlus_Menu_Settings::MENU_SLUG,
						'text'     => $link_text,
					)
				)
			);

		endif;

		/** WPGlobusPlus link to options page */
		$wp_customize->add_setting(
			'wpglobus_customize_plus_link',
			array(
				'type'       => 'option',
				'capability' => 'manage_options',
				'transport'  => 'postMessage',
			)
		);

		$link_text = __( 'Go to WPGlobus Plus Options page', 'wpglobus-plus' );
		$link_text = '<div style="text-decoration:underline;">' . esc_html( $link_text ) . '</div>';

		$wp_customize->add_control(
			new WPGlobusLink(
				$wp_customize,
				'wpglobus_customize_plus_link',
				array(
					'settings' => 'wpglobus_customize_plus_link',
					'title'    => __( 'WPGlobus Plus Options', 'wpglobus-plus' ),
					'section'  => 'wpglobus_plus_section',
					'priority' => 30,
					'href'     => admin_url() . 'admin.php?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE,
					'text'     => $link_text,
				)
			)
		);
	}

	/**
	 * Process ajax from any module.
	 */
	public function on_process_ajax() {

		$ajax_return = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$order = $_POST['order'];

		switch ( $order['action'] ) {
			case 'activate-module':
				$order['active_status'] = ! empty( $order['active_status'] ) ? $order['active_status'] : '';

				$options = (array) get_option( $this->option_key );

				if ( '' === $order['active_status'] ) {
					$options[ $order['module'] ]['active_status'] = '';
				} else {
					$options[ $order['module'] ]['active_status'] = $order['active_status'];
				}
				$ajax_return['result'] = update_option( $this->option_key, $options, false );

				break;
			case 'wpglobeditor-save-page':
			case 'wpglobeditor-save-element':
				if ( '0' === $order['key'] ) {
					$order['key'] = 0;
				} else {
					$order['key'] = empty( $order['key'] ) ? '' : (int) $order['key'];
				}

				/** @var array $opts */
				$opts = get_option( 'wpglobus_plus_wpglobeditor' );

				$action = 'add';
				if ( empty( $order['page'] ) ) {
					$ajax_return['result']  = 'error';
					$ajax_return['message'] = 'Empty field page';
				} else {
					if ( ! empty( $opts['page_list'] ) && ! empty( $opts['page_list'][ $order['page'] ] ) ) {
						/**
						 * check element existence in option
						 */
						foreach ( $opts['page_list'][ $order['page'] ] as $key => $value ) {
							if ( $value === $order['element'] ) {
								$action = '';
								break;
							}
						}
					}

					if ( ( 0 === $order['key'] && isset( $opts['page_list'][ $order['page'] ][ $order['key'] ] ) ) ||
						 ( ! empty( $order['key'] ) && isset( $opts['page_list'][ $order['page'] ][ $order['key'] ] ) )
					) {
						/**
						 * check key existence for update
						 */
						$action = 'update';
					}

					if ( 'add' === $action ) {
						$opts['page_list'][ $order['page'] ][] = $order['element'];
						$ajax_return['result']                 = update_option( 'wpglobus_plus_wpglobeditor', $opts, false );
					} elseif ( 'update' === $action ) {
						$opts['page_list'][ $order['page'] ][ $order['key'] ] = $order['element'];
						$ajax_return['result']                                = update_option( 'wpglobus_plus_wpglobeditor', $opts, false );
					} else {
						$ajax_return['result']  = 'error';
						$ajax_return['message'] = 'Element already exists';
					}
				}
				break;
			case 'wpglobeditor-remove':
				/** @var array $opts */
				$opts = get_option( 'wpglobus_plus_wpglobeditor' );

				if ( ! empty( $opts['page_list'][ $order['page'] ][ $order['key'] ] ) ) {
					unset( $opts['page_list'][ $order['page'] ][ $order['key'] ] );
				}

				if ( empty( $opts['page_list'][ $order['page'] ] ) ) {
					unset( $opts['page_list'][ $order['page'] ] );
				}

				if ( empty( $opts['page_list'] ) ) {
					unset( $opts['page_list'] );
				}

				$ajax_return['result'] = update_option( 'wpglobus_plus_wpglobeditor', $opts, false );

				break;
			case 'tinymce-save-page':
			case 'tinymce-save-element':
				// phpcs:ignore
				//error_log( print_r( $_POST, true ) );

				if ( '0' === $order['key'] ) {
					$order['key'] = 0;
				} else {
					$order['key'] = empty( $order['key'] ) ? '' : (int) $order['key'];
				}

				/** @var array $opts */
				$opts = get_option( $order['settings']['option_key'] );
				// phpcs:ignore
				//error_log( print_r( $opts, true ) );

				$action = 'add';
				if ( empty( $order['page'] ) ) :
					$ajax_return['result']  = 'error';
					$ajax_return['message'] = 'Empty field page';
				else :
					if ( ! empty( $opts['page_list'] ) && ! empty( $opts['page_list'][ $order['page'] ] ) ) {
						/**
						 * check element existence in option
						 */
						foreach ( $opts['page_list'][ $order['page'] ] as $key => $value ) {
							if ( $value === $order['element'] ) {
								$action = '';
								break;
							}
						}
					}

					if ( ( 0 === $order['key'] && isset( $opts['page_list'][ $order['page'] ][ $order['key'] ] ) ) ||
						 ( ! empty( $order['key'] ) && isset( $opts['page_list'][ $order['page'] ][ $order['key'] ] ) )
					) {
						/**
						 * check key existence for update
						 */
						$action = 'update';
					}

					if ( 'add' === $action ) {
						$opts['page_list'][ $order['page'] ][] = $order['element'];
						$ajax_return['result']                 = update_option( $order['settings']['option_key'], $opts, false );
					} elseif ( 'update' === $action ) {
						$opts['page_list'][ $order['page'] ][ $order['key'] ] = $order['element'];
						$ajax_return['result']                                = update_option( $order['settings']['option_key'], $opts, false );
					} else {
						$ajax_return['result']  = 'error';
						$ajax_return['message'] = 'Element already exists';
					}
				endif;

				// phpcs:ignore
				//error_log( print_r( $ajax_return, true ) );

				break;
			case 'tinymce-remove':
				/** @var array $opts */
				$opts = get_option( $order['settings']['option_key'] );

				if ( ! empty( $opts['page_list'][ $order['page'] ][ $order['key'] ] ) ) {
					unset( $opts['page_list'][ $order['page'] ][ $order['key'] ] );
				}

				if ( empty( $opts['page_list'][ $order['page'] ] ) ) {
					unset( $opts['page_list'][ $order['page'] ] );
				}

				if ( empty( $opts['page_list'] ) ) {
					unset( $opts['page_list'] );
				}

				if ( empty( $opts ) ) {
					$ajax_return['result'] = delete_option( $order['settings']['option_key'] );
					$ajax_return['action'] = 'complete delete option: ' . $order['settings']['option_key'];
				} else {
					$ajax_return['result'] = update_option( $order['settings']['option_key'], $opts, false );
					$ajax_return['action'] = 'delete from option array: ' . $order['settings']['option_key'];
				}

				// phpcs:ignore
				//$opts = get_option( $order['settings']['option_key'] );
				//error_log( print_r( $opts, true ) );

				break;
		}

		$ajax_return['order'] = $order;

		wp_die( json_encode( $ajax_return ) );

	}

	/**
	 * Enqueue admin scripts
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function on_admin_scripts() {

		$deps = array( 'jquery' );

		/**
		 * Module WPGlobus Editor
		 */
		if ( 'wpglobeditor' === $this->get_tab() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			wp_register_script(
				'wpglobus-plus-wpglobeditor',
				WPGlobusPlus_Asset::url_js( 'wpglobus-plus-wpglobeditor' ),
				array( 'jquery' ),
				WPGLOBUS_PLUS_VERSION,
				true
			);
			wp_enqueue_script( 'wpglobus-plus-wpglobeditor' );

			wp_localize_script(
				'wpglobus-plus-wpglobeditor',
				'WPGlobusPlusEditor',
				array(
					'version'      => WPGLOBUS_PLUS_VERSION,
					'process_ajax' => __CLASS__ . '_process_ajax',
					'module'       => 'wpglobeditor',
				)
			);
		}

		/**
		 * Module Taxonomies.
		 *
		 * @since 1.1.33
		 */
		if ( 'taxonomies' === $this->get_tab() ) {
			$deps = array( 'jquery', 'jquery-ui-core', 'jquery-ui-tabs' );
		}

		wp_register_script(
			'wpglobus-plus-main',
			WPGlobusPlus_Asset::url_js( 'wpglobus-plus-main' ),
			$deps,
			WPGLOBUS_PLUS_VERSION,
			true
		);
		wp_enqueue_script( 'wpglobus-plus-main' );

		wp_localize_script(
			'wpglobus-plus-main',
			'WPGlobusPlus',
			array(
				'version'           => WPGLOBUS_PLUS_VERSION,
				'option_page'       => 'admin.php?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE,
				'caption_menu_item' => esc_html__( 'WPGlobus Plus', 'wpglobus-plus' ),
				'process_ajax'      => __CLASS__ . '_process_ajax',
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				'tab'               => $this->get_tab(),
				//'bulk_status_link'  => $this->bulk_status_link,
				'customize'         => array(
					'plusOptionPage' => admin_url() . 'admin.php?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE,
				),
				'modules'           => $this->modules,
				'publish_action_links'  => $this->publish_action_links,
			)
		);

		/**
		 * @global string $pagenow
		 * //        global $pagenow;
		 * //        if ( $pagenow === 'admin.php' && isset( $_GET['page'] ) && self::WPGLOBUS_PLUS_OPTIONS_PAGE === $_GET['page']  ) :
		 * // maybe later
		 * //        endif;
		 */

	}

	/**
	 * Add hidden submenu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function on_admin_menu() {

		add_submenu_page(
			null,
			'',
			'',
			'administrator',
			self::WPGLOBUS_PLUS_OPTIONS_PAGE,
			array(
				$this,
				'options_page',
			)
		);

	}

	/**
	 * View: options panel.
	 */
	public function options_page() {

		$active_tab = $this->get_tab();

		?>

		<div class="wrap about-wrap wpglobus-about-wrap">
			<h1 class="wpglobus"><span class="wpglobus-wp">WP</span>Globus
				<?php echo esc_html_x( 'Plus', 'Part of the WPGlobus Plus', 'wpglobus-plus' ); ?>
				<span class="wpglobus-version"><?php echo esc_html( WPGLOBUS_PLUS_VERSION ); ?></span>
			</h1>

			<h2 class="nav-tab-wrapper">
				<?php
				foreach ( $this->get_tabs() as $tab => $option ) {

					/**
					 * @since 1.1.55
					 */
					if ( isset( $option['disabled'] ) && $option['disabled'] ) {
						continue;
					}

					if ( $active_tab === $tab ) {
						$option['class'] .= ' nav-tab-active';
					}
					?>
					<a href="<?php echo esc_url( $option['href'] ); ?>"
							class="<?php echo esc_attr( $option['class'] ); ?>"><?php echo wp_kses_post( $option['caption'] ); ?></a>
				<?php } ?>
			</h2>
			<div class="wpglobus-plus-sections" style="width:95%;margin:0 auto;overflow:auto;display:none;">
			</div>
			<div class="feature-section one-col wpglobus-plus-section widefat">
				<div class="col">
					<?php

					switch ( $active_tab ) {
						case $this->default_tab:
							?>

							<h3><?php esc_html_e( 'Active Modules', 'wpglobus-plus' ); ?></h3>

							<div class="wp-ui-highlight"
									style="padding: .5em; margin-bottom: 1em;"><?php esc_html_e( 'Uncheck the modules you are not planning to use:', 'wpglobus-plus' ); ?></div>
							<?php

							foreach ( $this->modules as $module => $option ) {

								/**
								 * A module is considered active by default,
								 * so the condition is either unset or true.
								 */
								$is_module_active = (
									! isset( $this->options[ $module ]['active_status'] )
									|| ! empty( $this->options[ $module ]['active_status'] )
								);

								$is_checkbox_disabled = (
									isset( $option['checkbox_disabled'] )
									&& $option['checkbox_disabled']
								);
								?>
								<div class="module-block">
									<span class="wpglobus-plus-spinner"></span>
									<label for="wpglobus-plus-<?php echo esc_attr( $module ); ?>"
											style="display: block">
										<input type="checkbox"
												class="wpglobus-plus-module"
												data-module="<?php echo esc_attr( $module ); ?>"
												id="wpglobus-plus-<?php echo esc_attr( $module ); ?>"
												name="wpglobus-plus-<?php echo esc_attr( $module ); ?>"
											<?php checked( $is_module_active ); ?>
											<?php disabled( $is_checkbox_disabled ); ?> />
										<strong><?php echo wp_kses_post( $option['caption'] ); ?></strong>:
										<?php echo wp_kses_post( $option['desc'] ); ?>
									</label>
									<?php if ( ! empty( $option['subtitle'] ) ) { ?>
										<?php echo wp_kses_post( $option['subtitle'] ); ?>
									<?php } ?>
								</div>
								<br/>
								<?php
							}
							?>
							<hr/>
							<div class="return-to-dashboard" style="padding-left:10px;">
								<a class="button button-primary" href="admin.php?page=wpglobus_options">
									<?php esc_html_e( 'Go to WPGlobus Settings', 'wpglobus' ); ?>
								</a>
							</div>

							<?php
							break;
						case 'publish':
							$is_module_active = true;
							if ( ! empty( $this->options['publish'] ) ) {
								if ( isset( $this->options['publish']['active_status'] ) && empty( $this->options['publish']['active_status'] ) ) {
									$is_module_active = false;
								}
							}
							if ( $is_module_active ) {
								if ( empty( $this->modules['publish']['subfolder'] ) ) {
									/** @noinspection PhpIncludeInspection */
									require_once 'class-wpglobus-plus-publish-extend.php';
								} else {
									/** @noinspection PhpIncludeInspection */
									require_once dirname( __FILE__ ) . '/' . $this->modules['publish']['subfolder'] . '/class-wpglobus-plus-publish-extend.php';
								}
								WPGlobusPlus_Publish_Extend::constructor( $this->publish_action_links );

							} else {
								?>
								<h4><?php esc_html_e( 'Please, activate module Publish', 'wpglobus-plus' ); ?></h4>
								<?php
							}

							break;
						case 'wpglobeditor':
							$is_module_active = true;
							if ( ! empty( $this->options['wpglobeditor'] ) ) {
								if ( isset( $this->options['wpglobeditor']['active_status'] ) && empty( $this->options['wpglobeditor']['active_status'] ) ) {
									$is_module_active = false;
								}
							}

							if ( $is_module_active ) {

								if ( empty( $this->modules['wpglobeditor']['subfolder'] ) ) {
									/** @noinspection PhpIncludeInspection */
									require_once 'class-wpglobus-plus-wpglobeditor.php';
								} else {
									/** @noinspection PhpIncludeInspection */
									require_once dirname( __FILE__ ) . '/' . $this->modules['wpglobeditor']['subfolder'] . '/class-wpglobus-plus-wpglobeditor.php';
								}
								/** @noinspection OnlyWritesOnParameterInspection */
								/** @noinspection PhpUnusedLocalVariableInspection */
								$WPGlobusPlus_Editor_Table = new WPGlobusPlus_Editor_Table();

							} else {
								?>
								<h4><?php esc_html_e( 'Please, activate module WPGlobus Editor', 'wpglobus-plus' ); ?></h4>
								<?php
							}

							break;
						case 'tinymce':
							$is_module_active = true;
							if ( ! empty( $this->options['tinymce'] ) ) {
								if ( isset( $this->options['tinymce']['active_status'] ) && empty( $this->options['tinymce']['active_status'] ) ) {
									$is_module_active = false;
								}
							}
							if ( $is_module_active ) {
								if ( empty( $this->modules['tinymce']['subfolder'] ) ) {
									/** @noinspection PhpIncludeInspection */
									require_once 'class-wpglobus-plus-tinymce-table.php';
								} else {
									/** @noinspection PhpIncludeInspection */
									require_once dirname( __FILE__ ) . '/' . $this->modules['tinymce']['subfolder'] . '/class-wpglobus-plus-tinymce-table.php';
								}
								/** @noinspection OnlyWritesOnParameterInspection */
								/** @noinspection PhpUnusedLocalVariableInspection */
								WPGlobusPlus_TinyMCE_Table::get_instance();

							} else {
								?>
								<h4><?php esc_html_e( 'Please, activate module TinyMCE: WYSIWYG Editor', 'wpglobus-plus' ); ?></h4>
								<?php
							}

							break;
						case 'taxonomies':
							$is_module_active = true;
							if ( ! empty( $this->options['taxonomies'] ) ) {
								if ( isset( $this->options['taxonomies']['active_status'] ) && empty( $this->options['taxonomies']['active_status'] ) ) {
									$is_module_active = false;
								}
							}
							if ( $is_module_active ) {
								?>
								<div id="tabs-taxonomies"></div>
								<?php
							} else {
								?>
								<h4><?php esc_html_e( 'Please, activate module Taxonomies: Multilingual Taxonomies and CPTs', 'wpglobus-plus' ); ?></h4>
								<p>
									<?php esc_html_e( 'With the “Taxonomies” module, you will be able to translate slugs of the categories, tags, and terms, including those coming with Custom Post Types.', 'wpglobus-plus' ); ?>
								</p>
								<?php if ( ! defined( 'WPGLOBUS_PLUS_MODULE_TAXONOMIES' ) ) { ?>
									<p>
										<?php esc_html_e( 'This module is currently at a beta-testing stage. Please contact us for more details.', 'wpglobus-plus' ); ?>
									</p>
									<?php
								}
							}

							break;
						case 'wpseo':
							$is_module_active = true;
							if ( ! empty( $this->options['wpseo'] ) ) {
								if ( isset( $this->options['wpseo']['active_status'] ) && empty( $this->options['wpseo']['active_status'] ) ) {
									$is_module_active = false;
								}
							}
							if ( $is_module_active ) {
								?>
								<div id="tabs-wpseo">
									<?php
									/** @noinspection PhpIncludeInspection */
									require_once dirname( __FILE__ ) . '/' . $this->modules['wpseo']['subfolder'] . '/class-wpglobus-plus-yoastseo-settings.php';
									WPGlobusPlus_YoastSEO_Settings::get_instance();
									?>
								</div>
							<?php } else { ?>
								<h4><?php esc_html_e( 'Please, activate module Yoast SEO Plus: Multilingual Focus Keywords and Page Analysis', 'wpglobus-plus' ); ?></h4>
								<p>
									<?php //phpcs:ignore
									// esc_html_e( 'With the “Taxonomies” module, you will be able to translate slugs of the categories, tags, and terms, including those coming with Custom Post Types.', 'wpglobus-plus' ); ?>
								</p>
								<?php
							}
							break;
					}
					?>
				</div>
			</div>

		</div> <!-- .wrap -->
		<?php
		// http://www.wpg.dev/wp-admin/admin.php?page=wpglobus-about
	}

	/**
	 * Enqueue admin styles
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function on_admin_styles() {

		$deps = array();
		
		/**
		 * Module WPGlobus Editor.
		 */
		if ( 'wpglobeditor' === $this->get_tab() ) {
			wp_register_style(
				'wpglobus-plus-wpglobeditor',
				WPGlobusPlus_Asset::url_css( 'wpglobus-plus-wpglobeditor' ),
				array(),
				WPGLOBUS_PLUS_VERSION,
				'all'
			);
			wp_enqueue_style( 'wpglobus-plus-wpglobeditor' );
		}

		global $WPGlobus;

		$enabled_pages = array(
			#Module Publish
			'post.php',
			'post-new.php',
			'admin.php',
		);

		$enabled_pages = array_merge( $enabled_pages, $WPGlobus->enabled_pages );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = empty( $_GET['page'] ) ? '' : $_GET['page'];

		if ( WPGlobus_WP::is_pagenow( $enabled_pages ) || in_array( $page, $enabled_pages, true ) ) :

			/**
			 * Load CSS for enabled pages.
			 *
			 * @since 1.1.39
			 * @todo  remove after testing.
			 * if (
			 * 'admin.php' === $pagenow &&
			 * ( empty( $_GET['page'] ) || self::WPGLOBUS_PLUS_OPTIONS_PAGE !== $_GET['page'] )
			 * ) {
			 * return;
			 * }
			 * // */

			/**
			 * Module Taxonomies.
			 *
			 * @since 1.1.33
			 */
			/**
			 * if ( 'taxonomies' === $this->get_tab() ) {
			 * // @todo remove after testing.
			 * // $deps = array( 'jquery-ui-tabs' );
			 * }
			 */

			wp_register_style(
				'wpglobus-plus-admin',
				WPGlobusPlus_Asset::url_css( 'wpglobus-plus-admin' ),
				$deps,
				WPGLOBUS_PLUS_VERSION,
				'all'
			);
			wp_enqueue_style( 'wpglobus-plus-admin' );

		endif;

	}

	/**
	 * Add a link to the settings page to the plugins list.
	 *
	 * @since 1.1.25
	 *
	 * @param array $links array of links for the plugins, adapted when the current plugin is found.
	 *
	 * @return array $links
	 */
	public function filter__plugin_action_links( $links ) {
		
		/*
		 * W.I.P @since 1.2.0
		$support_link = '<a style="font-weight:bold;" href="' . esc_url( admin_url( 'admin.php?page=' . WPGlobus::PAGE_WPGLOBUS_HELPDESK ) ) . '">' .
						 esc_html__( 'Premium Support' ) .
						 '</a>';
		array_unshift( $links, $support_link );		
		// */
		
		$settings_link = '<a class="dashicons-before dashicons-admin-settings" href="' . esc_url( admin_url( 'admin.php?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE ) ) . '">' .
						 esc_html__( 'Settings' ) .
						 '</a>';
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Get options page key.
	 *
	 * @since 1.1.33
	 */
	public function get_options_page_key() {
		return self::WPGLOBUS_PLUS_OPTIONS_PAGE;
	}

	/**
	 * Get current tab.
	 *
	 * @since 1.3.0
	 * @return string
	 */
	public function get_tab() {
		return $this->tab;
	}

	/**
	 * Get tabs.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function get_tabs() {
		return $this->tabs;
	}
	
	/**
	 * Set tabs.
	 *
	 * @since 1.3.0
	 */	
	public function set_tabs() {

		/**
		 * Default tab.
		 */
		$this->tabs[$this->default_tab] = array(
			'href'    => '?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE . '&tab='.$this->default_tab,
			'class'   => 'nav-tab',
			'caption' => esc_html__( 'Modules', 'wpglobus-plus' ),
		);

		$this->tabs['wpglobeditor'] = array(
			'href'    => '?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE . '&tab=wpglobeditor',
			'class'   => 'nav-tab',
			'caption' => esc_html__( 'Editor Settings', 'wpglobus-plus' ),
		);

		/**
		 * Tab TinyMCE.
		 *
		 * @since 1.1.25
		 */
		$this->tabs['tinymce'] = array(
			'href'    => '?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE . '&tab=tinymce',
			'class'   => 'nav-tab',
			'caption' => esc_html__( 'TinyMCE Settings', 'wpglobus-plus' ),
		);
		
		/**
		 * Tab publish.
		 *
		 * @since 1.1.8
		 */
		$this->tabs['publish'] = array(
			'href'    => '?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE . '&tab=publish',
			'class'   => 'nav-tab',
			'caption' => esc_html__( 'Publish', 'wpglobus-plus' ),
		);
		
		/**
		 * Tab Menu.
		 *
		 * @since 1.2.7
		 */
		$this->tabs['menu'] = array(
			'href'    => '?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE . '&tab=menu',
			'class'   => 'nav-tab',
			'caption' => esc_html__( 'Menu', 'wpglobus-plus' ),
		);
		
		/**
		 * Tab Taxonomies.
		 *
		 * @since 1.1.33
		 */
		$this->tabs['taxonomies'] = array(
			'href'     => '?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE . '&tab=taxonomies',
			'class'    => 'nav-tab',
			'caption'  => esc_html__( 'Taxonomies', 'wpglobus-plus' ),
			'disabled' => version_compare( PHP_VERSION, WPGLOBUS_PLUS_TAXONOMIES_PHP_VERSION, '>=' ) ? false : true,
		);

		/**
		 * Tab Yoast SEO Plus.
		 *
		 * @since 1.1.48
		 */
		$this->tabs['wpseo'] = array(
			'href'    => '?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE . '&tab=wpseo',
			'class'   => 'nav-tab',
			'caption' => esc_html__( 'Yoast SEO Plus', 'wpglobus-plus' ),
		);

		/**
		 * Tab Pods.
		 * 
		 * @since 1.3.0 @W.I.P
		 */
		/* 
		$this->tabs['pods'] = array(
			'href'    => '?page=' . self::WPGLOBUS_PLUS_OPTIONS_PAGE . '&tab=pods',
			'class'   => 'nav-tab',
			'caption' => esc_html__( 'Pods Plus', 'wpglobus-plus' ),
		); 
		// */

		/**
		 * Tab Menu Settings
		 *
		 * @since 1.1.17
		 */
		/**
		 * $this->tabs['menu_settings'] = array(
		 * 'href'    => '?page=wpglobus-menu-settings',
		 * 'class' => 'nav-tab',
		 * 'caption' => esc_html__( 'Menu Settings', 'wpglobus-plus' )
		 * );    */
	}
	
} // class

# --- EOF
