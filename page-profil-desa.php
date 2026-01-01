<?php
/**
 * Template Name: Profil Desa Custom
 * URL akses: /desa/slug-desa (melalui Rewrite Rule di functions.php) 
 */

get_header();

global $wpdb;
$table_desa     = $wpdb->prefix . 'dw_desa';
$table_wisata   = $wpdb->prefix . 'dw_wisata';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

// 1. Tangkap Slug (dari URL /desa/slug...)
$slug_desa = get_query_var('dw_slug_desa');

// Fallback jika diakses manual via ?id=
$desa_id_param = isset($_GET['id']) ? intval($_GET['id']) : 0;

$desa = null;

// 2. Query Data Desa
if (!empty($slug_desa)) {
    // Decode slug untuk keamanan query
    $desa = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE slug_desa = %s AND status = 'aktif'", $slug_desa));
} 
elseif ($desa_id_param > 0) {
    $desa = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE id = %d AND status = 'aktif'", $desa_id_param));
}

// 3. Handler Jika Tidak Ditemukan
if (!$desa) {
    echo '<div class="min-h-[70vh] flex flex-col items-center justify-center text-center p-6 bg-gray-50">';
    echo '<div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center mb-6 animate-pulse"><i class="fas fa-landmark text-4xl text-gray-400"></i></div>';
    echo '<h1 class="text-3xl font-bold text-gray-800 mb-2">Desa Tidak Ditemukan</h1>';
    echo '<p class="text-gray-500 mb-8 max-w-md mx-auto">Halaman desa yang Anda cari mungkin belum terdaftar atau URL salah.</p>';
    echo '<a href="'.home_url('/').'" class="bg-green-600 text-white px-8 py-3 rounded-full font-bold hover:bg-green-700 transition shadow-lg">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// 4. Ambil Data Relasi (Wisata & UMKM)
$wisata_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE id_desa = %d AND status='aktif' ORDER BY created_at DESC", $desa->id));
$pedagang_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_akun='aktif' ORDER BY created_at DESC", $desa->id));

$count_wisata = count($wisata_list);
$count_umkm = count($pedagang_list);
$foto_desa = !empty($desa->foto) ? $desa->foto : get_template_directory_uri() . '/assets/img/placeholder-desa.jpg';

// --- PERBAIKAN: GENERATE CURRENT URL UNTUK SHARE ---
// Mengambil URL lengkap saat ini secara dinamis (termasuk https dan domain)
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>

<!-- === HERO SECTION === -->
<div class="relative h-[380px] lg:h-[480px] bg-gray-900 overflow-hidden group">
    <!-- Background Image dengan Overlay Gradient Halus -->
    <div class="absolute inset-0">
        <img src="<?php echo esc_url($foto_desa); ?>" class="w-full h-full object-cover opacity-60 transition duration-[3000ms] group-hover:scale-105" alt="Desa <?php echo esc_attr($desa->nama_desa); ?>">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/60 to-transparent"></div>
    </div>
    
    <div class="absolute bottom-0 left-0 w-full p-6 md:p-10 z-10">
        <div class="container mx-auto max-w-7xl">
            <div class="flex flex-col md:flex-row items-end justify-between gap-6">
                <div class="max-w-4xl w-full">
                    <!-- Badges -->
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        <span class="bg-green-600/90 backdrop-blur-md text-white border border-white/20 text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-2 shadow-lg">
                            <i class="fas fa-check-circle text-white"></i> Desa Terverifikasi
                        </span>
                        <?php if(!empty($desa->website_desa)): ?>
                        <a href="<?php echo esc_url($desa->website_desa); ?>" target="_blank" class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white border border-white/20 text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-2 transition">
                            <i class="fas fa-globe"></i> Website Resmi
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Title & Location -->
                    <h1 class="text-3xl md:text-5xl lg:text-6xl font-extrabold text-white mb-3 tracking-tight leading-tight drop-shadow-lg">
                        Desa <?php echo esc_html($desa->nama_desa); ?>
                    </h1>
                    
                    <p class="text-gray-200 text-sm md:text-lg mb-0 font-light flex flex-wrap items-center gap-2 md:gap-4 drop-shadow-md">
                        <span class="flex items-center gap-1.5"><i class="fas fa-map-marker-alt text-red-500"></i> <?php echo esc_html($desa->kecamatan); ?>, <?php echo esc_html($desa->kabupaten); ?></span>
                        <span class="hidden md:inline text-gray-400">|</span>
                        <span class="font-mono text-xs md:text-sm text-green-300 bg-green-900/30 px-2 py-0.5 rounded border border-green-500/30">@<?php echo esc_html($desa->slug_desa); ?></span>
                    </p>
                </div>

                <!-- Stats Cards (Desktop Only - Mobile Moved Below) -->
                <div class="hidden md:flex gap-4 shrink-0">
                    <div class="bg-white/10 backdrop-blur-xl border border-white/20 p-5 rounded-2xl text-center min-w-[120px] shadow-2xl">
                        <span class="block text-3xl font-extrabold text-white mb-1 drop-shadow-md"><?php echo $count_wisata; ?></span>
                        <span class="text-[10px] text-gray-200 uppercase tracking-widest font-bold">Wisata</span>
                    </div>
                    <div class="bg-white/10 backdrop-blur-xl border border-white/20 p-5 rounded-2xl text-center min-w-[120px] shadow-2xl">
                        <span class="block text-3xl font-extrabold text-white mb-1 drop-shadow-md"><?php echo $count_umkm; ?></span>
                        <span class="text-[10px] text-gray-200 uppercase tracking-widest font-bold">UMKM</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MOBILE STATS BAR (Sticky) === -->
<div class="md:hidden bg-white border-b border-gray-100 sticky top-[60px] z-30 shadow-sm">
    <div class="grid grid-cols-2 divide-x divide-gray-100">
        <div class="p-3 text-center">
            <span class="block text-xl font-bold text-gray-800"><?php echo $count_wisata; ?></span>
            <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">Destinasi Wisata</span>
        </div>
        <div class="p-3 text-center">
            <span class="block text-xl font-bold text-gray-800"><?php echo $count_umkm; ?></span>
            <span class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">UMKM Lokal</span>
        </div>
    </div>
</div>

<!-- === MAIN CONTENT === -->
<div class="bg-gray-50 min-h-screen py-8 md:py-12 relative font-sans">
    <div class="container mx-auto px-4 max-w-7xl relative z-10">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            
            <!-- KOLOM KIRI (Konten Utama) -->
            <div class="w-full lg:w-3/4 space-y-10">
                
                <!-- SECTION: TENTANG DESA -->
                <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100 transition hover:shadow-md">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                        <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">Tentang Desa</h2>
                    </div>
                    <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed text-justify">
                        <?php echo wpautop(esc_html($desa->deskripsi)); ?>
                    </div>
                </div>

                <!-- SECTION: DESTINASI WISATA -->
                <div id="wisata" class="scroll-mt-28">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            <span class="w-1.5 h-8 bg-green-600 rounded-full block"></span>
                            Destinasi Wisata
                        </h2>
                    </div>
                    
                    <?php if ($wisata_list) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($wisata_list as $w) : 
                            // Link harus mengarah ke /wisata/{slug} sesuai rewrite rule
                            $link_w = !empty($w->slug) ? home_url('/wisata/' . $w->slug) : home_url('/?dw_type=wisata&dw_slug=' . $w->slug);
                            
                            $img_w = !empty($w->foto_utama) ? $w->foto_utama : get_template_directory_uri().'/assets/img/placeholder-wisata.jpg';
                            $rating = ($w->rating_avg > 0) ? number_format($w->rating_avg, 1) : 'Baru';
                        ?>
                        <a href="<?php echo esc_url($link_w); ?>" class="group bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-xl hover:border-green-200 hover:-translate-y-1 transition duration-300 flex flex-col h-full">
                            <div class="relative h-52 overflow-hidden">
                                <img src="<?php echo esc_url($img_w); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700" alt="<?php echo esc_attr($w->nama_wisata); ?>">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-60"></div>
                                <div class="absolute top-3 right-3 bg-white/95 backdrop-blur px-2.5 py-1 rounded-lg text-xs font-bold shadow-md flex items-center gap-1">
                                    <i class="fas fa-star text-yellow-500"></i> <?php echo $rating; ?>
                                </div>
                                <div class="absolute bottom-4 left-4 right-4">
                                    <h3 class="font-bold text-white text-lg leading-tight group-hover:text-green-200 transition line-clamp-1"><?php echo esc_html($w->nama_wisata); ?></h3>
                                    <p class="text-xs text-gray-200 mt-1 flex items-center gap-1"><i class="far fa-clock"></i> <?php echo esc_html($w->jam_buka ?: '08:00 - 17:00'); ?></p>
                                </div>
                            </div>
                            <div class="p-5 flex flex-col flex-1">
                                <p class="text-sm text-gray-500 mb-4 line-clamp-2 leading-relaxed"><?php echo wp_trim_words($w->deskripsi, 18); ?></p>
                                <div class="mt-auto pt-4 border-t border-gray-50 flex justify-between items-center">
                                    <div>
                                        <span class="text-[10px] text-gray-400 uppercase font-bold block mb-0.5">Tiket Masuk</span>
                                        <span class="text-green-600 font-bold text-lg">
                                            <?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                                        </span>
                                    </div>
                                    <span class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-green-600 group-hover:text-white transition shadow-sm">
                                        <i class="fas fa-arrow-right text-sm"></i>
                                    </span>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="bg-white rounded-2xl p-10 text-center border-2 border-dashed border-gray-200">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                <i class="far fa-map text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800">Belum Ada Wisata</h4>
                            <p class="text-gray-500 text-sm mt-1">Desa ini belum menghubungkan data wisatanya.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION: UMKM / TOKO -->
                <div id="umkm" class="scroll-mt-28">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            <span class="w-1.5 h-8 bg-orange-500 rounded-full block"></span>
                            UMKM & Produk Lokal
                        </h2>
                    </div>
                    
                    <?php if ($pedagang_list) : ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
                        <?php foreach ($pedagang_list as $p) : 
                            $link_p = !empty($p->slug_toko) ? home_url('/toko/' . $p->slug_toko) : home_url('/profil-toko/?id=' . $p->id);
                            $img_p = !empty($p->foto_profil) ? $p->foto_profil : 'https://ui-avatars.com/api/?name='.urlencode($p->nama_toko).'&background=random&color=fff&bold=true';
                        ?>
                        <a href="<?php echo esc_url($link_p); ?>" class="group bg-white rounded-xl border border-gray-100 p-5 hover:shadow-lg hover:border-green-200 hover:-translate-y-1 transition duration-300 flex flex-col items-center text-center">
                            <div class="w-20 h-20 md:w-24 md:h-24 mb-4 relative">
                                <div class="w-full h-full rounded-full p-1 border-2 border-gray-100 group-hover:border-green-500 transition overflow-hidden">
                                    <img src="<?php echo esc_url($img_p); ?>" class="w-full h-full object-cover rounded-full" alt="<?php echo esc_attr($p->nama_toko); ?>">
                                </div>
                                <?php if($p->status_akun == 'aktif'): ?>
                                <div class="absolute bottom-1 right-1 bg-blue-500 text-white w-6 h-6 rounded-full flex items-center justify-center border-2 border-white text-[10px]" title="Terverifikasi">
                                    <i class="fas fa-check"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <h4 class="font-bold text-gray-800 text-sm md:text-base leading-tight group-hover:text-green-600 transition mb-1 line-clamp-1"><?php echo esc_html($p->nama_toko); ?></h4>
                            <p class="text-xs text-gray-500 mb-4 line-clamp-1">Owner: <?php echo esc_html($p->nama_pemilik); ?></p>
                            
                            <span class="mt-auto inline-flex items-center gap-1 text-[10px] md:text-xs font-bold bg-gray-50 text-gray-500 px-4 py-2 rounded-full group-hover:bg-green-600 group-hover:text-white transition w-full justify-center">
                                Kunjungi <i class="fas fa-arrow-right opacity-0 group-hover:opacity-100 transition-opacity w-0 group-hover:w-3"></i>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="bg-white rounded-2xl p-10 text-center border-2 border-dashed border-gray-200">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                                <i class="fas fa-store-alt text-2xl"></i>
                            </div>
                            <h4 class="font-bold text-gray-800">Belum Ada UMKM</h4>
                            <p class="text-gray-500 text-sm mt-1">Belum ada toko yang terdaftar di desa ini.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- KOLOM KANAN (SIDEBAR STICKY) -->
            <div class="w-full lg:w-1/4">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Informasi Kontak Card -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-100 border border-gray-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-600 to-emerald-500 p-4">
                            <h3 class="font-bold text-white flex items-center gap-2">
                                <i class="far fa-address-card"></i> Kontak Desa
                            </h3>
                        </div>
                        
                        <div class="p-6 space-y-5">
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 shrink-0"><i class="fas fa-map-pin text-sm"></i></div>
                                <div>
                                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Alamat Kantor</span>
                                    <p class="text-sm text-gray-700 leading-snug font-medium">
                                        <?php echo esc_html($desa->alamat_lengkap ?: 'Alamat belum dilengkapi.'); ?>
                                    </p>
                                </div>
                            </div>

                            <?php if(!empty($desa->no_rekening_desa)): ?>
                            <div class="flex gap-3">
                                <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 shrink-0"><i class="fas fa-university text-sm"></i></div>
                                <div class="w-full">
                                    <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-1">Rekening Resmi</span>
                                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 group hover:border-green-200 transition">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-bold text-xs text-gray-600"><?php echo esc_html($desa->nama_bank_desa); ?></span>
                                            <i class="fas fa-check-circle text-green-500 text-[10px]"></i>
                                        </div>
                                        <div class="font-mono font-bold text-gray-800 text-sm tracking-wide mb-1 select-all cursor-pointer group-hover:text-green-600 transition" title="Klik untuk menyalin" onclick="navigator.clipboard.writeText(this.innerText); alert('Nomor rekening disalin!');">
                                            <?php echo esc_html($desa->no_rekening_desa); ?>
                                        </div>
                                        <div class="text-[10px] text-gray-500 truncate border-t border-gray-200 pt-1 mt-1">
                                            a.n <?php echo esc_html($desa->atas_nama_rekening_desa); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Maps Button -->
                            <?php 
                                $maps_query = urlencode($desa->alamat_lengkap ?: $desa->nama_desa . ' ' . $desa->kabupaten);
                            ?>
                            <a href="https://maps.google.com/?q=<?php echo $maps_query; ?>" target="_blank" class="flex items-center justify-center w-full bg-white border-2 border-blue-50 hover:border-blue-500 hover:bg-blue-500 hover:text-white text-blue-600 font-bold py-3 rounded-xl text-sm transition gap-2 group mt-2">
                                <i class="fas fa-map-marked-alt group-hover:animate-bounce"></i> Buka Peta
                            </a>
                        </div>
                    </div>
                    
                    <!-- Share Button (Updated with Real URL) -->
                    <div class="bg-blue-50 p-5 rounded-2xl text-center border border-blue-100 shadow-sm">
                        <p class="text-xs text-blue-800 mb-3 font-bold uppercase tracking-wide">Bagikan Profil Ini</p>
                        <div class="flex justify-center gap-3">
                             <a href="https://wa.me/?text=Lihat%20Profil%20Desa%20<?php echo urlencode($desa->nama_desa); ?>%20di%20<?php echo urlencode($current_url); ?>" target="_blank" class="w-10 h-10 rounded-full bg-green-500 text-white flex items-center justify-center hover:bg-green-600 hover:scale-110 transition shadow-lg shadow-green-200"><i class="fab fa-whatsapp text-lg"></i></a>
                             <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>" target="_blank" class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 hover:scale-110 transition shadow-lg shadow-blue-200"><i class="fab fa-facebook-f text-lg"></i></a>
                             <button onclick="navigator.clipboard.writeText('<?php echo esc_js($current_url); ?>'); alert('Link disalin!');" class="w-10 h-10 rounded-full bg-white text-gray-600 flex items-center justify-center hover:bg-gray-100 hover:text-green-600 hover:scale-110 transition shadow-lg shadow-gray-200 border border-gray-100"><i class="fas fa-link text-lg"></i></button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>