<?php
/**
 * Functions and definitions
 * Menggunakan prefix 'tema_dw_' untuk menghindari konflik dengan plugin lain.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * 1. Theme Support & Setup
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
    add_theme_support('html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
    ));
}
add_action('after_setup_theme', 'tema_dw_setup');

/**
 * 2. Enqueue Scripts & Styles
 */
function tema_dw_scripts() {
    // Load Tailwind CSS via CDN (Untuk development cepat)
    wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.4.0', false);
    
    // Konfigurasi Tailwind Custom (Warna Identitas Desa)
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

    // Load Google Fonts (Inter)
    wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', array(), null);

    // Font Awesome Free (Icons)
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');

    // Style.css bawaan tema
    wp_enqueue_style('tema-dw-style', get_stylesheet_uri());
    
    // Custom CSS Tambahan
    wp_enqueue_style('tema-dw-main-css', get_template_directory_uri() . '/assets/css/main.css', array(), filemtime(get_template_directory() . '/assets/css/main.css'));

    // Main JavaScript (Logic UI & AJAX)
    wp_enqueue_script('tema-dw-main', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.2', true);

    // Localize Script (Mengirim variabel PHP ke JS)
    wp_localize_script('tema-dw-main', 'dw_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('dw_cart_nonce') // Nonce keamanan untuk cart
    ));

    // Script Filter (Hanya dimuat di halaman arsip)
    if (is_post_type_archive('dw_produk') || is_tax('kategori_produk') || is_post_type_archive('dw_wisata')) {
         wp_enqueue_script('tema-dw-filter', get_template_directory_uri() . '/assets/js/archive-filter.js', array('jquery'), '1.0.0', true);
    }
    
    // Script Checkout (Hanya di halaman Cart/Checkout)
    if (is_page_template('page-cart.php') || is_page_template('page-checkout.php')) {
        wp_enqueue_script('tema-dw-checkout', get_template_directory_uri() . '/assets/js/dw-checkout.js', array('jquery'), '1.0.0', true);
    }
}
add_action('wp_enqueue_scripts', 'tema_dw_scripts');

/**
 * 3. Helper Functions
 */

// Format Rupiah
function tema_dw_format_rupiah($angka) {
    if (function_exists('dw_format_rupiah')) return dw_format_rupiah($angka);
    return 'Rp ' . number_format($angka, 0, ',', '.');
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
 * 4. ROUTING & REWRITE RULES (Custom URL)
 * Menangani URL cantik: domain.com/wisata/slug dan domain.com/produk/slug
 */
function tema_dw_rewrite_rules() {
    // Rule Wisata
    add_rewrite_rule(
        '^wisata/([^/]*)/?',
        'index.php?dw_type=wisata&dw_slug=$matches[1]',
        'top'
    );
    
    // Rule Produk
    add_rewrite_rule(
        '^produk/([^/]*)/?',
        'index.php?dw_type=produk&dw_slug=$matches[1]',
        'top'
    );
    
    // Auto Flush Rules (Hanya sekali jalan jika belum ada flag)
    if (get_option('tema_dw_rules_flushed') !== 'yes') {
        flush_rewrite_rules();
        update_option('tema_dw_rules_flushed', 'yes');
    }
}
add_action('init', 'tema_dw_rewrite_rules');

// Reset opsi flush jika tema di-switch (agar refresh ulang saat aktivasi)
add_action('after_switch_theme', function() {
    delete_option('tema_dw_rules_flushed');
});

// Daftarkan Query Vars agar dibaca WordPress
function tema_dw_query_vars($vars) {
    $vars[] = 'dw_type';
    $vars[] = 'dw_slug';
    return $vars;
}
add_filter('query_vars', 'tema_dw_query_vars');

// Load Template File Berdasarkan Query Var
function tema_dw_template_include($template) {
    $dw_type = get_query_var('dw_type');
    
    if ($dw_type == 'wisata') {
        $file = get_template_directory() . '/single-dw_wisata.php';
        if (file_exists($file)) return $file;
    }
    
    if ($dw_type == 'produk') {
        $file = get_template_directory() . '/single-dw_produk.php';
        if (file_exists($file)) return $file;
    }
    
    return $template;
}
add_filter('template_include', 'tema_dw_template_include');

// Fitur Flush Manual via URL (domain.com/?update_rewrites=1)
function tema_dw_flush_rewrites() {
    if (isset($_GET['update_rewrites']) && $_GET['update_rewrites'] == '1') {
        flush_rewrite_rules();
        echo "Rewrite rules flushed! Silakan kembali ke home.";
        exit;
    }
}
add_action('init', 'tema_dw_flush_rewrites');

/**
 * 5. AJAX Handler: Add to Cart
 */
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
    
    // Cek Session/User
    $user_id = get_current_user_id();
    $session_id = session_id();
    if(empty($session_id)) $session_id = $_COOKIE['PHPSESSID'] ?? '';

    // Cek Item Existing
    if ($user_id > 0) {
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, qty FROM $table_name WHERE user_id = %d AND id_produk = %d AND id_variasi = %d",
            $user_id, $product_id, $variasi_id
        ));
    } else {
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, qty FROM $table_name WHERE session_id = %s AND id_produk = %d AND id_variasi = %d",
            $session_id, $product_id, $variasi_id
        ));
    }

    if ($existing) {
        // UPDATE: Tambah Qty
        $new_qty = $existing->qty + $qty;
        // Cek stok dulu di sini (Opsional, tapi bagus)
        // ... (Logic cek stok bisa ditambahkan)
        
        $wpdb->update($table_name, 
            ['qty' => $new_qty, 'updated_at' => current_time('mysql')], 
            ['id' => $existing->id]
        );
    } else {
        // INSERT: Item Baru
        $wpdb->insert($table_name, array(
            'user_id' => ($user_id > 0) ? $user_id : null,
            'session_id' => $session_id,
            'id_produk' => $product_id,
            'id_variasi' => $variasi_id,
            'qty' => $qty,
            'created_at' => current_time('mysql')
        ));
    }

    // Hitung Total Item untuk Badge Cart
    if ($user_id > 0) {
        $total_items = $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_name WHERE user_id = %d", $user_id));
    } else {
        $total_items = $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_name WHERE session_id = %s", $session_id));
    }

    wp_send_json_success([
        'message' => 'Berhasil ditambahkan', 
        'cart_count' => (int)$total_items
    ]);
}

