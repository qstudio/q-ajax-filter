<?php
/*
 * Wrapper functions
 * 
 * @todo - are these wrappers required ? ##
 */


/**
 *
 * Adds the Javascipt to be displayed inline
 * @param array $post_type
 * @since 1.0
 * @deprecated
 */
function add_inline_javascript( $post_type = array('post') )
{
    #global $q_ajax_filter;
    #$q_ajax_filter->add_inline_javascript($post_type);
    Q_AJAX_Filter::get_instance()->add_inline_javascript($post_type);
}

/**
 *
 * Displays the pageination section
 * @param int $totalPosts
 * @param int $posts_per_page
 * @since 1.1
 * @deprecated
 */
function af_pageination( $totalPosts, $posts_per_page )
{
    #global $q_ajax_filter;
    Q_AJAX_Filter::get_instance()->pageination($totalPosts, $posts_per_page );
}

/**
 *
 * Creates the section with the loop of all the posts
 * @param array $post_type
 * @param array $filters
 * @param int $posts_per_page
 * @param array $pagination_location
 * @param bool $use_queried_object
 * @since 1.0
 * @deprecated
 */
function create_filtered_section( $post_type = array("post"), $filters = array(), $posts_per_page = 15, $pagination_location = array('top','bottom'), $use_queried_object = true )
{
    #global $q_ajax_filter;
    Q_AJAX_Filter::get_instance()->create_filtered_section( $post_type, $filters, $posts_per_page, $pagination_location, $use_queried_object );
}

/**
 *
 * Creates the progress bar
 * @since 1.1
 * @deprecated
 */
function create_prog_bar()
{
    #global $q_ajax_filter;
    Q_AJAX_Filter::get_instance()->create_prog_bar();
}

/**
 *
 * Creates the filter navigation
 * @param array $taxonomies
 * @param array $post_type
 * @param int $show_count
 * @param int $show_titles
 * @since 1.0
 * @deprecated
 */
function create_filter_nav( $taxonomies = array('category'), $post_type= array('post'), $show_count = 1, $show_titles = 1 )
{
    #global $q_ajax_filter;
    Q_AJAX_Filter::get_instance()->create_filter_nav( $taxonomies, $post_type, $show_count, $show_titles );
}

/**
 *
 * The main functions that brings everything together if using the shortcode
 * @param array $atts
 * @since 1.1
 * @deprecated
 */
function ajax_filter( $atts = array() )
{
    #global $q_ajax_filter;
    Q_AJAX_Filter::get_instance()->ajax_filter( $atts );
}
/* End Compatability */