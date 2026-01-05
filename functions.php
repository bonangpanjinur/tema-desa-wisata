<?php
/**
 * Functions and definitions
 * Tema Desa Wisata (DW)
 * * Update: Integrasi Optimasi Wilayah & Caching
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Keluar jika diakses langsung
}

// Include Performance Optimizations (Pastikan file ini ada)
if ( file_exists( get_template_directory() . '/functions-performance.php' ) ) {
    require_once get_template_directory() . '/functions-performance.php';
}

/**
 * ==============================================================================
 * 1. THEME SETUP & ENQUEUE
 * ==============================================================================
 */

function tema_dw_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'dw-card-thumb', 400, 300, true );
    add_theme_support( 'custom-logo', array( 'height' => 60, 'width' => 180, 'flex-height' => true, 'flex-width' => true ) );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
    
    // Register Menus
    register_nav_menus( array(
        'primary' => esc_html__( 'Primary Menu', 'desa-wisata' ),
        'footer'  => esc_html__( 'Footer Menu', 'desa-wisata' ),
        'mobile'  => esc_html__( 'Mobile Menu', 'desa-wisata' ),
    ) );
}
add_action( 'after_setup_theme', 'tema_dw_setup' );

function tema_dw_scripts_optimized() {
    // 1. Tailwind CSS
    wp_enqueue_script( 'tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.4.0', false );
    
    // Konfigurasi Tailwind Inline
    wp_add_inline_script( 'tailwindcss', "
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
    " );

    // 2. CSS Styles
    wp_enqueue_style( 'google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null );
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );
    wp_enqueue_style( 'dashicons' );
    wp_enqueue_style( 'tema-dw-style', get_stylesheet_uri() );
    
    if ( file_exists( get_template_directory() . '/assets/css/main.css' ) ) {
        wp_enqueue_style( 'tema-dw-main-css', get_template_directory_uri() . '/assets/css/main.css', array(), filemtime( get_template_directory() . '/assets/css/main.css' ) );
    }

    // [BARU] Load Library Select2 (CDN) untuk Dropdown Searchable
    wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);

    // 3. Script Utama Theme
    wp_enqueue_script( 'tema-dw-main', get_template_directory_uri() . '/assets/js/main.js', array( 'jquery', 'select2-js' ), '1.0.8', true );
    
    wp_localize_script( 'tema-dw-main', 'dwData', array(
        'home_url' => home_url(),
        'ajax_url' => admin_url( 'admin-ajax.php' )
    ));

    // 4. Conditional Scripts
    if ( is_post_type_archive( 'dw_produk' ) || is_tax( 'kategori_produk' ) || is_post_type_archive( 'dw_wisata' ) ) {
         wp_enqueue_script( 'tema-dw-filter', get_template_directory_uri() . '/assets/js/archive-filter.js', array( 'jquery' ), '1.0.0', true );
    }

    if ( is_page_template( array( 'page-dashboard-desa.php', 'page-dashboard-verifikator.php' ) ) ) {
        wp_enqueue_script( 'dw-verifikator', get_template_directory_uri() . '/assets/js/dw-verifikator.js', array( 'jquery' ), '1.0.0', true );
    }
    
    if ( is_page_template( 'page-dashboard-toko.php' ) ) {
        wp_enqueue_script( 'dw-pedagang', get_template_directory_uri() . '/assets/js/dw-pedagang.js', array( 'jquery' ), '1.0.0', true );
        wp_localize_script( 'dw-pedagang', 'dw_pedagang_data', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'dw_pedagang_nonce' )
        ));
    }

    if ( is_page_template( 'page-dashboard-ojek.php' ) ) {
        wp_enqueue_script( 'dw-ojek', get_template_directory_uri() . '/assets/js/dw-ojek.js', array( 'jquery' ), '1.0.0', true );
    }
    
    if ( is_page_template( 'page-checkout.php' ) || is_page( 'checkout' ) ) {
        wp_enqueue_script( 'dw-checkout', get_template_directory_uri() . '/assets/js/dw-checkout.js', array( 'jquery' ), '1.0.0', true );
    }
    
    // 5. AJAX Cart
    if ( is_singular( 'dw_produk' ) || is_page( 'keranjang' ) || is_archive() || is_front_page() ) {
        wp_enqueue_script( 'sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), null, true );
        wp_enqueue_script( 'dw-ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', array( 'jquery', 'sweetalert2' ), '1.2.0', true );
        
        wp_localize_script( 'dw-ajax-cart', 'dw_ajax', array(
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( 'dw_cart_action' ),
            'cart_url'     => home_url( '/keranjang' ),
            'checkout_url' => home_url( '/checkout' ),
            'login_url'    => home_url( '/login' )
        ));
    }
}
add_action( 'wp_enqueue_scripts', 'tema_dw_scripts_optimized' );

/**
 * Load Region Scripts (Optimized)
 * Memuat dw-region.js dengan dependency Select2 dan Nonce Security
 */
