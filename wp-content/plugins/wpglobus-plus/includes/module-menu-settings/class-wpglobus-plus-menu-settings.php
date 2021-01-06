<?php
/**
 * Class WPGlobusPlus_Menu_Settings.
 *
 * @package    WPGlobus
 * @since      1.1.17
 */

if ( ! class_exists( 'WPGlobusPlus_Menu_Settings' ) ) :

	/**
	 * Class WPGlobusPlus_Menu_Settings
	 */
	class WPGlobusPlus_Menu_Settings {

		/**
		 * Menu slug constant.
		 */
		const MENU_SLUG = 'wpglobus-plus-menu-settings';

		/**
		 * Current menu location.
		 *
		 * @var string
		 */
		public static $current_menu_location = '';

		/**
		 * Menu location by default set to first location.
		 *
		 * @var string
		 */
		public static $default_menu_location = '';

		/**
		 * Menu ID.
		 *
		 * @var int
		 */
		public static $default_menu_id = 0;

		/**
		 * All navigation menu objects.
		 *
		 * @var array
		 */
		public static $nav_menus = array();

		/**
		 * All registered navigation menu locations in a theme.
		 *
		 * @var array
		 */
		public static $locations = array();

		/**
		 * An array with the registered navigation menu locations and the menu assigned to it.
		 *
		 * @var array
		 */
		public static $menu_locations = array();

		/**
		 * An array of options from options table.
		 *
		 * @var array
		 */
		public static $options = array();

		/**
		 * A piece of option key.
		 *
		 * @var string
		 */
		public static $option_key = '_wpglobus_plus_location_';

		/**
		 * An array to store relationship child to parent.
		 * Which menu must be loaded for extra language according to default language's menu.
		 *
		 * @var string[]
		 */
		public static $child_parent = array();

		/**
		 * @var bool $_SCRIPT_DEBUG Internal representation of the define('SCRIPT_DEBUG')
		 * @todo Remove.
		 */
		protected static $_SCRIPT_DEBUG = false; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * @var string $_SCRIPT_SUFFIX Whether to use minimized or full versions of JS and CSS.
		 * @todo Remove.
		 */
		protected static $_SCRIPT_SUFFIX = '.min'; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * Constructor.
		 */
		public static function controller() {

			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
				self::$_SCRIPT_DEBUG  = true;
				self::$_SCRIPT_SUFFIX = '';
			}

			self::$nav_menus = wp_get_nav_menus();

			if ( is_admin() ) {

				add_action( 'admin_menu', array( __CLASS__, 'action__admin_menu' ) );

				// Enqueue the CSS & JS scripts.
				add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );

				add_filter( 'wpglobus_enabled_pages', array( __CLASS__, 'filter__enable_page' ) );

				// AJAX handlers.
				add_action( 'wp_ajax_' . __CLASS__ . '_process_ajax', array( __CLASS__, 'ajax_events' ) );

			} else {

				/**
				 * Get existing menu locations assignments.
				 * revised @since 1.1.55
				 */
				if ( defined( 'WPGLOBUS_PLUS_GET_NAV_MENU_LOCATIONS' ) && ! WPGLOBUS_PLUS_GET_NAV_MENU_LOCATIONS ) {
					/**
					 * In single case we got:
					 * PHP Fatal error:  Call to a member function get_queried_object() on null wp-includes/query.php on line 44
					 * So, we are not using `get_nav_menu_locations()` function.
					 * And add `define( 'WPGLOBUS_PLUS_GET_NAV_MENU_LOCATIONS', false );` to wp-config.php file.
					 */
					$theme_mods = get_theme_mods();
					if ( ! empty( $theme_mods['nav_menu_locations'] ) ) {
						self::$menu_locations = $theme_mods['nav_menu_locations'];
					}
				} else {
					self::$menu_locations = get_nav_menu_locations();
				}

				add_filter( 'wp_nav_menu_args', array( __CLASS__, 'filter__nav_menu_args' ) );

				add_filter( 'wpglobus_menu_add_selector', array( __CLASS__, 'filter__add_selector' ), 10, 2 );
			}
		}


		/**
		 * Add appropriate selector.
		 *
		 * @param bool     $disable_add_selector Disable or not to add language selector to the menu.
		 * @param stdClass $args                 An object containing wp_nav_menu() arguments.
		 *
		 * @return bool
		 */
		public static function filter__add_selector( $disable_add_selector, $args ) {

			if ( WPGlobus::Config()->language === WPGlobus::Config()->default_language ) {
				return $disable_add_selector;
			}

			$opt = self::get_options( $args->theme_location, WPGlobus::Config()->language );

			if ( empty( $opt ) ) {
				return $disable_add_selector;
			}

			if ( 'all' === WPGlobus::Config()->nav_menu ) {
				return $disable_add_selector;
			}

			if ( ! empty( self::$child_parent[ $opt ] ) ) {

				if ( WPGlobus::Config()->nav_menu !== self::$child_parent[ $opt ] ) {

					$disable_add_selector = true;

				} else {

					if ( is_object( $args->menu ) ) {

						if ( (int) $opt === (int) $args->menu->term_id ) {
							$disable_add_selector = false;
						}
					} elseif ( is_string( $args->menu ) ) {

						/** @noinspection NestedPositiveIfStatementsInspection */
						if ( (int) $opt === (int) $args->menu ) {
							$disable_add_selector = false;
						}
					}
				}
			}

			return $disable_add_selector;

		}

		/**
		 * Change menu according to the option for extra language.
		 *
		 * @param array $args Array of wp_nav_menu() arguments.
		 *
		 * @return array Updated `$args`.
		 */
		public static function filter__nav_menu_args( $args ) {

			if ( WPGlobus::Config()->language === WPGlobus::Config()->default_language ) {
				return $args;
			}

			$menu_id = get_option( self::$option_key . $args['theme_location'] . '_' . WPGlobus::Config()->language );

			if ( empty( $menu_id ) ) {
				return $args;
			}

			$new_menu = '';
			foreach ( self::$nav_menus as $menu ) {
				if ( (int) $menu->term_id === (int) $menu_id ) {
					$menu_obj                       = get_term( self::$menu_locations[ $args['theme_location'] ], 'nav_menu' );
					self::$child_parent[ $menu_id ] = $menu_obj->slug;
					$new_menu                       = $menu;
					break;
				}
			}

			if ( ! empty( $new_menu ) ) {
				$args['menu'] = $new_menu;
			}

			return $args;

		}

		/**
		 * Ajax events handler.
		 *
		 * @param array $enabled_pages Unused.
		 */
		public static function ajax_events(
			/** @noinspection PhpUnusedParameterInspection */
			$enabled_pages
		) {

			$order = $_POST['order']; // phpcs:ignore WordPress.Security.NonceVerification.Missing

			switch ( $order['action'] ) {
				case 'save':
					foreach ( WPGlobus::Config()->enabled_languages as $language ) {

						if ( WPGlobus::Config()->default_language === $language ) {
							continue;
						}

						$option = self::$option_key . $order['location'] . '_' . $language;

						if ( empty( $order['menuID'][ $language ] ) ) {
							delete_option( $option );
						} else {
							update_option( $option, $order['menuID'][ $language ], true );
						}
					}

					break;
			}

			die();

		}

		/**
		 * Add page to load JS.
		 *
		 * @param array $enabled_pages Enabled pages.
		 *
		 * @return array Possibly modified input var.
		 */
		public static function filter__enable_page( $enabled_pages ) {
			/**
			 * Don't use 'admin.php' directly in array $enabled_pages to avoid interfere with other plugins.
			 * You need to use value of query var 'page' instead. ( e.g. 'admin.php?page=wpglobus-plus-menu-settings' )
			 */
			$enabled_pages[] = self::MENU_SLUG;

			return $enabled_pages;
		}

		/**
		 * Add hidden submenu
		 */
		public static function action__admin_menu() {

			//			@todo (use WPGlobus_WP method).
			//			global $pagenow;
			//			if ( 'nav-menus.php' === $pagenow ) {
			//			}

			// Get existing menu locations assignments
			self::$locations      = get_registered_nav_menus();
			self::$menu_locations = get_nav_menu_locations();

			if ( empty( self::$locations ) ) {
				self::$default_menu_location = '';
			} else {
				$keys                        = array_keys( self::$locations );
				self::$default_menu_location = $keys[0];
			}

			if ( isset( $_GET['location'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				self::$current_menu_location = $_GET['location']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			} else {
				self::$current_menu_location = self::$default_menu_location;
			}

			add_submenu_page(
				null,
				'',
				'',
				'administrator',
				'wpglobus-plus-menu-settings',
				array( __CLASS__, 'content' )
			);

		}

		/**
		 * Echo content of page.
		 */
		public static function content() {

			?>
			<!-- Main content -->
			<div class="wrap">
				<h2><?php esc_html_e( 'WPGlobus Menu Settings', 'wpglobus-plus' ); ?></h2>
				<?php

				if ( empty( self::$nav_menus ) ) {
					?>
					<div class="notice notice-warning">
						<p>
							<?php
							/**
							 * Message copied from @see WP_Nav_Menu_Widget::form
							 */
							echo sprintf(
								__( 'No menus have been created yet. <a href="%s">Create some</a>.' ), //phpcs:ignore
								esc_url( admin_url( 'nav-menus.php' ) )
							);
							?>
						</p>
					</div>
					<?php
					echo '</div><!-- .wrap -->';

					return;
				}

				/**
				 * There are two methods:
				 *
				 * @see get_registered_nav_menus Tells if the theme has registered
				 *                               menus using the @see register_nav_menus
				 *                               method.
				 * @see get_nav_menu_locations Checks menu assignments in `theme_mods`.
				 *
				 * Because `theme_mods` might stay unchanged in the `options`,
				 * we have to check both methods.
				 */

				$registered_theme_locations = get_registered_nav_menus();

				if ( empty( $registered_theme_locations ) || empty( self::$menu_locations ) ) {
					?>
					<div class="notice notice-warning">
						<p><?php esc_html_e( 'Your theme does not natively support menus or does not register any Theme Location.', 'wpglobus-plus' ); ?></p>
					</div>
					<?php
					echo '</div><!-- .wrap -->';

					return;
				}

				?>
				<label for="wpglobus-theme-location"><strong><?php esc_html_e( 'Theme Location' ); ?>:</strong></label>
				<select id="wpglobus-theme-location"
						onchange="WPGlobusPlusMenuSettings.selectLocation( jQuery('#wpglobus-theme-location option:selected').val() );">
					<?php
					foreach ( self::$locations as $menu_location => $menu_title ) {
						$selected = ( self::$current_menu_location === $menu_location ) ? 'selected' : '';
						?>
						<option <?php echo $selected; // phpcs:ignore ?>
								value="<?php echo esc_attr( $menu_location ); ?>"><?php echo wp_kses_post( $menu_location . ' (' . $menu_title . ')' ); ?></option>
					<?php } ?>
				</select>

				<?php
				$output_table = false;
				foreach ( self::$menu_locations as $menu_location => $menu_id ) {
					if ( self::$current_menu_location !== $menu_location ) {
						continue;
					}
					if ( 0 === (int) $menu_id ) {
						continue;
					}
					$output_table = true;
				}
				if ( $output_table ) :
					?>
					<p><i class="dashicons-before dashicons-info"></i>
						<?php esc_html_e( 'With WPGlobus Plus, you can associate different menus with each language. Choose which menu should be displayed at this Theme Location - by clicking on the table cells.', 'wpglobus-plus' ); ?>
					</p>
					<table class="widefat" style="margin: 1em 0">
						<thead>
						<tr>
							<th><?php esc_html_e( 'Menu' ); ?></th>
							<?php foreach ( WPGlobus::Config()->enabled_languages as $lang ) { ?>
								<th><?php echo esc_html( WPGlobus::Config()->en_language_name[ $lang ] ); ?></th>
							<?php } ?></tr>
						</thead>
						<tbody>
						<?php
						foreach ( self::$nav_menus as $menu ) :

							$_location = '';
							foreach ( self::$menu_locations as $menu_location => $menu_id ) :

								if ( self::$current_menu_location !== $menu_location ) {
									continue;
								}

								if ( $menu_id === $menu->term_id ) {

									self::$default_menu_id = $menu->term_id;

									/**
									 * This location for default language
									 */
									$_location = isset( self::$menu_locations[ $menu_location ] ) ? '&nbsp;(<b>' . self::$locations[ $menu_location ] . '</b>)' : '';

								}

							endforeach;
							$title                           = __( 'Click to select', 'wpglobus-plus' );
							$style                           = array();
							$style['cells-background-color'] = 'background-color:#e1e1e1';
							?>
							<tr>
								<?php // @formatter:off ?>
								<td style="<?php echo esc_attr( $style['cells-background-color'] ); ?>">
									<a target="_blank" href="<?php echo esc_url( admin_url() . 'nav-menus.php?action=edit&menu=' . $menu->term_id ); ?>"><?php echo wp_html_excerpt( $menu->name, 40, '&hellip;' ) . $_location; //phpcs:ignore ?></a>
								</td>
								<?php
								// @formatter:on
								foreach ( WPGlobus::Config()->enabled_languages as $lang ) {

									$cell_classes   = array();
									$cell_classes[] = 'cell-selector';
									$cell_title     = '';

									if ( WPGlobus::Config()->default_language !== $lang ) {

										if ( ! empty( $_location ) ) {
											$style['background-color'] = 'background-color: #0f0';
										}

										$cell_classes[] = 'cell-selector-enabled';
										$cell_classes[] = self::$current_menu_location . '-' . $lang;
										$style[]        = 'cursor:pointer';
										$cell_title     = $title;

									}

									$cell_style = $style;
									?>
									<td title="<?php echo esc_attr( $cell_title ); ?>"
											id="<?php echo esc_attr( self::$current_menu_location . '-' . $lang . '-' . $menu->term_id ); ?>"
											class="<?php echo esc_attr( implode( ' ', $cell_classes ) ); ?>"
											data-language="<?php echo esc_attr( $lang ); ?>"
											data-menu-id="<?php echo esc_attr( $menu->term_id ); ?>"
											data-current-menu-location="<?php echo esc_attr( self::$current_menu_location ); ?>"
											style="<?php echo esc_attr( implode( ';', $cell_style ) ); ?>">
										<?php echo wp_kses_post( $_location ); ?>
									</td>
								<?php } ?>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>

					<form id="wpglobus-plus-menu-setting-form">

						<input id="wpglobus_plus_menu_location" name="wpglobus_plus_menu_location"
								value="<?php echo esc_attr( self::$current_menu_location ); ?>" type="hidden"/>

						<input id="wpglobus_plus_menu_id_<?php echo esc_attr( WPGlobus::Config()->default_language ); ?>"
								name="wpglobus_plus_menu_id_<?php echo esc_attr( WPGlobus::Config()->default_language ); ?>"
								value="<?php echo esc_attr( self::$default_menu_id ); ?>"
								type="hidden"/>

						<?php foreach ( WPGlobus::Config()->enabled_languages as $lang ) : ?>
							<?php
							if ( WPGlobus::Config()->default_language !== $lang ) {

								$value = '';
								if ( ! empty( self::$options[ self::$current_menu_location ] ) &&
									 ! empty( self::$options[ self::$current_menu_location ][ $lang ] )
								) {
									$value = self::$options[ self::$current_menu_location ][ $lang ];
								}
								?>
								<input id="wpglobus_plus_menu_id_<?php echo esc_attr( $lang ); ?>"
										name="wpglobus_plus_menu_id_<?php echo esc_attr( $lang ); ?>"
										value="<?php echo esc_attr( $value ); ?>"
										type="hidden"/>
							<?php } ?>
						<?php endforeach; ?>

						<button id="wpglobus_menu_settings_save" type="button" class="button-primary" value="save"
								formnovalidate><?php esc_html_e( 'Save Changes' ); ?></button>
					</form>
					<div class="notice">
						<p>
							<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'Edit Menus' ); ?></a>
							|
							<a href="<?php echo esc_url( admin_url( 'nav-menus.php?action=locations' ) ); ?>"><?php esc_html_e( 'Manage Locations' ); ?></a>
						</p>
					</div>
				<?php else : ?>
					<div class="notice notice-warning">
						<p>
							<?php esc_html_e( 'You need to assign a menu for this Theme Location.', 'wpglobus-plus' ); ?>
							<a href="<?php echo esc_url( admin_url( 'nav-menus.php?action=locations' ) ); ?>"><?php esc_html_e( 'Manage Locations' ); ?></a>
						</p>
					</div>
				<?php endif; ?>
			</div>    <!-- .wrap -->
			<?php

		}

		/**
		 * Get options from options table.
		 *
		 * @param string $location Theme location.
		 * @param string $language Current language.
		 *
		 * @return string|null
		 */
		public static function get_options( $location = '', $language = '' ) {

			global $wpdb;

			$return_option = false;

			/**
			 * Load options
			 */
			if ( '' !== $location && '' !== $language ) {
				$key           = self::$option_key . $location . '_' . $language;
				$return_option = true;
			} else {
				$key = self::$option_key . self::$current_menu_location . '_';
			}

			$query = "SELECT * FROM $wpdb->options WHERE $wpdb->options.option_name LIKE '%{$key}%'";

			$res = $wpdb->get_results( $query ); //phpcs:ignore

			if ( $return_option ) {

				if ( ! empty( $res[0] ) ) {
					return $res[0]->option_value;
				}
			} else {

				foreach ( $res as $opt ) {
					$on = str_replace( $key, '', $opt->option_name );

					self::$options[ self::$current_menu_location ][ $on ] = $opt->option_value;
				}
			}

			return null;

		}

		/**
		 * Enqueue admin JS scripts.
		 *
		 * @param string $hook_page The current admin page.
		 */
		public static function enqueue_scripts( $hook_page ) {

			global $pagenow;

			if ( 'admin_page_' . self::MENU_SLUG === $hook_page || 'nav-menus.php' === $pagenow ) {

				self::get_options();

				wp_register_script(
					'wpglobus-plus-menu-settings',
					WPGlobusPlus_Asset::url_js( 'wpglobus-plus-menu-settings' ),
					array( 'wpglobus-admin' ),
					WPGLOBUS_PLUS_VERSION,
					true
				);
				wp_enqueue_script( 'wpglobus-plus-menu-settings' );
				wp_localize_script(
					'wpglobus-plus-menu-settings',
					'WPGlobusPlusMenuSettings',
					array(
						'version'             => WPGLOBUS_PLUS_VERSION,
						'pagenow'             => $pagenow,
						'hook_page'           => $hook_page,
						'redirect'            => admin_url( add_query_arg( array( 'page' => self::MENU_SLUG ), 'admin.php' ) ),
						'locations'           => self::$locations,
						'menu_locations'      => array_intersect_key( self::$menu_locations, self::$locations ),
						'process_ajax'        => __CLASS__ . '_process_ajax',
						'options'             => self::$options,
						'currentMenuLocation' => self::$current_menu_location,
					)
				);

			}
		}

	}

endif;
