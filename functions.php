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
    wp_enqueue_script( 'tema-dw-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '1.0.1', true );

    wp_localize_script( 'tema-dw-main-js', 'dwData', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'api_url'  => get_rest_url( null, 'dw/v1/' ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
        'is_logged_in' => is_user_logged_in(),
        'home_url' => home_url('/'),
    ));
}
add_action( 'wp_enqueue_scripts', 'tema_dw_scripts' );

// 3. Helper: Cek Harga Produk (NAMA FUNGSI DIGANTI)
function tema_dw_get_product_price_html($post_id) {
    $harga = get_post_meta($post_id, '_price', true);
    if (!$harga) {
        return '<span class="text-sm text-gray-500">Cek Detail</span>';
    }
    return '<span class="text-primary font-bold">Rp ' . number_format($harga, 0, ',', '.') . '</span>';
}

// 4. Helper: Redirect Pedagang (NAMA FUNGSI DIGANTI)
function tema_dw_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        if ( in_array( 'pedagang', $user->roles ) ) {
            return home_url( '/dashboard-toko/' );
        }
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'tema_dw_login_redirect', 10, 3 );

// 5. Hide Admin Bar (NAMA FUNGSI DIGANTI)
function tema_dw_disable_admin_bar() {
    if ( ! current_user_can( 'administrator' ) && ! is_admin() ) {
        show_admin_bar( false );
    }
}
add_action( 'after_setup_theme', 'tema_dw_disable_admin_bar' );

// 6. Handler AJAX Login (NAMA FUNGSI & ACTION DIGANTI)
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
// Menggunakan nama action baru 'tema_dw_ajax_login' agar tidak bentrok dengan plugin
add_action( 'wp_ajax_tema_dw_ajax_login', 'tema_dw_ajax_login_handler' );
add_action( 'wp_ajax_nopriv_tema_dw_ajax_login', 'tema_dw_ajax_login_handler' );