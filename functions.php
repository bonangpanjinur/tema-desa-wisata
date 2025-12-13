<?php
/**
 * Functions and definitions for Desa Wisata API Theme
 */

// 1. Konfigurasi API
function dw_api_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'dw_api_settings', array(
        'title'    => __( 'Konfigurasi API Desa Wisata', 'dw-api-theme' ),
        'priority' => 30,
    ) );

    $wp_customize->add_setting( 'dw_api_base_url', array(
        'default'   => 'https://admin.bonang.my.id', // URL Server Plugin Anda
        'transport' => 'refresh',
    ) );

    $wp_customize->add_control( 'dw_api_base_url', array(
        'label'    => __( 'URL Website Server (Core)', 'dw-api-theme' ),
        'section'  => 'dw_api_settings',
        'type'     => 'url',
    ) );
}
add_action( 'customize_register', 'dw_api_customize_register' );

/**
 * 2. Fungsi Fetching Data Utama
 */
function dw_fetch_api_data( $endpoint, $cache_time = 3600 ) {
    $base_url = get_theme_mod( 'dw_api_base_url', 'https://admin.bonang.my.id' );
    $base_url = untrailingslashit( $base_url );
    $full_url = $base_url . $endpoint;

    // Cache Key
    $cache_key = 'dw_api_' . md5( $full_url );
    
    // Matikan cache saat developing agar perubahan data langsung terlihat
    // $cached_data = get_transient( $cache_key );
    // if ( false !== $cached_data ) return $cached_data;

    $response = wp_remote_get( $full_url, array(
        'timeout'   => 30, // Perpanjang timeout
        'sslverify' => false, // Matikan cek SSL (berguna jika SSL server target bermasalah/self-signed)
        'headers'   => array( 'Accept' => 'application/json' )
    ) );

    if ( is_wp_error( $response ) ) {
        return array( 'error' => true, 'message' => $response->get_error_message() );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );
    
    if ( $response_code !== 200 ) {
        return array( 
            'error' => true, 
            'message' => "HTTP Error $response_code",
            'debug'   => $body
        );
    }

    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return array( 'error' => true, 'message' => 'Response bukan format JSON yang valid.' );
    }

    // Cek apakah data dibungkus dalam properti 'data' (common pattern)
    // Tapi berdasarkan docs sepertinya langsung array, kita biarkan raw dulu.
    
    if ( ! isset( $data['error'] ) && ! isset( $data['code'] ) ) {
        set_transient( $cache_key, $data, $cache_time );
    }

    return $data;
}

/**
 * 3. Alat Bantu Debugging Route
 * Mencari namespace 'dw/v1' sesuai dokumentasi
 */
function dw_debug_find_routes() {
    $base_url = get_theme_mod( 'dw_api_base_url', 'https://admin.bonang.my.id' );
    $base_url = untrailingslashit( $base_url );
    
    $response = wp_remote_get( $base_url . '/wp-json/', array( 'sslverify' => false ) );
    
    if ( is_wp_error( $response ) ) return "Gagal koneksi ke root API ($base_url)";

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( ! isset( $data['routes'] ) ) return "Tidak ada rute API ditemukan.";

    $found_routes = [];
    foreach ( $data['routes'] as $route => $details ) {
        // Filter khusus namespace dw/v1
        if ( strpos( $route, '/dw/v1' ) !== false ) {
            $found_routes[] = $route;
        }
    }

    return $found_routes;
}
?>