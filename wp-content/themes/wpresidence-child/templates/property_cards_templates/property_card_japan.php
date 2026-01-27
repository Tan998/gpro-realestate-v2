<?php
global $post;

$price   = wpestate_property_price($post->ID);
$address = get_post_meta($post->ID,'property_address',true);
$rooms   = get_post_meta($post->ID,'property_rooms',true);
$size    = get_post_meta($post->ID,'property_size',true);
$year    = get_post_meta($post->ID,'property_year',true);
?>

<div class="jp-property-card">

  <div class="jp-card-image">
    <?php echo wpestate_return_property_list_picture($post->ID,'property_listings'); ?>
  </div>

  <div class="jp-card-content">

    <div class="jp-card-address">
      <?php echo esc_html($address); ?>
    </div>

    <div class="jp-card-price">
      <?php echo esc_html($price); ?>
    </div>

    <div class="jp-card-meta">
      <span>土地: <?php echo esc_html($size); ?>㎡</span>
      <span>間取り: <?php echo esc_html($rooms); ?></span>
      <span>築年: <?php echo esc_html($year); ?></span>
    </div>

    <div class="jp-card-actions">
      <a href="<?php the_permalink(); ?>" class="btn-detail">詳細を見る</a>
      <a href="#" class="btn-like">♡ お気に入り</a>
      <a href="tel:000000000" class="btn-call">電話問合せ</a>
      <a href="<?php the_permalink(); ?>#contact" class="btn-contact">お問合せ（無料）</a>
    </div>

  </div>

</div>
