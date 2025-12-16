<?php
/**
 * Template Name: Profil Desa Custom
 * Gunakan page ini dengan slug: profil-desa
 * URL akses: /profil-desa/?id=1 (ID Desa)
 */

get_header();

global $wpdb;
$table_desa     = $wpdb->prefix . 'dw_desa';
$table_wisata   = $wpdb->prefix . 'dw_wisata';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

// 1. Tangkap ID Desa
$desa_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$desa = null;

if ($desa_id > 0) {
    $desa = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE id = %d AND status = 'aktif'", $desa_id));
}

// 2. Not Found
if (!$desa) {
    echo '<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-4">';
    echo '<div class="text-6xl text-gray-200 mb-4"><i class="fas fa-home"></i></div>';
    echo '<h1 class="text-2xl font-bold text-gray-800 mb-2">Desa Tidak Ditemukan</h1>';
    echo '<a href="'.home_url('/').'" class="text-primary font-bold hover:underline">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// 3. Statistik & Data
$wisata_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE id_desa = %d AND status='aktif'", $desa_id));
$pedagang_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_akun='aktif'", $desa_id));

$count_wisata = count($wisata_list);
$count_umkm = count($pedagang_list);

$foto_desa = !empty($desa->foto) ? $desa->foto : 'https://via.placeholder.com/1200x500?text=Desa+Wisata';
?>

<!-- === HEADER DESA === -->
<div class="relative h-[300px] md:h-[400px] bg-gray-900">
    <img src="<?php echo esc_url($foto_desa); ?>" class="w-full h-full object-cover opacity-60">
    <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent"></div>
    
    <div class="absolute bottom-0 left-0 w-full p-6 md:p-10">
        <div class="container mx-auto text-center md:text-left">
            <span class="bg-primary text-white text-xs font-bold px-3 py-1 rounded-full mb-3 inline-block">Desa Wisata Resmi</span>
            <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-2"><?php echo esc_html($desa->nama_desa); ?></h1>
            <p class="text-gray-300 text-sm md:text-base mb-6 max-w-2xl">
                <?php echo esc_html($desa->kabupaten); ?>, <?php echo esc_html($desa->provinsi); ?>
            </p>
            
            <div class="flex justify-center md:justify-start gap-6 text-white">
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
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 border-l-4 border-primary pl-3">Tentang Desa</h2>
                    <div class="prose max-w-none text-gray-600">
                        <?php echo wpautop(esc_html($desa->deskripsi)); ?>
                    </div>
                </div>

                <!-- Daftar Wisata -->
                <div>
                    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="fas fa-map-marked-alt text-primary"></i> Destinasi Wisata
                    </h2>
                    
                    <?php if ($wisata_list) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($wisata_list as $w) : 
                            $link_w = home_url('/detail-wisata/?id=' . $w->id);
                            $img_w = !empty($w->foto_utama) ? $w->foto_utama : 'https://via.placeholder.com/400x300';
                        ?>
                        <a href="<?php echo esc_url($link_w); ?>" class="flex bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition group h-32 md:h-40">
                            <div class="w-1/3 relative overflow-hidden">
                                <img src="<?php echo esc_url($img_w); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                            </div>
                            <div class="w-2/3 p-4 flex flex-col justify-center">
                                <h3 class="font-bold text-gray-800 mb-1 group-hover:text-primary line-clamp-1"><?php echo esc_html($w->nama_wisata); ?></h3>
                                <div class="text-xs text-gray-500 mb-2 line-clamp-2"><?php echo wp_trim_words($w->deskripsi, 10); ?></div>
                                <span class="text-xs font-bold text-green-600 bg-green-50 px-2 py-1 rounded w-max">
                                    <?php echo ($w->harga_tiket > 0) ? 'Tiket: Rp ' . number_format($w->harga_tiket) : 'Gratis'; ?>
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Belum ada data wisata.</p>
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
                            $link_p = home_url('/profil-toko/?id=' . $p->id);
                            $img_p = !empty($p->foto_profil) ? $p->foto_profil : 'https://via.placeholder.com/150';
                        ?>
                        <a href="<?php echo esc_url($link_p); ?>" class="bg-white rounded-xl border border-gray-100 p-4 text-center hover:shadow-md transition group">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full mb-3 overflow-hidden border border-gray-200 group-hover:border-primary transition">
                                <img src="<?php echo esc_url($img_p); ?>" class="w-full h-full object-cover">
                            </div>
                            <h4 class="font-bold text-gray-800 text-sm truncate"><?php echo esc_html($p->nama_toko); ?></h4>
                            <p class="text-xs text-gray-500 truncate"><?php echo esc_html($p->nama_pemilik); ?></p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <p class="text-gray-500 italic">Belum ada data UMKM.</p>
                    <?php endif; ?>
                </div>

            </div>

            <!-- SIDEBAR -->
            <div class="w-full lg:w-1/4 space-y-6">
                <!-- Info Kontak Desa -->
                <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm sticky top-24">
                    <h3 class="font-bold text-gray-900 mb-4">Informasi Desa</h3>
                    
                    <div class="space-y-4 text-sm">
                        <div>
                            <span class="block text-gray-400 text-xs uppercase font-bold mb-1">Kepala Desa</span>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 bg-gray-200 rounded-full overflow-hidden">
                                    <!-- Placeholder User -->
                                    <i class="fas fa-user text-gray-400 mt-1 ml-1.5"></i>
                                </div>
                                <span class="font-medium text-gray-700">Admin Desa</span>
                            </div>
                        </div>

                        <div>
                            <span class="block text-gray-400 text-xs uppercase font-bold mb-1">Lokasi</span>
                            <p class="text-gray-600 leading-snug">
                                <?php echo esc_html($desa->alamat_lengkap ?: 'Alamat belum diisi'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <button class="w-full bg-blue-50 text-blue-600 font-bold py-2 rounded-lg text-sm hover:bg-blue-100 transition">
                            Lihat Peta Desa
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>