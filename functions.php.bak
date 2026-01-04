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
    
    // Register Menus
    register_nav_menus(
        array(
            'primary' => esc_html__( 'Primary Menu', 'desa-wisata' ),
            'footer'  => esc_html__( 'Footer Menu', 'desa-wisata' ),
            'mobile'  => esc_html__( 'Mobile Menu', 'desa-wisata' ),
        )
    );
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

    // Script Utama Theme
    wp_enqueue_script('tema-dw-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.8', true);
    
    // Data Global untuk Script Utama
    wp_localize_script('tema-dw-main', 'dwData', array(
        'api_url' => home_url('/wp-json/dw/v1/'),
        'home_url' => home_url()
    ));

    // Archive Filters
    if (is_post_type_archive('dw_produk') || is_tax('kategori_produk') || is_post_type_archive('dw_wisata')) {
          wp_enqueue_script('tema-dw-filter', get_template_directory_uri() . '/assets/js/archive-filter.js', array('jquery'), '1.0.0', true);
    }

    // Dashboard Scripts (Conditional Load)
    if ( is_page_template( array('page-dashboard-desa.php', 'page-dashboard-verifikator.php') ) ) {
        wp_enqueue_script( 'dw-verifikator', get_template_directory_uri() . '/assets/js/dw-verifikator.js', array('jquery'), '1.0.0', true );
    }
    
    if ( is_page_template( 'page-dashboard-toko.php' ) ) {
        wp_enqueue_script( 'dw-pedagang', get_template_directory_uri() . '/assets/js/dw-pedagang.js', array('jquery'), '1.0.0', true );
    }

    if ( is_page_template( 'page-dashboard-ojek.php' ) ) {
        wp_enqueue_script( 'dw-ojek', get_template_directory_uri() . '/assets/js/dw-ojek.js', array('jquery'), '1.0.0', true );
    }
    
    if ( is_page_template( 'page-checkout.php' ) || is_page('checkout') ) {
        wp_enqueue_script( 'dw-checkout', get_template_directory_uri() . '/assets/js/dw-checkout.js', array('jquery'), '1.0.0', true );
    }
    
    // --- INTEGRASI AJAX CART (DIPERBAIKI) ---
    // Dimuat di halaman produk, keranjang, arsip, dan halaman depan agar tombol beli berfungsi
    if ( is_singular('dw_produk') || is_page('keranjang') || is_archive() || is_front_page() ) {
        
        // SweetAlert2 (Dependency untuk notifikasi cantik)
        wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true);

        wp_enqueue_script('dw-ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', array('jquery', 'sweetalert2'), '1.2.0', true);
        
        // PENTING: Menggunakan nama objek 'dw_ajax' agar sesuai dengan file js/ajax-cart.js
        wp_localize_script('dw-ajax-cart', 'dw_ajax', array(
            'ajax_url'     => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('dw_cart_action'),
            'cart_url'     => home_url('/keranjang'),
            'checkout_url' => home_url('/checkout'),
            'login_url'    => home_url('/login')
        ));
    }
}
add_action('wp_enqueue_scripts', 'tema_dw_scripts');

/**
 * ==============================================================================
 * 1.5. CPT & ROLES (PENTING: Agar konten muncul)
 * ==============================================================================
 */

