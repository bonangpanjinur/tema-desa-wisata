<?php
/**
 * Functions and definitions
 * Menggunakan prefix 'tema_dw_' untuk menghindari konflik dengan plugin 'desa-wisata-core'
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * 1. Theme Support & Setup
 */
function tema_dw_setup() {
    // Menambahkan dukungan title tag
    add_theme_support('title-tag');

    // Menambahkan dukungan featured image
    add_theme_support('post-thumbnails');

    // Menambahkan dukungan custom logo
    add_theme_support('custom-logo', array(
        'height'      => 60,
        'width'       => 180,
        'flex-height' => true,
        'flex-width'  => true,
    ));

    // HTML5 support
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action('after_setup_theme', 'tema_dw_setup');

/**
 * 2. Enqueue Scripts & Styles (Load Tailwind)
 */
function tema_dw_scripts() {
    // Load Tailwind CSS via CDN (Versi Production harusnya di-build, tapi ini agar langsung jalan)
    wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.4.0', false);
    
    // Konfigurasi Tailwind Custom (Warna Desa)
    wp_add_inline_script('tailwindcss', "
        tailwind.config = {
          theme: {
            extend: {
              colors: {
                primary: '#16a34a', // Hijau desa (green-600)
                primaryDark: '#15803d', // Hijau tua (green-700)
                secondary: '#ca8a04', // Kuning emas (yellow-600)
                accent: '#0f172a', // Biru gelap (slate-900)
                surface: '#f8fafc', // Background terang (slate-50)
              },
              fontFamily: {
                sans: ['Inter', 'sans-serif'],
              }
            }
          }
        }
    ");

    // Load Google Fonts (Inter)
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);

    // Font Awesome Free
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');

    // Style.css bawaan (hanya untuk metadata, visual utama via Tailwind)
    wp_enqueue_style('tema-dw-style', get_stylesheet_uri());

    // Main JS Custom
    wp_enqueue_script('tema-dw-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.1', true);

    // Localize Script untuk AJAX (Penting untuk Cart/Filter)
    wp_localize_script('tema-dw-main', 'dw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_nonce')
    ));

    // Script Khusus Halaman
    if (is_post_type_archive('dw_produk') || is_tax('kategori_produk')) {
          wp_enqueue_script('tema-dw-filter', get_template_directory_uri() . '/assets/js/archive-filter.js', array('jquery'), '1.0.0', true);
    }
    
    if (is_page_template('page-cart.php') || is_page_template('page-checkout.php')) {
        wp_enqueue_script('tema-dw-checkout', get_template_directory_uri() . '/assets/js/dw-checkout.js', array('jquery'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'tema_dw_scripts');

/**
 * 3. Register CPT & Taxonomies
 * PENTING: Kita cek dulu apakah function sudah ada (dari plugin) agar tidak ERROR FATAL.
 */

if (!function_exists('dw_register_cpts')) {
    function tema_dw_register_cpts() {
        // CPT Produk
        register_post_type('dw_produk', array(
            'labels' => array('name' => 'Produk Desa', 'singular_name' => 'Produk'),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-cart',
            'rewrite' => array('slug' => 'produk'),
        ));

        // CPT Wisata
        register_post_type('dw_wisata', array(
            'labels' => array('name' => 'Paket Wisata', 'singular_name' => 'Wisata'),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'menu_icon' => 'dashicons-palmtree',
            'rewrite' => array('slug' => 'wisata'),
        ));

        // CPT Transaksi
        register_post_type('dw_transaksi', array(
            'labels' => array('name' => 'Transaksi', 'singular_name' => 'Transaksi'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title'),
            'menu_icon' => 'dashicons-money-alt',
        ));
    }
    add_action('init', 'tema_dw_register_cpts');
}

if (!function_exists('dw_register_taxonomies')) {
    function tema_dw_register_taxonomies() {
        register_taxonomy('kategori_produk', 'dw_produk', array(
            'labels' => array('name' => 'Kategori Produk'),
            'hierarchical' => true,
            'rewrite' => array('slug' => 'kategori-produk'),
        ));

        register_taxonomy('kategori_wisata', 'dw_wisata', array(
            'labels' => array('name' => 'Kategori Wisata'),
            'hierarchical' => true,
            'rewrite' => array('slug' => 'kategori-wisata'),
        ));
    }
    add_action('init', 'tema_dw_register_taxonomies');
}

/**
 * 4. Helper Functions
 */

// Format Rupiah
function tema_dw_format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Redirect Login
function tema_dw_login_redirect($url, $request, $user) {
    if ($user && is_object($user) && is_a($user, 'WP_User')) {
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        } elseif (in_array('pengelola_desa', $user->roles)) {
            return home_url('/dashboard-desa');
        } elseif (in_array('pedagang', $user->roles)) {
            return home_url('/dashboard-toko');
        } elseif (in_array('ojek_wisata', $user->roles)) {
            return home_url('/dashboard-ojek');
        } else {
            return home_url('/akun-saya');
        }
    }
    return $url;
}
add_filter('login_redirect', 'tema_dw_login_redirect', 10, 3);

// Sembunyikan Admin Bar untuk user biasa
function tema_dw_disable_admin_bar() {
    if (!current_user_can('edit_posts')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'tema_dw_disable_admin_bar');

// Session Start (untuk Simple Cart)
function tema_dw_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'tema_dw_start_session');

/**
 * 5. AJAX Handler untuk Cart (Contoh Basic)
 */
add_action('wp_ajax_dw_add_to_cart', 'tema_dw_cart_handler');
add_action('wp_ajax_nopriv_dw_add_to_cart', 'tema_dw_cart_handler');

function tema_dw_cart_handler() {
    // Validasi nonce di production!
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

    if (!$product_id) {
        wp_send_json_error(['message' => 'Produk tidak valid']);
    }

    if (!isset($_SESSION['dw_cart'])) {
        $_SESSION['dw_cart'] = [];
    }

    if (isset($_SESSION['dw_cart'][$product_id])) {
        $_SESSION['dw_cart'][$product_id] += $qty;
    } else {
        $_SESSION['dw_cart'][$product_id] = $qty;
    }

    wp_send_json_success([
        'message' => 'Berhasil ditambahkan', 
        'cart_count' => array_sum($_SESSION['dw_cart'])
    ]);
}
?>