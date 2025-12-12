</main>
        <!-- CONTENT END -->

        <!-- BOTTOM NAVIGATION (Mobile Only - Hidden on Desktop) -->
        <?php if (!is_page(['login', 'register', 'checkout']) && !is_singular(['dw_produk', 'dw_wisata'])) : ?>
        <div class="md:hidden fixed bottom-6 left-0 right-0 z-40 flex justify-center px-4 pointer-events-none">
            <nav class="bg-gray-900/90 backdrop-blur-xl border border-white/10 shadow-2xl rounded-full px-6 py-3 flex items-center gap-8 pointer-events-auto">
                
                <a href="<?php echo home_url(); ?>" class="flex flex-col items-center gap-1 text-gray-400 hover:text-white transition-colors <?php echo is_front_page() ? '!text-white' : ''; ?>">
                    <i class="<?php echo is_front_page() ? 'ph-fill' : 'ph-bold'; ?> ph-house text-xl"></i>
                </a>

                <a href="<?php echo site_url('/wisata'); ?>" class="flex flex-col items-center gap-1 text-gray-400 hover:text-white transition-colors <?php echo is_page('wisata') ? '!text-white' : ''; ?>">
                    <i class="<?php echo is_page('wisata') ? 'ph-fill' : 'ph-bold'; ?> ph-map-trifold text-xl"></i>
                </a>

                <!-- Central Shop Button -->
                <div class="relative -top-8">
                    <?php 
                    $shop_link = function_exists('get_post_type_archive_link') ? get_post_type_archive_link('dw_produk') : site_url('/produk'); 
                    ?>
                    <a href="<?php echo esc_url($shop_link); ?>" class="w-14 h-14 bg-gradient-to-tr from-primary to-emerald-400 rounded-full flex items-center justify-center text-white shadow-lg shadow-primary/40 border-4 border-[#F8FAFC] transform transition-transform active:scale-90">
                        <i class="ph-fill ph-storefront text-2xl"></i>
                    </a>
                </div>

                <a href="<?php echo site_url('/transaksi'); ?>" class="flex flex-col items-center gap-1 text-gray-400 hover:text-white transition-colors <?php echo is_page('transaksi') ? '!text-white' : ''; ?>">
                    <i class="<?php echo is_page('transaksi') ? 'ph-fill' : 'ph-bold'; ?> ph-receipt text-xl"></i>
                </a>

                <a href="<?php echo site_url('/akun-saya'); ?>" class="flex flex-col items-center gap-1 text-gray-400 hover:text-white transition-colors <?php echo is_page('akun-saya') ? '!text-white' : ''; ?>">
                    <i class="<?php echo is_page('akun-saya') ? 'ph-fill' : 'ph-bold'; ?> ph-user text-xl"></i>
                </a>

            </nav>
        </div>
        <?php endif; ?>

        <!-- DESKTOP FOOTER (Visible only on Desktop) -->
        <footer class="bg-white border-t border-gray-200 pt-16 pb-8 hidden md:block">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-4 gap-8 mb-12">
                    <div class="col-span-1">
                        <h3 class="font-extrabold text-xl text-primary mb-4">Desa Wisata</h3>
                        <p class="text-sm text-gray-500 leading-relaxed">
                            Platform digital yang menghubungkan wisatawan dengan keindahan alam dan produk lokal desa wisata Indonesia.
                        </p>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 mb-4">Jelajah</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><a href="<?php echo site_url('/wisata'); ?>" class="hover:text-primary">Destinasi</a></li>
                            <li><a href="<?php echo site_url('/produk'); ?>" class="hover:text-primary">Produk UMKM</a></li>
                            <li><a href="#" class="hover:text-primary">Peta Desa</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 mb-4">Bantuan</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><a href="#" class="hover:text-primary">Cara Pesan</a></li>
                            <li><a href="#" class="hover:text-primary">Daftar Mitra</a></li>
                            <li><a href="#" class="hover:text-primary">Hubungi Kami</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 mb-4">Ikuti Kami</h4>
                        <div class="flex gap-3">
                            <a href="#" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white transition-colors"><i class="ph-fill ph-instagram-logo"></i></a>
                            <a href="#" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white transition-colors"><i class="ph-fill ph-facebook-logo"></i></a>
                            <a href="#" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-primary hover:text-white transition-colors"><i class="ph-fill ph-youtube-logo"></i></a>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 pt-8 text-center text-sm text-gray-400">
                    &copy; <?php echo date('Y'); ?> Desa Wisata Core. All rights reserved.
                </div>
            </div>
        </footer>

    </div> <!-- End Page Wrapper -->
    
    <?php wp_footer(); ?>
</body>
</html>