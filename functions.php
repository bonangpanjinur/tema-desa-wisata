<?php
/**
 * Functions and definitions
 * Tema Desa Wisata
 */

if ( ! function_exists( 'tema_desa_wisata_setup' ) ) :
	function tema_desa_wisata_setup() {
		load_theme_textdomain( 'tema-desa-wisata', get_template_directory() . '/languages' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		) );
	}
endif;
add_action( 'after_setup_theme', 'tema_desa_wisata_setup' );

/**
 * Enqueue scripts and styles.
 */
function tema_desa_wisata_scripts() {
	wp_enqueue_style( 'tema-desa-wisata-style', get_stylesheet_uri() );
	
	// Bootstrap 5 (CDN)
	wp_enqueue_style( 'bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css', array(), '5.3.0' );
	wp_enqueue_script( 'bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js', array(), '5.3.0', true );

	// Bootstrap Icons
	wp_enqueue_style( 'bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css' );

	// Custom CSS & JS
	wp_enqueue_style( 'main-css', get_template_directory_uri() . '/assets/css/main.css', array('bootstrap-css'), '1.0.0' );
	wp_enqueue_script( 'main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true );

	// AJAX untuk filter arsip & cart
	wp_localize_script( 'main-js', 'dw_ajax', array(
		'ajax_url' => admin_url( 'admin-ajax.php' ),
		'nonce'    => wp_create_nonce( 'dw_nonce' ) // Keamanan
	));

	// Script khusus halaman
	if ( is_post_type_archive( 'dw_produk' ) || is_tax( 'dw_kategori_produk' ) ) {
		wp_enqueue_script( 'archive-filter', get_template_directory_uri() . '/assets/js/archive-filter.js', array('jquery'), '1.0', true );
	}
    
    // Script Cart
    if ( is_page_template('page-cart.php') ) {
        wp_enqueue_script('ajax-cart', get_template_directory_uri() . '/assets/js/ajax-cart.js', array('jquery'), '1.0', true);
    }
    
    // Script Checkout
    if ( is_page_template('page-checkout.php') ) {
        wp_enqueue_script('dw-checkout', get_template_directory_uri() . '/assets/js/dw-checkout.js', array('jquery'), '1.0', true);
        wp_localize_script('dw-checkout', 'dw_checkout_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('dw_checkout_nonce')
        ));
    }

    // Script Dashboard Pedagang
    if ( is_page_template('page-dashboard-toko.php') ) {
        wp_enqueue_script('dw-pedagang', get_template_directory_uri() . '/assets/js/dw-pedagang.js', array('jquery'), '1.0', true);
        wp_localize_script('dw-pedagang', 'dw_pedagang', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('dw_pedagang_nonce')
        ));
    }

    // Script Dashboard Ojek
    if ( is_page_template('page-dashboard-ojek.php') ) {
        wp_enqueue_script('dw-ojek', get_template_directory_uri() . '/assets/js/dw-ojek.js', array('jquery'), '1.0', true);
        wp_localize_script('dw-ojek', 'dw_ojek', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('dw_ojek_nonce')
        ));
    }
}
add_action( 'wp_enqueue_scripts', 'tema_desa_wisata_scripts' );

/**
 * Register Custom Post Types: Produk, Wisata, Desa, Transaksi
 */
function dw_register_cpt() {
	// 1. Produk
	register_post_type( 'dw_produk', array(
		'labels' => array(
			'name'          => 'Produk',
			'singular_name' => 'Produk',
            'add_new'       => 'Tambah Produk',
            'add_new_item'  => 'Tambah Produk Baru',
            'edit_item'     => 'Edit Produk',
		),
		'public'      => true,
		'has_archive' => true,
		'menu_icon'   => 'dashicons-cart',
		'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
        // Slug bersih: sadesa.site/produk/nama-produk
		'rewrite'     => array( 'slug' => 'produk', 'with_front' => false ), 
	));

	// 2. Wisata
	register_post_type( 'dw_wisata', array(
		'labels' => array(
			'name'          => 'Wisata',
			'singular_name' => 'Wisata',
		),
		'public'      => true,
		'has_archive' => true,
		'menu_icon'   => 'dashicons-palmtree',
		'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
        // Slug bersih: sadesa.site/wisata/nama-wisata
		'rewrite'     => array( 'slug' => 'wisata', 'with_front' => false ),
	));

	// 3. Desa (Profil Desa)
	register_post_type( 'dw_desa', array(
		'labels' => array(
			'name'          => 'Desa',
			'singular_name' => 'Desa',
		),
		'public'      => true,
		'has_archive' => true,
		'menu_icon'   => 'dashicons-admin-home',
		'supports'    => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'rewrite'     => array( 'slug' => 'desa', 'with_front' => false ),
	));

    // 4. Transaksi (Private)
    register_post_type( 'dw_transaksi', array(
        'labels' => array(
            'name'          => 'Transaksi',
            'singular_name' => 'Transaksi',
        ),
        'public'      => false,  // Tidak bisa diakses publik lewat URL langsung
        'show_ui'     => true,   // Muncul di admin dashboard
        'has_archive' => false,
        'menu_icon'   => 'dashicons-money-alt',
        'supports'    => array( 'title', 'editor' ), // title=No Invoice
    ));
}
add_action( 'init', 'dw_register_cpt' );

