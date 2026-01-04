<?php
/**
 * Template Name: Single Wisata (Ultimate Refined Responsive)
 * Description: Desain detail wisata premium dengan Modal Peta, Sticky Nav Mobile, & Rekomendasi Desa.
 */

get_header();

global $wpdb, $post;

// --- 1. LOGIC & DATA FETCHING ---
$slug = get_query_var('dw_slug');
if (empty($slug)) $slug = get_query_var('name');
if (empty($slug) && isset($post->post_name)) $slug = $post->post_name;

$t_wisata = $wpdb->prefix . 'dw_wisata';
$t_desa   = $wpdb->prefix . 'dw_desa';

// Query Data Utama
$sql = $wpdb->prepare(
    "SELECT w.*, 
            d.id as desa_id,
            d.nama_desa, 
            d.alamat_lengkap as alamat_desa, 
            d.kabupaten, 
            d.provinsi, 
            d.foto as foto_desa_logo, 
            d.slug_desa
    FROM $t_wisata w 
    JOIN $t_desa d ON w.id_desa = d.id 
    WHERE w.slug = %s AND w.status = 'aktif'",
    $slug
);

$wisata = $wpdb->get_row($sql);

// 404 Handler
if (!$wisata) {
    status_header(404);
    if(locate_template('404.php')) {
        get_template_part('404');
    } else {
        echo "<div class='min-h-screen flex items-center justify-center bg-gray-50 flex-col gap-6 text-center p-6'>";
        echo "<div class='w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center text-gray-400 text-4xl mb-2'><i class='fas fa-map-signs'></i></div>";
        echo "<h1 class='text-3xl font-bold text-gray-800'>Tujuan Tidak Ditemukan</h1>";
        echo "<p class='text-gray-500 max-w-md mx-auto'>Halaman wisata yang Anda cari mungkin telah dihapus atau URL salah.</p>";
        echo "<a href='".home_url('/')."' class='px-8 py-3 bg-green-600 text-white rounded-full font-bold shadow-lg hover:bg-green-700 transition'>Kembali ke Beranda</a>";
        echo "</div>";
    }
    get_footer(); 
    exit;
}

// --- 2. DATA PREPARATION ---
$id_wisata      = $wisata->id;
$id_desa        = $wisata->desa_id; 
$judul          = esc_html($wisata->nama_wisata);
$deskripsi      = wp_kses_post($wisata->deskripsi);
$harga_raw      = floatval($wisata->harga_tiket);
$harga          = ($harga_raw > 0) ? number_format($harga_raw, 0, ',', '.') : 'Gratis';
$jam_buka       = !empty($wisata->jam_buka) ? esc_html($wisata->jam_buka) : '08:00 - 17:00';
$kontak         = esc_attr($wisata->kontak_pengelola); 
$lokasi_raw     = $wisata->lokasi_maps; 

// Gambar & Avatar
$foto_utama     = !empty($wisata->foto_utama) ? $wisata->foto_utama : get_template_directory_uri() . '/assets/img/placeholder-wisata.jpg';
$foto_desa      = !empty($wisata->foto_desa_logo) ? $wisata->foto_desa_logo : get_template_directory_uri() . '/assets/img/icon-desa-default.png';

// Galeri Logic
$galeri_items   = !empty($wisata->galeri) ? json_decode($wisata->galeri, true) : [];
if (!is_array($galeri_items)) $galeri_items = [];
if(!in_array($foto_utama, $galeri_items)) array_unshift($galeri_items, $foto_utama);
$galeri_items = array_values(array_unique($galeri_items)); 

// Fasilitas
$fasilitas_list = array_filter(array_map('trim', explode(',', $wisata->fasilitas)));
if (empty($fasilitas_list)) $fasilitas_list = ['Area Parkir', 'Toilet Umum', 'Spot Foto', 'Musholla'];

// Lokasi String
$alamat_display = sprintf('%s, %s, %s', $wisata->nama_desa, $wisata->kabupaten, $wisata->provinsi);
$maps_query = urlencode($judul . ' ' . $wisata->nama_desa . ' ' . $wisata->kabupaten);
$google_maps_link = "https://www.google.com/maps/search/?api=1&query=" . $maps_query;
$google_maps_embed_url = "https://maps.google.com/maps?q=" . $maps_query . "&t=&z=14&ie=UTF8&iwloc=&output=embed";

