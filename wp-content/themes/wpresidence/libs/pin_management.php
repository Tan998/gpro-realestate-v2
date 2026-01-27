<?php



/**
 * Determines the flex direction class for half map layout based on map position setting
 * 
 * This function checks the WordPress theme option 'wp_estate_half_map_position'
 * and returns the appropriate Bootstrap flex direction class. When the map 
 * position is set to 'left', it returns the default row direction. Otherwise,
 * it returns the reversed row direction.
 * 
 * @since 1.0.0
 * @access public
 * 
 * @uses wpresidence_get_option() To get the half map position theme setting
 * 
 * @return string Bootstrap flex direction class ('flex-md-row' or 'flex-md-row-reverse')
*/

if( !function_exists('wpresidence_map_map_orientation_class') ):
function wpresidence_map_map_orientation_class(){
    $half_map_position = wpresidence_get_option('wp_estate_half_map_position');
    if($half_map_position=='left'){
        return 'flex-md-row';
    }else{
        return 'flex-md-row-reverse';
    }
}

endif;

/*
* 
*
*
*/

if( !function_exists('wpestate_pin_unit_creation') ):
    function wpestate_pin_unit_creation2($the_id,$wpestate_currency,$where_currency,$unit,$counter){

        ////////////////////////////////////// gathering data for markups
        $gmap_lat    =      floatval(get_post_meta($the_id, 'property_latitude', true));
        $gmap_long   =      floatval(get_post_meta($the_id, 'property_longitude', true));

        if($gmap_lat==0){
            $gmap_lat='';
        }

        if($gmap_long==0){
            $gmap_long='';
        }

        $slug        =   array();
        $prop_type   =   array();

        $types       =   get_the_terms($the_id,'property_category' );
        $types_act   =   get_the_terms($the_id,'property_action_category' );

        $prop_type_name=array();
        if ( $types && ! is_wp_error( $types ) ) {
            foreach ($types as $single_type) {
                $prop_type[]      = $single_type->slug;
                $prop_type_name[] = $single_type->name;
                $slug             = $single_type->slug;
                $parent_term      = $single_type->parent;

            }

            $single_first_type      = $prop_type[0];
            $single_first_type_pin  = $prop_type[0];
            if($parent_term!=0){
                $single_first_type=$single_first_type.wpestate_add_parent_infobox($parent_term,'property_category');
            }
            $single_first_type_name= $prop_type_name[0];
        }else{
            $single_first_type        ='';
            $single_first_type_name   ='';
            $single_first_type_pin    ='';
        }


        ////////////////////////////////////// get property action
        $prop_action        =   array();
        $prop_action_name   =   array();
        if ( $types_act && ! is_wp_error( $types_act ) ) {
              foreach ($types_act as $single_type) {
                $prop_action[]      =   $single_type->slug;
                $prop_action_name[] =   $single_type->name;
                $slug               =   $single_type->slug;
                $parent_term        =   $single_type->parent;
               }
        $single_first_action        = $prop_action[0];
        $single_first_action_pin    = $prop_action[0];

        if($parent_term!=0){
            $single_first_action=$single_first_action.wpestate_add_parent_infobox($parent_term,'property_action_category');
        }
        $single_first_action_name   = $prop_action_name[0];
        }else{
            $single_first_action        ='';
            $single_first_action_name   ='';
            $single_first_action_pin    ='';
        }

        // composing name of the pin
        if($single_first_action=='' || $single_first_action ==''){
              $pin                   =  sanitize_key(wpestate_limit54($single_first_type_pin.$single_first_action_pin));
        }else{
              $pin                   =  sanitize_key(wpestate_limit27($single_first_type_pin)).sanitize_key(wpestate_limit27($single_first_action_pin));
        }
        $counter++;


        //// get price
        $price              =   floatval    ( get_post_meta($the_id, 'property_price', true) );
        $price_label        =   esc_html    ( get_post_meta($the_id, 'property_label', true) );
        $price_label_before =   esc_html    ( get_post_meta($the_id, 'property_label_before', true) );
        $clean_price        =   floatval    ( get_post_meta($the_id, 'property_price', true) );

        $hidingprice = wpestate_hide_show_price( $the_id );

        if ( $hidingprice ) {
            $hiddenPriceLabel = wpresidence_get_option( 'wp_estate_property_hide_price_text', '', 'Price on application' );
            // If price is hidden, return empty string or print nothing
            $price = $hiddenPriceLabel;
            $pin_price = $hiddenPriceLabel;
        } else {
        // If price is not hidden, format it as usual
        

        if($price==0){
            $price      =   $price_label_before.''.$price_label;
            $pin_price  =   '';
        }else{
            $th_separator   =   stripslashes ( wpresidence_get_option('wp_estate_prices_th_separator','') );

            $pin_price      =   $price;
             $price    =   wpestate_format_number_price($price,$th_separator);

            if($where_currency=='before'){
                $price=$wpestate_currency.$price;
            }else{
                $price=$price.$wpestate_currency;
            }


            $indian_format = esc_html(wpresidence_get_option('wp_estate_price_indian_format', ''));
    
            if( wpresidence_get_option('wp_estate_use_price_pins_full_price','')=='no'){
                
                if($indian_format=='no'){
                    $pin_price  =   wpestate_price_pin_converter($pin_price,$where_currency,$wpestate_currency);
                }else{
                   $pin_price  =   wpestate_moneyFormatIndia_short($pin_price);
                }



            }else{
               $pin_price  =='<span class="infocur infocur_first">'.$price_label_before.'</span>'.$price.'<span class="infocur">'.$price_label.'</span>';
         
              
            }

            $price='<span class="infocur infocur_first">'.$price_label_before.'</span>'.$price.'<span class="infocur">'.$price_label.'</span>';
        }

        }



        $rooms      =   get_post_meta($the_id, 'property_bedrooms', true);
        $bathrooms  =   get_post_meta($the_id, 'property_bathrooms', true);
        $size       =   wpestate_get_converted_measure( $the_id, 'property_size' );

        $place_markers  =   array();
        $title_orig     =   wpresidence_get_sanitized_truncated_title($the_id,0);
        $title_orig     =   str_replace('%','', $title_orig);

        $place_markers[]    =   rawurlencode ($title_orig);//0
        $place_markers[]    =   $gmap_lat;//1
        $place_markers[]    =   $gmap_long;//2
        $place_markers[]    =   $counter;//3
        $place_markers[]    =   rawurlencode ( get_the_post_thumbnail($the_id,'agent_picture_thumb') );////4
        $place_markers[]    =   rawurlencode ( $price); //5
        $place_markers[]    =   rawurlencode ($single_first_type);//6
        $place_markers[]    =   rawurlencode ($single_first_action);//7
        $place_markers[]    =   rawurlencode ($pin);//8
        $place_markers[]    =   rawurlencode (  esc_url ( get_permalink($the_id) ) );//9
        $place_markers[]    =   $the_id;//10
        $place_markers[]    =   $clean_price;//11
        $place_markers[]    =   $rooms;//12
        $place_markers[]    =   $bathrooms;//13
        $place_markers[]    =   $size;//14
        $place_markers[]    =   rawurlencode ( $single_first_type_name);//15
        $place_markers[]    =   rawurlencode  ( $single_first_action_name);//16
        $place_markers[]    =   rawurlencode($pin_price);
        $place_markers[]    =   rawurlencode(get_the_post_thumbnail($the_id,'widget_thumb') );
        return $place_markers;



    }
