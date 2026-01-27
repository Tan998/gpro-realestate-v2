<?php
/**
 * WpResidence Filter List Properties Function
 *
 * This file contains the function to generate a filtered list of properties
 * with a filter bar for the WpResidence theme. It handles arrays of IDs for taxonomies.
 *
 * @package WpResidence
 * @subpackage Shortcodes
 * @since 1.0.0
 *
 * @dependencies 
 * - wpestate_return_first_term()
 * - wpestate_return_filter_data()
 * - wpestate_filter_bar()
 * - wpestate_shortcode_build_list()
 *
 * Usage: [filter_list_properties category_ids="1,2,3" action_ids="4,5,6" city_ids="7,8,9" status_ids="10,11" features_ids="12,13" number="6" rownumber="3"]
 */

if (!function_exists("wpestate_filter_list_properties")):
function wpestate_filter_list_properties($attributes, $content = null) {
    // Extract and process taxonomy IDs
 
     if (is_tax()) {
        $current_term = get_queried_object();
        if ($current_term && isset($current_term->taxonomy)) {
            switch ($current_term->taxonomy) {
                case 'property_city':
                    $attributes['city_ids'] = isset($attributes['city_ids']) 
                        ? $attributes['city_ids'] . ',' . $current_term->term_id 
                        : $current_term->term_id;
                    break;
                case 'property_area':
                    $attributes['area_ids'] = isset($attributes['area_ids']) 
                        ? $attributes['area_ids'] . ',' . $current_term->term_id 
                        : $current_term->term_id;
                    break;
                case 'property_category':
                    $attributes['category_ids'] = isset($attributes['category_ids']) 
                        ? $attributes['category_ids'] . ',' . $current_term->term_id 
                        : $current_term->term_id;
                    break;
                case 'property_action_category':
                    $attributes['action_ids'] = isset($attributes['action_ids']) 
                        ? $attributes['action_ids'] . ',' . $current_term->term_id 
                        : $current_term->term_id;
                    break;
                case 'property_county_state':
                    $attributes['state_ids'] = isset($attributes['state_ids'])
                        ? $attributes['state_ids'] . ',' . $current_term->term_id
                        : $current_term->term_id;
                    break;
                case 'property_status':
                    $attributes['status_ids'] = isset($attributes['status_ids'])
                        ? $attributes['status_ids'] . ',' . $current_term->term_id
                        : $current_term->term_id;
                    break;
                case 'property_features':
                    $attributes['features_ids'] = isset($attributes['features_ids'])
                        ? $attributes['features_ids'] . ',' . $current_term->term_id
                        : $current_term->term_id;
                    break;

                 case 'property_features':
                    $attributes['features_ids'] = isset($attributes['features_ids']) 
                        ? $attributes['features_ids'] . ',' . $current_term->term_id 
                        : $current_term->term_id;
                    break;  
                    
                    
                 case 'property_status ':
                    $attributes['status_ids'] = isset($attributes['status_ids']) 
                        ? $attributes['status_ids'] . ',' . $current_term->term_id 
                        : $current_term->term_id;
                    break; 
            }
        }
    }

 



    $category_ids = isset($attributes['category_ids']) ? $attributes['category_ids'] : '';
    $action_ids = isset($attributes['action_ids']) ? $attributes['action_ids'] : '';
    $city_ids = isset($attributes['city_ids']) ? $attributes['city_ids'] : '';
    $area_ids = isset($attributes['area_ids']) ? $attributes['area_ids'] : '';
    $state_ids = isset($attributes['state_ids']) ? $attributes['state_ids'] : '';
    $status_ids = isset($attributes['status_ids']) ? $attributes['status_ids'] : '';
    $features_ids = isset($attributes['features_ids']) ? $attributes['features_ids'] : '';
    
    // Extract other attributes
    $sort_by = isset($attributes['sort_by']) ? $attributes['sort_by'] : '';
    $align = isset($attributes['align']) ? $attributes['align'] : '';
    $card_version = isset($attributes['card_version']) ? $attributes['card_version'] : '';
    $context = isset($attributes['context']) ? $attributes['context'] : '';





    // Get first terms for each taxonomy
    $city = wpestate_return_first_term($city_ids, 'property_city');
    $area = wpestate_return_first_term($area_ids, 'property_area');
    $category = wpestate_return_first_term($category_ids, 'property_category');
    $types = wpestate_return_first_term($action_ids, 'property_action_category');
    $county = wpestate_return_first_term($state_ids, 'property_county_state');
    $status = wpestate_return_first_term($status_ids, 'property_status');
    $features = wpestate_return_first_term($features_ids, 'property_features');

    // Build filter selection array
    $filter_selection = array(
        'city' => $city,
        'area' => $area,
        'types' => $types,
        'category' => $category,
        'county' => $county,
        'status' => $status,
        'features' => $features,
        'sort_by' => $sort_by
    );

    // Get filter data
    $filter_data = wpestate_return_filter_data($filter_selection);
    $filter_data['sort_by'] = $sort_by;
    $filter_data['listing_filter'] = 0;

    // Generate filter bar
   
    $return_string = wpestate_filter_bar($filter_data);

    // Set type for shortcode
    $attributes['type'] = 'properties';



    // Build wrapper for property list
    $return_string .= '<div class="wpestate_filter_list_properties_wrapper row" 
                            data-ishortcode="1" 
                            data-context="'.esc_attr($context).'"
                            data-number="' . esc_attr($attributes['number']) . '"  
                            data-rownumber="' . esc_attr($attributes['rownumber']) . '" 
                            data-card_version="' . esc_attr($card_version) . '" 
                            data-align="' . esc_attr($align) . '">';
    $return_string .= wpestate_shortcode_build_list($attributes);
    $return_string .= '<div class="spinner" id="listing_loader2">
                           <div class="new_prelader"></div>
                       </div>';
    $return_string .= '</div>';

    return $return_string;
}
endif;




