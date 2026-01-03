<?php
/**
 * Functions and definitions
 * Menggunakan prefix 'tema_dw_' untuk menghindari konflik.
 */

if (!defined('ABSPATH')) {
    exit; // Keluar jika diakses langsung
}

/**
 * ==============================================================================
 * 1. THEME SETUP & ENQUEUE
 * ==============================================================================
 */

function tema_dw_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array('height' => 60, 'width' => 180, 'flex-height' => true, 'flex-width' => true));
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
}
add_action('after_setup_theme', 'tema_dw_setup');

function tema_dw_scripts() {
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

    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style('tema-dw-style', get_stylesheet_uri());
    
    if (file_exists(get_template_directory() . '/assets/css/main.css')) {
        wp_enqueue_style('tema-dw-main-css', get_template_directory_uri() . '/assets/css/main.css', array(), filemtime(get_template_directory() . '/assets/css/main.css'));
    }

    wp_enqueue_script('tema-dw-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.8', true);

    wp_localize_script('tema-dw-main', 'dw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_cart_nonce')
    ));
    
    wp_localize_script('tema-dw-main', 'dwData', array(
        'api_url' => home_url('/wp-json/dw/v1/'),
        'home_url' => home_url()
    ));

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

function tema_dw_format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function dw_get_merchant_id() {
    global $wpdb;
    $user_id = get_current_user_id();
    return $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}dw_pedagang WHERE id_user = %d", $user_id));
}

function dw_get_desa_id() {
    global $wpdb;
    $user_id = get_current_user_id();
    return $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}dw_desa WHERE id_user_desa = %d", $user_id));
}

function tema_dw_login_redirect($url, $request, $user) {
    if ($user && is_object($user) && is_a($user, 'WP_User')) {
        $roles = (array) $user->roles;
        $dashboard_roles = array('administrator', 'editor_desa', 'admin_desa', 'pedagang', 'dw_ojek', 'verifikator_umkm');
        
        foreach ($dashboard_roles as $role) {
            if (in_array($role, $roles)) {
                return home_url('/dashboard');
            }
        }
        return home_url('/akun-saya');
    }
    return $url;
}
add_filter('login_redirect', 'tema_dw_login_redirect', 10, 3);

add_filter( 'get_avatar_url', 'dw_custom_avatar_url', 10, 3 );
function dw_custom_avatar_url( $url, $id_or_email, $args ) {
    $user_id = 0;
    if ( is_numeric( $id_or_email ) ) { $user_id = $id_or_email; }
    elseif ( is_string( $id_or_email ) && ( $user = get_user_by( 'email', $id_or_email ) ) ) { $user_id = $user->ID; }
    elseif ( is_object( $id_or_email ) && ! empty( $id_or_email->user_id ) ) { $user_id = $id_or_email->user_id; }
    elseif ( $id_or_email instanceof WP_User ) { $user_id = $id_or_email->ID; }

    if ( $user_id ) {
        $custom_avatar = get_user_meta( $user_id, 'dw_custom_avatar_url', true );
        if ( $custom_avatar ) return $custom_avatar;
    }
    return $url;
}

