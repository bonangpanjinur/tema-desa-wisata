<?php
/**
 * Footer Template
 * Description: Menampilkan footer desktop dan navigasi mobile sticky.
 */
?>

    <!-- =========================================
         FOOTER DESKTOP (Hidden di Mobile)
         ========================================= -->
    <footer class="bg-white border-t border-gray-100 pt-16 pb-32 md:pb-10 mt-auto hidden md:block">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <!-- Kolom 1: Info -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-gradient-to-tr from-orange-500 to-amber-400 rounded-lg flex items-center justify-center text-white shadow-md">
                            <i class="fas fa-store text-sm"></i>
                        </div>
                        <!-- NAMA WEBSITE DINAMIS -->
                        <span class="font-bold text-xl tracking-tight"><?php echo get_bloginfo('name'); ?></span>
                    </div>
                    <p class="text-gray-500 text-sm leading-relaxed">
                        Platform digital yang menghubungkan potensi UMKM desa dengan pasar nasional. Belanja produk asli desa, dukung ekonomi lokal.
                    </p>
                    <div class="flex gap-3 pt-2">
                        <a href="#" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-orange-100 hover:text-orange-600 transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-orange-100 hover:text-orange-600 transition"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-orange-100 hover:text-orange-600 transition"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Kolom 2: Tautan -->
                <div>
                    <h4 class="font-bold text-gray-900 mb-4">Jelajahi</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li><a href="<?php echo home_url('/wisata'); ?>" class="hover:text-orange-600 transition">Destinasi Wisata</a></li>
                        <li><a href="<?php echo home_url('/produk'); ?>" class="hover:text-orange-600 transition">Produk UMKM</a></li>
                        <li><a href="<?php echo home_url('/desa'); ?>" class="hover:text-orange-600 transition">Profil Desa</a></li>
                        <li><a href="<?php echo home_url('/tentang'); ?>" class="hover:text-orange-600 transition">Tentang Kami</a></li>
                    </ul>
                </div>

                <!-- Kolom 3: Bantuan -->
                <div>
                    <h4 class="font-bold text-gray-900 mb-4">Bantuan</h4>
                    <ul class="space-y-2 text-sm text-gray-500">
                        <li><a href="<?php echo home_url('/cara-belanja'); ?>" class="hover:text-orange-600 transition">Cara Belanja</a></li>
                        <li><a href="<?php echo home_url('/konfirmasi-pembayaran'); ?>" class="hover:text-orange-600 transition">Konfirmasi Pembayaran</a></li>
                        <li><a href="<?php echo home_url('/syarat-ketentuan'); ?>" class="hover:text-orange-600 transition">Syarat & Ketentuan</a></li>
                        <li><a href="<?php echo home_url('/hubungi-kami'); ?>" class="hover:text-orange-600 transition">Hubungi Admin</a></li>
                    </ul>
                </div>

                <!-- Kolom 4: Pembayaran -->
                <div>
                    <h4 class="font-bold text-gray-900 mb-4">Metode Pembayaran</h4>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="h-8 bg-gray-50 border border-gray-100 rounded flex items-center justify-center"><i class="fas fa-university text-gray-400"></i></div>
                        <div class="h-8 bg-gray-50 border border-gray-100 rounded flex items-center justify-center"><i class="fas fa-qrcode text-gray-400"></i></div>
                        <div class="h-8 bg-gray-50 border border-gray-100 rounded flex items-center justify-center"><i class="fas fa-wallet text-gray-400"></i></div>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-100 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-xs text-gray-400">&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. Hak Cipta Dilindungi.</p>
                <p class="text-xs text-gray-400">Dibuat dengan <i class="fas fa-heart text-red-400 mx-1"></i> untuk Desa Indonesia</p>
            </div>
        </div>
    </footer>

    <!-- =========================================
         MOBILE BOTTOM NAVIGATION (Sticky)
         ========================================= -->
    <div class="md:hidden fixed bottom-0 left-0 w-full bg-white border-t border-gray-200 z-50 pb-safe shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        <div class="grid grid-cols-5 h-[60px] items-center text-[10px] font-medium text-gray-400">
            
            <!-- 1. Beranda -->
            <a href="<?php echo home_url(); ?>" class="flex flex-col items-center justify-center h-full space-y-1 <?php echo is_front_page() ? 'text-orange-600' : 'hover:text-gray-600'; ?>">
                <i class="fas fa-home text-lg <?php echo is_front_page() ? 'animate-bounce-short' : ''; ?>"></i>
                <span>Beranda</span>
            </a>

            <!-- 2. Wisata -->
            <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center justify-center h-full space-y-1 <?php echo is_post_type_archive('dw_wisata') ? 'text-orange-600' : 'hover:text-gray-600'; ?>">
                <i class="fas fa-map-marked-alt text-lg"></i>
                <span>Wisata</span>
            </a>

            <!-- 3. PRODUK (CENTER FLOATING BUTTON) -->
            <div class="relative -top-5">
                <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center justify-center w-14 h-14 bg-gradient-to-br from-orange-500 to-orange-600 rounded-full shadow-lg shadow-orange-200 text-white transform transition-all active:scale-95 border-[4px] border-gray-50 absolute left-1/2 -translate-x-1/2">
                    <i class="fas fa-box-open text-xl mb-0.5"></i>
                </a>
                <span class="absolute -bottom-6 left-1/2 -translate-x-1/2 w-full text-center text-orange-600 font-bold">Produk</span>
            </div>

            <!-- 4. Transaksi -->
            <a href="<?php echo home_url('/transaksi'); ?>" class="flex flex-col items-center justify-center h-full space-y-1 <?php echo is_page('transaksi') ? 'text-orange-600' : 'hover:text-gray-600'; ?>">
                <i class="fas fa-receipt text-lg"></i>
                <span>Transaksi</span>
            </a>

            <!-- 5. Akun -->
            <a href="<?php echo is_user_logged_in() ? home_url('/akun-saya') : home_url('/login'); ?>" class="flex flex-col items-center justify-center h-full space-y-1 <?php echo is_page(['akun-saya', 'login']) ? 'text-orange-600' : 'hover:text-gray-600'; ?>">
                <i class="fas fa-user text-lg"></i>
                <span>Akun</span>
            </a>

        </div>
    </div>

    <!-- Tambahan Style untuk Safe Area di iPhone X+ -->
    <style>
        .pb-safe { padding-bottom: env(safe-area-inset-bottom); }
        @keyframes bounce-short {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        .animate-bounce-short { animation: bounce-short 0.5s ease-in-out 1; }
    </style>

    <?php wp_footer(); ?>
</body>
</html>