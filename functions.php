<?php
/**
 * Functions and definitions
 */

if ( ! function_exists( 'dw_setup' ) ) :
	function dw_setup() {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
        add_theme_support( 'woocommerce' );
	}
endif;
add_action( 'after_setup_theme', 'dw_setup' );

function dw_scripts() {
	wp_enqueue_style( 'dw-style', get_stylesheet_uri() );
	wp_enqueue_style( 'dw-main-style', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.3' );
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );
    
    // Tailwind (CDN untuk development)
    wp_enqueue_script( 'tailwind', 'https://cdn.tailwindcss.com', array(), '3.3.0', false );

	wp_enqueue_script( 'dw-main-script', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true );

    // Lokalisasi script untuk AJAX
    wp_localize_script('dw-main-script', 'dw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_nonce') 
    ));
}
add_action( 'wp_enqueue_scripts', 'dw_scripts' );

// Registrasi Query Var
function dw_query_vars( $vars ) {
    $vars[] = 'dw_slug';      // Untuk Wisata
    $vars[] = 'dw_slug_toko'; // Untuk Toko
    $vars[] = 'dw_slug_desa'; // Untuk Desa
    return $vars;
}
add_filter( 'query_vars', 'dw_query_vars' );

// Rewrite Rules Custom
function dw_rewrite_rules() {
    // 1. Profil Desa: sadesa.site/@nama-desa
    add_rewrite_rule(
        '^@([^/]*)/?',
        'index.php?pagename=profil-desa&dw_slug_desa=$matches[1]',
        'top'
    );

    // 2. Detail Wisata: sadesa.site/wisata/detail/slug-wisata
    add_rewrite_rule(
        '^wisata/detail/([^/]*)/?',
        'index.php?pagename=detail-wisata&dw_slug=$matches[1]',
        'top'
    );

    // 3. Profil Toko: sadesa.site/toko/slug-toko
    add_rewrite_rule(
        '^toko/([^/]*)/?',
        'index.php?pagename=profil-toko&dw_slug_toko=$matches[1]',
        'top'
    );
}
add_action( 'init', 'dw_rewrite_rules' );


/**
 * ============================================================================
 * FUNGSI DATABASE & INSTALASI (SETUP TABEL)
 * ============================================================================
 */
