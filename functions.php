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
    // Styles Utama
    wp_enqueue_style('main-style', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0');
    wp_enqueue_style('theme-style', get_stylesheet_uri());
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );
    wp_enqueue_script( 'tailwind', 'https://cdn.tailwindcss.com', array(), '3.3.0', false );
    
    // Scripts JS
    wp_enqueue_script('jquery');
    wp_enqueue_script('main-script', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', array('jquery'), '1.0.0', true);

    // Localize Script untuk AJAX Cart & Main Script
    $dw_ajax_params = array(
        'ajax_url'     => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('dw_cart_nonce'),
        'site_url'     => home_url(),
        'checkout_url' => home_url('/checkout')
    );

    wp_localize_script('ajax-cart', 'dw_ajax_data', $dw_ajax_params); // Legacy support
    wp_localize_script('main-script', 'dw_ajax', $dw_ajax_params);
}
add_action('wp_enqueue_scripts', 'tema_desa_wisata_scripts');

/**
 * ============================================================================
 * 4. HELPER FUNCTIONS (Safe Mode)
 * ============================================================================
 */

// Format Rupiah
if (!function_exists('dw_format_rupiah')) {
    function dw_format_rupiah($angka) {
        return 'Rp ' . number_format((float)$angka, 0, ',', '.');
    }
}

// Ambil Data Pedagang
if (!function_exists('dw_get_pedagang_data')) {
    function dw_get_pedagang_data($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'dw_pedagang';
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
        if(is_array($items)) {
            foreach ($items as $item) {
                $total += $item['price'] * $item['quantity'];
            }
        }
        return $total;
    }
}

// Get Cart Count (Untuk Header)
if ( ! function_exists( 'dw_get_cart_count' ) ) {
    function dw_get_cart_count() {
        if (isset($_SESSION['dw_cart'])) {
            return count($_SESSION['dw_cart']);
        }
        return 0;
    }
}

/**
 * ============================================================================
 * 5. AJAX HANDLERS (ADD TO CART & LOGIN)
 * ============================================================================
 */

// Add To Cart
if (!function_exists('dw_ajax_add_to_cart')) {
    function dw_ajax_add_to_cart() {
        // Cek Nonce (Security)
        if (isset($_POST['nonce'])) {
            if (!wp_verify_nonce($_POST['nonce'], 'dw_cart_nonce')) wp_send_json_error(['message' => 'Security check failed']);
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
            // Fallback ke Post Type (Wisata Ticket misalnya)
            $table_wisata = $wpdb->prefix . 'dw_wisata'; // Asumsi tiket wisata disimpan di tabel custom juga
            $wisata = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_wisata WHERE id = %d", $product_id));
            
            if($wisata) {
                $product_name = $wisata->nama_wisata;
                $product_price = $wisata->harga_tiket;
                $product_image = $wisata->foto_utama;
            } else {
                 // Fallback terakhir ke WP Post (jika ada)
                 $product = get_post($product_id);
                 if($product) {
                    $product_name  = $product->post_title;
                    $product_price = get_post_meta($product_id, 'dw_harga_produk', true) ?: 0;
                    $product_image = get_the_post_thumbnail_url($product_id, 'thumbnail');
                 }
            }
        }

        if (!isset($_SESSION['dw_cart'])) $_SESSION['dw_cart'] = [];

        // Cek apakah produk sudah ada
        $found = false;
        foreach ($_SESSION['dw_cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }

        // Tambah baru jika belum ada
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

// Login AJAX Handler
if (!function_exists('dw_ajax_login_handler')) {
    function dw_ajax_login_handler() {
        check_ajax_referer('dw_cart_nonce', 'security'); // Menggunakan nonce yang sama dg script localize
        
        $creds = array(
            'user_login'    => sanitize_text_field($_POST['username']),
            'user_password' => $_POST['password'],
            'remember'      => true
        );

        $user = wp_signon($creds, false);

        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
        } else {
            // Redirect Logic
            $redirect_url = home_url('/akun-saya');
            if (in_array('administrator', (array) $user->roles)) {
                $redirect_url = admin_url();
            } elseif (in_array('pedagang', (array) $user->roles)) {
                $redirect_url = home_url('/dashboard-toko');
            } elseif (in_array('admin_desa', (array) $user->roles)) {
                $redirect_url = home_url('/dashboard-desa');
            }
            
            wp_send_json_success(array('redirect_url' => $redirect_url));
        }
    }
}
add_action('wp_ajax_dw_login', 'dw_ajax_login_handler');
add_action('wp_ajax_nopriv_dw_login', 'dw_ajax_login_handler');


/**
 * ============================================================================
 * 6. CHECKOUT HANDLER
 * ============================================================================
 */
if (!function_exists('dw_process_checkout_handler')) {
    function dw_process_checkout_handler() {
        if (!isset($_POST['action']) || $_POST['action'] !== 'dw_process_checkout') return;
        if (!isset($_POST['dw_checkout_nonce']) || !wp_verify_nonce($_POST['dw_checkout_nonce'], 'dw_checkout_action')) wp_die('Security Check Failed');
        if (!is_user_logged_in()) { wp_redirect(home_url('/login')); exit; }

        $cart = dw_get_cart_items();
        if (empty($cart)) { wp_redirect(home_url('/cart')); exit; }

        global $wpdb;
        $user_id = get_current_user_id();
        
        $tbl_transaksi = $wpdb->prefix . 'dw_transaksi';
        $tbl_sub       = $wpdb->prefix . 'dw_transaksi_sub'; // Asumsi tabel sub ada (jika pakai plugin core)
        $tbl_items     = $wpdb->prefix . 'dw_detail_transaksi'; // Perbaikan nama tabel sesuai struktur plugin core
        $tbl_pedagang  = $wpdb->prefix . 'dw_pedagang';
        $tbl_produk    = $wpdb->prefix . 'dw_produk'; 

        $grand_total = 0;
        foreach ($cart as $item) {
            $grand_total += $item['price'] * $item['quantity'];
        }

        $kode_unik = 'TRX-' . strtoupper(wp_generate_password(6, false));
        
        // Insert Parent Transaksi
        $wpdb->insert($tbl_transaksi, [
            'id_user_pembeli' => $user_id, // Perbaikan nama kolom: id_pembeli -> id_user_pembeli
            'kode_transaksi' => $kode_unik, // Perbaikan nama kolom: kode_unik -> kode_transaksi
            'total_belanja' => $grand_total, // Perbaikan nama kolom
            'status_pembayaran' => 'pending', // Perbaikan enum status
            'detail_pengiriman' => sanitize_textarea_field($_POST['billing_address']), // Asumsi kolom ini ada
            'metode_pembayaran' => sanitize_text_field($_POST['payment_method']),
            'tanggal_transaksi' => current_time('mysql') // Perbaikan nama kolom created_at
        ]);
        $parent_trx_id = $wpdb->insert_id;

        // Insert Detail Items
        foreach ($cart as $item) {
             $wpdb->insert($tbl_items, [
                'id_transaksi' => $parent_trx_id,
                'id_produk' => $item['product_id'], // Asumsi produk, sesuaikan jika wisata
                'jenis_item' => 'produk', // Default produk
                'nama_item' => $item['name'],
                'harga_satuan' => $item['price'],
                'qty' => $item['quantity'], 
                'subtotal' => $item['price'] * $item['quantity'],
                'status_pesanan' => 'menunggu_pembayaran'
            ]);
        }

        unset($_SESSION['dw_cart']);
        wp_redirect(home_url('/transaksi')); // Redirect ke halaman riwayat
        exit;
    }
}
add_action('admin_post_dw_process_checkout', 'dw_process_checkout_handler');

// Redirect Login (Custom Rule)
if (!function_exists('dw_custom_login_redirect')) {
    function dw_custom_login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('pedagang', $user->roles)) return home_url('/dashboard-toko');
            elseif (in_array('admin_desa', $user->roles)) return home_url('/dashboard-desa');
            elseif (in_array('administrator', $user->roles)) return admin_url();
        }
        
        // Redirect khusus (misal dari halaman favorit yang butuh login)
        if (isset($_GET['redirect_to'])) {
            return esc_url($_GET['redirect_to']);
        }
        
        return home_url('/akun-saya');
    }
}
add_filter('login_redirect', 'dw_custom_login_redirect', 10, 3);

