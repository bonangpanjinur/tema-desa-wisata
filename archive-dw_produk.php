<?php get_header(); ?>

<!-- Page Header -->
<div class="mt-4 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="font-bold text-2xl text-gray-800">Produk Desa</h1>
        <p class="text-sm text-gray-500">Temukan kerajinan dan kuliner unik dari desa wisata</p>
    </div>
    
    <!-- Filter & Sort -->
    <div class="flex gap-3 overflow-x-auto no-scrollbar pb-1">
        <select class="bg-white border border-gray-200 text-sm rounded-lg px-4 py-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none">
            <option>Urutkan Terbaru</option>
            <option>Harga Terendah</option>
            <option>Harga Tertinggi</option>
            <option>Paling Laris</option>
        </select>
        <button class="bg-white border border-gray-200 text-gray-600 px-4 py-2 rounded-lg text-sm flex items-center gap-2 hover:bg-gray-50 whitespace-nowrap">
            <i class="fas fa-filter"></i> Filter
        </button>
    </div>
</div>

<div class="pb-10">
    <?php if ( have_posts() ) : ?>
        <!-- Grid: 2 Col Mobile, 3 Col Tablet, 4 Col Desktop, 5 Col Large -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 md:gap-6">
            <?php while ( have_posts() ) : the_post(); 
                $harga = get_post_meta(get_the_ID(), 'harga', true) ?: 0;
                $penjual = get_post_meta(get_the_ID(), 'nama_toko', true) ?: 'UMKM Desa';
                $terjual = get_post_meta(get_the_ID(), 'terjual', true) ?: 0;
                $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium_large') : 'https://via.placeholder.com/500x500?text=No+Image';
            ?>
                <!-- Product Card -->
                <div class="bg-white p-2.5 md:p-3 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between h-full hover:shadow-xl hover:-translate-y-1 transition duration-300 group">
                    <a href="<?php the_permalink(); ?>">
                        <div class="aspect-square rounded-lg bg-gray-100 overflow-hidden mb-3 relative">
                            <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                        </div>
                        <h4 class="font-medium text-gray-800 text-sm line-clamp-2 leading-snug mb-1 group-hover:text-emerald-600 transition"><?php the_title(); ?></h4>
                        <div class="flex items-center gap-1 mb-2">
                            <i class="fas fa-store text-[10px] text-gray-400"></i>
                            <p class="text-[10px] text-gray-500 truncate"><?php echo esc_html($penjual); ?></p>
                        </div>
                    </a>
                    <div class="mt-auto flex justify-between items-end">
                        <div class="flex flex-col">
                            <span class="text-emerald-700 font-bold text-sm">Rp <?php echo number_format($harga, 0, ',', '.'); ?></span>
                            <span class="text-[10px] text-gray-400">Terjual <?php echo $terjual; ?></span>
                        </div>
                        <button class="bg-emerald-50 text-emerald-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-emerald-600 hover:text-white transition shadow-sm">
                            <i class="fas fa-cart-plus text-xs"></i>
                        </button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <div class="flex justify-center mt-10">
             <?php
            the_posts_pagination(array(
                'prev_text' => '<span class="px-3 py-1 border rounded bg-white hover:bg-gray-50">Prev</span>',
                'next_text' => '<span class="px-3 py-1 border rounded bg-white hover:bg-gray-50">Next</span>',
                'screen_reader_text' => ' ',
                'mid_size' => 2,
            ));
            ?>
        </div>

    <?php else : ?>
        <div class="text-center py-20 bg-gray-50 rounded-xl">
            <i class="fas fa-box-open text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500 font-medium">Produk tidak ditemukan.</p>
            <p class="text-gray-400 text-sm">Coba kata kunci lain atau reset filter.</p>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>