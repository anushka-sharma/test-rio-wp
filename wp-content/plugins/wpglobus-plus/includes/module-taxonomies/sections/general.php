<?php
/**
 * File general.php.
 * @since 1.3.7
 *
 * @package WPGlobus Plus
 * @module Taxonomies.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wpglobus-plus-taxonomies-general-wrap">
	<div class="wpglobus-plus-taxonomies-general-sidebar wpglobus-plus-taxonomies-general-wrap__item">
		<ul>
			<?php foreach ( $wp_post_types as $post_type => $post_type_obj ) {
				if ( ! $this->is_post_type_enabled( $post_type, $post_type_obj ) ) {
					continue;
				}
				?>
				<li data-post-type="<?php echo $post_type; ?>" class="tab-post-type-<?php echo $post_type; ?>">
					<a id="post-type-<?php echo $post_type; ?>" href="#tab-<?php echo $post_type; ?>"><?php echo $post_type_obj->labels->singular_name; ?></a>
				</li>
			<?php } ?>
		</ul>
	</div><!-- .wpglobus-plus-taxonomies-general-wrap__item -->	
	<div class="wpglobus-plus-taxonomies-general-main wpglobus-plus-taxonomies-general-wrap__item">
		<p style="margin-bottom:10px;font-weight: bold;">
			<?php esc_html_e( 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' ); ?>
		</p>
		<?php
		$slug_label = esc_html__( '%s slug', 'wpglobus-plus' );
		$separator  = '<span style="display:inline-block; width: 3em;"></span>';
		foreach ( $wp_post_types as $post_type => $post_type_obj ) {

			if ( ! $this->is_post_type_enabled( $post_type, $post_type_obj ) ) {
				continue;
			}
			
			$enable_taxonomies = true;
			if ( defined('WOOCOMMERCE_VERSION') ) {
				/**
				 * Don't provide here the support for Woocommerce taxonomies by default.
				 */
				if ( in_array($post_type, array('product')) ) {
					/**
					 * Filter to enable/disable the handling of taxonomies.
					 *
					 * The dynamic portion of the filter name, `$post_type`, refers to the post type.
					 *
					 * @since 1.2.8
					 *
					 * @param false Value by default.
					 */
					$enable_taxonomies = apply_filters( "wpglobus_plus_handling_{$post_type}_taxonomies", false );
				}
			}
			
			/**
			 * Filter to enable/disable the handling of taxonomies.
			 *
			 * @since 1.2.8
			 *
			 * @param boolean $enable_taxonomies Enable/Disable the handling of taxonomies.
			 * @param string  $post_type  		 The post type slug.
			 */				
			$enable_taxonomies = apply_filters( 'wpglobus_plus_handling_taxonomies', $enable_taxonomies, $post_type );
			?>
			<div id="tab-<?php echo esc_attr( $post_type ); ?>" class="post-type-tab">
				<h3><?php
					esc_html_e( 'Post Type label:', 'wpglobus-plus' );
					?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $post_type ) ); ?>" target="_blank"><?php echo esc_html( $post_type_obj->labels->singular_name ); ?></a>
				</h3>
				<?php if ( 'post' !== $post_type ) { ?>
					<div style="clear:both;">
						<h4><?php
							esc_html_e( 'Post Type name:', 'wpglobus-plus' );
							echo $separator;
							echo esc_html( $post_type );
							?></h4>
						<?php
						/**
						 * Post type slug for default language.
						 */
						$post_type_default = $post_type;
						/**
						 * Rewrite for Woocommerce product.
						 *   [rewrite] => Array
						 *		(
						 *			[slug] => /produkt
						 *			[with_front] => 
						 *			[feeds] => 1
						 *			[pages] => 1
						 *			[ep_mask] => 1
						 *		)
						 */
						//if ( ! empty( $post_type_obj->rewrite ) && $post_type_obj->rewrite['with_front'] ) {
						if ( ! empty( $post_type_obj->rewrite ) ) {
							$post_type_default = str_replace( '/', '', $post_type_obj->rewrite['slug'] );
						}
						?>
						<div style="clear:both;height: 35px;">
							<div style="float:left;width:150px;padding-top:4px;" class="language-name">
								<?php
								echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ WPGlobus::Config()->default_language ] ) ); ?>
							</div>
							<div style="float:left">
								<input disabled
									   type="text"
									   size="30"
									   value="<?php echo esc_attr( $post_type_default ); ?>"
									   class="wpglobus-translatable"
									   data-language="<?php echo esc_attr( WPGlobus::Config()->default_language ); ?>"
									   title="" />
								<span 
									class="wpglobus-plus-taxonomies-help dashicons dashicons-editor-help"
									title="<?php esc_attr_e( 'To edit the slug for the default language, please use the Custom Rewrite Slug parameter in the settings of the plugin that created this Custom Post Type.', 'wpglobus-plus' ); ?>" style="height:10px;width:10px;">
								</span>
							</div>
						</div>
						<?php
						/** 
						 * Init post type's slug source.
						 */
						$source = '';
						if ( ! empty( $opts['post_type'][ $post_type ] ) ) {
							$source = $opts['post_type'][ $post_type ];
						}
						foreach ( $this->extra_languages as $language ) {
							if ( '' == WPGlobus_Core::text_filter($source, $language) ) {
								$source .= WPGlobus::add_locale_marks( esc_attr( urldecode( $post_type_default ) ), $language );
							}
						}
						
						/**
						 * Post type slug for extra languages.
						 */
						foreach ( $this->extra_languages as $language ) :
							$slug = '';
							if ( ! empty( $source ) ) {
								$slug = WPGlobus_Core::extract_text( $source, $language );
							}
							?>
							<div style="clear:both;height: 35px;">
								<div style="float:left;width:150px;padding-top:4px;" class="language-name">
									<?php echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ $language ] ) ); ?>
								</div>
								<div style="float:left;">
									<input type="text"
										   size="30"
										   value="<?php echo esc_attr( $slug ); ?>"
										   class="wpglobus-translatable wpglobus-plus-taxonomy-field"
										   data-language="<?php echo esc_attr( $language ); ?>"
										   data-post-type="<?php echo esc_attr( $post_type ); ?>"
										   data-source-id="#wpglobus-source-<?php echo esc_attr( $post_type ); ?>" 
										   data-slug-default="<?php echo esc_attr( urldecode( $post_type_default ) ); ?>" title="" />
								</div>
							</div>
						<?php endforeach; ?>
						<?php
						/**
						 * Hidden field for post type.
						 */
						?>
						<input type="text"
							   id="wpglobus-source-<?php echo esc_attr( $post_type ); ?>"
							   value="<?php echo esc_attr( $source ); ?>"
							   size="80"
							   class="wpglobus-taxonomy-source hidden"
							   data-post-type="<?php echo esc_attr( $post_type ); ?>" title="" />
					</div>
				<?php } ?>
				<?php
				if ( $enable_taxonomies ) : ?>
					<div class="taxonomies" style="clear:both;">
						<?php
						/**
						 * Taxonomy.
						 * @global array $wp_taxonomies .
						 */
						foreach ( $wp_taxonomies as $taxonomy => $taxonomy_data ) {

							if ( ! $this->is_taxonomy_enabled( $taxonomy, $taxonomy_data, $post_type ) ) {
								continue;
							}

							/**
							 * Get terms.
							 */
							$terms = get_terms( array(
								'taxonomy'   => $taxonomy,
								'hide_empty' => false,
							) );

							$real_taxonomy_slug = $taxonomy_data->query_var;

							if ( ! empty( $taxonomy_data->rewrite['slug'] ) ) {
								$real_taxonomy_slug = $taxonomy_data->rewrite['slug'];
							}
							?>
							<div class="taxonomy-box" style="border:1px #000 solid;border-radius:20px;margin-bottom:10px;padding:0 10px 20px 10px;">
								<h4><?php
									esc_html_e( 'Taxonomy', 'wpglobus-plus' );
									echo $separator;
									?>
									<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . $taxonomy ) ); ?>"><?php echo esc_html( $taxonomy ); ?></a>
								</h4>
								<?php
								/**
								 * Post type slug for default language.
								 */
								$help_title = '';
								if ( in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
									$help_title = __( 'To edit the slug for &#8220;%s&#8221 in the default language, please visit the Settings->Permalinks page.', 'wpglobus-plus' );
									$help_title = sprintf( $help_title, $taxonomy );
								}
								$is_dot = false;
								if ( $taxonomy == 'category' && $real_taxonomy_slug == '.' ) {
									$is_dot = true;
								}
								?>
								<div style="height: 35px;">
									<div style="float:left;width:150px;padding-top:4px;" class="language-name">
										<?php echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ WPGlobus::Config()->default_language ] ) ); ?>
									</div>
									<div style="float:left;">
										<input disabled type="text"
											   size="30"
											   value="<?php echo esc_attr( $real_taxonomy_slug ); ?>"
											   class="wpglobus-translatable"
											   data-language="<?php echo esc_attr( WPGlobus::Config()->default_language ); ?>" title="" />
										<?php if ( $help_title ) { ?>
											<span class="dashicons dashicons-editor-help wpglobus-plus-taxonomies-help" title="<?php echo esc_attr( $help_title ); ?>" style="height:10px;width:10px;"></span>
										<?php } ?>
									</div>
								</div>
								<?php
								/** 
								 * Init taxonomy's slug source.
								 */						
								$source = '';
								if ( ! empty( $opts['taxonomy'][ $taxonomy ]['slug'] ) ) {
									$source = $opts['taxonomy'][ $taxonomy ]['slug'];
								}
								foreach ( $this->extra_languages as $language ) {
									if ( '' == WPGlobus_Core::text_filter($source, $language) ) {
										$source .= WPGlobus::add_locale_marks( esc_attr( $real_taxonomy_slug ), $language );
									}
								}
								
								foreach ( $this->extra_languages as $language ) :
									$slug = '';
									if ( ! empty( $source ) ) {
										$slug = WPGlobus_Core::extract_text( $source, $language );
									}
									$disabled = '';
									if ( $is_dot ) {
										$slug = '.';
										$disabled = 'disabled';
									}
									?>
									<div style="height:35px;">
										<div style="float:left;width:150px;padding-top:4px;" class="language-name">
											<?php echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ $language ] ) ); ?>
										</div>
										<div style="float:left;">
											<input type="text" <?php echo $disabled; ?> 
												   size="30"
												   value="<?php echo esc_attr( $slug ); ?>"
												   class="wpglobus-translatable wpglobus-plus-taxonomy-field"
												   data-language="<?php echo esc_attr( $language ); ?>"
												   data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
												   data-source-id="#wpglobus-source-<?php echo esc_attr( $taxonomy ); ?>" 
												   data-slug-default="<?php echo esc_attr( urldecode( $real_taxonomy_slug ) ); ?>" title="" />
										</div>
									</div>
								<?php endforeach; ?>
								<?php
								/**
								 * Hidden field for taxonomy.
								 */
								?>
								<input type="text"
									   id="wpglobus-source-<?php echo esc_attr( $taxonomy ); ?>"
									   value="<?php echo esc_attr( $source ); ?>"
									   size="80"
									   class="wpglobus-taxonomy-source hidden"
									   data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" title="" />

								<input type="button"
									   style="width:80%;cursor:pointer;"
									   class="wpglobus-plus-taxonomies-toggle"
									   value="<?php echo esc_attr(
										   sprintf( __( 'Terms of %s (%d items total) :', 'wpglobus-plus' ),
											   $taxonomy, count( $terms ) ) ); ?>"
									   data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" />

								<div id="wpglobus-plus-taxonomies-<?php echo esc_attr( $taxonomy ); ?>-terms-box" class="wpglobus-plus-taxonomies-terms-box">
									<input type="checkbox"
										   class="wpglobus-plus-taxonomies-hide-empty-terms"
										   id="wpglobus-plus-taxonomies-hide-empty-<?php echo esc_attr( $taxonomy ); ?>"
										   data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>" title="" /><?php esc_html_e( 'Hide empty terms.', 'wpglobus-plus' ); ?>
									<?php
									/**
									 * Taxonomy terms.
									 */
									foreach ( $terms as $term ) : ?>
										<a id="<?php echo esc_attr( $taxonomy . '_' . $term->term_id ); ?>" name="<?php echo esc_attr( $taxonomy . '_' . $term->term_id ); ?>"></a>
										<ul id="taxonomy-term-<?php echo esc_attr( $term->term_id ); ?>"
											class="wpglobus-plus-taxonomies-term"
											data-term-slug="<?php echo esc_attr( urldecode( $term->slug ) ); ?>"
											data-term-name="<?php echo esc_attr( $term->name ); ?>">

											<li style="padding-left:100px;height:30px;">
												<?php esc_html_e( 'Term name', 'wpglobus' ); ?>:
												<a href="<?php echo esc_url( admin_url( 'term.php?taxonomy=' . $taxonomy . '&tag_ID=' . $term->term_id . '&post_type=' . $post_type ) ); ?>"><strong><?php echo esc_attr( $term->name ); ?></strong></a>
											</li>
											<li style="height:30px;">
												<div style="float:left;width:150px;padding-top:4px;" class="language-name">
													<?php echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ WPGlobus::Config()->default_language ] ) ); ?>
												</div>
												<div style="float:left;">
													<input disabled type="text"
														   size="30"
														   value="<?php echo esc_attr( urldecode( $term->slug ) ); ?>"
														   class="wpglobus-translatable"
														   data-language="<?php echo esc_attr( WPGlobus::Config()->default_language ); ?>" title="" />
												</div>
											</li>
											<?php
											/** 
											 * Init term's slug source.
											 */										
											$source = '';
											if ( ! empty( $opts['taxonomy'][ $taxonomy ]['term_slug'][ 'term_id_' . $term->term_id ] ) ) {
												$source = $opts['taxonomy'][ $taxonomy ]['term_slug'][ 'term_id_' . $term->term_id ];
											}
											foreach ( $this->extra_languages as $language ) {
												if ( '' == WPGlobus_Core::text_filter($source, $language) ) {
													$source .= WPGlobus::add_locale_marks( $term->slug, $language );
												}
											}
											
											foreach ( $this->extra_languages as $language ) :
												$_slug = '';
												if ( ! empty( $source ) ) {
													$_slug = WPGlobus_Core::extract_text( $source, $language );
												}
												?>
												<li style="height:30px;">
													<div style="float:left;width:150px;padding-top:4px;" class="language-name">
														<?php echo esc_html( sprintf( $slug_label, WPGlobus::Config()->en_language_name[ $language ] ) ); ?>
													</div>
													<div style="float:left;">
														<input type="text"
															   size="30"
															   value="<?php echo esc_attr( $_slug ); ?>"
															   class="wpglobus-translatable wpglobus-plus-taxonomy-field"
															   data-language="<?php echo esc_attr( $language ); ?>"
															   data-term="<?php echo esc_attr( $taxonomy ); ?>"
															   data-source-id="#wpglobus-source-term-id-<?php echo esc_attr( $term->term_id ); ?>" 
															   data-slug-default="<?php echo esc_attr( urldecode( $term->slug ) ); ?>" title="" />
													</div>
												</li>
											<?php endforeach; // $language ?>
											<?php
											/**
											 * Hidden field for taxonomy term.
											 */
											?>
											<input type="text"
												   id="wpglobus-source-term-id-<?php echo esc_attr( $term->term_id ); ?>"
												   value="<?php echo esc_attr( $source ); ?>"
												   class="wpglobus-taxonomy-term-source wpglobus-plus-taxonomy-field hidden"
												   size="80"
												   data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>"
												   data-term-id="<?php echo esc_attr( $term->term_id ); ?>"
												   data-term-slug="<?php echo esc_attr( urldecode( $term->slug ) ); ?>" title="" />
										</ul>
									<?php endforeach; // $terms ?>
								</div><!-- .wpglobus-plus-taxonomies-terms-box -->
							</div><!-- .taxonomy-box -->
						<?php } ?>
					</div><!-- .taxonomies -->
					<?php
				endif; /** enable_taxonomies */
				?>
				<div style="">
					<input id="post-type-<?php echo esc_attr( $post_type ); ?>-save"
						   type="button"
						   class="button button-primary post-type-save"
						   value="<?php esc_attr_e( 'Save' ); ?>"
						   data-post-type="<?php echo esc_attr( $post_type ); ?>"
						   data-tab-id="#tab-<?php echo esc_attr( $post_type ); ?>" />
					<span style="float:left;" class="spinner"></span>
				</div>
			</div>

			<?php
		} // $wp_post_types ?>
	</div><!-- .wpglobus-plus-taxonomies-general-wrap__item -->	
</div><!-- .wpglobus-plus-taxonomies-general-wrap --> <?php

# --- EOF