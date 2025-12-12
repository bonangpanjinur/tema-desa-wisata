</main>
        <!-- CONTENT END -->

        <!-- BOTTOM NAVIGATION (Hanya muncul jika bukan halaman login/checkout/single) -->
        <?php if (!is_page(['login', 'register', 'checkout']) && !is_singular(['dw_produk', 'dw_wisata'])) : ?>
        <div class="fixed bottom-0 w-full max-w-[480px] z-50 px-6 pb-6 pt-2 pointer-events-none">
            <nav class="bg-white/95 backdrop-blur-xl border border-white/50 shadow-[0_8px_30px_rgb(0,0,0,0.12)] rounded-2xl px-2 py-2 flex justify-between items-center pointer-events-auto">
                
                <a href="<?php echo home_url(); ?>" class="nav-item flex-1 flex flex-col items-center gap-1 py-2 text-gray-400 hover:text-primary transition-all <?php echo is_front_page() ? 'text-primary font-bold' : ''; ?>">
                    <i class="<?php echo is_front_page() ? 'ph-fill' : 'ph-bold'; ?> ph-house text-2xl"></i>
                    <span class="text-[10px]">Beranda</span>
                </a>

                <a href="<?php echo site_url('/wisata'); ?>" class="nav-item flex-1 flex flex-col items-center gap-1 py-2 text-gray-400 hover:text-primary transition-all <?php echo is_page('wisata') ? 'text-primary font-bold' : ''; ?>">
                    <i class="<?php echo is_page('wisata') ? 'ph-fill' : 'ph-bold'; ?> ph-map-trifold text-2xl"></i>
                    <span class="text-[10px]">Jelajah</span>
                </a>

                <!-- Main Action Button (Center) -->
                <?php
                // Fix: Gunakan get_post_type_archive_link dengan fallback
                $produk_link = function_exists('get_post_type_archive_link') ? get_post_type_archive_link('dw_produk') : site_url('/produk');
                if (!$produk_link) $produk_link = site_url('/produk');
                ?>
                <div class="relative w-16 flex justify-center group">
                    <a href="<?php echo esc_url($produk_link); ?>" class="absolute -top-8 w-14 h-14 bg-gray-900 text-white rounded-full shadow-lg shadow-gray-900/40 flex items-center justify-center border-4 border-[#F8FAFC] transform transition-transform group-hover:scale-105 active:scale-95">
                        <i class="ph-fill ph-storefront text-2xl"></i>
                    </a>
                    <span class="text-[10px] text-gray-500 font-medium mt-7 group-hover:text-primary">Pasar</span>
                </div>

                <a href="<?php echo site_url('/transaksi'); ?>" class="nav-item flex-1 flex flex-col items-center gap-1 py-2 text-gray-400 hover:text-primary transition-all <?php echo is_page('transaksi') ? 'text-primary font-bold' : ''; ?>">
                    <i class="<?php echo is_page('transaksi') ? 'ph-fill' : 'ph-bold'; ?> ph-receipt text-2xl"></i>
                    <span class="text-[10px]">Pesanan</span>
                </a>

                <a href="<?php echo site_url('/akun-saya'); ?>" class="nav-item flex-1 flex flex-col items-center gap-1 py-2 text-gray-400 hover:text-primary transition-all <?php echo is_page('akun-saya') ? 'text-primary font-bold' : ''; ?>">
                    <i class="<?php echo is_page('akun-saya') ? 'ph-fill' : 'ph-bold'; ?> ph-user text-2xl"></i>
                    <span class="text-[10px]">Akun</span>
                </a>

            </nav>
        </div>
        <?php endif; ?>

    </div> <!-- End App Frame -->
    <?php wp_footer(); ?>
</body>
</html>