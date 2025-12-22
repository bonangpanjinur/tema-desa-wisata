<footer class="bg-gray-800 text-gray-300 py-12 mt-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Kolom 1: Info Desa -->
                <div>
                    <h3 class="text-white text-lg font-bold mb-4">Desa Wisata</h3>
                    <p class="text-sm leading-relaxed mb-4">
                        Nikmati keindahan alam, budaya, dan produk lokal asli dari desa kami. 
                        Dukung ekonomi lokal dengan berbelanja produk asli desa.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-gray-400 hover:text-white transition"><i class="fab fa-youtube fa-lg"></i></a>
                    </div>
                </div>

                <!-- Kolom 2: Tautan Cepat -->
                <div>
                    <h3 class="text-white text-lg font-bold mb-4">Tautan</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="<?php echo home_url('/wisata'); ?>" class="hover:text-primary transition">Paket Wisata</a></li>
                        <li><a href="<?php echo home_url('/produk'); ?>" class="hover:text-primary transition">Produk UMKM</a></li>
                        <li><a href="<?php echo home_url('/register-desa-khusus'); ?>" class="hover:text-primary transition">Gabung Mitra</a></li>
                        <li><a href="<?php echo home_url('/page-ojek'); ?>" class="hover:text-primary transition">Ojek Wisata</a></li>
                    </ul>
                </div>

                <!-- Kolom 3: Kontak -->
                <div>
                    <h3 class="text-white text-lg font-bold mb-4">Hubungi Kami</h3>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-primary"></i>
                            <span>Jl. Raya Desa No. 123, Kabupaten Sejahtera, Indonesia</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-3 text-primary"></i>
                            <span>+62 812-3456-7890</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-primary"></i>
                            <span>info@desawisata.com</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 mt-10 pt-6 text-center text-sm">
                <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>