<?php

/*
 * Plugin Name: Search & Filter via AJAX
 * Plugin URI: http://qstudio.us/plugins/
 * Description: Filter posts by taxonomies or text search using AJAX to load results
 * Version: 1.7.1
 * Author: Q Studio
 * Author URI: http://qstudio.us
 * License: GPL2
 * Class:           Q_AJAX_Filter
 * Text Domain:     q-ajax-filter
*/

defined( 'ABSPATH' ) OR exit;

if ( ! class_exists( 'Q_AJAX_Filter' ) ) 
{

    // define install path ##
    $installpath = pathinfo(__FILE__);

    // include php files in lib folder ##
    foreach ( glob( $installpath['dirname']."/lib/*.php") as $filename ){
        include $filename;
    }
    
    // plugin version
    define( 'Q_AJAX_FILTER_VERSION', '1.7.1' ); // version ##
    
    // instatiate plugin via WP hook - not too early, not too late ##
    add_action( 'init', array ( 'Q_AJAX_Filter', 'get_instance' ), 0 );
    
    /**
     *
     * The main class used by the plugin
     * @since 1.5
     * @author James Irving-Swift
     *
     */
    class Q_AJAX_Filter extends Q_AJAX_Filter_Core 
    {
        
        // Refers to a single instance of this class. ##
        private static $instance = null;
        
        // for translation ##
        public static $text_domain = 'q-ajax-filter';
        
        
        /**
         * Creates or returns an instance of this class.
         *
         * @return  Foo     A single instance of this class.
         */
        public static function get_instance() {

            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;

        }
        
        
        /**
         * Instatiate Class
         * 
         * @since       1.7.0
         * @return      void
         */
        private function __construct(){
            
            // templates ##
            $this->templates = array();
            
            // set text domain ##
            add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
            
            // ajax search calls ##
            add_action( 'wp_ajax_q_ajax_filter', array( $this, 'create_filtered_section' ) );
            add_action( 'wp_ajax_nopriv_q_ajax_filter', array( $this, 'create_filtered_section' ) );
            
            if ( ! is_admin() ) {
                
                // include scripts and css on WP init ##
                add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) ); 
                
                // add shortcode ##
                add_shortcode( 'ajaxFilter', array( $this, 'ajax_filter' ) ); 
                
                // add body class to pages which include the [ajaxFilter] shortcode ##
                add_filter( 'body_class', array ( $this, 'body_class' ) );
                
            }
            
        }
        
        
        /**
         * Load Text Domain for translations
         * 
         * @since       1.7.0
         * 
         */
        public function load_plugin_textdomain() 
        {
            
            $domain = self::$text_domain;
	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
            
        }
        
        
        /**
         * Add body classes to pages with the shortcode [ajaxFilter]
         * 
         * @since       1.7.0
         * @param       array   $classes
         * @return      array
         */
        public function body_class( $classes ) {
            
            global $post;
            
            if ( is_page() && $post && has_shortcode( $post->post_content, 'ajaxFilter' ) ){
                
                // build an empty array, if not present ##
                if ( ! is_array( $classes ) ) { $classes = array(); }
                
                // add 'class-name' to the $classes array
                $classes[] = 'ajax-filter';

            }
            
            // return the $classes array
            return $classes;

        }
        
        
        /**
         * The is the method that is used by the shortcode
         * 
         * @param       array   $atts
         * @since       1.5
         * @return      HTML
         */
        function ajax_filter( $atts )
        {
            
            // test ##
            #pr($atts);
            
            // post_type ##
            $post_type = isset( $atts['post_type'] ) ? preg_split( '/\s*,\s*/', trim( $atts['post_type'] ) ) : array( 'post' ) ;
            
            // taxonomies ##
            $taxonomies = isset( $atts['taxonomies'] ) ? preg_split( '/\s*,\s*/', trim( $atts['taxonomies'] ) ) : array( 'category' ) ;
            
            // show post count ##
            $show_count = isset( $atts['show_count'] ) && $atts['show_count'] == 1 ? 1 : 0 ;
            
            // show filter titles ##
            $hide_titles = isset( $atts['hide_titles'] ) && $atts['hide_titles'] == 1 ? 1 : 0 ;

            // pagination ##
            $hide_pagination = isset( $atts['hide_pagination'] ) && $atts['hide_pagination'] == 1 ? 1 : 0 ;
            
            // posts per page ##
            $posts_per_page = isset( $atts['posts_per_page'] ) ? (int)$atts['posts_per_page'] : 10 ;
            
            // filters ##
            $filters = isset( $atts['filters'] ) ? preg_split( '/\s*,\s*/', trim( $atts['filters'] ) ) : array() ;
            
            // template ##
            $template = isset( $atts['template'] ) && ! empty( $atts['template'] ) ? $atts['template'].'.php' : 'ajax-filter.php' ;
            #wp_die(pr($template));

            // order ##
            $order = isset( $atts['order'] ) && ! empty( $atts['order'] ) ? $atts['order'] : 'DESC' ;
            
            // order by ##
            $order_by = isset( $atts['order_by'] ) && ! empty( $atts['order_by'] ) ? $atts['order_by'] : 'date' ;
            
            // filter position ##
            $filter_position = isset( $atts['filter_position'] ) && ! empty( $atts['filter_position'] ) ? $atts['filter_position'] : 'side' ;
            
            // filter type ##
            $filter_type = isset( $atts['filter_type'] ) && ! empty( $atts['filter_type'] ) ? $atts['filter_type'] : 'list' ;
            
            // add inline JS to instatiate AJAX call ##
            $this->add_inline_javascript( $post_type, $template, $order, $order_by, $filter_type, $filter_position );
            
            // build filter navigation ##
            $this->create_filter_nav( $taxonomies, $template, $post_type, $filter_position, $filter_type, $show_count, $hide_titles );
            
            // position the content correctly ##
            $position = $filter_position == 'vertical' ? 'vertical' : 'horizontal' ;
            
?>  
            <div id="ajax-content" class="r-content-wide <?php echo $position; ?>">
<?php 

                // add progress bar ##
                $this->create_prog_bar();

?>
                <section id="ajax-filtered-section">
<?php 

                    // add content ##
                    $this->create_filtered_section( $post_type, $filters, $posts_per_page, $hide_pagination, $template, $order, $order_by, $filter_position );

?>
                </section>
            </div>
<?php
    
        }

        
        /**
         *
         * method to include all required scripts
         * @since 1.5
         */
        function wp_enqueue_scripts(){
            
            global $post;
            
            if ( isset( $post ) && has_shortcode( $post->post_content, 'ajaxFilter') ){
                
                // get the theme path & URL ##
                $theme_path = get_stylesheet_directory();
                $theme_url = get_stylesheet_directory_uri();
                
                // check in active template ##
                if ( file_exists( $theme_path.'/q_ajax_filter.css' ) ) {
                    
                    wp_register_style( 'q-ajax-filter-css', $theme_url.'/library/css/q_ajax_filter.css' );
                    
                } else {
                    
                    wp_register_style( 'q-ajax-filter-css', plugins_url( "css/q_ajax_filter.css", __FILE__ ) );
                    
                }
                
                wp_enqueue_style('q-ajax-filter-css');
                
                // check in active template ##
                if ( file_exists( $theme_path.'/library/js/q_ajax_filter.js' ) ) {
                    
                    wp_register_style( 'q-ajax-filter-css', $theme_url.'/library/js/q_ajax_filter.js' );
                    
                } else {
                    
                    wp_register_script('q-ajax-filter-js', plugins_url( "js/q_ajax_filter.js" , __FILE__ ) ,array('jquery'), Q_AJAX_FILTER_VERSION ,true);
                    
                }
                
                wp_enqueue_script('q-ajax-filter-js');

                // lozalize script to translate content inside JS file ##
                $translation_array = array( 
                        'site_name' => get_bloginfo("sitename")
                    ,   'search' => __( 'Search', self::$text_domain )
                    ,   'search_results_for' => __( 'Search Results For', self::$text_domain )
                    ,   'on_load_text' => __( 'Search & Filter!', self::$text_domain )
                );
                wp_localize_script( 'q-ajax-filter-js', 'q_ajax_filter', $translation_array );
            
            }

        }

    }
    
}