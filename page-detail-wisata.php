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
$rating = $wisata->rating_avg > 0 ? $wisata->rating_avg : '4.5';
$img_hero = !empty($wisata->foto_utama) ? $wisata->foto_utama : 'https://via.placeholder.com/1200x600?text=Wisata+Desa';

$wa_link = '#';
if (!empty($wisata->kontak_pengelola)) {
    $wa_num = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $wisata->kontak_pengelola));
    $wa_text = urlencode("Halo, saya tertarik berkunjung ke " . $wisata->nama_wisata . ". Boleh info lebih lanjut?");
    $wa_link = "https://wa.me/{$wa_num}?text={$wa_text}";
}

// === LOGIKA GALERI FOTO ===
$gallery_images = [];
if (!empty($img_hero)) {
    $gallery_images[] = $img_hero;
}
if (!empty($wisata->galeri)) {
    $decoded_gallery = json_decode($wisata->galeri);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_gallery)) {
        foreach($decoded_gallery as $g_img) {
            if ($g_img != $img_hero) {
                $gallery_images[] = $g_img;
            }
        }
    }
}
?>

<!-- === HERO SECTION & GALLERY GRID === -->
<div class="bg-white pt-4 pb-8">
    <div class="container mx-auto px-4">
        
        <!-- Breadcrumb -->
        <div class="flex items-center gap-2 text-xs text-gray-500 mb-4 overflow-x-auto whitespace-nowrap pb-2">
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
                <span class="flex items-center gap-1.5 font-medium"><i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?> <span class="text-gray-400 font-normal">(Ulasan)</span></span>
                <span class="hidden md:inline text-gray-300">|</span>
                <span class="bg-green-50 text-green-700 text-xs px-2.5 py-1 rounded-full font-bold border border-green-100 uppercase tracking-wide"><?php echo esc_html($wisata->kategori ?: 'Wisata'); ?></span>
            </div>
        </div>

        <!-- Gallery Grid (Desktop: 1 Besar + 2 Kecil, Mobile: Slider/Grid) -->
        <div class="relative h-[300px] md:h-[480px] rounded-2xl overflow-hidden group cursor-pointer shadow-sm border border-gray-100" onclick="openLightbox(0)">
            <?php if (count($gallery_images) >= 3) : ?>
                <!-- Layout 3 Gambar (Desktop) -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 h-full">
                    <!-- Gambar Utama -->
                    <div class="md:col-span-3 h-full relative overflow-hidden">
                        <img src="<?php echo esc_url($gallery_images[0]); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105" alt="Main Image">
                    </div>
                    <!-- Gambar Samping -->
                    <div class="hidden md:flex flex-col gap-2 h-full">
                        <div class="h-1/2 relative overflow-hidden">
                            <img src="<?php echo esc_url($gallery_images[1]); ?>" class="w-full h-full object-cover opacity-90 hover:opacity-100 transition duration-500">
                        </div>
                        <div class="h-1/2 relative overflow-hidden">
                            <img src="<?php echo esc_url($gallery_images[2]); ?>" class="w-full h-full object-cover opacity-90 hover:opacity-100 transition duration-500">
                            
                            <!-- Overlay "Lihat Semua" jika ada lebih dari 3 foto -->
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
                <!-- Layout Single Image (Jika kurang dari 3 foto) -->
                <img src="<?php echo esc_url($gallery_images[0]); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105" alt="Main Image">
                <?php if (count($gallery_images) > 1) : ?>
                    <div class="absolute bottom-4 right-4 bg-black/50 text-white px-3 py-1.5 rounded-lg text-xs font-bold backdrop-blur-md flex items-center gap-2">
                        <i class="fas fa-images"></i> Lihat <?php echo count($gallery_images); ?> Foto
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Tombol Mobile Overlay -->
            <button class="md:hidden absolute bottom-4 right-4 bg-white/90 text-gray-800 px-3 py-1.5 rounded-lg text-xs font-bold shadow-md flex items-center gap-2">
                <i class="fas fa-th"></i> Galeri
            </button>
        </div>

    </div>