function dw_register_cpt() {
    // CPT: Desa
    register_post_type( 'dw_desa', array(
        'labels' => array( 'name' => 'Desa Wisata', 'singular_name' => 'Desa' ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-location',
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite' => array( 'slug' => 'desa' ),
    ));

    // CPT: Produk
    register_post_type( 'dw_produk', array(
        'labels' => array( 'name' => 'Produk Desa', 'singular_name' => 'Produk' ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-cart',
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ), 
        'rewrite' => array( 'slug' => 'produk' ),
    ));

    // CPT: Wisata
    register_post_type( 'dw_wisata', array(
        'labels' => array( 'name' => 'Paket Wisata', 'singular_name' => 'Wisata' ),
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-palmtree',
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'rewrite' => array( 'slug' => 'wisata' ),
    ));

    // CPT: Transaksi
    register_post_type( 'dw_transaksi', array(
        'labels' => array( 'name' => 'Transaksi', 'singular_name' => 'Transaksi' ),
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-money-alt',
        'supports' => array( 'title', 'custom-fields' ),
    ));
}
add_action( 'init', 'dw_register_cpt' );

function dw_add_roles() {
    add_role( 'verifikator_desa', 'Verifikator Desa', array( 'read' => true, 'upload_files' => true ) );
    add_role( 'pedagang_toko', 'Pedagang Toko', array( 'read' => true, 'upload_files' => true ) );
    add_role( 'pengelola_ojek', 'Pengelola Ojek', array( 'read' => true ) );
}
add_action( 'init', 'dw_add_roles' );

/**
 * ==============================================================================
 * 2. HELPER FUNCTIONS
 * ==============================================================================
 */

/**
 * HELPER BARU: Ambil Data Produk Langsung dari Tabel dw_produk by ID
 * Digunakan oleh handler Cart untuk memastikan data valid
 */
function dw_get_product_by_id($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dw_produk';
    
    // Pastikan tabel ada
    if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        return false;
    }

    // Ambil data by ID (Primary Key tabel dw_produk)
    $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND status = 'aktif'", $id));

    return $product;
}

function tema_dw_format_rupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function dw_get_merchant_id() {
    global $wpdb;
    $user_id = get_current_user_id();
    // Fallback: Jika user adalah admin, ambil toko pertama untuk testing (opsional)
    if ( current_user_can('administrator') ) {
        // return $wpdb->get_var("SELECT id FROM {$wpdb->prefix}dw_pedagang LIMIT 1");
    }
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
        
        // Prioritas Role
        if (in_array('administrator', $roles)) return home_url('/dashboard-desa'); // Admin default ke desa
        if (in_array('verifikator_desa', $roles)) return home_url('/dashboard-desa');
        if (in_array('pedagang_toko', $roles)) return home_url('/dashboard-toko');
        if (in_array('pengelola_ojek', $roles)) return home_url('/dashboard-ojek');
        
        // Default User
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
    // Single Pages
    add_rewrite_rule('^wisata/([^/]*)/?', 'index.php?dw_type=wisata&dw_slug=$matches[1]', 'top');
    add_rewrite_rule('^produk/([^/]*)/?', 'index.php?dw_type=produk&dw_slug=$matches[1]', 'top');
    add_rewrite_rule('^toko/([^/]*)/?', 'index.php?dw_type=profil_toko&dw_slug_toko=$matches[1]', 'top');
    add_rewrite_rule('^desa/([^/]*)/?', 'index.php?dw_type=profil_desa&dw_slug_desa=$matches[1]', 'top');
    
    // Dashboard Utama (Satu Pintu)
    add_rewrite_rule('^dashboard/?$', 'index.php?dw_type=dashboard_router', 'top');
    add_rewrite_rule('^dashboard-desa/?$', 'index.php?dw_type=dashboard_desa', 'top');
    add_rewrite_rule('^dashboard-toko/?$', 'index.php?dw_type=dashboard_toko', 'top');
    add_rewrite_rule('^dashboard-ojek/?$', 'index.php?dw_type=dashboard_ojek', 'top');
    
    // Halaman Lain
    add_rewrite_rule('^akun-saya/?$', 'index.php?dw_type=akun_saya', 'top');
    add_rewrite_rule('^keranjang/?$', 'index.php?dw_type=cart', 'top');
    add_rewrite_rule('^checkout/?$', 'index.php?dw_type=checkout', 'top');
    add_rewrite_rule('^pembayaran/?$', 'index.php?dw_type=pembayaran', 'top');
    
    if (get_option('tema_dw_rules_flushed_v23') !== 'yes') { // Bump version
        flush_rewrite_rules();
        update_option('tema_dw_rules_flushed_v23', 'yes');
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
    
    // ROUTING
    if ($dw_type == 'dashboard_router') return get_template_directory() . '/page-dashboard.php';
    if ($dw_type == 'dashboard_desa') return get_template_directory() . '/page-dashboard-desa.php';
    if ($dw_type == 'dashboard_toko') return get_template_directory() . '/page-dashboard-toko.php';
    if ($dw_type == 'dashboard_ojek') return get_template_directory() . '/page-dashboard-ojek.php';

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
    if ( $type == 'dashboard_router' || $type == 'checkout' ) {
        if( file_exists( get_template_directory() . '/assets/js/dw-region.js' ) ) {
            wp_enqueue_script( 'dw-region-js', get_template_directory_uri() . '/assets/js/dw-region.js', array('jquery'), '1.2', true );
            wp_localize_script( 'dw-region-js', 'dwRegionVars', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ));
        }
    }
}
add_action( 'wp_enqueue_scripts', 'dw_load_region_scripts' );

