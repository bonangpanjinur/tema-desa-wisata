<?php get_header(); ?>

<div class="min-h-[70vh] flex items-center justify-center bg-gray-50">
    <div class="text-center px-4">
        <div class="text-9xl font-bold text-primary opacity-20">404</div>
        <h1 class="text-4xl font-bold text-gray-800 -mt-12 mb-4 relative z-10">Halaman Tidak Ditemukan</h1>
        <p class="text-gray-500 text-lg mb-8 max-w-md mx-auto">Maaf, halaman yang Anda cari mungkin telah dihapus, dipindahkan, atau tidak tersedia.</p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?php echo home_url(); ?>" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition font-medium">
                Kembali ke Beranda
            </a>
            <a href="<?php echo home_url('/produk'); ?>" class="bg-white text-gray-700 border border-gray-300 px-6 py-3 rounded-lg hover:bg-gray-50 transition font-medium">
                Belanja Produk
            </a>
        </div>
    </div>
</div>

<?php get_footer(); ?>