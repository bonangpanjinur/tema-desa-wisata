<?php
/**
 * Template Name: Detail Wisata Sadesa
 */
get_header();

// 1. LOGIKA DATA (Hybrid: Local Post atau API)
$post_id = get_the_ID();
$api_data = null;

// Jika API Helper ada, kita coba fetch detail terbaru dari API untuk memastikan data sinkron
if (function_exists('dw_fetch_api_data')) {
    // Kita gunakan ID post sebagai referensi ID di API (Asumsi ID sama)
    $api_data = dw_fetch_api_data('/wp-json/dw/v1/wisata/' . $post_id);
}

// Data Fallback (Local WP Post Meta) jika API gagal/lambat
$title = get_the_title();
$img_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'full') : 'https://via.placeholder.com/800x600';
$content = get_the_content();
$price = get_post_meta($post_id, 'harga_tiket', true) ?: 0;
$location = get_post_meta($post_id, 'lokasi', true) ?: 'Desa Wisata';
$rating = 4.8;
$facilities = ['Parkir Area', 'Toilet Bersih', 'Spot Foto', 'Warung Makan'];

// Jika API berhasil, override data
if ($api_data && !isset($api_data['error'])) {
    // Sesuaikan key dengan respon API Anda
    if (!empty($api_data['nama_wisata'])) $title = $api_data['nama_wisata'];
    if (!empty($api_data['thumbnail'])) $img_url = $api_data['thumbnail'];
    if (!empty($api_data['deskripsi'])) $content = $api_data['deskripsi'];
    if (isset($api_data['harga_tiket'])) $price = $api_data['harga_tiket'];
    if (!empty($api_data['alamat'])) $location = $api_data['alamat'];
    if (!empty($api_data['rating'])) $rating = $api_data['rating'];
    if (!empty($api_data['fasilitas'])) $facilities = $api_data['fasilitas']; // Asumsi array
}

$price_display = ($price > 0) ? 'Rp ' . number_format($price, 0, ',', '.') : 'Gratis';
?>

<!-- STYLE KHUSUS HALAMAN INI -->
<style>
    .hero-gradient { background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0) 50%, rgba(0,0,0,0.6) 100%); }
    .content-overlap { margin-top: -40px; border-top-left-radius: 30px; border-top-right-radius: 30px; }
</style>

<div class="bg-gray-50 min-h-screen pb-24 relative">

    <!-- HERO IMAGE SECTION -->
    <div class="relative h-[300px] md:h-[400px] w-full bg-gray-200">
        <img src="<?php echo esc_url($img_url); ?>" class="w-full h-full object-cover">
        <div class="hero-gradient absolute inset-0 pointer-events-none"></div>
        
        <!-- Back Button -->
        <a href="javascript:history.back()" class="absolute top-24 left-4 md:left-8 bg-white/20 backdrop-blur-md border border-white/30 text-white w-10 h-10 rounded-full flex items-center justify-center hover:bg-white hover:text-gray-800 transition z-10">
            <i class="fas fa-arrow-left"></i>
        </a>

        <!-- Title Overlay (Mobile Friendly) -->
        <div class="absolute bottom-14 left-0 right-0 px-5 md:px-8 pb-4 text-white z-10">
            <span class="bg-primary/90 text-white text-[10px] font-bold px-2 py-1 rounded mb-2 inline-block">
                Destinasi Wisata
            </span>
            <h1 class="text-2xl md:text-4xl font-bold leading-tight drop-shadow-md mb-1"><?php echo esc_html($title); ?></h1>
            <div class="flex items-center gap-4 text-xs md:text-sm text-gray-100 font-medium">
                <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($location); ?></span>
                <span class="flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i> <?php echo esc_html($rating); ?> (Ulasan)</span>
            </div>
        </div>
    </div>

    <!-- CONTENT BODY (Overlapping) -->
    <div class="content-overlap relative bg-white z-20 px-5 md:px-8 py-8 shadow-sm min-h-[50vh]">
        <div class="container mx-auto max-w-4xl">
            
            <!-- Info Bar (Horizontal Scrollable) -->
            <div class="flex gap-4 overflow-x-auto no-scrollbar mb-8 pb-2 border-b border-gray-50">
                <div class="flex items-center gap-3 p-3 bg-green-50 rounded-xl min-w-[140px] border border-green-100">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-primary shadow-sm text-lg"><i class="fas fa-ticket-alt"></i></div>
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase font-bold">Tiket</p>
                        <p class="text-xs font-bold text-gray-800">Tersedia</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-xl min-w-[140px] border border-blue-100">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-blue-500 shadow-sm text-lg"><i class="fas fa-clock"></i></div>
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase font-bold">Buka</p>
                        <p class="text-xs font-bold text-gray-800">08:00 - 17:00</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 bg-orange-50 rounded-xl min-w-[140px] border border-orange-100">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-orange-500 shadow-sm text-lg"><i class="fas fa-cloud-sun"></i></div>
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase font-bold">Cuaca</p>
                        <p class="text-xs font-bold text-gray-800">Cerah</p>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Main Description -->
                <div class="md:col-span-2">
                    <h3 class="font-bold text-lg text-gray-800 mb-3 flex items-center gap-2">
                        <i class="fas fa-align-left text-primary"></i> Deskripsi
                    </h3>
                    <div class="prose prose-sm text-gray-600 leading-relaxed text-justify mb-8">
                        <?php echo apply_filters('the_content', $content); ?>
                    </div>

                    <!-- Fasilitas -->
                    <h3 class="font-bold text-lg text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-concierge-bell text-primary"></i> Fasilitas
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-8">
                        <?php foreach ($facilities as $fasilitas): ?>
                        <div class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-lg border border-gray-100">
                            <i class="fas fa-check-circle text-primary text-xs"></i>
                            <span class="text-xs font-medium text-gray-600"><?php echo esc_html($fasilitas); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sidebar (Map & Contact) -->
                <div class="md:col-span-1">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 sticky top-24">
                        <h3 class="font-bold text-gray-800 mb-3 text-sm">Lokasi</h3>
                        <div class="bg-gray-200 w-full h-40 rounded-lg mb-4 flex items-center justify-center text-gray-400">
                            <!-- Placeholder Map -->
                            <div class="text-center">
                                <i class="fas fa-map-marked-alt text-3xl mb-1"></i>
                                <p class="text-xs">Peta Lokasi</p>
                            </div>
                        </div>
                        <a href="#" class="block w-full text-center py-2 bg-gray-100 text-gray-600 text-xs font-bold rounded-lg hover:bg-gray-200 transition">
                            Buka di Google Maps
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- BOTTOM STICKY ACTION BAR -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 z-40 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
        <div class="container mx-auto max-w-4xl flex items-center justify-between gap-4">
            <div>
                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">Harga Tiket</p>
                <p class="text-xl font-bold text-primary"><?php echo $price_display; ?> <span class="text-xs text-gray-400 font-normal">/org</span></p>
            </div>
            
            <div class="flex gap-2">
                <a href="https://wa.me/?text=Halo%20saya%20mau%20tanya%20wisata%20<?php echo urlencode($title); ?>" target="_blank" class="w-12 h-12 flex items-center justify-center border border-gray-200 rounded-xl text-green-600 hover:bg-green-50 transition">
                    <i class="fab fa-whatsapp text-xl"></i>
                </a>
                <button class="bg-primary text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-green-200 hover:bg-green-700 transition transform hover:-translate-y-0.5">
                    Booking Sekarang
                </button>
            </div>
        </div>
    </div>

</div>

<?php get_footer(); ?>