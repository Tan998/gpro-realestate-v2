/**
 * Directory Filtering JavaScript
 *
 * This script handles the filtering functionality for the property directory
 * in the WpResidence theme. It manages slider interactions, checkbox filters,
 * and AJAX requests for dynamic content loading.
 *
 * @package WpResidence
 * @subpackage JavaScript
 * @since 1.0 
 */

let sliderTimerScroll;
const sliderAjaxDelayScroll = 400;
let with_clear = 1;

/**
 * Increase the pagination value and trigger filtering
 */
function wpresidence_directory_increase_pagination() {
    const paginationEl = jQuery('#property_dir_pagination');
    let page = parseInt(paginationEl.val(), 10);
    paginationEl.val(++page);
    wpestate_directory_filtering();
}

jQuery(document).ready(function() {
    // Cache the initial text of the load more button
    const loadMoreButton = jQuery('#directory_load_more');
    loadMoreButton.data('default-text', loadMoreButton.text());

    // Load more button click handler
    loadMoreButton.on('click', function(event) {
        const isDone = parseInt(jQuery('#property_dir_done').val(), 10);
        if (isDone === 0) {
            with_clear = 0;
            wpresidence_directory_increase_pagination();
        }
    });

    // Initialize sliders
    wpresidence_directory_initializeSliders();

    // Checkbox change handler
    jQuery('.extended_search_check_wrapper_directory input[type="checkbox"]').on('click', wpestate_directory_filtering);

    // Dropdown selection handler
    jQuery('.listing_filters_head_directory li, .directory_sidebar li').on('click', function() {
        const pick = jQuery(this).text();
        const value = jQuery(this).attr('data-value');
        const parent = jQuery(this).parent().parent();
        
        parent.find('.filter_menu_trigger')
            .text(pick)
            .append('<span class="caret caret_filter"></span>')
            .attr('data-value', value);
        
        parent.find('input:hidden').val(value);
        wpestate_directory_filtering();
    });

    // Status and keyword change handler
    jQuery('#property_status, #property_keyword').on('change', wpestate_directory_filtering);
});

let sliderTimer;
const sliderAjaxDelay = 500;

/**
 * Trigger directory filtering with a delay
 */
function wpestate_directory_filtering() {
    if (sliderTimer) {
        clearTimeout(sliderTimer);
    }
    //sliderTimer = setTimeout(wpresidence_directory_filtering_action, sliderAjaxDelay);
    filter_container    =   jQuery('.directory_sidebar_wrapper');
    cards_container     =   jQuery('#listing_ajax_container');
    do_clear_container  = parseInt(with_clear, 10);
    sliderTimer = setTimeout(function(){
        wpresidence_universal_query_filtering_action(filter_container,cards_container,do_clear_container);
    }, sliderAjaxDelay);

}

/**
 * Perform the actual directory filtering action
 */
function wpresidence_directory_filtering_action() {





    with_clear = parseInt(with_clear, 10);
   
    if (with_clear === 1) {
        jQuery('#property_dir_pagination').val('1');
        wpresidence_directory_updateLoadMoreButton(false);
    }
    
    const ajaxurl = ajaxcalls_vars.admin_url + 'admin-ajax.php';
    const filterData = wpresidence_directory_getFilterData();

    
    // Show skeleton placeholders instead of generic loader
   

   if (with_clear === 1) {
    // Remove only real cards, keep skeletons
        wpestate_createSkeletons(jQuery('#listing_ajax_container'));
    }else{
        wpestate_createSkeletons(jQuery('#listing_ajax_container'),'true');
    }

    jQuery('.pagination_nojax').hide();
    //jQuery('#listing_loader').show();
    
    const nonce = jQuery('#wpestate_ajax_filtering').val();
    
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        dataType: 'json',
        data: {
            'action': 'wpestate_classic_ondemand_directory',
            ...filterData,
            'security': nonce
        },
        success: function(data) {
            wpresidence_directory_handleAjaxSuccess(data);
        },
        error: function(errorThrown) {
             jQuery('.skeleton-placeholder').remove();
            jQuery('#listing_loader').hide();
            console.error('AJAX error:', errorThrown);
        }
    });
    
    with_clear = 1;
}

