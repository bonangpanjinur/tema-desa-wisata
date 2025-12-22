<?php
/**
 * Template Name: Single Wisata (Modern Travel Style)
 * Description: Menampilkan detail wisata dengan UI Imersif
 */

get_header();

global $wpdb;
$slug = get_query_var('dw_slug');

// 1. Query Data Wisata + Desa
$table_wisata = $wpdb->prefix . 'dw_wisata';
$table_desa   = $wpdb->prefix . 'dw_desa';

$sql = $wpdb->prepare(
    "SELECT w.*, d.nama_desa, d.alamat_lengkap as alamat_desa, d.kabupaten, d.provinsi 
    FROM $table_wisata w 
    JOIN $table_desa d ON w.id_desa = d.id 
    WHERE w.slug = %s AND w.status = 'aktif'",
    $slug
);

$wisata = $wpdb->get_row($sql);

// 2. 404 Handler
if (!$wisata) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit();
}

// 3. Setup Data
$id_wisata   = $wisata->id;
$judul       = esc_html($wisata->nama_wisata);
$deskripsi   = wp_kses_post($wisata->deskripsi);
$harga       = floatval($wisata->harga_tiket);
$harga_fmt   = ($harga > 0) ? tema_dw_format_rupiah($harga) : 'Gratis';
$lokasi_maps = $wisata->lokasi_maps;
$rating      = floatval($wisata->rating_avg);
$ulasan      = intval($wisata->total_ulasan);
$kontak      = $wisata->kontak_pengelola ?? '';
$jam_buka    = $wisata->jam_buka ?? '08:00 - 17:00';
$fasilitas   = !empty($wisata->fasilitas) ? explode(',', $wisata->fasilitas) : []; // Asumsi dipisah koma
$kategori    = esc_html($wisata->kategori);
$alamat_full = $wisata->nama_desa . ', ' . $wisata->kabupaten;

// Gambar
$foto_utama  = !empty($wisata->foto_utama) ? esc_url($wisata->foto_utama) : 'https://via.placeholder.com/1200x600?text=Wisata+Alam';
$galeri      = !empty($wisata->galeri) ? json_decode($wisata->galeri, true) : [];

?>
<title><?php echo $judul; ?> - Wisata Desa</title>

