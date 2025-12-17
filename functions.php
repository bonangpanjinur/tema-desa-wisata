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
 * 1. SESSION START (Wajib untuk Cart)
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
 * 2. SETUP DASAR THEME
 * ============================================================================
 */
function tema_desa_wisata_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('woocommerce');
    
    register_nav_menus(array(
        'primary' => __('Primary Menu', 'tema-desa-wisata'),
        'footer'  => __('Footer Menu', 'tema-desa-wisata'),
    ));
}
add_action('after_setup_theme', 'tema_desa_wisata_setup');

/**
 * ============================================================================
 * 3. ENQUEUE SCRIPTS & STYLES
 * ============================================================================
 */
function tema_desa_wisata_scripts() {
    // Styles
    wp_enqueue_style('main-style', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0');
    wp_enqueue_style('theme-style', get_stylesheet_uri());
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );
    wp_enqueue_script( 'tailwind', 'https://cdn.tailwindcss.com', array(), '3.3.0', false );
    
    // Scripts
    wp_enqueue_script('jquery');
    wp_enqueue_script('main-script', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true);
    // Pastikan path ajax-cart.js benar
    wp_enqueue_script('ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', array('jquery'), '1.0.0', true);

    // Localize Script untuk AJAX Cart
    wp_localize_script('main-script', 'dw_ajax', array(
        'ajax_url'     => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('dw_cart_nonce'),
        'site_url'     => home_url(),
        'checkout_url' => home_url('/checkout')
    ));
    // Support legacy script handle jika ada
    wp_localize_script('ajax-cart', 'dw_ajax_data', array(
        'ajax_url'     => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('dw_cart_nonce'),
        'site_url'     => home_url()
    ));
}
add_action('wp_enqueue_scripts', 'tema_desa_wisata_scripts');

/**
 * ============================================================================
 * 4. CUSTOM URL & REWRITE RULES (SOLUSI 404)
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
    // 1. Profil Desa: sadesa.site/@nama-desa
    // Mengarahkan ke index.php dengan query var khusus, bukan pagename
    add_rewrite_rule(
        '^@([^/]*)/?',
        'index.php?dw_slug_desa=$matches[1]',
        'top'
    );

    // 2. Profil Toko: sadesa.site/toko/nama-toko
    add_rewrite_rule(
        '^toko/([^/]*)/?',
        'index.php?dw_slug_toko=$matches[1]',
        'top'
    );

    // 3. Detail Wisata: sadesa.site/wisata/detail/judul-wisata
    add_rewrite_rule(
        '^wisata/detail/([^/]*)/?',
        'index.php?dw_slug=$matches[1]', // Menggunakan dw_slug agar tidak bentrok dengan post type default
        'top'
    );
    
    // 4. Detail Produk: sadesa.site/produk/detail/judul-produk
    add_rewrite_rule(
        '^produk/detail/([^/]*)/?',
        'index.php?pagename=detail-produk&dw_slug=$matches[1]',
        'top'
    );
}
add_action( 'init', 'dw_add_rewrite_rules' );

// C. Template Loader (Mencegah 404 dengan memuat file template secara paksa)
function dw_template_include( $template ) {
    // Jika ada query var dw_slug_desa, muat page-profil-desa.php
    if ( get_query_var( 'dw_slug_desa' ) ) {
        $new_template = locate_template( array( 'page-profil-desa.php' ) );
        if ( '' != $new_template ) {
            return $new_template;
        }
    }

    // Jika ada query var dw_slug_toko, muat page-profil-toko.php
    if ( get_query_var( 'dw_slug_toko' ) ) {
        $new_template = locate_template( array( 'page-profil-toko.php' ) );
        if ( '' != $new_template ) {
            return $new_template;
        }
    }

    // Jika ada query var dw_slug (untuk wisata), muat page-detail-wisata.php
    if ( get_query_var( 'dw_slug' ) ) {
        $new_template = locate_template( array( 'page-detail-wisata.php' ) );
        if ( '' != $new_template ) {
            return $new_template;
        }
    }

    return $template;
}
add_filter( 'template_include', 'dw_template_include' );

/**
 * ============================================================================
 * 5. DATABASE SETUP (Dijalankan sekali saat switch theme)
 * ============================================================================
 */
