<?php
/**
 * Template Name: Halaman Detail Wisata
 *
 * @package DesaWisataTheme
 */

get_header();

global $wpdb;
$table_wisata = $wpdb->prefix . 'dw_wisata';

// 1. Ambil ID dari URL
$wisata_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$wisata = null;

// 2. Query Data Detail
if ($wisata_id > 0 && $wpdb->get_var("SHOW TABLES LIKE '$table_wisata'") === $table_wisata) {
    $wisata = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_wisata WHERE id = %d", $wisata_id), ARRAY_A);
}

// Redirect atau Tampilkan 404 jika data tidak ditemukan
if (!$wisata) {
    echo '<div class="container mx-auto py-20 text-center">';
    echo '<h2 class="text-2xl font-bold mb-4">Wisata tidak ditemukan</h2>';
    echo '<a href="' . home_url('/wisata') . '" class="bg-emerald-600 text-white px-6 py-2 rounded-lg">Kembali ke Daftar Wisata</a>';
    echo '</div>';
    get_footer();
    exit;
}

// Sanitasi Data Utama
$nama = !empty($wisata['nama_wisata']) ? $wisata['nama_wisata'] : 'Wisata Tanpa Nama';
$deskripsi = !empty($wisata['deskripsi']) ? $wisata['deskripsi'] : 'Belum ada deskripsi.';
$harga = !empty($wisata['harga_tiket']) ? $wisata['harga_tiket'] : 0;
$lokasi = !empty($wisata['lokasi']) ? $wisata['lokasi'] : 'Lokasi belum diatur';
$gambar_utama = !empty($wisata['gambar']) ? $wisata['gambar'] : 'https://via.placeholder.com/1200x600?text=No+Image';

// Simulasi Data Tambahan (Bisa dikembangkan nanti dengan Custom Fields/Meta)
$jam_buka = "08:00 - 17:00 WIB";
$fasilitas = ['Area Parkir Luas', 'Toilet Bersih', 'Musholla', 'Spot Foto Instagramable', 'Warung Makan'];
$rating = 4.8;
$ulasan_count = 124;

?>

<!-- BREADCRUMB (Opsional) -->
<div class="bg-gray-100 py-3 border-b border-gray-200">
    <div class="container mx-auto px-4 text-sm text-gray-500">
        <a href="<?php echo home_url(); ?>" class="hover:text-emerald-600">Beranda</a> 
        <span class="mx-2">/</span> 
        <a href="<?php echo home_url('/wisata'); ?>" class="hover:text-emerald-600">Wisata</a> 
        <span class="mx-2">/</span> 
        <span class="text-gray-800 font-semibold"><?php echo esc_html($nama); ?></span>
    </div>
</div>