endif;

/**
 * Create map pin data for a property
 * 
 * Optimized function to generate pin data for property map markers, using cached property data
 * to reduce database queries and improve performance.
 * 
 * @param int    $the_id          Property ID
 * @param string $wpestate_currency Currency symbol
 * @param string $where_currency  Currency position (before/after)
 * @param string $unit            Unit of measurement
 * @param int    $counter         Pin counter
 * @return array Array of pin data for map markers
 */
if(!function_exists('wpestate_pin_unit_creation')):
    function wpestate_pin_unit_creation($the_id, $wpestate_currency, $where_currency, $unit, $counter){
        if(!function_exists('wpestate_api_get_cached_post_data')){
            return;
        }
        // Get cached property data
        $cached_data = wpestate_api_get_cached_post_data($the_id, 'estate_property');
        
        // Extract latitude and longitude from cached meta
        $gmap_lat = floatval($cached_data['meta']['property_latitude'] ?? 0);
        $gmap_long = floatval($cached_data['meta']['property_longitude'] ?? 0);
        
        // Set empty string for zero coordinates
        if($gmap_lat == 0) {
            $gmap_lat = '';
        }
        
        if($gmap_long == 0) {
            $gmap_long = '';
        }
        
        // Initialize variables
        $slug = array();
        $prop_type = array();
        $prop_type_name = array();
        
        // Get property categories from cached terms
        $types = $cached_data['terms']['property_category'] ?? array();
        $types_act = $cached_data['terms']['property_action_category'] ?? array();
        
        // Process property categories
        if(!empty($types)) {
            foreach($types as $single_type) {
                $prop_type[] = $single_type['slug'];
                $prop_type_name[] = $single_type['name'];
                $slug = $single_type['slug'];
                $parent_term = isset($single_type['parent']) ? $single_type['parent'] : 0;
            }
            
            $single_first_type = $prop_type[0];
            $single_first_type_pin = $prop_type[0];
            
            if(isset($parent_term) && $parent_term != 0) {
                $single_first_type = $single_first_type.wpestate_add_parent_infobox($parent_term, 'property_category');
            }
            
            $single_first_type_name = $prop_type_name[0];
        } else {
            $single_first_type = '';
            $single_first_type_name = '';
            $single_first_type_pin = '';
        }
        
        // Process property actions
        $prop_action = array();
        $prop_action_name = array();
        
        if(!empty($types_act)) {
            foreach($types_act as $single_type) {
                $prop_action[] = $single_type['slug'];
                $prop_action_name[] = $single_type['name'];
                $slug = $single_type['slug'];
                $parent_term = isset($single_type['parent']) ? $single_type['parent'] : 0;
            }
            
            $single_first_action = $prop_action[0];
            $single_first_action_pin = $prop_action[0];
            
            if(isset($parent_term) && $parent_term != 0) {
                $single_first_action = $single_first_action.wpestate_add_parent_infobox($parent_term, 'property_action_category');
            }
            
            $single_first_action_name = $prop_action_name[0];
        } else {
            $single_first_action = '';
            $single_first_action_name = '';
            $single_first_action_pin = '';
        }
        
        // Compose pin name
        if($single_first_action == '' || $single_first_type == '') {
            $pin = sanitize_key(wpestate_limit54($single_first_type_pin.$single_first_action_pin));
        } else {
            $pin = sanitize_key(wpestate_limit27($single_first_type_pin)).sanitize_key(wpestate_limit27($single_first_action_pin));
        }
        
        $counter++;
        
        // Get price information from cached meta
        $price = floatval($cached_data['meta']['property_price'] ?? 0);
        $price_label = esc_html($cached_data['meta']['property_label'] ?? '');
        $price_label_before = esc_html($cached_data['meta']['property_label_before'] ?? '');
        $clean_price = $price;

        $hidingprice = wpestate_hide_show_price( $the_id );

        if ( $hidingprice ) {
            $hiddenPriceLabel = wpresidence_get_option( 'wp_estate_property_hide_price_text', '', 'Price on application' );
            // If price is hidden, return empty string or print nothing
            $price = $hiddenPriceLabel;
            $pin_price = $hiddenPriceLabel;
        } else {
        // If price is not hidden, format it as usual
        
        // Format price
        if($price == 0) {
            $price = $price_label_before.''.$price_label;
            $pin_price = '';
        } else {
            $th_separator = stripslashes(wpresidence_get_option('wp_estate_prices_th_separator', ''));
            $pin_price = $price;
            $price = wpestate_format_number_price($price, $th_separator);
            
            if($where_currency == 'before') {
                $price = $wpestate_currency.$price;
            } else {
                $price = $price.$wpestate_currency;
            }
            
            $indian_format = esc_html(wpresidence_get_option('wp_estate_price_indian_format', ''));
            
            if(wpresidence_get_option('wp_estate_use_price_pins_full_price', '') == 'no') {
                if($indian_format == 'no') {
                    $pin_price = wpestate_price_pin_converter($pin_price, $where_currency, $wpestate_currency);
                } else {
                    $pin_price = wpestate_moneyFormatIndia_short($pin_price);
                }
            } else {
                $pin_price = '<span class="infocur infocur_first">'.$price_label_before.'</span>'.$price.'<span class="infocur">'.$price_label.'</span>';
            }
            
            $price = '<span class="infocur infocur_first">'.$price_label_before.'</span>'.$price.'<span class="infocur">'.$price_label.'</span>';
        }
        }
        
        // Get property details from cached meta
        $rooms = $cached_data['meta']['property_bedrooms'] ?? '';
        $bathrooms = $cached_data['meta']['property_bathrooms'] ?? '';
        $size = wpestate_get_converted_measure($the_id, 'property_size');
        
        // Get property title
        $title_orig = $cached_data['title'] ?? wpresidence_get_sanitized_truncated_title($the_id,0);
        $title_orig = str_replace('%', '', $title_orig);
        
        // Get property thumbnail
        if(!empty($cached_data['media'])) {
            // Need to recreate the HTML markup since we have only image URL in cache
            $first_media_id = array_key_first($cached_data['media']);
            if(isset($cached_data['media'][$first_media_id]['agent_picture_thumb'])) {
                $img_url = $cached_data['media'][$first_media_id]['agent_picture_thumb'];
                $thumbnail = '<img width="120" height="120" src="' . esc_url($img_url) . '" class="attachment-agent_picture_thumb size-agent_picture_thumb wp-post-image" alt="" loading="lazy">';
            } else {
                $thumbnail = get_the_post_thumbnail($the_id, 'agent_picture_thumb');
            }
            
            if(isset($cached_data['media'][$first_media_id]['widget_thumb'])) {
                $img_url = $cached_data['media'][$first_media_id]['widget_thumb'];
                $widget_thumb = '<img width="105" height="70" src="' . esc_url($img_url) . '" class="attachment-widget_thumb size-widget_thumb wp-post-image" alt="" loading="lazy">';
            } else {
                $widget_thumb = get_the_post_thumbnail($the_id, 'widget_thumb');
            }
        } else {
            $thumbnail = get_the_post_thumbnail($the_id, 'agent_picture_thumb');
            $widget_thumb = get_the_post_thumbnail($the_id, 'widget_thumb');
        }
        
        // Prepare marker data array
        $place_markers = array();
        $place_markers[] = rawurlencode($title_orig); // 0
        $place_markers[] = $gmap_lat; // 1
        $place_markers[] = $gmap_long; // 2
        $place_markers[] = $counter; // 3
        $place_markers[] = rawurlencode($thumbnail); // 4
        $place_markers[] = rawurlencode($price); // 5
        $place_markers[] = rawurlencode($single_first_type); // 6
        $place_markers[] = rawurlencode($single_first_action); // 7
        $place_markers[] = rawurlencode($pin); // 8
        $place_markers[] = rawurlencode(esc_url(get_permalink($the_id))); // 9
        $place_markers[] = $the_id; // 10
        $place_markers[] = $clean_price; // 11
        $place_markers[] = $rooms; // 12
        $place_markers[] = $bathrooms; // 13
        $place_markers[] = $size; // 14
        $place_markers[] = rawurlencode($single_first_type_name); // 15
        $place_markers[] = rawurlencode($single_first_action_name); // 16
        $place_markers[] = rawurlencode($pin_price); // 17
        $place_markers[] = rawurlencode($widget_thumb); // 18
        
        return $place_markers;
    }
