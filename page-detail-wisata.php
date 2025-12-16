<?php
/**
 * Template Name: Detail Wisata Custom
 */

get_header();

global $wpdb;
$table_wisata = $wpdb->prefix . 'dw_wisata';
$table_desa   = $wpdb->prefix . 'dw_desa';
$table_ulasan = $wpdb->prefix . 'dw_ulasan';

$wisata = null;

// 1. Coba Tangkap Slug dari URL (Rewrite Rule)
$slug = get_query_var('dw_slug');

// 2. Coba Tangkap ID (Fallback)
$id_param = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 3. Query Data
if (!empty($slug)) {
    $wisata = $wpdb->get_row($wpdb->prepare("
        SELECT w.*, d.nama_desa, d.kabupaten 
        FROM $table_wisata w
        LEFT JOIN $table_desa d ON w.id_desa = d.id
        WHERE w.slug = %s AND w.status = 'aktif'
    ", $slug));
} elseif ($id_param > 0) {
    $wisata = $wpdb->get_row($wpdb->prepare("
        SELECT w.*, d.nama_desa, d.kabupaten 
        FROM $table_wisata w
        LEFT JOIN $table_desa d ON w.id_desa = d.id
        WHERE w.id = %d AND w.status = 'aktif'
    ", $id_param));
}

// 4. Not Found
if (!$wisata) {
    echo '<div class="min-h-[60vh] flex flex-col items-center justify-center text-center p-4">';
    echo '<div class="text-6xl text-gray-200 mb-4"><i class="fas fa-map-signs"></i></div>';
    echo '<h1 class="text-2xl font-bold text-gray-800 mb-2">Wisata Tidak Ditemukan</h1>';
    echo '<a href="'.home_url('/').'" class="bg-primary text-white px-6 py-3 rounded-xl font-bold hover:bg-green-700 transition">Kembali ke Beranda</a>';
    echo '</div>';
    get_footer();
    exit;
}

// 5. Query Ulasan
$ulasan_list = [];
// Cek apakah tabel ulasan ada
if ($wpdb->get_var("SHOW TABLES LIKE '$table_ulasan'") == $table_ulasan) {
    $ulasan_list = $wpdb->get_results($wpdb->prepare("
        SELECT u.*, users.display_name, users.user_email 
        FROM $table_ulasan u
        LEFT JOIN {$wpdb->users} users ON u.user_id = users.ID
        WHERE u.target_id = %d AND u.target_type = 'wisata' AND u.status_moderasi = 'disetujui'
        ORDER BY u.created_at DESC LIMIT 5
    ", $wisata->id));
}

// Data Formatting
$harga_tiket = $wisata->harga_tiket;
$price_display = ($harga_tiket > 0) ? '<span class="text-xs text-gray-500 font-normal block mb-1">Mulai dari</span> <span class="text-primary font-bold text-2xl">Rp ' . number_format($harga_tiket, 0, ',', '.') . '</span>' : '<span class="text-primary font-bold text-2xl">Gratis</span>';
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
$rating = $wisata->rating_avg > 0 ? $wisata->rating_avg : 'Belum ada rating';
$img_hero = !empty($wisata->foto_utama) ? $wisata->foto_utama : 'https://via.placeholder.com/1200x600?text=Wisata+Desa';

$wa_link = '#';
if (!empty($wisata->kontak_pengelola)) {
    $wa_num = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $wisata->kontak_pengelola));
    $wa_text = urlencode("Halo, saya tertarik berkunjung ke " . $wisata->nama_wisata . ". Boleh info lebih lanjut?");
    $wa_link = "https://wa.me/{$wa_num}?text={$wa_text}";
}

// Galeri
$gallery_images = [];
if (!empty($img_hero)) {
    $gallery_images[] = $img_hero;
}
if (!empty($wisata->galeri)) {
    $decoded_gallery = json_decode($wisata->galeri);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_gallery)) {
        foreach($decoded_gallery as $g_img) {
            if ($g_img != $img_hero) $gallery_images[] = $g_img;
        }
    }
}
?>

<!-- === HERO SECTION & GALLERY === -->
<div class="bg-white pt-4 pb-8">
    <div class="container mx-auto px-4">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-gray-500 mb-4 overflow-x-auto whitespace-nowrap pb-2 no-scrollbar">
            <a href="<?php echo home_url('/'); ?>" class="hover:text-primary transition">Beranda</a>
            <i class="fas fa-chevron-right text-[10px] text-gray-300"></i>
            <a href="<?php echo home_url('/wisata'); ?>" class="hover:text-primary transition">Wisata</a>
            <i class="fas fa-chevron-right text-[10px] text-gray-300"></i>
            <span class="text-gray-800 font-medium truncate"><?php echo esc_html($wisata->nama_wisata); ?></span>
        </div>

        <!-- Header Info -->
        <div class="mb-6">
            <h1 class="text-2xl md:text-4xl font-extrabold text-gray-900 mb-3 leading-tight"><?php echo esc_html($wisata->nama_wisata); ?></h1>
            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                <span class="flex items-center gap-1.5"><i class="fas fa-map-marker-alt text-red-500"></i> <?php echo esc_html($lokasi); ?></span>
                <span class="hidden md:inline text-gray-300">|</span>
                <span class="flex items-center gap-1.5 font-medium"><i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?></span>
                <span class="hidden md:inline text-gray-300">|</span>
                <span class="bg-green-50 text-green-700 text-xs px-2.5 py-1 rounded-full font-bold border border-green-100 uppercase tracking-wide"><?php echo esc_html($wisata->kategori ?: 'Wisata'); ?></span>
            </div>
        </div>

        <!-- Gallery Grid -->
        <div class="relative h-[300px] md:h-[480px] rounded-2xl overflow-hidden group cursor-pointer shadow-sm border border-gray-100" onclick="openLightbox(0)">
            <?php if (count($gallery_images) >= 3) : ?>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 h-full">
                    <div class="md:col-span-3 h-full relative overflow-hidden">
                        <img src="<?php echo esc_url($gallery_images[0]); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
                    </div>
                    <div class="hidden md:flex flex-col gap-2 h-full">
                        <div class="h-1/2 relative overflow-hidden">
                            <img src="<?php echo esc_url($gallery_images[1]); ?>" class="w-full h-full object-cover opacity-90 hover:opacity-100 transition duration-500">
                        </div>
                        <div class="h-1/2 relative overflow-hidden">
                            <img src="<?php echo esc_url($gallery_images[2]); ?>" class="w-full h-full object-cover opacity-90 hover:opacity-100 transition duration-500">
                            <?php if (count($gallery_images) > 3) : ?>
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center hover:bg-black/30 transition backdrop-blur-[1px]">
                                <span class="text-white font-bold text-sm bg-white/20 px-3 py-1.5 rounded-lg border border-white/30 backdrop-blur-md">
                                    +<?php echo count($gallery_images) - 3; ?> Foto
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <img src="<?php echo esc_url($gallery_images[0]); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105">
                <?php if (count($gallery_images) > 1) : ?>
                    <div class="absolute bottom-4 right-4 bg-black/50 text-white px-3 py-1.5 rounded-lg text-xs font-bold backdrop-blur-md flex items-center gap-2">
                        <i class="fas fa-images"></i> Lihat <?php echo count($gallery_images); ?> Foto
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <button class="md:hidden absolute bottom-4 right-4 bg-white/90 text-gray-800 px-3 py-1.5 rounded-lg text-xs font-bold shadow-md flex items-center gap-2">
                <i class="fas fa-th"></i> Galeri
            </button>
        </div>

    </div>
</div>

<!-- === MAIN CONTENT === -->
<div class="bg-gray-50 min-h-screen relative">
    
    <!-- STICKY NAVIGATION BAR -->
    <!-- top-16 untuk mobile (header h-16), top-20 untuk desktop (header h-20) -->
    <div class="sticky top-16 md:top-20 z-40 bg-white border-b border-gray-200 shadow-sm transition-all duration-300" id="sub-nav">
        <div class="container mx-auto px-4">
            <div class="flex gap-4 md:gap-8 text-sm font-medium text-gray-500 overflow-x-auto no-scrollbar scroll-smooth">
                <a href="#tentang" class="py-4 border-b-2 border-primary text-primary font-bold whitespace-nowrap sub-nav-link active" data-target="tentang">Tentang</a>
                <a href="#harga" class="py-4 border-b-2 border-transparent hover:text-primary hover:border-gray-300 transition whitespace-nowrap sub-nav-link" data-target="harga">Harga Tiket</a>
                <?php if (!empty($fasilitas)) : ?>
                <a href="#fasilitas" class="py-4 border-b-2 border-transparent hover:text-primary hover:border-gray-300 transition whitespace-nowrap sub-nav-link" data-target="fasilitas">Fasilitas</a>
                <?php endif; ?>
                <a href="#lokasi" class="py-4 border-b-2 border-transparent hover:text-primary hover:border-gray-300 transition whitespace-nowrap sub-nav-link" data-target="lokasi">Lokasi</a>
                <a href="#ulasan" class="py-4 border-b-2 border-transparent hover:text-primary hover:border-gray-300 transition whitespace-nowrap sub-nav-link" data-target="ulasan">Ulasan</a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            
            <!-- KOLOM KIRI (Konten Utama) -->
            <div class="w-full lg:w-2/3 space-y-10">
                
                <!-- 1. TENTANG -->
                <div id="tentang" class="scroll-mt-36 content-section">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        Tentang Tempat Ini
                    </h2>
                    <div class="prose prose-green prose-sm md:prose-base max-w-none text-gray-600 leading-relaxed bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <?php echo wpautop(esc_html($wisata->deskripsi)); ?>
                    </div>
                </div>

                <!-- 2. HARGA TIKET -->
                <div id="harga" class="scroll-mt-36 content-section">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Informasi Tiket</h2>
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Tiket Masuk (Per Orang)</p>
                            <div class="text-2xl font-bold text-primary">
                                <?php echo ($harga_tiket > 0) ? 'Rp ' . number_format($harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs font-bold text-green-600 bg-green-100 px-3 py-1 rounded-full">
                                Harga Terbaik
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. FASILITAS -->
                <?php if (!empty($fasilitas)) : ?>
                <div id="fasilitas" class="scroll-mt-36 content-section">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Fasilitas Tersedia</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach ($fasilitas as $f) : if(trim($f) == '') continue; ?>
                            <div class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition hover:border-green-100 group">
                                <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-primary flex-shrink-0 group-hover:bg-green-100 transition">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700"><?php echo esc_html(trim($f)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 4. LOKASI -->
                <div id="lokasi" class="scroll-mt-36 content-section">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Lokasi & Peta</h2>
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <p class="text-gray-600 mb-4 flex items-start gap-2 text-sm">
                            <i class="fas fa-map-pin mt-1 text-primary"></i> 
                            <?php echo esc_html($lokasi); ?>
                        </p>
                        <div class="relative w-full h-[300px] bg-gray-100 rounded-xl overflow-hidden group border border-gray-200">
                            <div class="absolute inset-0 opacity-20 bg-[url('https://upload.wikimedia.org/wikipedia/commons/thumb/e/ec/World_map_blank_without_borders.svg/2000px-World_map_blank_without_borders.svg.png')] bg-cover bg-center"></div>
                            <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center z-10">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg text-red-500 mb-4 animate-bounce-slow border-4 border-red-50">
                                    <i class="fas fa-map-marked-alt text-2xl"></i>
                                </div>
                                <?php if (!empty($wisata->lokasi_maps)) : ?>
                                    <a href="<?php echo esc_url($wisata->lokasi_maps); ?>" target="_blank" class="bg-primary hover:bg-green-700 text-white px-8 py-3 rounded-full font-bold shadow-lg transition transform hover:-translate-y-1 flex items-center gap-2">
                                        <i class="fas fa-location-arrow"></i> Buka Google Maps
                                    </a>
                                <?php else : ?>
                                    <span class="text-gray-400 font-medium bg-white/80 px-4 py-2 rounded-lg border border-gray-200 text-sm">Peta belum tersedia</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. ULASAN -->
                <div id="ulasan" class="scroll-mt-36 content-section">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Ulasan Pengunjung</h2>
                        <?php if(!empty($ulasan_list)): ?>
                            <a href="#" class="text-primary text-sm font-bold hover:underline">Lihat Semua</a>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($ulasan_list)) : ?>
                        <div class="space-y-4">
                            <?php foreach ($ulasan_list as $ulasan) : ?>
                            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 font-bold">
                                        <?php echo substr(esc_html($ulasan->display_name ?: 'User'), 0, 1); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm"><?php echo esc_html($ulasan->display_name ?: 'Pengunjung'); ?></h4>
                                        <div class="flex text-yellow-400 text-xs">
                                            <?php for($i=1; $i<=5; $i++) echo ($i <= $ulasan->rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                        </div>
                                    </div>
                                    <span class="ml-auto text-xs text-gray-400"><?php echo date('d M Y', strtotime($ulasan->created_at)); ?></span>
                                </div>
                                <p class="text-gray-600 text-sm leading-relaxed"><?php echo esc_html($ulasan->komentar); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="bg-white p-8 rounded-2xl border border-gray-100 text-center">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-300">
                                <i class="far fa-comment-dots text-2xl"></i>
                            </div>
                            <p class="text-gray-500 text-sm">Belum ada ulasan untuk wisata ini.</p>
                            <p class="text-xs text-gray-400 mt-1">Jadilah yang pertama memberikan ulasan!</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- KOLOM KANAN (Sidebar Sticky) -->
            <div class="w-full lg:w-1/3 relative">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Card Booking / Info -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <div class="mb-6 pb-6 border-b border-gray-100">
                                <?php echo $price_display; ?>
                                <span class="text-xs text-gray-400 font-medium mt-1 block">/ orang (estimasi)</span>
                            </div>

                            <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100 flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-blue-500 shadow-sm border border-gray-100">
                                    <i class="far fa-clock"></i>
                                </div>
                                <div>
                                    <span class="block text-xs font-bold text-gray-400 uppercase mb-0.5">Jam Operasional</span>
                                    <span class="text-sm font-bold text-gray-800 block"><?php echo esc_html($jam_buka); ?> WIB</span>
                                    <span class="text-[10px] text-green-600 font-bold bg-green-100 px-2 py-0.5 rounded-full inline-block mt-1">Buka Hari Ini</span>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <?php if (!empty($wisata->kontak_pengelola)) : ?>
                                <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="flex items-center justify-center w-full bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-xl transition shadow-lg shadow-green-100 gap-2 transform hover:-translate-y-0.5">
                                    <i class="fab fa-whatsapp text-xl"></i> Hubungi Pengelola
                                </a>
                                <?php endif; ?>
                                
                                <button class="flex items-center justify-center w-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold py-3.5 rounded-xl transition gap-2">
                                    <i class="fas fa-share-alt"></i> Bagikan
                                </button>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 text-center">
                            <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wide">
                                <i class="fas fa-shield-alt text-green-500 mr-1"></i> Terverifikasi Resmi
                            </p>
                        </div>
                    </div>

                    <!-- Card Bantuan -->
                    <div class="hidden lg:block bg-gradient-to-br from-blue-50 to-white rounded-2xl p-6 border border-blue-100 text-center relative overflow-hidden">
                        <div class="relative z-10">
                            <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center text-blue-500 mx-auto mb-3 shadow-sm">
                                <i class="fas fa-headset text-xl"></i>
                            </div>
                            <p class="text-sm text-gray-800 font-bold mb-1">Butuh Bantuan?</p>
                            <p class="text-xs text-gray-500 mb-3">Tim kami siap membantu perjalanan Anda.</p>
                            <a href="<?php echo home_url('/kontak'); ?>" class="text-xs font-bold text-blue-600 hover:text-blue-700 hover:underline">Hubungi CS &rarr;</a>
                        </div>
                        <!-- Dekorasi -->
                        <div class="absolute top-0 right-0 w-20 h-20 bg-blue-100 rounded-full -mr-10 -mt-10 opacity-50"></div>
                        <div class="absolute bottom-0 left-0 w-16 h-16 bg-blue-100 rounded-full -ml-8 -mb-8 opacity-50"></div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- === MOBILE FLOATING ACTION BAR === -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 z-50 shadow-[0_-4px_20px_-1px_rgba(0,0,0,0.1)] pb-safe">
    <div class="flex gap-3 items-center">
        <div class="flex-1">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Harga Tiket</div>
            <div class="font-extrabold text-primary text-lg leading-tight">
                <?php echo ($harga_tiket > 0) ? 'Rp ' . number_format($harga_tiket, 0, ',', '.') : 'Gratis'; ?>
            </div>
        </div>
        <div class="flex-1">
            <?php if (!empty($wisata->kontak_pengelola)) : ?>
            <a href="<?php echo esc_url($wa_link); ?>" class="flex items-center justify-center w-full bg-green-600 active:bg-green-700 text-white font-bold py-3 rounded-xl shadow-md text-sm gap-2">
                <i class="fab fa-whatsapp text-lg"></i> Hubungi
            </a>
            <?php else: ?>
            <button disabled class="w-full bg-gray-100 text-gray-400 font-bold py-3 rounded-xl text-sm border border-gray-200">Info Kontak N/A</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- === LIGHTBOX MODAL === -->
<div id="lightbox-modal" class="fixed inset-0 z-[60] bg-black/95 hidden flex flex-col items-center justify-center transition-opacity duration-300 opacity-0">
    <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white/70 hover:text-white p-2 z-50 bg-black/50 rounded-full w-10 h-10 flex items-center justify-center backdrop-blur-md">
        <i class="fas fa-times text-xl"></i>
    </button>
    <div class="relative w-full h-full flex items-center justify-center px-4 md:px-10 py-16">
        <img id="lightbox-img" src="" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl transition-transform duration-300">
        <button onclick="prevImage()" class="absolute left-2 md:left-6 text-white/50 hover:text-white p-4 text-3xl focus:outline-none"><i class="fas fa-chevron-left"></i></button>
        <button onclick="nextImage()" class="absolute right-2 md:right-6 text-white/50 hover:text-white p-4 text-3xl focus:outline-none"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="w-full bg-black/50 backdrop-blur-md p-4 flex gap-2 overflow-x-auto justify-center hide-scroll absolute bottom-0 left-0">
        <?php foreach ($gallery_images as $idx => $img) : ?>
            <img src="<?php echo esc_url($img); ?>" onclick="showImage(<?php echo $idx; ?>)" class="h-12 w-16 md:h-16 md:w-24 object-cover rounded cursor-pointer opacity-50 hover:opacity-100 transition border border-transparent lightbox-thumb" data-index="<?php echo $idx; ?>">
        <?php endforeach; ?>
    </div>
</div>

<!-- === SCRIPTS === -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('.content-section');
    const navLinks = document.querySelectorAll('.sub-nav-link');
    
    // Smooth Scroll Click
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if(targetSection) {
                // Offset calculation based on header heights
                // Mobile header h-16 (64px) + sticky sub-nav (approx 50px) = ~114px
                // Desktop header h-20 (80px) + sticky sub-nav = ~130px
                const headerOffset = window.innerWidth < 768 ? 130 : 150;
                const offsetTop = targetSection.offsetTop - headerOffset;
                
                window.scrollTo({ top: offsetTop, behavior: 'smooth' });
            }
        });
    });

    // Scroll Spy
    window.addEventListener('scroll', () => {
        let current = '';
        const scrollPosition = window.scrollY + 200; // Trigger point offset

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollPosition >= sectionTop) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('border-primary', 'text-primary', 'font-bold');
            link.classList.add('border-transparent');
            if (link.dataset.target === current) {
                link.classList.remove('border-transparent');
                link.classList.add('border-primary', 'text-primary', 'font-bold');
                
                // Auto scroll horizontal menu on mobile to keep active tab visible
                link.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        });
    });
});

