<?php
/**
 * Functions and definitions
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * 1. Theme Support
 */
function dw_theme_support() {
    // Menambahkan dukungan title tag
    add_theme_support('title-tag');

    // Menambahkan dukungan featured image
    add_theme_support('post-thumbnails');

    // Menambahkan dukungan custom logo
    add_theme_support('custom-logo', array(
        'height'      => 50,
        'width'       => 150,
        'flex-height' => true,
        'flex-width'  => true,
    ));
}
add_action('after_setup_theme', 'dw_theme_support');

/**
 * 2. Enqueue Scripts & Styles
 */
function dw_enqueue_scripts() {
    // 1. Load Tailwind CSS via CDN (Development Mode)
    // Untuk production disarankan menggunakan build process, tapi ini cara tercepat agar langsung jalan.
    wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.4.0', false);
    
    // Konfigurasi Tailwind untuk warna custom (opsional, disesuaikan dengan tema desa)
    wp_add_inline_script('tailwindcss', "
        tailwind.config = {
          theme: {
            extend: {
              colors: {
                primary: '#16a34a', // green-600
                secondary: '#ca8a04', // yellow-600
                dark: '#1f2937', // gray-800
              }
            }
          }
        }
    ");

    // Load Main CSS Lama (Saya komen agar full Tailwind, aktifkan jika ada style custom spesifik)
    // wp_enqueue_style('dw-main-style', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0');
    
    // Style.css bawaan WP (biarkan untuk metadata, tapi isinya mungkin tidak lagi berpengaruh banyak pada visual)
    wp_enqueue_style('dw-style', get_stylesheet_uri());

    // Font Awesome (Opsional, jika dipakai di desain lama)
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

    // Load JS
    // jQuery (WordPress built-in)
    wp_enqueue_script('jquery');

    // Main JS Custom
    wp_enqueue_script('dw-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true);

    // Localize Script untuk AJAX URL
    wp_localize_script('dw-main-js', 'dw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_nonce') // Security
    ));

    // Script Khusus Halaman
    if (is_post_type_archive('dw_produk') || is_tax('kategori_produk')) {
         wp_enqueue_script('dw-archive-filter', get_template_directory_uri() . '/assets/js/archive-filter.js', array('jquery'), '1.0.0', true);
    }
    
    // Script Cart/Checkout
    if (is_page_template('page-cart.php') || is_page_template('page-checkout.php')) {
        wp_enqueue_script('dw-checkout', get_template_directory_uri() . '/assets/js/dw-checkout.js', array('jquery'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'dw_enqueue_scripts');


/**
 * 3. Register Custom Post Types (CPT)
 * - Produk Desa (dw_produk)
 * - Paket Wisata (dw_wisata)
 * - Transaksi (dw_transaksi)
 */
function dw_register_cpts() {
    
    // A. Produk Desa
    register_post_type('dw_produk', array(
        'labels' => array(
            'name' => 'Produk Desa',
            'singular_name' => 'Produk',
            'add_new' => 'Tambah Produk',
            'add_new_item' => 'Tambah Produk Baru',
            'edit_item' => 'Edit Produk',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'menu_icon' => 'dashicons-cart',
        'rewrite' => array('slug' => 'produk'),
    ));

    // B. Paket Wisata
    register_post_type('dw_wisata', array(
        'labels' => array(
            'name' => 'Paket Wisata',
            'singular_name' => 'Wisata',
            'add_new' => 'Tambah Wisata',
            'edit_item' => 'Edit Wisata',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
        'menu_icon' => 'dashicons-palmtree',
        'rewrite' => array('slug' => 'wisata'),
    ));

    // C. Transaksi
    register_post_type('dw_transaksi', array(
        'labels' => array(
            'name' => 'Transaksi',
            'singular_name' => 'Transaksi',
        ),
        'public' => false, // Tidak bisa diakses publik via URL langsung kecuali admin/code
        'show_ui' => true,
        'supports' => array('title'), // Title bisa diisi No Invoice
        'menu_icon' => 'dashicons-money-alt',
    ));
}
add_action('init', 'dw_register_cpts');

/**
 * 4. Register Taxonomies (Kategori)
 */
function dw_register_taxonomies() {
    // Kategori Produk
    register_taxonomy('kategori_produk', 'dw_produk', array(
        'labels' => array(
            'name' => 'Kategori Produk',
            'singular_name' => 'Kategori',
        ),
        'hierarchical' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'kategori-produk'),
    ));

    // Kategori Wisata
    register_taxonomy('kategori_wisata', 'dw_wisata', array(
        'labels' => array(
            'name' => 'Kategori Wisata',
            'singular_name' => 'Kategori',
        ),
        'hierarchical' => true,
        'show_admin_column' => true,
        'rewrite' => array('slug' => 'kategori-wisata'),
    ));
}
add_action('init', 'dw_register_taxonomies');

/**
 * 5. Custom Roles (Untuk Multi-Vendor / Dashboard User)
 */
function dw_add_roles() {
    // Role: Pengelola Desa (Bisa posting Wisata & Produk Umum)
    add_role('pengelola_desa', 'Pengelola Desa', array(
        'read' => true,
        'upload_files' => true,
    ));

    // Role: Pedagang (Bisa posting Produk saja)
    add_role('pedagang', 'Pedagang', array(
        'read' => true,
        'upload_files' => true,
    ));

    // Role: Ojek Wisata (Bisa terima orderan ojek - logic nanti)
    add_role('ojek_wisata', 'Ojek Wisata', array(
        'read' => true,
    ));
    
    // Role: Wisatawan (Customer biasa)
    // Pakai role 'subscriber' bawaan WP saja atau buat baru
    add_role('wisatawan', 'Wisatawan', array(
        'read' => true,
    ));
}
add_action('init', 'dw_add_roles');

/**
 * 6. Start Session (Untuk Cart sederhana)
 */
function dw_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'dw_start_session');

// Helper function: Format Rupiah
function format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Redirect setelah login berdasarkan role
function dw_login_redirect($url, $request, $user) {
    if ($user && is_object($user) && is_a($user, 'WP_User')) {
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        } elseif (in_array('pengelola_desa', $user->roles)) {
            return home_url('/dashboard-desa'); // Page Template Dashboard Desa
        } elseif (in_array('pedagang', $user->roles)) {
            return home_url('/dashboard-toko'); // Page Template Dashboard Toko
        } elseif (in_array('ojek_wisata', $user->roles)) {
            return home_url('/dashboard-ojek'); // Page Template Dashboard Ojek
        } else {
            return home_url('/akun-saya'); // Page Template Akun Saya (Wisatawan)
        }
    }
    return $url;
}
add_filter('login_redirect', 'dw_login_redirect', 10, 3);

// Sembunyikan Admin Bar untuk non-admin
function dw_disable_admin_bar() {
    if (!current_user_can('administrator')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'dw_disable_admin_bar');

// AJAX Handlers (Contoh Placeholder untuk Cart)
add_action('wp_ajax_dw_add_to_cart', 'dw_add_to_cart_handler');
add_action('wp_ajax_nopriv_dw_add_to_cart', 'dw_add_to_cart_handler');

function dw_add_to_cart_handler() {
    // Logic cart sederhana masuk session
    // ... (Implementasi detail ada di file ajax-cart.js & handler ini)
    
    $product_id = intval($_POST['product_id']);
    $qty = intval($_POST['qty']);

    if (!$product_id) {
        wp_send_json_error(array('message' => 'Produk tidak valid'));
    }

    // Cek session cart
    if (!isset($_SESSION['dw_cart'])) {
        $_SESSION['dw_cart'] = array();
    }

    // Add or Update
    if (isset($_SESSION['dw_cart'][$product_id])) {
        $_SESSION['dw_cart'][$product_id] += $qty;
    } else {
        $_SESSION['dw_cart'][$product_id] = $qty;
    }

    wp_send_json_success(array(
        'message' => 'Produk masuk keranjang', 
        'count' => count($_SESSION['dw_cart'])
    ));
}