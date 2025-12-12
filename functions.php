<?php
/**
 * Functions and definitions for Tema Desa Wisata
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// 1. Setup Dasar Theme
function tema_dw_setup() {
    // Menambahkan dukungan Title Tag
    add_theme_support( 'title-tag' );

    // Menambahkan dukungan Featured Image (Thumbnail)
    add_theme_support( 'post-thumbnails' );

    // Mendaftarkan Menu Navigasi
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'tema-dw' ),
        'footer'  => __( 'Footer Menu', 'tema-dw' ),
    ) );

    // Menambahkan dukungan Logo
    add_theme_support( 'custom-logo', array(
        'height'      => 50,
        'width'       => 150,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
}
add_action( 'after_setup_theme', 'tema_dw_setup' );

// 2. Enqueue Scripts & Styles
function tema_dw_scripts() {
    // Load Tailwind CSS via CDN (Untuk kemudahan development & hasil instan)
    // Di production sebaiknya di-compile, tapi untuk fase ini CDN sangat efisien.
    wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.4.0', false);
    
    // Konfigurasi Tailwind Custom Config (agar warna sesuai brand Desa Wisata)
    wp_add_inline_script('tailwindcss', "
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#16a34a', // Green 600
                        secondary: '#15803d', // Green 700
                        accent: '#ca8a04', // Yellow 600
                        dark: '#1e293b',
                    }
                }
            }
        }
    ");

    // Main Style (style.css bawaan)
    wp_enqueue_style( 'tema-dw-style', get_stylesheet_uri() );

    // Font Awesome untuk Icon
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

    // Main JS
    wp_enqueue_script( 'tema-dw-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.0', true );

    // Localize Script untuk mengirim data PHP ke JS (Penting untuk AJAX/API)
    wp_localize_script( 'tema-dw-main-js', 'dwData', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'api_url'  => get_rest_url( null, 'dw/v1/' ),
        'nonce'    => wp_create_nonce( 'wp_rest' ), // Nonce untuk keamanan WP REST API
        'is_logged_in' => is_user_logged_in(),
        'home_url' => home_url('/'),
    ));
}
add_action( 'wp_enqueue_scripts', 'tema_dw_scripts' );

// 3. Helper: Cek Harga Produk (Integrasi dengan Plugin)
function dw_get_product_price_html($post_id) {
    // Coba ambil dari meta data plugin
    $harga = get_post_meta($post_id, '_price', true);
    if (!$harga) {
        // Fallback jika menggunakan sistem variasi
        // Logika ini harus disesuaikan dengan cara plugin menyimpan harga
        return '<span class="text-sm text-gray-500">Cek Detail</span>';
    }
    return '<span class="text-primary font-bold">Rp ' . number_format($harga, 0, ',', '.') . '</span>';
}

// 4. Helper: Redirect Pedagang ke Dashboard setelah Login
function dw_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'pedagang', $user->roles ) ) {
            // Asumsi slug page dashboard toko adalah 'dashboard-toko'
            return home_url( '/dashboard-toko/' );
        }
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'dw_login_redirect', 10, 3 );

// 5. Hide Admin Bar untuk User non-admin
function dw_disable_admin_bar() {
    if ( ! current_user_can( 'administrator' ) && ! is_admin() ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'dw_disable_admin_bar' );