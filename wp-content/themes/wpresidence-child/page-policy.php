<?php
/*
Template Name: Policy Page Template
*/
get_header();
add_filter('body_class', function ($classes) {
  $classes[] = 'is-policy-page';
  return $classes;
});

?>

<!-- HERO -->
<?php
$hero_img = get_the_post_thumbnail_url(get_the_ID(), 'full');
?>
<section class="policy-hero"
  style="background-image: url('<?php echo esc_url($hero_img ?: get_stylesheet_directory_uri() . '/assets/policy-default.jpg'); ?>');">
</section>
<div class="policy-hero-inner">
    <h1><?php the_title(); ?></h1>
  </div>
<!-- CONTENT -->
<section class="policy-wrapper">
  <div class="policy-container">

    <!-- LEFT SIDEBAR -->
    <aside class="policy-sidebar">
      <ul class="policy-menu">
        <?php
        global $post;

        // 1. Xác định page cha
        $parent_id = $post->post_parent ? $post->post_parent : $post->ID;

        // 2. Lấy page cha
        $parent_page = get_post($parent_id);

        // 3. Hiển thị page cha
        $is_child_active = ($post->ID != $parent_id);
        $parent_open = $is_child_active ? ' open' : '';

        echo '<li class="policy-parent' . $parent_open . '">';
        echo '<button type="button" class="policy-toggle">';
        echo esc_html($parent_page->post_title);
        echo '<span class="arrow"></span>';
        echo '</button>';

        // 4. Lấy các page con
        $children = get_pages([
          'child_of' => $parent_id,
          'sort_column' => 'menu_order',
          'post_status' => 'publish'
        ]);

        // 5. Hiển thị page con
        echo '<ul class="policy-submenu">';

        foreach ($children as $child) {
          $active = ($post->ID == $child->ID) ? 'current' : '';
          echo '<li class="policy-child ' . $active . '">';
          echo '<a href="' . get_permalink($child->ID) . '">' . esc_html($child->post_title) . '</a>';
          echo '</li>';
        }

        echo '</ul>';
        echo '</li>'; // đóng policy-parent
        ?>
      </ul>
    </aside>


    <!-- MAIN CONTENT -->
    <main class="policy-content">
      <?php
      while ( have_posts() ) :
        the_post();
        the_content();
      endwhile;
      ?>
    </main>

  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const parents = document.querySelectorAll('.policy-parent');

  function isMobile() {
    return window.innerWidth <= 768;
  }

  parents.forEach(parent => {
    const toggle = parent.querySelector('.policy-toggle');
    const submenu = parent.querySelector('.policy-submenu');

    // Auto open nếu có page con active
    if (parent.querySelector('.policy-child.current')) {
      parent.classList.add('open');
    }

    toggle.addEventListener('click', function () {
      if (isMobile()) {
        // Mobile: chỉ mở 1 dropdown
        parents.forEach(p => {
          if (p !== parent) p.classList.remove('open');
        });
      }
      parent.classList.toggle('open');
    });
  });

  // Khi resize từ mobile -> desktop, mở lại dropdown đang active
  window.addEventListener('resize', function () {
    if (!isMobile()) {
      parents.forEach(parent => {
        if (parent.querySelector('.policy-child.current')) {
          parent.classList.add('open');
        }
      });
    }
  });
});
</script>


<?php get_footer(); ?>
