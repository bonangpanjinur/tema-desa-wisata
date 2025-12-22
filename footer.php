<!-- Footer Section -->
    <footer class="bg-accent text-gray-300 pt-16 pb-8 border-t-4 border-secondary mt-auto">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                
                <!-- Kolom 1: Brand -->
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
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-red-600 hover:text-white transition"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-green-500 hover:text-white transition"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Kolom 2: Jelajahi -->
                <div>
                    <h3 class="text-white text-lg font-bold mb-6 border-b border-gray-700 pb-2 inline-block">Jelajahi</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="<?php echo home_url('/wisata'); ?>" class="hover:text-primary transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-gray-600"></i> Paket Wisata</a></li>
                        <li><a href="<?php echo home_url('/produk'); ?>" class="hover:text-primary transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-gray-600"></i> Produk UMKM</a></li>
                        <li><a href="<?php echo home_url('/agenda'); ?>" class="hover:text-primary transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-gray-600"></i> Agenda Desa</a></li>
                        <li><a href="<?php echo home_url('/galeri'); ?>" class="hover:text-primary transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-gray-600"></i> Galeri Foto</a></li>
                    </ul>
                </div>

                <!-- Kolom 3: Layanan -->
                <div>
                    <h3 class="text-white text-lg font-bold mb-6 border-b border-gray-700 pb-2 inline-block">Layanan</h3>
                    <ul class="space-y-3 text-sm">
                        <li><a href="<?php echo home_url('/register-desa-khusus'); ?>" class="hover:text-primary transition">Gabung Mitra Desa</a></li>
                        <li><a href="<?php echo home_url('/page-ojek'); ?>" class="hover:text-primary transition">Daftar Ojek Wisata</a></li>
                        <li><a href="<?php echo home_url('/faq'); ?>" class="hover:text-primary transition">Bantuan / FAQ</a></li>
                        <li><a href="<?php echo home_url('/syarat-ketentuan'); ?>" class="hover:text-primary transition">Syarat & Ketentuan</a></li>
                    </ul>
                </div>

                <!-- Kolom 4: Kontak -->
                <div>
                    <h3 class="text-white text-lg font-bold mb-6 border-b border-gray-700 pb-2 inline-block">Kontak Kami</h3>
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
            </div>

            <div class="border-t border-gray-800 pt-8 text-center md:text-left flex flex-col md:flex-row justify-between items-center text-xs text-gray-500">
                <p>&copy; <?php echo date('Y'); ?> <strong><?php bloginfo('name'); ?></strong>. Dibuat dengan bangga untuk Indonesia.</p>
                <div class="mt-4 md:mt-0 flex space-x-4">
                    <a href="#" class="hover:text-white">Privacy Policy</a>
                    <span class="text-gray-700">|</span>
                    <a href="#" class="hover:text-white">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>