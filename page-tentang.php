<?php
/**
 * Template Name: Halaman Tentang
 */

get_header(); ?>

<!-- Hero Section -->
<div class="bg-gray-900 py-24 relative overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <img src="https://images.unsplash.com/photo-1533038590840-1cde6e668a91?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80" class="w-full h-full object-cover">
    </div>
    <div class="container mx-auto px-4 relative z-10 text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">Membangun Desa, <br>Menghidupkan Ekonomi</h1>
        <p class="text-xl text-gray-300 max-w-2xl mx-auto">Platform digital yang menghubungkan potensi desa wisata Indonesia dengan dunia.</p>
    </div>
</div>

<!-- Misi Kami -->
<div class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <img src="https://images.unsplash.com/photo-1596422846543-75c6fc197f07?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" class="rounded-2xl shadow-xl w-full">
            </div>
            <div>
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Misi Kami</h2>
                <p class="text-gray-600 mb-4 leading-relaxed">
                    Kami percaya bahwa setiap desa memiliki cerita unik dan potensi luar biasa. Misi kami adalah memberdayakan masyarakat desa melalui teknologi, membuka akses pasar yang lebih luas untuk produk UMKM dan destinasi wisata lokal.
                </p>
                <p class="text-gray-600 mb-6 leading-relaxed">
                    Dengan platform ini, kami menjembatani kesenjangan digital, memungkinkan wisatawan untuk menemukan keaslian budaya Indonesia sekaligus berkontribusi langsung pada kesejahteraan warga desa.
                </p>
                
                <div class="grid grid-cols-2 gap-6 mt-8">
                    <div>
                        <h4 class="text-4xl font-bold text-primary mb-1">100+</h4>
                        <p class="text-sm text-gray-500">Desa Terdaftar</p>
                    </div>
                    <div>
                        <h4 class="text-4xl font-bold text-primary mb-1">500+</h4>
                        <p class="text-sm text-gray-500">Produk UMKM</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tim Kami (Opsional) -->
<div class="py-20 bg-gray-50">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold text-gray-800 mb-12">Di Balik Layar</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
            <div class="bg-white p-6 rounded-xl shadow-sm hover:shadow-md transition">
                <div class="w-24 h-24 bg-gray-200 rounded-full mx-auto mb-4 overflow-hidden">
                    <img src="https://ui-avatars.com/api/?name=Bonang+Panji&background=16a34a&color=fff" class="w-full h-full object-cover">
                </div>
                <h3 class="font-bold text-gray-800">Bonang Panji</h3>
                <p class="text-sm text-primary font-medium">Founder & Developer</p>
            </div>
            <!-- Tambah tim lain di sini -->
        </div>
    </div>
</div>

<!-- Kontak -->
<div class="py-20 bg-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold text-gray-800 mb-8">Ingin Berkolaborasi?</h2>
        <p class="text-gray-600 mb-8 max-w-xl mx-auto">Apakah Anda pengelola desa wisata atau ingin menjadi mitra kami? Jangan ragu untuk menghubungi kami.</p>
        <a href="mailto:info@desawisata.com" class="inline-flex items-center gap-2 bg-primary text-white px-8 py-3 rounded-full hover:bg-secondary transition font-bold shadow-lg">
            <i class="fas fa-envelope"></i> Hubungi Kami
        </a>
    </div>
</div>

<?php get_footer(); ?>