function dw_load_region_scripts() {
    $type = get_query_var( 'dw_type' );
    $is_region_page = false;

    // Cek halaman yang butuh fitur wilayah (Register, Edit Profil, Checkout)
    if ( is_page( array('register', 'edit-profil', 'checkout', 'pendaftaran') ) ) $is_region_page = true;
    if ( $type == 'dashboard_router' || $type == 'checkout' || $type == 'akun_saya' ) $is_region_page = true;

    if ( $is_region_page ) {
        if( file_exists( get_template_directory() . '/assets/js/dw-region.js' ) ) {
            // [UPDATE] Tambahkan dependency 'select2-js' agar library termuat sebelum script region
            wp_enqueue_script( 'dw-region-js', get_template_directory_uri() . '/assets/js/dw-region.js', array( 'jquery', 'select2-js' ), '1.3', true );
            
            // [UPDATE] Gunakan nama objek 'dw_ajax' agar konsisten dengan script JS baru
            wp_localize_script( 'dw-region-js', 'dw_ajax', array( 
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'dw_region_nonce' ) // Security Nonce
            ));
        }
    }
}
add_action( 'wp_enqueue_scripts', 'dw_load_region_scripts' );

// Optimasi: Defer Scripts
function dw_defer_scripts( $tag, $handle, $src ) {
    $defer_scripts = array( 'tema-dw-main', 'font-awesome', 'tema-dw-filter', 'dw-ajax-cart' );
    if ( in_array( $handle, $defer_scripts ) ) {
        return '<script src="' . $src . '" defer="defer"></script>' . "\n";
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'dw_defer_scripts', 10, 3 );

// Optimasi: Hapus Aset Bawaan Tak Terpakai
add_action( 'wp_enqueue_scripts', function() {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
}, 100 );

/**
 * ==============================================================================
 * 2. CPT & ROLES
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
 * 3. HELPER FUNCTIONS
 * ==============================================================================
 */

function dw_get_product_by_id( $id ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dw_produk';
    
    // Cek apakah tabel custom ada
    if( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
        return false;
    }

    $product = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d AND status = 'aktif'", $id ) );
    return $product;
}

function tema_dw_format_rupiah( $angka ) {
    return 'Rp ' . number_format( $angka, 0, ',', '.' );
}

function dw_get_merchant_id() {
    global $wpdb;
    $user_id = get_current_user_id();
    // Fallback: Admin bisa akses untuk debug
    if ( current_user_can( 'administrator' ) ) {
        // return 1; // Uncomment jika ingin hardcode ID toko untuk admin
    }
    return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}dw_pedagang WHERE id_user = %d", $user_id ) );
}

function dw_get_desa_id() {
    global $wpdb;
    $user_id = get_current_user_id();
    return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}dw_desa WHERE id_user_desa = %d", $user_id ) );
}

function tema_dw_login_redirect( $url, $request, $user ) {
    if ( $user && is_object( $user ) && is_a( $user, 'WP_User' ) ) {
        $roles = (array) $user->roles;
        
        if ( in_array( 'administrator', $roles ) ) return home_url( '/dashboard-desa' );
        if ( in_array( 'verifikator_desa', $roles ) ) return home_url( '/dashboard-desa' );
        if ( in_array( 'pedagang_toko', $roles ) ) return home_url( '/dashboard-toko' );
        if ( in_array( 'pengelola_ojek', $roles ) ) return home_url( '/dashboard-ojek' );
        
        return home_url( '/akun-saya' );
    }
    return $url;
}
add_filter( 'login_redirect', 'tema_dw_login_redirect', 10, 3 );

// Custom Avatar
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
    if ( ! current_user_can( 'edit_posts' ) && ! is_admin() ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'tema_dw_disable_admin_bar' );

function tema_dw_start_session() {
    if ( ! session_id() && ! headers_sent() ) {
        session_start();
    }
}
add_action( 'init', 'tema_dw_start_session' );

/**
 * ==============================================================================
 * 4. ROUTING & REWRITE RULES
 * ==============================================================================
 */

function tema_dw_rewrite_rules() {
    // Single Pages
    add_rewrite_rule( '^wisata/([^/]*)/?', 'index.php?dw_type=wisata&dw_slug=$matches[1]', 'top' );
    add_rewrite_rule( '^produk/([^/]*)/?', 'index.php?dw_type=produk&dw_slug=$matches[1]', 'top' );
    add_rewrite_rule( '^toko/([^/]*)/?', 'index.php?dw_type=profil_toko&dw_slug_toko=$matches[1]', 'top' );
    add_rewrite_rule( '^desa/([^/]*)/?', 'index.php?dw_type=profil_desa&dw_slug_desa=$matches[1]', 'top' );
    
    // Dashboard Routing
    add_rewrite_rule( '^dashboard/?$', 'index.php?dw_type=dashboard_router', 'top' );
    add_rewrite_rule( '^dashboard-desa/?$', 'index.php?dw_type=dashboard_desa', 'top' );
    add_rewrite_rule( '^dashboard-toko/?$', 'index.php?dw_type=dashboard_toko', 'top' );
    add_rewrite_rule( '^dashboard-ojek/?$', 'index.php?dw_type=dashboard_ojek', 'top' );
    
    // Halaman Lain
    add_rewrite_rule( '^akun-saya/?$', 'index.php?dw_type=akun_saya', 'top' );
    add_rewrite_rule( '^keranjang/?$', 'index.php?dw_type=cart', 'top' );
    add_rewrite_rule( '^checkout/?$', 'index.php?dw_type=checkout', 'top' );
    add_rewrite_rule( '^pembayaran/?$', 'index.php?dw_type=pembayaran', 'top' );
    
    // Auto Flush Rules (Hanya saat versi berubah)
    if ( get_option( 'tema_dw_rules_flushed_v24' ) !== 'yes' ) { 
        flush_rewrite_rules();
        update_option( 'tema_dw_rules_flushed_v24', 'yes' );
    }
}
add_action( 'init', 'tema_dw_rewrite_rules' );

