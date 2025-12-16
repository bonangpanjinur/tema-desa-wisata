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
        SELECT w.*, d.nama_desa, d.kabupaten, d.id as id_desa
        FROM $table_wisata w
        LEFT JOIN $table_desa d ON w.id_desa = d.id
        WHERE w.slug = %s AND w.status = 'aktif'
    ", $slug));
} elseif ($id_param > 0) {
    $wisata = $wpdb->get_row($wpdb->prepare("
        SELECT w.*, d.nama_desa, d.kabupaten, d.id as id_desa
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
if ($wpdb->get_var("SHOW TABLES LIKE '$table_ulasan'") == $table_ulasan) {
    $ulasan_list = $wpdb->get_results($wpdb->prepare("
        SELECT u.*, users.display_name 
        FROM $table_ulasan u
        LEFT JOIN {$wpdb->users} users ON u.user_id = users.ID
        WHERE u.target_id = %d AND u.target_type = 'wisata' AND u.status_moderasi = 'disetujui'
        ORDER BY u.created_at DESC LIMIT 5
    ", $wisata->id));
}

// Data Formatting
$harga_tiket = $wisata->harga_tiket;
$price_display = ($harga_tiket > 0) ? '<span class="text-xs text-gray-500 font-normal block mb-1">Mulai dari</span> <span class="text-primary font-bold text-2xl">Rp ' . number_format($harga_tiket, 0, ',', '.') . '</span>' : '<span class="text-primary font-bold text-2xl">Gratis</span>';

// Link Desa (Profil Desa)
$link_desa = home_url('/profil-desa/?id=' . $wisata->id_desa);
$lokasi_html = !empty($wisata->nama_desa) 
    ? '<a href="'.esc_url($link_desa).'" class="hover:text-primary hover:underline font-bold">Desa ' . esc_html($wisata->nama_desa) . '</a>, ' . esc_html($wisata->kabupaten) 
    : esc_html($wisata->kabupaten);

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
$rating = $wisata->rating_avg > 0 ? $wisata->rating_avg : '4.8';
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

<!-- === HERO SECTION === -->
<div class="bg-white pt-4 pb-6">
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
            <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold text-gray-900 mb-3 leading-tight"><?php echo esc_html($wisata->nama_wisata); ?></h1>
            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                <span class="flex items-center gap-1.5 text-gray-800">
                    <i class="fas fa-map-marker-alt text-red-500"></i> <?php echo $lokasi_html; ?>
                </span>
                <span class="text-gray-300">|</span>
                <span class="flex items-center gap-1.5 font-medium"><i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?></span>
                <span class="text-gray-300">|</span>
                <span class="bg-green-50 text-green-700 text-xs px-2.5 py-1 rounded-full font-bold border border-green-100 uppercase tracking-wide"><?php echo esc_html($wisata->kategori ?: 'Wisata'); ?></span>
            </div>
        </div>

        <!-- Gallery Grid (FIXED) -->
        <div class="relative h-[250px] md:h-[480px] rounded-2xl overflow-hidden shadow-sm border border-gray-100">
            <?php if (count($gallery_images) >= 3) : ?>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 h-full">
                    <div class="md:col-span-3 h-full relative overflow-hidden group cursor-pointer" onclick="openLightbox(0)">
                        <img src="<?php echo esc_url($gallery_images[0]); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105" alt="Main Photo">
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition"></div>
                    </div>
                    <div class="hidden md:flex flex-col gap-2 h-full">
                        <div class="h-1/2 relative overflow-hidden group cursor-pointer" onclick="openLightbox(1)">
                            <img src="<?php echo esc_url($gallery_images[1]); ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-110" alt="Photo 2">
                        </div>
                        <div class="h-1/2 relative overflow-hidden group cursor-pointer" onclick="openLightbox(2)">
                            <img src="<?php echo esc_url($gallery_images[2]); ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-110" alt="Photo 3">
                            <?php if (count($gallery_images) > 3) : ?>
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center hover:bg-black/40 transition backdrop-blur-[1px]">
                                <span class="text-white font-bold text-sm border border-white/50 px-3 py-1 rounded-lg">
                                    +<?php echo count($gallery_images) - 3; ?> Foto
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <div class="w-full h-full relative group cursor-pointer" onclick="openLightbox(0)">
                    <img src="<?php echo esc_url($gallery_images[0]); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105" alt="Main Photo">
                    <?php if (count($gallery_images) > 1) : ?>
                        <div class="absolute bottom-4 right-4 bg-black/60 text-white px-3 py-1.5 rounded-lg text-xs font-bold backdrop-blur-md flex items-center gap-2">
                            <i class="fas fa-images"></i> Lihat <?php echo count($gallery_images); ?> Foto
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <button onclick="openLightbox(0)" class="md:hidden absolute bottom-3 right-3 bg-white/90 text-gray-900 px-3 py-1.5 rounded-lg text-xs font-bold shadow-md flex items-center gap-2 z-10 border border-gray-200">
                <i class="fas fa-th"></i> Lihat Galeri
            </button>
        </div>

    </div>
</div>

<!-- === STICKY NAVIGATION === -->
<div class="sticky top-[60px] md:top-[80px] z-30 bg-white border-b border-gray-200 shadow-sm transition-all duration-300" id="sticky-tabs">
    <div class="container mx-auto px-4">
        <div class="flex gap-6 md:gap-8 text-sm font-medium text-gray-500 overflow-x-auto no-scrollbar scroll-smooth">
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

<!-- === MAIN CONTENT === -->
<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            
            <!-- KOLOM KIRI (Konten Utama) -->
            <div class="w-full lg:w-2/3 space-y-8">
                
                <!-- 1. TENTANG -->
                <div id="tentang" class="scroll-mt-32 content-section bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="far fa-file-alt text-primary"></i> Tentang Tempat Ini
                    </h2>
                    <div class="prose prose-green prose-sm md:prose-base max-w-none text-gray-600 leading-relaxed">
                        <?php echo wpautop(esc_html($wisata->deskripsi)); ?>
                    </div>
                </div>

                <!-- 2. HARGA TIKET -->
                <div id="harga" class="scroll-mt-32 content-section bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="fas fa-ticket-alt text-primary"></i> Informasi Tiket
                    </h2>
                    <div class="flex items-center justify-between bg-green-50 p-4 rounded-xl border border-green-100">
                        <div>
                            <p class="text-xs text-gray-500 mb-1 uppercase tracking-wide font-bold">Harga Masuk</p>
                            <div class="text-2xl font-bold text-primary">
                                <?php echo ($harga_tiket > 0) ? 'Rp ' . number_format($harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">*Harga per orang</p>
                        </div>
                        <div class="text-right">
                            <span class="block text-xs font-bold text-green-700 bg-white px-3 py-1 rounded-full shadow-sm">
                                Tiket Tersedia
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 3. FASILITAS -->
                <?php if (!empty($fasilitas)) : ?>
                <div id="fasilitas" class="scroll-mt-32 content-section bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="fas fa-concierge-bell text-primary"></i> Fasilitas
                    </h2>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach ($fasilitas as $f) : if(trim($f) == '') continue; ?>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100 hover:border-green-200 transition">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span class="text-sm font-medium text-gray-700"><?php echo esc_html(trim($f)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 4. LOKASI -->
                <div id="lokasi" class="scroll-mt-32 content-section bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b border-gray-100 pb-3">
                        <i class="fas fa-map-marked-alt text-primary"></i> Lokasi
                    </h2>
                    <p class="text-gray-600 mb-4 text-sm flex items-start gap-2">
                        <i class="fas fa-map-pin mt-1 text-red-500"></i> 
                        <?php echo $lokasi_html; ?>
                    </p>
                    
                    <div class="relative w-full h-[250px] bg-gray-100 rounded-xl overflow-hidden group border border-gray-200">
                        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#000_1px,transparent_1px)] [background-size:16px_16px]"></div>
                        <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center z-10">
                            <?php if (!empty($wisata->lokasi_maps)) : ?>
                                <a href="<?php echo esc_url($wisata->lokasi_maps); ?>" target="_blank" class="bg-white hover:bg-gray-50 text-gray-800 px-6 py-3 rounded-full font-bold shadow-lg transition transform hover:-translate-y-1 flex items-center gap-2 border border-gray-200">
                                    <span class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center text-red-500"><i class="fas fa-location-arrow"></i></span>
                                    Buka Google Maps
                                </a>
                            <?php else : ?>
                                <span class="text-gray-400 font-medium bg-white/60 px-4 py-2 rounded-lg border border-gray-200 text-sm">Peta belum tersedia</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 5. ULASAN -->
                <div id="ulasan" class="scroll-mt-32 content-section">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-bold text-gray-900">Ulasan Pengunjung</h2>
                    </div>

                    <?php if (!empty($ulasan_list)) : ?>
                        <div class="space-y-4">
                            <?php foreach ($ulasan_list as $ulasan) : ?>
                            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 font-bold text-sm border border-gray-200">
                                        <?php echo substr(esc_html($ulasan->display_name ?: 'A'), 0, 1); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 text-sm"><?php echo esc_html($ulasan->display_name ?: 'Pengunjung'); ?></h4>
                                        <div class="flex text-yellow-400 text-[10px] gap-0.5">
                                            <?php for($i=1; $i<=5; $i++) echo ($i <= $ulasan->rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star text-gray-300"></i>'; ?>
                                        </div>
                                    </div>
                                    <span class="ml-auto text-[10px] text-gray-400 bg-gray-50 px-2 py-1 rounded-full"><?php echo date('d M Y', strtotime($ulasan->created_at)); ?></span>
                                </div>
                                <p class="text-gray-600 text-sm leading-relaxed mt-2 pl-[52px]"><?php echo esc_html($ulasan->komentar); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="bg-white p-8 rounded-2xl border border-gray-100 text-center shadow-sm">
                            <div class="w-14 h-14 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-300">
                                <i class="far fa-comment-alt text-xl"></i>
                            </div>
                            <p class="text-gray-500 text-sm font-medium">Belum ada ulasan</p>
                            <p class="text-xs text-gray-400">Jadilah yang pertama mengulas tempat ini.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- KOLOM KANAN (Sidebar Sticky - Desktop Only) -->
            <div class="hidden lg:block w-full lg:w-1/3 relative">
                <div class="sticky top-28 space-y-6">
                    
                    <!-- Card Info -->
                    <div class="bg-white rounded-2xl shadow-lg shadow-gray-100 border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-100">
                                <div>
                                    <span class="text-xs text-gray-400 uppercase font-bold">Harga Tiket</span>
                                    <div class="text-2xl font-bold text-primary mt-1">
                                        <?php echo ($harga_tiket > 0) ? 'Rp ' . number_format($harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                                    </div>
                                </div>
                                <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center">
                                    <i class="fas fa-tag"></i>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="flex items-center gap-3 text-sm text-gray-600">
                                    <i class="far fa-clock text-gray-400 w-5 text-center"></i>
                                    <span>Buka: <span class="font-bold text-gray-800"><?php echo esc_html($jam_buka); ?> WIB</span></span>
                                </div>
                                <div class="flex items-center gap-3 text-sm text-gray-600">
                                    <i class="fas fa-map-marker-alt text-gray-400 w-5 text-center"></i>
                                    <span class="truncate"><?php echo $lokasi_html; ?></span>
                                </div>
                            </div>

                            <div class="mt-6 pt-6 border-t border-gray-100 space-y-3">
                                <?php if (!empty($wisata->kontak_pengelola)) : ?>
                                <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="flex items-center justify-center w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-green-100 gap-2 transform active:scale-95">
                                    <i class="fab fa-whatsapp text-lg"></i> Hubungi Pengelola
                                </a>
                                <?php else: ?>
                                <button disabled class="w-full bg-gray-100 text-gray-400 font-bold py-3.5 rounded-xl cursor-not-allowed">Kontak Tidak Tersedia</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- === MOBILE FLOATING ACTION BAR === -->
<div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 z-50 shadow-[0_-4px_20px_-1px_rgba(0,0,0,0.1)] pb-safe">
    <div class="flex gap-3 items-center">
        <div class="flex-1">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-wide">Harga Tiket</div>
            <div class="font-extrabold text-primary text-lg leading-tight">
                <?php echo ($harga_tiket > 0) ? 'Rp ' . number_format($harga_tiket, 0, ',', '.') : 'Gratis'; ?>
            </div>
        </div>
        <div class="flex-none">
            <?php if (!empty($wisata->kontak_pengelola)) : ?>
            <a href="<?php echo esc_url($wa_link); ?>" class="flex items-center justify-center bg-green-600 active:bg-green-700 text-white font-bold py-3 px-6 rounded-xl shadow-md text-sm gap-2">
                <i class="fab fa-whatsapp text-lg"></i> Hubungi
            </a>
            <?php else: ?>
            <button disabled class="bg-gray-100 text-gray-400 font-bold py-3 px-6 rounded-xl text-sm border border-gray-200">Kontak N/A</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- === LIGHTBOX MODAL === -->
<div id="lightbox-modal" class="fixed inset-0 z-[60] bg-black/95 hidden flex flex-col items-center justify-center transition-opacity duration-300 opacity-0 touch-none">
    <button onclick="closeLightbox()" class="absolute top-6 right-6 text-white/70 hover:text-white p-2 z-50 bg-white/10 rounded-full w-10 h-10 flex items-center justify-center backdrop-blur-md">
        <i class="fas fa-times text-xl"></i>
    </button>
    <div class="flex-1 w-full h-full flex items-center justify-center px-2 md:px-10 py-16 relative">
        <img id="lightbox-img" src="" class="max-w-full max-h-full object-contain shadow-2xl transition-transform duration-300 select-none">
        <button onclick="prevImage()" class="hidden md:block absolute left-6 text-white/50 hover:text-white p-4 text-4xl focus:outline-none bg-black/20 rounded-full h-16 w-16 hover:bg-black/40 flex items-center justify-center transition"><i class="fas fa-chevron-left"></i></button>
        <button onclick="nextImage()" class="hidden md:block absolute right-6 text-white/50 hover:text-white p-4 text-4xl focus:outline-none bg-black/20 rounded-full h-16 w-16 hover:bg-black/40 flex items-center justify-center transition"><i class="fas fa-chevron-right"></i></button>
        <div class="md:hidden absolute inset-y-0 left-0 w-1/3 z-10" onclick="prevImage()"></div>
        <div class="md:hidden absolute inset-y-0 right-0 w-1/3 z-10" onclick="nextImage()"></div>
    </div>
    <div class="w-full bg-black/80 backdrop-blur-md p-4 flex gap-2 overflow-x-auto justify-center hide-scroll absolute bottom-0 left-0 h-24 items-center z-20">
        <?php foreach ($gallery_images as $idx => $img) : ?>
            <img src="<?php echo esc_url($img); ?>" onclick="showImage(<?php echo $idx; ?>)" class="h-14 w-20 object-cover rounded cursor-pointer opacity-40 hover:opacity-100 transition border-2 border-transparent lightbox-thumb flex-shrink-0" data-index="<?php echo $idx; ?>">
        <?php endforeach; ?>
    </div>
</div>

<!-- === SCRIPTS === -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('.content-section');
    const navLinks = document.querySelectorAll('.sub-nav-link');
    const stickyHeader = document.getElementById('sticky-tabs');
    const headerHeight = stickyHeader ? stickyHeader.offsetHeight + 100 : 150;

    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            if(targetSection) {
                const offsetTop = targetSection.offsetTop - headerHeight;
                window.scrollTo({ top: offsetTop, behavior: 'smooth' });
            }
        });
    });

    window.addEventListener('scroll', () => {
        let current = '';
        const scrollPosition = window.scrollY + headerHeight + 50; 
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollPosition >= sectionTop) current = section.getAttribute('id');
        });
        navLinks.forEach(link => {
            link.classList.remove('border-primary', 'text-primary');
            link.classList.add('border-transparent');
            if (link.dataset.target === current) {
                link.classList.remove('border-transparent');
                link.classList.add('border-primary', 'text-primary');
                link.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }
        });
    });
});

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
            thumb.classList.remove('opacity-40', 'border-transparent');
            thumb.classList.add('opacity-100', 'border-white');
            thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        } else {
            thumb.classList.add('opacity-40', 'border-transparent');
            thumb.classList.remove('opacity-100', 'border-white');
        }
    });
}

function showImage(index) { currentIndex = index; updateLightboxImage(); }
function nextImage(e) { if(e) e.stopPropagation(); currentIndex = (currentIndex + 1) % galleryImages.length; updateLightboxImage(); }
function prevImage(e) { if(e) e.stopPropagation(); currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length; updateLightboxImage(); }

document.addEventListener('keydown', function(e) {
    if (lightbox.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') nextImage();
    if (e.key === 'ArrowLeft') prevImage();
});
</script>

<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.pb-safe { padding-bottom: env(safe-area-inset-bottom); }
.scroll-mt-32 { scroll-margin-top: 140px; }
</style>

<?php get_footer(); ?>