/**
 * Initialize all sliders
 */
function wpresidence_directory_initializeSliders() {
    const sliders = [
        { id: '#slider_price_widget', change: wpestate_directory_filtering },
        { id: '#slider_property_size_widget', config: wpresidence_directory_getSizeSliderConfig() },
        { id: '#slider_property_lot_size_widget', config: wpresidence_directory_getLotSizeSliderConfig() },
        { id: '#slider_property_rooms_widget', config: wpresidence_directory_getRoomsSliderConfig('rooms') },
        { id: '#slider_property_bedrooms_widget', config: wpresidence_directory_getRoomsSliderConfig('bedrooms') },
        { id: '#slider_property_bathrooms_widget', config: wpresidence_directory_getRoomsSliderConfig('bathrooms') }
    ];

    sliders.forEach(slider => {
        if (slider.change) {
            jQuery(slider.id).slider({ change: slider.change });
        } else {
            jQuery(slider.id).slider(slider.config);
        }
    });
}

/**
 * Get configuration for size slider
 */
function wpresidence_directory_getSizeSliderConfig() {
    return {
        range: true,
        min: parseFloat(directory_vars.dir_min_size),
        max: parseFloat(directory_vars.dir_max_size),
        values: [directory_vars.dir_min_size, directory_vars.dir_max_size],
        slide: function(event, ui) {
            jQuery("#property_size_low").val(ui.values[0]);
            jQuery("#property_size_max").val(ui.values[1]);
            jQuery("#property_size").html(ui.values[0] + " " + directory_vars.measures_sys + " " + control_vars.to + " " + ui.values[1] + " " + directory_vars.measures_sys);
            wpestate_directory_filtering();
        }
    };
}

/**
 * Get configuration for lot size slider
 */
function wpresidence_directory_getLotSizeSliderConfig() {
    return {
        range: true,
        min: parseFloat(directory_vars.dir_min_lot_size),
        max: parseFloat(directory_vars.dir_max_lot_size),
        values: [directory_vars.dir_min_lot_size, directory_vars.dir_max_lot_size],
        slide: function(event, ui) {
            jQuery("#property_lot_size_low").val(ui.values[0]);
            jQuery("#property_lot_size_max").val(ui.values[1]);
            jQuery("#property_lot_size").html(ui.values[0] + " " + directory_vars.measures_sys + " " + control_vars.to + " " + ui.values[1] + " " + directory_vars.measures_sys);
            wpestate_directory_filtering();
        }
    };
}

/**
 * Get configuration for rooms, bedrooms, or bathrooms slider
 */
function wpresidence_directory_getRoomsSliderConfig(type) {
    return {
        range: true,
        min: parseFloat(directory_vars[`dir_${type}_min`]),
        max: parseFloat(directory_vars[`dir_${type}_max`]),
        values: [directory_vars[`dir_${type}_min`], directory_vars[`dir_${type}_max`]],
        slide: function(event, ui) {
            jQuery(`#property_${type}_low`).val(ui.values[0]);
            jQuery(`#property_${type}_max`).val(ui.values[1]);
            jQuery(`#property_${type}`).text(ui.values[0] + " " + control_vars.to + " " + ui.values[1]);
            wpestate_directory_filtering();
        }
    };
}

/**
 * Get all filter data
 */
