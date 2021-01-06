<?php
// Our custom post type function
function create_posttype_videos() {
 
    register_post_type( 'faq',
    // CPT Options
        array(
            'labels' => array(
                'name' => __( 'Faqs' ),
                'singular_name' => __( 'Faq' )
            ),
            'public' => true,
            'has_archive' => true,
            'rewrite' => array('slug' => 'Faq'),
            'supports' => array( 'title','editor')
        )
    );
    
    
    $labels = array(
        'name'                       => 'Faq Type',
        'singular_name'              => 'Faq Type',
        'menu_name'                  => 'Faqcat',
        'all_items'                  => 'All Faqcat',
        'parent_item'                => 'Parent Faqcat',
        'parent_item_colon'          => 'Parent Faqcat:',
        'new_item_name'              => 'New Faqcat Name',
        'add_new_item'               => 'Add New Faqcat',
        'edit_item'                  => 'Edit Faqcat',
        'update_item'                => 'Update Faqcat',
        'separate_items_with_commas' => 'Separate Faqcat with commas',
        'search_items'               => 'Search Faqcat',
        'add_or_remove_items'        => 'Add or remove Faqcat',
        'choose_from_most_used'      => 'Choose from the most used Faqcat',
    );
    $args = array(
        'labels'                     => $labels,    
        // 'public'                     => true,
        'show_ui'                    => true,
        'show_admin_column'          => true,
        'show_in_nav_menus'          => true,
        // 'show_tagcloud'              => true,
    );
    register_taxonomy( 'pro_type', 'faq', $args );
    register_taxonomy_for_object_type( 'pro_type', 'faq' );
    
}
// Hooking up our function to theme setup
add_action( 'init', 'create_posttype_videos' );