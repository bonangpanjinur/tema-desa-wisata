<?php
/**
 * Template Name: Detail Wisata Custom
 */

get_header();

global $wpdb;
$table_wisata = $wpdb->prefix . 'dw_wisata';
$table_desa   = $wpdb->prefix . 'dw_desa';

$wisata = null;

// 1. Coba Tangkap Slug dari URL (Rewrite Rule)
$slug = get_query_var('dw_slug');

// 2. Coba Tangkap ID (Fallback)
$id_param = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 3. Query Data (AMAN DARI SQL INJECTION)
if (!empty($slug)) {
    // Cari berdasarkan Slug
    $wisata = $wpdb->get_row($wpdb->prepare("
        SELECT w.*, d.nama_desa, d.kabupaten 
        FROM $table_wisata w
        LEFT JOIN $table_desa d ON w.id_desa = d.id
        WHERE w.slug = %s AND w.status = 'aktif'
    ", $slug));
} elseif ($id_param > 0) {
    // Cari berdasarkan ID
    $wisata = $wpdb->get_row($wpdb->prepare("
        SELECT w.*, d.nama_desa, d.kabupaten 
        FROM $table_wisata w
        LEFT JOIN $table_desa d ON w.id_desa = d.id
        WHERE w.id = %d AND w.status = 'aktif'
    ", $id_param));
}

// 4. Jika Data Tidak Ditemukan
if (!$wisata) {
    echo '<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-4">';
    echo '<div class="text-6xl text-gray-200 mb-4"><i class="fas fa-map-signs"></i></div>';
    echo '<h1 class="text-2xl font-bold text-gray-800 mb-2">Wisata Tidak Ditemukan</h1>';
    echo '<p class="text-gray-500 mb-6">Mungkin link rusak atau wisata telah dihapus.</p>';
    echo '<a href="'.home_url('/').'" class="bg-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-green-700 transition">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// Data Formatting
$harga_tiket = $wisata->harga_tiket;
$price_display = ($harga_tiket > 0) ? '<span class="text-xs text-gray-500 font-normal">Tiket Masuk</span> <span class="text-primary font-bold text-xl block">Rp ' . number_format($harga_tiket, 0, ',', '.') . '</span>' : '<span class="text-primary font-bold text-xl">Gratis</span>';
$lokasi = !empty($wisata->nama_desa) ? "Desa " . $wisata->nama_desa . ", " . $wisata->kabupaten : $wisata->kabupaten;

$fasilitas = [];
if (!empty($wisata->fasilitas)) {
    $json_test = json_decode($wisata->fasilitas);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json_test)) {
        $fasilitas = $json_test;
    } else {
        $fasilitas = explode(',', $wisata->fasilitas);
    }
}

$jam_buka = !empty($wisata->jam_buka) ? $wisata->jam_buka : '08:00 - 17:00';
$rating = $wisata->rating_avg > 0 ? $wisata->rating_avg : '4.5';
$img_hero = !empty($wisata->foto_utama) ? $wisata->foto_utama : 'https://via.placeholder.com/800x400';

$wa_link = '#';
if (!empty($wisata->kontak_pengelola)) {
    $wa_num = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $wisata->kontak_pengelola));
    $wa_text = urlencode("Halo, saya tertarik berkunjung ke " . $wisata->nama_wisata . ". Boleh info lebih lanjut?");
    $wa_link = "https://wa.me/{$wa_num}?text={$wa_text}";
}
?>

<!-- HERO SECTION -->
<div class="relative bg-gray-900 h-[300px] md:h-[400px]">
    <img src="<?php echo esc_url($img_hero); ?>" class="w-full h-full object-cover opacity-60">
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
    <div class="absolute bottom-0 left-0 w-full p-4 md:p-8">
        <div class="container mx-auto">
            <span class="bg-primary text-white text-xs font-bold px-2 py-1 rounded mb-2 inline-block uppercase tracking-wider">
                <?php echo esc_html($wisata->kategori ?: 'Wisata'); ?>
            </span>
            <h1 class="text-2xl md:text-4xl font-bold text-white mb-1 leading-tight">
                <?php echo esc_html($wisata->nama_wisata); ?>
            </h1>
            <p class="text-gray-200 text-sm md:text-base flex items-center gap-1">
                <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($lokasi); ?>
            </p>
        </div>
    </div>
</div>

<!-- CONTENT & SIDEBAR -->
<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col lg:flex-row gap-8">
        <div class="w-full lg:w-2/3">
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-3">Tentang Wisata</h3>
                <div class="prose max-w-none text-gray-600 text-sm leading-relaxed">
                    <?php echo wpautop(esc_html($wisata->deskripsi)); ?>
                </div>
            </div>
            <?php if(!empty($fasilitas)): ?>
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-3">Fasilitas Tersedia</h3>
                <div class="grid grid-cols-2 gap-3">
                    <?php foreach($fasilitas as $f): if(trim($f) == '') continue; ?>
                    <div class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 p-2 rounded-lg border border-gray-100">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span><?php echo esc_html(trim($f)); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if(!empty($wisata->lokasi_maps)): ?>
            <div class="mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-3">Lokasi</h3>
                <div class="rounded-xl overflow-hidden shadow-sm border border-gray-200 bg-gray-100 h-48 relative flex items-center justify-center group">
                    <div class="absolute inset-0 bg-cover opacity-20" style="background-image: url('https://upload.wikimedia.org/wikipedia/commons/thumb/e/ec/World_map_blank_without_borders.svg/2000px-World_map_blank_without_borders.svg.png');"></div>
                    <div class="relative z-10 text-center">
                        <a href="<?php echo esc_url($wisata->lokasi_maps); ?>" target="_blank" class="bg-white text-primary px-4 py-2 rounded-full font-bold shadow hover:shadow-lg transition flex items-center gap-2">
                            <i class="fas fa-map-marked-alt"></i> Buka di Google Maps
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="w-full lg:w-1/3">
            <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 sticky top-24">
                <div class="flex justify-between items-center mb-4 pb-4 border-b border-gray-100">
                    <div><?php echo $price_display; ?></div>
                    <div class="text-right">
                        <div class="flex items-center gap-1 text-yellow-400 text-sm font-bold">
                            <i class="fas fa-star"></i> <?php echo $rating; ?>
                        </div>
                        <span class="text-xs text-gray-400">Rating Pengunjung</span>
                    </div>
                </div>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 flex-shrink-0">
                            <i class="far fa-clock"></i>
                        </div>
                        <div>
                            <span class="block text-xs text-gray-400 font-bold uppercase">Jam Operasional</span>
                            <span class="text-sm font-semibold text-gray-700"><?php echo esc_html($jam_buka); ?> WIB</span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="block w-full bg-green-600 text-white text-center py-3 rounded-xl font-bold hover:bg-green-700 transition shadow-md flex items-center justify-center gap-2">
                        <i class="fab fa-whatsapp text-lg"></i> Hubungi Pengelola
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RELATED POSTS -->
<div class="bg-gray-50 py-10 mt-8 border-t border-gray-200">
    <div class="container mx-auto px-4">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Wisata Lainnya</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php
            $related = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE status='aktif' AND id != %d ORDER BY RAND() LIMIT 4", $wisata ? $wisata->id : 0));
            if($related): foreach($related as $r):
                $r_img = !empty($r->foto_utama) ? $r->foto_utama : 'https://via.placeholder.com/300';
                $r_price = ($r->harga_tiket > 0) ? 'Rp '.number_format($r->harga_tiket,0,',','.') : 'Gratis';
                $r_link = home_url('/wisata/detail/' . $r->slug);
            ?>
            <a href="<?php echo esc_url($r_link); ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-md transition block">
                <div class="h-32 bg-gray-200 relative overflow-hidden">
                    <img src="<?php echo esc_url($r_img); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                </div>
                <div class="p-3">
                    <h4 class="text-sm font-bold text-gray-800 line-clamp-1 mb-1 group-hover:text-primary"><?php echo esc_html($r->nama_wisata); ?></h4>
                    <div class="text-sm font-bold text-primary"><?php echo $r_price; ?></div>
                </div>
            </a>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>