function wpresidence_directory_getFilterData() {
    const data = {
        'action_values': jQuery('#sidebar-adv_actions').attr('data-value'),
        'category_values': jQuery('#sidebar-adv_categ').attr('data-value'),
        'city': jQuery('#sidebar-advanced_city').attr('data-value'),
        'area': jQuery('#sidebar-adv_area').attr('data-value'),
        'county': jQuery('#sidebar-adv_conty').attr('data-value'),
        'price_low': parseFloat(jQuery('#price_low_widget').val(), 10),
        'price_max': parseFloat(jQuery('#price_max_widget').val(), 10),
        'postid': directory_vars.post_id,
        'min_size': parseFloat(jQuery('#property_size_low').val(), 10),
        'max_size': parseFloat(jQuery('#property_size_max').val(), 10),
        'min_lot_size': parseFloat(jQuery('#property_lot_size_low').val(), 10),
        'max_lot_size': parseFloat(jQuery('#property_lot_size_max').val(), 10),
        'min_rooms': parseFloat(jQuery('#property_rooms_low').val(), 10),
        'max_rooms': parseFloat(jQuery('#property_rooms_max').val(), 10),
        'min_bedrooms': parseFloat(jQuery('#property_bedrooms_low').val(), 10),
        'max_bedrooms': parseFloat(jQuery('#property_bedrooms_max').val(), 10),
        'min_bathrooms': parseFloat(jQuery('#property_bathrooms_low').val(), 10),
        'max_bathrooms': parseFloat(jQuery('#property_bathrooms_max').val(), 10),
        'status': jQuery('#property_status').val(),
        'keyword': jQuery('#property_keyword').val(),
        'order': jQuery('#a_filter_order_directory').attr('data-value'),
        'pagination': jQuery('#property_dir_pagination').val(),
        'all_checkers': wpresidence_directory_getCheckedFilters()
    };

    return data;
}

/**
 * Get all checked filters
 */
function wpresidence_directory_getCheckedFilters() {
    return jQuery('.extended_search_check_wrapper_directory input[type="checkbox"]:checked')
        .map(function() {
            return jQuery(this).attr("name-title");
        })
        .get()
        .join(',');
}










/**
 * Handle successful AJAX response
 */
function wpresidence_directory_handleAjaxSuccess(data) {
    const totalResults = parseInt(data.no_results, 10);
    const perPage = parseInt(jQuery('#property_dir_per_page').val(), 10);
    const listingContainer = jQuery('#listing_ajax_container');

    // Always replace skeleton placeholders with returned cards or messages
    wpestate_replaceSkeletons(listingContainer, data.cards);

    const noMore = totalResults < perPage || listingContainer.find('.no_results').length;
    wpresidence_directory_updateLoadMoreButton(noMore);

    jQuery('#listing_loader').hide();
    wpestate_restart_js_after_ajax();
}

/**
 * Update the load more button state
 */
function wpresidence_directory_updateLoadMoreButton(noMore) {
    const loadMoreButton = jQuery('#directory_load_more');
    const defaultText = loadMoreButton.data('default-text') || loadMoreButton.text();
    if (noMore) {
        jQuery('#property_dir_done').val('1');
        loadMoreButton.text(directory_vars.no_more).addClass('no_more_list');
    } else {
        jQuery('#property_dir_done').val('0');
        loadMoreButton.text(defaultText).removeClass('no_more_list');
    }
}




/*===============================================================================================================================================*/

/**
 * Perform the actual directory filtering action
 */
//cards_container = listing_ajax_container

