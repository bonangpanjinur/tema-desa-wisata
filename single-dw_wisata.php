<?php
/**
 * Template Name: Detail Wisata Sadesa
 */
get_header();

// 1. Ambil Slug & Data
$post_id = get_the_ID();
$slug = get_post_field('post_name', $post_id);
$api_data = null;

if (function_exists('dw_fetch_api_data')) {
    // [PENTING] Gunakan endpoint slug, bukan ID
    $api_data = dw_fetch_api_data('/wp-json/dw/v1/wisata/slug/' . $slug);
}

// 2. Variable Fallback
$title = get_the_title();
$img_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'full') : 'https://via.placeholder.com/800x600';
$content = get_the_content();
$price = get_post_meta($post_id, 'harga_tiket', true) ?: 0;
$location = get_post_meta($post_id, 'lokasi', true) ?: 'Desa Wisata';
$rating = 4.8;
$facilities = ['Parkir Area', 'Toilet', 'Spot Foto'];

// 3. Override jika API Sukses
if ($api_data && !isset($api_data['error'])) {
    if (!empty($api_data['nama_wisata'])) $title = $api_data['nama_wisata'];
    if (!empty($api_data['thumbnail'])) $img_url = $api_data['thumbnail'];
    if (!empty($api_data['deskripsi'])) $content = $api_data['deskripsi'];
    if (isset($api_data['harga_tiket'])) $price = $api_data['harga_tiket'];
    if (!empty($api_data['alamat'])) $location = $api_data['alamat'];
    if (!empty($api_data['fasilitas'])) {
        $facilities = is_array($api_data['fasilitas']) ? $api_data['fasilitas'] : explode(',', $api_data['fasilitas']);
    }
}

$price_display = ($price > 0) ? 'Rp ' . number_format($price, 0, ',', '.') : 'Gratis';
?>

<div class="bg-gray-50 min-h-screen pb-24 relative">
    
    <!-- Hero Header -->
    <div class="relative h-[350px] md:h-[450px] w-full">
        <img src="<?php echo esc_url($img_url); ?>" class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
        
        <a href="javascript:history.back()" class="absolute top-24 left-6 w-10 h-10 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white border border-white/30 hover:bg-white hover:text-gray-900 transition z-20">
            <i class="fas fa-arrow-left"></i>
        </a>

        <div class="absolute bottom-12 left-0 right-0 px-6 container mx-auto max-w-4xl text-white z-20">
            <span class="bg-primary px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider mb-2 inline-block shadow-lg">Wisata</span>
            <h1 class="text-3xl md:text-5xl font-bold leading-tight mb-2 drop-shadow-md"><?php echo esc_html($title); ?></h1>
            <div class="flex items-center gap-4 text-sm font-medium text-gray-200">
                <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($location); ?></span>
                <span class="flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?></span>
            </div>
        </div>
    </div>

    <!-- Content Card Overlap -->
    <div class="relative -mt-6 z-30 px-4">
        <div class="bg-white rounded-t-3xl shadow-lg p-6 md:p-8 container mx-auto max-w-4xl min-h-[500px]">
            
            <!-- Quick Stats -->
            <div class="flex gap-3 overflow-x-auto no-scrollbar pb-6 mb-6 border-b border-gray-100">
                <div class="flex items-center gap-3 p-3 bg-green-50 rounded-xl border border-green-100 min-w-[140px]">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-primary shadow-sm"><i class="fas fa-ticket-alt"></i></div>
                    <div><p class="text-[10px] text-gray-500 font-bold uppercase">Tiket</p><p class="text-xs font-bold text-gray-800">Tersedia</p></div>
                </div>
                <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-xl border border-blue-100 min-w-[140px]">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-blue-500 shadow-sm"><i class="fas fa-clock"></i></div>
                    <div><p class="text-[10px] text-gray-500 font-bold uppercase">Buka</p><p class="text-xs font-bold text-gray-800">08:00 - 17:00</p></div>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="md:col-span-2">
                    <h3 class="font-bold text-lg text-gray-900 mb-3">Tentang Destinasi</h3>
                    <div class="prose prose-sm text-gray-600 leading-relaxed text-justify mb-8">
                        <?php echo apply_filters('the_content', $content); ?>
                    </div>

                    <h3 class="font-bold text-lg text-gray-900 mb-3">Fasilitas</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <?php foreach($facilities as $f): ?>
                        <div class="flex items-center gap-2 bg-gray-50 px-3 py-2 rounded-lg text-sm text-gray-600 border border-gray-100">
                            <i class="fas fa-check-circle text-primary text-xs"></i> <?php echo esc_html(trim($f)); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="md:col-span-1">
                    <div class="bg-white border border-gray-200 rounded-2xl p-4 shadow-sm">
                        <h4 class="font-bold text-sm text-gray-900 mb-3">Lokasi</h4>
                        <div class="aspect-square bg-gray-200 rounded-xl mb-3 flex items-center justify-center text-gray-400">
                            <i class="fas fa-map-marked-alt text-3xl"></i>
                        </div>
                        <a href="https://maps.google.com/?q=<?php echo urlencode($location); ?>" target="_blank" class="block w-full py-2.5 bg-gray-900 text-white text-center rounded-xl text-xs font-bold hover:bg-gray-800">Buka Maps</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Bottom Booking -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 z-50 shadow-[0_-5px_20px_rgba(0,0,0,0.05)]">
        <div class="container mx-auto max-w-4xl flex items-center justify-between">
            <div>
                <p class="text-[10px] text-gray-400 font-bold uppercase">Harga Tiket</p>
                <p class="text-xl font-bold text-primary"><?php echo $price_display; ?> <span class="text-xs text-gray-400 font-normal">/org</span></p>
            </div>
            <div class="flex gap-2">
                <a href="https://wa.me/?text=Info..." class="w-12 h-12 flex items-center justify-center bg-green-50 text-green-600 rounded-xl border border-green-200 hover:bg-green-100"><i class="fab fa-whatsapp text-xl"></i></a>
                <button class="bg-primary text-white px-6 py-3 rounded-xl font-bold shadow-lg shadow-green-200 hover:bg-green-700 transition">Pesan Tiket</button>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>