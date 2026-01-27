<?php
/**MILLDONE
 * Template Name: Properties list half
 * src: page-templates\property_list_half.php
 * This template handles the display of property listings with a half map layout in the WpResidence theme.
 *
 * @package WpResidence
 * @subpackage PropertyListing
 * @since WpResidence 1.0
 */

// Check for WpResidence Core Plugin
if (!function_exists('wpestate_residence_functionality')) {
    wp_die(esc_html__('This page requires the WpResidence Core Plugin. Please activate it from the plugins menu!', 'wpresidence'));
}

get_header();
$wpestate_options = get_query_var('wpestate_options');

// Initialize variables
$wpestate_currency       = esc_html(wpresidence_get_option('wp_estate_currency_symbol', ''));
$where_currency          = esc_html(wpresidence_get_option('wp_estate_where_currency_symbol', ''));
$wpestate_prop_unit      = esc_html(wpresidence_get_option('wp_estate_prop_unit', ''));
$property_number_to_show = intval(wpresidence_get_option('wp_estate_prop_no', ''));
$curent_fav              = wpestate_return_favorite_listings_per_user();
$transient_appendix      = '';

if (get_post_meta($post->ID, 'show_filter_area', true) === 'yes') {
    $transient_appendix .= '_show_featured';
}

$show_compare = 1;
$align_class = '';
$prop_unit_class = '';
if ($wpestate_prop_unit == 'list') {
    $prop_unit_class = "ajax12";
    $align_class = 'the_list_view';
}

// Read the property list order
$order = get_post_meta($post->ID, 'listing_filter', true);
if (isset($_GET['order']) && is_numeric($_GET['order'])) {
    $order = intval($_GET['order']);
}

// Read the property list page
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
if (is_front_page()) {
    $paged = (get_query_var('page')) ? get_query_var('page') : 1;
}

// Create temp arguments to be transformed into wp_query arguments
$temp_arguments     = wpresidence_create_arguments_for_property_list($post->ID);
$arguments_array    = wpestate_create_query_arguments($temp_arguments);
$args               = $arguments_array['query_arguments'];
$transient_appendix = $arguments_array['transient_appendix'];

// Attempt to get cached property selection
$prop_selection = wpestate_request_transient_cache('wpestate_prop_list' . $transient_appendix);
if ($prop_selection === false) {
    if ($order == 0) {
        $prop_selection = wpestate_return_filtered_by_order($args);
    } else {
        $prop_selection = new WP_Query($args);
    }
    wpestate_set_transient_cache('wpestate_prop_list' . $transient_appendix, $prop_selection, 60 * 60 * 4);
}

// Include template for property list display
include(locate_template('templates/properties_list_templates/property_list_page_half_map_core.php'));

// Handle Maps script if enqueued
if (wp_script_is('googlecode_regular', 'enqueued')) {
    $mapargs                    = $args;
    $max_pins                   = intval(wpresidence_get_option('wp_estate_map_max_pins'));
    $mapargs['posts_per_page']  = $max_pins;
    $mapargs['offset']          = ($paged - 1) * $property_number_to_show;
    $mapargs['fields']          = 'ids';

    $transient_appendix .= '_half_map_maxpins_' . $max_pins . '_offset_' . ($paged - 1) * $property_number_to_show;
    $selected_pins      = wpestate_listing_pins($transient_appendix, 1, $mapargs, 1); //call the new pins
    wp_localize_script('googlecode_regular', 'googlecode_regular_vars2',
        array('markers2' => $selected_pins)
    );
}

get_footer();
?>