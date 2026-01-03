<?php
/**
 * Template Name: Dashboard Router
 * Description: Mengarahkan user ke dashboard yang sesuai berdasarkan role. 
 * Semua dashboard dimuat di URL /dashboard agar konsisten sebagai aplikasi.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Proteksi: Wajib Login
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( home_url('/dashboard') ) );
    exit;
}

$current_user = wp_get_current_user();
$roles = (array) $current_user->roles;

/**
 * Helper: Fungsi muat template dengan pengecekan keberadaan file
 * Menggunakan include() agar URL tetap /dashboard/
 */
function dw_load_smart_template( $file_name ) {
    $template = locate_template( $file_name );
    if ( $template ) {
        include( $template );
        exit; // Hentikan eksekusi setelah template dimuat agar tidak ada output ganda
    } else {
        // Tampilan Error yang lebih 'App-like'
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px; background:#f3f4f6; min-height:100vh; display:flex; flex-direction:column; justify-content:center; align-items:center;'>";
        echo "<div style='background:white; padding:30px; border-radius:15px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); max-width:400px;'>";
        echo "<h2 style='color:#ef4444; margin-bottom:10px;'>Opps! Dashboard Hilang</h2>";
        echo "<p style='color:#374151; line-height:1.5;'>File <code>{$file_name}</code> belum diupload ke folder tema. Silakan hubungi pengembang.</p>";
        echo "<br><a href='" . home_url() . "' style='padding:12px 24px; background:#16a34a; color:white; text-decoration:none; border-radius:8px; font-weight:600; display:inline-block;'>Kembali ke Beranda</a>";
        echo "</div></div>";
        exit;
    }
}

// =========================================================
// ROUTING LOGIC (Internal Loading)
// =========================================================

// 1. Dashboard Admin Desa / Editor / Administrator
if ( in_array( 'administrator', $roles ) || in_array( 'editor_desa', $roles ) || in_array( 'admin_desa', $roles ) || in_array( 'verifikator_desa', $roles ) ) {
    dw_load_smart_template( 'page-dashboard-desa.php' );

// 2. Dashboard Pedagang (Merchant)
} elseif ( in_array( 'pedagang', $roles ) || in_array( 'pedagang_toko', $roles ) ) {
    dw_load_smart_template( 'page-dashboard-toko.php' );

// 3. Dashboard Ojek
} elseif ( in_array( 'dw_ojek', $roles ) || in_array( 'pengelola_ojek', $roles ) ) {
    dw_load_smart_template( 'page-dashboard-ojek.php' );

// 4. Dashboard Verifikator UMKM
} elseif ( in_array( 'verifikator_umkm', $roles ) ) {
    dw_load_smart_template( 'page-dashboard-verifikator.php' );

// 5. User Biasa / Role Lainnya
} else {
    // Jika user biasa punya file dashboard sendiri, gunakan itu. 
    // Jika tidak, arahkan ke halaman profil akun standar.
    if ( locate_template('page-dashboard-user.php') ) {
        dw_load_smart_template('page-dashboard-user.php');
    } else {
        // Fallback untuk user biasa: Redirect ke Akun Saya
        wp_redirect( home_url('/akun-saya') );
        exit;
    }
}
?>