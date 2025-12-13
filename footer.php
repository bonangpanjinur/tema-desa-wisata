<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

    </main> <!-- #main-content -->

    <!-- Footer Copyright -->
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?>. <br>Desa Wisata Digital.</p>
        </div>
    </footer>

    <!-- Bottom Navigation Bar -->
    <div class="bottom-nav">
        
        <!-- 1. Beranda -->
        <a href="<?php echo home_url('/'); ?>" class="nav-item <?php echo is_front_page() ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Beranda</span>
        </a>
        
        <!-- 2. Jelajah -->
        <?php 
        $link_desa = get_post_type_archive_link('dw_desa');
        if ( ! $link_desa ) $link_desa = home_url( '?post_type=dw_desa' );
        ?>
        <a href="<?php echo esc_url( $link_desa ); ?>" class="nav-item <?php echo is_post_type_archive('dw_desa') ? 'active' : ''; ?>">
            <i class="fas fa-compass"></i>
            <span>Jelajah</span>
        </a>
        
        <!-- 3. Tombol Tengah (Pesanan/Transaksi) -->
        <div class="nav-center-wrapper">
            <a href="<?php echo site_url('/transaksi'); ?>" class="nav-btn-center" aria-label="Pesanan">
                <i class="fas fa-receipt"></i>
            </a>
        </div>

        <!-- 4. Belanja -->
        <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="nav-item <?php echo is_post_type_archive('dw_produk') ? 'active' : ''; ?>">
            <i class="fas fa-shopping-bag"></i>
            <span>Belanja</span>
        </a>
        
        <!-- 5. Akun -->
        <a href="<?php echo is_user_logged_in() ? site_url('/akun-saya') : site_url('/login'); ?>" class="nav-item <?php echo (is_page('akun-saya') || is_page('login')) ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span><?php echo is_user_logged_in() ? 'Akun' : 'Masuk'; ?></span>
        </a>

    </div>

</div> <!-- .app-container -->

<?php wp_footer(); ?>
</body>
</html>