// Rating & URL
$rating_val     = floatval($wisata->rating_avg);
$total_review   = intval($wisata->total_ulasan);
$current_url    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

// Ulasan Terbaru
$t_ulasan = $wpdb->prefix . 'dw_ulasan';
$t_user   = $wpdb->prefix . 'users'; 
$ulasan_list = $wpdb->get_results($wpdb->prepare(
    "SELECT u.*, us.display_name, us.user_email 
     FROM $t_ulasan u
     LEFT JOIN $t_user us ON u.user_id = us.ID
     WHERE u.target_id = %d AND u.tipe = 'wisata' AND u.status_moderasi = 'approved'
     ORDER BY u.created_at DESC LIMIT 3",
    $id_wisata
));

// --- UPDATE LOGIC (FIXED): Rekomendasi Wisata (Satu Desa) ---
// PERBAIKAN: Menambahkan JOIN ke tabel desa untuk mengambil kolom 'kabupaten'
$related_wisata = $wpdb->get_results($wpdb->prepare(
    "SELECT w.id, w.nama_wisata, w.slug, w.foto_utama, w.harga_tiket, w.rating_avg, d.kabupaten 
     FROM $t_wisata w
     JOIN $t_desa d ON w.id_desa = d.id
     WHERE w.status = 'aktif' AND w.id_desa = %d AND w.id != %d 
     ORDER BY RAND() LIMIT 4",
    $id_desa, $id_wisata
));

if (empty($related_wisata)) {
    // Fallback: Ambil wisata lain (random) jika di desa tersebut kosong
    $related_wisata = $wpdb->get_results($wpdb->prepare(
        "SELECT w.id, w.nama_wisata, w.slug, w.foto_utama, w.harga_tiket, w.rating_avg, d.kabupaten 
         FROM $t_wisata w
         JOIN $t_desa d ON w.id_desa = d.id
         WHERE w.status = 'aktif' AND w.id != %d 
         ORDER BY RAND() LIMIT 4",
        $id_wisata
    ));
    $related_title = "Wisata Populer Lainnya";
} else {
    $related_title = "Wisata Lain di Desa " . esc_html($wisata->nama_desa);
}
?>

