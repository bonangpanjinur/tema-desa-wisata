<?php get_header(); ?>

<?php while (have_posts()) : the_post(); 
    $harga = get_post_meta(get_the_ID(), '_dw_harga', true);
    $stok = get_post_meta(get_the_ID(), '_dw_stok', true);
    // TODO: Ambil nama pedagang dari relasi user
    $nama_toko = "Toko Desa"; 
?>

<!-- Full Image with Back Button Overlay -->
<div class="relative w-full h-[320px] bg-gray-200">
    <?php if(has_post_thumbnail()) the_post_thumbnail('large', ['class' => 'w-full h-full object-cover']); ?>
    <div class="absolute top-0 left-0 w-full h-20 bg-gradient-to-b from-black/40 to-transparent"></div>
</div>

<!-- Content Container (Rounded Top) -->
<div class="relative -mt-6 bg-white rounded-t-3xl px-5 py-6 min-h-[50vh] pb-32">
    
    <div class="flex justify-between items-start mb-2">
        <h1 class="text-xl font-bold text-gray-900 leading-tight w-[70%]"><?php the_title(); ?></h1>
        <div class="text-right">
            <span class="block text-lg font-bold text-primary"><?php echo dw_price($harga); ?></span>
        </div>
    </div>

    <!-- Meta Info -->
    <div class="flex items-center gap-3 text-xs text-gray-500 mb-6 border-b border-gray-100 pb-4">
        <span class="flex items-center gap-1"><i class="ph-fill ph-package"></i> Stok: <?php echo $stok; ?></span>
        <span class="flex items-center gap-1"><i class="ph-fill ph-star text-yellow-400"></i> 4.8</span>
    </div>

    <!-- Seller Card -->
    <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-xl mb-6">
        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-500">
            <i class="ph-fill ph-storefront text-xl"></i>
        </div>
        <div class="flex-1">
            <h4 class="text-sm font-bold text-gray-800"><?php echo $nama_toko; ?></h4>
            <p class="text-[10px] text-gray-500">Online 5 menit lalu</p>
        </div>
        <button class="text-xs font-bold text-primary border border-primary px-3 py-1.5 rounded-lg">Kunjungi</button>
    </div>

    <!-- Deskripsi -->
    <h3 class="font-bold text-gray-800 mb-2">Deskripsi</h3>
    <div class="text-sm text-gray-600 leading-relaxed space-y-2">
        <?php the_content(); ?>
    </div>

</div>

<!-- Sticky Bottom Action -->
<div class="fixed bottom-0 w-full max-w-[480px] bg-white border-t border-gray-100 p-4 z-50 flex items-center gap-3 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
    <button class="w-12 h-12 border border-gray-200 rounded-xl flex items-center justify-center text-gray-500 hover:text-primary hover:border-primary">
        <i class="ph-bold ph-chat-circle-text text-xl"></i>
    </button>
    <button id="btn-add-to-cart" class="flex-1 bg-gray-900 text-white font-bold h-12 rounded-xl flex items-center justify-center gap-2 active:scale-95 transition-transform" 
            data-id="<?php echo get_the_ID(); ?>">
        <i class="ph-bold ph-plus"></i> Masuk Keranjang
    </button>
    <button class="flex-1 bg-primary text-white font-bold h-12 rounded-xl shadow-lg shadow-primary/30 active:scale-95 transition-transform">
        Beli Langsung
    </button>
</div>

<?php endwhile; ?>
<?php get_footer(); ?>