function tema_dw_disable_admin_bar() {
    if (!current_user_can('edit_posts') && !is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'tema_dw_disable_admin_bar');

function tema_dw_start_session() {
    if (!session_id() && !headers_sent()) {
        session_start();
    }
}
add_action('init', 'tema_dw_start_session');

/**
 * ==============================================================================
 * 3. ROUTING & REWRITE RULES
 * ==============================================================================
 */

function tema_dw_rewrite_rules() {
    add_rewrite_rule('^wisata/([^/]*)/?', 'index.php?dw_type=wisata&dw_slug=$matches[1]', 'top');
    add_rewrite_rule('^produk/([^/]*)/?', 'index.php?dw_type=produk&dw_slug=$matches[1]', 'top');
    add_rewrite_rule('^toko/([^/]*)/?', 'index.php?dw_type=profil_toko&dw_slug_toko=$matches[1]', 'top');
    add_rewrite_rule('^desa/([^/]*)/?', 'index.php?dw_type=profil_desa&dw_slug_desa=$matches[1]', 'top');
    add_rewrite_rule('^dashboard/?$', 'index.php?dw_type=dashboard_router', 'top');
    add_rewrite_rule('^akun-saya/?$', 'index.php?dw_type=akun_saya', 'top');
    add_rewrite_rule('^keranjang/?$', 'index.php?dw_type=cart', 'top');
    add_rewrite_rule('^checkout/?$', 'index.php?dw_type=checkout', 'top');
    add_rewrite_rule('^pembayaran/?$', 'index.php?dw_type=pembayaran', 'top');
    
    if (get_option('tema_dw_rules_flushed_v18') !== 'yes') {
        flush_rewrite_rules();
        update_option('tema_dw_rules_flushed_v18', 'yes');
    }
}
add_action('init', 'tema_dw_rewrite_rules');

function tema_dw_query_vars($vars) {
    $vars[] = 'dw_type';
    $vars[] = 'dw_slug';
    $vars[] = 'dw_slug_toko';
    $vars[] = 'dw_slug_desa';
    return $vars;
}
add_filter('query_vars', 'tema_dw_query_vars');

function tema_dw_template_include($template) {
    $dw_type = get_query_var('dw_type');
    
    if ($dw_type == 'dashboard_router') {
        $dashboard_file = get_template_directory() . '/page-dashboard.php';
        if (file_exists($dashboard_file)) {
            return $dashboard_file;
        }
    }

    if ($dw_type == 'wisata') return get_template_directory() . '/single-dw_wisata.php';
    if ($dw_type == 'produk') return get_template_directory() . '/single-dw_produk.php';
    if ($dw_type == 'profil_toko') return get_template_directory() . '/page-profil-toko.php';
    if ($dw_type == 'profil_desa') return get_template_directory() . '/page-profil-desa.php';
    if ($dw_type == 'cart')       return get_template_directory() . '/page-cart.php';
    if ($dw_type == 'checkout')   return get_template_directory() . '/page-checkout.php';
    if ($dw_type == 'pembayaran') return get_template_directory() . '/page-pembayaran.php';
    if ($dw_type == 'akun_saya')  return get_template_directory() . '/page-akun-saya.php';
    
    return $template;
}
add_filter('template_include', 'tema_dw_template_include');

/**
 * ==============================================================================
 * 4. SCRIPT LOADER FOR REGIONS
 * ==============================================================================
 */
function dw_load_region_scripts() {
    $type = get_query_var('dw_type');
    if ( $type == 'dashboard_router') {
        if( file_exists( get_template_directory() . '/assets/js/dw-region.js' ) ) {
            wp_enqueue_script( 'dw-region-js', get_template_directory_uri() . '/assets/js/dw-region.js', array('jquery'), '1.2', true );
            wp_localize_script( 'dw-region-js', 'dwRegionVars', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ));
        }
    }
}
add_action( 'wp_enqueue_scripts', 'dw_load_region_scripts' );

/**
 * ==============================================================================
 * 5. AJAX HANDLERS: CART SYSTEM
 * ==============================================================================
 */

