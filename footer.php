<footer class="site-footer" style="padding: 20px 0; background: #fff; margin-top: 40px; border-top: 1px solid #eee; text-align: center; color: #777;">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Bottom Navigation Bar (Mobile Only - Sticky Footer) -->
    <div class="bottom-nav">
        <div class="bottom-nav-inner">
            <a href="<?php echo home_url('/'); ?>" class="nav-item <?php echo is_front_page() ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Beranda</span>
            </a>
            
            <a href="<?php echo site_url('/wisata'); ?>" class="nav-item <?php echo is_post_type_archive('dw_wisata') ? 'active' : ''; ?>">
                <i class="fas fa-map-marked-alt"></i>
                <span>Wisata</span>
            </a>

            <!-- Tombol Tengah (Bisa Search atau Scan QR) -->
            <a href="<?php echo site_url('/produk'); ?>" class="nav-item <?php echo is_post_type_archive('dw_produk') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-bag"></i>
                <span>Belanja</span>
            </a>

            <a href="<?php echo site_url('/cart'); ?>" class="nav-item <?php echo is_page('cart') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Keranjang</span>
            </a>

            <a href="<?php echo is_user_logged_in() ? site_url('/akun-saya') : site_url('/login'); ?>" class="nav-item <?php echo (is_page('akun-saya') || is_page('login')) ? 'active' : ''; ?>">
                <i class="fas fa-user"></i>
                <span><?php echo is_user_logged_in() ? 'Akun' : 'Masuk'; ?></span>
            </a>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>