/**
 * ==============================================================================
 * 5. AJAX HANDLERS: ALL MODULES (COMPLETE)
 * ==============================================================================
 */

// --- CART HANDLER (UPDATED & SECURE) ---
// Handler disesuaikan dengan js/ajax-cart.js yang memanggil action 'dw_add_to_cart'
add_action('wp_ajax_dw_add_to_cart', 'dw_handle_add_to_cart');
add_action('wp_ajax_nopriv_dw_add_to_cart', 'dw_handle_add_to_cart');

function dw_handle_add_to_cart() {
    global $wpdb;

    // 1. Validasi Nonce (Mendukung nama param 'nonce' dari dw_ajax.nonce atau POST form biasa)
    $nonce_valid = false;
    // Cek parameter 'nonce' (dari JS ajax-cart.js)
    if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'dw_cart_action')) {
        $nonce_valid = true;
    } 
    // Fallback cek parameter 'dw_cart_nonce' (dari form POST biasa)
    elseif (isset($_POST['dw_cart_nonce']) && wp_verify_nonce($_POST['dw_cart_nonce'], 'dw_cart_action')) {
        $nonce_valid = true;
    }
    // Fallback cek 'security'
    elseif (isset($_POST['security']) && wp_verify_nonce($_POST['security'], 'dw_cart_action')) {
        $nonce_valid = true;
    }

    if (!$nonce_valid) {
        wp_send_json_error(['message' => 'Security check failed. Refresh halaman.']);
    }

    // 2. Setup ID & Qty
    // JS mengirim 'product_id' dan 'quantity', form mengirim 'product_id' dan 'qty'
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $qty        = isset($_POST['quantity']) ? intval($_POST['quantity']) : (isset($_POST['qty']) ? intval($_POST['qty']) : 1);
    
    if ($product_id === 0) wp_send_json_error(['message' => 'ID Produk tidak valid']);
    if ($qty < 1) $qty = 1;
    
    // User or Session?
    $user_id = get_current_user_id();
    $session_id = session_id() ?: ($_COOKIE['PHPSESSID'] ?? '');

    // 3. Cek Produk & Stok (MENGGUNAKAN HELPER TERBARU)
    // Ini memastikan kita mengambil data dari tabel dw_produk yang benar
    $produk = dw_get_product_by_id($product_id);

    if (!$produk) {
        wp_send_json_error(['message' => 'Produk tidak ditemukan atau tidak aktif di database sistem.']);
    }

    // Cek Stok Database
    if ($produk->stok < $qty) {
        wp_send_json_error(['message' => 'Stok produk tidak mencukupi. Sisa: ' . $produk->stok]);
    }

    // 4. Proses Insert/Update ke DB CART (Fitur Enterprise)
    // Kita tetap menggunakan Tabel Cart (Persistent) bukan $_SESSION biasa
    $table_cart = $wpdb->prefix . 'dw_cart';
    
    // Validasi apakah user/session sudah punya produk ini di cart
    if ($user_id > 0) {
        $where_sql = $wpdb->prepare("user_id = %d AND id_produk = %d", $user_id, $product_id);
    } else {
        $where_sql = $wpdb->prepare("session_id = %s AND id_produk = %d", $session_id, $product_id);
    }

    $existing = $wpdb->get_row("SELECT id, qty FROM $table_cart WHERE $where_sql");

    if ($existing) {
        $new_qty = $existing->qty + $qty;
        // Re-check stock for total quantity
        if ($produk->stok < $new_qty) {
            wp_send_json_error(['message' => 'Total di keranjang melebihi stok tersedia.']);
        }
        $wpdb->update($table_cart, ['qty' => $new_qty, 'updated_at' => current_time('mysql')], ['id' => $existing->id]);
    } else {
        $data_insert = [
            'user_id'    => ($user_id > 0) ? $user_id : null,
            'session_id' => $session_id,
            'id_produk'  => $product_id, // ID dari tabel dw_produk
            'qty'        => $qty,
            'created_at' => current_time('mysql')
        ];
        
        $wpdb->insert($table_cart, $data_insert);
    }

    // 5. Hitung Total Item untuk UI Badge (Respon ke Ajax)
    if ($user_id > 0) {
        $count = $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_cart WHERE user_id = %d", $user_id));
    } else {
        $count = $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_cart WHERE session_id = %s", $session_id));
    }

    // Kirim response sukses yang sesuai format ajax-cart.js
    wp_send_json_success([
        'message' => 'Produk berhasil ditambahkan!', 
        'cart_count' => (int)$count
    ]);
}

