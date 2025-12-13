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
        'default'   => 'https://admin.bonang.my.id', // DEFAULT URL SERVER ANDA
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
 * 2. Fungsi Fetching Data dengan Error Handling Lengkap
 */
function dw_fetch_api_data( $endpoint, $cache_time = 3600 ) {
    $base_url = get_theme_mod( 'dw_api_base_url', 'https://admin.bonang.my.id' ); // Fallback ke URL Anda
    
    // Bersihkan slash di akhir URL jika ada
    $base_url = untrailingslashit( $base_url );
    $full_url = $base_url . $endpoint;

    // Cek Cache dulu
    $cache_key = 'dw_api_' . md5( $full_url );
    $cached_data = get_transient( $cache_key );
    
    // Hapus baris ini jika ingin test realtime terus (matikan cache)
    // if ( false !== $cached_data ) return $cached_data;

    // Request ke Server
    $response = wp_remote_get( $full_url, array(
        'timeout'   => 20,
        'sslverify' => false, // PENTING: Matikan verifikasi SSL sementara untuk mencegah error koneksi
        'headers'   => array( 'Accept' => 'application/json' )
    ) );

    // Cek Error Koneksi (WP Error)
    if ( is_wp_error( $response ) ) {
        return array( 'error' => true, 'message' => $response->get_error_message() );
    }

    // Cek Kode HTTP
    $response_code = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );

    if ( $response_code !== 200 ) {
        return array( 
            'error' => true, 
            'message' => "HTTP Error $response_code",
            'debug'   => substr($body, 0, 200) // Tampilkan sedikit response body
        );
    }

    // Decode JSON
    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE ) {
        return array( 'error' => true, 'message' => 'Format JSON Salah/Rusak.' );
    }

    // Simpan Cache jika sukses
    if ( ! isset( $data['error'] ) ) {
        set_transient( $cache_key, $data, $cache_time );
    }

    return $data;
}

// 3. Helper Debug Status
function dw_get_api_status() {
    $url = get_theme_mod( 'dw_api_base_url', 'https://admin.bonang.my.id' );
    return "Target Server: " . $url;
}
?>