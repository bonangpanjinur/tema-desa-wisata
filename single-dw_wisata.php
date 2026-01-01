<?php
/**
 * Template Name: Single Wisata (Sadesa Green Style)
 * Description: Menampilkan detail wisata dengan UI Hijau Sadesa (Fixed Database Schema)
 */

get_header();

global $wpdb, $post;

// --- 1. DETEKSI SLUG YANG LEBIH KUAT ---
$slug = get_query_var('dw_slug'); // Dari Rewrite Rule
if ( empty($slug) ) {
    $slug = get_query_var('name'); // Fallback standard WP
}
if ( empty($slug) && isset($post->post_name) ) {
    $slug = $post->post_name; // Fallback global post
}

// --- 2. QUERY DATABASE (DISESUAIKAN DENGAN SKEMA ASLI) ---
$table_wisata = $wpdb->prefix . 'dw_wisata';
$table_desa   = $wpdb->prefix . 'dw_desa';

// Perbaikan Column: d.foto (bukan foto_profil), w.kontak_pengelola, w.lokasi_maps
$sql = $wpdb->prepare(
    "SELECT w.*, 
            d.nama_desa, 
            d.alamat_lengkap as alamat_desa, 
            d.kabupaten, 
            d.provinsi,
            d.foto as foto_desa_logo 
    FROM $table_wisata w 
    JOIN $table_desa d ON w.id_desa = d.id 
    WHERE w.slug = %s AND w.status = 'aktif'",
    $slug
);

$wisata = $wpdb->get_row($sql);

// --- 3. 404 HANDLER ---
if (!$wisata) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    if (locate_template('404.php')) {
        get_template_part('404');
    } else {
        echo '<div class="min-h-screen flex items-center justify-center bg-gray-50">';
        echo '<div class="text-center">';
        echo '<h1 class="text-6xl font-bold text-gray-300 mb-4">404</h1>';
        echo '<p class="text-xl text-gray-600 mb-8">Wisata tidak ditemukan atau URL salah.</p>';
        echo '<a href="'.home_url('/').'" class="bg-[#00BA61] text-white px-6 py-3 rounded-full font-bold">Kembali ke Beranda</a>';
        echo '</div></div>';
    }
    get_footer();
    exit();
}

// --- 4. SETUP VARIABEL DATA ---
$id_wisata   = $wisata->id;
$judul       = esc_html($wisata->nama_wisata);
$deskripsi   = wp_kses_post($wisata->deskripsi);
$harga       = number_format($wisata->harga_tiket, 0, ',', '.');
$jam_buka    = !empty($wisata->jam_buka) ? esc_html($wisata->jam_buka) : '08:00 - 17:00';

// Mapping Kolom Database yang benar
$kontak      = esc_attr($wisata->kontak_pengelola); 
$link_peta   = !empty($wisata->lokasi_maps) ? $wisata->lokasi_maps : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($judul . ' ' . $wisata->nama_desa);
$foto_utama  = !empty($wisata->foto_utama) ? $wisata->foto_utama : get_template_directory_uri() . '/assets/img/placeholder-wisata.jpg';
$foto_desa   = !empty($wisata->foto_desa_logo) ? $wisata->foto_desa_logo : get_template_directory_uri() . '/assets/img/icon-desa-default.png';

// Parsing Fasilitas
$fasilitas_raw  = isset($wisata->fasilitas) ? $wisata->fasilitas : ''; 
$fasilitas_list = array_filter(array_map('trim', explode(',', $fasilitas_raw)));
if (empty($fasilitas_list)) {
    $fasilitas_list = ['Parkir Area', 'Toilet Umum', 'Spot Foto']; 
}

// Format Alamat
$alamat_full = sprintf('%s, %s, %s', 
    $wisata->nama_desa, 
    $wisata->kabupaten, 
    $wisata->provinsi
);
if (!empty($wisata->alamat_desa)) {
    $alamat_full = $wisata->alamat_desa . ', ' . $alamat_full;
}

?>

