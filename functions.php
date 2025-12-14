<?php
/**
 * Functions and definitions for Tema Desa Wisata
 * Integrasi Penuh dengan Desa Wisata Core DB
 */

if (!defined('ABSPATH')) {
    exit;
}

// 1. Setup Dasar Theme
function tema_desa_wisata_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'tema-desa-wisata'),
        'footer'  => __('Footer Menu', 'tema-desa-wisata'),
    ));
}
add_action('after_setup_theme', 'tema_desa_wisata_setup');

// 2. Enqueue Scripts
function tema_desa_wisata_scripts() {
    wp_enqueue_style('main-style', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0');
    wp_enqueue_style('theme-style', get_stylesheet_uri());
    
    wp_enqueue_script('jquery');
    wp_enqueue_script('main-script', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', array('jquery'), '1.0.0', true);

    // Kirim data ke JS
    wp_localize_script('ajax-cart', 'dw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_cart_nonce'),
        'site_url' => home_url(),
        'checkout_url' => home_url('/checkout')
    ));
}
add_action('wp_enqueue_scripts', 'tema_desa_wisata_scripts');

// 3. Session Start (Penting untuk Cart)
function dw_theme_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'dw_theme_start_session', 1);

/**
 * ============================================================================
 * DATABASE HELPERS (INTEGRASI PLUGIN)
 * ============================================================================
 */

// Format Rupiah
function dw_format_rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

// Ambil Data Pedagang berdasarkan ID User WP
function dw_get_pedagang_data($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'dw_pedagang';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id_user = %d", $user_id));
}

// Ambil Data Desa berdasarkan ID User WP
function dw_get_desa_data($user_id) {
    global $wpdb;
    $table = $wpdb->prefix . 'dw_desa';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id_user_desa = %d", $user_id));
}

/**
 * ============================================================================
 * CART HANDLERS
 * ============================================================================
 */

// Get Cart
function dw_get_cart_items() {
    return isset($_SESSION['dw_cart']) ? $_SESSION['dw_cart'] : array();
}