add_action('wp_ajax_dw_add_to_cart', 'dw_handle_add_to_cart');
add_action('wp_ajax_nopriv_dw_add_to_cart', 'dw_handle_add_to_cart');
function dw_handle_add_to_cart() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $product_id = intval($_POST['product_id']);
    $qty = intval($_POST['qty']);
    if (!$product_id) wp_send_json_error(['message' => 'Produk tidak valid']);
    $table_name = $wpdb->prefix . 'dw_cart';
    $user_id = get_current_user_id();
    $session_id = session_id() ?: ($_COOKIE['PHPSESSID'] ?? '');
    $where = ($user_id > 0) ? $wpdb->prepare("user_id = %d AND id_produk = %d", $user_id, $product_id) : $wpdb->prepare("session_id = %s AND id_produk = %d", $session_id, $product_id);
    $existing = $wpdb->get_row("SELECT id, qty FROM $table_name WHERE $where");
    if ($existing) {
        $wpdb->update($table_name, ['qty' => $existing->qty + $qty, 'updated_at' => current_time('mysql')], ['id' => $existing->id]);
    } else {
        $wpdb->insert($table_name, [
            'user_id' => ($user_id > 0) ? $user_id : null,
            'session_id' => $session_id,
            'id_produk' => $product_id,
            'qty' => $qty,
            'created_at' => current_time('mysql')
        ]);
    }
    $total_items = ($user_id > 0) ? $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_name WHERE user_id = %d", $user_id)) : $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_name WHERE session_id = %s", $session_id));
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
    $totals = $wpdb->get_row($wpdb->prepare("SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items FROM $table_cart c JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)", $user_id, $session_id));
    wp_send_json_success([ 'new_qty' => $qty, 'grand_total_fmt' => tema_dw_format_rupiah($totals->grand_total ?? 0), 'total_items' => intval($totals->total_items ?? 0) ]);
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
    $totals = $wpdb->get_row($wpdb->prepare("SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items FROM {$wpdb->prefix}dw_cart c JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)", $user_id, $session_id));
    wp_send_json_success([ 'grand_total_fmt' => tema_dw_format_rupiah($totals->grand_total ?? 0), 'total_items' => intval($totals->total_items ?? 0) ]);
}

/**
 * ==============================================================================
 * 6. AJAX HANDLERS: MERCHANT DASHBOARD (TOKO)
 * ==============================================================================
 */

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

add_action('wp_ajax_dw_merchant_get_products', 'dw_ajax_merchant_get_products');
function dw_ajax_merchant_get_products() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $pid = dw_get_merchant_id();
    $products = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_produk WHERE id_pedagang = %d AND status != 'arsip' ORDER BY created_at DESC", $pid));
    wp_send_json_success($products);
}

add_action('wp_ajax_dw_merchant_save_product', 'dw_ajax_merchant_save_product');
function dw_ajax_merchant_save_product() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $pid = dw_get_merchant_id();
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if (empty($_POST['nama_produk']) || empty($_POST['harga'])) wp_send_json_error(['message' => 'Lengkapi data']);
    
    $data = [
        'id_pedagang' => $pid,
        'nama_produk' => sanitize_text_field($_POST['nama_produk']),
        'harga' => floatval($_POST['harga']),
        'stok' => intval($_POST['stok']),
        'kategori' => sanitize_text_field($_POST['kategori']),
        'deskripsi' => wp_kses_post($_POST['deskripsi']),
        'updated_at' => current_time('mysql')
    ];
    if ($id == 0) {
        $data['slug'] = sanitize_title($_POST['nama_produk']) . '-' . time();
        $data['created_at'] = current_time('mysql');
    }
    if (!empty($_FILES['foto_utama']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        $att_id = media_handle_upload('foto_utama', 0);
        if (!is_wp_error($att_id)) $data['foto_utama'] = wp_get_attachment_url($att_id);
    }
    if ($id > 0) { $wpdb->update("{$wpdb->prefix}dw_produk", $data, ['id' => $id, 'id_pedagang' => $pid]); }
    else { $wpdb->insert("{$wpdb->prefix}dw_produk", $data); }
    wp_send_json_success(['message' => 'Tersimpan']);
}

add_action('wp_ajax_dw_merchant_delete_product', 'dw_ajax_merchant_delete_product');
function dw_ajax_merchant_delete_product() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $wpdb->update("{$wpdb->prefix}dw_produk", ['status' => 'arsip'], ['id' => intval($_POST['product_id']), 'id_pedagang' => dw_get_merchant_id()]);
    wp_send_json_success();
}

