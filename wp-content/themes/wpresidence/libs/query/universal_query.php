<?php

// Register AJAX actions for universal property query
add_action( 'wp_ajax_nopriv_wpestate_universal_property_query', 'wpestate_universal_property_query' );
add_action( 'wp_ajax_wpestate_universal_property_query', 'wpestate_universal_property_query' );

if ( ! function_exists( 'wpestate_universal_property_query' ) ) {
    /**
     * Universal AJAX handler for all property queries across the theme.
     */
    function wpestate_universal_property_query() {
        
        // Step 1: Security check
        $nonce = isset( $_POST['security'] ) ? $_POST['security'] : '';
        if ( ! wp_verify_nonce( $nonce, 'wpestate_ajax_filtering' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            return;
        }
        
        // Step 2: Disable caching during query
        wp_suspend_cache_addition( true );
        
        // Step 3: Get query context (directory, map, agent, featured)
        $query_context = isset( $_POST['query_context'] ) ? sanitize_text_field( $_POST['query_context'] ) : 'directory';
        
        // Step 4: Build parameters array for wpestate_build_property_query
        $allowed_html = array();
        $params = array();
        
        // Basic parameters
        $params['paged'] = isset( $_POST['pagination'] ) ? intval( $_POST['pagination'] ) : 1;
        $params['posts_per_page'] = intval( wpresidence_get_option( 'wp_estate_prop_no', '' ) );
        $params['no_found_rows'] = false;
        $params['fields'] = '';
        
        // Order parameter
        if ( isset( $_POST['order'] ) ) {
            $params['order'] = intval( $_POST['order'] );
        }
        
        // Step 5: Parse taxonomy filters
        $taxonomy_map = array(
            'category_values' => 'category',
            'action_values'   => 'action',
            'city'            => 'city',
            'area'            => 'area',
            'county'          => 'county_state',
        );
        
        foreach ( $taxonomy_map as $request_key => $param_key ) {
            if ( isset( $_REQUEST[ $request_key ] ) && $_REQUEST[ $request_key ] !== '' && $_REQUEST[ $request_key ] !== 'all' ) {
                $params[ $param_key ] = sanitize_title( wp_kses( $_REQUEST[ $request_key ], $allowed_html ) );
            }
        }
        
        // Step 6: Parse status filter
        if ( isset( $_POST['status'] ) && $_POST['status'] !== '' ) {
            $status = html_entity_decode( esc_html( $_POST['status'] ), ENT_QUOTES );
            $params['status'] = sanitize_title( $status );
        }
        
        // Step 7: Parse keyword search
        if ( isset( $_POST['keyword'] ) ) {
            $params['keyword'] = sanitize_text_field( $_POST['keyword'] );
        }
        
        // Step 8: Parse feature checkboxes
        if ( isset( $_POST['all_checkers'] ) && $_POST['all_checkers'] !== '' ) {
            $features = array_filter( array_map( 'sanitize_title', explode( ',', wp_kses( $_POST['all_checkers'], $allowed_html ) ) ) );
            if ( ! empty( $features ) ) {
                $params['features'] = $features;
            }
        }
        
        // Step 9: Parse price filters
        if ( isset( $_POST['price_low'] ) ) {
            $params['price_min'] = floatval( $_POST['price_low'] );
        }
        if ( isset( $_POST['price_max'] ) ) {
            $params['price_max'] = floatval( $_POST['price_max'] );
        }
        
        // Step 10: Parse size filters
        if ( isset( $_POST['min_size'] ) ) {
            $params['size_min'] = wpestate_convert_measure( floatval( $_POST['min_size'] ) );
        }
        if ( isset( $_POST['max_size'] ) ) {
            $params['size_max'] = wpestate_convert_measure( floatval( $_POST['max_size'] ) );
        }
        
        // Step 11: Parse lot size filters
        if ( isset( $_POST['min_lot_size'] ) ) {
            $params['lot_size_min'] = wpestate_convert_measure( floatval( $_POST['min_lot_size'] ) );
        }
        if ( isset( $_POST['max_lot_size'] ) ) {
            $params['lot_size_max'] = wpestate_convert_measure( floatval( $_POST['max_lot_size'] ) );
        }
        
        // Step 12: Parse room filters
        $room_types = array( 'rooms', 'bedrooms', 'bathrooms' );
        foreach ( $room_types as $room_type ) {
            if ( isset( $_POST['min_' . $room_type] ) ) {
                $key = ( $room_type === 'bedrooms' ) ? 'beds_min' : 
                       ( $room_type === 'bathrooms' ) ? 'baths_min' : 'rooms_min';
                $params[$key] = floatval( $_POST['min_' . $room_type] );
            }
            if ( isset( $_POST['max_' . $room_type] ) ) {
                $key = ( $room_type === 'bedrooms' ) ? 'beds_max' : 
                       ( $room_type === 'bathrooms' ) ? 'baths_max' : 'rooms_max';
                $params[$key] = floatval( $_POST['max_' . $room_type] );
            }
        }
        
        // Step 13: Parse geolocation filters
        if ( isset( $_POST['geo_lat'], $_POST['geo_long'] ) && 'yes' === wpresidence_get_option( 'wp_estate_use_geo_location', '' ) && $_POST['geo_lat'] !== '' && $_POST['geo_long'] !== '' ) {
            $params['geo_lat'] = floatval( $_POST['geo_lat'] );
            $params['geo_long'] = floatval( $_POST['geo_long'] );
            $params['geo_rad'] = isset( $_POST['geo_rad'] ) ? floatval( $_POST['geo_rad'] ) : 0;
        }
        
        // Step 14: Context-specific modifications
        if ( $query_context === 'agent' && isset( $_POST['agent_id'] ) ) {
            $params['agent'] = intval( $_POST['agent_id'] );
        }
        
        if ( $query_context === 'featured' ) {
            $params['meta'] = array(
                array(
                    'key' => 'prop_featured',
                    'value' => 1,
                    'compare' => '=',
                    'type' => 'NUMERIC'
                )
            );
            $params['order'] = 0;
        }
        
        if ( $query_context === 'map' ) {
            $params['posts_per_page'] = 500;
            $params['fields'] = 'ids';
        }
        
        // Step 15: Build final WP_Query arguments from collected parameters
        $args = wpestate_build_property_query( $params );
        
        // Step 16: Run the query with optional custom ordering when order=0
        $prop_selection = wpestate_run_property_query( $params );
        
        // Step 16: Generate response based on context
        if ( $query_context === 'map' ) {
            // Return JSON data for map markers
            $markers = array();
            if ( $prop_selection->have_posts() ) {
                while ( $prop_selection->have_posts() ) {
                    $prop_selection->the_post();
                    $lat = get_post_meta( get_the_ID(), 'property_latitude', true );
                    $lng = get_post_meta( get_the_ID(), 'property_longitude', true );
                    
                    if ( ! empty( $lat ) && ! empty( $lng ) ) {
                        $markers[] = array(
                            'id' => get_the_ID(),
                            'lat' => floatval( $lat ),
                            'lng' => floatval( $lng ),
                            'title' => get_the_title(),
                            'price' => get_post_meta( get_the_ID(), 'property_price', true ),
                            'url' => get_permalink(),
                        );
                    }
                }
                wp_reset_postdata();
            }
            
            echo wp_json_encode( array(
                'markers' => $markers,
                'total' => count( $markers ),
            ) );
            
        } else {
            // Return HTML cards for directory, agent, featured contexts
            $postid = isset( $_POST['postid'] ) ? intval( $_POST['postid'] ) : 0;
            $wpestate_options = wpestate_page_details( $postid );
            
            ob_start();
            wpresidence_display_property_list_as_html( $prop_selection, $wpestate_options, 'shortcode_list' );
            $cards = ob_get_clean();
            
            echo wp_json_encode( array(
                'cards' => $cards,
                'no_results' => $prop_selection->found_posts,
                'context' => $query_context,
            ) );
        }
        
        // Step 17: Clean up
        wp_suspend_cache_addition( false );
        wp_die();
    }
}

?>