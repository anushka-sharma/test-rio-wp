<?php
/**
 * File debug.php.
 * @since 1.3.11
 *
 * @package WPGlobus Plus
 * @module Taxonomies.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $wp_taxonomies as $taxonomy => $data ) :
	if ( empty( $data->wpglobus ) ) {
		continue;
	}
	$_taxonomy_before = esc_html__( 'Taxonomy', 'wpglobus-plus' );
	?>
	<h4 data-id="<?php echo $taxonomy; ?>" class="wpglobus-plus-taxonomies-title"><?php echo sprintf( '%1s: <a href="#">%2s</a>', $_taxonomy_before, $taxonomy ); ?></h4>
	<div style="" class="wpglobus-plus-taxonomies-debug-data wpglobus-plus-taxonomies-debug-data-<?php echo $taxonomy; ?> hidden">
		<pre>
			<?php echo esc_html( print_r( $data, true ) ); ?>
		</pre>
	</div>
	<?php
endforeach;

foreach ( $wp_post_types as $post_type => $data ) :
	if ( ! $this->is_post_type_enabled( $post_type, $data ) ) {
		continue;
	}
	$_post_type_before = esc_html__( 'Post type', 'wpglobus-plus' );
	?>
	<h4 data-id="<?php echo $post_type; ?>" class="wpglobus-plus-taxonomies-title"><?php echo sprintf( '%1s: <a href="#">%2s</a>', $_post_type_before, $post_type ); ?></h4>
	<div style="" class="wpglobus-plus-taxonomies-debug-data wpglobus-plus-taxonomies-debug-data-<?php echo $post_type; ?> hidden">
		<pre>
			<?php echo esc_html( print_r( $data, true ) ); ?>
		</pre>
	</div>
	<?php
endforeach;

# --- EOF