add_action('wp_ajax_dw_merchant_get_orders', 'dw_ajax_merchant_get_orders');
function dw_ajax_merchant_get_orders() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
    $sql = $wpdb->prepare("SELECT sub.*, m.kode_unik, m.nama_penerima as nama_pembeli, m.tanggal_transaksi, m.bukti_pembayaran FROM {$wpdb->prefix}dw_transaksi_sub sub JOIN {$wpdb->prefix}dw_transaksi m ON sub.id_transaksi = m.id WHERE sub.id_pedagang = %d ORDER BY sub.created_at DESC LIMIT %d", dw_get_merchant_id(), $limit);
    $orders = $wpdb->get_results($sql);
    foreach($orders as $o) {
        $o->formatted_date = date('d M Y', strtotime($o->tanggal_transaksi));
        $o->formatted_total = tema_dw_format_rupiah($o->total_pesanan_toko);
        $o->status_label = ucwords(str_replace('_', ' ', $o->status_pesanan));
    }
    wp_send_json_success($orders);
}

add_action('wp_ajax_dw_merchant_update_status', 'dw_ajax_merchant_update_status');
function dw_ajax_merchant_update_status() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $data = ['status_pesanan' => sanitize_text_field($_POST['status'])];
    if(isset($_POST['resi'])) $data['no_resi'] = sanitize_text_field($_POST['resi']);
    $wpdb->update("{$wpdb->prefix}dw_transaksi_sub", $data, ['id' => intval($_POST['order_id']), 'id_pedagang' => dw_get_merchant_id()]);
    wp_send_json_success();
}

add_action('wp_ajax_dw_merchant_get_profile', 'dw_ajax_merchant_get_profile');
function dw_ajax_merchant_get_profile() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_pedagang WHERE id = %d", dw_get_merchant_id()));
    wp_send_json_success([ 
        'nama_toko' => $p->nama_toko, 
        'deskripsi_toko' => $p->alamat_lengkap, 
        'no_rekening' => $p->no_rekening, 
        'nama_bank' => $p->nama_bank, 
        'atas_nama_rekening' => $p->atas_nama_rekening 
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
 * 7. AJAX HANDLERS: DESA DASHBOARD
 * ==============================================================================
 */

add_action('wp_ajax_dw_desa_stats', 'dw_ajax_desa_stats');
function dw_ajax_desa_stats() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $desa_id = dw_get_desa_id();
    if (!$desa_id) wp_send_json_error(['message' => 'Desa tidak ditemukan']);
    $wisata_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dw_wisata WHERE id_desa = %d AND status = 'aktif'", $desa_id));
    $avg_rating = $wpdb->get_var($wpdb->prepare("SELECT AVG(rating_avg) FROM {$wpdb->prefix}dw_wisata WHERE id_desa = %d AND status = 'aktif'", $desa_id));
    wp_send_json_success([ 'total_wisata' => (int)$wisata_count, 'avg_rating' => number_format((float)$avg_rating, 1) ]);
}

add_action('wp_ajax_dw_desa_get_wisata', 'dw_ajax_desa_get_wisata');
function dw_ajax_desa_get_wisata() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $desa_id = dw_get_desa_id();
    $wisata = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_wisata WHERE id_desa = %d AND status != 'nonaktif' ORDER BY created_at DESC", $desa_id));
    wp_send_json_success($wisata);
}

