<?php
/**
 * Functions and definitions for Tema Desa Wisata
 * Integrasi Penuh dengan Desa Wisata Core DB
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================================
 * 1. SETUP DASAR THEME
 * ============================================================================
 */
function tema_desa_wisata_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    
    // Register Menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'tema-desa-wisata'),
        'footer'  => __('Footer Menu', 'tema-desa-wisata'),
    ));
}
add_action('after_setup_theme', 'tema_desa_wisata_setup');

/**
 * ============================================================================
 * 2. ENQUEUE SCRIPTS & STYLES
 * ============================================================================
 */
function tema_desa_wisata_scripts() {
    // Styles
    wp_enqueue_style('main-style', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0');
    wp_enqueue_style('theme-style', get_stylesheet_uri());
    
    // Scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('main-script', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', array('jquery'), '1.0.0', true);

    // Localize Script untuk AJAX Cart
    wp_localize_script('ajax-cart', 'dw_ajax', array(
        'ajax_url'     => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('dw_cart_nonce'),
        'site_url'     => home_url(),
        'checkout_url' => home_url('/checkout')
    ));
}
add_action('wp_enqueue_scripts', 'tema_desa_wisata_scripts');

/**
 * ============================================================================
 * 3. SESSION START (Untuk Cart Sementara)
 * ============================================================================
 */
function dw_theme_start_session() {
    if (!session_id() && !headers_sent()) {
        session_start();
    }
}
add_action('init', 'dw_theme_start_session', 1);

/**
 * ============================================================================
 * 4. HELPER FUNCTIONS
 * ============================================================================
 */

// Format Rupiah
if (!function_exists('dw_format_rupiah')) {
    function dw_format_rupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}

// Ambil Data Pedagang by User ID
if (!function_exists('dw_get_pedagang_data')) {
    function dw_get_pedagang_data($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'dw_pedagang';
        // Cek table exists untuk menghindari error saat baru install
        if($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) return null;
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id_user = %d", $user_id));
    }
}

// Get Cart Items
if (!function_exists('dw_get_cart_items')) {
    function dw_get_cart_items() {
        return isset($_SESSION['dw_cart']) ? $_SESSION['dw_cart'] : array();
    }
}

// Get Cart Total
if (!function_exists('dw_get_cart_total')) {
    function dw_get_cart_total() {
        $items = dw_get_cart_items();
        $total = 0;
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        return $total;
    }
}

/**
 * ============================================================================
 * 5. AJAX HANDLERS (ADD TO CART)
 * ============================================================================
 */
function dw_ajax_add_to_cart() {
    check_ajax_referer('dw_cart_nonce', 'nonce');

    $product_id = intval($_POST['product_id']);
    $quantity   = intval($_POST['quantity']);
    $is_custom_db = isset($_POST['is_custom_db']) ? true : false;

    if (!$product_id) wp_send_json_error(['message' => 'Produk tidak valid']);

    $product_name  = 'Produk';
    $product_price = 0;
    $product_image = '';

    // Ambil data produk (bisa dari Custom DB atau Post Type)
    if ($is_custom_db) {
        global $wpdb;
        $tbl_prod = $wpdb->prefix . 'dw_produk';
        $prod = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl_prod WHERE id = %d", $product_id));
        
        if($prod) {
            $product_name  = $prod->nama_produk;
            $product_price = $prod->harga;
            $product_image = $prod->foto_utama;
        }
    } else {
        // Fallback ke Post Type (jika pakai WooCommerce/CPT biasa)
        $product = get_post($product_id);
        if($product) {
            $product_name  = $product->post_title;
            $product_price = get_post_meta($product_id, 'dw_harga_produk', true) ?: 0;
            $product_image = get_the_post_thumbnail_url($product_id, 'thumbnail');
        }
    }

    if (!isset($_SESSION['dw_cart'])) $_SESSION['dw_cart'] = [];

    // Cek apakah produk sudah ada di cart
    $found = false;
    foreach ($_SESSION['dw_cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }

    // Jika belum ada, tambahkan baru
    if (!$found) {
        $_SESSION['dw_cart'][] = [
            'product_id' => $product_id,
            'name'       => $product_name,
            'price'      => $product_price,
            'image'      => $product_image,
            'quantity'   => $quantity
        ];
    }

    wp_send_json_success(['message' => 'Berhasil masuk keranjang', 'count' => count($_SESSION['dw_cart'])]);
}
add_action('wp_ajax_dw_theme_add_to_cart', 'dw_ajax_add_to_cart');
add_action('wp_ajax_nopriv_dw_theme_add_to_cart', 'dw_ajax_add_to_cart');

/**
 * ============================================================================
 * 6. CHECKOUT HANDLER (FORM POST)
 * ============================================================================
 */
function dw_process_checkout_handler() {
    // 1. Validasi Request
    if (!isset($_POST['action']) || $_POST['action'] !== 'dw_process_checkout') return;
    if (!isset($_POST['dw_checkout_nonce']) || !wp_verify_nonce($_POST['dw_checkout_nonce'], 'dw_checkout_action')) wp_die('Security Check Failed');
    
    // 2. Cek Login
    if (!is_user_logged_in()) { wp_redirect(home_url('/login')); exit; }

    // 3. Cek Cart Kosong
    $cart = dw_get_cart_items();
    if (empty($cart)) { wp_redirect(home_url('/cart')); exit; }

    global $wpdb;
    $user_id = get_current_user_id();
    
    // Nama Tabel
    $tbl_transaksi = $wpdb->prefix . 'dw_transaksi';
    $tbl_sub       = $wpdb->prefix . 'dw_transaksi_sub';
    $tbl_items     = $wpdb->prefix . 'dw_transaksi_items';
    $tbl_pedagang  = $wpdb->prefix . 'dw_pedagang';
    $tbl_produk    = $wpdb->prefix . 'dw_produk'; 

    // 4. Kelompokkan Order per Toko
    $grouped_orders = [];
    $grand_total = 0;

    foreach ($cart as $item) {
        $product_id = $item['product_id'];
        
        // Ambil ID Pedagang dari produk
        $prod_data = $wpdb->get_row($wpdb->prepare("SELECT id_pedagang FROM $tbl_produk WHERE id = %d", $product_id));
        
        $pedagang_id = 0;
        $nama_toko   = get_bloginfo('name'); // Default nama toko

        if ($prod_data) {
            $pedagang_id = $prod_data->id_pedagang;
            $pedagang_info = $wpdb->get_row($wpdb->prepare("SELECT nama_toko FROM $tbl_pedagang WHERE id = %d", $pedagang_id));
            if($pedagang_info) $nama_toko = $pedagang_info->nama_toko;
        }

        $subtotal = $item['price'] * $item['quantity'];
        $grand_total += $subtotal;

        if (!isset($grouped_orders[$pedagang_id])) {
            $grouped_orders[$pedagang_id] = [
                'nama_toko'  => $nama_toko, 
                'total_toko' => 0, 
                'items'      => []
            ];
        }
        $grouped_orders[$pedagang_id]['items'][] = $item;
        $grouped_orders[$pedagang_id]['total_toko'] += $subtotal;
    }

    // 5. Insert Transaksi Utama (Parent)
    $kode_unik = 'TRX-' . strtoupper(wp_generate_password(6, false));
    
    $wpdb->insert($tbl_transaksi, [
        'id_pembeli'        => $user_id,
        'kode_unik'         => $kode_unik,
        'total_produk'      => $grand_total, 
        'total_transaksi'   => $grand_total, // Belum termasuk ongkir
        'status_transaksi'  => 'menunggu_pembayaran',
        'alamat_lengkap'    => sanitize_textarea_field($_POST['billing_address']),
        'nama_penerima'     => sanitize_text_field($_POST['billing_name']),
        'no_hp'             => sanitize_text_field($_POST['billing_phone']),
        'metode_pembayaran' => sanitize_text_field($_POST['payment_method']),
        'created_at'        => current_time('mysql')
    ]);
    $parent_trx_id = $wpdb->insert_id;

    // 6. Insert Sub Transaksi & Items
    foreach ($grouped_orders as $pid => $data) {
        $wpdb->insert($tbl_sub, [
            'id_transaksi'       => $parent_trx_id,
            'id_pedagang'        => $pid,
            'nama_toko'          => $data['nama_toko'],
            'sub_total'          => $data['total_toko'],
            'ongkir'             => 0, // Hitung ongkir nanti
            'total_pesanan_toko' => $data['total_toko'],
            'status_pesanan'     => 'menunggu_konfirmasi',
            'created_at'         => current_time('mysql')
        ]);
        $sub_trx_id = $wpdb->insert_id;

        foreach ($data['items'] as $prod) {
            $wpdb->insert($tbl_items, [
                'id_sub_transaksi' => $sub_trx_id,
                'id_produk'        => $prod['product_id'],
                'nama_produk'      => $prod['name'],
                'harga_satuan'     => $prod['price'],
                'jumlah'           => $prod['quantity'], 
                'total_harga'      => $prod['price'] * $prod['quantity']
            ]);
        }
    }

    // 7. Bersihkan Cart & Redirect
    unset($_SESSION['dw_cart']);
    wp_redirect(home_url('/transaksi')); // Ganti ke halaman sukses atau list transaksi
    exit;
}
add_action('admin_post_dw_process_checkout', 'dw_process_checkout_handler');

/**
 * ============================================================================
 * 7. LOGIN REDIRECT CUSTOM
 * ============================================================================
 */
function dw_custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('pedagang', $user->roles)) return home_url('/dashboard-toko');
        elseif (in_array('admin_desa', $user->roles)) return home_url('/dashboard-desa');
        elseif (in_array('administrator', $user->roles)) return admin_url();
    }
    return home_url('/akun-saya');
}
add_filter('login_redirect', 'dw_custom_login_redirect', 10, 3);

