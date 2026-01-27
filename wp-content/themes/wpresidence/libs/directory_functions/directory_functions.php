<?php

add_action( 'wp_ajax_nopriv_wpestate_classic_ondemand_directory_query', 'wpestate_classic_ondemand_directory_query' );
add_action( 'wp_ajax_wpestate_classic_ondemand_directory_query', 'wpestate_classic_ondemand_directory_query' );


add_action( 'wp_ajax_nopriv_wpestate_classic_ondemand_directory', 'wpestate_classic_ondemand_directory' );
add_action( 'wp_ajax_wpestate_classic_ondemand_directory', 'wpestate_classic_ondemand_directory' );

if ( ! function_exists( 'wpestate_directory_get_request_post_id' ) ) :

    /**
     * Extracts the directory page ID from AJAX requests.
     *
     * Both `postid` and `postID` are used throughout the theme, so this helper
     * centralises the fallback logic and guarantees we always return a valid
     * integer.
     *
     * @return int Directory post ID or 0 when missing.
     */
    function wpestate_directory_get_request_post_id() {

        // Step 1: Prefer the lowercase `postid` parameter when present.
        if ( isset( $_POST['postid'] ) ) {
            return intval( $_POST['postid'] );
        }

        // Step 2: Accept the camelCase variant used by some older scripts.
        if ( isset( $_POST['postID'] ) ) {
            return intval( $_POST['postID'] );
        }

        // Step 3: Default to 0 to avoid undefined index notices downstream.
        return 0;
    }

endif; // end wpestate_directory_get_request_post_id

if ( ! function_exists( 'wpestate_directory_normalize_range' ) ) :

    /**
     * Compares a requested numeric range with its defaults and clears matches.
     *
     * Directory sliders often submit their full range even when the visitor
     * hasn't narrowed the search. This helper keeps the query lean by
     * returning zeroed values whenever both ends are still equal to their
     * configured defaults.
     *
     * @param float $value_min    Requested minimum value.
     * @param float $value_max    Requested maximum value.
     * @param float $default_min  Slider default minimum value.
     * @param float $default_max  Slider default maximum value.
     * @param float $epsilon      Tolerance for float comparisons.
     * @return array              Two-item array with normalised min and max.
     */
    function wpestate_directory_normalize_range( $value_min, $value_max, $default_min, $default_max, $epsilon = 0.0001 ) {

        // Step 1: Cast everything to floats so comparisons behave predictably.
        $value_min   = floatval( $value_min );
        $value_max   = floatval( $value_max );
        $default_min = floatval( $default_min );
        $default_max = floatval( $default_max );

        // Step 2: Only run the matching logic when at least one bound is set.
        if ( $value_min || $value_max ) {
            $min_matches_default = ( $value_min <= 0 && $default_min <= 0 );
            if ( ! $min_matches_default && $default_min > 0 ) {
                $min_matches_default = ( abs( $value_min - $default_min ) <= $epsilon );
            }

            $max_matches_default = ( $value_max <= 0 && $default_max <= 0 );
            if ( ! $max_matches_default && $default_max > 0 ) {
                $max_matches_default = ( $value_max >= ( $default_max - $epsilon ) );
            }

            // Step 3: Clear both values whenever the request still spans
            // the entire slider range.
            if ( $min_matches_default && $max_matches_default ) {
                $value_min = 0;
                $value_max = 0;
            }
        }

        return array( $value_min, $value_max );
    }

endif; // end wpestate_directory_normalize_range

if ( ! function_exists( 'wpestate_directory_normalize_price_range' ) ) :

    /**
     * Normalises the requested price range by removing currency effects and
     * default slider bounds.
     *
     * @param float $price_min Requested minimum price.
     * @param float $price_max Requested maximum price.
     * @param float $epsilon   Tolerance for float comparisons.
     * @return array           Two-item array with sanitised min and max.
     */
    function wpestate_directory_normalize_price_range( $price_min, $price_max, $epsilon = 0.0001 ) {

        // Step 1: Cast request values to floats for consistent math.
        $price_min = floatval( $price_min );
        $price_max = floatval( $price_max );

        // Step 2: Skip early when no price filters were provided.
        if ( ! $price_min && ! $price_max ) {
            return array( 0, 0 );
        }

        // Step 3: Convert from the user-selected currency back to the base
        // currency so comparisons against stored meta values are accurate.
        $custom_fields = wpresidence_get_option( 'wp_estate_multi_curr', '' );
        if ( ! empty( $custom_fields ) && isset( $_COOKIE['my_custom_curr_pos'] ) ) {
            $index = intval( $_COOKIE['my_custom_curr_pos'] );
            if ( $index >= 0 && isset( $custom_fields[ $index ][2] ) ) {
                $exchange_rate = floatval( $custom_fields[ $index ][2] );
                if ( $exchange_rate > 0 ) {
                    if ( $price_min ) {
                        $price_min = $price_min / $exchange_rate;
                    }
                    if ( $price_max ) {
                        $price_max = $price_max / $exchange_rate;
                    }
                }
            }
        }

        // Step 4: Compare the converted values against the global slider
        // defaults and clear them when the request still spans the full range.
        $default_price_min = floatval( wpresidence_get_option( 'wp_estate_show_slider_min_price', '' ) );
        $default_price_max = floatval( wpresidence_get_option( 'wp_estate_show_slider_max_price', '' ) );

        return wpestate_directory_normalize_range( $price_min, $price_max, $default_price_min, $default_price_max, $epsilon );
    }

