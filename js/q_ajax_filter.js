(function ($) {
    
    var AF_Filter = function (opts) {
        this.init(opts);
    };

    AF_Filter.prototype = {

        selected: function () {

            var self = this,
            arr = this.loop( $('.' + self.selected_filters), 'tax' );
            
            // Join the array with an "&" so we can break it later.
            return arr.join('&');

        },

        progress: function (i) {

            // Increase the progress bar based on the value passed.
            this.progbar.stop(true, true).animate({
                width: i + '%'
            }, 30);

        },
        
        title: function () {
            
            var $searcher = $("input#searcher").val();
            var $category = $("select#category").val();
            var $post_tag = $("select#post_tag").val();
            
            // default ##
            var $filter_title = '';
            var $filter_page_title = '';
            var $filter_title_update = false; // nope ##
                        
            // add search term ? ##
            if ( $searcher > '' ) {
                $filter_title = $filter_title + jQuery("input#searcher").val() + ' | ';
                if ( $filter_page_title.length > 0 ) { $filter_page_title = $filter_page_title+' + '; }
                $filter_page_title = $filter_page_title +'"'+ jQuery("input#searcher").val()+'"';
                $filter_title_update = true; // yep ##
            }
            
            // add category ? ##
            if ( $category > 0 ) {
                $filter_title = $filter_title + jQuery("select#category option:selected").text() + ' | ';
                if ( $filter_page_title.length > 0 ) { $filter_page_title = $filter_page_title+' + '; }
                $filter_page_title = $filter_page_title + jQuery("select#category option:selected").text();
                $filter_title_update = true; // yep ##
            }
            
            // add tag ? ##
            if ( $post_tag > 0 ) {
                $filter_title = $filter_title + jQuery("select#post_tag option:selected").text() + ' | ';
                if ( $filter_page_title.length > 0 ) { $filter_page_title = $filter_page_title+' + '; }
                $filter_page_title = $filter_page_title + jQuery("select#post_tag option:selected").text();
                $filter_title_update = true; // yep ##
            }
            
            
            
            if ( $filter_title_update === true ) {
                
                // get base title ##
                $title = q_ajax_filter.search+' | '+q_ajax_filter.site_name;

                // update title tag ##
                document.title = $filter_title+$title;

                // get base title ##
                $page_title = q_ajax_filter.search+': <span class="bold">'+$filter_page_title+'</span>';
                
                // update page title ##
                jQuery("body.ajax-filter h1.entry-title").html($page_title);
            
            // reset titles ##
            } else {
                
                // get base title ##
                $title = q_ajax_filter.search+' | '+q_ajax_filter.site_name;

                // update title tag ##
                document.title = $title;

                // get base title ##
                $page_title = q_ajax_filter.search;
                
                // update page title ##
                jQuery("body.ajax-filter h1.entry-title").html($page_title);
                
            }
            
        } ,

        loop: function ( node, tax ) {

            // Return an array of selected navigation classes.
            var arr = [];
            node.each(function () {
                if ( $(this).attr("id") == 'searcher' ) {
                    var id = "search="+$("#searcher").val()
                } else {
                    var id = $(this).data( tax );
                }
                if ( id ) arr.push(id);
            });
            return arr;

        },

        filter: function (arr) {

            var self = this;

            // Return all the relevant posts...
            $.ajax({
                
                url: AF_CONFIG['ajaxurl'],
                type: 'post',
                data: {
                    'action': 		'q_ajax_filter',
                    'filters': 		arr,
                    'post_type':        AF_CONFIG['post_type'],
                    'template':         AF_CONFIG['template'],
                    'order':            AF_CONFIG['order'],
                    'order_by':         AF_CONFIG['order_by'],
                    'queried_object':   AF_CONFIG['queried_object'],
                    'paged': 		AF_CONFIG['thisPage'],
                    '_ajax_nonce':      AF_CONFIG['nonce']
                },

                beforeSend: function () {
                    self.loader.fadeIn();
                    self.section.animate({
                        'opacity': .0
                    }, 'slow');
                    $("body.ajax-filter .pagination").hide("slow"); // show pagination ##
                    self.progress(33);
                },

                success: function (html) {
                    self.progress(80);
                    //alert('before');
                    self.section.empty();
                    self.section.append(html);
                    //alert('after');
                },

                complete: function () {
                    //console.log("ID: "+self.section.attr("id"));
                    $(".ajax-loaded").fadeIn();
                    $('html, body').animate({
                        scrollTop: $(self.section).offset().top -120
                    }, 500);
                    self.section.animate({
                        'opacity': 1
                    }, 'slow');
                    $(".pagination").show("slow"); // show pagination ##
                    self.progress(100);
                    self.loader.fadeOut();
                    self.title();
                    self.running = false;
                },

                error: function () {}

            });
        },
        
        clicker: function () {

            var self = this;
            
            // first load ##
            if ( self.first == true ) {
                
                //console.log( "first time.." );
                self.reset();
                self.first = false; // load normally from now ##
                
            }

            $('body').on('click', this.links, function (e) {

                if (self.running == false) {

                    self.first = false; // load normally from now ##

                    // Set to true to stop function chaining.
                    self.running = true;

                    // The following line resets the queried_object var so that in an ajax request it page's queried object is ignored.
                    AF_CONFIG['queried_object'] = 'af_null';

                    // Cache some of the DOM elements for re-use later in the method.
                    var link = $(this),
                        parent = link.parent('li'),
                        relation = link.attr('rel');

                    if (parent.length > 0) {
                        parent.toggleClass(self.selected_filters);
                        AF_CONFIG['thisPage'] = 1;
                    }

                    if (relation === 'next') {
                        AF_CONFIG['thisPage']++;
                    } else if (relation === 'prev') {
                        AF_CONFIG['thisPage']--;
                    } else if (link.hasClass('pagelink')) {
                        AF_CONFIG['thisPage'] = relation;
                    }

                    self.filter(self.selected());

                }

                e.preventDefault();

            });


            $('body').on('change', this.select, function (e) {

                if (self.running == false) {

                    self.first = false; // load normally from now ##

                    // Set to true to stop function chaining.
                    self.running = true;

                    // The following line resets the queried_object var so that in an ajax request it page's queried object is ignored.
                    AF_CONFIG['queried_object'] = 'af_null';

                    // remove all selected_filters from options in this <select> ##
                    $(this).find('option').removeClass(self.selected_filters);

                    // Cache some of the DOM elements for re-use later in the method.
                    var link = $(this),
                        parent = link.parent('select'),
                        relation = link.attr('rel');


                    $(this).find(':selected').toggleClass(self.selected_filters);
                    AF_CONFIG['thisPage'] = 1;

                    if (relation === 'next') {
                        AF_CONFIG['thisPage']++;
                    } else if (relation === 'prev') {
                        AF_CONFIG['thisPage']--;
                    } else if (link.hasClass('pagelink')) {
                        AF_CONFIG['thisPage'] = relation;
                    }

                    self.filter(self.selected());

                }

                e.preventDefault();

            });
            
        },
        
        reset: function () {
            
            // remove all other ".no-results" ##
            $(".no-results").remove();
            
            $("body.ajax-filter #ajax-filtered-section").append("<p class='no-results'></p>"); // add msg ##
            $(".no-results").html(q_ajax_filter.on_load_text).fadeIn();
            $("body.ajax-filter .ajax-loaded").hide(); // hide all results ##
            $("body.ajax-filter .pagination").hide(); // hide pagination ##
            
            $('html, body').animate({
                scrollTop: $("#ajax-filtered-section").offset().top -120
            }, 500);
            
        },
        
        init: function (opts) {

            // Set up the properties
            this.opts = opts;
            this.running = false;
            this.first = true; // load differently the first time ##
            this.loader = $(this.opts['loader']);
            this.section = $(this.opts['section']);
            this.links = this.opts['links'];
            this.select = this.opts['select'];
            this.progbar = $(this.opts['progbar']);
            this.selected_filters = this.opts['selected_filters'];

            // Run the methods.
            this.clicker();

        }
        
    };
    
    var af_filter = new AF_Filter({
        'loader': 			'#ajax-loader',
        'section': 			'#ajax-filtered-section',
        'links': 			'.ajax-filter-label, .paginationNav, .pagelink, #go',
        'select': 			'.ajax-select',
        'progbar': 			'#progbar',
        'selected_filters':             'filter-selected'
    });
    
    // only allow one filter per UL to be selected at once ##
    $("li.ajaxFilterItem").click( function() {
        $(this).parent("ul").find("li").not(this).each(function(){
            $(this).removeClass('filter-selected');
        });
    })
    
    // toggle placeholder text on search input ##
    var placeholder_search = jQuery('#searcher').attr('placeholder');
    jQuery('#searcher').focus(function(){
        jQuery(this).attr('placeholder','');
    });
    jQuery('#searcher').focusout(function(){
        jQuery(this).attr('placeholder', placeholder_search );
    });
    
    // reset search ##
    $(".reset").click(function(e) {

        // stop default action ##
        e.preventDefault();

        // update search passed variable ##
        $search_passed = false;

        // remove all "filter-selected" classes ##
        $("option.filter-selected, li.filter-selected").removeClass("filter-selected");

        // empty search ##
        //$("input#searcher").removeClass("filter-selected");
        $("input#searcher").val("");

        // reset all forms ##
        $("#ajax-filters select").each(function(){
            jQuery(this).find('option:first').attr('selected', 'selected'); // select first option ##
            jQuery(this).find("option").show(); // show all options ##
            jQuery(this).prop('selectedIndex',0);
        });

        // reset page title ##
        document.title = q_ajax_filter.search+' | '+q_ajax_filter.site_name; // update title tag ##
        $("body.ajax-filter h1.entry-title").html(q_ajax_filter.search); // h1 title ##
        
        // back to basics ##
        af_filter.reset();
        
        // reload search ##
        //$('#go')[0].click()

    });
    
    $("input#searcher").keypress(function(event) {
        if (event.which == 13) {
            event.preventDefault();
            $('#go')[0].click()
        }
    });
        
})(jQuery);