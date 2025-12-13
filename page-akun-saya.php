<?php
/* Template Name: Akun Saya */
if ( ! is_user_logged_in() ) {
    wp_redirect( site_url('/login') );
    exit;
}
get_header(); 
$current_user = wp_get_current_user();
?>

<!-- Profil Header -->
<div class="profile-header">
    <?php echo get_avatar( $current_user->ID, 80, '', 'Avatar', array('class' => 'profile-pic') ); ?>
    <div class="profile-info">
        <h3><?php echo esc_html( $current_user->display_name ); ?></h3>
        <p><?php echo esc_html( $current_user->user_email ); ?></p>
        <p style="font-size: 0.8rem; color: var(--primary); margin-top: 5px; font-weight: 600;">
            <i class="fas fa-user-circle"></i> 
            <?php 
                $roles = $current_user->roles;
                echo !empty($roles) ? ucfirst( str_replace('_', ' ', $roles[0]) ) : 'Pengguna';
            ?>
        </p>
    </div>
</div>

<!-- Menu Akun -->
<div class="menu-list">
    <div class="menu-section-title">Pengaturan Akun</div>
    <a href="<?php echo admin_url('profile.php'); ?>" class="menu-item">
        <div class="menu-icon"><i class="fas fa-edit"></i></div>
        <div class="menu-text">Edit Profil</div>
        <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
    </a>
    <a href="#" class="menu-item">
        <div class="menu-icon"><i class="fas fa-map-marker-alt"></i></div>
        <div class="menu-text">Alamat Tersimpan</div>
        <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
    </a>
</div>

<!-- Menu Aktivitas -->
<div class="menu-list" style="margin-top: 20px;">
    <div class="menu-section-title">Aktivitas Saya</div>
    <a href="<?php echo site_url('/transaksi'); ?>" class="menu-item">
        <div class="menu-icon"><i class="fas fa-receipt"></i></div>
        <div class="menu-text">Daftar Transaksi</div>
        <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
    </a>
    
    <!-- Menu Khusus Pedagang -->
    <?php if ( in_array( 'pedagang', $current_user->roles ) ) : ?>
        <a href="<?php echo site_url('/dashboard-toko'); ?>" class="menu-item" style="background-color: #F1F8E9;">
            <div class="menu-icon"><i class="fas fa-store"></i></div>
            <div class="menu-text">Dashboard Toko</div>
            <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
        </a>
    <?php endif; ?>

    <!-- Menu Khusus Pengelola Desa -->
    <?php if ( in_array( 'pengelola_desa', $current_user->roles ) ) : ?>
        <a href="<?php echo site_url('/dashboard-desa'); ?>" class="menu-item" style="background-color: #E3F2FD;">
            <div class="menu-icon"><i class="fas fa-landmark"></i></div>
            <div class="menu-text">Dashboard Desa</div>
            <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
        </a>
    <?php endif; ?>
</div>

<!-- Menu Bantuan -->
<div class="menu-list" style="margin-top: 20px;">
    <div class="menu-section-title">Bantuan & Info</div>
    <a href="#" class="menu-item">
        <div class="menu-icon"><i class="fas fa-headset"></i></div>
        <div class="menu-text">Hubungi Admin</div>
        <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
    </a>
    <a href="<?php echo site_url('/tentang'); ?>" class="menu-item">
        <div class="menu-icon"><i class="fas fa-info-circle"></i></div>
        <div class="menu-text">Tentang Aplikasi</div>
        <div class="menu-arrow"><i class="fas fa-chevron-right"></i></div>
    </a>
</div>

<!-- Logout -->
<a href="<?php echo wp_logout_url( home_url() ); ?>" class="btn-logout">
    <i class="fas fa-sign-out-alt"></i> Keluar
</a>

<?php get_footer(); ?>