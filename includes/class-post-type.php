<?php
/**
 * Post Type Class
 * 
 * Registers the flashcard_set custom post type
 */

if (!defined('ABSPATH')) {
    exit;
}

class FCARDM_Post_Type {

    /**
     * Register the flashcard_set post type
     */
    public static function register() {
        $labels = array(
            'name'                  => _x('Flashcard Sets', 'Post type general name', 'flashcard-manager'),
            'singular_name'         => _x('Flashcard Set', 'Post type singular name', 'flashcard-manager'),
            'menu_name'             => _x('Flashcard Sets', 'Admin Menu text', 'flashcard-manager'),
            'name_admin_bar'        => _x('Flashcard Set', 'Add New on Toolbar', 'flashcard-manager'),
            'add_new'               => __('Add New', 'flashcard-manager'),
            'add_new_item'          => __('Add New Flashcard Set', 'flashcard-manager'),
            'new_item'              => __('New Flashcard Set', 'flashcard-manager'),
            'edit_item'             => __('Edit Flashcard Set', 'flashcard-manager'),
            'view_item'             => __('View Flashcard Set', 'flashcard-manager'),
            'all_items'             => __('All Sets', 'flashcard-manager'),
            'search_items'          => __('Search Flashcard Sets', 'flashcard-manager'),
            'parent_item_colon'     => __('Parent Flashcard Sets:', 'flashcard-manager'),
            'not_found'             => __('No flashcard sets found.', 'flashcard-manager'),
            'not_found_in_trash'    => __('No flashcard sets found in Trash.', 'flashcard-manager'),
            'featured_image'        => _x('Flashcard Set Cover Image', 'Overrides the "Featured Image" phrase', 'flashcard-manager'),
            'set_featured_image'    => _x('Set cover image', 'Overrides the "Set featured image" phrase', 'flashcard-manager'),
            'remove_featured_image' => _x('Remove cover image', 'Overrides the "Remove featured image" phrase', 'flashcard-manager'),
            'use_featured_image'    => _x('Use as cover image', 'Overrides the "Use as featured image" phrase', 'flashcard-manager'),
            'archives'              => _x('Flashcard Set archives', 'The post type archive label', 'flashcard-manager'),
            'insert_into_item'      => _x('Insert into flashcard set', 'Overrides the "Insert into post" phrase', 'flashcard-manager'),
            'uploaded_to_this_item' => _x('Uploaded to this flashcard set', 'Overrides the "Uploaded to this post" phrase', 'flashcard-manager'),
            'filter_items_list'     => _x('Filter flashcard sets list', 'Screen reader text', 'flashcard-manager'),
            'items_list_navigation' => _x('Flashcard sets list navigation', 'Screen reader text', 'flashcard-manager'),
            'items_list'            => _x('Flashcard sets list', 'Screen reader text', 'flashcard-manager'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false, // We'll add it to our custom menu
            'query_var'          => true,
            'rewrite'            => array('slug' => 'flashcard-set'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-index-card',
            'supports'           => array('title'),
            'show_in_rest'       => true,
        );

        register_post_type('flashcard_set', $args);
    }
}

