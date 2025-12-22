<?php
/**
 * Functions and definitions
 * Menggunakan prefix 'tema_dw_' untuk menghindari konflik.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ==============================================================================
 * 1. THEME SETUP & ENQUEUE
 * ==============================================================================
 */

function tema_dw_setup() {
    // Menambahkan dukungan title tag otomatis
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

    // HTML5 support untuk elemen form dan gallery
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
}
add_action('after_setup_theme', 'tema_dw_setup');

function tema_dw_scripts() {
    // Tailwind CSS (CDN - Untuk development, di production sebaiknya di-build)
    wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.4.0', false);
    
    // Config Tailwind (Warna Identitas Desa)
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

    // Fonts & Icons
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');

    // Styles & Scripts Tema Utama
    wp_enqueue_style('tema-dw-style', get_stylesheet_uri());
    
    // Cek file css main ada atau tidak sebelum load
    if (file_exists(get_template_directory() . '/assets/css/main.css')) {
        wp_enqueue_style('tema-dw-main-css', get_template_directory_uri() . '/assets/css/main.css', array(), filemtime(get_template_directory() . '/assets/css/main.css'));
    }

    // Main JavaScript (Logic UI, AJAX Cart, Dashboard)
    wp_enqueue_script('tema-dw-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.5', true);

    // Localize Script (Mengirim variabel PHP ke JS agar bisa diakses di main.js)
    wp_localize_script('tema-dw-main', 'dw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_cart_nonce')
    ));
    
    // Data API (Opsional jika pakai REST API terpisah, tapi kita pakai admin-ajax.php utama)
    wp_localize_script('tema-dw-main', 'dwData', array(
        'api_url' => home_url('/wp-json/dw/v1/'),
        'home_url' => home_url()
    ));

    // Script Filter (Hanya dimuat di halaman arsip)
    if (is_post_type_archive('dw_produk') || is_tax('kategori_produk') || is_post_type_archive('dw_wisata')) {
         wp_enqueue_script('tema-dw-filter', get_template_directory_uri() . '/assets/js/archive-filter.js', array('jquery'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'tema_dw_scripts');

/**
 * ==============================================================================
 * 2. HELPER FUNCTIONS
 * ==============================================================================
 */

// Helper: Format Rupiah
function tema_dw_format_rupiah($angka) {
    if (function_exists('dw_format_rupiah')) return dw_format_rupiah($angka);
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Helper: Ambil ID Pedagang dari User Login
function dw_get_merchant_id() {
    global $wpdb;
    $user_id = get_current_user_id();
    // Pastikan tabel prefix sesuai
    $table_pedagang = $wpdb->prefix . 'dw_pedagang';
    return $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_pedagang WHERE id_user = %d", $user_id));
}

// Redirect Login Berdasarkan Role User
function tema_dw_login_redirect($url, $request, $user) {
    if ($user && is_object($user) && is_a($user, 'WP_User')) {
        if (in_array('administrator', $user->roles)) return admin_url();
        if (in_array('pengelola_desa', $user->roles)) return home_url('/dashboard-desa');
        if (in_array('pedagang', $user->roles)) return home_url('/dashboard-toko');
        if (in_array('ojek_wisata', $user->roles)) return home_url('/dashboard-ojek');
        return home_url('/akun-saya');
    }
    return $url;
}
add_filter('login_redirect', 'tema_dw_login_redirect', 10, 3);

// Sembunyikan Admin Bar untuk User Biasa
function tema_dw_disable_admin_bar() {
    if (!current_user_can('edit_posts')) show_admin_bar(false);
}
add_action('after_setup_theme', 'tema_dw_disable_admin_bar');

// Mulai Session PHP (Penting untuk Cart Pengunjung/Guest)
function tema_dw_start_session() {
    if (!session_id()) session_start();
}
add_action('init', 'tema_dw_start_session');

/**
 * ==============================================================================
 * 3. ROUTING & REWRITE RULES
 * ==============================================================================
 */

function tema_dw_rewrite_rules() {
    // A. Single Custom Post Type
    add_rewrite_rule('^wisata/([^/]*)/?', 'index.php?dw_type=wisata&dw_slug=$matches[1]', 'top');
    add_rewrite_rule('^produk/([^/]*)/?', 'index.php?dw_type=produk&dw_slug=$matches[1]', 'top');
    
    // B. Dashboard Virtual Pages
    add_rewrite_rule('^dashboard-toko/?', 'index.php?dw_type=dashboard_toko', 'top');
    add_rewrite_rule('^dashboard-desa/?', 'index.php?dw_type=dashboard_desa', 'top');
    add_rewrite_rule('^dashboard-ojek/?', 'index.php?dw_type=dashboard_ojek', 'top');
    add_rewrite_rule('^akun-saya/?', 'index.php?dw_type=akun_saya', 'top');
    
    // C. Transaction Pages
    add_rewrite_rule('^keranjang/?', 'index.php?dw_type=cart', 'top');
    add_rewrite_rule('^checkout/?', 'index.php?dw_type=checkout', 'top');
    add_rewrite_rule('^pembayaran/?', 'index.php?dw_type=pembayaran', 'top');
    
    // Auto Flush Rules (Mencegah 404 saat update tema)
    if (get_option('tema_dw_rules_flushed_v6') !== 'yes') {
        flush_rewrite_rules();
        update_option('tema_dw_rules_flushed_v6', 'yes');
    }
}
add_action('init', 'tema_dw_rewrite_rules');

// Reset opsi flush jika tema di-switch (agar refresh ulang saat aktivasi)
add_action('after_switch_theme', function() {
    delete_option('tema_dw_rules_flushed_v6');
});

// Daftarkan Query Vars
function tema_dw_query_vars($vars) {
    $vars[] = 'dw_type';
    $vars[] = 'dw_slug';
    return $vars;
}
add_filter('query_vars', 'tema_dw_query_vars');

// Template Loader (Mengarahkan URL Virtual ke File PHP)
function tema_dw_template_include($template) {
    $dw_type = get_query_var('dw_type');
    
    // Single Pages
    if ($dw_type == 'wisata') return get_template_directory() . '/single-dw_wisata.php';
    if ($dw_type == 'produk') return get_template_directory() . '/single-dw_produk.php';
    
    // Transaction Pages
    if ($dw_type == 'cart')       return get_template_directory() . '/page-cart.php';
    if ($dw_type == 'checkout')   return get_template_directory() . '/page-checkout.php';
    if ($dw_type == 'pembayaran') return get_template_directory() . '/page-pembayaran.php';
    
    // Dashboard Routing
    if ($dw_type == 'dashboard_toko') return get_template_directory() . '/page-dashboard-toko.php';
    if ($dw_type == 'dashboard_desa') return get_template_directory() . '/page-dashboard-desa.php';
    if ($dw_type == 'dashboard_ojek') return get_template_directory() . '/page-dashboard-ojek.php';
    if ($dw_type == 'akun_saya')      return get_template_directory() . '/page-akun-saya.php';
    
    return $template;
}
add_filter('template_include', 'tema_dw_template_include');

/**
 * ==============================================================================
 * 4. AJAX HANDLERS: CART SYSTEM
 * ==============================================================================
 */

// Add to Cart
add_action('wp_ajax_dw_add_to_cart', 'dw_handle_add_to_cart');
add_action('wp_ajax_nopriv_dw_add_to_cart', 'dw_handle_add_to_cart');

function dw_handle_add_to_cart() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;

    $product_id = intval($_POST['product_id']);
    $qty        = intval($_POST['qty']);
    $variasi_id = isset($_POST['variasi_id']) ? intval($_POST['variasi_id']) : 0;

    if (!$product_id) wp_send_json_error(['message' => 'Produk tidak valid']);

    $table_name = $wpdb->prefix . 'dw_cart';
    $user_id = get_current_user_id();
    $session_id = session_id() ?: ($_COOKIE['PHPSESSID'] ?? '');

    $where = ($user_id > 0) 
        ? $wpdb->prepare("user_id = %d AND id_produk = %d", $user_id, $product_id)
        : $wpdb->prepare("session_id = %s AND id_produk = %d", $session_id, $product_id);

    $existing = $wpdb->get_row("SELECT id, qty FROM $table_name WHERE $where");

    if ($existing) {
        $wpdb->update($table_name, ['qty' => $existing->qty + $qty, 'updated_at' => current_time('mysql')], ['id' => $existing->id]);
    } else {
        $wpdb->insert($table_name, [
            'user_id' => ($user_id > 0) ? $user_id : null,
            'session_id' => $session_id,
            'id_produk' => $product_id,
            'id_variasi' => $variasi_id,
            'qty' => $qty,
            'created_at' => current_time('mysql')
        ]);
    }

    $total_items = ($user_id > 0)
        ? $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_name WHERE user_id = %d", $user_id))
        : $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_name WHERE session_id = %s", $session_id));

    wp_send_json_success(['message' => 'Berhasil ditambahkan', 'cart_count' => (int)$total_items]);
}

// Update Cart Quantity (AJAX)
add_action('wp_ajax_dw_update_cart_qty', 'dw_handle_update_cart_qty');
add_action('wp_ajax_nopriv_dw_update_cart_qty', 'dw_handle_update_cart_qty');

function dw_handle_update_cart_qty() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $cart_id = intval($_POST['cart_id']);
    $qty = intval($_POST['qty']);
    $table_cart = $wpdb->prefix . 'dw_cart';
    $table_produk = $wpdb->prefix . 'dw_produk';
    
    // Cek Stok Real (Opsional tapi direkomendasikan)
    $item = $wpdb->get_row($wpdb->prepare("SELECT c.id_produk, p.stok FROM $table_cart c JOIN $table_produk p ON c.id_produk = p.id WHERE c.id = %d", $cart_id));
    
    if ($item && $qty > $item->stok) {
        wp_send_json_error(['message' => 'Stok tidak cukup', 'current_qty' => $item->stok]);
    }
    
    if($qty < 1) wp_send_json_error(['message' => 'Minimal 1']);
    
    // Update DB
    $wpdb->update($table_cart, ['qty' => $qty], ['id' => $cart_id]);
    
    // Recalculate Totals
    $user_id = get_current_user_id();
    $session_id = session_id() ?: ($_COOKIE['PHPSESSID'] ?? '');
    
    $totals = $wpdb->get_row($wpdb->prepare(
        "SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items
         FROM $table_cart c JOIN $table_produk p ON c.id_produk = p.id
         WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)",
        $user_id, $session_id
    ));

    wp_send_json_success([
        'new_qty' => $qty,
        'grand_total_fmt' => tema_dw_format_rupiah($totals->grand_total ?? 0),
        'total_items' => intval($totals->total_items ?? 0)
    ]);
}

