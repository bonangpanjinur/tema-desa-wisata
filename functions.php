<?php
/**
 * Functions and definitions for Tema Desa Wisata
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// 1. Setup Dasar Theme
function tema_dw_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'tema-dw' ),
        'footer'  => __( 'Footer Menu', 'tema-dw' ),
    ) );
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
    wp_enqueue_script('tailwindcss', 'https://cdn.tailwindcss.com', array(), '3.4.0', false);
    
    wp_add_inline_script('tailwindcss', "
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#16a34a',
                        secondary: '#15803d',
                        accent: '#ca8a04',
                        dark: '#1e293b',
                    }
                }
            }
        }
    ");

    wp_enqueue_style( 'tema-dw-style', get_stylesheet_uri() );
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
    
    // Versi script di-bump ke 1.0.2 untuk refresh cache browser
    wp_enqueue_script( 'tema-dw-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.2', true );

    wp_localize_script( 'tema-dw-main-js', 'dwData', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'api_url'  => get_rest_url( null, 'dw/v1/' ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
        'is_logged_in' => is_user_logged_in(),
        'home_url' => home_url('/'),
    ));
}
add_action( 'wp_enqueue_scripts', 'tema_dw_scripts' );

// 3. Helper: Cek Harga Produk
function tema_dw_get_product_price_html($post_id) {
    $harga = get_post_meta($post_id, '_price', true);
    if (!$harga) {
        return '<span class="text-sm text-gray-500">Cek Detail</span>';
    }
    return '<span class="text-primary font-bold">Rp ' . number_format($harga, 0, ',', '.') . '</span>';
}

// 4. Helper: Redirect Pedagang
function tema_dw_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'pedagang', $user->roles ) ) {
            return home_url( '/dashboard-toko/' );
        }
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'tema_dw_login_redirect', 10, 3 );

// 5. Hide Admin Bar
function tema_dw_disable_admin_bar() {
    if ( ! current_user_can( 'administrator' ) && ! is_admin() ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'tema_dw_disable_admin_bar' );

// 6. Handler AJAX Login
function tema_dw_ajax_login_handler() {
    check_ajax_referer( 'wp_rest', 'security' );

    $creds = array(
        'user_login'    => $_POST['username'],
        'user_password' => $_POST['password'],
        'remember'      => true
    );

    $user = wp_signon( $creds, false );

    if ( is_wp_error( $user ) ) {
        wp_send_json_error( array( 'message' => $user->get_error_message() ) );
    } else {
        wp_send_json_success( array( 
            'message' => 'Login berhasil',
            'redirect_url' => home_url('/dashboard-toko')
        ) );
    }
}
add_action( 'wp_ajax_tema_dw_ajax_login', 'tema_dw_ajax_login_handler' );
add_action( 'wp_ajax_nopriv_tema_dw_ajax_login', 'tema_dw_ajax_login_handler' );
// 7. Redirect Halaman Lost Password Bawaan WP
function tema_dw_custom_lost_password_url( $lostpassword_url, $redirect ) {
    return home_url( '/lupa-password/' ); // Pastikan slug halaman sesuai dengan yang Anda buat
}
add_filter( 'lostpassword_url', 'tema_dw_custom_lost_password_url', 10, 2 );

// 8. Redirect setelah Reset Password (Optional)
// Agar user tidak bingung setelah klik link di email
function tema_dw_login_url_redirect() {
    // Jika user mengakses wp-login.php?action=lostpassword secara manual
    if ( isset( $_GET['action'] ) && $_GET['action'] == 'lostpassword' && ! is_user_logged_in() ) {
        wp_redirect( home_url( '/lupa-password/' ) );
        exit;
    }
}
add_action( 'init', 'tema_dw_login_url_redirect' );