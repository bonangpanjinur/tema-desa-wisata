<?php
/**
 * Optimized Script & Style Enqueue
 * Part of Phase 1 Performance Improvement
 */

function tema_dw_scripts_optimized() {
    // 1. Tailwind CSS - Tetap di header karena krusial untuk layout (LCP)
    wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.4.0', false);
    
    wp_add_inline_script('tailwindcss', "
        tailwind.config = {
          theme: {
            extend: {
              colors: {
                primary: '#16a34a', 
                primaryDark: '#15803d', 
                secondary: '#ca8a04', 
                accent: '#0f172a', 
                surface: '#f8fafc', 
              },
              fontFamily: {
                sans: ['Inter', 'sans-serif'],
              }
            }
          }
        }
    ");

    // 2. Google Fonts - Gunakan &display=swap untuk performa
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);
    
    // 3. Font Awesome - Load di footer jika memungkinkan, atau gunakan kit yang lebih ringan
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
    
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style('tema-dw-style', get_stylesheet_uri());
    
    if (file_exists(get_template_directory() . '/assets/css/main.css')) {
        wp_enqueue_style('tema-dw-main-css', get_template_directory_uri() . '/assets/css/main.css', array(), filemtime(get_template_directory() . '/assets/css/main.css'));
    }

    // 4. Script Utama Theme - Pindahkan ke footer (true)
    wp_enqueue_script('tema-dw-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.8', true);
    
    // Data Global untuk Script Utama
    wp_localize_script('tema-dw-main', 'dwData', array(
        'api_url' => home_url('/wp-json/dw/v1/'),
        'home_url' => home_url()
    ));

    // 5. Conditional Loading - Hanya muat script di halaman yang membutuhkan
    
    // Archive Filters
    if (is_post_type_archive('dw_produk') || is_tax('kategori_produk') || is_post_type_archive('dw_wisata')) {
          wp_enqueue_script('tema-dw-filter', get_template_directory_uri() . '/assets/js/archive-filter.js', array('jquery'), '1.0.0', true);
    }

    // Dashboard Scripts
    if ( is_page_template( array('page-dashboard-desa.php', 'page-dashboard-verifikator.php') ) ) {
        wp_enqueue_script( 'dw-verifikator', get_template_directory_uri() . '/assets/js/dw-verifikator.js', array('jquery'), '1.0.0', true );
    }
    
    if ( is_page_template( 'page-dashboard-toko.php' ) ) {
        wp_enqueue_script( 'dw-pedagang', get_template_directory_uri() . '/assets/js/dw-pedagang.js', array('jquery'), '1.0.0', true );
    }

    if ( is_page_template( 'page-dashboard-ojek.php' ) ) {
        wp_enqueue_script( 'dw-ojek', get_template_directory_uri() . '/assets/js/dw-ojek.js', array('jquery'), '1.0.0', true );
    }
    
    if ( is_page_template( 'page-checkout.php' ) || is_page('checkout') ) {
        wp_enqueue_script( 'dw-checkout', get_template_directory_uri() . '/assets/js/dw-checkout.js', array('jquery'), '1.0.0', true );
    }
    
    // AJAX Cart - Hanya di halaman yang relevan
    if ( is_singular('dw_produk') || is_page('keranjang') || is_archive() || is_front_page() ) {
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);
        wp_enqueue_script('dw-ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', array('jquery', 'sweetalert2'), '1.2.0', true);
        
        wp_localize_script('dw-ajax-cart', 'dw_ajax', array(
            'ajax_url'     => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('dw_cart_action'),
            'cart_url'     => home_url('/keranjang'),
            'checkout_url' => home_url('/checkout'),
            'login_url'    => home_url('/login')
        ));
    }
}
// Note: In functions.php, replace the old add_action with:
// add_action('wp_enqueue_scripts', 'tema_dw_scripts_optimized');