endif;

/*
* 
*
*
*/


if( !function_exists('wpestate_listing_pins_marker_creation') ):
    function wpestate_listing_pins_marker_creation($prop_selection=''){
        $counter                    =   0;
        $unit                       =   wpresidence_get_option('wp_estate_measure_sys','');
        $wpestate_currency                   =   wpresidence_get_option('wp_estate_currency_symbol','');
        $where_currency             =   wpresidence_get_option('wp_estate_where_currency_symbol', '');
        $markers                    =   array();

        while($prop_selection->have_posts()): $prop_selection->the_post();

            $the_id      =   get_the_ID();
            ////////////////////////////////////// gathering data for markups
            $gmap_lat    =      floatval(get_post_meta($the_id, 'property_latitude', true));
            $gmap_long   =      floatval(get_post_meta($the_id, 'property_longitude', true));

            if($gmap_lat==0){
                $gmap_lat='';
            }

            if($gmap_long==0){
                $gmap_long='';
            }

            $slug        =   array();
            $prop_type   =   array();

            $types       =   get_the_terms($the_id,'property_category' );
            $types_act   =   get_the_terms($the_id,'property_action_category' );

            $prop_type_name=array();
            if ( $types && ! is_wp_error( $types ) ) {
                foreach ($types as $single_type) {
                    $prop_type[]      = $single_type->slug;
                    $prop_type_name[] = $single_type->name;
                    $slug             = $single_type->slug;
                    $parent_term      = $single_type->parent;

                }

                $single_first_type      = $prop_type[0];
                $single_first_type_pin  = $prop_type[0];
                if($parent_term!=0){
                    $single_first_type=$single_first_type.wpestate_add_parent_infobox($parent_term,'property_category');
                }
                $single_first_type_name= $prop_type_name[0];
            }else{
                $single_first_type        ='';
                $single_first_type_name   ='';
                $single_first_type_pin    ='';
            }


            ////////////////////////////////////// get property action
            $prop_action        =   array();
            $prop_action_name   =   array();
            if ( $types_act && ! is_wp_error( $types_act ) ) {
                  foreach ($types_act as $single_type) {
                    $prop_action[]      =   $single_type->slug;
                    $prop_action_name[] =   $single_type->name;
                    $slug               =   $single_type->slug;
                    $parent_term        =   $single_type->parent;
                   }
            $single_first_action        = $prop_action[0];
            $single_first_action_pin    = $prop_action[0];

            if($parent_term!=0){
                $single_first_action=$single_first_action.wpestate_add_parent_infobox($parent_term,'property_action_category');
            }
            $single_first_action_name   = $prop_action_name[0];
            }else{
                $single_first_action        ='';
                $single_first_action_name   ='';
                $single_first_action_pin    ='';
            }

            // composing name of the pin
            if($single_first_action=='' || $single_first_action ==''){
                  $pin                   =  sanitize_key(wpestate_limit54($single_first_type_pin.$single_first_action_pin));
            }else{
                  $pin                   =  sanitize_key(wpestate_limit27($single_first_type_pin)).sanitize_key(wpestate_limit27($single_first_action_pin));
            }
            $counter++;


            //// get price
            $price              =   floatval    ( get_post_meta($the_id, 'property_price', true) );
            $price_label        =   esc_html    ( get_post_meta($the_id, 'property_label', true) );
            $price_label_before =   esc_html    ( get_post_meta($the_id, 'property_label_before', true) );
            $clean_price        =   floatval    ( get_post_meta($the_id, 'property_price', true) );

            $hidingprice = wpestate_hide_show_price( $the_id );

            if ( $hidingprice ) {
                $hiddenPriceLabel = wpresidence_get_option( 'wp_estate_property_hide_price_text', '', 'Price on application' );
                // If price is hidden, return empty string or print nothing
                $price = $hiddenPriceLabel;
                $pin_price = $hiddenPriceLabel;
            } else {
            // If price is not hidden, format it as usual

            if($price==0){
                $price      =   $price_label_before.''.$price_label;
                $pin_price  =   '';
            }else{
                $th_separator   =   stripslashes ( wpresidence_get_option('wp_estate_prices_th_separator','') );
                $pin_price      =   $price;
                 $price    =   wpestate_format_number_price($price,$th_separator);

                if($where_currency=='before'){
                    $price=$wpestate_currency.$price;
                }else{
                    $price=$price.$wpestate_currency;
                }

                $indian_format = esc_html(wpresidence_get_option('wp_estate_price_indian_format', ''));
     
                if( wpresidence_get_option('wp_estate_use_price_pins_full_price','')=='no'){
                   
                    if($indian_format=='no'){
                        $pin_price  =   wpestate_price_pin_converter($pin_price,$where_currency,$wpestate_currency);
                    }else{
                       $pin_price  =   wpestate_moneyFormatIndia_short($pin_price);
                    }


                }else{
                    $pin_price  =='<span class="infocur infocur_first">'.$price_label_before.'</span>'.$price.'<span class="infocur">'.$price_label.'</span>';
                   
                }

                $price='<span class="infocur infocur_first">'.$price_label_before.'</span>'.$price.'<span class="infocur">'.$price_label.'</span>';
            }
            }



            $rooms      =   get_post_meta($the_id, 'property_bedrooms', true);
            $bathrooms  =   get_post_meta($the_id, 'property_bathrooms', true);
            $size       =   wpestate_get_converted_measure( $the_id, 'property_size' );

            $place_markers  =   array();
            $title_orig     =   get_the_title();
            $title_orig     =   str_replace('%','', $title_orig);

            $place_markers[]    =   rawurlencode ($title_orig);//0
            $place_markers[]    =   $gmap_lat;//1
            $place_markers[]    =   $gmap_long;//2
            $place_markers[]    =   $counter;//3
            $place_markers[]    =   rawurlencode ( get_the_post_thumbnail($the_id,'agent_picture_thumb') );////4
            $place_markers[]    =   rawurlencode (  $price); //5
            $place_markers[]    =   rawurlencode ($single_first_type);//6
            $place_markers[]    =   rawurlencode ($single_first_action);//7
            $place_markers[]    =   rawurlencode ($pin);//8
            $place_markers[]    =   rawurlencode (  esc_url ( get_permalink() ) );//9
            $place_markers[]    =   $the_id;//10
            $place_markers[]    =   $clean_price;//11
            $place_markers[]    =   $rooms;//12
            $place_markers[]    =   $bathrooms;//13
            $place_markers[]    =   $size;//14
            $place_markers[]    =   rawurlencode ( $single_first_type_name);//15
            $place_markers[]    =   rawurlencode  ( $single_first_action_name);//16
            $place_markers[]    =   rawurlencode($pin_price);
            $place_markers[]    =   rawurlencode(get_the_post_thumbnail($the_id,'widget_thumb') );
            $markers[]=$place_markers;
        endwhile;

        return $markers;
    }
