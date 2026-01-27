<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

 
if ( !function_exists( 'wpestate_chld_thm_cfg_parent_css' ) ):
    function wpestate_chld_thm_cfg_parent_css() {
        $parent_style = 'wpestate_style'; 
     
        
        $use_mimify     =   wpresidence_get_option('wp_estate_use_mimify','');
        $mimify_prefix  =   '';
        if($use_mimify==='yes'){
            $mimify_prefix  =   '.min';    
        }
        
        if($mimify_prefix===''){
            wp_enqueue_style($parent_style,get_template_directory_uri().'/style.css', '', '1.0', 'all');  
        }else{
            wp_enqueue_style($parent_style,get_template_directory_uri().'/style.min.css', '', '1.0', 'all');  
        }
        
        if ( is_rtl() ) {
           wp_enqueue_style( 'chld_thm_cfg_parent-rtl',  trailingslashit( get_template_directory_uri() ). '/rtl.css' );
    }
        wp_enqueue_style( 'wpestate-child-style',
            get_stylesheet_directory_uri() . '/style.css',
                array( $parent_style ),
                wp_get_theme()->get('Version')
        );
        
    }
endif;


add_action('after_setup_theme', function() {
    $domain = 'wpresidence';
    $locale = get_locale();

    // 1. Load parent theme translations from WP language directory
    load_theme_textdomain($domain, WP_LANG_DIR . '/themes');
    
    // 2. Load child theme translations
    load_child_theme_textdomain($domain, WP_LANG_DIR . '/themes');
    
    // 3. Fallback to child theme languages directory
    $child_mofile = get_stylesheet_directory() . "/languages/{$locale}.mo";
    if (file_exists($child_mofile)) {
        load_textdomain($domain, $child_mofile);
    }
    
    
});

add_action( 'wp_enqueue_scripts', 'wpestate_chld_thm_cfg_parent_css' );

/**
 * Force WP Residence to use ja_JP locale instead of ja
 */
add_filter('locale', function ($locale) {
    if ($locale === 'ja') {
        return 'ja_JP';
    }
    return $locale;
});

// Disable block widgets editor (use classic widgets)
//add_filter('use_widgets_block_editor', '__return_false');

add_filter('body_class', function ($classes) {

    // áp dụng cho page thường (không phải home)
    if (is_page() && !is_front_page()) {
        $classes[] = 'header_transparent';
    }

    return $classes;
});

add_filter('template_include', function ($template) {
  if (is_page()) {
    global $post;

    // Tìm page cha gốc
    $ancestor_ids = get_post_ancestors($post->ID);
    $root_parent_id = $ancestor_ids ? end($ancestor_ids) : $post->ID;
    $root_parent = get_post($root_parent_id);

    // ĐỔI slug này theo page cha của anh
    if ($root_parent && $root_parent->post_name === 'terms_of_use_privacy_policy') {
      $custom = locate_template('page-policy.php');
      if ($custom) {
        return $custom;
      }
    }
  }
  return $template;
});
add_filter('body_class', function ($classes) {
  if (is_page()) {
    global $post;

    $ancestor_ids = get_post_ancestors($post->ID);
    $root_parent_id = $ancestor_ids ? end($ancestor_ids) : $post->ID;
    $root_parent = get_post($root_parent_id);

    if ($root_parent && $root_parent->post_name === 'terms_of_use_privacy_policy') {
      $classes[] = 'is-policy-page';
    }
  }
  return $classes;
});

//set list view is default in Advanced Search page
add_action('wp_footer', function () {
    ?>
    <script>
    (function () {

        // chỉ áp dụng cho trang advanced-search
        if (!window.location.pathname.includes('/advanced-search')) {
            return;
        }

        function switchToListView() {
            const listBtn = document.getElementById('list_view');
            const gridBtn = document.getElementById('grid_view');

            if (!listBtn || !gridBtn) return;

            // nếu grid đang active thì switch
            if (gridBtn.classList.contains('icon_selected')) {
                listBtn.click();
            }
        }

        // 1️⃣ chạy khi DOM ready
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(switchToListView, 300);
        });

        // 2️⃣ chạy lại khi WP Residence load Ajax results
        document.addEventListener('ajaxComplete', function () {
            setTimeout(switchToListView, 300);
        });

    })();
    </script>
    <?php
});

/**
 * Override GDPR / Terms link for WP Residence forms
 */
add_action('wp_footer', function () {
    $privacy_url = get_privacy_policy_url();
    if (!$privacy_url) return;
    ?>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.wpestate_gdpr_label a').forEach(function (link) {
          link.href = "<?php echo esc_js($privacy_url); ?>";
        });
      });
    </script>
    <?php
});