add_action('wp_ajax_dw_desa_save_wisata', 'dw_ajax_desa_save_wisata');
function dw_ajax_desa_save_wisata() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $desa_id = dw_get_desa_id();
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $data = [
        'id_desa' => $desa_id,
        'nama_wisata' => sanitize_text_field($_POST['nama_wisata']),
        'harga_tiket' => floatval($_POST['harga_tiket']),
        'kategori' => sanitize_text_field($_POST['kategori']),
        'deskripsi' => wp_kses_post($_POST['deskripsi']),
        'jam_buka' => sanitize_text_field($_POST['jam_buka']),
        'updated_at' => current_time('mysql')
    ];
    if ($id == 0) {
        $data['slug'] = sanitize_title($_POST['nama_wisata']) . '-' . time();
        $data['created_at'] = current_time('mysql');
    }
    if (!empty($_FILES['foto_utama']['name'])) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        $att_id = media_handle_upload('foto_utama', 0);
        if (!is_wp_error($att_id)) $data['foto_utama'] = wp_get_attachment_url($att_id);
    }
    if ($id > 0) { $wpdb->update("{$wpdb->prefix}dw_wisata", $data, ['id' => $id, 'id_desa' => $desa_id]); }
    else { $wpdb->insert("{$wpdb->prefix}dw_wisata", $data); }
    wp_send_json_success(['message' => 'Wisata Tersimpan']);
}

add_action('wp_ajax_dw_desa_delete_wisata', 'dw_ajax_desa_delete_wisata');
function dw_ajax_desa_delete_wisata() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $wpdb->update("{$wpdb->prefix}dw_wisata", ['status' => 'nonaktif'], ['id' => intval($_POST['wisata_id']), 'id_desa' => dw_get_desa_id()]);
    wp_send_json_success();
}

add_action('wp_ajax_dw_desa_get_profile', 'dw_ajax_desa_get_profile');
function dw_ajax_desa_get_profile() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_desa WHERE id = %d", dw_get_desa_id()));
    wp_send_json_success([ 'nama_desa' => $p->nama_desa, 'deskripsi' => $p->deskripsi, 'provinsi' => $p->provinsi, 'kabupaten' => $p->kabupaten ]);
}

add_action('wp_ajax_dw_desa_save_profile', 'dw_ajax_desa_save_profile');
function dw_ajax_desa_save_profile() {
    check_ajax_referer('dw_cart_nonce', 'security');
    global $wpdb;
    $data = [
        'nama_desa' => sanitize_text_field($_POST['nama_desa']),
        'deskripsi' => sanitize_textarea_field($_POST['deskripsi']),
        'provinsi' => sanitize_text_field($_POST['provinsi']),
        'kabupaten' => sanitize_text_field($_POST['kabupaten'])
    ];
    $wpdb->update("{$wpdb->prefix}dw_desa", $data, ['id' => dw_get_desa_id()]);
    wp_send_json_success();
}

/**
 * ==============================================================================
 * 8. REGION HANDLERS (WILAYAH)
 * ==============================================================================
 */

add_action('wp_ajax_dw_get_provinces', 'dw_ajax_get_provinces');
add_action('wp_ajax_nopriv_dw_get_provinces', 'dw_ajax_get_provinces');
function dw_ajax_get_provinces() {
    global $wpdb;
    $results = $wpdb->get_results("SELECT id, nama FROM {$wpdb->prefix}dw_provinsi ORDER BY nama ASC");
    wp_send_json_success($results);
}

add_action('wp_ajax_dw_get_regencies', 'dw_ajax_get_regencies');
add_action('wp_ajax_nopriv_dw_get_regencies', 'dw_ajax_get_regencies');
function dw_ajax_get_regencies() {
    global $wpdb;
    $prov_id = intval($_GET['province_id']);
    $results = $wpdb->get_results($wpdb->prepare("SELECT id, nama FROM {$wpdb->prefix}dw_kabupaten WHERE id_provinsi = %d ORDER BY nama ASC", $prov_id));
    wp_send_json_success($results);
}

