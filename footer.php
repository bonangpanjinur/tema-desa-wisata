</main> <!-- #main-content -->

    <!-- Footer Copyright (Opsional di Mobile App View) -->
    <footer class="site-footer" style="padding: 20px; text-align: center; color: #999; font-size: 0.8rem; margin-top: 20px;">
        <p>&copy; <?php echo date('Y'); ?> <?php bloginfo( 'name' ); ?>.</p>
    </footer>

    <!-- Bottom Navigation (Mobile Sticky) -->
    <nav class="bottom-nav">
        <a href="<?php echo home_url('/'); ?>" class="nav-btn <?php echo is_front_page() ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Beranda</span>
        </a>
        
        <a href="<?php echo get_post_type_archive_link('dw_desa'); ?>" class="nav-btn <?php echo is_post_type_archive('dw_desa') ? 'active' : ''; ?>">
            <i class="fas fa-compass"></i>
            <span>Jelajah</span>
        </a>
        
        <!-- Tombol Tengah (Belanja) -->
        <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="nav-btn-center">
            <i class="fas fa-shopping-basket"></i>
        </a>

        <a href="<?php echo site_url('/transaksi'); ?>" class="nav-btn <?php echo is_page('transaksi') ? 'active' : ''; ?>">
            <i class="fas fa-receipt"></i>
            <span>Pesanan</span>
        </a>
        
        <a href="<?php echo is_user_logged_in() ? site_url('/akun-saya') : site_url('/login'); ?>" class="nav-btn <?php echo (is_page('akun-saya') || is_page('login')) ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Akun</span>
        </a>
    </nav>

</div> <!-- .app-container -->

<?php wp_footer(); ?>
</body>
</html>