<!-- CONTENT WRAPPER -->
<div class="container mx-auto px-4 py-8 md:py-12">
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 md:gap-12">
        
        <!-- KOLOM KIRI: Galeri & Deskripsi -->
        <div class="lg:col-span-2 space-y-8">
            
            <!-- Hero Image / Galeri Utama -->
            <div class="rounded-2xl overflow-hidden shadow-lg border border-gray-100 bg-gray-200">
                <img src="<?php echo esc_url($gambar_utama); ?>" alt="<?php echo esc_attr($nama); ?>" class="w-full h-auto object-cover max-h-[500px]">
            </div>

            <!-- Judul & Info Singkat Mobile (Hanya muncul di HP) -->
            <div class="block lg:hidden">
                <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo esc_html($nama); ?></h1>
                <div class="flex items-center text-sm text-gray-600 mb-4">
                    <i class="fas fa-map-marker-alt text-red-500 mr-2"></i> <?php echo esc_html($lokasi); ?>
                </div>
            </div>

            <!-- Deskripsi Lengkap -->
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-emerald-600"></i> Tentang Destinasi
                </h2>
                <div class="prose prose-emerald max-w-none text-gray-600 leading-relaxed">
                    <?php echo wpautop(esc_html($deskripsi)); ?>
                </div>
            </div>

            <!-- Fasilitas -->
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-concierge-bell text-emerald-600"></i> Fasilitas Tersedia
                </h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($fasilitas as $item) : ?>
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span class="text-gray-700 font-medium"><?php echo $item; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Peta Lokasi (Placeholder / Embed GMaps) -->
            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-map-marked text-emerald-600"></i> Lokasi
                </h2>
                <div class="w-full h-64 bg-gray-200 rounded-xl overflow-hidden flex items-center justify-center relative">
                    <!-- Jika ada link GMaps di DB, bisa di-embed di sini -->
                    <div class="absolute inset-0 bg-[url('https://maps.googleapis.com/maps/api/staticmap?center=-7.123,110.123&zoom=13&size=600x300&maptype=roadmap')] bg-cover bg-center opacity-50"></div>
                    <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($lokasi); ?>" target="_blank" class="relative z-10 bg-white px-6 py-2 rounded-full shadow-lg font-bold text-blue-600 hover:bg-blue-50 transition">
                        <i class="fas fa-location-arrow mr-2"></i> Buka di Google Maps
                    </a>
                </div>
                <p class="mt-4 text-gray-600"><i class="fas fa-map-pin mr-2 text-gray-400"></i> <?php echo esc_html($lokasi); ?></p>
            </div>

        </div>

        <!-- KOLOM KANAN: Booking Card (Sticky) -->
        <div class="lg:col-span-1">
            <div class="sticky top-24 space-y-6">
                
                <!-- Card Pemesanan -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    <div class="p-6">
                        <!-- Judul Desktop -->
                        <div class="hidden lg:block border-b border-gray-100 pb-4 mb-4">
                            <h1 class="text-2xl font-bold text-gray-900 leading-tight mb-2"><?php echo esc_html($nama); ?></h1>
                            <div class="flex items-center gap-1 text-yellow-400 text-sm font-bold">
                                <i class="fas fa-star"></i> <?php echo $rating; ?> <span class="text-gray-400 font-normal ml-1">(<?php echo $ulasan_count; ?> ulasan)</span>
                            </div>
                        </div>

                        <!-- Harga -->
                        <div class="flex items-end gap-2 mb-6">
                            <span class="text-gray-500 text-sm mb-1">Mulai dari</span>
                            <span class="text-3xl font-bold text-emerald-600">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></span>
                            <span class="text-gray-400 text-sm mb-1">/orang</span>
                        </div>

                        <!-- Info Singkat -->
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500"><i class="far fa-clock mr-2"></i> Jam Buka</span>
                                <span class="font-medium text-gray-800"><?php echo $jam_buka; ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500"><i class="far fa-calendar-check mr-2"></i> Operasional</span>
                                <span class="font-medium text-gray-800">Setiap Hari</span>
                            </div>
                        </div>

                        <!-- Tombol Aksi -->
                        <div class="space-y-3">
                            <!-- Add to Cart (Direct Link) -->
                            <a href="<?php echo esc_url(add_query_arg(['add-to-cart' => $wisata_id, 'type' => 'wisata'], home_url('/cart'))); ?>" class="block w-full bg-emerald-600 hover:bg-emerald-700 text-white text-center font-bold py-3 rounded-xl transition shadow-md hover:shadow-lg transform active:scale-95">
                                Pesan Tiket Sekarang
                            </a>
                            
                            <!-- WhatsApp -->
                            <a href="https://wa.me/6281234567890?text=Halo,%20saya%20ingin%20bertanya%20tentang%20wisata%20<?php echo urlencode($nama); ?>" target="_blank" class="block w-full bg-white border-2 border-emerald-600 text-emerald-600 hover:bg-emerald-50 text-center font-bold py-3 rounded-xl transition">
                                <i class="fab fa-whatsapp mr-1"></i> Tanya Admin
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-4 text-center text-xs text-gray-500 border-t border-gray-100">
                        <i class="fas fa-shield-alt mr-1"></i> Transaksi Aman & Terpercaya
                    </div>
                </div>

                <!-- Banner Promo Kecil (Opsional) -->
                <div class="bg-gradient-to-r from-orange-400 to-red-500 rounded-2xl p-6 text-white shadow-md relative overflow-hidden">
                    <div class="relative z-10">
                        <h3 class="font-bold text-lg mb-1">Diskon Rombongan!</h3>
                        <p class="text-sm opacity-90 mb-3">Dapatkan potongan harga khusus untuk kunjungan > 20 orang.</p>
                        <button class="bg-white text-red-500 text-xs font-bold px-3 py-1.5 rounded-lg">Info Selengkapnya</button>
                    </div>
                    <i class="fas fa-users absolute -bottom-4 -right-4 text-6xl opacity-20"></i>
                </div>

            </div>
        </div>

    </div>

    <!-- RELATED SECTION: Wisata Lainnya -->
    <div class="mt-16 border-t border-gray-200 pt-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Jelajahi Wisata Lainnya</h2>
        
        <?php
        // Query Wisata Lain (Random 3 item, kecuali yang sedang dibuka)
        $related_wisata = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $table_wisata WHERE id != %d ORDER BY RAND() LIMIT 3", $wisata_id),
            ARRAY_A
        );

        if (!empty($related_wisata)) :
        ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($related_wisata as $rel) : 
                $rel_img = !empty($rel['gambar']) ? $rel['gambar'] : 'https://via.placeholder.com/600x400?text=No+Image';
                $rel_link = home_url('/detail-wisata/?id=' . $rel['id']);
            ?>
            <a href="<?php echo esc_url($rel_link); ?>" class="group block bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                <div class="h-40 overflow-hidden bg-gray-200 relative">
                    <img src="<?php echo esc_url($rel_img); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    <div class="absolute inset-0 bg-black/10 group-hover:bg-transparent transition"></div>
                </div>
                <div class="p-4">
                    <h4 class="font-bold text-gray-800 mb-1 truncate"><?php echo esc_html($rel['nama_wisata']); ?></h4>
                    <p class="text-emerald-600 text-sm font-bold">Rp <?php echo number_format((float)$rel['harga_tiket'], 0, ',', '.'); ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <p class="text-gray-500">Belum ada wisata lain.</p>
        <?php endif; ?>
    </div>

</div>

<?php get_footer(); ?>