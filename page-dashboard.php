<?php
/**
 * Template Name: Dashboard Router
 * Description: Mengarahkan user ke dashboard yang sesuai berdasarkan role (Desa, Pedagang, Ojek, Verifikator).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Cek Login
if ( ! is_user_logged_in() ) {
    // Redirect ke halaman login atau homepage
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

$current_user = wp_get_current_user();
$roles = (array) $current_user->roles;

// =========================================================
// ROUTING LOGIC
// =========================================================

// 1. Dashboard Admin Desa / Administrator
if ( in_array( 'administrator', $roles ) || in_array( 'editor_desa', $roles ) ) {
    
    if ( $template = locate_template( 'page-dashboard-desa.php' ) ) {
        load_template( $template );
    } else {
        wp_die( 'Error: File template <code>page-dashboard-desa.php</code> tidak ditemukan di folder tema.', 'Template Missing' );
    }

// 2. Dashboard Pedagang (Merchant)
} elseif ( in_array( 'pedagang', $roles ) ) {
    
    if ( $template = locate_template( 'page-dashboard-toko.php' ) ) {
        load_template( $template );
    } else {
        wp_die( 'Error: File template <code>page-dashboard-toko.php</code> tidak ditemukan.', 'Template Missing' );
    }

// 3. Dashboard Ojek
} elseif ( in_array( 'dw_ojek', $roles ) ) {
    
    if ( $template = locate_template( 'page-dashboard-ojek.php' ) ) {
        load_template( $template );
    } else {
        wp_die( 'Error: File template <code>page-dashboard-ojek.php</code> tidak ditemukan.', 'Template Missing' );
    }

// 4. Dashboard Verifikator UMKM (UPDATED)
} elseif ( in_array( 'verifikator_umkm', $roles ) ) {
    
    // Sekarang memanggil file verifikator yang baru dibuat
    if ( $template = locate_template( 'page-dashboard-verifikator.php' ) ) {
        load_template( $template );
    } else {
        // Pesan error jika file belum diupload
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px; background:#f3f4f6; height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>";
        echo "<h2 style='color:#ef4444; margin-bottom:10px;'>Template Tidak Ditemukan</h2>";
        echo "<p style='color:#374151;'>Pastikan file <strong>page-dashboard-verifikator.php</strong> sudah ada di folder tema Anda.</p>";
        echo "<br><a href='" . wp_logout_url(home_url()) . "' style='padding:10px 20px; background:#374151; color:white; text-decoration:none; border-radius:5px;'>Logout</a>";
        echo "</div>";
        exit;
    }

// 5. User Biasa / Pembeli / Role Lain
} else {
    // Redirect ke Homepage atau Halaman Akun Default WooCommerce
    wp_redirect( home_url() ); 
    exit;
}
?>