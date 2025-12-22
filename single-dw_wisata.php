<?php
/**
 * Template Name: Single Wisata (Custom Table)
 * Description: Menampilkan detail wisata dari tabel wp_dw_wisata
 */

get_header();

global $wpdb;
$slug = get_query_var('dw_slug');

// 1. Query Data Wisata + Nama Desa
$table_wisata = $wpdb->prefix . 'dw_wisata';
$table_desa   = $wpdb->prefix . 'dw_desa';

$sql = $wpdb->prepare(
    "SELECT w.*, d.nama_desa 
    FROM $table_wisata w 
    JOIN $table_desa d ON w.id_desa = d.id 
    WHERE w.slug = %s AND w.status = 'aktif'",
    $slug
);

$wisata = $wpdb->get_row($sql);

// 2. Jika Data Tidak Ditemukan -> 404
if (!$wisata) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit();
}

// 3. Setup Variabel Tampilan
$id_wisata   = $wisata->id;
$judul       = esc_html($wisata->nama_wisata);
$deskripsi   = wp_kses_post($wisata->deskripsi); // Izinkan HTML aman
$harga       = floatval($wisata->harga_tiket);
$harga_fmt   = ($harga > 0) ? tema_dw_format_rupiah($harga) : 'Gratis';
$lokasi_maps = $wisata->lokasi_maps;
$rating      = floatval($wisata->rating_avg);
$kontak      = $wisata->kontak_pengelola ?? '';
$jam_buka    = $wisata->jam_buka ?? '08:00 - 17:00';
$fasilitas   = $wisata->fasilitas; // Bisa jadi JSON atau text biasa
$image_url   = !empty($wisata->foto_utama) ? esc_url($wisata->foto_utama) : 'https://via.placeholder.com/800x600?text=Wisata';
$galeri      = !empty($wisata->galeri) ? json_decode($wisata->galeri) : [];

// Breadcrumb Title (untuk SEO)
?>
<title><?php echo $judul; ?> - Desa Wisata</title>

<div class="bg-gray-50 min-h-screen pb-24 md:pb-0 font-sans">
    
    <div class="max-w-6xl mx-auto md:grid md:grid-cols-2 md:gap-8 md:py-8 md:px-4">
        
        <!-- Kolom Kiri: Gambar -->
        <div class="relative h-[300px] md:h-[500px] bg-gray-200 md:rounded-2xl overflow-hidden group">
            <img src="<?php echo $image_url; ?>" alt="<?php echo $judul; ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
            
            <div class="absolute top-4 left-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-primary shadow-sm flex items-center gap-1">
                <i class="fas fa-map-marker-alt"></i> <?php echo esc_html($wisata->nama_desa); ?>
            </div>
        </div>

        <!-- Kolom Kanan: Info -->
        <div class="px-5 py-6 md:py-2 flex flex-col justify-center">
            
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2 leading-tight"><?php echo $judul; ?></h1>
            
            <div class="flex items-center gap-4 text-sm text-gray-500 mb-6">
                <span class="flex items-center gap-1 text-yellow-500 font-bold">
                    <i class="fas fa-star"></i> <?php echo number_format($rating, 1); ?>
                </span>
                <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                <span><?php echo esc_html($wisata->kategori); ?></span>
            </div>

            <!-- Harga & Deskripsi -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 mb-6">
                <div class="flex justify-between items-center mb-4 border-b border-gray-100 pb-4">
                    <span class="text-gray-500">Harga Tiket</span>
                    <span class="text-2xl font-bold text-primary"><?php echo $harga_fmt; ?></span>
                </div>
                
                <div class="prose prose-sm text-gray-600 max-w-none">
                    <?php echo $deskripsi; ?>
                </div>
            </div>

            <!-- Informasi Tambahan -->
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-blue-50 p-4 rounded-xl">
                    <span class="block text-xs text-blue-400 mb-1">Jam Operasional</span>
                    <span class="font-semibold text-blue-900"><?php echo esc_html($jam_buka); ?></span>
                </div>
                <div class="bg-green-50 p-4 rounded-xl">
                    <span class="block text-xs text-green-400 mb-1">Fasilitas</span>
                    <span class="font-semibold text-green-900 truncate"><?php echo esc_html($fasilitas); ?></span>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex gap-3">
                <?php if ($kontak): ?>
                <a href="https://wa.me/<?php echo esc_attr($kontak); ?>?text=Halo, saya ingin bertanya tentang <?php echo urlencode($judul); ?>" target="_blank" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition-all">
                    <i class="fab fa-whatsapp text-xl"></i> Chat Pengelola
                </a>
                <?php endif; ?>
                
                <?php if ($lokasi_maps): ?>
                <a href="<?php echo esc_url($lokasi_maps); ?>" target="_blank" class="w-14 h-14 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl flex items-center justify-center transition-all">
                    <i class="fas fa-map-marked-alt text-xl"></i>
                </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>