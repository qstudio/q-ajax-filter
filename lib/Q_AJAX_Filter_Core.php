<?php

/**
 *
 * the core class of the plugin
 * @author James Irving-Swift
 *
 *
 */
class Q_AJAX_Filter_Core {
    
    var $date_range;
    
    /**
     * Add inline JS to search page
     * 
     * @since       1.7.0
     * @param       array   $post_type
     * @param       string  $template
     * @param       string  $order
     * @param       string  $order_by
     */
    public function add_inline_javascript( 
            $post_type = array('post')
        ,   $template = 'ajax-filter.php'
        ,   $order = 'DESC'
        ,   $order_by = 'date'
        ,   $filter_type = 'list' 
        ,   $filter_position = 'side' 
        )
    {
        
        // grab the queried object ##
        $queried_object = get_queried_object();

        // get the page's current taxonomy to filter
        if( isset( $queried_object->term_id ) ) {
            
           $queried_object_string = $queried_object->taxonomy."##".$queried_object->term_id;
           
        } else {
            
           $queried_object_string = "af_null";
           
        }
        
?>
        <script type="text/javascript">
            
            // configure AF - Q AJAX Filters ##
            var AF_CONFIG = {
            	ajaxurl: '<?php echo home_url( 'wp-admin/admin-ajax.php' ) ?>',
                post_type: '<?php echo implode(',',$post_type); ?>',
                template: '<?php echo $template; ?>',
                order: '<?php echo $order; ?>',
                order_by: '<?php echo $order_by; ?>',
                filter_type: '<?php echo $filter_type; ?>',
                filter_position: '<?php echo $filter_position; ?>',
                queried_object: '<?php echo $queried_object_string; ?>',
                thisPage: 1,
                nonce: '<?php echo esc_js(wp_create_nonce('filternonce')); ?>'
            };
            
        </script>
<?php

    }
    
    
    /**
     * Build the filtered element on the search results page
     * 
     * @since       1.7.0
     * @global      string      $date_range
     * @param       array       $post_type
     * @param       array       $filters
     * @param       int         $posts_per_page
     * @param       string      $hide_pagination
     * @param       string      $template
     * @param       string      $order
     * @param       string      $order_by
     * @param       boolean     $use_queried_object
     * @param       string      $filter_position
     * 
     * @return      string      HTML for results
     */
    public function create_filtered_section ( 
            $post_type              = array( "post" )
        ,   $filters                = array()
        ,   $posts_per_page         = 10
        ,   $hide_pagination        = false
        ,   $template               = 'ajax-filter.php'
        ,   $order                  = 'DESC'
        ,   $order_by               = 'date'
        ,   $use_queried_object     = true // $use_queried_object refers to using the Queried Object ##
        ,   $filter_position        = 'side' 
        ) 
    {
        
    	// post data passed, so update values ##
        if( $_POST ){
            
            // secure with a nonce ##
            check_ajax_referer('filternonce');
            
            // grab post data ##
            $post_type = isset( $_POST['post_type'] ) ? explode( ',', $_POST['post_type'] ) : $post_type;
            $template = isset( $_POST['template'] ) ? $_POST['template'] : $template ;
            $template = isset( $_POST['order'] ) ? $_POST['order'] : $order ;
            $template = isset( $_POST['order_by'] ) ? $_POST['order_by'] : $order_by ;
            $_POST_filters = isset( $_POST['filters'] ) ? explode('&',$_POST['filters']) : null ;
            #pr( $_POST_filters );
            
        }
        
        // counter ##
        $c = 0;

        if ( isset( $_POST_filters ) && $_POST_filters[0] != "" ) { //check that the array isn't blank

            // this while loop puts the filters in a usable array ##
            while( $c < count( $_POST_filters ) ){
                
                // explode string to array ##
                $string = explode('=',$_POST_filters[$c]);
                
                // check if each item is an array - or caste ##
                if( ! is_array ( $filters[$string[0]] ) ) {
                    $filters[$string[0]] = array();
                }
                
                // add items to array ##
                array_push($filters[$string[0]],$string[1]);
                
                // clean up empty items ##
                array_filter( $filters );
                
                // iterate ##
                $c++;
                
            }
            
            #pr( $filters );

        }
        
        // build args list ##
        $args = array(
                "post_type"         => $post_type
            ,   "posts_per_page"    => (int)$posts_per_page
            ,   "tax_query"         => array()
            ,   "orderby"           => $order_by
            ,   "order"             => $order
            ,   "post_status"       => "publish"
        );

        // no posted data ##
        if ( ! $_POST ) {
            
            // grab the queried object ##
            $queried_object = get_queried_object();
            
            //get the page's current taxonomy to filter
            if ( isset( $queried_object->term_id ) && $use_queried_object === true ){
                
                array_push( $args['tax_query'],
                    array(
                            'taxonomy'     => $queried_object->taxonomy
                        ,   'field'        => 'id'
                        ,   'terms'        => $queried_object->term_id
                    )
                );
                
            }
            
        } else {
            
            // check if the queried_object is good ##
            if( $_POST['queried_object'] != 'af_null' ) {
                
                // explode queried_object string ##
                $queried_object = explode('##',$_POST['queried_object']);
                
                // push array items into a tax_query ##
                array_push($args['tax_query'],
                    array(
                            'taxonomy'  => $queried_object[0]
                        ,   'field'     => 'id'
                        ,   'terms'     => $queried_object[1]
                    )
                );
            }
            
            // check if paging value passed, if so add to the query ##
            if ( isset ( $_POST['paged'] ) ) {
                
                $args['paged'] = $_POST['paged'];
                
            }
            
        }

        // check if paging value passed, if so add to the query ##
        if ( isset ( $_POST['paged'] ) ) {
            $args['paged'] = $_POST['paged'];
        } else {
            $args['paged'] = 1;
        }
        
        #if ( isset( $_POST['template'] ) ) {
        #    $template = $_POST['template'];
        #}
        
        // filters ##
        if ( isset( $filters ) ){
           
            // add all the filters to tax_query ##
            foreach( $filters as $taxonomy => $ids ){
                
                // data filtering ##
                if ( $taxonomy == 'date' ) {
                    
                    global $date_range; // @todo - why global ?? ##
                    $date_range = $ids;
                
                // authoer filtering ##
                } elseif ( $taxonomy == 'author' ) {
                    
                    array_push( $args['author'] = implode(',', $ids) );
                
                // text search filtering ##
                } elseif ( $taxonomy == 'search' ) {
                    
                    #echo("search tax: ".implode(',', $ids));
                    array_push($args['s'] = implode(',', $ids));
                    
                // taxonomy filtering ##
                } else {
                
                    foreach( $ids as $id ){
                        array_push( $args['tax_query'],
                            array(
                                'taxonomy' => $taxonomy,
                                'field' => 'id',
                                'terms' => $id
                            )
                        );
                    }
                
                }
                
            }
        }

        // inserts a "AND" relation if more than one array in the tax_query ##
        if( count( $args['tax_query'] ) > 1 ) {
            
            $args['tax_query']['relation'] = 'AND';
        
        }

        // template file ##
        if ( file_exists(get_stylesheet_directory()."/library/templates/".$template) ) {
            
            include get_stylesheet_directory()."/library/templates/".$template;
            
        } else {
            
            // counter ##
            $i = 0;
            
            #pr( $args );
            
            // new WP_Query ##
            $q_ajax_filter_wp_query = new WP_Query();
            
            // parse args ##
            $q_ajax_filter_wp_query->query( $args );
            
            if ( $q_ajax_filter_wp_query->have_posts() ) {
                
                while ( $q_ajax_filter_wp_query->have_posts() ) {
                    
                    $q_ajax_filter_wp_query->the_post(); 
                
                    // iterate ##
                    $i++; 
                
?>
                <article class="ajax-loaded">
                    <h3><?php the_title();?></h3>
                    <?php the_post_thumbnail(array( 150, 150 )); ?>
                    <p><?php the_excerpt(); ?></p>
                    <a href="<?php the_permalink(); ?>" title="<?php the_title();?>"><?php _e( "Read More", Q_AJAX_Filter::$text_domain ); ?></a>
                </article>
<?php 
            
                } // white loop ##
                
            } else {
                
                echo "<p class='no-results'>"; _e( "No Results found :(", Q_AJAX_Filter::$text_domain ); echo "</p>"; 
            
            }
            
        }

        if( $hide_pagination === false ) {
            
            $this->pageination( $q_ajax_filter_wp_query->found_posts, $posts_per_page );
            #echo "<p class="total">Total Results: {$q_ajax_filter_wp_query->found_posts}</p>";
            
        }
            
        // reset global post object ##
        wp_reset_query();
        
        // called from ajax - so needs to die ##
        if ( $_POST ) {
            
            //echo 'nada';
            die();
            
        }
        
    }

    
    /**
     * Buid pagination 
     * 
     * @since       1.4.0
     * @return      String      HTML for pagination
     */
    public function pageination( $total_posts, $posts_per_page ) 
    {
        
?>
    <nav class="pagination">
<?php 
        
    if( $_POST && isset($_POST['paged']) && $_POST['paged'] > 1 ) {

        $page_number = $_POST['paged']; 
            
?>
        <div class="prevPage"><a class="paginationNav" rel="prev" href="#">&lsaquo; <?php _e("Back", Q_AJAX_Filter::$text_domain ); ?></a></div>
<?php 
    
    } else {
        
        $page_number = 1;
        
    }
    
?>
        <div class="af-pages">
<?php
        
        // get paging info ##
        $paging_info = $this->get_paging_info( $total_posts, $posts_per_page, $page_number );

        // $max is equal to number of links shown
        $max = 7;
        if ( isset( $q_device ) && $q_device["width"] < 640 ) {
            $max = 3;
        }

        // check things out ##
        if($paging_info['page_number'] < $max) {
            $sp = 1;
        } elseif ($paging_info['page_number'] >= ($paging_info['pages'] - floor($max / 2)) ) {
            $sp = $paging_info['pages'] - $max + 1;
        } elseif($paging_info['page_number'] >= $max) {
            $sp = $paging_info['page_number']  - floor($max/2);
        }

        // If the current page >= $max then show link to 1st page
        if ( $paging_info['page_number'] >= $max ) { 
            
?>
            <a href='#' class='pagelink-1 pagelink' rel="1">1</a>..
<?php 

        } 
        
        //Loop though max number of pages shown and show links either side equal to $max / 2 -->
        for($i = $sp; $i <= ($sp + $max -1);$i++) { 

            if($i > $paging_info['pages'])
                continue;

            // current ##
            if ( $paging_info['page_number'] == $i ) { 
            
?>
                <a href="#" class="pagelink-<?php echo $i; ?> pagelink current" rel="<?php echo $i; ?>"><?php echo $i; ?></a>

<?php 
        
            // normal ##
            } else { 
            
?>

                <a href='#' class="pagelink-<?php echo $i; ?> pagelink" rel="<?php echo $i; ?>"><?php echo $i; ?></a>

<?php 

            } 

        } 

        // If the current page is less than the last page minus $max pages divided by 2 ##
        if ( $paging_info['page_number'] < ( $paging_info['pages'] - floor($max / 2 ) ) ) {
        
?>
            ..<a href='#' class="pagelink-<?php echo $paging_info['pages']; ?> pagelink" rel="<?php echo $paging_info['pages']; ?>"><?php echo $paging_info['pages']; ?></a>
<?php 

        } 
    
?>
<?php
    
        /*
        $p = 1; // iteration control ##
        
        // loop over total
        while( $p <= ceil( $total_posts/$posts_per_page ) ){
            
            // open tag ##
            echo '<a href="#" class="pagelink-'.$p.' pagelink';
            
            // highlight ##
            if( $p == $page_number || !$_POST && $p == 1 ) {
                echo "current";
            }
            
            // close tag ##
            echo '" rel="'.$p.'">'.$p.'</a>';
            
            // add break bretween pages ##
            #if($p <= ceil($total_posts/$posts_per_page-1)) {
                #echo " | ";
            #}
            
            // iterate ##
            $p++;
            
        }
         */

?>
        </div>
<?php 

        // check if we need to print pagination ##
        if ( ( $posts_per_page * $page_number ) < $total_posts && $posts_per_page < $total_posts ) { 
            
?>
        <div class="nextPage"><a class="paginationNav" rel="next" href="#"><?php _e( "Next", Q_AJAX_Filter::$text_domain ); ?> &rsaquo;</a></div>
<?php 

        } // pagination check ## 

?>
    </nav>
<?php 

    }
    
    
    /**
     * Paging Info
     * 
     * @since   1.7.0
     * @link    http://stackoverflow.com/questions/8361808/limit-pagination-page-number
     * @return  array   data for paging
     */
    public function get_paging_info( $total_posts, $posts_per_page, $page_number ) 
    {
        
        $pages = ceil( $total_posts / $posts_per_page ); // calc pages

        $data = array(); // start out array
        $data['offset']         = ( $page_number * $posts_per_page ) - $posts_per_page; // what row to start at -- was ["si"]
        $data['pages']          = $pages;                   // add the pages
        $data['page_number']    = $page_number;               // Whats the current page

        return $data; // return the paging data

    }
    
    
    /** 
     * build list of terms to filter by
     * 
     * @since       1.7.0
     * @param       array   $taxonomies
     * @param       string  $template
     * @param       array   $post_type
     * @param       int     $show_count
     * @param       int     $hide_titles
     * 
     * @return      string      HTML for filter nav
     */
    public function create_filter_nav( 
            $taxonomies = array('category')
        ,   $template = 'ajax-filter.php'
        ,   $post_type = array('post')
        ,   $filter_position = 'side'
        ,   $filter_type = 'list' 
        ,   $show_count = 0
        ,   $hide_titles = 0 
    ) 
    {
        
        // checked for past values and knock on search ##
        $category = isset($_GET["category"]) ? $_GET["category"] : '';
        if ( isset($category) ) {
            $category_term = get_term_by( 'slug', $category, 'category' );
            if ( $category ) $category = $category_term->term_id;
        }
        $tag = isset($_GET["tag"]) ? $_GET["tag"] : '';
        if ( isset($tag) ) {
            $tag_term = get_term_by( 'slug', $tag, 'post_tag' );
            if ( $tag ) $tag = $tag_term->term_id;
        }
        $searcher = isset($_GET["s"]) ? $_GET["s"] : "";
        
        // position the filters correctly ##
        $position = $filter_position == 'vertical' ? 'vertical' : 'horizontal' ;
        
?>
        <ul id="ajax-filters" class="ajax-filters <?php echo $position; ?>">
            
            <li class="input text">
                <input type="text" value="<?php $searcher; ?>" name="searcher" id="searcher" placeholder="<?php _e("Search", Q_AJAX_Filter::$text_domain ); ?>" class="filter-selected" />
            </li>
<?php
            
        $queried_object = get_queried_object();
        #pr($taxonomies);  
        
        if ( $taxonomies && isset( $taxonomies[0] ) && $taxonomies[0] > '' ) {
            
            foreach( $taxonomies as $taxonomy ) {
                
                if ( ! taxonomy_exists ( $taxonomy ) ) { 
                    echo "skipping {$taxonomy}";
                    continue; 
                }
                
?>
            <li class="ajax-filters-li ajax-filter-li-<?php echo $taxonomy; ?>">
<?php
                
                // date filtering options ##
                if ( $taxonomy == 'date' ) {
                    
                    $the_tax_name = __( 'Date Range', Q_AJAX_Filter::$text_domain );
                    
                    $terms = array (
                        '1' => array (
                            'slug' => 'today',
                            'term_id' => '1',
                            'name' => __( 'Today', Q_AJAX_Filter::$text_domain )
                        ),
                        '2' => array (
                            'slug' => 'one-week',
                            'term_id' => '7',
                            'name' => __( 'One Week', Q_AJAX_Filter::$text_domain )
                        ),
                        '3' => array (
                            'slug' => 'one-month',
                            'term_id' => '31',
                            'name' => __( 'One Month', Q_AJAX_Filter::$text_domain )
                        ),
                        '4' => array (
                            'slug' => 'one-year',
                            'term_id' => '365',
                            'name' => __( 'One Year', Q_AJAX_Filter::$text_domain )
                        )
                    );
                    
                    // cast to object ##
                    $terms = $this->array_to_object($terms);
                    
                } elseif ( $taxonomy == 'author' ) {
                    
                    $the_tax_name = __( 'Authors', Q_AJAX_Filter::$text_domain );
                    
                    $terms = array(
                        '0' => array ()
                    );
                    
                    $authors = get_users ('role=contributor');
                    foreach ( $authors as $author ) {
                        
                        // skip authors with zero posts ##
                        $numposts = count_user_posts($author->ID);
                        if ( $numposts < 1 ) continue;
                        
                        $terms[] = array (
                            'slug' => $author->user_login,
                            'term_id' => $author->ID,
                            'name' => $author->display_name ? $author->display_name : $author->user_login
                        );
                    }
                     
                    // cast to object ##
                    #pr($terms);
                    $terms = $this->array_to_object($terms);
                    
                } else {

                    #pr($taxonomy);
                    $terms = get_terms( $taxonomy, array(
                            'orderby'    => 'name',
                            'hide_empty' => 1
                        )
                    );
                    
                    if ( ! isset( $terms ) || empty ( $terms ) || is_wp_error( $terms ) ) {
                        
                        #pr($terms);
                        #echo "term empty or error - skipping {$taxonomy}"; 
                        continue;
                        
                    }
                        
                    reset($terms);
                    $first_key = key($terms);

                    // nothing cooking in this taxonomy ##
                    if ( ! $terms[$first_key] ) { 
                        
                        #echo "no first key - skipping {$taxonomy}"; 
                        #pr($terms[$first_key]);
                        continue; 
                        
                    }
                    
                    #pr("first_key : {$first_key}");

                    // get tax name ##
                    $the_tax = get_taxonomy( $terms[$first_key]->taxonomy );
                    #pr( $the_tax->labels->singular_name );
                    $the_tax_name = $the_tax->labels->singular_name;
                    
                    if ( $filter_type == 'list' && $hide_titles == 0 ){

                        #pr($the_tax);
                        echo "<h4>{$the_tax_name}</h4>";

                    }
                    
                }
                
?>
                <ul class="ajax-filters-li-ul">
<?php
                    
                        #pr($term);
                        
                        // select or list items ? ##
                        switch( $filter_type ) {
                            
                            // build selects for changing values ##
                            case "select";
                                
                                echo "<li class=\"selector category general\">";
                                echo "<select class=\"ajaxFilterSelect filter-$taxonomy ajax-select\">";
                                echo "<option value=\"\" class=\"default\">-- ".$the_tax_name." --</option>";
                                
                                #wp_die(pr($terms));
                                foreach( $terms as $term ) {
                                    
                                    $option_class = '';
                                    if ( $term->term_id == $queried_object->term_id ) {
                                        $option_class = "filter-selected";
                                    }

                                    echo "<option value=\"$taxonomy={$term->term_id}\" data-tax=\"$taxonomy={$term->term_id}\" data-slug=\"{$term->slug}\" class=\"{$option_class}\">";

                                        echo "{$term->name}";

                                        if( $show_count == 1 ) {
                                            echo " ({$term->count})";
                                        }

                                    echo "</option>";
                                    
                                }
                                
                                echo "</select>";
                                echo "</li>";
                                
                                break;
                                
                            // build list items for changing values ##
                            case "list";
                               
                                default;
                                
                                #wp_die(pr($terms));
                                foreach( $terms as $term ) {
                                    
                                    echo "<li class=\"ajaxFilterItem filter-$taxonomy {$term->slug} af-$taxonomy-{$term->term_id} ajax-filters-li-ul-li";

                                    if ( $term->term_id == $queried_object->term_id ) {
                                        echo " filter-selected";
                                    }

                                    echo "\" data-tax=\"$taxonomy={$term->term_id}\" data-slug=\"{$term->slug}\"><a href=\"#\" class=\"ajax-filter-label\"><span class=\"checkbox\"></span>{$term->name}</a></label>";
                                    if( $show_count == 1 ) {
                                        echo " ({$term->count})";
                                    }
                                    echo "</li>";
                                
                                } 
                                
                                break;
                            
                        } // switch ##
                        
?>
                </ul>
            </li>
<?php       
    
            } // loop ## 
            
        } // taxs set ##

?>
            <li class="input reset">
                <input type="reset" id="reset" class="reset" value="<?php _e("Reset", Q_AJAX_Filter::$text_domain ); ?>" />
            </li>
            <li class="input submit">
                <input type="submit" id="go" class="go filter" value="<?php _e("Search", Q_AJAX_Filter::$text_domain );?>" />
            </li>
        </ul>
<?php

    }

    
    /** 
     * build prog bar function
     * 
     * @since       1.7.0
     * @return      string      HTML for progress bar
     */
    public function create_prog_bar()
    { 

?>
    	<div id='ajax-loader' style="display:none">
            <div id='progbar-container'>
                <div id='progbar'></div>
            </div>
        </div>
<?php 

    }
    
    
    /**
     * Caste Array to Object
     * 
     * @param type $array
     * @return \stdClass|boolean
     */
    public function array_to_object($array) 
    {
        
        if(!is_array($array)) {
            return $array;
        }

        $object = new stdClass();
        if (is_array($array) && count($array) > 0) {
          foreach ($array as $name=>$value) {
             $name = strtolower(trim($name));
             if (!empty($name)) {
                $object->$name = $this->array_to_object($value);
             }
          }
          return $object; 
        }
        else {
          return false;
        }
        
    }
    

} // class wrapper ##