</div>

<!-- === MAIN CONTENT === -->
<div class="bg-gray-50 min-h-screen relative">
    
    <!-- Sticky Sub-Navigation (Tab Berfungsi) -->
    <div class="sticky top-0 md:top-20 z-20 bg-white border-b border-gray-200 shadow-sm transition-all duration-300" id="sub-nav">
        <div class="container mx-auto px-4">
            <div class="flex gap-6 md:gap-8 text-sm font-medium text-gray-500 overflow-x-auto hide-scroll">
                <!-- Gunakan <a> dengan HREF ID untuk scroll -->
                <a href="#ikhtisar" class="py-4 border-b-2 border-primary text-primary font-bold whitespace-nowrap sub-nav-link active" data-target="ikhtisar">Ikhtisar</a>
                <?php if (!empty($fasilitas)) : ?>
                <a href="#fasilitas" class="py-4 border-b-2 border-transparent hover:text-primary hover:border-gray-300 transition whitespace-nowrap sub-nav-link" data-target="fasilitas">Fasilitas</a>
                <?php endif; ?>
                <a href="#lokasi" class="py-4 border-b-2 border-transparent hover:text-primary hover:border-gray-300 transition whitespace-nowrap sub-nav-link" data-target="lokasi">Lokasi</a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            
            <!-- KOLOM KIRI (Konten Utama) -->
            <div class="w-full lg:w-2/3 space-y-10">
                
                <!-- Section: Deskripsi -->
                <!-- Tambahkan ID agar link scroll berfungsi -->
                <div id="ikhtisar" class="scroll-mt-36 content-section">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                        Tentang Tempat Ini
                    </h2>
                    <div class="prose prose-green prose-sm md:prose-base max-w-none text-gray-600 leading-relaxed bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <?php echo wpautop(esc_html($wisata->deskripsi)); ?>
                    </div>
                </div>

                <!-- Section: Fasilitas -->
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

                <!-- Section: Lokasi -->
                <div id="lokasi" class="scroll-mt-36 content-section">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Lokasi & Peta</h2>
                    
                    <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
                        <p class="text-gray-600 mb-4 flex items-start gap-2 text-sm">
                            <i class="fas fa-map-pin mt-1 text-primary"></i> 
                            <?php echo esc_html($lokasi); ?>
                        </p>
                        
                        <div class="relative w-full h-[300px] bg-gray-100 rounded-xl overflow-hidden group border border-gray-200">
                            <!-- Pattern Background -->
                            <div class="absolute inset-0 opacity-20 bg-[url('https://upload.wikimedia.org/wikipedia/commons/thumb/e/ec/World_map_blank_without_borders.svg/2000px-World_map_blank_without_borders.svg.png')] bg-cover bg-center"></div>
                            
                            <div class="absolute inset-0 flex flex-col items-center justify-center p-6 text-center z-10">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg text-red-500 mb-4 animate-bounce-slow border-4 border-red-50">
                                    <i class="fas fa-map-marked-alt text-2xl"></i>
                                </div>
                                
                                <?php if (!empty($wisata->lokasi_maps)) : ?>
                                    <a href="<?php echo esc_url($wisata->lokasi_maps); ?>" target="_blank" class="bg-primary hover:bg-green-700 text-white px-8 py-3 rounded-full font-bold shadow-lg transition transform hover:-translate-y-1 flex items-center gap-2">
                                        <i class="fas fa-location-arrow"></i> Buka Google Maps
                                    </a>
                                    <p class="text-xs text-gray-500 mt-3 bg-white/80 px-3 py-1 rounded-full">Klik tombol untuk navigasi langsung</p>
                                <?php else : ?>
                                    <span class="text-gray-400 font-medium bg-white/80 px-4 py-2 rounded-lg border border-gray-200 text-sm">Peta belum tersedia</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- KOLOM KANAN (Sidebar Sticky) -->
            <div class="w-full lg:w-1/3 relative">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Card Booking / Info Utama -->
                    <div class="bg-white rounded-2xl shadow-xl shadow-gray-100 border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <!-- Harga -->
                            <div class="mb-6 pb-6 border-b border-gray-100">
                                <?php echo $price_display; ?>
                                <span class="text-xs text-gray-400 font-medium mt-1 block">/ orang (estimasi)</span>
                            </div>

                            <!-- Jam Operasional -->
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

                            <!-- Action Buttons -->
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
                        
                        <!-- Footer Card -->
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

