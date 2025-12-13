<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); 
    $harga = get_post_meta(get_the_ID(), 'harga_tiket', true) ?: 0;
    $lokasi = get_post_meta(get_the_ID(), 'lokasi', true) ?: 'Desa Wisata';
    $durasi = get_post_meta(get_the_ID(), 'durasi', true) ?: 'Seharian';
    $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'full') : 'https://via.placeholder.com/800x600?text=No+Image';
?>

<!-- Hero Image -->
<div class="relative h-64 w-full">
    <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover">
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 to-transparent"></div>
    <a href="javascript:history.back()" class="absolute top-4 left-4 bg-white/20 backdrop-blur-md text-white w-10 h-10 rounded-full flex items-center justify-center hover:bg-white/40 transition">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div class="absolute bottom-4 left-4 right-4 text-white">
        <span class="bg-emerald-500 text-[10px] font-bold px-2 py-0.5 rounded mb-2 inline-block">Wisata</span>
        <h1 class="text-2xl font-bold leading-tight mb-1"><?php the_title(); ?></h1>
        <div class="flex items-center gap-4 text-xs text-gray-200">
            <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($lokasi); ?></span>
            <span class="flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i> 4.8 (120 Review)</span>
        </div>
    </div>
</div>

<!-- Content Container -->
<div class="bg-gray-50 -mt-4 rounded-t-3xl relative z-10 px-5 pt-6 pb-24 min-h-[500px]">
    
    <!-- Info Grid -->
    <div class="flex justify-between bg-white p-4 rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="text-center">
            <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center text-blue-600 mx-auto mb-1">
                <i class="fas fa-clock"></i>
            </div>
            <p class="text-[10px] text-gray-500">Durasi</p>
            <p class="text-xs font-bold text-gray-800"><?php echo esc_html($durasi); ?></p>
        </div>
        <div class="text-center border-l border-gray-100 pl-4">
            <div class="w-10 h-10 bg-orange-50 rounded-full flex items-center justify-center text-orange-600 mx-auto mb-1">
                <i class="fas fa-user-friends"></i>
            </div>
            <p class="text-[10px] text-gray-500">Kapasitas</p>
            <p class="text-xs font-bold text-gray-800">50 Pax</p>
        </div>
        <div class="text-center border-l border-gray-100 pl-4">
            <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center text-green-600 mx-auto mb-1">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <p class="text-[10px] text-gray-500">Tiket</p>
            <p class="text-xs font-bold text-gray-800">Tersedia</p>
        </div>
    </div>

    <!-- Deskripsi -->
    <div class="mb-6">
        <h3 class="font-bold text-gray-800 mb-2">Deskripsi</h3>
        <div class="prose prose-sm text-gray-600 leading-relaxed text-sm">
            <?php the_content(); ?>
        </div>
    </div>

    <!-- Fasilitas (Hardcoded Example) -->
    <div class="mb-6">
        <h3 class="font-bold text-gray-800 mb-3">Fasilitas</h3>
        <div class="grid grid-cols-2 gap-3">
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <i class="fas fa-check-circle text-emerald-500"></i> Parkir Luas
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <i class="fas fa-check-circle text-emerald-500"></i> Toilet Bersih
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <i class="fas fa-check-circle text-emerald-500"></i> Warung Makan
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-600">
                <i class="fas fa-check-circle text-emerald-500"></i> Spot Foto
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Bar (Above Footer Nav) -->
<div class="fixed bottom-[70px] left-0 right-0 sm:max-w-md sm:mx-auto px-4 z-30 pointer-events-none">
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-4 flex items-center justify-between pointer-events-auto">
        <div>
            <p class="text-[10px] text-gray-500">Harga Tiket</p>
            <p class="text-emerald-600 font-bold text-lg">Rp <?php echo number_format($harga, 0, ',', '.'); ?></p>
        </div>
        <button class="bg-emerald-600 text-white px-6 py-2.5 rounded-lg font-bold text-sm hover:bg-emerald-700 transition shadow-md shadow-emerald-200">
            Booking Sekarang
        </button>
    </div>
</div>

<?php endwhile; ?>

<div class="h-10"></div> <!-- Extra spacer for floating bar -->

<?php get_footer(); ?>