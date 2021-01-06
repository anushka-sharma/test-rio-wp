<?php
/**
 * Filters the ACF field $value after it has been loaded.
 *
 * @since 1.3.5
 *
 * @param mixed  $value The value to preview.
 * @param string $post_id The post ID for this value.
 * @param array  $field The field array.
 */
function filter__wpglobus_plus_acf_load_value($value, $post_id, $field) {

	if ( 'table' == $field['type'] ) {
		/**
		 * @see https://wordpress.org/plugins/advanced-custom-fields-table-field/
		 *
		 * How to output the table html?
		 * @see https://wordpress.org/plugins/advanced-custom-fields-table-field/#how%20to%20output%20the%20table%20html%3F
		 */
		if ( is_string($value) && WPGlobus_Core::has_translations($value) ) {
			$value = WPGlobus_Core::text_filter( $value, WPGlobus::Config()->language );
			$value = maybe_unserialize( $value );
		}
	}	
	
	return $value;
}
/**
 * @see advanced-custom-fields\includes\acf-value-functions.php
 */
add_filter( 'acf/load_value', 'filter__wpglobus_plus_acf_load_value', 5, 3 );

/**
 * Activate wysiwyg field.
 * @since 1.1.42
 *
 * @param boolean $value
 * @return boolean
 */
function wpglobus_plus_acf_field_wysiwyg($value) {
	return true;
}
add_filter( 'wpglobus/vendor/acf/field/wysiwyg', 'wpglobus_plus_acf_field_wysiwyg' );

/**
 * Activate table field.
 * @see https://wordpress.org/plugins/advanced-custom-fields-table-field/
 * @since 1.1.55
 *
 * @param boolean $value
 * @return boolean
 */
function wpglobus_plus_acf_field_table($value) {

	static $_value = null;
	
	if ( is_null($_value) ) {
		/**
		 * @see wpglobus-plus\includes\wpglobus-plus-main.php to get $option_key.
		 */
		$option_key = 'wpglobus_plus_options'; 
		$option 	= (array) get_option($option_key);

		$_value = true;
		if ( isset($option['acf']['active_status']) && ! $option['acf']['active_status'] ) {
			$_value = false;
		}
	}

	return $_value;
}
/**
 * @see wpglobus\includes\vendor\acf\class-wpglobus-acf.php
 */
add_filter( 'wpglobus/vendor/acf/field/table', 'wpglobus_plus_acf_field_table' );

/**
 * Disable/enable to filter meta field. Case when WPGlobus Plus is active but module ACF Plus is deactivated.
 *
 * @see wpglobus\includes\admin\meta\class-wpglobus-meta.php
 * @since 1.1.45
 *
 * @param string $meta_key Meta key.
 *
 * @return string|boolean Meta key or false if module ACF Plus is deactivated.	
 */
function wpglobus_plus_acf_field_wysiwyg_status($meta_key) {
	
	if ( class_exists('WPGlobusPlus_Acf') ) {
		/**
		 * ACF Plus is active.
		 */
		return $meta_key;
	}
	
	global $post;
	
	if ( class_exists('WPGlobus_Acf_2') ) {
		$fields = WPGlobus_Acf_2::get_acf_fields($post->ID);
		if ( ! empty($fields[$meta_key]) && $fields[$meta_key]['type'] == 'wysiwyg'  ) {
			return false;
		}
	}
	
	return $meta_key;
}
add_filter( 'wpglobus/meta/key', 'wpglobus_plus_acf_field_wysiwyg_status' );


# --- EOF
