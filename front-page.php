<?php
/**
 * Functions and definitions for Desa Wisata API Theme
 */

// 1. Tambahkan Pengaturan di Customizer untuk URL API
// Ini agar Anda bisa mengganti alamat server tanpa ubah kodingan
function dw_api_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'dw_api_settings', array(
        'title'    => __( 'Konfigurasi API Desa Wisata', 'dw-api-theme' ),
        'priority' => 30,
    ) );

    $wp_customize->add_setting( 'dw_api_base_url', array(
        'default'   => 'https://website-utama-anda.com', // Ganti default ini
        'transport' => 'refresh',
    ) );

    $wp_customize->add_control( 'dw_api_base_url', array(
        'label'    => __( 'URL Website Server (Core)', 'dw-api-theme' ),
        'description' => 'Masukkan URL website tempat plugin Desa Wisata Core terinstall (tanpa slash di akhir).',
        'section'  => 'dw_api_settings',
        'type'     => 'url',
    ) );
}
add_action( 'customize_register', 'dw_api_customize_register' );

/**
 * 2. Fungsi Utama Fetching Data (Jantung Tema Ini)
 * Menggunakan Transients API untuk Caching agar tidak berat.
 * * @param string $endpoint Contoh: '/wp-json/dw-api/v1/desa'
 * @param int $cache_time Waktu simpan cache dalam detik (default 1 jam)
 * @return array|mixed Data JSON yang sudah di-decode atau false jika gagal
 */
function dw_fetch_api_data( $endpoint, $cache_time = 3600 ) {
    // Ambil URL dasar dari setting
    $base_url = get_theme_mod( 'dw_api_base_url' );
    
    if ( empty( $base_url ) ) {
        return false;
    }

    // Buat kunci cache unik berdasarkan endpoint
    $cache_key = 'dw_api_' . md5( $endpoint );
    
    // Cek apakah data ada di cache database lokal
    $cached_data = get_transient( $cache_key );
    if ( false !== $cached_data ) {
        return $cached_data; // Kembalikan data cache, tidak perlu request ke server
    }

    // Jika tidak ada cache, LAKUKAN REQUEST KE SERVER
    $full_url = $base_url . $endpoint;
    
    // Request ke API
    $response = wp_remote_get( $full_url, array(
        'timeout' => 15, // Tunggu maks 15 detik
        'headers' => array(
            'Accept' => 'application/json'
        )
    ) );

    // Cek Error Koneksi
    if ( is_wp_error( $response ) ) {
        return null; // Gagal koneksi
    }

    // Cek Kode HTTP (Harus 200 OK)
    $response_code = wp_remote_retrieve_response_code( $response );
    if ( $response_code !== 200 ) {
        return null; // Server merespon error (404/500 dll)
    }

    // Ambil Body dan Decode JSON
    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true ); // true = jadikan array asosiatif

    // Simpan ke Cache (Transient)
    if ( ! empty( $data ) ) {
        set_transient( $cache_key, $data, $cache_time );
    }

    return $data;
}

// 3. Helper untuk Debugging (Cek status koneksi)
function dw_api_status_check() {
    $base_url = get_theme_mod( 'dw_api_base_url' );
    if( ! $base_url ) return "URL API belum disetting di Customize.";
    return "Terhubung ke: " . $base_url;
}
?>