// Remove Cart Item
add_action('wp_ajax_dw_remove_cart_item', 'dw_handle_remove_cart_item');
add_action('wp_ajax_nopriv_dw_remove_cart_item', 'dw_handle_remove_cart_item');

function dw_handle_remove_cart_item() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $cart_id = intval($_POST['cart_id']);
    
    // Hapus
    $wpdb->delete($wpdb->prefix . 'dw_cart', ['id' => $cart_id]);
    
    // Recalculate
    $user_id = get_current_user_id();
    $session_id = session_id() ?: ($_COOKIE['PHPSESSID'] ?? '');
    
    $totals = $wpdb->get_row($wpdb->prepare(
        "SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items
         FROM {$wpdb->prefix}dw_cart c JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
         WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)",
        $user_id, $session_id
    ));

    wp_send_json_success([
        'grand_total_fmt' => tema_dw_format_rupiah($totals->grand_total ?? 0),
        'total_items' => intval($totals->total_items ?? 0)
    ]);
}

/**
 * ==============================================================================
 * 5. AJAX HANDLERS: MERCHANT DASHBOARD
 * ==============================================================================
 */

// Stats
add_action('wp_ajax_dw_merchant_stats', 'dw_ajax_merchant_stats');
function dw_ajax_merchant_stats() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $pid = dw_get_merchant_id();
    
    if (!$pid) wp_send_json_error(['message' => 'Toko tidak ditemukan']);

    $sales = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_pesanan_toko) FROM {$wpdb->prefix}dw_transaksi_sub WHERE id_pedagang = %d AND status_pesanan IN ('selesai', 'dikirim_ekspedisi')", $pid));
    $orders = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dw_transaksi_sub WHERE id_pedagang = %d AND status_pesanan = 'menunggu_konfirmasi'", $pid));
    $products = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dw_produk WHERE id_pedagang = %d AND stok <= 0", $pid));

    wp_send_json_success(['sales' => (int)$sales, 'orders' => (int)$orders, 'products_empty' => (int)$products]);
}

