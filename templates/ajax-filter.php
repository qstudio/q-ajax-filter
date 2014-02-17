<?php

/**
 * AJAX Search Programs ##
 *
 * @package WordPress
 * @subpackage Q
 * @since 1.1.0
 */

    // globalize post object - we'll need thi ##
    global $post, $date_range;

    // start count ##
    $i =0;

    #pr($args);
    if ( $date_range ) {
        
        // Create a new filtering function that will add our where clause to the query
        function q_ajax_filter_where( $where = '' ) {
            
            // get highest value, as that's what counts ##
            global $date_range;
            #pr($date_range);
            $key = array_search(max($date_range), $date_range);
            $range = $date_range[$key];
            #pr($range);
            $date = getdate();
            $cutoff = date('Y-m-d', mktime( 0, 0, 0, $date['mon'], $date['mday'] - $range, $date['year']));
            $where .= " AND post_date > '$cutoff'";
            #wp_die("where filtered: ".$where);
            
            return $where;
            
        }
        
        add_filter( 'posts_where', 'q_ajax_filter_where' );
        
    }
    
    #pr($args);
    
    // open wp_query ##
    #query_posts('posts_per_page=10');
    #pr($args);
    $ajaxPostfilter = new WP_Query();
    $ajaxPostfilter->query($args);
    
    // remove filter - might need to do this dynamically ##
    remove_filter( 'posts_where', 'q_ajax_filter_where' );
    
    #pr($ajaxPostfilter->request);
    
    // pagination ##
    #if(in_array("top",$paginationDisplay)) {
        #$this->pageination($ajaxPostfilter->found_posts, $postPerPage);
    #}
    
    // posts found ##
    if ( $ajaxPostfilter->have_posts() ): 

        while ( $ajaxPostfilter->have_posts() ) : $ajaxPostfilter->the_post(); 

            // iterate total ##
            $i++; 

            // get custom field data via ACF ##
            #$location = get_field( "location" ); 

            // get category terms ##
            $term_category = wp_get_post_terms( $post->ID, 'category' );
            
            #pr($term_category); exit;
            
            // get term link ##
            if ( $term_category ) $term_category_link = get_term_link( $term_category[0] );
            
            #echo $term_category[0]->term_id; exit;
            
            // image ##
            if ( has_post_thumbnail( $post->ID ) ) {
                
                // show small image, linking to larger image ##
                $img_src = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'search' );
                $img_src = $img_src[0]; // take first array item ##
                $img_alt = get_post_meta( get_post_thumbnail_id( $post->ID ), '_wp_attachment_image_alt', true);
                $img_alt = ( $img_alt ? $img_alt : get_the_title() ) ;    
            
            // backup ##
            } else {
                
                $img_src = q_locate_template("images/holder/170x152.png", false, false, false );
                $img_alt = get_the_title(); 
                
            }
                
?>
                    <div class="ajax-loaded">
                        <div class="image r-min-320">
                            <a href="<?php the_permalink(); ?>" title="<?php echo $img_alt; ?>">
                                <img src="<?php echo $img_src; ?>" alt="<?php echo $img_alt; ?>" />
                            </a>
                        </div>
                        <div class="text">
                            <h2><a href="<?php the_permalink(); ?>"><?php the_title();?></a></h2>
                            <span class="what"><a href="<?php echo $term_category_link; ?>"><?php echo $term_category[0]->name; ?></a></span>
                            <p><?php echo strip_tags(q_excerpt_from_id( $post->ID, 200 )); ?></p>
                        </div>
                    </div>
<?php 

        endwhile; 
        
        // reset global post object ##
        wp_reset_query();

    else:
                
?>
                    <p class="empty"><?php _e("No Results found - Please use a broader search", "q_child" ); ?></p>
<?php
                
    endif;