function tema_dw_query_vars( $vars ) {
    $vars[] = 'dw_type';
    $vars[] = 'dw_slug';
    $vars[] = 'dw_slug_toko';
    $vars[] = 'dw_slug_desa';
    return $vars;
}
add_filter( 'query_vars', 'tema_dw_query_vars' );

function tema_dw_template_include( $template ) {
    $dw_type = get_query_var( 'dw_type' );
    
    // Routing Template
    if ( $dw_type == 'dashboard_router' ) return get_template_directory() . '/page-dashboard.php';
    if ( $dw_type == 'dashboard_desa' )   return get_template_directory() . '/page-dashboard-desa.php';
    if ( $dw_type == 'dashboard_toko' )   return get_template_directory() . '/page-dashboard-toko.php';
    if ( $dw_type == 'dashboard_ojek' )   return get_template_directory() . '/page-dashboard-ojek.php';

    if ( $dw_type == 'wisata' )      return get_template_directory() . '/single-dw_wisata.php';
    if ( $dw_type == 'produk' )      return get_template_directory() . '/single-dw_produk.php';
    if ( $dw_type == 'profil_toko' ) return get_template_directory() . '/page-profil-toko.php';
    if ( $dw_type == 'profil_desa' ) return get_template_directory() . '/page-profil-desa.php';
    if ( $dw_type == 'cart' )        return get_template_directory() . '/page-cart.php';
    if ( $dw_type == 'checkout' )    return get_template_directory() . '/page-checkout.php';
    if ( $dw_type == 'pembayaran' )  return get_template_directory() . '/page-pembayaran.php';
    if ( $dw_type == 'akun_saya' )   return get_template_directory() . '/page-akun-saya.php';
    
    return $template;
}
add_filter( 'template_include', 'tema_dw_template_include' );

/**
 * ==============================================================================
 * 5. AJAX HANDLERS
 * ==============================================================================
 */

/**
 * [BARU] AJAX Handler untuk Wilayah dengan TRANSIENT CACHING
 * Menyimpan hasil query database ke cache server
 */
function dw_ajax_get_wilayah_optimized() {
    // 1. Security Check (Nonce)
    check_ajax_referer('dw_region_nonce', 'nonce');

    // 2. Ambil parameter & Sanitasi
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $id   = isset($_POST['id']) ? sanitize_text_field($_POST['id']) : '';

    if (empty($type)) {
        wp_send_json_error(['message' => 'Tipe wilayah diperlukan']);
    }

    // 3. Cek Cache (Transient)
    // Format Key: dw_region_{tipe}_{parent_id}
    // Contoh: dw_region_kabupaten_33 (Untuk Kab di Prov ID 33)
    $cache_key = 'dw_region_' . $type . '_' . $id;
    $cached_data = get_transient($cache_key);

    if (false !== $cached_data) {
        // Jika ada di cache, kirim langsung (Hemat Database Query!)
        wp_send_json_success($cached_data);
    }

    // 4. Jika tidak ada di cache, Query Database
    global $wpdb;
    $results = [];
    $table_prov = $wpdb->prefix . 'dw_provinsi';
    $table_kab  = $wpdb->prefix . 'dw_kabupaten';
    $table_kec  = $wpdb->prefix . 'dw_kecamatan';
    $table_desa = $wpdb->prefix . 'dw_desa_kelurahan';

    // Sesuaikan nama tabel dengan database Anda yang sebenarnya
    // Pastikan tabel ini ada, jika tidak, ganti dengan logic 'dw_desa' CPT query
    
    if ($type === 'provinsi') {
        // Cek tabel provinsi exists?
        if($wpdb->get_var("SHOW TABLES LIKE '$table_prov'") == $table_prov) {
            $results = $wpdb->get_results("SELECT id, name FROM $table_prov ORDER BY name ASC");
        } 
    } elseif ($type === 'kabupaten' && !empty($id)) {
        if($wpdb->get_var("SHOW TABLES LIKE '$table_kab'") == $table_kab) {
            $results = $wpdb->get_results($wpdb->prepare("SELECT id, name FROM $table_kab WHERE province_id = %s ORDER BY name ASC", $id));
        }
    } elseif ($type === 'kecamatan' && !empty($id)) {
        if($wpdb->get_var("SHOW TABLES LIKE '$table_kec'") == $table_kec) {
            $results = $wpdb->get_results($wpdb->prepare("SELECT id, name FROM $table_kec WHERE regency_id = %s ORDER BY name ASC", $id));
        }
    } elseif ($type === 'desa' && !empty($id)) {
        if($wpdb->get_var("SHOW TABLES LIKE '$table_desa'") == $table_desa) {
            $results = $wpdb->get_results($wpdb->prepare("SELECT id, name FROM $table_desa WHERE district_id = %s ORDER BY name ASC", $id));
        }
    }

    // Fallback Dummy Data jika tabel belum siap (Untuk Testing)
    if (empty($results)) {
        if ($type == 'provinsi') $results = [['id'=>'33', 'name'=>'Jawa Tengah (Demo)'], ['id'=>'34', 'name'=>'DIY (Demo)']];
        if ($type == 'kabupaten') $results = [['id'=>'3374', 'name'=>'Semarang (Demo)'], ['id'=>'3324', 'name'=>'Kendal (Demo)']];
        if ($type == 'kecamatan') $results = [['id'=>'101', 'name'=>'Banyumanik (Demo)'], ['id'=>'102', 'name'=>'Tembalang (Demo)']];
        if ($type == 'desa') $results = [['id'=>'1001', 'name'=>'Desa Wisata A'], ['id'=>'1002', 'name'=>'Desa Wisata B']];
    }

    // 5. Simpan ke Cache selama 7 Hari (WEEK_IN_SECONDS)
    if (!empty($results)) {
        set_transient($cache_key, $results, WEEK_IN_SECONDS);
    }

    wp_send_json_success($results);
}
// Daftarkan Action AJAX Baru
add_action('wp_ajax_get_region_data', 'dw_ajax_get_wilayah_optimized');
add_action('wp_ajax_nopriv_get_region_data', 'dw_ajax_get_wilayah_optimized');