/**
 * ============================================================================
 * 8. CUSTOM REWRITE RULES (URL CANTIK & SEO FRIENDLY)
 * ============================================================================
 */

// A. Daftarkan variable query baru
function dw_register_query_vars( $vars ) {
    $vars[] = 'dw_slug';      // Untuk Wisata
    $vars[] = 'dw_slug_toko'; // Untuk Profil Toko
    $vars[] = 'dw_slug_desa'; // Untuk Profil Desa
    return $vars;
}
add_filter( 'query_vars', 'dw_register_query_vars' );

// B. Tambahkan Aturan Rewrite URL
function dw_add_rewrite_rules() {
    
    // 1. Profil Desa: sadesa.site/profil/desa/nama-desa
    add_rewrite_rule(
        '^profil/desa/([^/]*)/?',
        'index.php?pagename=profil-desa&dw_slug_desa=$matches[1]',
        'top'
    );

    // 2. Profil Toko: sadesa.site/profil/toko/nama-toko
    add_rewrite_rule(
        '^profil/toko/([^/]*)/?',
        'index.php?pagename=profil-toko&dw_slug_toko=$matches[1]',
        'top'
    );

    // 3. Wisata: sadesa.site/wisata/detail/judul-wisata
    add_rewrite_rule(
        '^wisata/detail/([^/]*)/?',
        'index.php?pagename=detail-wisata&dw_slug=$matches[1]',
        'top'
    );

    // 4. Produk: sadesa.site/produk/detail/judul-produk
    add_rewrite_rule(
        '^produk/detail/([^/]*)/?',
        'index.php?pagename=detail-produk&dw_slug=$matches[1]',
        'top'
    );
}
add_action( 'init', 'dw_add_rewrite_rules' );
?>