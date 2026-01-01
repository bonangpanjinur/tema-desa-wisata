<?php
/**
 * Template Name: Single Wisata (Ultimate Refined Responsive)
 * Description: Desain detail wisata premium dengan perbaikan responsivitas mobile (Profil Desa Terlihat).
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

// Lokasi
$alamat_display = sprintf('%s, %s, %s', $wisata->nama_desa, $wisata->kabupaten, $wisata->provinsi);

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

// Rekomendasi Wisata Lain
$related_wisata = $wpdb->get_results($wpdb->prepare(
    "SELECT id, nama_wisata, slug, foto_utama, harga_tiket, rating_avg, kabupaten 
     FROM $t_wisata 
     WHERE status = 'aktif' AND id != %d 
     ORDER BY RAND() LIMIT 3",
    $id_wisata
));
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
    .nav-link { position: relative; font-weight: 500; color: #64748b; padding: 1rem 0; transition: color 0.2s; }
    .nav-link:hover { color: var(--primary); }
    .nav-link.active { color: var(--primary); font-weight: 700; }
    .nav-link.active::after {
        content: ''; position: absolute; bottom: -1px; left: 0; right: 0;
        height: 3px; background: var(--primary); border-radius: 3px 3px 0 0;
    }

    /* Map */
    .map-embed iframe { width: 100%; height: 100%; border: 0; border-radius: 1.5rem; filter: saturate(0.8); }
    .map-embed iframe:hover { filter: saturate(1); transition: filter 0.3s; }
    
    /* Mobile Safe Area Padding */
    .pb-safe { padding-bottom: calc(90px + env(safe-area-inset-bottom)); }
</style>