// --- CART HANDLER ---
add_action( 'wp_ajax_dw_add_to_cart', 'dw_handle_add_to_cart' );
add_action( 'wp_ajax_nopriv_dw_add_to_cart', 'dw_handle_add_to_cart' );

function dw_handle_add_to_cart() {
    global $wpdb;

    // 1. Validasi Nonce
    $nonce_valid = false;
    if ( isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'dw_cart_action' ) ) {
        $nonce_valid = true;
    } elseif ( isset( $_POST['dw_cart_nonce'] ) && wp_verify_nonce( $_POST['dw_cart_nonce'], 'dw_cart_action' ) ) {
        $nonce_valid = true;
    } elseif ( isset( $_POST['security'] ) && wp_verify_nonce( $_POST['security'], 'dw_cart_action' ) ) {
        $nonce_valid = true;
    }

    if ( ! $nonce_valid ) {
        wp_send_json_error( [ 'message' => 'Security check failed. Refresh halaman.' ] );
    }

    // 2. Setup ID & Qty
    $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
    $qty        = isset( $_POST['quantity'] ) ? intval( $_POST['quantity'] ) : ( isset( $_POST['qty'] ) ? intval( $_POST['qty'] ) : 1 );
    
    if ( $product_id === 0 ) wp_send_json_error( [ 'message' => 'ID Produk tidak valid' ] );
    if ( $qty < 1 ) $qty = 1;
    
    $user_id = get_current_user_id();
    $session_id = session_id() ?: ( $_COOKIE['PHPSESSID'] ?? '' );

    // 3. Cek Produk & Stok
    $produk = dw_get_product_by_id( $product_id );

    if ( ! $produk ) {
        wp_send_json_error( [ 'message' => 'Produk tidak ditemukan atau tidak aktif.' ] );
    }

    if ( $produk->stok < $qty ) {
        wp_send_json_error( [ 'message' => 'Stok produk tidak mencukupi. Sisa: ' . $produk->stok ] );
    }

    // 4. Insert/Update ke DB CART
    $table_cart = $wpdb->prefix . 'dw_cart';
    
    if ( $user_id > 0 ) {
        $where_sql = $wpdb->prepare( "user_id = %d AND id_produk = %d", $user_id, $product_id );
    } else {
        $where_sql = $wpdb->prepare( "session_id = %s AND id_produk = %d", $session_id, $product_id );
    }

    $existing = $wpdb->get_row( "SELECT id, qty FROM $table_cart WHERE $where_sql" );

    if ( $existing ) {
        $new_qty = $existing->qty + $qty;
        if ( $produk->stok < $new_qty ) {
            wp_send_json_error( [ 'message' => 'Total di keranjang melebihi stok tersedia.' ] );
        }
        $wpdb->update( $table_cart, [ 'qty' => $new_qty, 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $existing->id ] );
    } else {
        $data_insert = [
            'user_id'    => ( $user_id > 0 ) ? $user_id : null,
            'session_id' => $session_id,
            'id_produk'  => $product_id,
            'qty'        => $qty,
            'created_at' => current_time( 'mysql' )
        ];
        $wpdb->insert( $table_cart, $data_insert );
    }

    // 5. Total Item Badge
    if ( $user_id > 0 ) {
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(qty) FROM $table_cart WHERE user_id = %d", $user_id ) );
    } else {
        $count = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(qty) FROM $table_cart WHERE session_id = %s", $session_id ) );
    }

    wp_send_json_success([
        'message'    => 'Produk berhasil ditambahkan!', 
        'cart_count' => (int)$count
    ]);
}