function wpresidence_universal_query_filtering_action(filter_container,cards_container,do_clear_container) {




    do_clear_container = parseInt(do_clear_container, 10);
    if (do_clear_container === 1) {
        filter_container.find('#property_dir_pagination').val('1');
        wpresidence_directory_updateLoadMoreButton(false);
    }
    
    const ajaxurl = ajaxcalls_vars.admin_url + 'admin-ajax.php';
    const filterData = wpresidence_universal_getFilterData(filter_container);

    // Ensure the order value from the dedicated dropdown is always included
    const orderDropdown = jQuery('.wpresidence_wrap_a_filter_order_directory #a_filter_order_directory');
    if (orderDropdown.length) {
        const dropdownValue = orderDropdown.attr('data-value');
        if (typeof dropdownValue !== 'undefined') {
            filterData.order = dropdownValue;
        }
    }

    
    // Show skeleton placeholders instead of generic loader
   

   if (do_clear_container === 1) {
        // Remove only real cards, keep skeletons
        wpestate_createSkeletons(cards_container);
    }else{
        wpestate_createSkeletons(cards_container,'true');
    }

    jQuery('.pagination_nojax').hide();
    //jQuery('#listing_loader').show();
    
    const nonce = jQuery('#wpestate_ajax_filtering').val();
    
    jQuery.ajax({
        type: 'POST',
        url: ajaxurl,
        dataType: 'json',
        data: {
            'action': 'wpestate_classic_ondemand_directory',
            ...filterData,
            'security': nonce
        },
        success: function(data) {
            wpresidence_directory_handleAjaxSuccess(data);
        },
        error: function(errorThrown) {
             jQuery('.skeleton-placeholder').remove();
            jQuery('#listing_loader').hide();
            console.error('AJAX error:', errorThrown);
        }
    });
    
    with_clear = 1;
}


function wpresidence_universal_getFilterData(container) {
    const data = {
        'action_values'     : container.find('#sidebar-adv_actions').attr('data-value'),
        'category_values'   : container.find('#sidebar-adv_categ').attr('data-value'),
        'city_values'       : container.find('#sidebar-advanced_city').attr('data-value'),
        'area_values'       : container.find('#sidebar-adv_area').attr('data-value'),
        'county_values'     : container.find('#sidebar-adv_conty').attr('data-value'),
        'price_low'         : parseFloat(container.find('#price_low_widget').val(), 10),
        'price_max'         : parseFloat(container.find('#price_max_widget').val(), 10),
        'postID'            : directory_vars.post_id,
        'min_size'          : parseFloat(container.find('#property_size_low').val(), 10),
        'max_size'          : parseFloat(container.find('#property_size_max').val(), 10),
        'min_lot_size'      : parseFloat(container.find('#property_lot_size_low').val(), 10),
        'max_lot_size'      : parseFloat(container.find('#property_lot_size_max').val(), 10),
        'min_rooms'         : parseFloat(container.find('#property_rooms_low').val(), 10),
        'max_rooms'         : parseFloat(container.find('#property_rooms_max').val(), 10),
        'min_bedrooms'      : parseFloat(container.find('#property_bedrooms_low').val(), 10),
        'max_bedrooms'      : parseFloat(container.find('#property_bedrooms_max').val(), 10),
        'min_bathrooms'     : parseFloat(container.find('#property_bathrooms_low').val(), 10),
        'max_bathrooms'     : parseFloat(container.find('#property_bathrooms_max').val(), 10),
        'status'            : container.find('#property_status').val(),
        'keyword'           : container.find('#property_keyword').val(),
        'order'             : container.find('#a_filter_order_directory').attr('data-value'),
        'pagination'        : container.find('#property_dir_pagination').val(),
        'all_checkers'      : wpresidence_directory_getCheckedFilters(container)
    };

    return data;
}


function wpresidence_universal_getCheckedFilters(container) {
    return container.find('.extended_search_check_wrapper_directory input[type="checkbox"]:checked')
        .map(function() {
            return jQuery(this).attr("name-title");
        })
        .get()
        .join(',');
}



/**
 * Handle successful AJAX response
 */
function wpresidence_ajax_universal_handleAjaxSuccess(container,cards_container,data) {
    const noResults = parseInt(data.no_results, 10);
    const perPage = parseInt(jQuery('#property_dir_per_page').val(), 10);

    if (noResults !== 0) {
        wpestate_replaceSkeletons(cards_container, data.cards);
    } else {
        jQuery('.skeleton-placeholder').remove();
    }

    const noMore = noResults < perPage || noResults === 0;
    wpresidence_directory_updateLoadMoreButton(noMore);

    //jQuery('#listing_loader').hide();
    wpestate_restart_js_after_ajax();
}