// Lightbox Logic
const galleryImages = <?php echo json_encode($gallery_images); ?>;
let currentIndex = 0;
const lightbox = document.getElementById('lightbox-modal');
const lightboxImg = document.getElementById('lightbox-img');

function openLightbox(index) {
    if(galleryImages.length === 0) return;
    currentIndex = index;
    updateLightboxImage();
    lightbox.classList.remove('hidden');
    void lightbox.offsetWidth; 
    lightbox.classList.remove('opacity-0');
    document.body.style.overflow = 'hidden'; 
}

function closeLightbox() {
    lightbox.classList.add('opacity-0');
    setTimeout(() => {
        lightbox.classList.add('hidden');
        document.body.style.overflow = ''; 
    }, 300);
}

function updateLightboxImage() {
    lightboxImg.src = galleryImages[currentIndex];
    document.querySelectorAll('.lightbox-thumb').forEach((thumb, idx) => {
        if(idx === currentIndex) {
            thumb.classList.remove('opacity-50', 'border-transparent');
            thumb.classList.add('opacity-100', 'border-white');
        } else {
            thumb.classList.add('opacity-50', 'border-transparent');
            thumb.classList.remove('opacity-100', 'border-white');
        }
    });
}

function showImage(index) { currentIndex = index; updateLightboxImage(); }
function nextImage() { currentIndex = (currentIndex + 1) % galleryImages.length; updateLightboxImage(); }
function prevImage() { currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length; updateLightboxImage(); }

document.addEventListener('keydown', function(e) {
    if (lightbox.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') nextImage();
    if (e.key === 'ArrowLeft') prevImage();
});
</script>

<style>
/* Custom Utilities */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.pb-safe { padding-bottom: env(safe-area-inset-bottom); }
.animate-bounce-slow { animation: bounce 2s infinite; }
@keyframes bounce {
  0%, 100% { transform: translateY(-5%); animation-timing-function: cubic-bezier(0.8,0,1,1); }
  50% { transform: translateY(0); animation-timing-function: cubic-bezier(0,0,0.2,1); }
}
/* Scroll Margin Top untuk Sticky Header Offset */
.scroll-mt-36 { scroll-margin-top: 150px; }
</style>

<?php get_footer(); ?>