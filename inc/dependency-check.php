<?php
/**
 * SIMPAN FILE INI DI DALAM FOLDER TEMA: /tema-desa-wisata/inc/dependency-check.php
 * LALU INCLUDE DI functions.php: require_once get_template_directory() . '/inc/dependency-check.php';
 * * Fungsi: Mencegah Tema Crash jika Plugin Core mati.
 */

add_action('after_setup_theme', 'dw_theme_check_dependencies');

function dw_theme_check_dependencies() {
    // 1. Cek apakah Class utama Plugin aktif
    // Sesuaikan 'Desa_Wisata_Core' dengan nama class utama di file plugin Anda
    if ( ! class_exists( 'Desa_Wisata_Core' ) && ! defined('DESA_WISATA_CORE_VERSION') ) {
        
        // Jika di halaman admin, tampilkan notifikasi error
        if ( is_admin() ) {
            add_action( 'admin_notices', 'dw_dependency_admin_notice' );
        } 
        // Jika di frontend, stop loading template yang butuh plugin dan tampilkan pesan maintenance
        else {
            wp_die(
                '<h1>Situs Sedang Maintenance</h1>' .
                '<p>Sistem inti Desa Wisata (Plugin) belum aktif. Silakan hubungi administrator.</p>',
                'Plugin Core Missing'
            );
        }
    }
}

function dw_dependency_admin_notice() {
    $class = 'notice notice-error';
    $message = __( '<b>PERINGATAN KRITIS:</b> Tema "Desa Wisata" memerlukan plugin "Desa Wisata Core" untuk berfungsi. Mohon aktifkan plugin tersebut.', 'tema-desa-wisata' );

    printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
}

/**
 * Helper function dummy untuk mencegah error "Call to undefined function"
 * jika plugin mati, tapi kita masih ingin menampilkan header/footer dasar.
 */
if ( ! function_exists( 'dw_get_harga_format' ) ) {
    function dw_get_harga_format($price) {
        return 'Rp 0 (System Error)';
    }
}