endif;


/*
* 
*
*
*/




if( !function_exists('wpestate_listing_pins_on_demand') ):
    function wpestate_listing_pins_on_demand($args='',$jump=0){
        wp_suspend_cache_addition(true);
        global $wpestate_keyword;
        //set_time_limit (0);
        $counter=0;

        $cache                      =   get_option('wp_estate_cache','');
        $place_markers              =   array();
        $markers                    =   array();

        if( !empty($wpestate_keyword) ){
            add_filter( 'posts_where', 'wpestate_title_filter', 10, 2 );
        }

        if(isset($args['meta_key']) && $args['meta_key']=='prop_featured'){
            add_filter( 'posts_orderby', 'wpestate_my_order' );
        }


        if( wpresidence_get_option('wp_estate_use_geo_location','')=='yes' && 
            isset($_POST['geo_lat']) && $_POST['geo_lat']!='' && 
            isset($_POST['geo_long']) && $_POST['geo_long']!='' ){
            $geo_lat    = $_POST['geo_lat'];
            $geo_long   = $_POST['geo_long'];
            $geo_rad    = $_POST['geo_rad'];
            $args       = wpestate_geo_search_filter_function( $args, $geo_lat, $geo_long, $geo_rad);


        }



        // Exclude sold listings from map results when configured
        if( function_exists('wpestate_remove_sold_listings') ){
            $args = wpestate_remove_sold_listings( $args );
        }

        $prop_selection = new WP_Query($args);



        if(isset($args['meta_key']) && $args['meta_key']=='prop_featured'){
            if(function_exists('wpestate_disable_filtering')){
                wpestate_disable_filtering( 'posts_orderby', 'wpestate_my_order' );
            }
        }

        if( !empty($wpestate_keyword) ){
            if(function_exists('wpestate_disable_filtering')){
                wpestate_disable_filtering( 'posts_where', 'wpestate_title_filter', 10, 2 );
            }
        }
        wp_reset_query();


        $custom_advanced_search = wpresidence_get_option('wp_estate_custom_advanced_search','');
        $show_slider_price      = wpresidence_get_option('wp_estate_show_slider_price','');
        $has_slider             =   0;


        $markers=wpestate_listing_pins_marker_creation($prop_selection);

        wp_suspend_cache_addition(false);
        wp_reset_query();
        $return_array= array();
        $return_array['markers']=$markers;
        $return_array['results']=$prop_selection->found_posts;

        return ($return_array);
    }