<!-- STYLE KHUSUS -->
<style>
    :root {
        --primary: #10b981; /* Emerald 500 */
        --primary-dark: #059669;
    }
    html { scroll-behavior: smooth; }
    body { background-color: #FAFAFA; color: #1e293b; font-family: 'Inter', sans-serif; }
    
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    /* Navigation Sticky Active State */
    .nav-link { 
        position: relative; 
        font-weight: 500; 
        color: #64748b; 
        padding: 1rem 0; 
        white-space: nowrap;
        transition: color 0.2s; 
    }
    .nav-link:hover { color: var(--primary); }
    .nav-link.active { color: var(--primary); font-weight: 700; }
    .nav-link.active::after {
        content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
        height: 3px; background: var(--primary); border-radius: 3px 3px 0 0;
    }

    /* FIX STICKY NAV UNTUK ADMIN BAR WORDPRESS */
    body.admin-bar #sticky-nav {
        top: 32px !important;
    }
    @media screen and (max-width: 782px) {
        body.admin-bar #sticky-nav {
            top: 46px !important;
        }
    }

    /* Pattern Background for Map */
    .bg-pattern-map {
        background-color: #f3f4f6;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23e5e7eb' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    
    .pb-safe { padding-bottom: calc(90px + env(safe-area-inset-bottom)); }
    
    .modal-enter { opacity: 0; transform: scale(0.95); }
    .modal-enter-active { opacity: 1; transform: scale(1); transition: all 0.3s ease-out; }
    .modal-exit { opacity: 1; transform: scale(1); }
    .modal-exit-active { opacity: 0; transform: scale(0.95); transition: all 0.2s ease-in; }
</style>

<div class="min-h-screen pb-safe lg:pb-0">

    <!-- === HERO SECTION === -->
    <div class="relative h-[400px] lg:h-[550px] bg-gray-900 overflow-hidden group">
        <!-- Background Image -->
        <div class="absolute inset-0">
            <img src="<?php echo esc_url($foto_utama); ?>" class="w-full h-full object-cover opacity-80 transition duration-[3000ms] group-hover:scale-105" alt="<?php echo esc_attr($judul); ?>">
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent"></div>
        </div>
        
        <!-- Top Nav -->
        <div class="absolute top-0 left-0 right-0 p-4 md:p-6 z-20 flex justify-between items-start">
            <a href="<?php echo home_url('/'); ?>" class="w-10 h-10 rounded-full bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white hover:bg-white/20 transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="flex gap-3">
                <button onclick="navigator.clipboard.writeText('<?php echo esc_js($current_url); ?>'); alert('Link disalin!')" class="w-10 h-10 rounded-full bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white hover:bg-white/20 transition" title="Salin Link">
                    <i class="fas fa-share-alt"></i>
                </button>
                <button onclick="alert('Disimpan ke Favorit!')" class="w-10 h-10 rounded-full bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-white hover:bg-white/20 transition group/heart" title="Simpan">
                    <i class="far fa-heart group-hover/heart:text-red-400 transition-colors"></i>
                </button>
            </div>
        </div>

        <div class="absolute bottom-0 left-0 w-full p-4 md:p-10 z-10">
            <div class="max-w-7xl mx-auto w-full">
                <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 md:gap-6">
                    <div class="max-w-3xl">
                        <!-- Badges -->
                        <div class="flex flex-wrap items-center gap-2 md:gap-3 mb-3 md:mb-4">
                            <span class="bg-emerald-600/90 backdrop-blur-md text-white border border-white/20 text-[10px] md:text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-2 shadow-lg uppercase tracking-wider">
                                <?php echo !empty($wisata->kategori) ? esc_html($wisata->kategori) : 'Destinasi'; ?>
                            </span>
                            <span class="bg-white/10 backdrop-blur-md text-white border border-white/20 text-[10px] md:text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-2">
                                <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($wisata->kabupaten); ?>
                            </span>
                        </div>
                        
                        <!-- Title -->
                        <h1 class="text-2xl md:text-5xl lg:text-6xl font-extrabold text-white mb-2 md:mb-3 tracking-tight leading-tight drop-shadow-xl">
                            <?php echo $judul; ?>
                        </h1>
                        
                        <!-- Meta Info -->
                        <div class="flex flex-wrap items-center gap-3 md:gap-4 text-white/90 text-xs md:text-sm font-medium drop-shadow-md">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-star text-yellow-400"></i> 
                                <span class="font-bold text-white"><?php echo number_format($rating_val, 1); ?></span>
                                <span class="opacity-70">(<?php echo $total_review; ?> Ulasan)</span>
                            </div>
                            <span class="inline w-1 h-1 bg-white/50 rounded-full"></span>
                            <div class="flex items-center gap-1.5 opacity-90">
                                <i class="far fa-clock"></i> <?php echo $jam_buka; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Glass Stats Card -->
                    <div class="hidden lg:flex gap-4 shrink-0">
                        <div class="bg-white/10 backdrop-blur-xl border border-white/20 p-5 rounded-2xl text-center min-w-[140px] shadow-2xl">
                            <span class="text-[10px] text-gray-300 uppercase tracking-widest font-bold block mb-1">Tiket Masuk</span>
                            <span class="block text-2xl font-extrabold text-white mb-0">
                                <?php echo ($harga == 'Gratis') ? 'Gratis' : '<span class="text-xs font-normal align-top">Rp</span> ' . str_replace('Rp ', '', $harga); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- === STICKY NAVIGATION (Mobile & Desktop) === -->
    <div id="sticky-nav" class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-gray-200 shadow-sm transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 md:px-8">
            <div class="flex gap-6 md:gap-8 text-sm overflow-x-auto no-scrollbar whitespace-nowrap mask-linear-fade">
                <a href="#ikhtisar" class="nav-link active">Ikhtisar</a>
                <a href="#galeri" class="nav-link">Galeri</a>
                <a href="#fasilitas" class="nav-link">Fasilitas</a>
                <a href="#lokasi" class="nav-link">Lokasi</a>
                <a href="#ulasan" class="nav-link">Ulasan</a>
            </div>
        </div>
    </div>

    <!-- === MAIN CONTENT === -->
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-6 lg:py-12 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            
            <!-- LEFT CONTENT (8/12) -->
            <div class="lg:col-span-8 space-y-8 lg:space-y-10">
                
                <!-- IKHTISAR -->
                <div id="ikhtisar" class="scroll-mt-36 lg:scroll-mt-32">
                    <div class="bg-white p-5 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-4 md:mb-6 pb-4 border-b border-gray-50">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                                <i class="fas fa-align-left"></i>
                            </div>
                            <h2 class="text-lg md:text-xl font-bold text-gray-900">Tentang Wisata</h2>
                        </div>
                        <div class="prose prose-base md:prose-lg max-w-none text-gray-600 leading-relaxed text-justify">
                            <?php echo wpautop($deskripsi); ?>
                        </div>
                    </div>
                </div>

                <!-- GALERI -->
                <?php if(count($galeri_items) > 0): ?>
                <div id="galeri" class="scroll-mt-36 lg:scroll-mt-32">
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-4 md:mb-6 flex items-center gap-2">
                        <i class="far fa-images text-emerald-500"></i> Galeri Foto
                    </h3>
                    
                    <div class="hidden lg:grid grid-cols-4 grid-rows-2 gap-4 h-[450px] rounded-3xl overflow-hidden">
                        <?php foreach($galeri_items as $idx => $img): if($idx > 4) break; 
                            $gridClass = ($idx === 0) ? 'col-span-2 row-span-2' : 'col-span-1 row-span-1';
                        ?>
                        <div class="relative group <?php echo $gridClass; ?> bg-gray-100 cursor-pointer overflow-hidden" onclick="openLightbox('<?php echo esc_url($img); ?>')">
                            <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition duration-300"></div>
                            <?php if($idx === 4 && count($galeri_items) > 5): ?>
                                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center text-white font-medium group-hover:bg-black/70 transition">
                                    <span class="text-lg">+<?php echo count($galeri_items) - 5; ?> Foto</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="lg:hidden flex gap-3 overflow-x-auto no-scrollbar -mx-4 px-4 pb-2">
                        <?php foreach($galeri_items as $img): ?>
                            <img src="<?php echo esc_url($img); ?>" class="h-48 w-72 object-cover rounded-2xl shadow-sm flex-shrink-0 snap-center border border-gray-100" onclick="window.open(this.src, '_blank')">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- FASILITAS -->
                <div id="fasilitas" class="scroll-mt-36 lg:scroll-mt-32">
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-4 md:mb-6 flex items-center gap-2">
                        <i class="fas fa-concierge-bell text-emerald-500"></i> Fasilitas
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4">
                        <?php foreach($fasilitas_list as $f): ?>
                        <div class="flex items-center gap-3 p-3 md:p-4 bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition duration-300 group">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                            <span class="text-gray-700 font-medium text-xs md:text-sm"><?php echo esc_html($f); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- LOKASI -->
                <div id="lokasi" class="scroll-mt-36 lg:scroll-mt-32">
                    <div class="flex justify-between items-center mb-4 md:mb-6">
                        <h3 class="text-lg md:text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-map-marked-alt text-emerald-500"></i> Lokasi
                        </h3>
                    </div>
                    
                    <div class="bg-white p-2 rounded-3xl border border-gray-200 shadow-sm relative group overflow-hidden">
                        <div class="w-full h-[200px] md:h-[300px] bg-pattern-map rounded-2xl relative overflow-hidden flex flex-col items-center justify-center text-center">
                            <div class="absolute inset-0 bg-gradient-to-b from-transparent to-white/80"></div>
                            
                            <button onclick="openMapModal()" class="relative z-10 flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-full font-bold shadow-lg shadow-emerald-500/30 transition transform hover:-translate-y-1">
                                <i class="fas fa-map-marked-alt"></i> Lihat Lokasi Peta
                            </button>
                            <p class="relative z-10 text-xs text-gray-500 mt-3 font-medium bg-white/80 px-3 py-1 rounded-full backdrop-blur-sm">
                                <?php echo esc_html($alamat_display); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ULASAN -->
                <div id="ulasan" class="scroll-mt-36 lg:scroll-mt-32 pt-8 border-t border-gray-200">
                    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 md:mb-8 gap-4">
                        <div>
                            <h3 class="text-lg md:text-xl font-bold text-gray-900">Ulasan Pengunjung</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-2xl font-extrabold text-gray-900"><?php echo number_format($rating_val, 1); ?></span>
                                <div class="text-yellow-400 text-xs">
                                    <?php for($i=1; $i<=5; $i++) echo ($i <= round($rating_val)) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star text-gray-300"></i>'; ?>
                                </div>
                                <span class="text-sm text-gray-500">• <?php echo $total_review; ?> Ulasan</span>
                            </div>
                        </div>
                    </div>

                    <?php if ($ulasan_list): ?>
                        <div class="grid gap-4 md:gap-6">
                            <?php foreach($ulasan_list as $u): 
                                $avatar = get_avatar_url($u->user_email, ['size' => 64]);
                            ?>
                            <div class="bg-white p-5 md:p-6 rounded-3xl border border-gray-100 shadow-sm transition hover:shadow-md">
                                <div class="flex gap-4">
                                    <img src="<?php echo esc_url($avatar); ?>" class="w-10 h-10 md:w-12 md:h-12 rounded-full object-cover border-2 border-gray-50 bg-gray-100">
                                    <div class="flex-1">
                                        <div class="flex justify-between items-center mb-1">
                                            <h4 class="font-bold text-gray-900 text-sm"><?php echo esc_html($u->display_name ?: 'Pengunjung'); ?></h4>
                                            <span class="text-xs text-gray-400 font-medium"><?php echo date('d M Y', strtotime($u->created_at)); ?></span>
                                        </div>
                                        <div class="flex text-yellow-400 text-xs mb-3">
                                            <?php for($i=1; $i<=5; $i++) echo ($i <= $u->rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star text-gray-200"></i>'; ?>
                                        </div>
                                        <p class="text-gray-600 text-sm leading-relaxed">"<?php echo esc_html($u->komentar); ?>"</p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 bg-white rounded-3xl border border-dashed border-gray-200">
                            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-300">
                                <i class="far fa-comment-dots text-xl"></i>
                            </div>
                            <p class="text-gray-500 text-sm">Belum ada ulasan saat ini.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 4. REKOMENDASI WISATA LAIN DARI DESA YANG SAMA -->
                <?php if ($related_wisata): ?>
                <div class="pt-8 border-t border-gray-200">
                    <h3 class="text-lg md:text-2xl font-bold text-gray-900 mb-6 md:mb-8 flex items-center gap-2">
                        <i class="fas fa-map-signs text-emerald-500"></i> <?php echo esc_html($related_title); ?>
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <?php foreach ($related_wisata as $rw): 
                            $rw_img = !empty($rw->foto_utama) ? $rw->foto_utama : get_template_directory_uri().'/assets/img/placeholder-wisata.jpg';
                            $rw_link = home_url('/wisata/' . $rw->slug);
                            $rw_harga = ($rw->harga_tiket > 0) ? 'Rp '.number_format($rw->harga_tiket,0,',','.') : 'Gratis';
                        ?>
                        <a href="<?php echo esc_url($rw_link); ?>" class="group block bg-white rounded-[1.5rem] overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition duration-300">
                            <div class="relative h-48 overflow-hidden">
                                <img src="<?php echo esc_url($rw_img); ?>" alt="<?php echo esc_attr($rw->nama_wisata); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
                                <div class="absolute bottom-3 left-3 bg-white/90 backdrop-blur px-2 py-1 rounded-lg text-xs font-bold text-emerald-600">
                                    <?php echo $rw_harga; ?>
                                </div>
                            </div>
                            <div class="p-5">
                                <h4 class="font-bold text-gray-900 text-lg mb-1 group-hover:text-emerald-600 transition truncate"><?php echo esc_html($rw->nama_wisata); ?></h4>
                                <p class="text-xs text-gray-500 flex items-center gap-1 mb-3">
                                    <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($rw->kabupaten); ?>
                                </p>
                                <div class="flex items-center gap-1 text-xs text-yellow-500 font-bold">
                                    <i class="fas fa-star"></i> <?php echo number_format($rw->rating_avg, 1); ?>
                                </div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- RIGHT SIDEBAR -->
            <div class="lg:col-span-4 mt-4 lg:mt-0">
                <div class="lg:sticky lg:top-24 space-y-6">
                    
                    <!-- Managed By Widget -->
                    <div class="bg-white rounded-[1.5rem] p-5 border border-gray-100 shadow-sm flex items-center gap-4">
                        <img src="<?php echo esc_url($foto_desa); ?>" class="w-12 h-12 rounded-full border border-gray-100 object-cover">
                        <div class="flex-1 overflow-hidden">
                            <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wide">Dikelola Oleh</p>
                            <h4 class="text-sm font-bold text-gray-900 truncate">Desa <?php echo esc_html($wisata->nama_desa); ?></h4>
                        </div>
                        <a href="<?php echo home_url('/desa/' . $wisata->slug_desa); ?>" class="text-emerald-600 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-full text-xs font-bold transition">
                            Profil
                        </a>
                    </div>

                    <!-- BOOKING CARD -->
                    <div class="hidden lg:block bg-white rounded-[2rem] shadow-[0_20px_40px_-15px_rgba(0,0,0,0.08)] border border-gray-100 overflow-hidden relative">
                        <div class="p-6">
                            <div class="mb-6">
                                <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Tiket Masuk</p>
                                <div class="flex items-end gap-1">
                                    <span class="text-4xl font-extrabold text-gray-900"><?php echo $harga; ?></span>
                                    <?php if($harga != 'Gratis'): ?>
                                    <span class="text-gray-400 text-sm font-medium mb-1.5">/ orang</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="space-y-3 mb-8">
                                <div class="flex justify-between items-center text-sm py-2 border-b border-gray-50">
                                    <span class="text-gray-500"><i class="far fa-clock mr-2 text-emerald-500"></i> Jam Buka</span>
                                    <span class="font-bold text-gray-800"><?php echo $jam_buka; ?></span>
                                </div>
                                <div class="flex justify-between items-center text-sm py-2 border-b border-gray-50">
                                    <span class="text-gray-500"><i class="far fa-calendar-check mr-2 text-emerald-500"></i> Operasional</span>
                                    <span class="font-bold text-gray-800">Setiap Hari</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <?php if ($kontak): ?>
                                    <a href="https://wa.me/<?php echo esc_attr($kontak); ?>?text=Halo, saya ingin reservasi tiket wisata <?php echo urlencode($judul); ?>" target="_blank" 
                                       class="flex items-center justify-center gap-2 w-full py-4 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold rounded-2xl shadow-lg shadow-emerald-500/30 transition-all duration-300 transform hover:-translate-y-0.5">
                                        <i class="fab fa-whatsapp text-xl"></i>
                                        Reservasi via WhatsApp
                                    </a>
                                <?php else: ?>
                                    <button disabled class="w-full py-4 bg-gray-100 text-gray-400 font-bold rounded-2xl cursor-not-allowed">
                                        Kontak Belum Tersedia
                                    </button>
                                <?php endif; ?>
                                
                                <div class="grid grid-cols-2 gap-3">
                                    <button onclick="navigator.clipboard.writeText('<?php echo esc_js($current_url); ?>'); alert('Link disalin!');" class="w-full py-3 bg-white border border-gray-200 text-gray-600 font-semibold rounded-xl hover:bg-gray-50 transition flex items-center justify-center gap-2 text-xs">
                                        <i class="fas fa-link"></i> Salin Link
                                    </button>
                                    <button onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>', '_blank')" class="w-full py-3 bg-blue-50 border border-blue-100 text-blue-600 font-semibold rounded-xl hover:bg-blue-100 transition flex items-center justify-center gap-2 text-xs">
                                        <i class="fab fa-facebook-f"></i> Share
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- MOBILE FLOATING BAR (Fixed Bottom) -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 lg:hidden z-50 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] pb-safe">
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-col">
                <span class="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Tiket Masuk</span>
                <div class="flex items-baseline gap-1">
                    <span class="text-xl font-extrabold text-emerald-600"><?php echo $harga; ?></span>
                    <?php if($harga != 'Gratis'): ?><span class="text-xs text-gray-400 font-medium">/org</span><?php endif; ?>
                </div>
            </div>
            
            <?php if ($kontak): ?>
                <a href="https://wa.me/<?php echo esc_attr($kontak); ?>?text=Halo, saya ingin reservasi tiket wisata <?php echo urlencode($judul); ?>" target="_blank" 
                   class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white px-6 py-3 rounded-xl font-bold text-sm shadow-lg shadow-emerald-500/30 active:scale-95 transition flex items-center gap-2">
                    Reservasi <i class="fab fa-whatsapp"></i>
                </a>
            <?php else: ?>
                <button disabled class="bg-gray-100 text-gray-400 px-6 py-3 rounded-xl font-bold text-sm">
                    Tutup
                </button>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- === MODAL MAP === -->
<div id="map-modal" class="fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm transition-opacity opacity-0 modal-backdrop" onclick="closeMapModal()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-3xl opacity-0 scale-95 modal-panel">
                
                <!-- Header Modal -->
                <div class="bg-white px-4 py-3 sm:px-6 flex justify-between items-center border-b border-gray-100">
                    <h3 class="text-lg font-bold leading-6 text-gray-900 flex items-center gap-2">
                        <i class="fas fa-map-marked-alt text-emerald-500"></i> Lokasi Wisata
                    </h3>
                    <button onclick="closeMapModal()" class="text-gray-400 hover:text-gray-600 transition w-8 h-8 rounded-full flex items-center justify-center hover:bg-gray-100">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <!-- Content Modal (Iframe) -->
                <div class="bg-gray-50 relative w-full h-[400px] sm:h-[500px]">
                    <iframe 
                        src="<?php echo esc_url($google_maps_embed_url); ?>" 
                        class="w-full h-full border-0" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                
                <!-- Footer Modal (External Link) -->
                <div class="bg-white px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                    <a href="<?php echo esc_url($google_maps_link); ?>" target="_blank" class="w-full inline-flex justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-emerald-700 sm:w-auto items-center gap-2 transition transform active:scale-95">
                        <i class="fas fa-external-link-alt"></i> Buka di Google Maps App
                    </a>
                    <button type="button" onclick="closeMapModal()" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPTS -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- SCROLL SPY LOGIC ---
        const sections = document.querySelectorAll('div[id]');
        const navLinks = document.querySelectorAll('.nav-link');
        const navContainer = document.querySelector('#sticky-nav .overflow-x-auto');

        window.addEventListener('scroll', () => {
            let current = '';
            const navHeight = 180; // Offset lebih besar agar trigger lebih awal
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (scrollY >= (sectionTop - navHeight)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').includes(current)) {
                    link.classList.add('active');
                    
                    // Auto scroll menu horizontal di mobile agar item aktif selalu terlihat
                    if(navContainer) {
                        const linkLeft = link.offsetLeft;
                        const linkWidth = link.offsetWidth;
                        const containerWidth = navContainer.offsetWidth;
                        navContainer.scrollLeft = linkLeft - (containerWidth / 2) + (linkWidth / 2);
                    }
                }
            });
        });
    });

    // --- MODAL LOGIC ---
    const modal = document.getElementById('map-modal');
    const backdrop = modal.querySelector('.modal-backdrop');
    const panel = modal.querySelector('.modal-panel');

    function openMapModal() {
        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('opacity-0', 'scale-95');
            panel.classList.add('opacity-100', 'scale-100');
        }, 10);
        document.body.style.overflow = 'hidden'; 
    }

    function closeMapModal() {
        backdrop.classList.add('opacity-0');
        panel.classList.remove('opacity-100', 'scale-100');
        panel.classList.add('opacity-0', 'scale-95');
        
        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = ''; 
        }, 300);
    }
</script>

<?php get_footer(); ?>