/**
 * ==============================================================================
 * 9. WISHLIST & PROGRESSIVE WEB APP (PWA)
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

function dw_add_pwa_tags() {
    if (get_theme_mod('dw_pwa_enabled', '1') !== '1') return;
    $manifest_url = add_query_arg('dw-manifest', '1', home_url('/'));
    $sw_url       = add_query_arg('dw-sw', '1', home_url('/'));
    $theme_color  = get_theme_mod('dw_pwa_theme_color', '#16a34a');
    $site_icon    = get_option('site_icon');
    ?>
    <link rel="manifest" href="<?php echo esc_url($manifest_url); ?>">
    <meta name="theme-color" content="<?php echo esc_attr($theme_color); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <?php if ($site_icon) : ?>
    <link rel="apple-touch-icon" href="<?php echo esc_url(wp_get_attachment_image_url($site_icon, 'full')); ?>">
    <?php endif; ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo esc_url($sw_url); ?>', { scope: '/' })
                .then(function(reg) { console.log('PWA Registered'); })
                .catch(function(err) { console.log('PWA Error', err); });
            });
        }
    </script>
    <?php
}
add_action('wp_head', 'dw_add_pwa_tags');

function dw_pwa_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'dw_pwa_section', array( 'title' => 'Pengaturan PWA', 'priority' => 160 ));
    $wp_customize->add_setting( 'dw_pwa_enabled', array( 'default' => '1' ) );
    $wp_customize->add_control( 'dw_pwa_enabled', array( 'label' => 'Aktifkan PWA', 'section' => 'dw_pwa_section', 'type' => 'checkbox' ));
    $wp_customize->add_setting( 'dw_pwa_name', array( 'default' => get_bloginfo('name') ) );
    $wp_customize->add_control( 'dw_pwa_name', array( 'label' => 'Nama Aplikasi', 'section' => 'dw_pwa_section', 'type' => 'text' ));
    $wp_customize->add_setting( 'dw_pwa_short_name', array( 'default' => get_bloginfo('name') ) );
    $wp_customize->add_control( 'dw_pwa_short_name', array( 'label' => 'Nama Pendek', 'section' => 'dw_pwa_section', 'type' => 'text' ));
    $wp_customize->add_setting( 'dw_pwa_theme_color', array( 'default' => '#16a34a' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'dw_pwa_theme_color', array( 'label' => 'Warna Tema', 'section' => 'dw_pwa_section' )));
    $wp_customize->add_setting( 'dw_pwa_bg_color', array( 'default' => '#ffffff' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'dw_pwa_bg_color', array( 'label' => 'Warna Splash Screen', 'section' => 'dw_pwa_section' )));
}
add_action( 'customize_register', 'dw_pwa_customize_register' );

add_action('init', function() {
    if (isset($_GET['dw-manifest'])) {
        header('Content-Type: application/json; charset=utf-8');
        $name = get_theme_mod('dw_pwa_name', get_bloginfo('name'));
        $theme_color = get_theme_mod('dw_pwa_theme_color', '#16a34a');
        $site_icon_id = get_option('site_icon');
        $icons = [];
        if ($site_icon_id) {
            foreach ([192, 512] as $size) {
                $icon_data = wp_get_attachment_image_src($site_icon_id, [$size, $size]);
                if ($icon_data) $icons[] = [ "src" => $icon_data[0], "sizes" => "{$size}x{$size}", "type" => "image/png", "purpose" => "any maskable" ];
            }
        }
        echo json_encode([ "name" => $name, "short_name" => get_theme_mod('dw_pwa_short_name', $name), "start_url" => home_url('/'), "display" => "standalone", "background_color" => get_theme_mod('dw_pwa_bg_color', '#ffffff'), "theme_color" => $theme_color, "icons" => $icons ]);
        exit;
    }
    if (isset($_GET['dw-sw'])) {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: /'); 
        ?>
        const CACHE_NAME = 'dw-cache-v20';
        const OFFLINE_URL = '<?php echo home_url('/'); ?>';
        self.addEventListener('install', (event) => { event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll([OFFLINE_URL]))); self.skipWaiting(); });
        self.addEventListener('activate', (event) => { event.waitUntil(caches.keys().then((keys) => Promise.all(keys.map((k) => k !== CACHE_NAME && caches.delete(k))))); self.clients.claim(); });
        self.addEventListener('fetch', (event) => { if (event.request.mode === 'navigate') event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE_URL))); });
        <?php
        exit;
    }
});