/**
 * WpResidence Shortcode Build List Function
 *
 * This file contains the function to build and display a filtered list of properties
 * for the WpResidence theme's filter list properties shortcode.
 *
 * @package WpResidence
 * @subpackage Shortcodes
 * @since 1.0.0
 *
 * @dependencies 
 * - wpestate_prepare_arguments_shortcode()
 * - wpestate_recent_posts_shortocodes_create_arg()
 * - wpestate_add_global_details_transient()
 * - wpestate_request_transient_cache()
 * - wpestate_return_filtered_query()
 * - wpresidence_display_property_list_as_html()
 * - wpestate_set_transient_cache()
 * - wpestate_html_compress()
 *
 * Usage: [filter_list_properties category="residential" action="sale" city="New York" status_ids="10,11" features_ids="12,13" number="6" rownumber="3"]
 */

 if (!function_exists('wpestate_shortcode_build_list')):
    function wpestate_shortcode_build_list($attributes) {
        // Initialize variables
        $return_string = '';
        $orderby = 'meta_value';
        $type = 'estate_property';
        $featured_first = 'no';

    

        // Prepare shortcode arguments and ensure status and features are forwarded
        $shortcode_arguments = wpestate_prepare_arguments_shortcode($attributes);

        // Pass through status and features IDs for proper taxonomy filtering
        if (!empty($attributes['status_ids'])) {
            $shortcode_arguments['status'] = sanitize_text_field($attributes['status_ids']);
        }

        if (!empty($attributes['features_ids'])) {
            $shortcode_arguments['features'] = sanitize_text_field($attributes['features_ids']);
        }

        $args = wpestate_recent_posts_shortocodes_create_arg($shortcode_arguments);

        // Extract and sanitize attributes
        $order = isset($shortcode_arguments['sort_by']) ? intval($shortcode_arguments['sort_by']) : 0;
        $show_featured_only = isset($attributes['show_featured_only']) ? sanitize_text_field($attributes['show_featured_only']) : '';
        $control_terms_id = isset($attributes['control_terms_id']) ? sanitize_text_field($attributes['control_terms_id']) : '';
        $featured_first = isset($attributes['featured_first']) ? sanitize_text_field($attributes['featured_first']) : 'no';
        $random_pick = isset($attributes['$random_pick']) ? sanitize_text_field($attributes['$random_pick']) : '';
        $post_number_total = isset($attributes['number']) ? intval($attributes['number']) : 0;
        $row_number = isset($attributes['rownumber']) ? intval($attributes['rownumber']) : 0;

        // Extract taxonomy terms from shortcode arguments
        $taxonomy_terms = array(
            'category' => isset($shortcode_arguments['category']) ? sanitize_text_field($shortcode_arguments['category']) : '',
            'action' => isset($shortcode_arguments['action']) ? sanitize_text_field($shortcode_arguments['action']) : '',
            'city' => isset($shortcode_arguments['city']) ? sanitize_text_field($shortcode_arguments['city']) : '',
            'area' => isset($shortcode_arguments['area']) ? sanitize_text_field($shortcode_arguments['area']) : '',
            'state' => isset($shortcode_arguments['state']) ? sanitize_text_field($shortcode_arguments['state']) : '',
            'status' => isset($shortcode_arguments['status']) ? sanitize_text_field($shortcode_arguments['status']) : '',
            'features' => isset($shortcode_arguments['features']) ? sanitize_text_field($shortcode_arguments['features']) : '',
        );

        // Generate transient name
        $transient_name = 'wpestate_properties_list_filter_query_' . $type . '_' . 
                          implode('_', $taxonomy_terms) . '_' . $row_number . '_' . 
                          $post_number_total . '_' . $featured_first;
        $transient_name = wpestate_add_global_details_transient($transient_name);

        // Try to get cached template
        $templates = false;
        if (function_exists('wpestate_request_transient_cache')) {
            $templates = wpestate_request_transient_cache($transient_name);
        }

        // If no cached template or random pick is requested, generate new content
        if ($templates === false || $random_pick == 'yes') {
            $recent_posts = wpestate_return_filtered_query($args, $featured_first);
            
            ob_start();
            $pagination_parameters = array(
                'display' => 'yes',
                'order' => $order,
                'paged' => 1,
            );
            wpresidence_display_property_list_as_html($recent_posts, '', 'ajax_list_shortcode', $attributes, $pagination_parameters);
            $templates = ob_get_contents();
            ob_end_clean();

            // Cache the generated content if not using random order
            if ($orderby !== 'rand' && function_exists('wpestate_set_transient_cache')) {
                wpestate_set_transient_cache($transient_name, wpestate_html_compress($templates), 60 * 60 * 4);
            }

            wp_reset_query();
        }

        // Append templates to return string
        $return_string .= $templates;

        return $return_string;
    }
endif;