add_action( 'wp_ajax_dw_update_cart_qty', 'dw_handle_update_cart_qty' );
add_action( 'wp_ajax_nopriv_dw_update_cart_qty', 'dw_handle_update_cart_qty' );

function dw_handle_update_cart_qty() {
    check_ajax_referer( 'dw_cart_action', 'nonce' );
    global $wpdb;
    
    $cart_id = intval( $_POST['cart_id'] );
    $qty     = intval( $_POST['qty'] );
    
    if ( $qty < 1 ) wp_send_json_error( [ 'message' => 'Minimal 1' ] );
    
    $table_cart = $wpdb->prefix . 'dw_cart';
    $wpdb->update( $table_cart, [ 'qty' => $qty ], [ 'id' => $cart_id ] );
    
    $user_id    = get_current_user_id();
    $session_id = session_id() ?: ( $_COOKIE['PHPSESSID'] ?? '' );
    
    $totals = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items FROM $table_cart c JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)", $user_id, $session_id ) );
    
    wp_send_json_success([ 
        'new_qty'         => $qty, 
        'grand_total_fmt' => tema_dw_format_rupiah( $totals->grand_total ?? 0 ), 
        'total_items'     => intval( $totals->total_items ?? 0 ) 
    ]);
}

add_action( 'wp_ajax_dw_remove_cart_item', 'dw_handle_remove_cart_item' );
add_action( 'wp_ajax_nopriv_dw_remove_cart_item', 'dw_handle_remove_cart_item' );

function dw_handle_remove_cart_item() {
    check_ajax_referer( 'dw_cart_action', 'nonce' );
    global $wpdb;
    
    $cart_id = intval( $_POST['cart_id'] );
    $wpdb->delete( $wpdb->prefix . 'dw_cart', [ 'id' => $cart_id ] );
    
    $user_id    = get_current_user_id();
    $session_id = session_id() ?: ( $_COOKIE['PHPSESSID'] ?? '' );
    
    $totals = $wpdb->get_row( $wpdb->prepare( "SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items FROM {$wpdb->prefix}dw_cart c JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)", $user_id, $session_id ) );
    
    wp_send_json_success([ 
        'grand_total_fmt' => tema_dw_format_rupiah( $totals->grand_total ?? 0 ), 
        'total_items'     => intval( $totals->total_items ?? 0 ) 
    ]);
}

// --- MERCHANT HANDLERS ---
add_action( 'wp_ajax_dw_merchant_stats', 'dw_ajax_merchant_stats' );
function dw_ajax_merchant_stats() {
    check_ajax_referer( 'dw_cart_action', 'security' );
    global $wpdb;
    
    $pid = dw_get_merchant_id();
    if ( ! $pid ) wp_send_json_error( [ 'message' => 'Toko tidak ditemukan' ] );
    
    // Mock Data (Silakan uncomment jika tabel sudah siap)
    // $sales = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_pesanan_toko) FROM {$wpdb->prefix}dw_transaksi_sub WHERE id_pedagang = %d AND status_pesanan IN ('selesai', 'dikirim_ekspedisi')", $pid));
    
    // Cek tabel produk
    $products_empty = 0;
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}dw_produk'" ) ) {
        $products_empty = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}dw_produk WHERE id_pedagang = %d AND stok <= 0", $pid ) );
    }
    
    wp_send_json_success( [ 'sales' => 0, 'orders' => 0, 'products_empty' => (int)$products_empty ] );
}

add_action( 'wp_ajax_dw_merchant_get_products', 'dw_ajax_merchant_get_products' );
function dw_ajax_merchant_get_products() {
    check_ajax_referer( 'dw_cart_action', 'security' );
    global $wpdb;
    $pid = dw_get_merchant_id();
    
    // Menggunakan Tabel Custom
    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}dw_produk'" ) == $wpdb->prefix . 'dw_produk' ) {
        $products = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}dw_produk WHERE id_pedagang = %d AND status != 'arsip' ORDER BY created_at DESC", $pid ) );
        wp_send_json_success( $products );
    } else {
        // Fallback CPT
        $args = array( 'post_type' => 'dw_produk', 'author' => get_current_user_id(), 'posts_per_page' => -1 );
        $posts = get_posts( $args );
        $data = [];
        foreach ( $posts as $p ) {
            $data[] = [
                'id' => $p->ID,
                'nama_produk' => $p->post_title,
                'harga' => get_post_meta( $p->ID, 'harga', true ),
                'stok' => get_post_meta( $p->ID, 'stok', true ),
                'foto_utama' => get_the_post_thumbnail_url( $p->ID )
            ];
        }
        wp_send_json_success( $data );
    }
}

