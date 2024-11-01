<?php
/**
 * Plugin Name: Tofugear Omnitech
 * Plugin URI: https://blog.tofugear.com/omnitech-plugin-wordpress
 * Description: A WordPress plugin to integrate Tofugear Omnitech; A fully customized omni-channel retailing platform.
 * Version: 1.2.3
 * Author: Tofugear
 * Author URI: http://www.tofugear.com
 * License: GPL2
 */

require dirname( __FILE__ ) . '/lib/tofugear-omnitech-news.php';
require dirname( __FILE__ ) . '/lib/tofugear-omnitech-image-size.php';

// Register and define the settings
if( is_admin() ) {
	add_action('admin_menu', 'create_tofugear_settings');
	add_action('after_setup_theme', 'tgom_add_image_sizes');
}

add_action('init', 'tgom_setup');
add_filter('query_vars', 'tgom_onQueryVars');
add_action('parse_request', 'tgom_onParseRequest');