endif; // end   wpestate_listing_pins_on_demand

/*
* google map functions - contact pin array creation
*
*
*/



if( !function_exists('wpestate_contact_pin') ):
    function wpestate_contact_pin(){
            $place_markers=array();
            $company_name=esc_html(stripslashes( wpresidence_get_option('wp_estate_company_name','') ) );
            if($company_name==''){
                $company_name='Company Name';
            }

            $place_markers[0]    =   $company_name;
            $place_markers[1]    =   '';
            $place_markers[2]    =   '';
            $place_markers[3]    =   1;
            $place_markers[4]    =   (wpresidence_get_option('wp_estate_company_contact_image', '') );
            $place_markers[5]    =   '0';
            $place_markers[6]    =   'address';
            $place_markers[7]    =   'none';
            $place_markers[8]    =   '';
            return json_encode($place_markers);
    }
endif; // end   wpestate_contact_pin




/*
* 
*
*
*/


if( !function_exists('wpestate_add_parent_infobox') ):
    function wpestate_add_parent_infobox($parent_term,$taxonomy){
        $parent_term = get_term_by( 'id', $parent_term, $taxonomy);
       
        if( isset($parent_term) ){
         
            if( isset($paren_term->parent) && $parent_term->parent!=0){
                return  '.'.$parent_term->slug.wpestate_add_parent_infobox($parent_term->parent,$taxonomy);
            }else if( isset($paren_term->slug) ) {
                return '.'.$parent_term->slug;
            }
        }


}
endif;



