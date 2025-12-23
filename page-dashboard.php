<?php
/**
 * Template Name: Dashboard Router
 * Description: Router pusat untuk URL /dashboard yang memuat template sesuai role user.
 */

// 1. Cek Login
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$user = wp_get_current_user();
$roles = (array) $user->roles;

// 2. Logika Routing Berdasarkan Role
// Urutan cek penting: Admin > Pengelola Desa > Pedagang > Ojek > User Biasa

if (in_array('administrator', $roles)) {
    // Admin WP tetap ke wp-admin
    wp_redirect(admin_url());
    exit;
} 
elseif (in_array('pengelola_desa', $roles)) {
    // Load Dashboard Desa
    include(locate_template('page-dashboard-desa.php'));
    exit;
} 
elseif (in_array('pedagang', $roles)) {
    // Load Dashboard Toko
    include(locate_template('page-dashboard-toko.php'));
    exit;
} 
elseif (in_array('ojek_wisata', $roles)) {
    // Load Dashboard Ojek
    include(locate_template('page-dashboard-ojek.php'));
    exit;
} 
else {
    // User biasa (Pembeli) -> Load Akun Saya
    include(locate_template('page-akun-saya.php'));
    exit;
}
?>