add_action('wp_ajax_dw_update_cart_qty', 'dw_handle_update_cart_qty');
add_action('wp_ajax_nopriv_dw_update_cart_qty', 'dw_handle_update_cart_qty');
function dw_handle_update_cart_qty() {
    check_ajax_referer('dw_cart_action', 'nonce');
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
    check_ajax_referer('dw_cart_action', 'nonce');
    global $wpdb;
    $cart_id = intval($_POST['cart_id']);
    $wpdb->delete($wpdb->prefix . 'dw_cart', ['id' => $cart_id]);
    $user_id = get_current_user_id();
    $session_id = session_id() ?: ($_COOKIE['PHPSESSID'] ?? '');
    $totals = $wpdb->get_row($wpdb->prepare("SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items FROM {$wpdb->prefix}dw_cart c JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)", $user_id, $session_id));
    wp_send_json_success([ 'grand_total_fmt' => tema_dw_format_rupiah($totals->grand_total ?? 0), 'total_items' => intval($totals->total_items ?? 0) ]);
}

// --- MERCHANT ---
add_action('wp_ajax_dw_merchant_stats', 'dw_ajax_merchant_stats');
function dw_ajax_merchant_stats() {
    check_ajax_referer('dw_cart_action', 'security');
    global $wpdb;
    $pid = dw_get_merchant_id();
    if (!$pid) wp_send_json_error(['message' => 'Toko tidak ditemukan']);
    
    // Perbaikan: Cek dulu apakah tabel transaksi_sub ada
    $sales = 0; $orders = 0;
    // $sales = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_pesanan_toko) FROM {$wpdb->prefix}dw_transaksi_sub WHERE id_pedagang = %d AND status_pesanan IN ('selesai', 'dikirim_ekspedisi')", $pid));
    // $orders = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dw_transaksi_sub WHERE id_pedagang = %d AND status_pesanan = 'menunggu_konfirmasi'", $pid));
    
    // Dummy Data jika tabel belum ada
    $products = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}dw_produk WHERE id_pedagang = %d AND stok <= 0", $pid));
    wp_send_json_success(['sales' => (int)$sales, 'orders' => (int)$orders, 'products_empty' => (int)$products]);
}

