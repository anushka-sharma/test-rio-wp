<?php
/**
 * File rewrite_rules.php
 * @since 1.3.11
 *
 * @package WPGlobus Plus
 * @module Taxonomies.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h3>Rewrite rules</h3>
<?php
$opts  = get_option($this->option_key);
$rules = get_option('rewrite_rules');

if ( empty( $opts ) ) {
	
	esc_html_e( 'Module options is empty.', 'wpglobus-plus' );

} else {
	
	$extra_languages = WPGlobus::Config()->enabled_languages;
	unset($extra_languages[0]);
	
	$show = array('post_type', 'taxonomy');
	
	foreach( $opts as $option_key=>$option_data ) {
		
		if ( ! in_array($option_key, $show ) ) {
			continue;
		}

		switch ( $option_key ) :
		
			case 'post_type' :
			
				foreach( $option_data as $post_type=>$multilingual_slug ) :	?>
					<div style="margin-left:20px;width:120%;">
						<h4 class="wpglobus-plus-taxonomies-title" data-id="<?php echo $post_type; ?>">Post type: <a href="#"><?php echo $post_type; ?></a></h4>
						<div style="margin-left:20px;" class="wpglobus-plus-taxonomies-rewrite_rules-data wpglobus-plus-taxonomies-rewrite_rules-data-<?php echo $post_type; ?> hidden"><?php
						
							echo '<b>Language: '.WPGlobus::Config()->default_language.'</b><br />';   	?>
							<div style="margin-left:20px;">	<?php		
								foreach( $rules as $rule_key=>$rule ) {
									if ( false !== strpos( $rule_key,  $post_type ) && false !== strpos( $rule, 'post_type' ) )  {
										echo $rule_key . '  =>  <b>' . $rule . '</b><br />';
									}	
								}	?>
							</div>	<?php	
							echo '<hr />';
							
							foreach( $extra_languages as $language ) :
								echo '<b>Language: '.$language.'</b><br />';   	?>
								<div style="margin-left:20px;">	<?php						
									foreach( $rules as $rule_key=>$rule ) {
										$_slug = WPGlobus_Core::text_filter( $multilingual_slug, $language, WPGlobus::RETURN_EMPTY );
										if ( ! empty($_slug) ) {
											if ( false !== strpos( $rule_key,  $_slug ) && false !== strpos( $rule, 'post_type' ) )  {
												echo $rule_key . '  =>  <b>' . $rule . '</b><br />';
											}										
										}
									}	?>
								</div>	<?php	
								echo '<hr />';
							endforeach;	?>
						</div>		
					</div>		<?php
				endforeach;
				
				break;
					
			case 'taxonomy' :
			
				foreach( $option_data as $taxonomy=>$taxonomy_data ) :
					
					$search_taxonomy_left = $search_taxonomy_right = $taxonomy;
					
					if ( $taxonomy == 'post_tag' ) {
						$search_taxonomy_left	 = 'tag';
						$search_taxonomy_right 	 = 'tag';
					} else if( $taxonomy == 'category' ) {
						$search_taxonomy_left 	 = 'category';	
						$search_taxonomy_right 	 = 'category_name';	
					}

					?>
					<div style="margin-left:20px;width:120%;">
						<h4 class="wpglobus-plus-taxonomies-title" data-id="<?php echo $taxonomy; ?>">Taxonomy: <a href="#"><?php echo $taxonomy; ?></a></h4>
						<div style="margin-left:20px;" class="wpglobus-plus-taxonomies-rewrite_rules-data wpglobus-plus-taxonomies-rewrite_rules-data-<?php echo $taxonomy; ?> hidden"><?php
						
							echo '<b>Language: '.WPGlobus::Config()->default_language.'</b><br />';   	?>
							<div style="margin-left:20px;">	<?php
								$indx = 0;
								foreach( $rules as $rule_key=>$rule ) {
									// if ( false !== strpos( $rule_key,  $search_taxonomy . '/' ) && false !== strpos( $rule, $search_taxonomy ) )  {
									if ( false !== strpos( $rule_key,  $search_taxonomy_left . '/' ) && false !== strpos( $rule, $search_taxonomy_right ) )  {
										echo $indx+1 . ') ' . $rule_key . '  =>   <b>' . $rule . '</b><br />';
										$indx++;
									}	
								}	?>
							</div>	<?php
							echo '<hr />';								
							foreach( $extra_languages as $language ) :
								$indx = 0;
								echo '<b>Language: '.$language.'</b><br />';   	?>
								<div style="margin-left:20px;">	<?php	
									if ( ! empty($taxonomy_data['slug']) ) {
									
										foreach( $rules as $rule_key=>$rule ) {
											$_slug = WPGlobus_Core::text_filter( $taxonomy_data['slug'], $language, WPGlobus::RETURN_EMPTY );
											if ( ! empty($_slug) ) {
												if ( false !== strpos( $rule_key,  $_slug . '/' ) )  {
													echo $indx+1 . ') ' . $rule_key . '  =>   <b>' . $rule . '</b><br />';
													$indx++;
												}
											}
										}
										echo '============<br />';
									
									};	
									$i=0;
									
									if ( ! empty($taxonomy_data['term_slug']) ) {
									
										foreach( $taxonomy_data['term_slug'] as $_term_id=>$_term_slug ) :
											foreach( $rules as $rule_key=>$rule ) :
												$_slug = WPGlobus_Core::text_filter( $_term_slug, $language, WPGlobus::RETURN_EMPTY );
												if ( ! empty($_slug) ) {
													if ( 0 === strpos( $rule_key,  $_slug . '/' ) )  {
														echo $rule_key . '  =>  <b>' . $rule . '</b><br />';
													}
												}
												$i++;
											endforeach;
											$i++;
										endforeach;	
										echo '======== iterations: '.$i.' ====<br />';
										
									};	
									?>
								</div>	<?php	
							echo '<hr />';	
							endforeach;
							?>
						</div>	
					</div>		<?php
					
				endforeach;
		
				break;
			default:	
		endswitch;
		
	} // foreach.
}

# --- EOF