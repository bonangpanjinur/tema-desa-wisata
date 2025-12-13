<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

    </main> <!-- #main-content -->

    <!-- Footer Copyright -->
    <footer class="site-footer" style="margin-bottom: 80px;"> <!-- Margin bottom agar tidak ketutup nav -->
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?>. <br>Desa Wisata Digital.</p>
        </div>
    </footer>

    <!-- Bottom Navigation Bar (Mobile Sticky) -->
    <div class="bottom-nav">
        <!-- Wrapper dalam untuk flex distribution yang rapi -->
        <div class="bottom-nav-inner"> 
            
            <a href="<?php echo home_url('/'); ?>" class="nav-item <?php echo is_front_page() ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Beranda</span>
            </a>
            
            <?php 
            $link_desa = get_post_type_archive_link('dw_desa');
            if ( ! $link_desa ) $link_desa = home_url( '?post_type=dw_desa' );
            ?>
            <a href="<?php echo esc_url( $link_desa ); ?>" class="nav-item <?php echo is_post_type_archive('dw_desa') ? 'active' : ''; ?>">
                <i class="fas fa-compass"></i>
                <span>Jelajah</span>
            </a>
            
            <!-- Tombol Tengah (TRANSAKSI / PESANAN) -->
            <!-- Menggunakan class nav-btn-center agar menonjol -->
            <a href="<?php echo site_url('/transaksi'); ?>" class="nav-btn-center" aria-label="Pesanan Saya">
                <i class="fas fa-receipt"></i> <!-- Icon Receipt/Pesanan -->
            </a>

            <!-- Tombol Produk (Menggantikan posisi Pesanan sebelumnya) -->
            <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="nav-item <?php echo is_post_type_archive('dw_produk') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-bag"></i>
                <span>Belanja</span>
            </a>
            
            <a href="<?php echo is_user_logged_in() ? site_url('/akun-saya') : site_url('/login'); ?>" class="nav-item <?php echo (is_page('akun-saya') || is_page('login')) ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span><?php echo is_user_logged_in() ? 'Akun' : 'Masuk'; ?></span>
            </a>

        </div>
    </div>

</div> <!-- .app-container -->

<?php wp_footer(); ?>
</body>
</html>