function dw_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $table_prefix = $wpdb->prefix . 'dw_'; // Prefix khusus tema: wp_dw_

    // 1. Tabel Desa
    $sql_desa = "CREATE TABLE {$table_prefix}desa (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        id_user_desa BIGINT(20) UNSIGNED NOT NULL,
        nama_desa VARCHAR(255) NOT NULL,
        slug_desa VARCHAR(255) NOT NULL,
        deskripsi TEXT,
        foto VARCHAR(255) DEFAULT NULL,
        persentase_komisi_penjualan DECIMAL(5,2) DEFAULT 0,
        no_rekening_desa VARCHAR(50) DEFAULT NULL,
        nama_bank_desa VARCHAR(100) DEFAULT NULL,
        atas_nama_rekening_desa VARCHAR(100) DEFAULT NULL,
        qris_image_url_desa VARCHAR(255) DEFAULT NULL,
        website_desa VARCHAR(255) DEFAULT NULL,
        status ENUM('aktif','pending') DEFAULT 'pending',
        provinsi VARCHAR(100),
        kabupaten VARCHAR(100),
        kecamatan VARCHAR(100),
        kelurahan VARCHAR(100),
        api_provinsi_id VARCHAR(20),
        api_kabupaten_id VARCHAR(20),
        api_kecamatan_id VARCHAR(20),
        api_kelurahan_id VARCHAR(20),
        alamat_lengkap TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY id_user_desa (id_user_desa),
        KEY slug_desa (slug_desa),
        KEY idx_lokasi (api_kabupaten_id)
    ) $charset_collate;";
    dbDelta( $sql_desa );

    // 2. Tabel Wisata
    $sql_wisata = "CREATE TABLE {$table_prefix}wisata (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        id_desa BIGINT(20) NOT NULL,
        nama_wisata VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        deskripsi TEXT,
        harga_tiket DECIMAL(15,2) DEFAULT 0,
        jam_buka VARCHAR(100),
        foto_utama VARCHAR(255),
        foto_galeri TEXT, 
        rating_avg DECIMAL(3,2) DEFAULT 0,
        status ENUM('aktif','nonaktif') DEFAULT 'aktif',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY id_desa (id_desa),
        KEY slug (slug)
    ) $charset_collate;";
    dbDelta( $sql_wisata );

    // 3. Tabel Pedagang (UMKM/Toko)
    $sql_pedagang = "CREATE TABLE {$table_prefix}pedagang (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        id_user_pedagang BIGINT(20) UNSIGNED NOT NULL,
        id_desa BIGINT(20) DEFAULT NULL, 
        nama_toko VARCHAR(255) NOT NULL,
        slug_toko VARCHAR(255) NOT NULL,
        nama_pemilik VARCHAR(255),
        deskripsi_toko TEXT,
        foto_profil VARCHAR(255),
        no_hp_wa VARCHAR(20),
        alamat_toko TEXT,
        status_akun ENUM('aktif','pending','suspend') DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY id_user_pedagang (id_user_pedagang),
        KEY id_desa (id_desa),
        KEY slug_toko (slug_toko)
    ) $charset_collate;";
    dbDelta( $sql_pedagang );

     // 4. Tabel Produk
     $sql_produk = "CREATE TABLE {$table_prefix}produk (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        id_pedagang BIGINT(20) NOT NULL,
        nama_produk VARCHAR(255) NOT NULL,
        slug_produk VARCHAR(255) NOT NULL,
        deskripsi_produk TEXT,
        harga DECIMAL(15,2) NOT NULL,
        stok INT DEFAULT 0,
        foto_produk VARCHAR(255),
        kategori VARCHAR(100),
        terjual INT DEFAULT 0,
        status ENUM('aktif','habis','nonaktif') DEFAULT 'aktif',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY id_pedagang (id_pedagang),
        KEY slug_produk (slug_produk)
    ) $charset_collate;";
    dbDelta( $sql_produk );

    // 5. Tabel Transaksi
    $sql_transaksi = "CREATE TABLE {$table_prefix}transaksi (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        kode_transaksi VARCHAR(50) NOT NULL UNIQUE,
        id_user_pembeli BIGINT(20) UNSIGNED NOT NULL,
        total_belanja DECIMAL(15,2) NOT NULL,
        status_pembayaran ENUM('pending','success','failed','expired') DEFAULT 'pending',
        snap_token VARCHAR(255),
        payment_url TEXT,
        tanggal_transaksi DATETIME DEFAULT CURRENT_TIMESTAMP,
        metode_pembayaran VARCHAR(50),
        detail_pengiriman TEXT, 
        PRIMARY KEY  (id),
        KEY id_user_pembeli (id_user_pembeli)
    ) $charset_collate;";
    dbDelta( $sql_transaksi );

    // 6. Tabel Detail Transaksi (Item Belanja)
    $sql_detail_transaksi = "CREATE TABLE {$table_prefix}detail_transaksi (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        id_transaksi BIGINT(20) NOT NULL,
        id_produk BIGINT(20) DEFAULT NULL, 
        id_wisata BIGINT(20) DEFAULT NULL, 
        jenis_item ENUM('produk','tiket') NOT NULL,
        nama_item VARCHAR(255) NOT NULL,
        harga_satuan DECIMAL(15,2) NOT NULL,
        qty INT NOT NULL,
        subtotal DECIMAL(15,2) NOT NULL,
        catatan_pembeli TEXT,
        status_pesanan ENUM('menunggu_pembayaran','diproses','dikirim','selesai','dibatalkan') DEFAULT 'menunggu_pembayaran',
        no_resi VARCHAR(100),
        PRIMARY KEY  (id),
        KEY id_transaksi (id_transaksi)
    ) $charset_collate;";
    dbDelta( $sql_detail_transaksi );
    
    // 7. Tabel Keranjang (Cart)
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

// Jalankan pembuatan tabel saat tema diaktifkan
add_action('after_switch_theme', 'dw_create_tables');

/**
 * ============================================================================
 * FUNGSI AJAX HANDLERS
 * ============================================================================
 */

// 1. Handler Login
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
        $redirect_url = home_url('/akun-saya');
        if (in_array('administrator', (array) $user->roles)) {
            $redirect_url = admin_url();
        } 
        wp_send_json_success(array('redirect_url' => $redirect_url));
    }
}
add_action('wp_ajax_dw_login', 'dw_ajax_login_handler');
add_action('wp_ajax_nopriv_dw_login', 'dw_ajax_login_handler');

