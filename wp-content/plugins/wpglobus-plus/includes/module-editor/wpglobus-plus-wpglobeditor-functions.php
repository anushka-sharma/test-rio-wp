<?php
/**
 * File: wpglobus-plus-wpglobeditor-functions.php
 *
 * @since 1.2.7
 *
 * @package WPGlobus-Plus
 */
/**
 * Filter vendor's config.
 *
 * @since 1.2.7
 *
 * @param array  $config  Config.
 * @param object $builder An object WPGlobus_Config_Builder.
 *
 * @return array
 */
add_filter( 'wpglobus_config_vendors', 'filter__wpglobus_plus_config_vendors', 10, 2);
function filter__wpglobus_plus_config_vendors($config, $builder){

	if ( ! $builder->is_builder_page() ) {
		return $config;
	}
	
	/**
	 * @see $option_key in wpglobus-plus\includes\module-editor\class-wpglobus-plus-wpglobeditor.php
	 */
	$options = get_option('wpglobus_plus_wpglobeditor');

	if ( empty($options) || empty($options['page_list']) ) {
		return $config;
	}
	
	$_meta_fields = array();
	
	foreach( $options['page_list'] as $page=>$metas ) {
		foreach( $metas as $meta ) {
			$_meta_fields[$meta] = array();
		}
	}
	
	if ( ! empty($_meta_fields) ) {
		$config['wpglobus-plus'] = array(
			'post_meta_fields' 	=> $_meta_fields,
			'post_ml_fields' 	=> $_meta_fields
		);
	}
	
	return $config;
}
# --- EOF