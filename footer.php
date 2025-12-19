<?php
/**
 * Footer Template (Clean & Professional)
 * Description: Footer desktop yang rapi dengan kredit Bonang dan navigasi mobile sticky.
 */
?>

    <!-- =========================================
         1. FOOTER DESKTOP (Hidden di Mobile)
         ========================================= -->
    <footer class="bg-slate-50 border-t border-slate-200 pt-16 pb-32 md:pb-8 mt-auto hidden md:block text-slate-600">
        <div class="container mx-auto px-4">
            
            <!-- GRID UTAMA -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-10 mb-12">
                
                <!-- KOLOM 1: Brand & Info (Lebar sedikit lebih besar) -->
                <div class="lg:col-span-1 space-y-5">
                    <div class="flex items-center gap-3">
                        <!-- Logo Icon -->
                        <div class="w-10 h-10 bg-gradient-to-br from-green-600 to-emerald-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-green-200/50">
                            <i class="fas fa-leaf text-lg"></i>
                        </div>
                        <!-- Logo Text -->
                        <div>
                            <span class="font-extrabold text-xl tracking-tight text-slate-800 block leading-none"><?php echo get_bloginfo('name'); ?></span>
                            <span class="text-[10px] font-bold text-green-600 uppercase tracking-widest">Desa Wisata</span>
                        </div>
                    </div>

                    <p class="text-sm leading-relaxed text-slate-500 pr-4">
                        Platform digital resmi desa untuk mempromosikan pariwisata lokal dan produk UMKM unggulan ke pasar yang lebih luas.
                    </p>

                    <!-- Sosmed Icons (Minimalis) -->
                    <div class="flex gap-3">
                        <a href="#" class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all">
                            <i class="fab fa-facebook-f text-xs"></i>
                        </a>
                        <a href="#" class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-pink-600 hover:text-white hover:border-pink-600 transition-all">
                            <i class="fab fa-instagram text-xs"></i>
                        </a>
                        <a href="#" class="w-8 h-8 rounded-full bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:bg-green-600 hover:text-white hover:border-green-600 transition-all">
                            <i class="fab fa-whatsapp text-xs"></i>
                        </a>
                    </div>
                </div>

                <!-- KOLOM 2: Navigasi Cepat -->
                <div>
                    <h4 class="font-bold text-slate-800 mb-5 text-sm uppercase tracking-wider">Jelajahi</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="<?php echo home_url('/wisata'); ?>" class="hover:text-green-600 hover:translate-x-1 transition-all inline-block">Destinasi Wisata</a></li>
                        <li><a href="<?php echo home_url('/produk'); ?>" class="hover:text-green-600 hover:translate-x-1 transition-all inline-block">Produk UMKM</a></li>
                        <li><a href="<?php echo home_url('/ojek'); ?>" class="hover:text-green-600 hover:translate-x-1 transition-all inline-block">Layanan Ojek</a></li>
                        <li><a href="<?php echo home_url('/blog'); ?>" class="hover:text-green-600 hover:translate-x-1 transition-all inline-block">Kabar Desa</a></li>
                    </ul>
                </div>

                <!-- KOLOM 3: Bantuan -->
                <div>
                    <h4 class="font-bold text-slate-800 mb-5 text-sm uppercase tracking-wider">Bantuan</h4>
                    <ul class="space-y-3 text-sm">
                        <li><a href="<?php echo home_url('/cara-belanja'); ?>" class="hover:text-green-600 hover:translate-x-1 transition-all inline-block">Cara Belanja</a></li>
                        <li><a href="<?php echo home_url('/konfirmasi'); ?>" class="hover:text-green-600 hover:translate-x-1 transition-all inline-block">Konfirmasi Bayar</a></li>
                        <li><a href="<?php echo home_url('/syarat'); ?>" class="hover:text-green-600 hover:translate-x-1 transition-all inline-block">Syarat & Ketentuan</a></li>
                        <li><a href="<?php echo home_url('/kontak'); ?>" class="hover:text-green-600 hover:translate-x-1 transition-all inline-block">Hubungi Kami</a></li>
                    </ul>
                </div>

                <!-- KOLOM 4: Newsletter & Credit -->
                <div>
                    <h4 class="font-bold text-slate-800 mb-5 text-sm uppercase tracking-wider">Info Terbaru</h4>
                    <form class="space-y-2 mb-6">
                        <input type="email" placeholder="Email Anda..." class="w-full bg-white border border-slate-200 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-green-500 focus:ring-2 focus:ring-green-100 transition-all">
                        <button type="button" class="w-full bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-green-600 transition-colors">
                            Langganan
                        </button>
                    </form>
                </div>
            </div>

            <!-- COPYRIGHT BAR (Rapi & Bersih) -->
            <div class="border-t border-slate-200 pt-8 flex flex-col md:flex-row justify-between items-center gap-4 text-xs font-medium text-slate-400">
                
                <!-- Kiri: Copyright -->
                <p>&copy; <?php echo date('Y'); ?> <strong><?php bloginfo('name'); ?></strong>. Hak Cipta Dilindungi.</p>
                
                <!-- Kanan: Credit Bonang -->
                <div class="flex items-center gap-1">
                    <span>Dibuat dengan <i class="fas fa-heart text-red-400 mx-0.5 animate-pulse"></i> oleh</span>
                    <a href="https://bonang.my.id" target="_blank" class="text-green-600 hover:text-green-700 font-bold hover:underline transition-all">
                        Bonang
                    </a>
                </div>

            </div>
        </div>
    </footer>

    <!-- =========================================
         2. MOBILE BOTTOM NAVIGATION (Sticky)
         ========================================= -->
    <?php
    // Helper function untuk cek menu aktif
    function is_nav_active($slug) {
        $uri = $_SERVER['REQUEST_URI'];
        if ($slug == '/') return $uri == '/' || $uri == '/home';
        return strpos($uri, $slug) !== false;
    }

    $nav_base   = "flex flex-col items-center justify-center h-full w-full pt-1 transition-colors duration-200";
    $nav_active = "text-green-600 font-semibold";
    $nav_idle   = "text-slate-400 hover:text-slate-600 font-medium";
    ?>

    <div class="md:hidden fixed bottom-0 left-0 w-full bg-white/95 backdrop-blur-md border-t border-slate-200 z-[999] pb-safe shadow-[0_-4px_20px_-5px_rgba(0,0,0,0.05)]">
        <div class="grid grid-cols-5 h-[65px] items-end text-[10px]">
            
            <!-- 1. Beranda -->
            <a href="<?php echo home_url(); ?>" class="<?php echo $nav_base; ?> <?php echo is_front_page() ? $nav_active : $nav_idle; ?>">
                <div class="relative mb-1">
                    <i class="fas fa-home text-xl <?php echo is_front_page() ? 'animate-bounce-short' : ''; ?>"></i>
                </div>
                <span class="mb-2">Beranda</span>
            </a>

            <!-- 2. Wisata -->
            <a href="<?php echo home_url('/wisata'); ?>" class="<?php echo $nav_base; ?> <?php echo is_nav_active('/wisata') ? $nav_active : $nav_idle; ?>">
                <div class="relative mb-1">
                    <i class="fas fa-map-marked-alt text-xl"></i>
                </div>
                <span class="mb-2">Wisata</span>
            </a>

            <!-- 3. PRODUK (Tengah - Menonjol - FIX ALIGNMENT) -->
            <!-- Container khusus agar tombol dan teks sejajar secara vertikal -->
            <div class="relative h-full w-full flex flex-col items-center justify-end">
                <!-- Tombol Floating -->
                <a href="<?php echo home_url('/produk'); ?>" class="absolute -top-6 left-1/2 -translate-x-1/2 flex items-center justify-center w-14 h-14 bg-green-600 rounded-full shadow-lg shadow-green-200 text-white transform transition-all active:scale-95 border-[4px] border-slate-50 z-10 group">
                    <i class="fas fa-store text-xl group-hover:scale-110 transition-transform"></i>
                </a>
                
                <!-- Teks Label (Posisinya sekarang diatur fixed di bawah agar sejajar dengan menu lain) -->
                <a href="<?php echo home_url('/produk'); ?>" class="mb-2 text-[10px] font-bold <?php echo is_nav_active('/produk') ? 'text-green-600' : 'text-slate-500'; ?>">
                    Produk
                </a>
            </div>

            <!-- 4. Ojek -->
            <a href="<?php echo home_url('/ojek'); ?>" class="<?php echo $nav_base; ?> <?php echo is_nav_active('/ojek') ? 'text-orange-500 font-semibold' : $nav_idle; ?>">
                <div class="relative mb-1">
                    <i class="fas fa-motorcycle text-xl"></i>
                    <?php if(!is_nav_active('/ojek')): ?>
                        <span class="absolute top-0 -right-1.5 w-2 h-2 bg-red-500 rounded-full border border-white"></span>
                    <?php endif; ?>
                </div>
                <span class="mb-2">Ojek</span>
            </a>

            <!-- 5. Akun -->
            <a href="<?php echo is_user_logged_in() ? home_url('/akun-saya') : home_url('/login'); ?>" class="<?php echo $nav_base; ?> <?php echo is_nav_active('/akun-saya') || is_nav_active('/login') ? $nav_active : $nav_idle; ?>">
                <div class="relative mb-1">
                    <i class="fas fa-user-circle text-xl"></i>
                </div>
                <span class="mb-2">Akun</span>
            </a>

        </div>
    </div>

    <!-- Style Tambahan -->
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