<!-- UI Modern (Green Theme) -->
<style>
    :root {
        --primary: #00BA61;
        --primary-dark: #00964E;
        --primary-light: #E0F7EB;
        --text-dark: #1F2937;
        --text-grey: #6B7280;
        --bg-card: #FFFFFF;
        --radius-card: 20px;
    }

    /* Base Typography & Layout */
    .font-sadesa { font-family: 'Inter', sans-serif; }
    .text-primary { color: var(--primary); }
    .bg-primary { background-color: var(--primary); }
    .bg-primary-light { background-color: var(--primary-light); }

    /* Custom Card Style for Sidebar */
    .card-sadesa-sidebar {
        background: var(--bg-card);
        border-radius: var(--radius-card);
        overflow: hidden;
        border: 1px solid #F3F4F6;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-sadesa-sidebar:hover {
        border-color: var(--primary-light);
        box-shadow: 0 10px 25px rgba(0, 186, 97, 0.1);
    }

    /* Hero Gradient */
    .hero-gradient {
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);
    }

    /* Amenity Pill */
    .amenity-pill {
        background: var(--primary-light);
        border: 1px solid rgba(0, 186, 97, 0.2);
        color: var(--primary-dark);
        font-weight: 500;
        transition: all 0.2s;
    }
    .amenity-pill:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    /* Buttons */
    .btn-sadesa-primary {
        background-color: var(--primary);
        color: white;
        transition: all 0.3s ease;
        border: 2px solid var(--primary);
    }
    .btn-sadesa-primary:hover {
        background-color: var(--primary-dark);
        border-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 186, 97, 0.25);
    }

    .btn-sadesa-outline {
        background-color: white;
        color: var(--text-dark);
        border: 2px solid #E5E7EB;
        transition: all 0.3s ease;
    }
    .btn-sadesa-outline:hover {
        border-color: var(--primary);
        color: var(--primary);
        background-color: var(--primary-light);
    }

    /* Glass Effect for Mobile Price */
    .glass-panel {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.5);
    }

    /* Breadcrumb Link Hover */
    .breadcrumb-link:hover {
        color: var(--primary-light);
        text-shadow: 0 0 8px rgba(0,186,97,0.4);
    }
</style>

