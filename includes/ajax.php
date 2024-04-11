<?php
// AJAX Handler for Serial Number Search
function sn_handle_serial_number_search() {
    // Check for nonce security
    check_ajax_referer('sn_verify_nonce', 'sn_nonce_field');

    // Get date format setting from WordPress general settings
    $date_format = get_option('date_format');

    // Sanitize serial number input
    $serial_number = sanitize_text_field($_POST['serial-number']);

    // Query for the entered serial number
    $args = array(
        'post_type'      => 'serial-numbers',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array(
                'key'     => 'serial-number',
                'value'   => $serial_number,
                'compare' => '=',
            ),
        ),
    );

    $query = new WP_Query($args);

    // Check if the query has posts
    if ($query->have_posts()) {
        $query->the_post();
        $post_id = get_the_ID();

        // Retrieve warranty information and additional user data
        $user_info = array();
        $meta_fields = get_post_meta($post_id);
        foreach ($meta_fields as $key => $value) {
            // Exclude unnecessary meta fields
            if ($key !== '_edit_lock' && $key !== '_edit_last') {
                $user_info['meta_data'][$key] = $value[0];
            }
        }

        // Calculate warranty status and detailed message
        $expiry_date = $user_info['meta_data']['expiry-date'];
        $shipped_date = $user_info['meta_data']['shipped'];
        $serial_number = $user_info['meta_data']['serial-number'];
        $warranty_data = get_warranty_status($expiry_date, $shipped_date, $serial_number);

        // Include detailed message in user information array
        $user_info['detailed_message'] = $warranty_data['message'];
        
        // Format dates based on date format setting
        $user_info['meta_data']['expiry-date'] = date_i18n($date_format, strtotime($expiry_date));
        $user_info['meta_data']['shipped'] = date_i18n($date_format, strtotime($shipped_date));

        // Send JSON response with user information
        wp_send_json_success($user_info);
    } else {
        // Send JSON response with error message
        wp_send_json_error(array('message' => 'Serial number not found.'));
    }

    // Terminate script execution
    wp_die();
}


// Function to calculate warranty status and detailed message
function get_warranty_status($expiry_date, $shipped_date, $serial_number) {
    // Get date format setting from WordPress general settings
    $date_format = get_option('date_format');

    // Format the expiry date based on the WordPress date format settings
    $expiry_date_formatted = date_i18n($date_format, strtotime($expiry_date));

    // Calculate remaining time in seconds
    $remaining_time = strtotime($expiry_date) - strtotime(date('Y-m-d'));

    // Initialize variables to store remaining time
    $remaining_months = floor($remaining_time / (30 * 24 * 60 * 60));
    $remaining_days = floor($remaining_time / (24 * 60 * 60));
    $remaining_hours = floor($remaining_time / (60 * 60));

    // Initialize variable to store remaining time message
    $remaining_time_message = '';

    // Determine the remaining time format based on the remaining time
    if ($remaining_months > 0) {
        $remaining_time_message = "$remaining_months months and $remaining_days days";
    } elseif ($remaining_days > 0) {
        $remaining_time_message = "$remaining_days days";
    } else {
        $remaining_time_message = "$remaining_hours hours";
    }

    // Initialize warranty message variables
    $detailed_message = '';

    if ($remaining_time <= 0) {
        // Warranty has expired
        $detailed_message = "<span class='expired-message'>
The warranty for serial number $serial_number, which provided coverage for defects in materials or craftsmanship during normal usage, expired on $expiry_date_formatted. This expiration means that any protections against defects under the initial 365-day warranty period from the date of purchase are no longer valid. Please be aware that Vinylmasters Limited's warranty specifically covers material or craftsmanship defects and does not cover issues, malfunctions, or damages resulting from other causes";
    } else {
        // Warranty is still valid
        $detailed_message = "<span class='valid-message'>Great news! The warranty for serial number $serial_number remains active until $expiry_date_formatted, with $remaining_time_message left on your coverage. This coverage ensures protection against any defects in materials or craftsmanship, assuming normal usage conditions. Vinylmasters Limited provides a 365-day warranty from the date of purchase. It's important to remember that this warranty specifically addresses material or craftsmanship defects and does not cover issues, malfunctions, or damages that stem from other sources.";
    }

    return array(
        'message' => $detailed_message
    );
}


// Hook into the AJAX action for authenticated and non-authenticated users
add_action('wp_ajax_sn_search', 'sn_handle_serial_number_search');
add_action('wp_ajax_nopriv_sn_search', 'sn_handle_serial_number_search');
?>