/*
*
*google map functions - pin array creation
*
*/

if( !function_exists('wpestate_listing_pins') ):
//function wpestate_listing_pins($args='',$jump=0,$wpestate_keyword='',$id_array=''){
function wpestate_listing_pins($transient_appendix='',$with_cache=1,$args='',$jump=0,$wpestate_keyword='',$id_array=''){
    wp_suspend_cache_addition(true);
    $counter                    =   0;
    $unit                       =   wpresidence_get_option('wp_estate_measure_sys','');
    $wpestate_currency          =   wpresidence_get_option('wp_estate_currency_symbol','');
    $where_currency             =   wpresidence_get_option('wp_estate_where_currency_symbol', '');
    $place_markers  =   array();
    $markers        =   array();

    if  ( $args==''){
        $args = array(
            'post_type'                 =>    'estate_property',
            'post_status'               =>    'publish',
            'posts_per_page'            =>    intval( wpresidence_get_option('wp_estate_map_max_pins') ),
            'cache_results'             =>    false,
            'update_post_meta_cache'    =>    false,
            'update_post_term_cache'    =>    false,
            'fields'                    =>    'ids'
           );
        $transient_appendix='default_pins';
    }

    if( intval($id_array)!=0 ){
        $transient_appendix='default_pins_with_ids';
    }else{
        if( !empty($wpestate_keyword) ){
            $transient_appendix.='prop_title_filter';
        }

        if( isset($args['meta_key']) && $args['meta_key']=='prop_featured'){
            $transient_appendix.='_prop_featured';
        }
    }
    // translate wpml
    if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
        $transient_appendix.='_'. ICL_LANGUAGE_CODE;
    }
    // translate wpr
    if (function_exists('wpestate_get_current_language') ){
        $transient_appendix .= '_'.wpestate_get_current_language() ;
    }


    if ( isset($_COOKIE['my_custom_curr_symbol'] ) ){
        $transient_appendix.='_'.$_COOKIE['my_custom_curr_symbol'];
    }
    if(isset($_COOKIE['my_measure_unit'])){
          $transient_appendix.= $_COOKIE['my_measure_unit'];
    }

    $show_sold = wpresidence_get_option('wp_estate_show_sold_items','');
    if ( $show_sold === 'no' ) {
        $transient_appendix .= '_nosold';
    }


    if($with_cache==1){
        $markers = wpestate_request_transient_cache('wpestate_markers'.$transient_appendix);
    }else{
        $markers=false;
    }


    if( $markers === false ) {
        $markers=array();
        if( intval($id_array)!=0 ){
                $args=  array(
                    'post_type'     =>  'estate_property',
                    'p'             =>  $id_array,
                    'fields'        =>  'ids'
                );
            if( function_exists('wpestate_remove_sold_listings') ){
                $args = wpestate_remove_sold_listings( $args );
            }

            $prop_selection =   new WP_Query( $args);

        }else{
            if( !empty($wpestate_keyword) ){
                add_filter( 'posts_where', 'wpestate_title_filter', 10, 2 );
            }

            if( isset($args['meta_key']) && $args['meta_key']=='prop_featured'){
                add_filter( 'posts_orderby', 'wpestate_my_order' );
            }

            if( function_exists('wpestate_remove_sold_listings') ){
                $args = wpestate_remove_sold_listings( $args );
            }

            $prop_selection = new WP_Query($args);


            if( isset($args['meta_key']) && $args['meta_key']=='prop_featured'){
                if(function_exists('wpestate_disable_filtering')){
                    wpestate_disable_filtering( 'posts_orderby', 'wpestate_my_order' );
                }
            }

            if( !empty($wpestate_keyword) ){
                if(function_exists('wpestate_disable_filtering')){
                    wpestate_disable_filtering( 'posts_where', 'wpestate_title_filter', 10, 2 );
                }
            }
        }

         // build markers array
        foreach ($prop_selection->posts as $key=>$value){
            $counter++;

            $markers[]=wpestate_pin_unit_creation( $value,$wpestate_currency,$where_currency,$unit,$counter );
        }

        if($with_cache==1){
            wpestate_set_transient_cache('wpestate_markers'.$transient_appendix,$markers,4*60*60);
        }
    }

   //$markers    =   wpestate_listing_pins_marker_creation($prop_selection);



    wp_suspend_cache_addition(false);
    wp_reset_query();
    if (wpresidence_get_option('wp_estate_readsys','')=='yes' && $jump==0){
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once (ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }

        $path=wpestate_get_pin_file_path();

        $wp_filesystem->put_contents($path, json_encode($markers), FS_CHMOD_FILE );
    } else{

        return json_encode($markers);
    }
}
endif; // end   wpestate_listing_pins




