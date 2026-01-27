<?php
/**
 * Custom Property Card Details (Type 3) – Japan style
 * Safe override for WP Residence
 */

// permalink
$link = wpestate_return_data_from_cache_if_exists(
    $property_unit_cached_data,
    $postID,
    '',
    'permalink'
);

// cached meta
$property_size     = wpestate_get_converted_measure_from_cache($property_unit_cached_data, 'property_size');
$property_lot_size = wpestate_get_converted_measure_from_cache($property_unit_cached_data, 'property_lot_size');

$property_rooms = wpestate_return_data_from_cache_if_exists(
    $property_unit_cached_data,
    $postID,
    'meta',
    'property_rooms'
);

$property_year = wpestate_return_data_from_cache_if_exists(
    $property_unit_cached_data,
    $postID,
    'meta',
    'property_year'
);
?>

<div class="jp-property-details-v3">

    <?php if ($property_lot_size != '') : ?>
        <div class="jp-detail-item">
            <span class="jp-label">土地面積</span>
            <span class="jp-value"><?php echo esc_html($property_lot_size); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($property_size != '') : ?>
        <div class="jp-detail-item">
            <span class="jp-label">建物面積</span>
            <span class="jp-value"><?php echo esc_html($property_size); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($property_rooms != '') : ?>
        <div class="jp-detail-item">
            <span class="jp-label">間取り</span>
            <span class="jp-value"><?php echo esc_html($property_rooms); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($property_year != '') : ?>
        <div class="jp-detail-item">
            <span class="jp-label">築年</span>
            <span class="jp-value"><?php echo esc_html($property_year); ?>年</span>
        </div>
    <?php endif; ?>

</div>