// 2. Handler Register
function dw_ajax_register_handler() {
    check_ajax_referer('dw_nonce', 'security');

    $username = sanitize_user($_POST['username']);
    $email    = sanitize_email($_POST['email']);
    $password = $_POST['password'];
    $role     = sanitize_text_field($_POST['role']); 

    if (username_exists($username) || email_exists($email)) {
        wp_send_json_error(array('message' => 'Username atau Email sudah terdaftar.'));
    }

    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error(array('message' => $user_id->get_error_message()));
    } else {
        $user = new WP_User($user_id);
        $user->set_role('subscriber'); 
        
        update_user_meta($user_id, 'dw_role', $role);

        global $wpdb;
        if ($role === 'pengelola_desa') {
            $wpdb->insert($wpdb->prefix . 'dw_desa', array(
                'id_user_desa' => $user_id,
                'nama_desa'    => 'Desa Baru (Pending)',
                'slug_desa'    => 'desa-baru-' . $user_id, 
                'status'       => 'pending'
            ));
        } elseif ($role === 'pedagang') {
            $wpdb->insert($wpdb->prefix . 'dw_pedagang', array(
                'id_user_pedagang' => $user_id,
                'nama_toko'        => 'Toko Baru',
                'slug_toko'        => 'toko-baru-' . $user_id,
                'status_akun'      => 'pending'
            ));
        }

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);

        wp_send_json_success(array('redirect_url' => home_url('/akun-saya')));
    }
}
add_action('wp_ajax_dw_register', 'dw_ajax_register_handler');
add_action('wp_ajax_nopriv_dw_register', 'dw_ajax_register_handler');

// 3. Handler Add to Cart
function dw_ajax_add_to_cart_handler() {
    check_ajax_referer('dw_nonce', 'security');

    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Silakan login terlebih dahulu.'));
    }

    global $wpdb;
    $table_keranjang = $wpdb->prefix . 'dw_keranjang';
    $user_id = get_current_user_id();
    
    $item_id = intval($_POST['item_id']);
    $jenis   = sanitize_text_field($_POST['jenis']); // 'produk' atau 'tiket'
    $qty     = intval($_POST['qty']);

    $where = array('id_user' => $user_id, 'jenis_item' => $jenis);
    if ($jenis === 'produk') {
        $where['id_produk'] = $item_id;
    } else {
        $where['id_wisata'] = $item_id;
    }

    $query_cek = "SELECT id, qty FROM $table_keranjang WHERE id_user = $user_id AND jenis_item = '$jenis' AND " . ($jenis == 'produk' ? "id_produk = $item_id" : "id_wisata = $item_id");
    $existing = $wpdb->get_row($query_cek);

    if ($existing) {
        $new_qty = $existing->qty + $qty;
        $wpdb->update($table_keranjang, array('qty' => $new_qty), array('id' => $existing->id));
    } else {
        $data_insert = array(
            'id_user' => $user_id,
            'jenis_item' => $jenis,
            'qty' => $qty
        );
        if ($jenis === 'produk') $data_insert['id_produk'] = $item_id;
        else $data_insert['id_wisata'] = $item_id;

        $wpdb->insert($table_keranjang, $data_insert);
    }

    $count = $wpdb->get_var("SELECT SUM(qty) FROM $table_keranjang WHERE id_user = $user_id");

    wp_send_json_success(array('message' => 'Berhasil ditambahkan ke keranjang', 'cart_count' => $count));
}
add_action('wp_ajax_dw_add_to_cart', 'dw_ajax_add_to_cart_handler');

/**
 * ============================================================================
 * FUNGSI HELPER
 * ============================================================================
 */

// Format Rupiah (Wrapped to check if already defined by plugin)
if ( ! function_exists( 'dw_format_rupiah' ) ) {
    function dw_format_rupiah($angka) {
        return 'Rp ' . number_format($angka, 0, ',', '.');
    }
}

// Get Cart Count Global
if ( ! function_exists( 'dw_get_cart_count' ) ) {
    function dw_get_cart_count() {
        if (!is_user_logged_in()) return 0;
        global $wpdb;
        $user_id = get_current_user_id();
        $count = $wpdb->get_var("SELECT SUM(qty) FROM {$wpdb->prefix}dw_keranjang WHERE id_user = $user_id");
        return $count ? $count : 0;
    }
}
?>