/**
 * Register Taxonomies: Kategori Produk
 */
function dw_register_taxonomies() {
	register_taxonomy( 'dw_kategori_produk', 'dw_produk', array(
		'labels' => array(
			'name' => 'Kategori Produk',
		),
		'hierarchical' => true,
		'show_admin_column' => true,
		'rewrite' => array( 'slug' => 'kategori-produk' ),
	));
}
add_action( 'init', 'dw_register_taxonomies' );

/**
 * Custom Fields (Meta Boxes)
 */
function dw_add_meta_boxes() {
    // Meta Box Produk
    add_meta_box('dw_produk_meta', 'Data Produk', 'dw_produk_meta_callback', 'dw_produk', 'normal', 'high');
    // Meta Box Wisata
    add_meta_box('dw_wisata_meta', 'Data Wisata', 'dw_wisata_meta_callback', 'dw_wisata', 'normal', 'high');
    // Meta Box Transaksi
    add_meta_box('dw_transaksi_meta', 'Detail Transaksi', 'dw_transaksi_meta_callback', 'dw_transaksi', 'normal', 'high');
}
add_action('add_meta_boxes', 'dw_add_meta_boxes');

// Callback Produk
function dw_produk_meta_callback($post) {
    wp_nonce_field('dw_save_meta', 'dw_meta_nonce');
    $harga = get_post_meta($post->ID, 'harga_produk', true);
    $stok = get_post_meta($post->ID, 'stok_produk', true);
    $terjual = get_post_meta($post->ID, 'terjual', true);
    $lokasi = get_post_meta($post->ID, 'lokasi_desa', true);
    $id_penjual = get_post_meta($post->ID, 'id_penjual', true); 

    echo '<p><label>Harga (Rp):</label><br><input type="number" name="harga_produk" value="' . esc_attr($harga) . '" class="widefat"></p>';
    echo '<p><label>Stok:</label><br><input type="number" name="stok_produk" value="' . esc_attr($stok) . '" class="widefat"></p>';
    echo '<p><label>Terjual:</label><br><input type="number" name="terjual" value="' . esc_attr($terjual) . '" class="widefat"></p>';
    echo '<p><label>Lokasi / Desa:</label><br><input type="text" name="lokasi_desa" value="' . esc_attr($lokasi) . '" class="widefat"></p>';
    echo '<p><label>ID Penjual (User ID):</label><br><input type="number" name="id_penjual" value="' . esc_attr($id_penjual) . '" class="widefat"></p>';
}

// Callback Wisata
function dw_wisata_meta_callback($post) {
    wp_nonce_field('dw_save_meta', 'dw_meta_nonce');
    $harga = get_post_meta($post->ID, 'harga_tiket', true);
    $lokasi = get_post_meta($post->ID, 'lokasi_wisata', true);
    $rating = get_post_meta($post->ID, 'rating_wisata', true);

    echo '<p><label>Harga Tiket (Rp):</label><br><input type="number" name="harga_tiket" value="' . esc_attr($harga) . '" class="widefat"></p>';
    echo '<p><label>Lokasi:</label><br><input type="text" name="lokasi_wisata" value="' . esc_attr($lokasi) . '" class="widefat"></p>';
    echo '<p><label>Rating (1-5):</label><br><input type="number" step="0.1" max="5" name="rating_wisata" value="' . esc_attr($rating) . '" class="widefat"></p>';
}