<!-- === RELATED POSTS === -->
<div class="bg-white py-12 border-t border-gray-100 pb-24 md:pb-12">
    <div class="container mx-auto px-4">
        <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fas fa-compass text-primary"></i> Wisata Serupa Lainnya
        </h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <?php
            // Query Related (Random)
            $related = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE status='aktif' AND id != %d ORDER BY RAND() LIMIT 4", $wisata ? $wisata->id : 0));
            
            if ($related) : foreach ($related as $r) :
                $r_img = !empty($r->foto_utama) ? $r->foto_utama : 'https://via.placeholder.com/400x300';
                $r_price = ($r->harga_tiket > 0) ? 'Rp '.number_format($r->harga_tiket,0,',','.') : 'Gratis';
                $r_link = home_url('/wisata/detail/' . $r->slug);
                $r_cat = !empty($r->kategori) ? $r->kategori : 'Wisata';
            ?>
            <a href="<?php echo esc_url($r_link); ?>" class="group bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm hover:shadow-lg transition hover:-translate-y-1 block h-full flex flex-col">
                <div class="h-40 bg-gray-100 relative overflow-hidden">
                    <img src="<?php echo esc_url($r_img); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <div class="absolute top-3 left-3 bg-black/60 backdrop-blur-sm text-white text-[10px] px-2 py-1 rounded font-bold uppercase tracking-wide">
                        <?php echo esc_html($r_cat); ?>
                    </div>
                </div>
                <div class="p-4 flex-1 flex flex-col">
                    <h4 class="text-sm font-bold text-gray-800 line-clamp-2 mb-2 group-hover:text-primary transition"><?php echo esc_html($r->nama_wisata); ?></h4>
                    <div class="mt-auto flex items-center justify-between pt-2 border-t border-dashed border-gray-100">
                        <span class="text-xs text-gray-500">Tiket Masuk</span>
                        <span class="text-sm font-bold text-primary"><?php echo $r_price; ?></span>
                    </div>
                </div>
            </a>
            <?php endforeach; endif; ?>
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

<!-- === LIGHTBOX MODAL (Gallery Viewer) === -->
<div id="lightbox-modal" class="fixed inset-0 z-[60] bg-black/95 hidden flex flex-col items-center justify-center transition-opacity duration-300 opacity-0">
    <!-- Close Button -->
    <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white/70 hover:text-white p-2 z-50 bg-black/50 rounded-full w-10 h-10 flex items-center justify-center backdrop-blur-md">
        <i class="fas fa-times text-xl"></i>
    </button>

    <!-- Main Image Container -->
    <div class="relative w-full h-full flex items-center justify-center px-4 md:px-10 py-16">
        <img id="lightbox-img" src="" class="max-w-full max-h-full object-contain rounded-lg shadow-2xl transition-transform duration-300">
        
        <!-- Nav Buttons -->
        <button onclick="prevImage()" class="absolute left-2 md:left-6 text-white/50 hover:text-white p-4 text-3xl focus:outline-none">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button onclick="nextImage()" class="absolute right-2 md:right-6 text-white/50 hover:text-white p-4 text-3xl focus:outline-none">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>

    <!-- Thumbnail Strip (Bottom) -->
    <div class="w-full bg-black/50 backdrop-blur-md p-4 flex gap-2 overflow-x-auto justify-center hide-scroll absolute bottom-0 left-0">
        <?php foreach ($gallery_images as $idx => $img) : ?>
            <img src="<?php echo esc_url($img); ?>" onclick="showImage(<?php echo $idx; ?>)" 
                 class="h-12 w-16 md:h-16 md:w-24 object-cover rounded cursor-pointer opacity-50 hover:opacity-100 transition border border-transparent lightbox-thumb" 
                 data-index="<?php echo $idx; ?>">
        <?php endforeach; ?>
    </div>
