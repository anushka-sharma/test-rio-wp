<?php
/**
 * Class WPGlobusPlusSlug_Acf
 * @since 1.0.0
 */

if ( ! class_exists( 'WPGlobusPlusSlug_Acf' ) ) :

	class WPGlobusPlusSlug_Acf {

		public function __construct() {

			/**
			 * Hide slug box accordingly with ACF option
			 */
			add_filter(
				'acf/field_group/get_options',
				array(
					'WPGlobusPlusSlug_Acf',
					'filter__acf_get_options'
				), 99, 2
			);

		}

		/**
		 * Filter @see 'acf/field_group/get_options'
		 *
		 * @since 1.0.0
		 * @param array $options
		 * @param int $acf_id
		 * @return array
		 */
		public static function filter__acf_get_options( $options, $acf_id ){
			if( in_array( 'permalink', $options['hide_on_screen'] ) ) {
				add_filter(
					'wpglobus_plus_slug_box_style',
					array(
						'WPGlobusPlusSlug_Acf',
						'filter__slug_box_style'
					), 10, 2
				);
			}
			return $options;
		}

		/**
		 * Filter slug box style for extra language
		 *
		 * @since 1.0.0
		 * @param string $style
		 * @param        $language
		 * @return string
		 */
		public static function filter__slug_box_style( $style, $language ){
			return $style . 'display:none;';
		}

	} // WPGlobusPlusSlug_Acf

endif;