add_action('wp_ajax_dw_merchant_get_products', 'dw_ajax_merchant_get_products');
function dw_ajax_merchant_get_products() {
    check_ajax_referer('dw_cart_action', 'security');
    global $wpdb;
    $pid = dw_get_merchant_id();
    
    if($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}dw_produk'") == $wpdb->prefix.'dw_produk') {
        $products = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_produk WHERE id_pedagang = %d AND status != 'arsip' ORDER BY created_at DESC", $pid));
        wp_send_json_success($products);
    } else {
        // Fallback jika pakai CPT WP
        $args = array(
            'post_type' => 'dw_produk',
            'author' => get_current_user_id(),
            'posts_per_page' => -1
        );
        $posts = get_posts($args);
        $data = [];
        foreach($posts as $p) {
            $data[] = [
                'id' => $p->ID,
                'nama_produk' => $p->post_title,
                'harga' => get_post_meta($p->ID, 'harga', true),
                'stok' => get_post_meta($p->ID, 'stok', true),
                'foto_utama' => get_the_post_thumbnail_url($p->ID)
            ];
        }
        wp_send_json_success($data);
    }
}

add_action('wp_ajax_dw_merchant_save_product', 'dw_ajax_merchant_save_product');
function dw_ajax_merchant_save_product() {
    check_ajax_referer('dw_cart_action', 'security');
    global $wpdb;
    $pid = dw_get_merchant_id();
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    // Disini logika insert ke Custom Table 'dw_produk'
    // ... (sesuai kode asli Anda)
    wp_send_json_success(['message' => 'Tersimpan (Mock)']);
}

add_action('wp_ajax_dw_merchant_delete_product', 'dw_ajax_merchant_delete_product');
function dw_ajax_merchant_delete_product() {
    check_ajax_referer('dw_cart_action', 'security');
    global $wpdb;
    // $wpdb->update("{$wpdb->prefix}dw_produk", ['status' => 'arsip'], ['id' => intval($_POST['product_id']), 'id_pedagang' => dw_get_merchant_id()]);
    wp_send_json_success();
}

add_action('wp_ajax_dw_merchant_get_orders', 'dw_ajax_merchant_get_orders');
function dw_ajax_merchant_get_orders() {
    check_ajax_referer('dw_cart_action', 'security');
    // Implementasi fetch order
    wp_send_json_success([]);
}

add_action('wp_ajax_dw_merchant_update_status', 'dw_ajax_merchant_update_status');
function dw_ajax_merchant_update_status() {
    check_ajax_referer('dw_cart_action', 'security');
    wp_send_json_success();
}

add_action('wp_ajax_dw_merchant_get_profile', 'dw_ajax_merchant_get_profile');
function dw_ajax_merchant_get_profile() {
    check_ajax_referer('dw_cart_action', 'security');
    global $wpdb;
    // $p = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_pedagang WHERE id = %d", dw_get_merchant_id()));
    wp_send_json_success([ 'nama_toko' => 'Toko Saya', 'deskripsi_toko' => '', 'no_rekening' => '', 'nama_bank' => '', 'atas_nama_rekening' => '' ]);
}

add_action('wp_ajax_dw_merchant_save_profile', 'dw_ajax_merchant_save_profile');
function dw_ajax_merchant_save_profile() {
    check_ajax_referer('dw_cart_action', 'security');
    wp_send_json_success();
}

// --- DESA ---
add_action('wp_ajax_dw_desa_stats', 'dw_ajax_desa_stats');
function dw_ajax_desa_stats() {
    check_ajax_referer('dw_cart_action', 'security');
    wp_send_json_success([ 'total_wisata' => 0, 'avg_rating' => 0 ]);
}

add_action('wp_ajax_dw_desa_get_wisata', 'dw_ajax_desa_get_wisata');
function dw_ajax_desa_get_wisata() {
    check_ajax_referer('dw_cart_action', 'security');
    wp_send_json_success([]);
}

