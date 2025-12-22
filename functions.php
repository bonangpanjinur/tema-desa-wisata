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
 * 2. Enqueue Scripts & Styles (Load Tailwind & Assets)
 */
function tema_dw_scripts() {
    // Load Tailwind CSS via CDN
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

    // Style.css bawaan
    wp_enqueue_style('tema-dw-style', get_stylesheet_uri());
    
    // Main Custom CSS (Untuk custom style tambahan di luar Tailwind)
    wp_enqueue_style('tema-dw-main-css', get_template_directory_uri() . '/assets/css/main.css', array(), filemtime(get_template_directory() . '/assets/css/main.css'));

    // Main JS Custom
    wp_enqueue_script('tema-dw-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.1', true);

    // Localize Script untuk AJAX Global
    wp_localize_script('tema-dw-main', 'dw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_nonce')
    ));

    // Script Filter (Hanya di halaman arsip)
    if (is_post_type_archive('dw_produk') || is_tax('kategori_produk') || is_post_type_archive('dw_wisata')) {
         wp_enqueue_script('tema-dw-filter', get_template_directory_uri() . '/assets/js/archive-filter.js', array('jquery'), '1.0.0', true);
    }
    
    // Script Checkout (Hanya di halaman Cart/Checkout)
    if (is_page_template('page-cart.php') || is_page_template('page-checkout.php')) {
        wp_enqueue_script('tema-dw-checkout', get_template_directory_uri() . '/assets/js/dw-checkout.js', array('jquery'), '1.0.0', true);
    }
    
    // Script AJAX Cart untuk Single Produk (Custom Route)
    if (get_query_var('dw_type') == 'produk') {
        wp_enqueue_script('dw-ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', array('jquery'), '1.0', true);
        wp_localize_script('dw-ajax-cart', 'dw_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('dw_cart_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'tema_dw_scripts');

/**
 * 3. Helper Functions
 */

// Format Rupiah
function tema_dw_format_rupiah($angka) {
    if (function_exists('dw_format_rupiah')) {
        return dw_format_rupiah($angka);
    }
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Redirect Login Berdasarkan Role
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

// Sembunyikan Admin Bar untuk User Biasa
function tema_dw_disable_admin_bar() {
    if (!current_user_can('edit_posts')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'tema_dw_disable_admin_bar');

// Session Start (Untuk Guest Cart)
function tema_dw_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'tema_dw_start_session');

/**
 * 4. CUSTOM REWRITE RULES & ROUTING
 * Agar URL /wisata/slug dan /produk/slug bekerja dengan benar.
 */
function tema_dw_rewrite_rules() {
    // Rule untuk Wisata
    add_rewrite_rule(
        '^wisata/([^/]*)/?',
        'index.php?dw_type=wisata&dw_slug=$matches[1]',
        'top'
    );
    
    // Rule untuk Produk
    add_rewrite_rule(
        '^produk/([^/]*)/?',
        'index.php?dw_type=produk&dw_slug=$matches[1]',
        'top'
    );
}
add_action('init', 'tema_dw_rewrite_rules');

// Daftarkan Query Vars
function tema_dw_query_vars($vars) {
    $vars[] = 'dw_type';
    $vars[] = 'dw_slug';
    return $vars;
}
add_filter('query_vars', 'tema_dw_query_vars');

// Template Include Logic
function tema_dw_template_include($template) {
    $dw_type = get_query_var('dw_type');
    
    // Load single-dw_wisata.php
    if ($dw_type == 'wisata') {
        $new_template = locate_template(array('single-dw_wisata.php'));
        if ('' != $new_template) {
            return $new_template;
        }
    }
    
    // Load single-dw_produk.php
    if ($dw_type == 'produk') {
        $new_template = locate_template(array('single-dw_produk.php'));
        if ('' != $new_template) {
            return $new_template;
        }
    }
    
    return $template;
}
add_filter('template_include', 'tema_dw_template_include');

/**
 * 5. AJAX Handler untuk Cart (DATABASE VERSION)
 * Menggunakan tabel 'wp_dw_cart' sesuai skema di activation.php plugin.
 */
add_action('wp_ajax_dw_add_to_cart', 'tema_dw_cart_handler');
add_action('wp_ajax_nopriv_dw_add_to_cart', 'tema_dw_cart_handler');

function tema_dw_cart_handler() {
    global $wpdb;

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $qty        = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
    $variasi_id = isset($_POST['variasi_id']) ? intval($_POST['variasi_id']) : 0;

    if (!$product_id) {
        wp_send_json_error(['message' => 'Produk tidak valid']);
    }

    $table_name = $wpdb->prefix . 'dw_cart';

    // Cek keberadaan tabel (Fallback ke Session jika plugin mati)
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        if (!isset($_SESSION['dw_cart'])) $_SESSION['dw_cart'] = [];
        if (isset($_SESSION['dw_cart'][$product_id])) {
            $_SESSION['dw_cart'][$product_id] += $qty;
        } else {
            $_SESSION['dw_cart'][$product_id] = $qty;
        }
        wp_send_json_success([
            'message' => 'Berhasil (Session Mode)', 
            'cart_count' => array_sum($_SESSION['dw_cart'])
        ]);
        return;
    }

    $user_id = get_current_user_id(); 
    $session_id = session_id();

    // Cek item di DB
    if ($user_id > 0) {
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, qty FROM $table_name WHERE user_id = %d AND id_produk = %d AND id_variasi = %d",
            $user_id, $product_id, $variasi_id
        ));
    } else {
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, qty FROM $table_name WHERE session_id = %s AND id_produk = %d AND id_variasi = %d",
            $session_id, $product_id, $variasi_id
        ));
    }

    if ($existing) {
        // UPDATE
        $new_qty = $existing->qty + $qty;
        $wpdb->update(
            $table_name,
            array('qty' => $new_qty, 'updated_at' => current_time('mysql')),
            array('id' => $existing->id),
            array('%d', '%s'),
            array('%d')
        );
    } else {
        // INSERT
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => ($user_id > 0) ? $user_id : null,
                'session_id' => $session_id,
                'id_produk' => $product_id,
                'id_variasi' => $variasi_id,
                'qty' => $qty,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%d', '%d', '%s')
        );
    }

    // Hitung Total Item
    if ($user_id > 0) {
        $total_items = $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_name WHERE user_id = %d", $user_id));
    } else {
        $total_items = $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_name WHERE session_id = %s", $session_id));
    }

    wp_send_json_success([
        'message' => 'Berhasil ditambahkan ke Keranjang', 
        'cart_count' => (int) $total_items
    ]);
}
?>