</main>
        <!-- MAIN CONTENT END -->

        <!-- DESKTOP FOOTER (Hidden on Mobile) -->
        <footer class="hidden md:block bg-white border-t border-gray-200 pt-10 pb-6 mt-auto">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-4 gap-8 mb-8">
                    <div>
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-leaf text-emerald-600 text-2xl"></i>
                            <span class="font-bold text-xl text-gray-800"><?php bloginfo('name'); ?></span>
                        </div>
                        <p class="text-gray-500 text-sm leading-relaxed">Platform digital untuk menjelajahi keindahan desa wisata dan mendukung produk UMKM lokal.</p>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 mb-4">Navigasi</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><a href="<?php echo home_url('/wisata'); ?>" class="hover:text-emerald-600">Jelajah Wisata</a></li>
                            <li><a href="<?php echo home_url('/produk'); ?>" class="hover:text-emerald-600">Produk Desa</a></li>
                            <li><a href="<?php echo home_url('/tentang'); ?>" class="hover:text-emerald-600">Tentang Kami</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 mb-4">Bantuan</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><a href="#" class="hover:text-emerald-600">Cara Pembelian</a></li>
                            <li><a href="#" class="hover:text-emerald-600">Hubungi Kami</a></li>
                            <li><a href="#" class="hover:text-emerald-600">Syarat & Ketentuan</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 mb-4">Unduh Aplikasi</h4>
                        <div class="flex gap-2">
                            <button class="bg-gray-900 text-white px-3 py-2 rounded-lg text-xs flex items-center gap-2 hover:bg-gray-800">
                                <i class="fab fa-google-play text-lg"></i>
                                <div class="text-left">
                                    <span class="block text-[8px] uppercase">Get it on</span>
                                    <span class="font-bold">Google Play</span>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-100 pt-6 text-center text-sm text-gray-400">
                    &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.
                </div>
            </div>
        </footer>

        <!-- MOBILE BOTTOM NAVIGATION (Hidden on Desktop) -->
        <nav class="md:hidden bg-white border-t border-gray-200 px-4 py-3 flex justify-between items-end sticky bottom-0 z-50 w-full shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            
            <!-- 1. Beranda -->
            <a href="<?php echo home_url(); ?>" class="flex flex-col items-center gap-1 <?php echo is_front_page() ? 'text-emerald-600' : 'text-gray-400 hover:text-emerald-600'; ?> transition w-16">
                <i class="fas fa-home text-lg"></i>
                <span class="text-[10px] font-medium">Beranda</span>
            </a>
            
            <!-- 2. Jelajah (Link Fixed: /wisata) -->
            <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-1 <?php echo is_post_type_archive('dw_wisata') ? 'text-emerald-600' : 'text-gray-400 hover:text-emerald-600'; ?> transition w-16">
                <i class="fas fa-compass text-lg"></i>
                <span class="text-[10px] font-medium">Jelajah</span>
            </a>
            
            <!-- 3. Produk (Center Featured) -->
            <div class="relative -top-5 w-16 flex flex-col items-center">
                <a href="<?php echo home_url('/produk'); ?>" class="bg-emerald-600 text-white w-14 h-14 rounded-full flex items-center justify-center shadow-lg border-4 border-gray-50 transform active:scale-95 transition">
                    <i class="fas fa-box-open text-xl"></i>
                </a>
                <span class="text-[10px] font-medium text-emerald-700 mt-1">Produk</span>
            </div>
            
            <!-- 4. Transaksi -->
            <a href="<?php echo home_url('/transaksi'); ?>" class="flex flex-col items-center gap-1 text-gray-400 hover:text-emerald-600 transition w-16">
                <i class="fas fa-receipt text-lg"></i>
                <span class="text-[10px] font-medium">Transaksi</span>
            </a>
            
            <!-- 5. Akun -->
            <a href="<?php echo is_user_logged_in() ? home_url('/akun-saya') : home_url('/login'); ?>" class="flex flex-col items-center gap-1 <?php echo is_page('akun-saya') ? 'text-emerald-600' : 'text-gray-400 hover:text-emerald-600'; ?> transition w-16">
                <i class="fas fa-user text-lg"></i>
                <span class="text-[10px] font-medium">Akun</span>
            </a>
        </nav>

    </div> <!-- End of Main Wrapper -->

    <?php wp_footer(); ?>
</body>
</html>