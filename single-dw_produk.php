<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); 
    $harga = get_post_meta(get_the_ID(), 'harga', true) ?: 0;
    $penjual = get_post_meta(get_the_ID(), 'nama_toko', true) ?: 'UMKM Desa';
    $terjual = get_post_meta(get_the_ID(), 'terjual', true) ?: 0;
    $stok = get_post_meta(get_the_ID(), 'stok', true) ?: 10;
    $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'large') : 'https://via.placeholder.com/600x600?text=No+Image';
?>

<!-- Header Simple with Back Button -->
<div class="bg-white p-4 flex items-center gap-3 sticky top-0 z-20 shadow-sm sm:hidden">
    <a href="javascript:history.back()" class="text-gray-600"><i class="fas fa-arrow-left text-lg"></i></a>
    <h1 class="font-bold text-gray-800 text-lg truncate">Detail Produk</h1>
    <div class="ml-auto"><i class="fas fa-share-alt text-gray-500"></i></div>
</div>

<div class="pb-28">
    <!-- Product Image -->
    <div class="bg-white mb-2">
        <div class="aspect-square w-full bg-gray-100">
            <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover">
        </div>
    </div>

    <!-- Product Info -->
    <div class="bg-white p-4 mb-2">
        <div class="flex justify-between items-start mb-2">
            <h1 class="text-lg font-bold text-gray-900 leading-snug w-3/4"><?php the_title(); ?></h1>
            <div class="text-right">
                <span class="text-xs text-gray-500 block">Stok: <?php echo $stok; ?></span>
            </div>
        </div>
        <p class="text-2xl font-bold text-emerald-600 mb-3">Rp <?php echo number_format($harga, 0, ',', '.'); ?></p>
        <div class="flex items-center gap-3 text-sm text-gray-500 border-t border-gray-100 pt-3">
            <span class="flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i> 4.9</span>
            <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
            <span><?php echo $terjual; ?> Terjual</span>
        </div>
    </div>

    <!-- Seller Info -->
    <div class="bg-white p-4 mb-2 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gray-200 rounded-full overflow-hidden">
                <!-- Placeholder Avatar -->
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($penjual); ?>&background=random" class="w-full h-full object-cover">
            </div>
            <div>
                <h3 class="font-bold text-gray-800 text-sm"><?php echo esc_html($penjual); ?></h3>
                <p class="text-xs text-emerald-600"><i class="fas fa-map-marker-alt"></i> Desa Wisata</p>
            </div>
        </div>
        <button class="border border-emerald-600 text-emerald-600 px-3 py-1 rounded text-xs font-bold">Kunjungi</button>
    </div>

    <!-- Description -->
    <div class="bg-white p-4">
        <h3 class="font-bold text-gray-800 mb-2">Detail Produk</h3>
        <div class="prose prose-sm text-gray-600 text-sm leading-relaxed">
            <?php the_content(); ?>
        </div>
    </div>
</div>

<!-- Sticky Bottom Action (Above Footer Nav) -->
<div class="fixed bottom-[70px] left-0 right-0 sm:max-w-md sm:mx-auto px-4 z-30 pointer-events-none">
    <div class="flex gap-2 pointer-events-auto">
        <button class="bg-white border border-emerald-600 text-emerald-600 w-12 h-12 rounded-xl flex items-center justify-center text-xl shadow-lg">
            <i class="fas fa-comment-dots"></i>
        </button>
        <button class="bg-white border border-emerald-600 text-emerald-600 flex-1 rounded-xl font-bold text-sm shadow-lg">
            Beli Langsung
        </button>
        <button class="bg-emerald-600 text-white flex-1 rounded-xl font-bold text-sm shadow-lg shadow-emerald-200">
            + Keranjang
        </button>
    </div>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>