// Callback Transaksi 
function dw_transaksi_meta_callback($post) {
    $total = get_post_meta($post->ID, 'total_transaksi', true);
    $status = get_post_meta($post->ID, 'status_transaksi', true); 
    $customer_id = get_post_meta($post->ID, 'customer_id', true);
    
    echo '<p><strong>Total:</strong> Rp ' . number_format((float)$total,0,',','.') . '</p>';
    echo '<p><strong>Status:</strong> ' . esc_html($status) . '</p>';
    echo '<p><strong>Customer ID:</strong> ' . esc_html($customer_id) . '</p>';
}

// Save Meta
function dw_save_meta($post_id) {
    if (!isset($_POST['dw_meta_nonce']) || !wp_verify_nonce($_POST['dw_meta_nonce'], 'dw_save_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    $fields = array(
        'harga_produk', 'stok_produk', 'terjual', 'lokasi_desa', 'id_penjual', // Produk
        'harga_tiket', 'lokasi_wisata', 'rating_wisata' // Wisata
    );

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }
    }
}
add_action('save_post', 'dw_save_meta');

/**
 * Handle Session Start
 */
function dw_start_session() {
    if(!session_id()) {
        session_start();
    }
}
add_action('init', 'dw_start_session', 1);

/**
 * Fungsi Helper: Format Rupiah
 */
function dw_format_rupiah($angka){
	return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Ajax Handler: Tambah ke Keranjang
 */
add_action('wp_ajax_dw_add_to_cart', 'dw_add_to_cart_handler');
add_action('wp_ajax_nopriv_dw_add_to_cart', 'dw_add_to_cart_handler');

function dw_add_to_cart_handler() {
    // Validasi nonce idealnya dilakukan di sini
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

    if(!$product_id) {
        wp_send_json_error(array('message' => 'Produk tidak valid'));
    }

    // Penyimpanan Keranjang Sederhana via Session
    if(!isset($_SESSION['dw_cart'])) {
        $_SESSION['dw_cart'] = array();
    }

    if(isset($_SESSION['dw_cart'][$product_id])) {
        $_SESSION['dw_cart'][$product_id] += $qty;
    } else {
        $_SESSION['dw_cart'][$product_id] = $qty;
    }

    $total_items = array_sum($_SESSION['dw_cart']);

    wp_send_json_success(array(
        'message' => 'Produk berhasil ditambahkan!',
        'total_items' => $total_items
    ));
}

/**
 * Inisialisasi Role User Tambahan
 */
function dw_add_roles() {
    add_role( 'desa', 'Admin Desa', array( 'read' => true, 'upload_files' => true ) );
    add_role( 'pedagang', 'Pedagang Toko', array( 'read' => true, 'upload_files' => true ) );
    add_role( 'ojek', 'Ojek Wisata', array( 'read' => true ) );
    add_role( 'wisatawan', 'Wisatawan', array( 'read' => true ) );
}
add_action( 'init', 'dw_add_roles' );

// Izinkan pedagang upload gambar
if ( current_user_can('pedagang') && !current_user_can('upload_files') ) {
    $role = get_role('pedagang');
    $role->add_cap('upload_files');
}

/**
 * Redirect Login berdasarkan Role
 */
function dw_login_redirect( $url, $request, $user ) {
    if ( $user && is_object( $user ) && is_a( $user, 'WP_User' ) ) {
        if ( $user->has_cap( 'administrator' ) ) {
            return admin_url();
        } elseif ( in_array( 'desa', (array) $user->roles ) ) {
            return home_url('/dashboard-desa/');
        } elseif ( in_array( 'pedagang', (array) $user->roles ) ) {
            return home_url('/dashboard-toko/');
        } elseif ( in_array( 'ojek', (array) $user->roles ) ) {
            return home_url('/dashboard-ojek/');
        } else {
            return home_url('/akun-saya/');
        }
    }
    return $url;
}
add_filter( 'login_redirect', 'dw_login_redirect', 10, 3 );

/**
 * Filter Arsip Produk (AJAX)
 */
add_action('wp_ajax_filter_products', 'dw_filter_products_handler');
add_action('wp_ajax_nopriv_filter_products', 'dw_filter_products_handler');

function dw_filter_products_handler() {
    // Placeholder untuk logika filter produk di masa mendatang
    // Saat ini implementasi filter mungkin ditangani langsung di file js atau page template
    wp_send_json_error(array('message' => 'Filter belum diimplementasikan sepenuhnya.'));
}

/**
 * Require File Dependensi Lain
 */
// Pastikan file ini ada di folder inc/
if ( file_exists( get_template_directory() . '/inc/dependency-check.php' ) ) {
    require get_template_directory() . '/inc/dependency-check.php';
}