/*
*
*
*
*/

if( !function_exists('wpestate_listing_pins_query_based') ):
function wpestate_listing_pins_query_based($prop_selection,$jump=0){

    $markers    =   wpestate_listing_pins_marker_creation($prop_selection);
    wp_suspend_cache_addition(false);
    return json_encode($markers);

}
endif; // end   wpestate_listing_pins



/*
*
*
*
*/



if( !function_exists('wpestate_listing_pins_for_file') ):
function wpestate_listing_pins_for_file(){
    wp_suspend_cache_addition(true);


    $place_markers=$markers     =   array();

    $args = array(
        'post_type'                 =>    'estate_property',
        'post_status'               =>    'publish',
        'posts_per_page'            =>    -1,
        'cache_results'             =>    false,
        'update_post_meta_cache'    =>    false,
        'update_post_term_cache'    =>    false,
       );

    $prop_selection =   new WP_Query($args);
    $path           =   wpestate_get_pin_file_path();

    $markers    =   wpestate_listing_pins_marker_creation($prop_selection);
    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once (ABSPATH . '/wp-admin/includes/file.php');
        WP_Filesystem();
    }

    $path=wpestate_get_pin_file_path();

    $wp_filesystem->put_contents($path, json_encode($markers), FS_CHMOD_FILE );


}
endif;