/**
 * 6. AJAX Handler: Update Cart Qty (Tanpa Reload)
 */
add_action('wp_ajax_dw_update_cart_qty', 'dw_handle_update_cart_qty');
add_action('wp_ajax_nopriv_dw_update_cart_qty', 'dw_handle_update_cart_qty');

function dw_handle_update_cart_qty() {
    check_ajax_referer('dw_cart_nonce', 'security');

    global $wpdb;
    $cart_id = intval($_POST['cart_id']);
    $qty     = intval($_POST['qty']);
    
    $user_id = get_current_user_id();
    $session_id = session_id();
    if(empty($session_id)) $session_id = $_COOKIE['PHPSESSID'] ?? '';

    $table_cart = $wpdb->prefix . 'dw_cart';
    $table_produk = $wpdb->prefix . 'dw_produk';

    // 1. Ambil Data Item & Stok Produk
    $sql = $wpdb->prepare(
        "SELECT c.id, c.qty, p.stok, p.harga, p.nama_produk 
         FROM $table_cart c 
         JOIN $table_produk p ON c.id_produk = p.id
         WHERE c.id = %d AND (c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s))",
        $cart_id, $user_id, $session_id
    );
    
    $item = $wpdb->get_row($sql);

    if (!$item) {
        wp_send_json_error(['message' => 'Item tidak ditemukan']);
    }

    // 2. Validasi Stok
    if ($qty > $item->stok) {
        wp_send_json_error([
            'message' => 'Stok mentok! Sisa hanya ' . $item->stok, 
            'max_qty' => $item->stok,
            'current_qty' => $item->qty // Kembalikan ke qty sebelumnya
        ]);
    }
    
    if ($qty < 1) {
        wp_send_json_error(['message' => 'Jumlah minimal 1']);
    }

    // 3. Update Database
    $wpdb->update($table_cart, ['qty' => $qty, 'updated_at' => current_time('mysql')], ['id' => $cart_id]);

    // 4. Hitung Ulang Total Belanja (Grand Total)
    $sql_total = $wpdb->prepare(
        "SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items
         FROM $table_cart c
         JOIN $table_produk p ON c.id_produk = p.id
         WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)",
        $user_id, $session_id
    );
    $totals = $wpdb->get_row($sql_total);

    wp_send_json_success([
        'message' => 'Keranjang diperbarui',
        'cart_id' => $cart_id,
        'new_qty' => $qty,
        // Format rupiah dikirim dari server agar konsisten
        'grand_total_fmt' => tema_dw_format_rupiah($totals->grand_total ?? 0),
        'total_items' => intval($totals->total_items ?? 0)
    ]);
}

/**
 * 7. AJAX Handler: Remove Cart Item (Tanpa Reload)
 */
add_action('wp_ajax_dw_remove_cart_item', 'dw_handle_remove_cart_item');
add_action('wp_ajax_nopriv_dw_remove_cart_item', 'dw_handle_remove_cart_item');

function dw_handle_remove_cart_item() {
    check_ajax_referer('dw_cart_nonce', 'security');
    
    global $wpdb;
    $cart_id = intval($_POST['cart_id']);
    $user_id = get_current_user_id();
    $session_id = session_id();
    if(empty($session_id)) $session_id = $_COOKIE['PHPSESSID'] ?? '';

    $table_cart = $wpdb->prefix . 'dw_cart';

    // Hapus data
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_cart WHERE id = %d AND (user_id = %d OR (user_id IS NULL AND session_id = %s))",
        $cart_id, $user_id, $session_id
    ));

    if ($deleted) {
        // Hitung ulang total setelah hapus
        $table_produk = $wpdb->prefix . 'dw_produk';
        $sql_total = $wpdb->prepare(
            "SELECT SUM(c.qty * p.harga) as grand_total, SUM(c.qty) as total_items
             FROM $table_cart c
             JOIN $table_produk p ON c.id_produk = p.id
             WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)",
            $user_id, $session_id
        );
        $totals = $wpdb->get_row($sql_total);

        wp_send_json_success([
            'message' => 'Item dihapus',
            'grand_total_fmt' => tema_dw_format_rupiah($totals->grand_total ?? 0),
            'total_items' => intval($totals->total_items ?? 0)
        ]);
    } else {
        wp_send_json_error(['message' => 'Gagal menghapus item']);
    }
}
?>