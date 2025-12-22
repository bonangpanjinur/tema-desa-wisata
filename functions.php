<?php
/**
 * Functions and definitions
 * Menggunakan prefix 'tema_dw_' untuk menghindari konflik.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * 1. Theme Support & Setup
 */
function tema_dw_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array('height' => 60, 'width' => 180, 'flex-height' => true, 'flex-width' => true));
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
}
add_action('after_setup_theme', 'tema_dw_setup');

/**
 * 2. Enqueue Scripts & Styles
 */
function tema_dw_scripts() {
    // Tailwind CSS
    wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.4.0', false);
    
    // Config Tailwind (Warna Identitas)
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

    // Fonts & Icons
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');

    // Styles & Scripts
    wp_enqueue_style('tema-dw-style', get_stylesheet_uri());
    wp_enqueue_script('tema-dw-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.3', true);

    // Localize Script
    wp_localize_script('tema-dw-main', 'dw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_cart_nonce')
    ));
    
    // Data API untuk Dashboard JS
    wp_localize_script('tema-dw-main', 'dwData', array(
        'api_url' => home_url('/wp-json/dw/v1/'), // Sesuaikan dengan base URL API plugin Anda
        'home_url' => home_url()
    ));
}
add_action('wp_enqueue_scripts', 'tema_dw_scripts');

/**
 * 3. Helper Functions
 */
function tema_dw_format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Redirect Login Sesuai Role
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

function tema_dw_start_session() {
    if (!session_id()) session_start();
}
add_action('init', 'tema_dw_start_session');

/**
 * 4. ROUTING & REWRITE RULES (Fix 404 Dashboard & Detail)
 */
function tema_dw_rewrite_rules() {
    // A. Single Custom Post Type
    add_rewrite_rule('^wisata/([^/]*)/?', 'index.php?dw_type=wisata&dw_slug=$matches[1]', 'top');
    add_rewrite_rule('^produk/([^/]*)/?', 'index.php?dw_type=produk&dw_slug=$matches[1]', 'top');
    
    // B. Dashboard Virtual Pages & System Pages (Tanpa perlu buat Page di Admin)
    add_rewrite_rule('^dashboard-toko/?', 'index.php?dw_type=dashboard_toko', 'top');
    add_rewrite_rule('^dashboard-desa/?', 'index.php?dw_type=dashboard_desa', 'top');
    add_rewrite_rule('^dashboard-ojek/?', 'index.php?dw_type=dashboard_ojek', 'top');
    add_rewrite_rule('^akun-saya/?', 'index.php?dw_type=akun_saya', 'top');
    
    // C. Halaman Transaksi & Pembayaran
    add_rewrite_rule('^keranjang/?', 'index.php?dw_type=cart', 'top');
    add_rewrite_rule('^checkout/?', 'index.php?dw_type=checkout', 'top');
    add_rewrite_rule('^pembayaran/?', 'index.php?dw_type=pembayaran', 'top');
    
    // Flush Rules Otomatis (Sekali jalan untuk update v3)
    if (get_option('tema_dw_rules_flushed_v3') !== 'yes') {
        flush_rewrite_rules();
        update_option('tema_dw_rules_flushed_v3', 'yes');
    }
}
add_action('init', 'tema_dw_rewrite_rules');

// Register Query Vars
function tema_dw_query_vars($vars) {
    $vars[] = 'dw_type';
    $vars[] = 'dw_slug';
    return $vars;
}
add_filter('query_vars', 'tema_dw_query_vars');

// Template Loader (Mengarahkan URL ke File PHP)
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

// --- AJAX Handlers ---
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
    $session_id = session_id();
    if(empty($session_id)) $session_id = $_COOKIE['PHPSESSID'] ?? '';

    if ($user_id > 0) {
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id, qty FROM $table_name WHERE user_id = %d AND id_produk = %d", $user_id, $product_id));
    } else {
        $existing = $wpdb->get_row($wpdb->prepare("SELECT id, qty FROM $table_name WHERE session_id = %s AND id_produk = %d", $session_id, $product_id));
    }

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

add_action('wp_ajax_dw_update_cart_qty', 'dw_handle_update_cart_qty');
add_action('wp_ajax_nopriv_dw_update_cart_qty', 'dw_handle_update_cart_qty');

function dw_handle_update_cart_qty() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $cart_id = intval($_POST['cart_id']);
    $qty = intval($_POST['qty']);
    $table_cart = $wpdb->prefix . 'dw_cart';
    
    if($qty < 1) wp_send_json_error(['message' => 'Minimal 1']);
    
    $wpdb->update($table_cart, ['qty' => $qty], ['id' => $cart_id]);
    
    $user_id = get_current_user_id();
    $session_id = session_id() ?: ($_COOKIE['PHPSESSID'] ?? '');
    
    $sql_total = $wpdb->prepare(
        "SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items
         FROM $table_cart c JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
         WHERE c.id = %d", $cart_id
    ); 
    // Simplified logic: Just re-calc specific user cart totals usually needed
    $totals = $wpdb->get_row($wpdb->prepare(
        "SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items
         FROM $table_cart c JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
         WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)",
        $user_id, $session_id
    ));

    wp_send_json_success([
        'message' => 'Updated',
        'new_qty' => $qty,
        'grand_total_fmt' => tema_dw_format_rupiah($totals->grand_total ?? 0),
        'total_items' => intval($totals->total_items ?? 0)
    ]);
}

add_action('wp_ajax_dw_remove_cart_item', 'dw_handle_remove_cart_item');
add_action('wp_ajax_nopriv_dw_remove_cart_item', 'dw_handle_remove_cart_item');

function dw_handle_remove_cart_item() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $cart_id = intval($_POST['cart_id']);
    $wpdb->delete($wpdb->prefix . 'dw_cart', ['id' => $cart_id]);
    
    $user_id = get_current_user_id();
    $session_id = session_id() ?: ($_COOKIE['PHPSESSID'] ?? '');
    
    $totals = $wpdb->get_row($wpdb->prepare(
        "SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items
         FROM {$wpdb->prefix}dw_cart c JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
         WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)",
        $user_id, $session_id
    ));

    wp_send_json_success([
        'message' => 'Deleted',
        'grand_total_fmt' => tema_dw_format_rupiah($totals->grand_total ?? 0),
        'total_items' => intval($totals->total_items ?? 0)
    ]);
}
?>