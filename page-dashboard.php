<?php
/**
 * Template Name: Dashboard Router
 * Description: Mengarahkan user ke dashboard yang sesuai berdasarkan role.
 */

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url( '/login' ) );
    exit;
}

$current_user = wp_get_current_user();
$roles = $current_user->roles;

// 1. Dashboard Admin Desa / Administrator
if ( in_array( 'administrator', $roles ) || in_array( 'editor_desa', $roles ) ) {
    
    // Load template dashboard desa
    if ( $overridden_template = locate_template( 'page-dashboard-desa.php' ) ) {
        load_template( $overridden_template );
    } else {
        echo "Template Dashboard Desa tidak ditemukan.";
    }

// 2. Dashboard Pedagang / Toko
} elseif ( in_array( 'pedagang', $roles ) ) {
    
    if ( $overridden_template = locate_template( 'page-dashboard-toko.php' ) ) {
        load_template( $overridden_template );
    } else {
        echo "Template Dashboard Toko tidak ditemukan.";
    }

// 3. Dashboard Ojek
} elseif ( in_array( 'dw_ojek', $roles ) ) {
    
    if ( $overridden_template = locate_template( 'page-dashboard-ojek.php' ) ) {
        load_template( $overridden_template );
    } else {
        echo "Template Dashboard Ojek tidak ditemukan.";
    }

// 4. Dashboard Verifikator UMKM (BARU)
} elseif ( in_array( 'verifikator_umkm', $roles ) ) {
    
    if ( $overridden_template = locate_template( 'page-dashboard-verifikator.php' ) ) {
        load_template( $overridden_template );
    } else {
        // Fallback sementara jika file belum ada, atau tampilkan pesan
        echo "<div class='dw-container' style='padding:50px; text-align:center;'>";
        echo "<h2>Selamat Datang, Verifikator!</h2>";
        echo "<p>Template Dashboard Verifikator (page-dashboard-verifikator.php) belum dibuat. Silakan hubungi developer.</p>";
        echo "<a href='" . wp_logout_url(home_url()) . "' class='button'>Logout</a>";
        echo "</div>";
    }

// 5. User Biasa / Pembeli
} else {
    // Arahkan ke halaman akun saya atau homepage
    wp_redirect( home_url( '/akun-saya' ) ); 
    exit;
}
?>