/**
 * ============================================================================
 * 7. CUSTOM REWRITE RULES & TEMPLATE LOADER (SOLUSI 404 & URL CANTIK)
 * ============================================================================
 */

// A. Register Query Vars
function dw_register_query_vars( $vars ) {
    $vars[] = 'dw_slug';      // Untuk Wisata/Produk Detail
    $vars[] = 'dw_slug_toko'; // Untuk Profil Toko
    $vars[] = 'dw_slug_desa'; // Untuk Profil Desa
    $vars[] = 'dw_is_favorit'; // VARIABLE BARU UNTUK FAVORIT
    return $vars;
}
add_filter( 'query_vars', 'dw_register_query_vars' );

// B. Add Rewrite Rules
function dw_add_rewrite_rules() {
    // 1. Profil Desa: sadesa.site/@nama-desa
    add_rewrite_rule('^@([^/]*)/?', 'index.php?dw_slug_desa=$matches[1]', 'top');

    // 2. Profil Toko: sadesa.site/toko/nama-toko
    add_rewrite_rule('^toko/([^/]*)/?', 'index.php?dw_slug_toko=$matches[1]', 'top');

    // 3. Detail Wisata: sadesa.site/wisata/detail/slug
    add_rewrite_rule('^wisata/detail/([^/]*)/?', 'index.php?dw_slug=$matches[1]', 'top');
    
    // 4. Detail Produk: sadesa.site/produk/detail/slug
    add_rewrite_rule('^produk/detail/([^/]*)/?', 'index.php?pagename=detail-produk&dw_slug=$matches[1]', 'top');
    
    // 5. Halaman Favorit: sadesa.site/favorit
    add_rewrite_rule('^favorit/?', 'index.php?dw_is_favorit=1', 'top');
}
add_action( 'init', 'dw_add_rewrite_rules' );

// C. Template Loader (Agar tidak 404 tanpa membuat Halaman fisik)
function dw_template_include( $template ) {
    // Load Profil Desa
    if ( get_query_var( 'dw_slug_desa' ) ) {
        $new_template = locate_template( array( 'page-profil-desa.php' ) );
        if ( '' != $new_template ) return $new_template;
    }
    // Load Profil Toko
    if ( get_query_var( 'dw_slug_toko' ) ) {
        $new_template = locate_template( array( 'page-profil-toko.php' ) );
        if ( '' != $new_template ) return $new_template;
    }
    // Load Detail Wisata (Membedakan dengan Detail Produk via URL)
    if ( get_query_var( 'dw_slug' ) ) {
        if (strpos($_SERVER['REQUEST_URI'], '/wisata/detail/') !== false) {
            $new_template = locate_template( array( 'page-detail-wisata.php' ) );
            if ( '' != $new_template ) return $new_template;
        }
    }
    // Load Halaman Favorit
    if ( get_query_var( 'dw_is_favorit' ) ) {
        $new_template = locate_template( array( 'page-favorit.php' ) );
        if ( '' != $new_template ) return $new_template;
    }
    
    return $template;
}
add_filter( 'template_include', 'dw_template_include' );
?>