=== Search & Filter via AJAX ===
Contributors: qlstudio
Tags: filter, search, AJAX, posts, taxonomies
Requires at least: 3
Tested up to: 3.8.1
Stable tag: 1.7.1
License: GPL

Filter posts by taxonomies or text search using AJAX to load results

== Description ==

Filter posts by taxonomies or text search using AJAX to load results.

Shortcode options allow the posts types, taxonomies, filter location and methods to be customized.

Please leave feedback here http://www.wp-support.co/view/categories/search-filter-via-ajax

Please do not use the Wordpress.org forum to report bugs, as we do not monitor or answer questions there.

== Installation ==

1. Upload the plugin to your plugins directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enjoy!

== Screenshots ==

No applicable screenshots

== Frequently Asked Questions ==

= How does this plugin create the AJAX Filters? =

You need to use the ajaxFilter shortcode - for example:

[ajaxFilter post_type="property" taxonomies="property_type, location, price, size" filter_position="horizontal" filter_type="select"]

= How can I style the AJAX content =

The plugin includes a very simple HTML template wrapped in an <article> tag, this can be replaced by copying the included file "templates/ajax-filter.php" to your active theme at:

THEME/library/templates/ajax-filter.php

This follows the file system structure used by all Q themes and plugins.

= What are the shortcode arguments? =

The shortcode accepts the following arguments:

- post_type ( post types seperated by commas )
- taxonomies ( taxonomies seperated by commas )
- filter_position ( "horizontal" OR "vertical" )
- filter_type ( "select" OR "list" )

== Changelog ==

= 1.7.1 = 

* .po file updates

= 1.7.0 =

* Initial working version

== Upgrade Notice ==

= 1.7.0 =

* Initial working version