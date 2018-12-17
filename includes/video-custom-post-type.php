<?php
/**
 * Add the custom post type
 *
 * @return void
 */
function custom_post_type() {

	$labels = array(
		'name'                  => _x( 'Videos', 'Video General Name', 'videopress-local-import' ),
		'singular_name'         => _x( 'Video', 'Video Singular Name', 'videopress-local-import' ),
		'menu_name'             => __( 'Videos', 'videopress-local-import' ),
		'name_admin_bar'        => __( 'Video', 'videopress-local-import' ),
		'archives'              => __( 'Item Archives', 'videopress-local-import' ),
		'attributes'            => __( 'Item Attributes', 'videopress-local-import' ),
		'parent_item_colon'     => __( 'Parent Item:', 'videopress-local-import' ),
		'all_items'             => __( 'All Items', 'videopress-local-import' ),
		'add_new_item'          => __( 'Add New Item', 'videopress-local-import' ),
		'add_new'               => __( 'Add New', 'videopress-local-import' ),
		'new_item'              => __( 'New Item', 'videopress-local-import' ),
		'edit_item'             => __( 'Edit Item', 'videopress-local-import' ),
		'update_item'           => __( 'Update Item', 'videopress-local-import' ),
		'view_item'             => __( 'View Item', 'videopress-local-import' ),
		'view_items'            => __( 'View Items', 'videopress-local-import' ),
		'search_items'          => __( 'Search Item', 'videopress-local-import' ),
		'not_found'             => __( 'Not found', 'videopress-local-import' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'videopress-local-import' ),
		'featured_image'        => __( 'Featured Image', 'videopress-local-import' ),
		'set_featured_image'    => __( 'Set featured image', 'videopress-local-import' ),
		'remove_featured_image' => __( 'Remove featured image', 'videopress-local-import' ),
		'use_featured_image'    => __( 'Use as featured image', 'videopress-local-import' ),
		'insert_into_item'      => __( 'Insert into item', 'videopress-local-import' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'videopress-local-import' ),
		'items_list'            => __( 'Items list', 'videopress-local-import' ),
		'items_list_navigation' => __( 'Items list navigation', 'videopress-local-import' ),
		'filter_items_list'     => __( 'Filter items list', 'videopress-local-import' ),
	);
	$args   = array(
		'label'               => __( 'Video', 'videopress-local-import' ),
		'description'         => __( 'VideoPress Exports', 'videopress-local-import' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes' ),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_position'       => 5,
		'menu_icon'           => 'dashicons-format-video',
		'show_in_admin_bar'   => true,
		'show_in_nav_menus'   => true,
		'can_export'          => true,
		'has_archive'         => false,
		'exclude_from_search' => true,
		'publicly_queryable'  => true,
		'capability_type'     => 'page',
		'show_in_rest'        => true,
		'rest_base'           => 'videos',
	);
	register_post_type( 'video', $args );

}
add_action( 'init', 'custom_post_type', 0 );
