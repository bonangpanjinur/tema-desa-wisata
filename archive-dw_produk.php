<?php get_header(); ?>

<div class="px-4 mt-4 mb-2 flex justify-between items-end">
    <div>
        <h2 class="font-bold text-xl text-gray-800">Produk Desa</h2>
        <p class="text-xs text-gray-500">Oleh-oleh & Kerajinan UMKM</p>
    </div>
    <button class="text-emerald-600 text-sm"><i class="fas fa-filter"></i> Filter</button>
</div>

<div class="px-4 pb-6">
    <?php if ( have_posts() ) : ?>
        <div class="grid grid-cols-2 gap-3">
            <?php while ( have_posts() ) : the_post(); 
                $harga = get_post_meta(get_the_ID(), 'harga', true) ?: 0;
                $penjual = get_post_meta(get_the_ID(), 'nama_toko', true) ?: 'UMKM Desa';
                $terjual = get_post_meta(get_the_ID(), 'terjual', true) ?: 0;
                $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium') : 'https://via.placeholder.com/500x500?text=No+Image';
            ?>
                <!-- Product Card Grid -->
                <div class="bg-white p-2.5 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-md transition">
                    <a href="<?php the_permalink(); ?>">
                        <div class="h-36 rounded-lg bg-gray-200 overflow-hidden mb-2 relative">
                            <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover">
                        </div>
                        <h4 class="font-medium text-gray-800 text-sm line-clamp-2 leading-snug mb-1"><?php the_title(); ?></h4>
                        <div class="flex items-center gap-1 mb-2">
                            <i class="fas fa-store text-[10px] text-gray-400"></i>
                            <p class="text-[10px] text-gray-500 truncate"><?php echo esc_html($penjual); ?></p>
                        </div>
                    </a>
                    <div class="mt-auto flex justify-between items-end">
                        <div class="flex flex-col">
                            <span class="text-emerald-700 font-bold text-sm">Rp <?php echo number_format($harga, 0, ',', '.'); ?></span>
                            <span class="text-[9px] text-gray-400">Terjual <?php echo $terjual; ?></span>
                        </div>
                        <button class="bg-emerald-50 text-emerald-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-emerald-600 hover:text-white transition shadow-sm" onclick="event.preventDefault(); alert('Fitur tambah keranjang akan segera aktif!');">
                            <i class="fas fa-cart-plus text-xs"></i>
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <div class="flex justify-center mt-8">
             <?php
            the_posts_pagination(array(
                'prev_text' => '<i class="fas fa-chevron-left"></i>',
                'next_text' => '<i class="fas fa-chevron-right"></i>',
                'screen_reader_text' => ' ',
            ));
            ?>
        </div>

    <?php else : ?>
        <div class="text-center py-20">
            <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Produk tidak ditemukan.</p>
        </div>
    <?php endif; ?>
</div>

<div class="h-6"></div>

<?php get_footer(); ?>