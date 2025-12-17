<?php
/**
 * Template Name: Profil Desa Custom
 * URL akses: /profil/desa/slug-desa
 */

get_header();

global $wpdb;
$table_desa     = $wpdb->prefix . 'dw_desa';
$table_wisata   = $wpdb->prefix . 'dw_wisata';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

// 1. Tangkap Slug dari URL (Rewrite Rule) atau ID (Fallback)
$slug_desa = get_query_var('dw_slug_desa');
$desa_id_param = isset($_GET['id']) ? intval($_GET['id']) : 0;

$desa = null;

// 2. Query Data Desa
if (!empty($slug_desa)) {
    // Decode slug untuk menangani spasi (misal: "Desa%20Taraju" -> "Desa Taraju")
    $decoded_slug = urldecode($slug_desa);
    
    // Query berdasarkan Slug
    $desa = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM $table_desa 
        WHERE slug_desa = %s AND status = 'aktif'
    ", $decoded_slug));
} 
elseif ($desa_id_param > 0) {
    // Query berdasarkan ID (Fallback jika link masih pakai ?id=)
    $desa = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM $table_desa 
        WHERE id = %d AND status = 'aktif'
    ", $desa_id_param));
}

// 3. Not Found Handler
if (!$desa) {
    echo '<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-4">';
    echo '<div class="text-6xl text-gray-200 mb-4"><i class="fas fa-home"></i></div>';
    echo '<h1 class="text-2xl font-bold text-gray-800 mb-2">Desa Tidak Ditemukan</h1>';
    echo '<p class="text-gray-500 mb-6">Desa yang Anda cari mungkin belum terdaftar atau tautan salah.</p>';
    echo '<a href="'.home_url('/').'" class="bg-primary text-white px-6 py-2 rounded-lg font-bold hover:bg-green-700 transition">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// 4. Statistik & Data Relasi
$wisata_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE id_desa = %d AND status='aktif' ORDER BY created_at DESC", $desa->id));
$pedagang_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_akun='aktif' ORDER BY created_at DESC", $desa->id));

$count_wisata = count($wisata_list);
$count_umkm = count($pedagang_list);

$foto_desa = !empty($desa->foto) ? $desa->foto : 'https://via.placeholder.com/1200x500?text=Desa+Wisata';
?>

<!-- === HEADER DESA === -->
<div class="relative h-[300px] md:h-[400px] bg-gray-900 group">
    <img src="<?php echo esc_url($foto_desa); ?>" class="w-full h-full object-cover opacity-60 transition duration-1000 group-hover:scale-105">
    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent"></div>
    
    <div class="absolute bottom-0 left-0 w-full p-6 md:p-10">
        <div class="container mx-auto text-center md:text-left">
            <span class="bg-white/20 backdrop-blur-md text-white border border-white/30 text-xs font-bold px-3 py-1 rounded-full mb-3 inline-block">
                <i class="fas fa-check-circle text-green-400 mr-1"></i> Desa Wisata Resmi
            </span>
            <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-2 tracking-tight"><?php echo esc_html($desa->nama_desa); ?></h1>
            <p class="text-gray-300 text-sm md:text-base mb-6 max-w-2xl mx-auto md:mx-0 font-light">
                <i class="fas fa-map-marker-alt text-red-500 mr-1"></i> <?php echo esc_html($desa->kabupaten); ?>, <?php echo esc_html($desa->provinsi); ?>
            </p>
            
            <div class="flex justify-center md:justify-start gap-8 text-white border-t border-white/10 pt-4 md:border-none md:pt-0">
                <div class="text-center md:text-left">
                    <span class="block text-2xl font-bold"><?php echo $count_wisata; ?></span>
                    <span class="text-xs text-gray-400 uppercase tracking-wide">Destinasi</span>
                </div>
                <div class="text-center md:text-left">
                    <span class="block text-2xl font-bold"><?php echo $count_umkm; ?></span>
                    <span class="text-xs text-gray-400 uppercase tracking-wide">UMKM Lokal</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === CONTENT === -->
<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4">
        
        <div class="flex flex-col lg:flex-row gap-10">
            
            <!-- KOLOM UTAMA -->
            <div class="w-full lg:w-3/4 space-y-10">
                
                <!-- Tentang Desa -->
                <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-3">
                        <span class="w-1 h-6 bg-primary rounded-full"></span> Tentang Desa
                    </h2>
                    <div class="prose prose-sm md:prose-base max-w-none text-gray-600 leading-relaxed">
                        <?php echo wpautop(esc_html($desa->deskripsi)); ?>
                    </div>
                </div>

                <!-- Daftar Wisata -->
                <div>
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-map-marked-alt text-primary"></i> Destinasi Wisata
                        </h2>
                    </div>
                    
                    <?php if ($wisata_list) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($wisata_list as $w) : 
                            // Link ke detail wisata (pastikan menggunakan slug jika ada)
                            $link_w = !empty($w->slug) ? home_url('/wisata/detail/' . $w->slug) : home_url('/detail-wisata/?id=' . $w->id);
                            $img_w = !empty($w->foto_utama) ? $w->foto_utama : 'https://via.placeholder.com/400x300';
                        ?>
                        <a href="<?php echo esc_url($link_w); ?>" class="flex bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition group h-36 relative">
                            <div class="w-1/3 relative overflow-hidden">
                                <img src="<?php echo esc_url($img_w); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            </div>
                            <div class="w-2/3 p-4 flex flex-col justify-center">
                                <h3 class="font-bold text-gray-800 mb-1 group-hover:text-primary line-clamp-1 text-lg"><?php echo esc_html($w->nama_wisata); ?></h3>
                                <div class="text-xs text-gray-500 mb-3 line-clamp-2"><?php echo wp_trim_words($w->deskripsi, 12); ?></div>
                                <div class="mt-auto">
                                    <span class="text-xs font-bold text-green-700 bg-green-50 px-2 py-1 rounded border border-green-100">
                                        <?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket) : 'Gratis'; ?>
                                    </span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="p-8 text-center bg-white rounded-xl border border-dashed border-gray-200">
                            <i class="far fa-map text-gray-300 text-3xl mb-2"></i>
                            <p class="text-gray-500">Belum ada data wisata di desa ini.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Daftar UMKM -->
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="fas fa-store text-primary"></i> UMKM & Oleh-Oleh
                    </h2>
                    
                    <?php if ($pedagang_list) : ?>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <?php foreach ($pedagang_list as $p) : 
                            // Link ke profil toko (slug)
                            $link_p = !empty($p->slug_toko) ? home_url('/profil/toko/' . $p->slug_toko) : home_url('/profil-toko/?id=' . $p->id);
                            $img_p = !empty($p->foto_profil) ? $p->foto_profil : 'https://via.placeholder.com/150';
                        ?>
                        <a href="<?php echo esc_url($link_p); ?>" class="bg-white rounded-xl border border-gray-100 p-4 text-center hover:shadow-md hover:-translate-y-1 transition group">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full mb-3 overflow-hidden border-2 border-white shadow-sm group-hover:border-primary transition relative">
                                <img src="<?php echo esc_url($img_p); ?>" class="w-full h-full object-cover">
                            </div>
                            <h4 class="font-bold text-gray-800 text-sm truncate px-1 group-hover:text-primary transition"><?php echo esc_html($p->nama_toko); ?></h4>
                            <p class="text-xs text-gray-400 truncate mt-0.5"><?php echo esc_html($p->nama_pemilik); ?></p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="p-8 text-center bg-white rounded-xl border border-dashed border-gray-200">
                            <i class="fas fa-box-open text-gray-300 text-3xl mb-2"></i>
                            <p class="text-gray-500">Belum ada UMKM terdaftar.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- SIDEBAR -->
            <div class="w-full lg:w-1/4 space-y-6">
                <!-- Info Kontak Desa -->
                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm sticky top-24">
                    <h3 class="font-bold text-gray-900 mb-4 border-b border-gray-100 pb-3">Informasi Kontak</h3>
                    
                    <div class="space-y-4 text-sm">
                        <!-- Rekening Desa (Optional, jika ada di DB) -->
                        <?php if(!empty($desa->no_rekening_desa)): ?>
                        <div>
                            <span class="block text-gray-400 text-[10px] uppercase font-bold mb-1 tracking-wider">Rekening Resmi</span>
                            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                                <span class="block font-bold text-gray-800"><?php echo esc_html($desa->nama_bank_desa); ?></span>
                                <span class="block font-mono text-primary font-bold text-base my-1"><?php echo esc_html($desa->no_rekening_desa); ?></span>
                                <span class="block text-xs text-gray-500">a.n <?php echo esc_html($desa->atas_nama_rekening_desa); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div>
                            <span class="block text-gray-400 text-[10px] uppercase font-bold mb-1 tracking-wider">Alamat Kantor</span>
                            <p class="text-gray-600 leading-snug">
                                <?php echo esc_html($desa->alamat_lengkap ?: 'Alamat belum diisi'); ?>
                            </p>
                            <div class="mt-2 text-xs text-gray-500">
                                <span class="block">Kec. <?php echo esc_html($desa->kecamatan); ?></span>
                                <span class="block">Kel. <?php echo esc_html($desa->kelurahan); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <a href="https://maps.google.com/?q=<?php echo urlencode($desa->alamat_lengkap . ' ' . $desa->kabupaten); ?>" target="_blank" class="flex items-center justify-center w-full bg-blue-50 text-blue-600 font-bold py-2.5 rounded-lg text-sm hover:bg-blue-100 transition gap-2">
                            <i class="fas fa-map-marked-alt"></i> Lihat Peta
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>