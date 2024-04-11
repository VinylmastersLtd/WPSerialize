<?php
/*
Plugin Name: SerializeWP
Description: SerializeWP is a versatile WordPress plugin meticulously crafted to simplify the administration of serial numbers and warranty details. Seamlessly compatible with JetEngine and various other custom post type plugins, SerializeWP effortlessly harmonizes within your WordPress environment, presenting a comprehensive solution for organizing and monitoring serial numbers. Equipped with a frontend search form empowered by AJAX technology, users can swiftly retrieve and display relevant information regarding serial numbers, ensuring a seamless and efficient user experience.
Version: 1.0
Author: Leejay Hall
*/

if (!defined('ABSPATH')) {
exit; // Exit if accessed directly.
}

// Register Custom Post Type for Serial Numbers
function sn_register_post_type() {
$args = array(
'public' => true, // Make the post type publicly accessible.
'label'  => 'Serial Numbers', // Set the label for the post type.
'supports' => array('title'), // Assuming the title is used for the Serial Number.
);
	
register_post_type('serial-numbers', $args); // Register the post type with the specified arguments.
}

add_action('init', 'sn_register_post_type'); // Hook into the init action to register the post type.

// Shortcode for the Serial Number Search Form
function sn_serial_number_form_shortcode() {
// Nonce for security
$nonce = wp_nonce_field('sn_verify_nonce', 'sn_nonce_field', true, false);
    
// Form HTML
$form = <<<HTML
<div id="sn-form-container">
<form id="sn-serial-number-form">
{$nonce}
<input type="text" id="sn-serial-number" name="serial-number" placeholder="Enter Serial Number" required>
<button type="submit">Check Warranty</button>
</form>
<div id="sn-loading-container" style="display: none;"> <!-- Hide loading container initially -->
<div class="skeleton-loader">
<div class="loader"></div>
<div class="loader"></div>
<div class="loader"></div>
<span class="loading-text">Loading...</span> <!-- Move the loading text outside the loaders -->
</div>
</div>
<div id="sn-search-results" style="display: none;"></div>
</div>
HTML;

return $form;
}
add_shortcode('serial_number_form', 'sn_serial_number_form_shortcode'); // Register shortcode to display the form.

// Enqueue CSS and JavaScript files
function sn_enqueue_scripts() {
// Get the modification time of the CSS file
$style_css_path = plugin_dir_path(__FILE__) . 'assets/css/style.css';
$style_css_version = filemtime($style_css_path); // Get the last modified time of the CSS file.

// Enqueue CSS file with modification time as version
wp_enqueue_style('sn-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), $style_css_version); // Enqueue CSS file.

// Get the modification time of the JavaScript file
$script_js_path = plugin_dir_path(__FILE__) . 'assets/js/script.js';
$script_js_version = filemtime($script_js_path); // Get the last modified time of the JavaScript file.

// Enqueue JavaScript file with modification time as version
wp_enqueue_script('sn-script', plugin_dir_url(__FILE__) . 'assets/js/script.js', array('jquery'), $script_js_version, true); // Enqueue JavaScript file.

// Pass AJAX URL to script.js
wp_add_inline_script('sn-script', 'var ajaxurl = "' . admin_url('admin-ajax.php') . '";'); // Pass the AJAX URL to the script.
}

add_action('wp_enqueue_scripts', 'sn_enqueue_scripts'); // Hook into the wp_enqueue_scripts action to enqueue scripts.

// AJAX Handler for Serial Number Search
require_once(plugin_dir_path(__FILE__) . 'includes/ajax.php'); // Include the AJAX handling functions.
add_action('wp_ajax_sn_search', 'sn_handle_serial_number_search'); // Hook into the AJAX action for authenticated users.
add_action('wp_ajax_nopriv_sn_search', 'sn_handle_serial_number_search'); // Hook into the AJAX action for non-authenticated users.