// Get Products
add_action('wp_ajax_dw_merchant_get_products', 'dw_ajax_merchant_get_products');
function dw_ajax_merchant_get_products() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $pid = dw_get_merchant_id();
    $products = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_produk WHERE id_pedagang = %d AND status != 'arsip' ORDER BY created_at DESC", $pid));
    wp_send_json_success($products);
}

// Save Product
add_action('wp_ajax_dw_merchant_save_product', 'dw_ajax_merchant_save_product');
function dw_ajax_merchant_save_product() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $pid = dw_get_merchant_id();
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $data = [
        'id_pedagang' => $pid,
        'nama_produk' => sanitize_text_field($_POST['nama_produk']),
        'harga' => floatval($_POST['harga']),
        'stok' => intval($_POST['stok']),
        'kategori' => sanitize_text_field($_POST['kategori']),
        'deskripsi' => wp_kses_post($_POST['deskripsi']),
        'slug' => sanitize_title($_POST['nama_produk']) . '-' . time()
    ];

    if (!empty($_FILES['foto_utama']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        $att_id = media_handle_upload('foto_utama', 0);
        if (!is_wp_error($att_id)) $data['foto_utama'] = wp_get_attachment_url($att_id);
    }

    if ($id > 0) { unset($data['slug']); $wpdb->update("{$wpdb->prefix}dw_produk", $data, ['id' => $id, 'id_pedagang' => $pid]); }
    else { $wpdb->insert("{$wpdb->prefix}dw_produk", $data); }

    wp_send_json_success(['message' => 'Tersimpan']);
}

