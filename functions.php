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
    // Cek apakah plugin punya fungsi format rupiah sendiri
    if (function_exists('dw_format_rupiah')) {
        return dw_format_rupiah($angka);
    }
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

// Session Start (Diperlukan untuk tracking guest user sebelum login)
function tema_dw_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'tema_dw_start_session');

/**
 * 5. AJAX Handler untuk Cart (DATABASE VERSION)
 * Menggunakan tabel 'wp_dw_cart' sesuai skema di activation.php plugin.
 */
add_action('wp_ajax_dw_add_to_cart', 'tema_dw_cart_handler');
add_action('wp_ajax_nopriv_dw_add_to_cart', 'tema_dw_cart_handler');

function tema_dw_cart_handler() {
    global $wpdb; // Akses database WordPress

    // Validasi nonce untuk keamanan (opsional, disarankan diaktifkan di production)
    // if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'dw_nonce')) {
    //     wp_send_json_error(['message' => 'Security check failed']);
    // }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $qty        = isset($_POST['qty']) ? intval($_POST['qty']) : 1;
    $variasi_id = isset($_POST['variasi_id']) ? intval($_POST['variasi_id']) : 0;

    if (!$product_id) {
        wp_send_json_error(['message' => 'Produk tidak valid']);
    }

    // Tentukan Tabel Cart (Sesuai activation.php plugin)
    $table_name = $wpdb->prefix . 'dw_cart';

    // Cek apakah tabel ada (untuk memastikan plugin aktif)
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // Fallback ke Session jika plugin tidak aktif/tabel hilang
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

    // Identifikasi User (Logged in vs Guest via Session ID)
    $user_id = get_current_user_id(); // 0 jika guest
    $session_id = session_id();

    // Cek apakah produk sudah ada di cart user tersebut
    // Sesuai skema: user_id (bigint), session_id (varchar), id_produk, qty
    if ($user_id > 0) {
        // Logic untuk User Login
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, qty FROM $table_name WHERE user_id = %d AND id_produk = %d AND id_variasi = %d",
            $user_id, $product_id, $variasi_id
        ));
    } else {
        // Logic untuk Guest (pakai Session ID)
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, qty FROM $table_name WHERE session_id = %s AND id_produk = %d AND id_variasi = %d",
            $session_id, $product_id, $variasi_id
        ));
    }

    if ($existing) {
        // UPDATE: Tambahkan quantity
        $new_qty = $existing->qty + $qty;
        $wpdb->update(
            $table_name,
            array('qty' => $new_qty, 'updated_at' => current_time('mysql')),
            array('id' => $existing->id),
            array('%d', '%s'),
            array('%d')
        );
    } else {
        // INSERT: Buat baris baru
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => ($user_id > 0) ? $user_id : null, // Sesuai schema: NULLABLE
                'session_id' => $session_id,
                'id_produk' => $product_id,
                'id_variasi' => $variasi_id,
                'qty' => $qty,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%d', '%d', '%s')
        );
    }

    // Hitung Total Item di Cart untuk update UI badge
    if ($user_id > 0) {
        $total_items = $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_name WHERE user_id = %d", $user_id));
    } else {
        $total_items = $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_name WHERE session_id = %s", $session_id));
    }

    wp_send_json_success([
        'message' => 'Berhasil ditambahkan ke Database', 
        'cart_count' => (int) $total_items
    ]);
}
?>