// --- DESA HANDLERS (Basic Mockup) ---
add_action( 'wp_ajax_dw_desa_stats', 'dw_ajax_desa_stats' );
function dw_ajax_desa_stats() {
    check_ajax_referer( 'dw_cart_action', 'security' );
    wp_send_json_success( [ 'total_wisata' => 0, 'avg_rating' => 0 ] );
}

// --- OJEK HANDLER ---
add_action( 'wp_ajax_dw_ojek_ambil_order', 'dw_ajax_ojek_ambil_order_secure' );
function dw_ajax_ojek_ambil_order_secure() {
    check_ajax_referer( 'dw_ojek_action', 'security' );

    if ( ! current_user_can( 'dw_view_orders' ) && ! current_user_can( 'administrator' ) ) {
        wp_send_json_error( [ 'message' => 'Akses ditolak. Anda bukan Ojek resmi.' ] );
    }

    global $wpdb;
    $trx_id  = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
    $user_id = get_current_user_id();
    
    $table_trx = $wpdb->prefix . 'dw_transaksi';
    
    // Update status transaksi
    $updated = $wpdb->query( $wpdb->prepare( 
        "UPDATE $table_trx SET status_transaksi = 'menunggu_penjemputan', ojek_data = %s WHERE id = %d AND status_transaksi = 'menunggu_driver'", 
        json_encode( [ 'driver_id' => $user_id, 'timestamp' => time() ] ), 
        $trx_id 
    ));

    if ( $updated ) {
        wp_send_json_success( [ 'message' => 'Order berhasil diambil!' ] );
    } else {
        wp_send_json_error( [ 'message' => 'Order sudah diambil driver lain atau tidak valid.' ] );
    }
}

/**
 * ==============================================================================
 * 6. CHECK NEW ORDERS (REALTIME POLLING)
 * ==============================================================================
 */
add_action( 'wp_ajax_check_new_orders', 'dw_check_new_orders_handler' );
function dw_check_new_orders_handler() {
    global $wpdb;
    
    $pedagang_id   = intval( $_POST['pedagang_id'] );
    $last_order_id = intval( $_POST['last_order_id'] );
    
    if ( ! $pedagang_id ) wp_send_json_error( 'Invalid Pedagang ID' );

    $table_transaksi     = $wpdb->prefix . 'dw_transaksi';
    $table_transaksi_sub = $wpdb->prefix . 'dw_transaksi_sub';

    // Cek tabel ada
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_transaksi_sub'" ) != $table_transaksi_sub ) {
        wp_send_json_success( [ 'new_orders' => [], 'counts' => [], 'latest_id' => $last_order_id ] );
        return;
    }

    // 1. Ambil pesanan baru
    $new_orders = $wpdb->get_results( $wpdb->prepare( "
        SELECT sub.*, 
               t.kode_unik, t.bukti_pembayaran, t.status_transaksi as global_status, 
               t.nama_penerima, t.no_hp, t.alamat_lengkap AS alamat_kirim
        FROM $table_transaksi_sub sub
        JOIN $table_transaksi t ON sub.id_transaksi = t.id
        WHERE sub.id_pedagang = %d AND sub.id > %d
        ORDER BY sub.id DESC
    ", $pedagang_id, $last_order_id ) );

    // 2. Update Counter
    $all_orders = $wpdb->get_results( $wpdb->prepare( "
        SELECT sub.status_pesanan, t.status_transaksi as global_status
        FROM $table_transaksi_sub sub
        JOIN $table_transaksi t ON sub.id_transaksi = t.id
        WHERE sub.id_pedagang = %d
    ", $pedagang_id ) );

    $counts = [ 'all' => 0, 'belum_bayar' => 0, 'perlu_dikirim' => 0, 'dikirim' => 0, 'selesai' => 0, 'dibatalkan' => 0 ];

    foreach ( $all_orders as $o ) {
        $counts['all']++;
        $pay_status   = $o->global_status;
        $order_status = $o->status_pesanan;

        if ( $pay_status == 'menunggu_pembayaran' ) { 
            $counts['belum_bayar']++; 
        } elseif ( in_array( $order_status, [ 'dibatalkan', 'pembayaran_gagal' ] ) ) { 
            $counts['dibatalkan']++; 
        } elseif ( $order_status == 'selesai' ) { 
            $counts['selesai']++; 
        } elseif ( in_array( $order_status, [ 'dikirim_ekspedisi', 'diantar_ojek', 'dalam_perjalanan', 'siap_diambil' ] ) ) { 
            $counts['dikirim']++; 
        } elseif ( in_array( $order_status, [ 'menunggu_konfirmasi', 'diproses', 'menunggu_driver', 'penawaran_driver', 'nego', 'menunggu_penjemputan' ] ) ) { 
            $counts['perlu_dikirim']++; 
        }
    }

    wp_send_json_success( [
        'new_orders' => $new_orders,
        'counts'     => $counts,
        'latest_id'  => ! empty( $new_orders ) ? $new_orders[0]->id : $last_order_id
    ] );
}


/**
 * ==============================================================================
 * 7. PWA INTEGRATION (MANIFEST & SERVICE WORKER)
 * ==============================================================================
 */

// A. PWA Initialization & Tags in Head
function dw_add_pwa_tags() {
    if ( get_theme_mod( 'dw_pwa_enabled', '1' ) !== '1' ) return;
    
    $manifest_url = add_query_arg( 'dw-manifest', '1', home_url( '/' ) );
    $sw_url       = add_query_arg( 'dw-sw', '1', home_url( '/' ) );
    $theme_color  = get_theme_mod( 'dw_pwa_theme_color', '#16a34a' );
    $site_icon    = get_option( 'site_icon' );
    ?>
    <link rel="manifest" href="<?php echo esc_url( $manifest_url ); ?>">
    <meta name="theme-color" content="<?php echo esc_attr( $theme_color ); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( get_theme_mod( 'dw_pwa_short_name', get_bloginfo( 'name' ) ) ); ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <?php if ( $site_icon ) : 
        $icon_url = wp_get_attachment_image_url( $site_icon, 'full' );
    ?>
    <link rel="apple-touch-icon" href="<?php echo esc_url( $icon_url ); ?>">
    <link rel="apple-touch-startup-image" href="<?php echo esc_url( $icon_url ); ?>">
    <?php endif; ?>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('<?php echo esc_url( $sw_url ); ?>', { scope: '/' })
                .then(function(reg) { console.log('PWA Ready'); })
                .catch(function(err) { console.log('PWA Fail', err); });
            });
        }
    </script>
    <?php
}
// add_action( 'wp_head', 'dw_add_pwa_tags' );