function dw_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    $table_prefix = $wpdb->prefix . 'dw_'; 

    // Tabel Keranjang (Hanya contoh, tabel lain dianggap sudah ada dari plugin Core)
    $sql_keranjang = "CREATE TABLE {$table_prefix}keranjang (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        id_user BIGINT(20) UNSIGNED NOT NULL,
        id_produk BIGINT(20) DEFAULT NULL,
        id_wisata BIGINT(20) DEFAULT NULL,
        jenis_item ENUM('produk','tiket') NOT NULL,
        qty INT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY id_user (id_user)
    ) $charset_collate;";
    dbDelta( $sql_keranjang );
}
add_action('after_switch_theme', 'dw_create_tables');

/**
 * ============================================================================
 * 6. HELPER FUNCTIONS (Dengan Pengecekan agar tidak Crash)
 * ============================================================================
 */

// Format Rupiah
if ( ! function_exists( 'dw_format_rupiah' ) ) {
    function dw_format_rupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
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
        if(is_array($items)) {
            foreach ($items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
        }
        return $total;
    }
}

// Get Cart Count Global (Untuk Header)
if ( ! function_exists( 'dw_get_cart_count' ) ) {
    function dw_get_cart_count() {
        // Cek session dulu
        if (isset($_SESSION['dw_cart'])) {
            return count($_SESSION['dw_cart']);
        }
        // Jika user login dan ingin cek DB (opsional)
        if (is_user_logged_in()) {
            global $wpdb;
            $user_id = get_current_user_id();
            $table = $wpdb->prefix . 'dw_keranjang';
            if($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                $count = $wpdb->get_var("SELECT SUM(qty) FROM $table WHERE id_user = $user_id");
                return $count ? $count : 0;
            }
        }
        return 0;
    }
}

/**
 * ============================================================================
 * 7. AJAX HANDLERS
 * ============================================================================
 */

// Add To Cart (Session Based)
if (!function_exists('dw_ajax_add_to_cart')) {
    function dw_ajax_add_to_cart() {
        // Cek nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dw_cart_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        $product_id = intval($_POST['product_id']);
        $quantity   = intval($_POST['quantity']);
        $is_custom_db = isset($_POST['is_custom_db']) ? true : false;

        if (!$product_id) wp_send_json_error(['message' => 'Produk tidak valid']);

        $product_name  = 'Produk';
        $product_price = 0;
        $product_image = '';

        // Ambil data produk
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
            // Fallback ke Post Type (Wisata tiket misalnya)
            $table_wisata = $wpdb->prefix . 'dw_wisata';
            $wisata = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_wisata WHERE id = %d", $product_id));
            if($wisata) {
                $product_name = $wisata->nama_wisata;
                $product_price = $wisata->harga_tiket;
                $product_image = $wisata->foto_utama;
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
}
add_action('wp_ajax_dw_theme_add_to_cart', 'dw_ajax_add_to_cart');
add_action('wp_ajax_nopriv_dw_theme_add_to_cart', 'dw_ajax_add_to_cart');

// AJAX Login (Supaya tidak error jika file page-login.php memanggilnya)
if (!function_exists('dw_ajax_login_handler')) {
    function dw_ajax_login_handler() {
        check_ajax_referer('dw_nonce', 'security');
        $creds = array(
            'user_login'    => sanitize_text_field($_POST['username']),
            'user_password' => $_POST['password'],
            'remember'      => true
        );
        $user = wp_signon($creds, false);
        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
        } else {
            wp_send_json_success(array('redirect_url' => home_url('/akun-saya')));
        }
    }
}
add_action('wp_ajax_dw_login', 'dw_ajax_login_handler');
add_action('wp_ajax_nopriv_dw_login', 'dw_ajax_login_handler');

// Login Redirect
function dw_custom_login_redirect_final($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('pedagang', $user->roles)) return home_url('/dashboard-toko');
        elseif (in_array('admin_desa', $user->roles)) return home_url('/dashboard-desa');
        elseif (in_array('administrator', $user->roles)) return admin_url();
    }
    return home_url('/akun-saya');
}
add_filter('login_redirect', 'dw_custom_login_redirect_final', 10, 3);

?>