<div class="bg-white min-h-screen font-sans pb-24">

    <!-- HERO SECTION (Gambar Latar Full) -->
    <div class="relative h-[40vh] md:h-[60vh] w-full overflow-hidden bg-gray-900">
        <img src="<?php echo $foto_utama; ?>" alt="<?php echo $judul; ?>" class="w-full h-full object-cover opacity-80 fixed-bg-effect">
        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
        
        <!-- Konten Hero di Bawah -->
        <div class="absolute bottom-0 left-0 right-0 max-w-7xl mx-auto px-4 pb-8 md:pb-12 pt-20">
            <span class="inline-block bg-primary text-white text-xs font-bold px-3 py-1 rounded-full mb-3 uppercase tracking-wider shadow-lg">
                <?php echo $kategori; ?>
            </span>
            <h1 class="text-3xl md:text-5xl font-bold text-white mb-2 leading-tight drop-shadow-lg"><?php echo $judul; ?></h1>
            <div class="flex flex-wrap items-center gap-4 text-white/90 text-sm md:text-base">
                <div class="flex items-center gap-1">
                    <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo $alamat_full; ?>
                </div>
                <span class="hidden md:inline">•</span>
                <div class="flex items-center gap-1">
                    <i class="fas fa-star text-yellow-400"></i> 
                    <span class="font-bold"><?php echo number_format($rating, 1); ?></span> 
                    <span class="opacity-70">(<?php echo $ulasan; ?> Ulasan)</span>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT CONTAINER -->
    <div class="max-w-7xl mx-auto px-4 -mt-6 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- KOLOM KIRI (Deskripsi & Fasilitas) -->
            <div class="lg:col-span-2 space-y-8 bg-white rounded-t-3xl lg:rounded-3xl p-6 md:p-8 shadow-sm border border-gray-100 min-h-[500px]">
                
                <!-- Deskripsi -->
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-primary pl-3">Tentang Destinasi</h2>
                    <div class="prose prose-lg text-gray-600 max-w-none leading-relaxed">
                        <?php echo $deskripsi; ?>
                    </div>
                </div>

                <!-- Galeri Mini (Jika ada) -->
                <?php if (!empty($galeri)): ?>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-primary pl-3">Galeri Foto</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <?php foreach ($galeri as $foto): ?>
                            <div class="aspect-square rounded-xl overflow-hidden cursor-pointer hover:opacity-90 transition shadow-sm">
                                <img src="<?php echo esc_url($foto); ?>" class="w-full h-full object-cover" onclick="window.open(this.src)">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Fasilitas -->
                <?php if (!empty($fasilitas)): ?>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-primary pl-3">Fasilitas Tersedia</h2>
                    <div class="flex flex-wrap gap-3">
                        <?php foreach ($fasilitas as $item): 
                            $item = trim($item);
                            if(empty($item)) continue;
                        ?>
                            <div class="flex items-center gap-2 bg-gray-50 text-gray-700 px-4 py-3 rounded-xl border border-gray-100">
                                <i class="fas fa-check-circle text-primary text-sm"></i>
                                <span class="font-medium text-sm"><?php echo esc_html($item); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Peta Lokasi -->
                <?php if (!empty($lokasi_maps)): ?>
                <div>
                    <h2 class="text-xl font-bold text-gray-800 mb-4 border-l-4 border-primary pl-3">Lokasi</h2>
                    <div class="bg-gray-100 rounded-xl p-4 flex items-center justify-between">
                        <div class="text-gray-600 text-sm">
                            <i class="fas fa-map-marked text-2xl text-gray-400 mr-2 align-middle"></i>
                            Buka di Google Maps untuk rute terbaik.
                        </div>
                        <a href="<?php echo esc_url($lokasi_maps); ?>" target="_blank" class="bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-bold hover:bg-gray-50 transition shadow-sm">
                            Buka Peta
                        </a>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- KOLOM KANAN (Sidebar Info & Booking) -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-3xl p-6 shadow-lg border border-gray-100 sticky top-24">
                    
                    <div class="mb-6 pb-6 border-b border-gray-100">
                        <span class="text-sm text-gray-500 block mb-1">Harga Tiket Masuk</span>
                        <div class="text-3xl font-bold text-primary"><?php echo $harga_fmt; ?></div>
                        <div class="text-xs text-gray-400 mt-1">per orang</div>
                    </div>

                    <div class="space-y-4 mb-8">
                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 shrink-0">
                                <i class="far fa-clock"></i>
                            </div>
                            <div>
                                <span class="block font-bold text-gray-800 text-sm">Jam Operasional</span>
                                <span class="text-gray-500 text-sm"><?php echo esc_html($jam_buka); ?></span>
                            </div>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-green-500 shrink-0">
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <div>
                                <span class="block font-bold text-gray-800 text-sm">Pengelola</span>
                                <span class="text-gray-500 text-sm"><?php echo !empty($kontak) ? 'Terverifikasi' : 'Desa Wisata'; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-3">
                        <?php if ($kontak): ?>
                        <a href="https://wa.me/<?php echo esc_attr($kontak); ?>?text=Halo, saya ingin bertanya tentang wisata <?php echo urlencode($judul); ?>" target="_blank" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg shadow-green-500/30">
                            <i class="fab fa-whatsapp text-xl"></i> Hubungi Pengelola
                        </a>
                        <?php endif; ?>
                        
                        <a href="#" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 px-4 rounded-xl flex items-center justify-center gap-2 transition-all">
                            <i class="far fa-heart"></i> Simpan
                        </a>
                    </div>
                    
                </div>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>