// B. Generate Manifest JSON
// add_action( 'init', 'dw_pwa_generate_manifest' );
function dw_pwa_generate_manifest() {
    if ( isset( $_GET['dw-manifest'] ) ) {
        header( 'Content-Type: application/json; charset=utf-8' );
        
        $name = get_theme_mod( 'dw_pwa_name', get_bloginfo( 'name' ) );
        $theme_color = get_theme_mod( 'dw_pwa_theme_color', '#16a34a' );
        $site_icon_id = get_option( 'site_icon' );
        
        $icons = [];
        if ( $site_icon_id ) {
            foreach ( [ 192, 512 ] as $size ) {
                $icon_data = wp_get_attachment_image_src( $site_icon_id, [ $size, $size ] );
                if ( $icon_data ) {
                    $icons[] = [ 
                        "src" => $icon_data[0], 
                        "sizes" => "{$size}x{$size}", 
                        "type" => "image/png", 
                        "purpose" => "any maskable" 
                    ];
                }
            }
        }
        
        $manifest = [
            "name"             => $name,
            "short_name"       => get_theme_mod( 'dw_pwa_short_name', $name ),
            "description"      => get_bloginfo( 'description' ),
            "start_url"        => home_url( '/' ),
            "display"          => "standalone",
            "orientation"      => "portrait",
            "background_color" => get_theme_mod( 'dw_pwa_bg_color', '#ffffff' ),
            "theme_color"      => $theme_color,
            "icons"            => $icons
        ];
        
        echo json_encode( $manifest );
        exit;
    }
}

// C. Generate Service Worker (Updated with Modern Async/Await)
// add_action( 'init', 'dw_pwa_generate_sw' );
function dw_pwa_generate_sw() {
    if ( isset( $_GET['dw-sw'] ) ) {
        header( 'Content-Type: application/javascript; charset=utf-8' );
        header( 'Service-Worker-Allowed: /' );
        
        $cache_ver = 'dw-pwa-v' . time(); 
        $offline_url = home_url( '/' );
        
        // Aset Default
        $assets = [
            $offline_url,
            get_stylesheet_uri(),
            get_template_directory_uri() . '/assets/css/main.css',
            get_template_directory_uri() . '/assets/js/main.js',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
            'https://cdn.tailwindcss.com'
        ];
        
        // Output JS Service Worker
        ?>
        const CACHE_NAME = '<?php echo esc_js( $cache_ver ); ?>';
        const OFFLINE_URL = '<?php echo esc_url_raw( $offline_url ); ?>';
        const ASSETS_TO_CACHE = <?php echo json_encode( $assets ); ?>;

        // 1. Install Phase
        self.addEventListener('install', (event) => {
            self.skipWaiting();
            event.waitUntil(
                (async () => {
                    try {
                        const cache = await caches.open(CACHE_NAME);
                        return await cache.addAll(ASSETS_TO_CACHE);
                    } catch (error) {
                        console.error('PWA Install Error:', error);
                    }
                })()
            );
        });

        // 2. Activate Phase (Clean Old Caches)
        self.addEventListener('activate', (event) => {
            event.waitUntil(
                (async () => {
                    const keys = await caches.keys();
                    return Promise.all(
                        keys.map((key) => {
                            if (key !== CACHE_NAME) {
                                return caches.delete(key);
                            }
                        })
                    );
                })()
            );
            self.clients.claim();
        });

        // 3. Fetch Phase (Network First for Pages, Cache First for Assets)
        self.addEventListener('fetch', (event) => {
            if (event.request.mode === 'navigate') {
                event.respondWith(
                    (async () => {
                        try {
                            return await fetch(event.request);
                        } catch (error) {
                            const cache = await caches.open(CACHE_NAME);
                            return await cache.match(OFFLINE_URL);
                        }
                    })()
                );
                return;
            }

            event.respondWith(
                (async () => {
                    const cache = await caches.open(CACHE_NAME);
                    const cachedResponse = await cache.match(event.request);
                    if (cachedResponse) return cachedResponse;
                    
                    try {
                        const networkResponse = await fetch(event.request);
                        // Cache dynamic images/fonts
                        if (event.request.destination === 'image' || event.request.destination === 'font') {
                            cache.put(event.request, networkResponse.clone());
                        }
                        return networkResponse;
                    } catch (e) {
                        // Fail silently
                    }
                })()
            );
        });
        <?php
        exit;
    }
}

