<?php
/**
 * Template Name: Profil Desa Custom
 * URL akses: /@slug-desa (melalui Rewrite Rule di functions.php)
 */

get_header();

global $wpdb;
$table_desa     = $wpdb->prefix . 'dw_desa';
$table_wisata   = $wpdb->prefix . 'dw_wisata';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

// 1. Tangkap Slug (dari URL @...) atau ID (Fallback)
$slug_desa = get_query_var('dw_slug_desa');
$desa_id_param = isset($_GET['id']) ? intval($_GET['id']) : 0;

$desa = null;

// 2. Query Data Desa
if (!empty($slug_desa)) {
    $decoded_slug = urldecode($slug_desa);
    // Menggunakan kolom slug_desa sesuai struktur database
    $desa = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE slug_desa = %s AND status = 'aktif'", $decoded_slug));
} 
elseif ($desa_id_param > 0) {
    $desa = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE id = %d AND status = 'aktif'", $desa_id_param));
}

// 3. Handler Jika Tidak Ditemukan
if (!$desa) {
    echo '<div class="min-h-[70vh] flex flex-col items-center justify-center text-center p-6 bg-gray-50">';
    echo '<div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mb-6 animate-pulse"><i class="fas fa-landmark text-4xl text-gray-400"></i></div>';
    echo '<h1 class="text-3xl font-bold text-gray-800 mb-2">Desa Tidak Ditemukan</h1>';
    echo '<p class="text-gray-500 mb-8 max-w-md mx-auto">Halaman desa yang Anda cari ("@'.esc_html($slug_desa).'") mungkin belum terdaftar atau URL salah.</p>';
    echo '<a href="'.home_url('/').'" class="bg-primary text-white px-8 py-3 rounded-full font-bold hover:bg-green-700 transition shadow-lg hover:shadow-green-200">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// 4. Ambil Data Relasi (Wisata & UMKM milik desa ini)
$wisata_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE id_desa = %d AND status='aktif' ORDER BY created_at DESC", $desa->id));
$pedagang_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_akun='aktif' ORDER BY created_at DESC", $desa->id));

$count_wisata = count($wisata_list);
$count_umkm = count($pedagang_list);
$foto_desa = !empty($desa->foto) ? $desa->foto : 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'; // Fallback image alam
?>

<!-- === HERO SECTION === -->
<div class="relative h-[400px] lg:h-[500px] bg-gray-900 overflow-hidden group">
    <!-- Background Image -->
    <div class="absolute inset-0">
        <img src="<?php echo esc_url($foto_desa); ?>" class="w-full h-full object-cover opacity-60 transition duration-[3000ms] group-hover:scale-105" alt="Desa <?php echo esc_attr($desa->nama_desa); ?>">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent"></div>
    </div>
    
    <div class="absolute bottom-0 left-0 w-full p-6 md:p-12 z-10">
        <div class="container mx-auto">
            <div class="flex flex-col md:flex-row items-end justify-between gap-6">
                <div class="max-w-3xl">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-white/10 backdrop-blur-md text-white border border-white/20 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-2">
                            <i class="fas fa-check-circle text-blue-400"></i> Desa Terverifikasi
                        </span>
                        <?php if($desa->website_desa): ?>
                        <a href="<?php echo esc_url($desa->website_desa); ?>" target="_blank" class="text-gray-300 hover:text-white text-xs hover:underline flex items-center gap-1">
                            <i class="fas fa-globe"></i> Website Resmi
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-2 tracking-tight leading-none">
                        <?php echo esc_html($desa->nama_desa); ?>
                    </h1>
                    
                    <p class="text-gray-300 text-lg mb-0 font-light flex flex-wrap items-center gap-3">
                        <span class="flex items-center gap-1.5"><i class="fas fa-map-marker-alt text-red-500"></i> <?php echo esc_html($desa->kecamatan); ?>, <?php echo esc_html($desa->kabupaten); ?></span>
                        <span class="hidden md:inline text-gray-600">•</span>
                        <span class="text-sm bg-primary/20 text-primary-light px-2 py-0.5 rounded border border-primary/30">@<?php echo esc_html($desa->slug_desa); ?></span>
                    </p>
                </div>

                <!-- Stats Cards -->
                <div class="flex gap-3">
                    <div class="bg-white/10 backdrop-blur-md border border-white/10 p-4 rounded-2xl text-center min-w-[110px] hover:bg-white/20 transition cursor-default">
                        <span class="block text-3xl font-bold text-white mb-1"><?php echo $count_wisata; ?></span>
                        <span class="text-[10px] text-gray-300 uppercase tracking-widest font-bold">Objek Wisata</span>
                    </div>
                    <div class="bg-white/10 backdrop-blur-md border border-white/10 p-4 rounded-2xl text-center min-w-[110px] hover:bg-white/20 transition cursor-default">
                        <span class="block text-3xl font-bold text-white mb-1"><?php echo $count_umkm; ?></span>
                        <span class="text-[10px] text-gray-300 uppercase tracking-widest font-bold">UMKM Lokal</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MAIN CONTENT === -->
<div class="bg-gray-50 min-h-screen py-10 relative">
    <!-- Dekorasi Background -->
    <div class="absolute top-0 left-0 w-full h-64 bg-gradient-to-b from-gray-900/5 to-transparent pointer-events-none"></div>

    <div class="container mx-auto px-4 relative z-10">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            
            <!-- KOLOM KIRI (Konten Utama) -->
            <div class="w-full lg:w-3/4 space-y-10">
                
                <!-- SECTION: TENTANG DESA -->
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 border-l-4 border-primary pl-4">Tentang Desa</h3>
                    <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed text-justify">
                        <?php echo wpautop(esc_html($desa->deskripsi)); ?>
                    </div>
                </div>

                <!-- SECTION: DESTINASI WISATA -->
                <div id="wisata">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">Destinasi Wisata <span class="text-gray-400 font-normal text-lg">(<?php echo $count_wisata; ?>)</span></h2>
                    </div>
                    
                    <?php if ($wisata_list) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($wisata_list as $w) : 
                            // Pastikan link detail wisata juga menggunakan format yang benar
                            $link_w = !empty($w->slug) ? home_url('/wisata/detail/' . $w->slug) : home_url('/detail-wisata/?id=' . $w->id);
                            $img_w = !empty($w->foto_utama) ? $w->foto_utama : 'https://via.placeholder.com/600x400?text=Wisata';
                            $rating = ($w->rating_avg > 0) ? $w->rating_avg : 'New';
                        ?>
                        <a href="<?php echo esc_url($link_w); ?>" class="group bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-xl hover:border-primary/30 transition duration-300 flex flex-col h-full">
                            <div class="relative h-56 overflow-hidden">
                                <img src="<?php echo esc_url($img_w); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60"></div>
                                <div class="absolute top-4 right-4 bg-white/95 backdrop-blur px-2.5 py-1 rounded-lg text-xs font-bold shadow-md flex items-center gap-1">
                                    <i class="fas fa-star text-yellow-500"></i> <?php echo $rating; ?>
                                </div>
                                <div class="absolute bottom-4 left-4 right-4">
                                    <h3 class="font-bold text-white text-xl leading-tight group-hover:text-primary-light transition"><?php echo esc_html($w->nama_wisata); ?></h3>
                                </div>
                            </div>
                            <div class="p-5 flex flex-col flex-1">
                                <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?php echo wp_trim_words($w->deskripsi, 15); ?></p>
                                <div class="mt-auto pt-4 border-t border-gray-50 flex justify-between items-center">
                                    <span class="text-primary font-bold text-lg">
                                        <?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                                    </span>
                                    <span class="text-xs font-semibold text-gray-400 group-hover:text-primary transition">Lihat Detail <i class="fas fa-arrow-right ml-1"></i></span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="bg-white rounded-2xl p-10 text-center border-2 border-dashed border-gray-200">
                            <i class="far fa-map text-gray-300 text-4xl mb-3 block"></i>
                            <p class="text-gray-500">Belum ada data wisata yang terhubung dengan desa ini.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION: UMKM / TOKO -->
                <div id="umkm">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">UMKM & Produk Lokal <span class="text-gray-400 font-normal text-lg">(<?php echo $count_umkm; ?>)</span></h2>
                    </div>
                    
                    <?php if ($pedagang_list) : ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach ($pedagang_list as $p) : 
                            $link_p = !empty($p->slug_toko) ? home_url('/toko/' . $p->slug_toko) : home_url('/profil-toko/?id=' . $p->id);
                            $img_p = !empty($p->foto_profil) ? $p->foto_profil : 'https://ui-avatars.com/api/?name='.urlencode($p->nama_toko).'&background=random';
                        ?>
                        <a href="<?php echo esc_url($link_p); ?>" class="group bg-white rounded-xl border border-gray-100 p-5 hover:shadow-lg hover:-translate-y-1 transition duration-300 flex flex-col items-center text-center">
                            <div class="w-24 h-24 mb-4 relative">
                                <div class="w-full h-full rounded-full p-1 border border-gray-200 group-hover:border-primary transition">
                                    <img src="<?php echo esc_url($img_p); ?>" class="w-full h-full object-cover rounded-full">
                                </div>
                                <?php if($p->status_akun == 'aktif'): ?>
                                <div class="absolute bottom-1 right-1 bg-blue-500 text-white w-6 h-6 rounded-full flex items-center justify-center border-2 border-white text-xs" title="Terverifikasi">
                                    <i class="fas fa-check"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <h4 class="font-bold text-gray-800 text-base leading-tight group-hover:text-primary transition mb-1"><?php echo esc_html($p->nama_toko); ?></h4>
                            <p class="text-xs text-gray-500 mb-3"><?php echo esc_html($p->nama_pemilik); ?></p>
                            <span class="mt-auto inline-block text-[10px] font-bold bg-gray-50 text-gray-500 px-4 py-1.5 rounded-full group-hover:bg-primary group-hover:text-white transition">Kunjungi Toko</span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="bg-white rounded-2xl p-10 text-center border-2 border-dashed border-gray-200">
                            <i class="fas fa-store-alt text-gray-300 text-4xl mb-3 block"></i>
                            <p class="text-gray-500">Belum ada UMKM terdaftar di desa ini.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- KOLOM KANAN (SIDEBAR STICKY) -->
            <div class="w-full lg:w-1/4">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Informasi Kontak Card -->
                    <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 relative overflow-hidden">
                        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-primary to-green-400"></div>
                        <h3 class="font-bold text-gray-900 mb-5 flex items-center gap-2">
                            <i class="far fa-address-card text-primary"></i> Kontak Desa
                        </h3>
                        
                        <div class="space-y-5">
                            <div class="flex gap-3">
                                <div class="shrink-0 mt-0.5"><i class="fas fa-map-pin text-gray-400 w-5 text-center"></i></div>
                                <div>
                                    <span class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">Alamat</span>
                                    <p class="text-sm text-gray-700 leading-snug">
                                        <?php echo esc_html($desa->alamat_lengkap ?: 'Alamat kantor desa belum dilengkapi.'); ?>
                                    </p>
                                </div>
                            </div>

                            <?php if(!empty($desa->no_rekening_desa)): ?>
                            <div class="flex gap-3">
                                <div class="shrink-0 mt-0.5"><i class="fas fa-university text-gray-400 w-5 text-center"></i></div>
                                <div class="w-full">
                                    <span class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1">Rekening Resmi</span>
                                    <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-xs text-gray-600"><?php echo esc_html($desa->nama_bank_desa); ?></span>
                                        </div>
                                        <div class="font-mono font-bold text-gray-800 text-sm tracking-wide mb-1 select-all"><?php echo esc_html($desa->no_rekening_desa); ?></div>
                                        <div class="text-[10px] text-gray-500 truncate border-t border-gray-200 pt-1 mt-1">
                                            a.n <?php echo esc_html($desa->atas_nama_rekening_desa); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Maps Button -->
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <?php 
                                $maps_query = urlencode($desa->alamat_lengkap ?: $desa->nama_desa . ' ' . $desa->kabupaten);
                            ?>
                            <a href="https://maps.google.com/?q=<?php echo $maps_query; ?>" target="_blank" class="flex items-center justify-center w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-sm transition shadow-lg shadow-blue-200 transform active:scale-95 group">
                                <i class="fas fa-map-marked-alt mr-2 group-hover:animate-bounce"></i> Buka Peta Lokasi
                            </a>
                        </div>
                    </div>
                    
                    <!-- Share Button (Optional) -->
                    <div class="bg-blue-50 p-4 rounded-xl text-center border border-blue-100">
                        <p class="text-xs text-blue-600 mb-2 font-medium">Bagikan Profil Desa ini</p>
                        <div class="flex justify-center gap-2">
                             <a href="https://wa.me/?text=Lihat%20Profil%20Desa%20<?php echo urlencode($desa->nama_desa); ?>%20di%20<?php echo urlencode(get_permalink()); ?>" target="_blank" class="w-8 h-8 rounded-full bg-green-500 text-white flex items-center justify-center hover:bg-green-600 transition"><i class="fab fa-whatsapp"></i></a>
                             <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>" target="_blank" class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition"><i class="fab fa-facebook-f"></i></a>
                             <button onclick="navigator.clipboard.writeText(window.location.href); alert('Link disalin!');" class="w-8 h-8 rounded-full bg-gray-500 text-white flex items-center justify-center hover:bg-gray-600 transition"><i class="fas fa-link"></i></button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>