// Get Cart Total
function dw_get_cart_total() {
    $items = dw_get_cart_items();
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// AJAX Add to Cart
function dw_ajax_add_to_cart() {
    check_ajax_referer('dw_cart_nonce', 'nonce');

    $product_id = intval($_POST['product_id']);
    $quantity   = intval($_POST['quantity']);

    if (!$product_id) {
        wp_send_json_error(['message' => 'Produk tidak valid']);
    }

    // Ambil data produk real dari WP
    $product = get_post($product_id);
    $price = get_post_meta($product_id, 'dw_harga_produk', true) ?: 0; // Sesuaikan meta key plugin
    $image = get_the_post_thumbnail_url($product_id, 'thumbnail');

    // Init Session Cart
    if (!isset($_SESSION['dw_cart'])) {
        $_SESSION['dw_cart'] = [];
    }

    // Logic Tambah/Update
    $found = false;
    foreach ($_SESSION['dw_cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $_SESSION['dw_cart'][] = [
            'product_id' => $product_id,
            'name'       => $product->post_title,
            'price'      => $price,
            'image'      => $image,
            'quantity'   => $quantity
        ];
    }

    wp_send_json_success(['message' => 'Berhasil masuk keranjang', 'count' => count($_SESSION['dw_cart'])]);
}
add_action('wp_ajax_dw_theme_add_to_cart', 'dw_ajax_add_to_cart');
add_action('wp_ajax_nopriv_dw_theme_add_to_cart', 'dw_ajax_add_to_cart');

/**
 * ============================================================================
 * CHECKOUT PROCESS (SPLIT ORDER LOGIC)
 * ============================================================================
 */
function dw_process_checkout_handler() {
    if (!isset($_POST['action']) || $_POST['action'] !== 'dw_process_checkout') return;
    if (!isset($_POST['dw_checkout_nonce']) || !wp_verify_nonce($_POST['dw_checkout_nonce'], 'dw_checkout_action')) {
        wp_die('Security Check Failed');
    }

    if (!is_user_logged_in()) {
        wp_redirect(home_url('/login')); exit;
    }

    $cart = dw_get_cart_items();
    if (empty($cart)) {
        wp_redirect(home_url('/cart')); exit;
    }

    global $wpdb;
    $user_id = get_current_user_id();
    
    // Nama Tabel dari Plugin
    $tbl_transaksi = $wpdb->prefix . 'dw_transaksi';
    $tbl_sub       = $wpdb->prefix . 'dw_transaksi_sub';
    $tbl_items     = $wpdb->prefix . 'dw_transaksi_items';
    $tbl_pedagang  = $wpdb->prefix . 'dw_pedagang';

    // 1. Grouping Item per Pedagang
    $grouped_orders = [];
    $grand_total = 0;

    foreach ($cart as $item) {
        // Cari Author Produk (User ID Pedagang)
        $product_author_id = get_post_field('post_author', $item['product_id']);
        
        // Cari ID Pedagang di tabel dw_pedagang
        $pedagang = $wpdb->get_row($wpdb->prepare("SELECT id, nama_toko FROM $tbl_pedagang WHERE id_user = %d", $product_author_id));
        
        $pedagang_id = $pedagang ? $pedagang->id : 0; // 0 jika admin/platform
        $nama_toko   = $pedagang ? $pedagang->nama_toko : get_bloginfo('name');

        $subtotal = $item['price'] * $item['quantity'];
        $grand_total += $subtotal;

        if (!isset($grouped_orders[$pedagang_id])) {
            $grouped_orders[$pedagang_id] = [
                'nama_toko' => $nama_toko,
                'total_toko' => 0,
                'items' => []
            ];
        }

        $grouped_orders[$pedagang_id]['items'][] = $item;
        $grouped_orders[$pedagang_id]['total_toko'] += $subtotal;
    }

    // 2. Insert Parent Transaction
    $kode_unik = 'TRX-' . strtoupper(wp_generate_password(6, false));
    $billing_data = json_encode([
        'nama' => sanitize_text_field($_POST['billing_name']),
        'hp'   => sanitize_text_field($_POST['billing_phone']),
        'alamat'=> sanitize_textarea_field($_POST['billing_address'])
    ]);

    $wpdb->insert($tbl_transaksi, [
        'id_pembeli' => $user_id,
        'kode_unik' => $kode_unik,
        'total_harga_produk' => $grand_total,
        'total_akhir' => $grand_total, // Nanti ditambah ongkir
        'status_pesanan' => 'menunggu_pembayaran',
        'status_transaksi' => 'menunggu_pembayaran',
        'alamat_pengiriman' => $billing_data,
        'nama_penerima' => sanitize_text_field($_POST['billing_name']),
        'no_hp' => sanitize_text_field($_POST['billing_phone']),
        'alamat_lengkap_snapshot' => sanitize_textarea_field($_POST['billing_address']),
        'metode_pembayaran' => sanitize_text_field($_POST['payment_method']),
        'created_at' => current_time('mysql')
    ]);
    
    $parent_trx_id = $wpdb->insert_id;

    // 3. Insert Sub Transactions (Per Toko)
    foreach ($grouped_orders as $pid => $data) {
        $wpdb->insert($tbl_sub, [
            'id_transaksi' => $parent_trx_id,
            'id_pedagang' => $pid,
            'nama_toko' => $data['nama_toko'],
            'sub_total' => $data['total_toko'],
            'ongkir' => 0,
            'total_pesanan_toko' => $data['total_toko'],
            'status_pesanan' => 'menunggu_pembayaran',
            'created_at' => current_time('mysql')
        ]);

        $sub_trx_id = $wpdb->insert_id;

        // 4. Insert Items
        foreach ($data['items'] as $prod) {
            $wpdb->insert($tbl_items, [
                'id_transaksi' => $parent_trx_id,
                'id_sub_transaksi' => $sub_trx_id,
                'id_produk' => $prod['product_id'],
                'nama_produk' => $prod['name'],
                'harga_satuan' => $prod['price'],
                'kuantitas' => $prod['quantity'],
                'total_harga' => $prod['price'] * $prod['quantity']
            ]);
        }
    }

    // Clear Cart & Redirect
    unset($_SESSION['dw_cart']);
    wp_redirect(home_url('/transaksi')); // Ke halaman list transaksi
    exit;
}
add_action('admin_post_dw_process_checkout', 'dw_process_checkout_handler');

// Redirect Login Sesuai Role
function dw_custom_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('pedagang', $user->roles)) {
            return home_url('/dashboard-toko');
        } elseif (in_array('admin_desa', $user->roles)) { // Sesuai capability plugin
            return home_url('/dashboard-desa');
        } elseif (in_array('administrator', $user->roles)) {
            return admin_url();
        }
    }
    return home_url('/akun-saya');
}
add_filter('login_redirect', 'dw_custom_login_redirect', 10, 3);
?>