/**
 * Optimized map pin images function
 * 
 * Creates and caches pin image URLs for Google Maps with improved performance
 * using wpestate_get_cached_terms from taxonomy_transients_functions.php
 * 
 * @return string JSON-encoded array of pin image URLs
 * @since 4.0.0
 */
if( !function_exists('wpestate_pin_images') ):
function wpestate_pin_images(){
    // Try to get cached pin images
    $pins = get_transient('wpestate_pin_images');
    
    if($pins === false){
        $pins = array();
        
        // Get property categories and actions using the cached terms function
        $tax_terms = wpestate_get_cached_terms('property_action_category', array('hide_empty' => 0));
        $categories = wpestate_get_cached_terms('property_category', array('hide_empty' => 0));
        
        if(wpresidence_get_option('wp_estate_use_single_image_pin','') != 'yes'){
            if(is_array($tax_terms) && !is_wp_error($tax_terms)){
                foreach ($tax_terms as $tax_term) {
                    $name = sanitize_key(wpestate_limit64('wp_estate_'.$tax_term->slug));
                    $limit54 = sanitize_key(wpestate_limit54($tax_term->slug));
                    $pins[$limit54] = esc_html(wpresidence_get_option($name,'url'));
                }
            }
            
            if(is_array($categories) && !is_wp_error($categories)){
                foreach ($categories as $categ) {
                    $name = sanitize_key(wpestate_limit64('wp_estate_'.$categ->slug));
                    $limit54 = sanitize_key(wpestate_limit54($categ->slug));
                    $pins[$limit54] = esc_html(wpresidence_get_option($name,'url'));
                }
            }
            
            if(is_array($categories) && !is_wp_error($categories) && is_array($tax_terms) && !is_wp_error($tax_terms)){
                foreach ($tax_terms as $tax_term) {
                    foreach ($categories as $categ) {
                        $limit54 = sanitize_key(wpestate_limit27($categ->slug)).sanitize_key(wpestate_limit27($tax_term->slug));
                        $name = 'wp_estate_'.$limit54;
                        $pins[$limit54] = esc_html(wpresidence_get_option($name,'url'));
                    }
                }
            }
        }
        
        $pins['idxpin'] = esc_html(wpresidence_get_option('wp_estate_idxpin','url'));
        if($pins['idxpin'] == ''){
            $pins['idxpin'] = get_theme_file_uri('/css/css-images').'/sale.png';
        }
        
        $pins['single_pin'] = esc_html(wpresidence_get_option('wp_estate_single_pin','url'));
        if($pins['single_pin'] == ''){
            $pins['single_pin'] = get_theme_file_uri('/css/css-images').'/single.png';
        }
        
        $pins['cloud_pin'] = esc_html(wpresidence_get_option('wp_estate_cloud_pin','url'));
        if($pins['cloud_pin'] == ''){
            $pins['cloud_pin'] = get_theme_file_uri('/css/css-images').'/cloud.png';
        }
        
        $pins['userpin'] = esc_html(wpresidence_get_option('wp_estate_userpin','url'));
        if($pins['userpin'] == ''){
            $pins['userpin'] = get_theme_file_uri('/css/css-images').'/userpin.png';
        }
        
        // Set cache with direct transient function
        set_transient('wpestate_pin_images', $pins, 4*60*60);
    }
    
    return json_encode($pins);
}
endif;

/**
 * Reset pin images cache when taxonomy terms are modified
 * 
 * @param int $term_id The term ID that was modified
 * @param int $tt_id Term taxonomy ID
 * @param string $taxonomy Taxonomy name
 */
function wpestate_reset_pin_images_cache($term_id, $tt_id, $taxonomy) {
    if($taxonomy == 'property_action_category' || $taxonomy == 'property_category') {
        delete_transient('wpestate_pin_images');
    }
}

// Hook into term actions to clear pin cache
add_action('edited_property_action_category', 'wpestate_reset_pin_images_cache', 10, 3);
add_action('edited_property_category', 'wpestate_reset_pin_images_cache', 10, 3);
add_action('created_property_action_category', 'wpestate_reset_pin_images_cache', 10, 3);
add_action('created_property_category', 'wpestate_reset_pin_images_cache', 10, 3);
add_action('delete_property_action_category', 'wpestate_reset_pin_images_cache', 10, 3);
add_action('delete_property_category', 'wpestate_reset_pin_images_cache', 10, 3);


?>