<div class="min-h-screen pb-safe lg:pb-0">

    <!-- === HERO SECTION (Style Profil Desa) === -->
    <div class="relative h-[450px] lg:h-[550px] bg-gray-900 overflow-hidden group">
        <!-- Background Image -->
        <div class="absolute inset-0">
            <img src="<?php echo esc_url($foto_utama); ?>" class="w-full h-full object-cover opacity-80 transition duration-[3000ms] group-hover:scale-105" alt="<?php echo esc_attr($judul); ?>">
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-gray-900/40 to-transparent"></div>
        </div>
        
        <!-- Top Nav (Back & Share) -->
        <div class="absolute top-0 left-0 right-0 p-6 z-20 flex justify-between items-start">
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

        <div class="absolute bottom-0 left-0 w-full p-6 md:p-10 z-10">
            <div class="max-w-7xl mx-auto w-full">
                <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                    <div class="max-w-3xl">
                        <!-- Badges -->
                        <div class="flex flex-wrap items-center gap-3 mb-4">
                            <span class="bg-emerald-600/90 backdrop-blur-md text-white border border-white/20 text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-2 shadow-lg uppercase tracking-wider">
                                <?php echo !empty($wisata->kategori) ? esc_html($wisata->kategori) : 'Destinasi'; ?>
                            </span>
                            <span class="bg-white/10 backdrop-blur-md text-white border border-white/20 text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-2">
                                <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($wisata->kabupaten); ?>
                            </span>
                        </div>
                        
                        <!-- Title -->
                        <h1 class="text-3xl md:text-5xl lg:text-6xl font-extrabold text-white mb-3 tracking-tight leading-tight drop-shadow-xl">
                            <?php echo $judul; ?>
                        </h1>
                        
                        <!-- Meta Info -->
                        <div class="flex flex-wrap items-center gap-4 text-white/90 text-sm font-medium drop-shadow-md">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-star text-yellow-400"></i> 
                                <span class="font-bold text-white"><?php echo number_format($rating_val, 1); ?></span>
                                <span class="opacity-70">(<?php echo $total_review; ?> Ulasan)</span>
                            </div>
                            <span class="hidden sm:inline w-1 h-1 bg-white/50 rounded-full"></span>
                            <div class="flex items-center gap-1.5 opacity-90">
                                <i class="far fa-clock"></i> <?php echo $jam_buka; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Glass Stats Card (Desktop Only) -->
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

    <!-- === STICKY NAVIGATION (Desktop) === -->
    <div class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-gray-200 shadow-sm hidden lg:block">
        <div class="max-w-7xl mx-auto px-8">
            <div class="flex gap-8 text-sm">
                <a href="#ikhtisar" class="nav-link active">Ikhtisar</a>
                <a href="#galeri" class="nav-link">Galeri</a>
                <a href="#fasilitas" class="nav-link">Fasilitas</a>
                <a href="#lokasi" class="nav-link">Lokasi</a>
                <a href="#ulasan" class="nav-link">Ulasan</a>
            </div>
        </div>
    </div>

    <!-- === MAIN CONTENT LAYOUT === -->
    <div class="max-w-7xl mx-auto px-4 lg:px-8 py-8 lg:py-12 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
            
            <!-- LEFT CONTENT (8/12) -->
            <div class="lg:col-span-8 space-y-10">
                
                <!-- IKHTISAR -->
                <div id="ikhtisar" class="scroll-mt-32">
                    <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-gray-50">
                            <div class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600">
                                <i class="fas fa-align-left"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900">Tentang Wisata</h2>
                        </div>
                        <div class="prose prose-lg max-w-none text-gray-600 leading-relaxed text-justify">
                            <?php echo wpautop($deskripsi); ?>
                        </div>
                    </div>
                </div>

                <!-- GALERI (BENTO GRID) -->
                <?php if(count($galeri_items) > 0): ?>
                <div id="galeri" class="scroll-mt-32">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="far fa-images text-emerald-500"></i> Galeri Foto
                    </h3>
                    
                    <div class="hidden lg:grid grid-cols-4 grid-rows-2 gap-4 h-[450px] rounded-3xl overflow-hidden">
                        <?php foreach($galeri_items as $idx => $img): if($idx > 4) break; 
                            $gridClass = ($idx === 0) ? 'col-span-2 row-span-2' : 'col-span-1 row-span-1';
                        ?>
                        <div class="relative group <?php echo $gridClass; ?> bg-gray-100 cursor-pointer overflow-hidden" onclick="window.open('<?php echo esc_url($img); ?>', '_blank')">
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

                    <!-- Mobile Carousel -->
                    <div class="lg:hidden flex gap-3 overflow-x-auto no-scrollbar -mx-4 px-4 pb-2">
                        <?php foreach($galeri_items as $img): ?>
                            <img src="<?php echo esc_url($img); ?>" class="h-48 w-72 object-cover rounded-2xl shadow-sm flex-shrink-0 snap-center border border-gray-100" onclick="window.open(this.src, '_blank')">
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- FASILITAS -->
                <div id="fasilitas" class="scroll-mt-32">
                    <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="fas fa-concierge-bell text-emerald-500"></i> Fasilitas
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach($fasilitas_list as $f): ?>
                        <div class="flex items-center gap-3 p-4 bg-white border border-gray-100 rounded-2xl shadow-sm hover:shadow-md transition duration-300 group">
                            <div class="w-8 h-8 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0 group-hover:bg-emerald-600 group-hover:text-white transition">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                            <span class="text-gray-700 font-medium text-sm"><?php echo esc_html($f); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- LOKASI -->
                <div id="lokasi" class="scroll-mt-32">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                            <i class="fas fa-map-marked-alt text-emerald-500"></i> Lokasi
                        </h3>
                        <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($judul . ' ' . $wisata->nama_desa); ?>" target="_blank" class="text-sm font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-2 bg-emerald-50 px-4 py-2 rounded-full transition">
                            Buka Peta <i class="fas fa-external-link-alt text-xs"></i>
                        </a>
                    </div>
                    
                    <div class="bg-white p-2 rounded-3xl border border-gray-200 shadow-sm">
                        <div class="map-embed w-full h-[350px] bg-gray-100 rounded-2xl overflow-hidden relative">
                            <?php if (strpos($lokasi_raw, '<iframe') !== false): ?>
                                <?php echo $lokasi_raw; ?>
                            <?php else: ?>
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 bg-gray-50">
                                    <i class="fas fa-map-marked-alt text-4xl mb-3 opacity-30"></i>
                                    <span class="text-sm font-medium">Peta embed belum tersedia</span>
                                    <span class="text-xs mt-1 px-4 text-center text-gray-400"><?php echo esc_html($alamat_display); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ULASAN -->
                <div id="ulasan" class="scroll-mt-32 pt-8 border-t border-gray-200">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Ulasan</h3>
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
                        <div class="grid gap-6">
                            <?php foreach($ulasan_list as $u): 
                                $avatar = get_avatar_url($u->user_email, ['size' => 64]);
                            ?>
                            <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm transition hover:shadow-md">
                                <div class="flex gap-4">
                                    <img src="<?php echo esc_url($avatar); ?>" class="w-12 h-12 rounded-full object-cover border-2 border-gray-50 bg-gray-100">
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
                        <div class="text-center py-12 bg-white rounded-3xl border border-dashed border-gray-200">
                            <div class="w-12 h-12 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-300">
                                <i class="far fa-comment-dots text-xl"></i>
                            </div>
                            <p class="text-gray-500 text-sm">Belum ada ulasan saat ini.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- RIGHT SIDEBAR (RESPONSIVE: Moves to bottom on mobile) -->
            <div class="lg:col-span-4 mt-8 lg:mt-0">
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
                    <div class="bg-white rounded-[2rem] shadow-[0_20px_40px_-15px_rgba(0,0,0,0.08)] border border-gray-100 overflow-hidden relative">
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
                                
                                <!-- Tombol Share & Wishlist di Sidebar -->
                                <div class="grid grid-cols-2 gap-3">
                                    <button onclick="navigator.clipboard.writeText('<?php echo esc_js($current_url); ?>'); alert('Link disalin!');" class="w-full py-3 bg-white border border-gray-200 text-gray-600 font-semibold rounded-xl hover:bg-gray-50 transition flex items-center justify-center gap-2">
                                        <i class="fas fa-link"></i> Salin Link
                                    </button>
                                    <button onclick="window.open('https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($current_url); ?>', '_blank')" class="w-full py-3 bg-blue-50 border border-blue-100 text-blue-600 font-semibold rounded-xl hover:bg-blue-100 transition flex items-center justify-center gap-2">
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

    <!-- 4. REKOMENDASI WISATA LAIN -->
    <?php if ($related_wisata): ?>
    <div class="bg-white py-12 lg:py-16 border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 lg:px-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-8">Wisata Lainnya di Sekitar</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
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
    </div>
    <?php endif; ?>

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

<!-- Scroll Spy Logic -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sections = document.querySelectorAll('div[id]');
        const navLinks = document.querySelectorAll('.nav-link');

        window.addEventListener('scroll', () => {
            let current = '';
            const navHeight = 150; 
            
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
                }
            });
        });
    });
</script>

<?php get_footer(); ?>