// Delete Product
add_action('wp_ajax_dw_merchant_delete_product', 'dw_ajax_merchant_delete_product');
function dw_ajax_merchant_delete_product() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $wpdb->update("{$wpdb->prefix}dw_produk", ['status' => 'arsip'], ['id' => intval($_POST['product_id']), 'id_pedagang' => dw_get_merchant_id()]);
    wp_send_json_success();
}

// Get Orders (Updated: With Bukti Bayar & Kode Unik)
add_action('wp_ajax_dw_merchant_get_orders', 'dw_ajax_merchant_get_orders');
function dw_ajax_merchant_get_orders() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
    
    $sql = $wpdb->prepare(
        "SELECT sub.*, m.kode_unik, m.nama_penerima as nama_pembeli, m.tanggal_transaksi, m.bukti_pembayaran
         FROM {$wpdb->prefix}dw_transaksi_sub sub
         JOIN {$wpdb->prefix}dw_transaksi m ON sub.id_transaksi = m.id
         WHERE sub.id_pedagang = %d 
         ORDER BY sub.created_at DESC LIMIT %d", 
        dw_get_merchant_id(), $limit
    );
    
    $orders = $wpdb->get_results($sql);
    
    foreach($orders as $o) {
        $o->formatted_date = date('d M Y', strtotime($o->tanggal_transaksi));
        $o->formatted_total = tema_dw_format_rupiah($o->total_pesanan_toko);
        $o->status_label = ucwords(str_replace('_', ' ', $o->status_pesanan));
    }
    wp_send_json_success($orders);
}

// Update Order Status
add_action('wp_ajax_dw_merchant_update_status', 'dw_ajax_merchant_update_status');
function dw_ajax_merchant_update_status() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    
    $data = ['status_pesanan' => sanitize_text_field($_POST['status'])];
    if(isset($_POST['resi'])) $data['no_resi'] = sanitize_text_field($_POST['resi']);
    
    $wpdb->update("{$wpdb->prefix}dw_transaksi_sub", $data, ['id' => intval($_POST['order_id']), 'id_pedagang' => dw_get_merchant_id()]);
    wp_send_json_success();
}

// Get & Save Profile
add_action('wp_ajax_dw_merchant_get_profile', 'dw_ajax_merchant_get_profile');
function dw_ajax_merchant_get_profile() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_pedagang WHERE id = %d", dw_get_merchant_id()));
    wp_send_json_success([
        'nama_toko' => $p->nama_toko, 'deskripsi_toko' => $p->alamat_lengkap, 
        'no_rekening' => $p->no_rekening, 'nama_bank' => $p->nama_bank, 'atas_nama_rekening' => $p->atas_nama_rekening
    ]);
}

add_action('wp_ajax_dw_merchant_save_profile', 'dw_ajax_merchant_save_profile');
function dw_ajax_merchant_save_profile() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $data = [
        'nama_toko' => sanitize_text_field($_POST['nama_toko']),
        'alamat_lengkap' => sanitize_textarea_field($_POST['deskripsi_toko']),
        'no_rekening' => sanitize_text_field($_POST['no_rekening']),
        'nama_bank' => sanitize_text_field($_POST['nama_bank']),
        'atas_nama_rekening' => sanitize_text_field($_POST['atas_nama_rekening'])
    ];
    $wpdb->update("{$wpdb->prefix}dw_pedagang", $data, ['id' => dw_get_merchant_id()]);
    wp_send_json_success();
}

/**
 * ==============================================================================
 * 6. WISHLIST
 * ==============================================================================
 */

add_action('wp_ajax_dw_toggle_wishlist', 'dw_handle_toggle_wishlist');
add_action('wp_ajax_nopriv_dw_toggle_wishlist', 'dw_handle_toggle_wishlist');

function dw_handle_toggle_wishlist() {
    check_ajax_referer('dw_cart_nonce', 'security');
    if (!is_user_logged_in()) wp_send_json_error(['code' => 'not_logged_in']);
    
    global $wpdb;
    $user_id = get_current_user_id();
    $item_id = intval($_POST['item_id']);
    $type = sanitize_text_field($_POST['item_type']);
    $table = $wpdb->prefix . 'dw_wishlist';
    
    $exist = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE user_id=%d AND item_id=%d AND item_type=%s", $user_id, $item_id, $type));
    
    if($exist) {
        $wpdb->delete($table, ['id' => $exist]);
        wp_send_json_success(['status' => 'removed']);
    } else {
        $wpdb->insert($table, ['user_id' => $user_id, 'item_id' => $item_id, 'item_type' => $type]);
        wp_send_json_success(['status' => 'added']);
    }
}
?>