// Customizer for PWA
function dw_pwa_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'dw_pwa_section', array( 'title' => 'Pengaturan PWA', 'priority' => 160 ));
    
    $wp_customize->add_setting( 'dw_pwa_enabled', array( 'default' => '1' ) );
    $wp_customize->add_control( 'dw_pwa_enabled', array( 'label' => 'Aktifkan PWA', 'section' => 'dw_pwa_section', 'type' => 'checkbox' ));
    
    $wp_customize->add_setting( 'dw_pwa_name', array( 'default' => get_bloginfo( 'name' ) ) );
    $wp_customize->add_control( 'dw_pwa_name', array( 'label' => 'Nama Aplikasi', 'section' => 'dw_pwa_section', 'type' => 'text' ));
    
    $wp_customize->add_setting( 'dw_pwa_short_name', array( 'default' => get_bloginfo( 'name' ) ) );
    $wp_customize->add_control( 'dw_pwa_short_name', array( 'label' => 'Nama Pendek', 'section' => 'dw_pwa_section', 'type' => 'text' ));
    
    $wp_customize->add_setting( 'dw_pwa_theme_color', array( 'default' => '#16a34a' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'dw_pwa_theme_color', array( 'label' => 'Warna Tema', 'section' => 'dw_pwa_section' )));
    
    $wp_customize->add_setting( 'dw_pwa_bg_color', array( 'default' => '#ffffff' ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'dw_pwa_bg_color', array( 'label' => 'Warna Splash Screen', 'section' => 'dw_pwa_section' )));
}
add_action( 'customize_register', 'dw_pwa_customize_register' );


/**
 * ==============================================================================
 * 8. SYSTEM FIXES & REST API
 * ==============================================================================
 */

// 1. Batasi REST API
add_filter( 'rest_authentication_errors', function( $result ) {
    if ( ! empty( $result ) ) return $result;
    if ( ! is_user_logged_in() ) {
        return new WP_Error( 'rest_not_logged_in', 'REST API dibatasi.', array( 'status' => 401 ) );
    }
    return $result;
});

// 2. AJAX Handler untuk Simpan Produk (Secure)
add_action( 'wp_ajax_dw_save_produk_ajax', 'tema_dw_handle_save_produk_ajax' );
function tema_dw_handle_save_produk_ajax() {
    check_ajax_referer( 'dw_pedagang_nonce', 'security' );
    
    if ( ! current_user_can( 'dw_manage_pesanan' ) && ! current_user_can( 'administrator' ) ) {
        wp_send_json_error( [ 'message' => 'Akses ditolak.' ] );
    }

    // Sanitasi data
    $sanitized_post = [];
    foreach($_POST as $k => $v) {
        if(is_array($v)) $sanitized_post[$k] = array_map('sanitize_text_field', $v);
        else $sanitized_post[$k] = sanitize_text_field($v);
    }

    if ( function_exists( 'dw_save_produk_action' ) ) {
        $result = dw_save_produk_action( $sanitized_post, $_FILES );
        if ( $result ) wp_send_json_success( [ 'message' => 'Produk berhasil disimpan.' ] );
    }
    
    wp_send_json_error( [ 'message' => 'Gagal menyimpan / Fungsi tidak ditemukan.' ] );
}

// 3. AJAX Handler untuk Hapus Produk
add_action( 'wp_ajax_dw_delete_produk_ajax', 'tema_dw_handle_delete_produk_ajax' );
function tema_dw_handle_delete_produk_ajax() {
    check_ajax_referer( 'dw_pedagang_nonce', 'security' );
    
    $id = intval( $_POST['id'] );
    if ( ! $id ) wp_send_json_error( [ 'message' => 'ID tidak valid.' ] );

    if ( ! current_user_can( 'dw_manage_pesanan' ) && ! current_user_can( 'administrator' ) ) {
        wp_send_json_error( [ 'message' => 'Akses ditolak.' ] );
    }

    if ( function_exists( 'dw_delete_produk_action' ) ) {
        $result = dw_delete_produk_action( $id );
        if ( $result ) wp_send_json_success( [ 'message' => 'Produk berhasil dihapus.' ] );
    }

    wp_send_json_error( [ 'message' => 'Gagal menghapus / Fungsi tidak ditemukan.' ] );
}
?>