<?php
/**
 * Template Name: Halaman Wisata
 *
 * @package DesaWisataTheme
 */

get_header();

global $wpdb;
$table_wisata = $wpdb->prefix . 'dw_wisata';

// --- KONFIGURASI PAGINASI ---
$items_per_page = 9;
$current_page = max(1, get_query_var('paged'));
$offset = ($current_page - 1) * $items_per_page;

// --- QUERY DATA WISATA ---
// 1. Hitung total items untuk paginasi
$total_items = 0;
$wisata_list = [];

// Cek apakah tabel wisata ada
if ($wpdb->get_var("SHOW TABLES LIKE '$table_wisata'") === $table_wisata) {
    $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_wisata");
    
    // 2. Ambil data wisata dengan limit & offset
    // Asumsi kolom: id, nama_wisata, deskripsi, harga_tiket, lokasi, gambar
    $wisata_list = $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_wisata ORDER BY id DESC LIMIT %d OFFSET %d", $items_per_page, $offset),
        ARRAY_A
    );
}

// Hitung total halaman
$total_pages = ceil($total_items / $items_per_page);

?>

<!-- HERO SECTION -->
<div class="relative bg-emerald-600 py-16 md:py-24 overflow-hidden">
    <div class="absolute inset-0 bg-[url('https://images.unsplash.com/photo-1596423736798-75b43694f540?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80')] bg-cover bg-center mix-blend-overlay opacity-30"></div>
    <div class="container mx-auto px-4 relative z-10 text-center text-white">
        <h1 class="text-3xl md:text-5xl font-bold mb-4">Destinasi Wisata</h1>
        <p class="text-lg md:text-xl opacity-90 max-w-2xl mx-auto">Jelajahi keindahan alam, budaya, dan pengalaman tak terlupakan di desa kami.</p>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="container mx-auto px-4 py-12">
    
    <!-- Filter / Search (Opsional - Tampilan Saja) -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div class="text-gray-600">
            Menampilkan <strong><?php echo count($wisata_list); ?></strong> dari <strong><?php echo $total_items; ?></strong> destinasi
        </div>
        <!-- Bisa dikembangkan nanti untuk fitur pencarian -->
        <!-- 
        <div class="relative w-full md:w-64">
            <input type="text" placeholder="Cari wisata..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:border-emerald-500">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
        -->
    </div>

    <!-- GRID WISATA -->
    <?php if (!empty($wisata_list)) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($wisata_list as $wisata) : 
                // Sanitasi Data
                $id = $wisata['id'];
                $nama = !empty($wisata['nama_wisata']) ? $wisata['nama_wisata'] : 'Wisata Tanpa Nama';
                $lokasi = !empty($wisata['lokasi']) ? $wisata['lokasi'] : 'Lokasi tidak tersedia';
                $harga = !empty($wisata['harga_tiket']) ? $wisata['harga_tiket'] : 0;
                $gambar = !empty($wisata['gambar']) ? $wisata['gambar'] : 'https://via.placeholder.com/600x400?text=No+Image';
                $deskripsi = !empty($wisata['deskripsi']) ? wp_trim_words($wisata['deskripsi'], 15, '...') : '';
                
                // Link detail (disesuaikan dengan query string id karena bukan custom post type)
                // Jika ingin menggunakan custom permalink, perlu setup rewrite rules.
                // Untuk amannya sekarang pakai query param ?wisata_id=...
                $link_detail = home_url('/detail-wisata/?id=' . $id); 
            ?>
            
            <!-- Card Item -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition duration-300 group flex flex-col h-full">
                <!-- Image Wrapper -->
                <div class="relative h-56 overflow-hidden bg-gray-200">
                    <img src="<?php echo esc_url($gambar); ?>" 
                         alt="<?php echo esc_attr($nama); ?>" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-emerald-600 shadow-sm">
                        Tiket: Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="p-6 flex flex-col flex-1">
                    <div class="flex items-start justify-between mb-2">
                        <h3 class="text-xl font-bold text-gray-800 line-clamp-1" title="<?php echo esc_attr($nama); ?>">
                            <a href="<?php echo esc_url($link_detail); ?>" class="hover:text-emerald-600 transition">
                                <?php echo esc_html($nama); ?>
                            </a>
                        </h3>
                    </div>
                    
                    <div class="flex items-center text-sm text-gray-500 mb-4">
                        <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                        <span class="truncate"><?php echo esc_html($lokasi); ?></span>
                    </div>
                    
                    <p class="text-gray-600 text-sm mb-6 line-clamp-2 flex-1">
                        <?php echo esc_html($deskripsi); ?>
                    </p>
                    
                    <div class="mt-auto pt-4 border-t border-dashed border-gray-100 flex gap-2">
                        <a href="<?php echo esc_url($link_detail); ?>" class="flex-1 bg-white border border-gray-200 text-gray-700 py-2 rounded-lg text-sm font-bold text-center hover:bg-gray-50 transition">
                            Lihat Detail
                        </a>
                        <a href="<?php echo esc_url(add_query_arg(['add-to-cart' => $id, 'type' => 'wisata'], home_url('/cart'))); ?>" class="flex-1 bg-emerald-600 text-white py-2 rounded-lg text-sm font-bold text-center hover:bg-emerald-700 transition flex items-center justify-center gap-2">
                            <i class="fas fa-ticket-alt"></i> Pesan
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1) : ?>
        <div class="mt-12 flex justify-center gap-2">
            <?php 
            // Previous Link
            if ($current_page > 1) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page - 1)) . '" class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-emerald-50 hover:text-emerald-600 transition"><i class="fas fa-chevron-left"></i></a>';
            }

            // Page Numbers
            for ($i = 1; $i <= $total_pages; $i++) {
                $active_class = ($i == $current_page) ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-emerald-50 hover:text-emerald-600';
                echo '<a href="' . esc_url(get_pagenum_link($i)) . '" class="w-10 h-10 flex items-center justify-center rounded-lg border ' . $active_class . ' transition font-bold">' . $i . '</a>';
            }

            // Next Link
            if ($current_page < $total_pages) {
                echo '<a href="' . esc_url(get_pagenum_link($current_page + 1)) . '" class="w-10 h-10 flex items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-emerald-50 hover:text-emerald-600 transition"><i class="fas fa-chevron-right"></i></a>';
            }
            ?>
        </div>
        <?php endif; ?>

    <?php else : ?>
        
        <!-- EMPTY STATE (Jika data kosong) -->
        <div class="text-center py-20 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-400">
                <i class="fas fa-map-marked-alt text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Belum ada destinasi wisata</h3>
            <p class="text-gray-500 max-w-md mx-auto">Saat ini belum ada data wisata yang ditampilkan. Silakan kembali lagi nanti.</p>
            <a href="<?php echo home_url(); ?>" class="inline-block mt-6 px-6 py-2 bg-emerald-600 text-white rounded-lg font-bold hover:bg-emerald-700 transition">
                Kembali ke Beranda
            </a>
        </div>

    <?php endif; ?>

</div>

<!-- CALL TO ACTION -->
<div class="bg-gray-900 text-white py-16 mt-12">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-4">Ingin Berkunjung Rame-Rame?</h2>
        <p class="text-gray-400 mb-8 max-w-2xl mx-auto">Dapatkan penawaran khusus untuk rombongan wisata atau kunjungan edukasi sekolah.</p>
        <a href="https://wa.me/6281234567890" target="_blank" class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-8 py-3 rounded-xl font-bold transition transform hover:-translate-y-1">
            <i class="fab fa-whatsapp text-xl"></i> Hubungi Kami
        </a>
    </div>
</div>

<?php get_footer(); ?>