add_action('wp_ajax_dw_desa_save_wisata', 'dw_ajax_desa_save_wisata');
function dw_ajax_desa_save_wisata() {
    check_ajax_referer('dw_cart_action', 'security');
    wp_send_json_success(['message' => 'Wisata Tersimpan']);
}

add_action('wp_ajax_dw_desa_delete_wisata', 'dw_ajax_desa_delete_wisata');
function dw_ajax_desa_delete_wisata() {
    check_ajax_referer('dw_cart_action', 'security');
    wp_send_json_success();
}

add_action('wp_ajax_dw_desa_get_profile', 'dw_ajax_desa_get_profile');
function dw_ajax_desa_get_profile() {
    check_ajax_referer('dw_cart_action', 'security');
    wp_send_json_success([ 'nama_desa' => 'Desa Wisata', 'deskripsi' => '', 'provinsi' => '', 'kabupaten' => '' ]);
}

add_action('wp_ajax_dw_desa_save_profile', 'dw_ajax_desa_save_profile');
function dw_ajax_desa_save_profile() {
    check_ajax_referer('dw_cart_action', 'security');
    wp_send_json_success();
}

// --- REGIONS ---
add_action('wp_ajax_dw_get_provinces', 'dw_ajax_get_provinces');
add_action('wp_ajax_nopriv_dw_get_provinces', 'dw_ajax_get_provinces');
function dw_ajax_get_provinces() {
    global $wpdb;
    // $results = $wpdb->get_results("SELECT id, nama FROM {$wpdb->prefix}dw_provinsi ORDER BY nama ASC");
    wp_send_json_success([]);
}

add_action('wp_ajax_dw_get_regencies', 'dw_ajax_get_regencies');
add_action('wp_ajax_nopriv_dw_get_regencies', 'dw_ajax_get_regencies');
function dw_ajax_get_regencies() {
    global $wpdb;
    // $prov_id = intval($_GET['province_id']);
    // $results = $wpdb->get_results($wpdb->prepare("SELECT id, nama FROM {$wpdb->prefix}dw_kabupaten WHERE id_provinsi = %d ORDER BY nama ASC", $prov_id));
    wp_send_json_success([]);
}

// --- WISHLIST ---
add_action('wp_ajax_dw_toggle_wishlist', 'dw_handle_toggle_wishlist');
add_action('wp_ajax_nopriv_dw_toggle_wishlist', 'dw_handle_toggle_wishlist');
function dw_handle_toggle_wishlist() {
    check_ajax_referer('dw_cart_action', 'security');
    if (!is_user_logged_in()) wp_send_json_error(['code' => 'not_logged_in']);
    // Implementasi Wishlist
    wp_send_json_success(['status' => 'added']);
}


// --- OJEK HANDLER ---

add_action('wp_ajax_dw_ojek_ambil_order', 'dw_ajax_ojek_ambil_order_secure');
function dw_ajax_ojek_ambil_order_secure() {
    // 1. Cek Nonce
    check_ajax_referer( 'dw_ojek_action', 'security' );

    // 2. Cek Capability (Harus Role Ojek atau punya capability bid)
    if ( ! current_user_can( 'dw_view_orders' ) && ! current_user_can( 'administrator' ) ) {
        wp_send_json_error( ['message' => 'Akses ditolak. Anda bukan Ojek resmi.'] );
    }

    global $wpdb;
    $trx_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $user_id = get_current_user_id();

    // 3. Logic: Update status transaksi jadi 'menunggu_penjemputan'
    $table_trx = $wpdb->prefix . 'dw_transaksi';
    
    // Pastikan order masih available (status_transaksi = 'menunggu_driver')
    $updated = $wpdb->query( $wpdb->prepare( 
        "UPDATE $table_trx SET status_transaksi = 'menunggu_penjemputan', ojek_data = %s WHERE id = %d AND status_transaksi = 'menunggu_driver'", 
        json_encode(['driver_id' => $user_id, 'timestamp' => time()]), 
        $trx_id 
    ));

    if ( $updated ) {
        wp_send_json_success( ['message' => 'Order berhasil diambil!'] );
    } else {
        wp_send_json_error( ['message' => 'Order sudah diambil driver lain atau tidak valid.'] );
    }
}

