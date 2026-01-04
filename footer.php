<!-- Main Footer (Desktop Focused, Simplified on Mobile) -->
    <footer class="bg-gray-900 text-gray-300 pt-16 pb-24 md:pb-8 border-t-4 border-primary mt-auto hidden md:block">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                
                <!-- Brand -->
                <div class="space-y-4">
                    <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-leaf text-primary"></i> <?php bloginfo('name'); ?>
                    </h2>
                    <p class="text-sm leading-relaxed text-gray-400">
                        Platform Desa Wisata terintegrasi yang menghubungkan potensi lokal, alam yang indah, dan produk UMKM kreatif kepada dunia.
                    </p>
                    <div class="flex space-x-4 pt-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary hover:text-white transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-pink-600 hover:text-white transition"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-green-500 hover:text-white transition"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Link Cepat -->
                <div>
                    <h3 class="text-white font-bold text-lg mb-6 border-b border-gray-700 pb-2 inline-block">Jelajahi</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="<?php echo home_url('/wisata'); ?>" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> Destinasi Wisata</a></li>
                        <li><a href="<?php echo home_url('/produk'); ?>" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> Produk UMKM</a></li>
                        <li><a href="<?php echo home_url('/blog'); ?>" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> Berita Desa</a></li>
                        <li><a href="<?php echo home_url('/tentang'); ?>" class="hover:text-primary transition flex items-center gap-2"><i class="fas fa-chevron-right text-[10px] text-primary"></i> Tentang Kami</a></li>
                    </ul>
                </div>

                <!-- Kontak -->
                <div>
                    <h3 class="text-white font-bold text-lg mb-6 border-b border-gray-700 pb-2 inline-block">Hubungi Kami</h3>
                    <ul class="space-y-4 text-sm">
                        <li class="flex items-start">
                            <i class="fas fa-map-marked-alt mt-1 mr-3 text-primary"></i>
                            <span class="text-gray-400">Kantor Kepala Desa Sejahtera, Kec. Alam Indah, Kab. Wisata, Indonesia 40123</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt mr-3 text-primary"></i>
                            <span class="text-gray-400">+62 812-3456-7890</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-primary"></i>
                            <span class="text-gray-400">halo@desawisata.id</span>
                        </li>
                    </ul>
                </div>
                
                <!-- Newsletter -->
                <div>
                    <h3 class="text-white font-bold text-lg mb-6 border-b border-gray-700 pb-2 inline-block">Info Terbaru</h3>
                    <p class="text-xs text-gray-400 mb-4">Dapatkan update promo wisata dan produk terbaru.</p>
                    <form class="flex">
                        <input type="email" placeholder="Email Anda" class="w-full px-4 py-2 bg-gray-800 border border-gray-700 rounded-l-lg focus:outline-none focus:border-primary text-sm">
                        <button class="bg-primary hover:bg-primaryDark px-4 py-2 rounded-r-lg text-white font-bold transition"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 text-center md:text-left flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> <strong><?php bloginfo('name'); ?></strong>. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>

    <!-- MOBILE BOTTOM NAVIGATION BAR (App Like Experience) -->
    <!-- Fixed di bawah, hanya muncul di layar < 768px (md) -->
    <nav class="fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] z-50 md:hidden pb-safe">
        <div class="grid grid-cols-5 h-[60px] items-end pb-1">
            
            <!-- 1. Beranda -->
            <a href="<?php echo home_url(); ?>" class="flex flex-col items-center justify-center h-full w-full group <?php echo is_front_page() ? 'text-primary' : 'text-gray-400 hover:text-gray-600'; ?>">
                <div class="mb-0.5 relative">
                    <i class="fas fa-home text-lg transition-transform group-active:scale-90"></i>
                    <?php if(is_front_page()): ?>
                        <span class="absolute -top-1 -right-1 w-1.5 h-1.5 bg-primary rounded-full border border-white"></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] font-medium leading-none">Beranda</span>
            </a>

            <!-- 2. Wisata -->
            <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center justify-center h-full w-full group <?php echo is_post_type_archive('dw_wisata') || is_singular('dw_wisata') ? 'text-primary' : 'text-gray-400 hover:text-gray-600'; ?>">
                <div class="mb-0.5">
                    <i class="fas fa-map-marked-alt text-lg transition-transform group-active:scale-90"></i>
                </div>
                <span class="text-[10px] font-medium leading-none">Wisata</span>
            </a>

            <!-- 3. Produk (Tengah - Menonjol & Rapi) -->
            <div class="relative h-full flex justify-center items-end">
                <a href="<?php echo home_url('/produk'); ?>" class="absolute bottom-4 flex flex-col items-center justify-center group">
                    <div class="w-12 h-12 bg-primary rounded-full shadow-lg shadow-primary/40 text-white flex items-center justify-center transform transition-transform group-active:scale-95 border-4 border-white box-content">
                        <i class="fas fa-shopping-basket text-lg"></i>
                    </div>
                    <span class="text-[10px] font-medium mt-1 leading-none text-gray-500 <?php echo is_post_type_archive('dw_produk') ? 'text-primary font-bold' : ''; ?>">Produk</span>
                </a>
            <            <!-- 4. Favorit -->
            <?php 
            $fav_link = is_user_logged_in() ? home_url('/akun-saya?tab=favorites') : home_url('/login');
            ?>
            <a href="<?php echo $fav_link; ?>" class="flex flex-col items-center justify-center h-full w-full group <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'favorites') ? 'text-primary' : 'text-gray-400 hover:text-gray-600'; ?>">
                <div class="mb-0.5">
                    <i class="fas fa-heart text-lg transition-transform group-active:scale-90"></i>
                </div>
                <span class="text-[10px] font-medium leading-none">Favorit</span>
            </a>- 5. Akun -->
            <?php 
            $akun_link = is_user_logged_in() ? home_url('/akun-saya') : home_url('/login');
            $is_akun_active = is_page('akun-saya') || is_page('login') || is_page('dashboard-desa') || is_page('dashboard-toko');
            ?>
            <a href="<?php echo $akun_link; ?>" class="flex flex-col items-center justify-center h-full w-full group <?php echo $is_akun_active ? 'text-primary' : 'text-gray-400 hover:text-gray-600'; ?>">
                <div class="mb-0.5">
                    <i class="fas fa-user text-lg transition-transform group-active:scale-90"></i>
                </div>
                <span class="text-[10px] font-medium leading-none">Akun</span>
            </a>

        </div>
    </nav>

<?php wp_footer(); ?>
</body>
</html>