<footer class="bg-dark text-white pt-16 pb-8">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
            <!-- Brand Info -->
            <div class="col-span-1 md:col-span-1">
                <h3 class="text-2xl font-bold mb-4 text-white">Desa<span class="text-accent">Wisata</span></h3>
                <p class="text-gray-400 text-sm leading-relaxed mb-4">
                    Platform marketplace terintegrasi yang menghubungkan potensi desa wisata dan produk UMKM lokal langsung ke wisatawan.
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center hover:bg-primary transition"><i class="fab fa-facebook-f text-sm"></i></a>
                    <a href="#" class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center hover:bg-primary transition"><i class="fab fa-instagram text-sm"></i></a>
                    <a href="#" class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center hover:bg-primary transition"><i class="fab fa-youtube text-sm"></i></a>
                </div>
            </div>

            <!-- Links -->
            <div>
                <h4 class="text-lg font-semibold mb-4 text-white">Jelajahi</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="<?php echo home_url('/wisata'); ?>" class="hover:text-primary transition">Destinasi Wisata</a></li>
                    <li><a href="<?php echo home_url('/produk'); ?>" class="hover:text-primary transition">Belanja Ole-Oleh</a></li>
                    <li><a href="<?php echo home_url('/paket-wisata'); ?>" class="hover:text-primary transition">Paket Wisata</a></li>
                    <li><a href="<?php echo home_url('/blog'); ?>" class="hover:text-primary transition">Berita Desa</a></li>
                </ul>
            </div>

            <!-- Links -->
            <div>
                <h4 class="text-lg font-semibold mb-4 text-white">Dukungan</h4>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li><a href="#" class="hover:text-primary transition">Cara Pesan</a></li>
                    <li><a href="#" class="hover:text-primary transition">Metode Pembayaran</a></li>
                    <li><a href="#" class="hover:text-primary transition">Daftar Sebagai Mitra</a></li>
                    <li><a href="#" class="hover:text-primary transition">Syarat & Ketentuan</a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div>
                <h4 class="text-lg font-semibold mb-4 text-white">Hubungi Kami</h4>
                <ul class="space-y-2 text-sm text-gray-400 mb-4">
                    <li class="flex items-start gap-3"><i class="fas fa-map-marker-alt mt-1 text-primary"></i> <span>Jl. Desa Wisata No. 1, Indonesia</span></li>
                    <li class="flex items-center gap-3"><i class="fas fa-envelope text-primary"></i> <span>info@desawisata.com</span></li>
                    <li class="flex items-center gap-3"><i class="fas fa-phone text-primary"></i> <span>+62 812 3456 7890</span></li>
                </ul>
            </div>
        </div>

        <div class="border-t border-gray-700 pt-8 flex flex-col md:flex-row justify-between items-center text-sm text-gray-500">
            <p>&copy; <?php echo date('Y'); ?> Desa Wisata Core. All rights reserved.</p>
            <p>Built with <i class="fas fa-heart text-red-500"></i> by BonangPanjiNur</p>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>