<?php
/**
 * Functions and definitions for Tema Desa Wisata
 *
 * @package TemaDesaWisata
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Setup Dasar Tema
function dw_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
    
    // Register Menu
    register_nav_menus( array(
        'primary' => __( 'Primary Menu (Desktop)', 'tema-desa-wisata' ),
        'mobile'  => __( 'Mobile Menu', 'tema-desa-wisata' ),
    ) );
}
add_action( 'after_setup_theme', 'dw_theme_setup' );

// 2. Enqueue Scripts & Styles
function dw_theme_scripts() {
    // Main CSS
    wp_enqueue_style( 'dw-main-style', get_template_directory_uri() . '/assets/css/main.css', array(), '1.0.0' );
    // FontAwesome (Untuk Icon)
    wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' );
    // Main JS
    wp_enqueue_script( 'dw-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true );
    
    // Localize Script untuk AJAX
    wp_localize_script( 'dw-main-js', 'dw_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'dw_ajax_nonce' )
    ));
}
add_action( 'wp_enqueue_scripts', 'dw_theme_scripts' );

// 3. Helper: Mengambil Banner dari Plugin
function dw_theme_get_banners() {
    global $wpdb;
    // Pastikan tabel ada (nama tabel dari plugin core)
    $table_name = $wpdb->prefix . 'dw_banner';
    
    // Cek apakah tabel exists untuk menghindari error jika plugin mati
    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) {
        return [];
    }

    $results = $wpdb->get_results( "SELECT * FROM $table_name WHERE status = 'aktif' ORDER BY prioritas ASC" );
    return $results;
}

// 4. Helper: Cek apakah user sedang di mobile (Sederhana)
function dw_is_mobile() {
    return wp_is_mobile();
}

// 5. Shortcode Tombol Hubungi Admin (Untuk Register Desa)
function dw_contact_admin_button() {
    // Ganti nomor WA dengan nomor admin sebenarnya
    $phone = '6281234567890'; 
    $text = urlencode('Halo Admin, saya ingin mendaftarkan Desa Wisata saya di platform ini.');
    $url = "https://wa.me/{$phone}?text={$text}";
    
    return '<a href="' . esc_url($url) . '" class="btn-contact-admin" target="_blank"><i class="fab fa-whatsapp"></i> Hubungi Admin untuk Pendaftaran Desa</a>';
}
add_shortcode('dw_contact_admin', 'dw_contact_admin_button');

// 6. Menambahkan Class pada Body untuk styling spesifik
function dw_body_classes( $classes ) {
    if ( dw_is_mobile() ) {
        $classes[] = 'is-mobile-device';
    } else {
        $classes[] = 'is-desktop-device';
    }
    return $classes;
}
add_filter( 'body_class', 'dw_body_classes' );
?>