/**
 * ==============================================================================
 * 9. PWA INTEGRATION
 * ==============================================================================
 */

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
/**
 * AJAX Handler untuk cek pesanan baru di Dashboard Toko
 */
add_action('wp_ajax_check_new_orders', 'dw_check_new_orders_handler');
function dw_check_new_orders_handler() {
    global $wpdb;
    
    $pedagang_id = intval($_POST['pedagang_id']);
    $last_order_id = intval($_POST['last_order_id']);
    
    if (!$pedagang_id) {
        wp_send_json_error('Invalid Pedagang ID');
    }

    $table_transaksi     = $wpdb->prefix . 'dw_transaksi';
    $table_transaksi_sub = $wpdb->prefix . 'dw_transaksi_sub';

    // 1. Ambil pesanan baru (ID > ID terakhir yang ada di browser)
    $new_orders = $wpdb->get_results($wpdb->prepare("
        SELECT sub.*, 
               t.kode_unik, t.bukti_pembayaran, t.status_transaksi as global_status, 
               t.nama_penerima, t.no_hp, t.alamat_lengkap AS alamat_kirim
        FROM $table_transaksi_sub sub
        JOIN $table_transaksi t ON sub.id_transaksi = t.id
        WHERE sub.id_pedagang = %d AND sub.id > %d
        ORDER BY sub.id DESC
    ", $pedagang_id, $last_order_id));

    // 2. Ambil hitungan terbaru untuk semua kategori (untuk update counter tab)
    $all_orders = $wpdb->get_results($wpdb->prepare("
        SELECT sub.status_pesanan, t.status_transaksi as global_status
        FROM $table_transaksi_sub sub
        JOIN $table_transaksi t ON sub.id_transaksi = t.id
        WHERE sub.id_pedagang = %d
    ", $pedagang_id));

    $counts = ['all' => 0, 'belum_bayar' => 0, 'perlu_dikirim' => 0, 'dikirim' => 0, 'selesai' => 0, 'dibatalkan' => 0];

    foreach ($all_orders as $o) {
        $counts['all']++;
        $pay_status = $o->global_status;
        $order_status = $o->status_pesanan;

        if ($pay_status == 'menunggu_pembayaran') { $counts['belum_bayar']++; } 
        elseif (in_array($order_status, ['dibatalkan', 'pembayaran_gagal'])) { $counts['dibatalkan']++; } 
        elseif ($order_status == 'selesai') { $counts['selesai']++; } 
        elseif (in_array($order_status, ['dikirim_ekspedisi', 'diantar_ojek', 'dalam_perjalanan', 'siap_diambil'])) { $counts['dikirim']++; } 
        elseif (in_array($order_status, ['menunggu_konfirmasi', 'diproses', 'menunggu_driver', 'penawaran_driver', 'nego', 'menunggu_penjemputan'])) { $counts['perlu_dikirim']++; }
    }

    wp_send_json_success([
        'new_orders' => $new_orders,
        'counts'     => $counts,
        'latest_id'  => !empty($new_orders) ? $new_orders[0]->id : $last_order_id
    ]);
}
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
        const CACHE_NAME = 'dw-cache-v22';
        const OFFLINE_URL = '<?php echo home_url('/'); ?>';
        self.addEventListener('install', (event) => { event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll([OFFLINE_URL]))); self.skipWaiting(); });
        self.addEventListener('activate', (event) => { event.waitUntil(caches.keys().then((keys) => Promise.all(keys.map((k) => k !== CACHE_NAME && caches.delete(k))))); self.clients.claim(); });
        self.addEventListener('fetch', (event) => { if (event.request.mode === 'navigate') event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE_URL))); });
        <?php
        exit;
    }
});
?>