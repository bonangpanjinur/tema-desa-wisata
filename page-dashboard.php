<?php
/**
 * Template Name: Dashboard User
 */

if ( ! is_user_logged_in() ) {
    wp_redirect( site_url('/login') );
    exit;
}

$user = wp_get_current_user();
get_header(); 
?>

<div class="dashboard-wrapper">
    <div class="container">
        <div class="row">
            
            <!-- SIDEBAR (Kiri) -->
            <aside class="col-3 dashboard-sidebar">
                <div class="user-profile-summary">
                    <div class="user-avatar">
                        <?php echo get_avatar( $user->ID, 96 ); ?>
                    </div>
                    <h4 style="margin-bottom:0; font-size:18px;"><?php echo esc_html($user->display_name); ?></h4>
                    <small style="color:#666;"><?php echo esc_html($user->user_email); ?></small>
                </div>

                <nav class="dashboard-menu">
                    <ul>
                        <li><a href="<?php echo site_url('/dashboard'); ?>" class="<?php echo is_page('dashboard') ? 'active' : ''; ?>">Dashboard Utama</a></li>
                        <li><a href="<?php echo site_url('/transaksi'); ?>">Riwayat Transaksi</a></li>
                        <li><a href="<?php echo site_url('/akun-saya'); ?>">Edit Profil</a></li>
                        
                        <?php if (current_user_can('pedagang')) : ?>
                            <li style="margin-top:10px; border-top:1px solid #eee; padding-top:10px;"><strong>Menu Pedagang</strong></li>
                            <li><a href="<?php echo site_url('/dashboard-toko'); ?>">Kelola Produk</a></li>
                            <li><a href="<?php echo site_url('/pesanan-masuk'); ?>">Pesanan Masuk</a></li>
                        <?php endif; ?>

                        <li style="margin-top:20px;">
                            <a href="<?php echo wp_logout_url(home_url()); ?>" style="color:#dc3545;">Logout / Keluar</a>
                        </li>
                    </ul>
                </nav>
            </aside>

            <!-- KONTEN UTAMA (Kanan) -->
            <main class="col-9">
                <div class="dashboard-content">
                    <h2 style="border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
                        <?php the_title(); ?>
                    </h2>
                    
                    <?php
                    while ( have_posts() ) :
                        the_post();
                        the_content();
                    endwhile;
                    ?>
                </div>
            </main>

        </div>
    </div>
</div>

<?php get_footer(); ?>