</div>

<!-- === SCRIPTS === -->
<script>
// --- Navigasi Sticky Active State & Smooth Scroll ---
document.addEventListener('DOMContentLoaded', () => {
    const sections = document.querySelectorAll('.content-section');
    const navLinks = document.querySelectorAll('.sub-nav-link');
    
    // 1. Smooth Scroll saat klik Link Tab
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if(targetSection) {
                // Hitung offset agar tidak tertutup header sticky
                const offsetTop = targetSection.offsetTop - 180; 
                window.scrollTo({ top: offsetTop, behavior: 'smooth' });
                
                // Update active state manual langsung agar responsif
                navLinks.forEach(n => {
                    n.classList.remove('border-primary', 'text-primary', 'font-bold');
                    n.classList.add('border-transparent');
                });
                this.classList.remove('border-transparent');
                this.classList.add('border-primary', 'text-primary', 'font-bold');
            }
        });
    });

    // 2. Scroll Spy (Otomatis ganti tab aktif saat scroll)
    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            // 200px offset untuk trigger lebih awal sebelum sampai atas persis
            if (window.scrollY >= (sectionTop - 250)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('border-primary', 'text-primary', 'font-bold');
            link.classList.add('border-transparent');
            if (link.dataset.target === current) {
                link.classList.remove('border-transparent');
                link.classList.add('border-primary', 'text-primary', 'font-bold');
            }
        });
    });
});

// --- Lightbox Logic ---
const galleryImages = <?php echo json_encode($gallery_images); ?>;
let currentIndex = 0;
const lightbox = document.getElementById('lightbox-modal');
const lightboxImg = document.getElementById('lightbox-img');

function openLightbox(index) {
    if(galleryImages.length === 0) return;
    currentIndex = index;
    updateLightboxImage();
    lightbox.classList.remove('hidden');
    // Force reflow for transition
    void lightbox.offsetWidth; 
    lightbox.classList.remove('opacity-0');
    document.body.style.overflow = 'hidden'; // Disable scroll
}

function closeLightbox() {
    lightbox.classList.add('opacity-0');
    setTimeout(() => {
        lightbox.classList.add('hidden');
        document.body.style.overflow = ''; // Enable scroll
    }, 300);
}

function updateLightboxImage() {
    lightboxImg.src = galleryImages[currentIndex];
    // Update active thumb
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

function showImage(index) {
    currentIndex = index;
    updateLightboxImage();
}

function nextImage() {
    currentIndex = (currentIndex + 1) % galleryImages.length;
    updateLightboxImage();
}

function prevImage() {
    currentIndex = (currentIndex - 1 + galleryImages.length) % galleryImages.length;
    updateLightboxImage();
}

// Keyboard Navigation
document.addEventListener('keydown', function(e) {
    if (lightbox.classList.contains('hidden')) return;
    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowRight') nextImage();
    if (e.key === 'ArrowLeft') prevImage();
});
</script>

<style>
/* Custom Utilities */
.hide-scroll::-webkit-scrollbar { display: none; }
.hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
.pb-safe { padding-bottom: env(safe-area-inset-bottom); }
.animate-bounce-slow { animation: bounce 2s infinite; }
@keyframes bounce {
  0%, 100% { transform: translateY(-5%); animation-timing-function: cubic-bezier(0.8,0,1,1); }
  50% { transform: translateY(0); animation-timing-function: cubic-bezier(0,0,0.2,1); }
}
/* Scroll Margin Top untuk Sticky Header */
.scroll-mt-36 { scroll-margin-top: 150px; }
</style>

<?php get_footer(); ?>