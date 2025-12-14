<?php
/**
 * Functions and definitions for Tema Desa Wisata
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// 1. Setup Dasar Theme
function tema_desa_wisata_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    
    // Register Menus
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'tema-desa-wisata'),
        'footer'  => __('Footer Menu', 'tema-desa-wisata'),
    ));
}
add_action('after_setup_theme', 'tema_desa_wisata_setup');

// 2. Enqueue Scripts & Styles
function tema_desa_wisata_scripts() {
    // Load CSS Utama
    wp_enqueue_style('main-style', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0');
    wp_enqueue_style('theme-style', get_stylesheet_uri()); // style.css

    // Load JS Utama
    wp_enqueue_script('main-script', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true);

    // Load Ajax Cart Script (Wajib untuk integrasi Plugin)
    wp_enqueue_script('ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', array('jquery'), '1.0.0', true);

    // Localize Script untuk AJAX
    wp_localize_script('ajax-cart', 'dw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_cart_nonce'),
        'cart_url' => home_url('/cart'), // Pastikan slug page-cart.php adalah 'cart'
        'checkout_url' => home_url('/checkout')
    ));
}
add_action('wp_enqueue_scripts', 'tema_desa_wisata_scripts');

// 3. Helper Functions Integrasi Plugin

/**
 * Cek apakah Plugin Desa Wisata Core aktif
 */
if ( ! function_exists( 'is_dw_core_active' ) ) {
    function is_dw_core_active() {
        return class_exists('Desa_Wisata_Core');
    }
}

/**
 * Format Rupiah
 * Pengecekan function_exists ditambahkan untuk menghindari bentrok dengan plugin
 */
if ( ! function_exists( 'dw_format_rupiah' ) ) {
    function dw_format_rupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

/**
 * Get Harga Produk (Mengambil dari Meta Box Plugin)
 */
if ( ! function_exists( 'dw_get_product_price' ) ) {
    function dw_get_product_price($post_id) {
        // Asumsi meta key di plugin adalah 'dw_harga' atau '_price'
        // Sesuaikan dengan logic di includes/meta-boxes.php plugin Anda
        $price = get_post_meta($post_id, 'dw_harga', true);
        return $price ? $price : 0;
    }
}

/**
 * Redirect User setelah Login berdasarkan Role
 */
function dw_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
            return admin_url();
        } elseif (in_array('dw_admin_desa', $user->roles)) {
            return home_url('/dashboard-desa'); // Sesuai page-dashboard-desa.php
        } elseif (in_array('dw_pedagang', $user->roles)) {
            return home_url('/dashboard-toko'); // Sesuai page-dashboard-toko.php
        } else {
            return home_url('/akun-saya'); // User biasa/pembeli
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'dw_login_redirect', 10, 3);

/**
 * Start Session jika belum ada (Untuk Cart)
 */
function dw_start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'dw_start_session', 1);

/**
 * Mendapatkan item di keranjang (Helper sederhana)
 */
if ( ! function_exists( 'dw_get_cart_items' ) ) {
    function dw_get_cart_items() {
        if (isset($_SESSION['dw_cart']) && !empty($_SESSION['dw_cart'])) {
            return $_SESSION['dw_cart'];
        }
        return array();
    }
}

/**
 * Hitung Total Keranjang
 */
if ( ! function_exists( 'dw_get_cart_total' ) ) {
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
 * [BARU] Fungsi Helper untuk Menyimpan Transaksi ke Database Plugin
 * Fungsi ini akan dipanggil oleh handler checkout.
 */
if ( ! function_exists( 'dw_create_transaction' ) ) {
    function dw_create_transaction($user_id, $items, $billing_data, $total_amount, $payment_method) {
        global $wpdb;
        
        // Nama tabel sesuai dengan prefix WP dan nama tabel di activation.php plugin
        $table_transaksi = $wpdb->prefix . 'dw_transaksi'; // Pastikan nama ini sama dengan di activation.php
        $table_detail = $wpdb->prefix . 'dw_detail_transaksi';

        // 1. Insert ke Tabel Transaksi Utama
        $result = $wpdb->insert(
            $table_transaksi,
            array(
                'user_id' => $user_id,
                'total_amount' => $total_amount,
                'status' => 'pending', // Status awal
                'payment_method' => $payment_method,
                'billing_name' => $billing_data['name'],
                'billing_email' => $billing_data['email'],
                'billing_phone' => $billing_data['phone'],
                'billing_address' => $billing_data['address'],
                'order_note' => $billing_data['note'],
                'created_at' => current_time('mysql')
            ),
            array('%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            return false; // Gagal insert
        }

        $transaction_id = $wpdb->insert_id;

        // 2. Insert ke Tabel Detail Transaksi (Looping item)
        foreach ($items as $item) {
            $wpdb->insert(
                $table_detail,
                array(
                    'transaction_id' => $transaction_id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['price'] * $item['quantity']
                ),
                array('%d', '%d', '%d', '%f', '%f')
            );
        }

        return $transaction_id;
    }
}

/**
 * [BARU] Handler Form Checkout
 * Menangani POST request dari page-checkout.php
 */
function dw_handle_checkout_process() {
    if (isset($_POST['action']) && $_POST['action'] === 'dw_process_checkout') {
        
        // Verifikasi Nonce
        if (!isset($_POST['dw_checkout_nonce']) || !wp_verify_nonce($_POST['dw_checkout_nonce'], 'dw_checkout_action')) {
            wp_die('Security check failed');
        }

        // Cek Login
        if (!is_user_logged_in()) {
            wp_die('Anda harus login untuk melakukan checkout.');
        }

        $user_id = get_current_user_id();
        $cart_items = dw_get_cart_items();
        
        if (empty($cart_items)) {
            wp_redirect(home_url('/cart'));
            exit;
        }

        // Ambil Data Billing
        $billing_data = array(
            'name' => sanitize_text_field($_POST['billing_name']),
            'email' => sanitize_email($_POST['billing_email']),
            'phone' => sanitize_text_field($_POST['billing_phone']),
            'address' => sanitize_textarea_field($_POST['billing_address']),
            'note' => sanitize_textarea_field($_POST['order_note']),
        );
        
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $total_amount = dw_get_cart_total();

        // Simpan Transaksi
        $transaction_id = dw_create_transaction($user_id, $cart_items, $billing_data, $total_amount, $payment_method);

        if ($transaction_id) {
            // Kosongkan Keranjang
            unset($_SESSION['dw_cart']);
            
            // Redirect ke Halaman Sukses / Detail Transaksi
            // Anda bisa buat page khusus atau redirect ke akun saya
            $redirect_url = home_url('/transaksi?id=' . $transaction_id . '&status=success');
            wp_redirect($redirect_url);
            exit;
        } else {
            wp_die('Terjadi kesalahan saat memproses pesanan. Silakan coba lagi.');
        }
    }
}
add_action('admin_post_dw_process_checkout', 'dw_handle_checkout_process'); // Untuk User Login
add_action('admin_post_nopriv_dw_process_checkout', 'dw_handle_checkout_process'); // (Opsional) Jika guest checkout diizinkan
?>