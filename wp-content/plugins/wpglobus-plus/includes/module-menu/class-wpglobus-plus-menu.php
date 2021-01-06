<?php
/**
 * @package WPGlobusPlus_Menu
 *
 * @since 1.0.0
 */

if ( ! class_exists( 'WPGlobusPlus_Menu' ) ) :

	/**
	 * Class WPGlobusPlus_Menu
	 */
	class WPGlobusPlus_Menu {

		/** */
		public function __construct() {

			if ( is_admin() ) {

				add_filter(
					'wpglobus_option_sections',
					array(
						__CLASS__,
						'add_option'
					), 10
				);

			} else {

				add_filter(
					'wpglobus_dropdown_menu',
					array(
						__CLASS__,
						'on_dropdown_menu'
					), 10, 2
				);

			}

		}

		/**
		 * Add option to section
		 *
		 * @since 1.0.0
		 * @param array $sections
		 * @return array
		 */
		public static function add_option( $sections ) {

			foreach ( $sections as $key => $section ) :

				if ( 'languages' === $section['wpglobus_id'] ) {

					$field_key = null;
					foreach ( $section['fields'] as $f_key => $field ) {
						if ( 'selector_wp_list_pages' === $field['id'] ) {
							$field_key = $f_key;
							break;
						}
					}

					if ( null !== $field_key ) {
						if ( is_callable( array( 'WPGlobus_Options', 'field_switcher_menu_style' ) ) ) {
							/**
							 * New WPGlobus Options panel (since WPGlobus 1.9.10).
							 *
							 * @since 1.1.39
							 */
							$field = array( WPGlobus_Options::field_switcher_menu_style() );
						} else {
							// For WPGlobus 1.9.9 and before.
							$field = array(
								array(
									'id'          => 'switcher_menu_style',
									'type'        => 'wpglobus_select',
									'title'       => esc_html__(
										'Language Selector Menu Style', 'wpglobus-plus' ),
									'subtitle'    => '',
									'desc'        => esc_html__(
										'Drop-down languages menu or Flat (in one line)', 'wpglobus-plus' ),
									'placeholder' => esc_html__(
										'Do not change', 'wpglobus-plus' ),
									'select2'     => array(
										'allowClear' => true
									),
									'compiler'    => false,
									'style'       => '',
									'options'     => array(
										'dropdown' => esc_html__( 'Drop-down (vertical)', 'wpglobus-plus' ),
										'flat'     => esc_html__( 'Flat (horizontal)', 'wpglobus-plus' ),
									)
								)
							);
						}

						array_splice( $sections[ $key ]['fields'], $field_key + 1, 0, $field );

						break;

					}

				}

			endforeach;

			return $sections;

		}

		/**
		 * Show language switcher menu as a dropdown or flat.
		 *
		 * @since 1.0.0
		 * @param bool            $dropdown
		 * @param WPGlobus_Config $config
		 * @return bool
		 */
		public static function on_dropdown_menu( $dropdown, $config ) {

			if ( ! empty( $config->extended_options['switcher_menu_style'] ) ) {
				switch ( $config->extended_options['switcher_menu_style'] ) {
					case 'dropdown':
						$dropdown = true;
						break;
					case 'flat':
						$dropdown = false;
						break;
				}
			}

			return $dropdown;

		}

	} // WPGlobusPlus_Menu

endif;

# --- EOF