<div class="bg-gray-50 min-h-screen pb-20 font-sadesa">
    
    <!-- Hero Section Full Width -->
    <div class="relative h-[50vh] md:h-[60vh] lg:h-[70vh] w-full overflow-hidden">
        <img src="<?php echo esc_url($foto_utama); ?>" alt="<?php echo $judul; ?>" class="w-full h-full object-cover transition-transform duration-700 hover:scale-105">
        <div class="absolute inset-0 hero-gradient"></div>
        
        <!-- Breadcrumb on Image -->
        <div class="absolute top-6 left-0 right-0 px-4 md:px-8 z-10">
            <div class="max-w-7xl mx-auto">
                <nav class="flex text-sm font-medium text-white/90 space-x-2 items-center">
                    <a href="<?php echo home_url('/'); ?>" class="breadcrumb-link transition">Beranda</a>
                    <span class="text-white/60"><i class="fas fa-chevron-right text-xs"></i></span>
                    <a href="<?php echo home_url('/wisata'); ?>" class="breadcrumb-link transition">Wisata</a>
                    <span class="text-white/60"><i class="fas fa-chevron-right text-xs"></i></span>
                    <span class="text-white truncate font-semibold"><?php echo $judul; ?></span>
                </nav>
            </div>
        </div>

        <!-- Title Content -->
        <div class="absolute bottom-0 left-0 right-0 px-4 md:px-8 pb-10 z-10">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-4">
                            <span class="px-4 py-1.5 bg-[#00BA61] text-white text-xs font-bold uppercase tracking-wider rounded-full shadow-lg">
                                <?php echo !empty($wisata->kategori) ? esc_html($wisata->kategori) : 'Wisata Alam'; ?>
                            </span>
                            <div class="flex items-center gap-1 bg-black/30 backdrop-blur-sm px-3 py-1 rounded-full border border-white/20">
                                <div class="flex text-yellow-400 text-sm">
                                    <?php 
                                    $rating = floatval($wisata->rating_avg);
                                    for($i=1; $i<=5; $i++) echo ($i <= $rating) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                    ?>
                                </div>
                                <span class="ml-1 text-white/90 text-xs font-medium">(<?php echo intval($wisata->total_ulasan); ?> ulasan)</span>
                            </div>
                        </div>
                        <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-2 leading-tight drop-shadow-md">
                            <?php echo $judul; ?>
                        </h1>
                        <p class="text-white/90 text-lg flex items-center gap-2 font-medium">
                            <i class="fas fa-map-marker-alt text-[#00BA61]"></i>
                            <?php echo esc_html($wisata->nama_desa); ?>, <?php echo esc_html($wisata->kabupaten); ?>
                        </p>
                    </div>
                    
                    <!-- Mobile Price (Visible only on small screens) -->
                    <div class="md:hidden">
                        <div class="glass-panel p-4 rounded-2xl inline-block shadow-lg">
                            <span class="block text-xs text-gray-600 font-bold uppercase mb-1">Harga Tiket</span>
                            <span class="text-xl font-bold text-[#00BA61]">Rp <?php echo $harga; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 md:px-8 -mt-8 relative z-20">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Column: Content (2/3) -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Info Desa Card -->
                <div class="card-sadesa-sidebar p-6 flex items-center justify-between bg-white">
                    <div class="flex items-center gap-4">
                        <img src="<?php echo esc_url($foto_desa); ?>" alt="Desa" class="w-16 h-16 rounded-full object-cover border-2 border-[#E0F7EB] p-0.5">
                        <div>
                            <h3 class="font-bold text-[#1F2937] text-lg">Desa Wisata <?php echo esc_html($wisata->nama_desa); ?></h3>
                            <p class="text-[#6B7280] text-sm flex items-center gap-1">
                                <i class="fas fa-map-pin text-[#00BA61] text-xs"></i>
                                <?php echo esc_html($wisata->kabupaten); ?>
                            </p>
                        </div>
                    </div>
                    <a href="<?php echo home_url('/desa/' . sanitize_title($wisata->nama_desa)); ?>" class="hidden sm:inline-flex items-center gap-2 px-5 py-2.5 bg-[#E0F7EB] text-[#00BA61] rounded-xl font-bold hover:bg-[#00BA61] hover:text-white transition-all text-sm border border-[#00BA61]">
                        <i class="fas fa-store"></i> Lihat Profil
                    </a>
                </div>

                <!-- Deskripsi -->
                <div class="card-sadesa-sidebar p-8 bg-white">
                    <h3 class="text-xl font-bold text-[#1F2937] mb-6 flex items-center gap-3 border-b border-gray-100 pb-4">
                        <div class="w-10 h-10 rounded-full bg-[#E0F7EB] flex items-center justify-center text-[#00BA61]">
                            <i class="fas fa-align-left"></i>
                        </div>
                        Tentang Wisata
                    </h3>
                    <div class="prose prose-green max-w-none text-[#4B5563] leading-relaxed">
                        <?php echo wpautop($deskripsi); ?>
                    </div>
                </div>

                <!-- Fasilitas -->
                <div class="card-sadesa-sidebar p-8 bg-white">
                    <h3 class="text-xl font-bold text-[#1F2937] mb-6 flex items-center gap-3 border-b border-gray-100 pb-4">
                        <div class="w-10 h-10 rounded-full bg-[#E0F7EB] flex items-center justify-center text-[#00BA61]">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        Fasilitas
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach($fasilitas_list as $fasilitas): ?>
                            <div class="amenity-pill px-4 py-3 rounded-xl flex items-center gap-3 text-sm">
                                <i class="fas fa-check-circle text-[#00BA61]"></i>
                                <?php echo esc_html($fasilitas); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Lokasi -->
                <div class="card-sadesa-sidebar p-8 bg-white">
                    <h3 class="text-xl font-bold text-[#1F2937] mb-6 flex items-center gap-3 border-b border-gray-100 pb-4">
                        <div class="w-10 h-10 rounded-full bg-[#E0F7EB] flex items-center justify-center text-[#00BA61]">
                            <i class="fas fa-map-marked-alt"></i>
                        </div>
                        Lokasi
                    </h3>
                    <p class="text-[#4B5563] mb-6 flex items-start gap-3 bg-[#F9FAFB] p-4 rounded-xl border border-gray-100">
                        <i class="fas fa-map-pin text-red-500 mt-1"></i>
                        <?php echo esc_html($alamat_full); ?>
                    </p>
                    
                    <?php if(!empty($wisata->lokasi_maps)): ?>
                         <div class="aspect-w-16 aspect-h-9 rounded-xl overflow-hidden bg-gray-100 relative group mb-4 border border-gray-200">
                            <div class="absolute inset-0 bg-[#F0FDF4] flex items-center justify-center flex-col gap-3 group-hover:bg-[#DCFCE7] transition">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-md">
                                    <i class="fas fa-map text-3xl text-[#00BA61]"></i>
                                </div>
                                <span class="text-[#00964E] font-bold">Lihat di Google Maps</span>
                            </div>
                         </div>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url($link_peta); ?>" target="_blank" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 bg-white border-2 border-gray-200 rounded-xl text-[#1F2937] font-bold hover:border-[#00BA61] hover:text-[#00BA61] hover:bg-[#E0F7EB] transition-all">
                        <i class="fas fa-directions"></i> Buka Navigasi Maps
                    </a>
                </div>

            </div>

            <!-- Right Column: Sidebar (1/3) -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Booking Card -->
                    <div class="card-sadesa-sidebar bg-white relative">
                        <div class="h-2 bg-[#00BA61] w-full"></div>
                        <div class="p-6">
                            <div class="mb-6 border-b border-dashed border-gray-200 pb-6">
                                <span class="text-[#6B7280] text-xs font-bold uppercase tracking-wide">Harga Tiket Masuk</span>
                                <div class="flex items-baseline gap-1 mt-1">
                                    <span class="text-4xl font-extrabold text-[#00BA61]">Rp <?php echo $harga; ?></span>
                                    <span class="text-[#6B7280] font-medium">/ org</span>
                                </div>
                            </div>

                            <div class="space-y-4 mb-8">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-100">
                                    <span class="text-[#4B5563] text-sm font-medium"><i class="far fa-clock mr-2 text-[#00BA61]"></i> Jam Buka</span>
                                    <span class="font-bold text-[#1F2937] text-sm"><?php echo $jam_buka; ?></span>
                                </div>
                                <!-- Status indicator removed -->
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-3">
                                <?php if ($kontak): ?>
                                <a href="https://wa.me/<?php echo esc_attr($kontak); ?>?text=Halo, saya ingin bertanya tentang wisata <?php echo urlencode($judul); ?>" target="_blank" class="w-full btn-sadesa-primary py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 text-lg font-bold">
                                    <i class="fab fa-whatsapp text-2xl"></i> Hubungi Pengelola
                                </a>
                                <?php else: ?>
                                <button disabled class="w-full bg-gray-100 text-gray-400 font-bold py-3.5 px-4 rounded-xl cursor-not-allowed border border-gray-200">
                                    Kontak Tidak Tersedia
                                </button>
                                <?php endif; ?>
                                
                                <button onclick="alert('Fitur favorit segera hadir!')" class="w-full btn-sadesa-outline py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 font-bold">
                                    <i class="far fa-heart"></i> Simpan ke Favorit
                                </button>
                            </div>
                            
                            <p class="text-[10px] text-center text-gray-400 mt-6 leading-tight">
                                *Pastikan melakukan konfirmasi ke pengelola sebelum datang untuk ketersediaan tiket.
                            </p>
                        </div>
                    </div>

                    <!-- Verified Badge -->
                    <div class="bg-[#F0FDF4] rounded-2xl p-6 border border-[#BBF7D0] text-center shadow-sm">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-3 text-[#00BA61] shadow-sm border border-[#E0F7EB]">
                            <i class="fas fa-shield-alt text-xl"></i>
                        </div>
                        <h4 class="font-bold text-[#00964E] mb-1">Wisata Terverifikasi</h4>
                        <p class="text-xs text-[#166534]">Data valid dan dikelola langsung oleh Desa <?php echo esc_html($wisata->nama_desa); ?>.</p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>