endif; // end wpestate_directory_normalize_price_range

if ( ! function_exists( 'wpestate_classic_ondemand_directory_query' ) ) :

    function wpestate_classic_ondemand_directory_query() {

        // Step 1: Security check
        $nonce = isset( $_POST['security'] ) ? $_POST['security'] : '';
        if ( ! wp_verify_nonce( $nonce, 'wpestate_ajax_filtering' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
            return;
        }



        
        // Step 2: temporarily disable cache additions and collect page options.
        wp_suspend_cache_addition( false );

        $allowed_html      = array();
        $directory_post_id = wpestate_directory_get_request_post_id();
        $wpestate_options  = wpestate_page_details( $directory_post_id );

        // Step 3: seed the parameter array with pagination defaults.
        $params = array(
            'paged'          => isset( $_POST['pagination'] ) ? intval( $_POST['pagination'] ) : 1,
            'posts_per_page' => intval( wpresidence_get_option( 'wp_estate_prop_no', '' ) ),
            'no_found_rows'  => false,
            'fields'         => '',
        );

        // Step 4: include ordering code when provided.
        if ( isset( $_POST['order'] ) ) {
            $params['order'] = intval( $_POST['order'] );
        }

        // Step 5: map basic location and taxonomy filters from the request.
        $map = array(
            'category_values' => 'category',
            'action_values'   => 'action',
            'city'            => 'city',
            'area'            => 'area',
            'county'          => 'county_state',
        );
        foreach ( $map as $request_key => $param_key ) {
            if ( isset( $_REQUEST[ $request_key ] ) && $_REQUEST[ $request_key ] !== '' && $_REQUEST[ $request_key ] !== 'all' ) {
                $params[ $param_key ] = sanitize_title( wp_kses( $_REQUEST[ $request_key ], $allowed_html ) );
            }
        }

        // Step 6: add property status when provided.
        if ( isset( $_POST['status'] ) && $_POST['status'] !== '' ) {
            $status           = html_entity_decode( esc_html( $_POST['status'] ), ENT_QUOTES );
            $params['status'] = sanitize_title( $status );
        }

        // Step 7: capture keyword searches.
        if ( isset( $_POST['keyword'] ) ) {
            $params['keyword'] = sanitize_text_field( $_POST['keyword'] );
        }

        // Step 8: parse selected feature checkboxes.
        if ( isset( $_POST['all_checkers'] ) && $_POST['all_checkers'] !== '' ) {
            $features = array_filter( array_map( 'sanitize_title', explode( ',', wp_kses( $_POST['all_checkers'], $allowed_html ) ) ) );
            if ( ! empty( $features ) ) {
                $params['features'] = $features;
            }
        }

        // Step 9: normalise the price range and drop default values.
        $epsilon = 0.0001;
        list( $price_min, $price_max ) = wpestate_directory_normalize_price_range(
            isset( $_POST['price_low'] ) ? $_POST['price_low'] : 0,
            isset( $_POST['price_max'] ) ? $_POST['price_max'] : 0,
            $epsilon
        );
        if ( $price_min > 0 ) {
            $params['price_min'] = $price_min;
        }
        if ( $price_max > 0 ) {
            $params['price_max'] = $price_max;
        }

        // Step 10: normalise size and lot size sliders to avoid redundant joins.
        $default_size_min = wpestate_convert_measure( floatval( get_post_meta( $directory_post_id, 'dir_min_size', true ) ), 1 );
        $default_size_max = wpestate_convert_measure( floatval( get_post_meta( $directory_post_id, 'dir_max_size', true ) ), 1 );
        $size_min_request = isset( $_POST['min_size'] ) ? wpestate_convert_measure( floatval( $_POST['min_size'] ), 1 ) : 0;
        $size_max_request = isset( $_POST['max_size'] ) ? wpestate_convert_measure( floatval( $_POST['max_size'] ), 1 ) : 0;
        list( $size_min, $size_max ) = wpestate_directory_normalize_range( $size_min_request, $size_max_request, $default_size_min, $default_size_max, $epsilon );
        if ( $size_min > 0 ) {
            $params['size_min'] = $size_min;
        }
        if ( $size_max > 0 ) {
            $params['size_max'] = $size_max;
        }

        $default_lot_min = wpestate_convert_measure( floatval( get_post_meta( $directory_post_id, 'dir_min_lot_size', true ) ), 1 );
        $default_lot_max = wpestate_convert_measure( floatval( get_post_meta( $directory_post_id, 'dir_max_lot_size', true ) ), 1 );
        $lot_min_request = isset( $_POST['min_lot_size'] ) ? wpestate_convert_measure( floatval( $_POST['min_lot_size'] ), 1 ) : 0;
        $lot_max_request = isset( $_POST['max_lot_size'] ) ? wpestate_convert_measure( floatval( $_POST['max_lot_size'] ), 1 ) : 0;
        list( $lot_size_min, $lot_size_max ) = wpestate_directory_normalize_range( $lot_min_request, $lot_max_request, $default_lot_min, $default_lot_max, $epsilon );
        if ( $lot_size_min > 0 ) {
            $params['lot_size_min'] = $lot_size_min;
        }
        if ( $lot_size_max > 0 ) {
            $params['lot_size_max'] = $lot_size_max;
        }

        // Step 11: normalise room, bedroom, and bathroom sliders.
        $default_rooms_min = floatval( get_post_meta( $directory_post_id, 'dir_rooms_min', true ) );
        $default_rooms_max = floatval( get_post_meta( $directory_post_id, 'dir_rooms_max', true ) );
        $rooms_min_request = isset( $_POST['min_rooms'] ) ? floatval( $_POST['min_rooms'] ) : 0;
        $rooms_max_request = isset( $_POST['max_rooms'] ) ? floatval( $_POST['max_rooms'] ) : 0;
        list( $rooms_min, $rooms_max ) = wpestate_directory_normalize_range( $rooms_min_request, $rooms_max_request, $default_rooms_min, $default_rooms_max, $epsilon );
        if ( $rooms_min > 0 ) {
            $params['rooms_min'] = $rooms_min;
        }
        if ( $rooms_max > 0 ) {
            $params['rooms_max'] = $rooms_max;
        }

        $default_beds_min = floatval( get_post_meta( $directory_post_id, 'dir_bedrooms_min', true ) );
        $default_beds_max = floatval( get_post_meta( $directory_post_id, 'dir_bedrooms_max', true ) );
        $beds_min_request  = isset( $_POST['min_bedrooms'] ) ? floatval( $_POST['min_bedrooms'] ) : 0;
        $beds_max_request  = isset( $_POST['max_bedrooms'] ) ? floatval( $_POST['max_bedrooms'] ) : 0;
        list( $beds_min, $beds_max ) = wpestate_directory_normalize_range( $beds_min_request, $beds_max_request, $default_beds_min, $default_beds_max, $epsilon );
        if ( $beds_min > 0 ) {
            $params['beds_min'] = $beds_min;
        }
        if ( $beds_max > 0 ) {
            $params['beds_max'] = $beds_max;
        }

        $default_baths_min = floatval( get_post_meta( $directory_post_id, 'dir_bathrooms_min', true ) );
        $default_baths_max = floatval( get_post_meta( $directory_post_id, 'dir_bathrooms_max', true ) );
        $baths_min_request = isset( $_POST['min_bathrooms'] ) ? floatval( $_POST['min_bathrooms'] ) : 0;
        $baths_max_request = isset( $_POST['max_bathrooms'] ) ? floatval( $_POST['max_bathrooms'] ) : 0;
        list( $baths_min, $baths_max ) = wpestate_directory_normalize_range( $baths_min_request, $baths_max_request, $default_baths_min, $default_baths_max, $epsilon );
        if ( $baths_min > 0 ) {
            $params['baths_min'] = $baths_min;
        }
        if ( $baths_max > 0 ) {
            $params['baths_max'] = $baths_max;
        }

        // Step 12: optionally apply geolocation radius filters.
        if ( isset( $_POST['geo_lat'], $_POST['geo_long'] ) && 'yes' === wpresidence_get_option( 'wp_estate_use_geo_location', '' ) && $_POST['geo_lat'] !== '' && $_POST['geo_long'] !== '' ) {
            $params['geo_lat']  = floatval( $_POST['geo_lat'] );
            $params['geo_long'] = floatval( $_POST['geo_long'] );
            $params['geo_rad']  = isset( $_POST['geo_rad'] ) ? floatval( $_POST['geo_rad'] ) : 0;
        }

        // Step 13: build final WP_Query arguments from collected parameters.
        $args = wpestate_build_property_query( $params );



        // Step 14: run the query with optional custom ordering when order=0.
        $prop_selection = wpestate_run_property_query( $params );



        // Step 15: render property cards and respond with JSON payload.
        ob_start();
        wpresidence_display_property_list_as_html( $prop_selection, $wpestate_options, 'shortcode_list' );
        $cards = ob_get_clean();

        echo wp_json_encode(
            array(
                'args'       => $args,
                'cards'      => $cards,
                'no_results' => $prop_selection->found_posts,
            )
        );

        // Step 16: restore cache behaviour and end request.
        wp_suspend_cache_addition( false );
        wp_die();
    }

endif; // end ajax_filter_listings

if ( ! function_exists( 'wpestate_classic_ondemand_directory' ) ) :

    function wpestate_classic_ondemand_directory() {

        wp_suspend_cache_addition( false );

        $allowed_html      = array();
        $directory_post_id = wpestate_directory_get_request_post_id();

        $wpestate_options = wpestate_page_details( $directory_post_id );
        $type_name        = 'category_values';
        $type_name_value  = wp_kses( $_REQUEST[ $type_name ], $allowed_html );
        $action_name      = 'action_values';
        if ( isset( $_REQUEST[ $action_name ] ) ) {
            $action_name_value = wp_kses( $_REQUEST[ $action_name ], $allowed_html );
        } else {
            $action_name_value = '';
        }
        $categ_array = '';
        if ( 'all' !== $type_name_value && '' !== $type_name_value ) {
            $taxcateg_include = array();
            $taxcateg_include = sanitize_title( wp_kses( $type_name_value, $allowed_html ) );

            $categ_array = array(
                'taxonomy' => 'property_category',
                'field'    => 'slug',
                'terms'    => $taxcateg_include,
            );
        }

        $action_array = '';
        if ( 'all' !== $action_name_value && '' !== $action_name_value ) {
            $taxaction_include = array();
            $taxaction_include = sanitize_title( wp_kses( $action_name_value, $allowed_html ) );

            $action_array = array(
                'taxonomy' => 'property_action_category',
                'field'    => 'slug',
                'terms'    => $taxaction_include,
            );
        }

        $city_array = '';
        if ( isset( $_REQUEST['city_values'] ) && 'all' !== $_REQUEST['city_values'] && '' !== $_REQUEST['city_values'] ) {
            $taxcity[] = sanitize_title( wp_kses( $_REQUEST['city_values'], $allowed_html ) );
            $city_array = array(
                'taxonomy' => 'property_city',
                'field'    => 'slug',
                'terms'    => $taxcity,
            );
        }

        $area_array = '';
        if ( isset( $_REQUEST['area_values'] ) && 'all' !== $_REQUEST['area_values'] && '' !== $_REQUEST['area_values'] ) {
            $taxarea[] = sanitize_title( wp_kses( $_REQUEST['area_values'], $allowed_html ) );
            $area_array = array(
                'taxonomy' => 'property_area',
                'field'    => 'slug',
                'terms'    => $taxarea,
            );
        }

        $county_array = '';
        if ( isset( $_REQUEST['county_values'] ) && 'all' !== $_REQUEST['county_values'] && '' !== $_REQUEST['county_values'] ) {
            $taxarea[] = sanitize_title( wp_kses( $_REQUEST['county_values'], $allowed_html ) );
            $county_array = array(
                'taxonomy' => 'property_county_state',
                'field'    => 'slug',
                'terms'    => $taxarea,
            );
        }

        $pagination = intval( $_POST['pagination'] );

        $price_low = '';
        if ( isset( $_POST['price_low'] ) ) {
            $price_low = floatval( $_POST['price_low'] );
        }

        $price_max = '';
        if ( isset( $_POST['price_max'] ) ) {
            $price_max = floatval( $_POST['price_max'] );
        }

        $min_size = '';
        if ( isset( $_POST['min_size'] ) ) {
            $min_size = wpestate_convert_measure( floatval( $_POST['min_size'] ) );
        }

        $max_size = '';
        if ( isset( $_POST['max_size'] ) ) {
            $max_size = wpestate_convert_measure( floatval( $_POST['max_size'] ) );
        }

        $min_lot_size = '';
        if ( isset( $_POST['min_lot_size'] ) ) {
            $min_lot_size = wpestate_convert_measure( floatval( $_POST['min_lot_size'] ) );
        }

        $max_lot_size = '';
        if ( isset( $_POST['max_lot_size'] ) ) {
            $max_lot_size = wpestate_convert_measure( floatval( $_POST['max_lot_size'] ) );
        }

        $min_rooms = '';
        if ( isset( $_POST['min_rooms'] ) ) {
            $min_rooms = floatval( $_POST['min_rooms'] );
        }

        $max_rooms = '';
        if ( isset( $_POST['max_rooms'] ) ) {
            $max_rooms = floatval( $_POST['max_rooms'] );
        }

        $min_bedrooms = '';
        if ( isset( $_POST['min_bedrooms'] ) ) {
            $min_bedrooms = floatval( $_POST['min_bedrooms'] );
        }

        $max_bedrooms = '';
        if ( isset( $_POST['max_bedrooms'] ) ) {
            $max_bedrooms = floatval( $_POST['max_bedrooms'] );
        }

        $min_bathrooms = '';
        if ( isset( $_POST['min_bathrooms'] ) ) {
            $min_bathrooms = floatval( $_POST['min_bathrooms'] );
        }

        $max_bathrooms = '';
        if ( isset( $_POST['max_bathrooms'] ) ) {
            $max_bathrooms = floatval( $_POST['max_bathrooms'] );
        }

        $status       = '';
        $status_array = '';
        if ( isset( $_POST['status'] ) && '' !== $_POST['status'] ) {
            $status = esc_html( $_POST['status'] );
            $status = html_entity_decode( $status, ENT_QUOTES );

            $status_array = array(
                'taxonomy' => 'property_status',
                'field'    => 'name',
                'terms'    => $status,
            );

        }

        $wpestate_keyword = '';
        if ( isset( $_POST['keyword'] ) ) {
            $wpestate_keyword = esc_html( $_POST['keyword'] );
        }

        $meta_order = 'prop_featured';
        $epsilon    = 0.0001;

        $custom_fields = wpresidence_get_option( 'wp_estate_multi_curr', '' );
        $price_min     = isset( $_REQUEST['price_low'] ) ? floatval( $_REQUEST['price_low'] ) : 0;
        $price_max     = isset( $_REQUEST['price_max'] ) ? floatval( $_REQUEST['price_max'] ) : 0;

        if ( $price_min || $price_max ) {
            if ( ! empty( $custom_fields ) && isset( $_COOKIE['my_custom_curr'], $_COOKIE['my_custom_curr_pos'], $_COOKIE['my_custom_curr_symbol'] ) ) {
                $index = intval( $_COOKIE['my_custom_curr_pos'] );
                if ( $index >= 0 && isset( $custom_fields[ $index ][2] ) ) {
                    $exchange_rate = floatval( $custom_fields[ $index ][2] );
                    if ( $exchange_rate > 0 ) {
                        if ( $price_min ) {
                            $price_min = $price_min / $exchange_rate;
                        }
                        if ( $price_max ) {
                            $price_max = $price_max / $exchange_rate;
                        }
                    }
                }
            }

            $default_price_min = floatval( wpresidence_get_option( 'wp_estate_show_slider_min_price', '' ) );
            $default_price_max = floatval( wpresidence_get_option( 'wp_estate_show_slider_max_price', '' ) );

            $min_matches_default = ( $price_min <= 0 && $default_price_min <= 0 );
            if ( ! $min_matches_default && $default_price_min > 0 ) {
                $min_matches_default = ( abs( $price_min - $default_price_min ) <= $epsilon );
            }

            $max_matches_default = ( $price_max <= 0 && $default_price_max <= 0 );
            if ( ! $max_matches_default && $default_price_max > 0 ) {
                $max_matches_default = ( $price_max >= ( $default_price_max - $epsilon ) );
            }

            if ( $min_matches_default && $max_matches_default ) {
                $price_min = 0;
                $price_max = 0;
            }
        }

        if ( $price_min || $price_max ) {
            if ( $price_min && $price_max ) {
                $meta_query[] = array(
                    'key'     => 'property_price',
                    'value'   => array( $price_min, $price_max ),
                    'type'    => 'numeric',
                    'compare' => 'BETWEEN',
                );
            } elseif ( $price_min ) {
                $meta_query[] = array(
                    'key'     => 'property_price',
                    'value'   => $price_min,
                    'type'    => 'numeric',
                    'compare' => '>=',
                );
            } else {
                $meta_query[] = array(
                    'key'     => 'property_price',
                    'value'   => $price_max,
                    'type'    => 'numeric',
                    'compare' => '<=',
                );
            }
        }

        $default_size_min = wpestate_convert_measure( floatval( get_post_meta( $directory_post_id, 'dir_min_size', true ) ), 1 );
        $default_size_max = wpestate_convert_measure( floatval( get_post_meta( $directory_post_id, 'dir_max_size', true ) ), 1 );
        $min_size         = isset( $_REQUEST['min_size'] ) ? wpestate_convert_measure( floatval( $_REQUEST['min_size'] ), 1 ) : 0;
        $max_size         = isset( $_REQUEST['max_size'] ) ? wpestate_convert_measure( floatval( $_REQUEST['max_size'] ), 1 ) : 0;

        if ( $min_size || $max_size ) {
            $min_matches_default = ( $min_size <= 0 && $default_size_min <= 0 );
            if ( ! $min_matches_default && $default_size_min > 0 ) {
                $min_matches_default = ( abs( $min_size - $default_size_min ) <= $epsilon );
            }

            $max_matches_default = ( $max_size <= 0 && $default_size_max <= 0 );
            if ( ! $max_matches_default && $default_size_max > 0 ) {
                $max_matches_default = ( $max_size >= ( $default_size_max - $epsilon ) );
            }

            if ( $min_matches_default && $max_matches_default ) {
                $min_size = 0;
                $max_size = 0;
            }
        }

        if ( $min_size || $max_size ) {
            if ( $min_size && $max_size ) {
                $meta_query[] = array(
                    'key'     => 'property_size',
                    'value'   => array( $min_size, $max_size ),
                    'type'    => 'numeric',
                    'compare' => 'BETWEEN',
                );
            } elseif ( $min_size ) {
                $meta_query[] = array(
                    'key'     => 'property_size',
                    'value'   => $min_size,
                    'type'    => 'numeric',
                    'compare' => '>=',
                );
            } else {
                $meta_query[] = array(
                    'key'     => 'property_size',
                    'value'   => $max_size,
                    'type'    => 'numeric',
                    'compare' => '<=',
                );
            }
        }

        $default_lot_min = wpestate_convert_measure( floatval( get_post_meta( $directory_post_id, 'dir_min_lot_size', true ) ), 1 );
        $default_lot_max = wpestate_convert_measure( floatval( get_post_meta( $directory_post_id, 'dir_max_lot_size', true ) ), 1 );
        $min_lot_size    = isset( $_REQUEST['min_lot_size'] ) ? wpestate_convert_measure( floatval( $_REQUEST['min_lot_size'] ), 1 ) : 0;
        $max_lot_size    = isset( $_REQUEST['max_lot_size'] ) ? wpestate_convert_measure( floatval( $_REQUEST['max_lot_size'] ), 1 ) : 0;

        if ( $min_lot_size || $max_lot_size ) {
            $min_matches_default = ( $min_lot_size <= 0 && $default_lot_min <= 0 );
            if ( ! $min_matches_default && $default_lot_min > 0 ) {
                $min_matches_default = ( abs( $min_lot_size - $default_lot_min ) <= $epsilon );
            }

            $max_matches_default = ( $max_lot_size <= 0 && $default_lot_max <= 0 );
            if ( ! $max_matches_default && $default_lot_max > 0 ) {
                $max_matches_default = ( $max_lot_size >= ( $default_lot_max - $epsilon ) );
            }

            if ( $min_matches_default && $max_matches_default ) {
                $min_lot_size = 0;
                $max_lot_size = 0;
            }
        }

        if ( $min_lot_size || $max_lot_size ) {
            if ( $min_lot_size && $max_lot_size ) {
                $meta_query[] = array(
                    'key'     => 'property_lot_size',
                    'value'   => array( $min_lot_size, $max_lot_size ),
                    'type'    => 'numeric',
                    'compare' => 'BETWEEN',
                );
            } elseif ( $min_lot_size ) {
                $meta_query[] = array(
                    'key'     => 'property_lot_size',
                    'value'   => $min_lot_size,
                    'type'    => 'numeric',
                    'compare' => '>=',
                );
            } else {
                $meta_query[] = array(
                    'key'     => 'property_lot_size',
                    'value'   => $max_lot_size,
                    'type'    => 'numeric',
                    'compare' => '<=',
                );
            }
        }

        $default_rooms_min = floatval( get_post_meta( $directory_post_id, 'dir_rooms_min', true ) );
        $default_rooms_max = floatval( get_post_meta( $directory_post_id, 'dir_rooms_max', true ) );
        $min_rooms         = isset( $_REQUEST['min_rooms'] ) ? floatval( $_REQUEST['min_rooms'] ) : 0;
        $max_rooms         = isset( $_REQUEST['max_rooms'] ) ? floatval( $_REQUEST['max_rooms'] ) : 0;

        if ( $min_rooms || $max_rooms ) {
            $min_matches_default = ( $min_rooms <= 0 && $default_rooms_min <= 0 );
            if ( ! $min_matches_default && $default_rooms_min > 0 ) {
                $min_matches_default = ( abs( $min_rooms - $default_rooms_min ) <= $epsilon );
            }

            $max_matches_default = ( $max_rooms <= 0 && $default_rooms_max <= 0 );
            if ( ! $max_matches_default && $default_rooms_max > 0 ) {
                $max_matches_default = ( $max_rooms >= ( $default_rooms_max - $epsilon ) );
            }

            if ( $min_matches_default && $max_matches_default ) {
                $min_rooms = 0;
                $max_rooms = 0;
            }
        }

        if ( $min_rooms || $max_rooms ) {
            if ( $min_rooms && $max_rooms ) {
                $meta_query[] = array(
                    'key'     => 'property_rooms',
                    'value'   => array( $min_rooms, $max_rooms ),
                    'type'    => 'numeric',
                    'compare' => 'BETWEEN',
                );
            } elseif ( $min_rooms ) {
                $meta_query[] = array(
                    'key'     => 'property_rooms',
                    'value'   => $min_rooms,
                    'type'    => 'numeric',
                    'compare' => '>=',
                );
            } else {
                $meta_query[] = array(
                    'key'     => 'property_rooms',
                    'value'   => $max_rooms,
                    'type'    => 'numeric',
                    'compare' => '<=',
                );
            }
        }

        $default_beds_min = floatval( get_post_meta( $directory_post_id, 'dir_bedrooms_min', true ) );
        $default_beds_max = floatval( get_post_meta( $directory_post_id, 'dir_bedrooms_max', true ) );
        $min_bedrooms     = isset( $_REQUEST['min_bedrooms'] ) ? floatval( $_REQUEST['min_bedrooms'] ) : 0;
        $max_bedrooms     = isset( $_REQUEST['max_bedrooms'] ) ? floatval( $_REQUEST['max_bedrooms'] ) : 0;

        if ( $min_bedrooms || $max_bedrooms ) {
            $min_matches_default = ( $min_bedrooms <= 0 && $default_beds_min <= 0 );
            if ( ! $min_matches_default && $default_beds_min > 0 ) {
                $min_matches_default = ( abs( $min_bedrooms - $default_beds_min ) <= $epsilon );
            }

            $max_matches_default = ( $max_bedrooms <= 0 && $default_beds_max <= 0 );
            if ( ! $max_matches_default && $default_beds_max > 0 ) {
                $max_matches_default = ( $max_bedrooms >= ( $default_beds_max - $epsilon ) );
            }

            if ( $min_matches_default && $max_matches_default ) {
                $min_bedrooms = 0;
                $max_bedrooms = 0;
            }
        }

        if ( $min_bedrooms || $max_bedrooms ) {
            if ( $min_bedrooms && $max_bedrooms ) {
                $meta_query[] = array(
                    'key'     => 'property_bedrooms',
                    'value'   => array( $min_bedrooms, $max_bedrooms ),
                    'type'    => 'numeric',
                    'compare' => 'BETWEEN',
                );
            } elseif ( $min_bedrooms ) {
                $meta_query[] = array(
                    'key'     => 'property_bedrooms',
                    'value'   => $min_bedrooms,
                    'type'    => 'numeric',
                    'compare' => '>=',
                );
            } else {
                $meta_query[] = array(
                    'key'     => 'property_bedrooms',
                    'value'   => $max_bedrooms,
                    'type'    => 'numeric',
                    'compare' => '<=',
                );
            }
        }

        $default_baths_min = floatval( get_post_meta( $directory_post_id, 'dir_bathrooms_min', true ) );
        $default_baths_max = floatval( get_post_meta( $directory_post_id, 'dir_bathrooms_max', true ) );
        $min_bathrooms     = isset( $_REQUEST['min_bathrooms'] ) ? floatval( $_REQUEST['min_bathrooms'] ) : 0;
        $max_bathrooms     = isset( $_REQUEST['max_bathrooms'] ) ? floatval( $_REQUEST['max_bathrooms'] ) : 0;

        if ( $min_bathrooms || $max_bathrooms ) {
            $min_matches_default = ( $min_bathrooms <= 0 && $default_baths_min <= 0 );
            if ( ! $min_matches_default && $default_baths_min > 0 ) {
                $min_matches_default = ( abs( $min_bathrooms - $default_baths_min ) <= $epsilon );
            }

            $max_matches_default = ( $max_bathrooms <= 0 && $default_baths_max <= 0 );
            if ( ! $max_matches_default && $default_baths_max > 0 ) {
                $max_matches_default = ( $max_bathrooms >= ( $default_baths_max - $epsilon ) );
            }

            if ( $min_matches_default && $max_matches_default ) {
                $min_bathrooms = 0;
                $max_bathrooms = 0;
            }
        }

        if ( $min_bathrooms || $max_bathrooms ) {
            if ( $min_bathrooms && $max_bathrooms ) {
                $meta_query[] = array(
                    'key'     => 'property_bathrooms',
                    'value'   => array( $min_bathrooms, $max_bathrooms ),
                    'type'    => 'numeric',
                    'compare' => 'BETWEEN',
                );
            } elseif ( $min_bathrooms ) {
                $meta_query[] = array(
                    'key'     => 'property_bathrooms',
                    'value'   => $min_bathrooms,
                    'type'    => 'numeric',
                    'compare' => '>=',
                );
            } else {
                $meta_query[] = array(
                    'key'     => 'property_bathrooms',
                    'value'   => $max_bathrooms,
                    'type'    => 'numeric',
                    'compare' => '<=',
                );
            }
        }

        $prop_no        = intval( wpresidence_get_option( 'wp_estate_prop_no', '' ) );
        $features_array = wpestate_add_feature_to_search( 'ajax' );
        $args           = array(
            'cache_results'           => false,
            'update_post_meta_cache'  => false,
            'update_post_term_cache'  => false,
            'post_type'               => 'estate_property',
            'post_status'             => 'publish',
            'paged'                   => $pagination,
            'posts_per_page'          => $prop_no,
            'meta_key'                => $meta_order,
            //  'orderby'         => $order_by,
            //'order'           => $meta_directions,
            'tax_query'               => array(
                'relation' => 'AND',
                $categ_array,
                $action_array,
                $city_array,
                $area_array,
                $county_array,
                $features_array,
                $status_array,

            ),
        );

        $order_array = array();
        if ( isset( $_POST['order'] ) ) {
            $order       = intval( $_POST['order'] );
            $order_array = wpestate_create_query_order_by_array( $order );
        }

        if ( isset( $order_array['order_array'] ) && is_array( $order_array['order_array'] ) ) {
            $args = array_merge( $args, $order_array['order_array'] );
        }

        if ( ! empty( $meta_query ) ) {
            $meta_query['relation'] = 'AND';
            $args['meta_query']    = $meta_query;
        }

        global $wpestate_keyword;
        $wpestate_keyword = isset( $_POST['keyword'] ) ? esc_html( $_POST['keyword'] ) : '';

        if ( ! empty( $wpestate_keyword ) ) {
            add_filter( 'posts_where', 'wpestate_title_filter', 10, 2 );
        }

        if ( 0 === $order ) {
            $prop_selection = wpestate_return_filtered_by_order( $args );
        } else {
            $prop_selection = new WP_Query( $args );
        }

        ob_start();

        wpresidence_display_property_list_as_html( $prop_selection, $wpestate_options, 'shortcode_list' );

        $cards = ob_get_contents();
        ob_end_clean();

        if ( ! empty( $wpestate_keyword ) ) {
            if ( function_exists( 'wpestate_disable_filtering' ) ) {
                wpestate_disable_filtering( 'posts_where', 'wpestate_title_filter', 10, 2 );
            }
        }

        echo json_encode(
            array(
                'args'       => $args,
                'cards'      => $cards,
                'no_results' => $prop_selection->found_posts,
            )
        );
        wp_suspend_cache_addition( false );
        die();
    }

endif; // end   ajax_filter_listings


