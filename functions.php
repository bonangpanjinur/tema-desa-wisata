<?php
/**
 * Functions and definitions for Desa Wisata API Theme
 * Version: 2.1.0 (With Debugger)
 */

// 1. Konfigurasi API
function dw_api_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'dw_api_settings', array(
        'title'    => __( 'Konfigurasi API Desa Wisata', 'dw-api-theme' ),
        'priority' => 30,
    ) );

    $wp_customize->add_setting( 'dw_api_base_url', array(
        'default'   => 'https://admin.bonang.my.id', // Pastikan tanpa trailing slash
        'transport' => 'refresh',
    ) );

    $wp_customize->add_control( 'dw_api_base_url', array(
        'label'    => __( 'URL Website Server (Core)', 'dw-api-theme' ),
        'description' => 'Masukkan URL utama website server (contoh: https://admin.bonang.my.id)',
        'section'  => 'dw_api_settings',
        'type'     => 'url',
    ) );
}
add_action( 'customize_register', 'dw_api_customize_register' );

/**
 * 2. Fungsi Fetching Data Utama (Updated)
 */
function dw_fetch_api_data( $endpoint, $cache_time = 300 ) { // Cache dikurangi jadi 5 menit utk dev
    // Ambil Base URL
    $base_url = get_theme_mod( 'dw_api_base_url', 'https://admin.bonang.my.id' );
    $base_url = untrailingslashit( $base_url );

    // Bersihkan endpoint agar tidak double slash
    $endpoint = '/' . ltrim( $endpoint, '/' );
    
    // Susun URL Lengkap
    $full_url = $base_url . $endpoint;

    // Cek apakah URL valid
    if ( filter_var( $full_url, FILTER_VALIDATE_URL ) === false ) {
        return array( 'error' => true, 'message' => 'URL API tidak valid: ' . $full_url );
    }

    // Cache Check (Skip jika user admin agar data selalu fresh)
    $cache_key = 'dw_api_' . md5( $full_url );
    if ( ! current_user_can( 'administrator' ) ) {
        $cached_data = get_transient( $cache_key );
        if ( false !== $cached_data ) return $cached_data;
    }

    // Request ke Server
    $response = wp_remote_get( $full_url, array(
        'timeout'   => 20, 
        'sslverify' => false, // Penting untuk dev environment/self-signed SSL
        'headers'   => array( 
            'Accept' => 'application/json',
            'User-Agent' => 'DesaWisataTheme/2.0'
        )
    ) );

    // Debugging untuk Admin (Akan muncul di inspect element -> console)
    if ( current_user_can( 'administrator' ) ) {
        $status = is_wp_error( $response ) ? $response->get_error_message() : wp_remote_retrieve_response_code( $response );
        echo "<script>console.log('API Fetch [$endpoint]:', " . json_encode(['url' => $full_url, 'status' => $status]) . ");</script>";
    }

    // Handle Error Koneksi (WP Error)
    if ( is_wp_error( $response ) ) {
        return array( 'error' => true, 'message' => 'Koneksi Gagal: ' . $response->get_error_message() );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );

    // Handle HTTP Error (404, 500, dll)
    if ( $response_code !== 200 ) {
        return array( 
            'error'   => true, 
            'message' => "HTTP Error $response_code",
            'debug'   => "URL: $full_url | Response: " . substr($body, 0, 200) . "..."
        );
    }

    $data = json_decode( $body, true );

    // Handle JSON Error
    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return array( 
            'error'   => true, 
            'message' => 'Format JSON Salah',
            'debug'   => "Error: " . json_last_error_msg() . " | Body: " . substr($body, 0, 100)
        );
    }

    // Simpan Cache jika sukses
    if ( ! isset( $data['error'] ) && ! isset( $data['code'] ) ) {
        set_transient( $cache_key, $data, $cache_time );
    }

    return $data;
}

/**
 * 3. Script Loader (Data untuk JS)
 */
function dw_theme_scripts() {
    // CSS
    wp_enqueue_style( 'dw-style', get_stylesheet_uri(), array(), '2.1.0' );
    wp_enqueue_style( 'dw-main', get_template_directory_uri() . '/assets/css/main.css', array(), '2.1.0' );
    
    // JS
    wp_enqueue_script( 'dw-main-js', get_template_directory_uri() . '/assets/js/main.js', array('jquery'), '2.1.0', true );

    // Localize Script (Agar JS tahu URL API)
    $api_base = get_theme_mod( 'dw_api_base_url', 'https://admin.bonang.my.id' );
    wp_localize_script( 'dw-main-js', 'dwData', array(
        'api_url' => untrailingslashit($api_base) . '/wp-json/dw/v1/',
        'site_url' => home_url(),
        'nonce' => wp_create_nonce('dw_nonce')
    ));
}
add_action( 'wp_enqueue_scripts', 'dw_theme_scripts' );

/**
 * 4. Helper: Format Harga
 */
function dw_format_price($price) {
    return 'Rp ' . number_format((float)$price, 0, ',', '.');
}
?>