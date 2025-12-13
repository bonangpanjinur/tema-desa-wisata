<?php get_header(); ?>

<div class="px-4 mt-4 mb-2">
    <h2 class="font-bold text-xl text-gray-800">Jelajah Wisata</h2>
    <p class="text-xs text-gray-500">Temukan surga tersembunyi di desa kami</p>
</div>

<!-- Filter Chips (Static Prototype) -->
<div class="px-4 mb-4 overflow-x-auto no-scrollbar">
    <div class="flex gap-2">
        <button class="bg-emerald-600 text-white px-4 py-1.5 rounded-full text-xs font-medium whitespace-nowrap">Semua</button>
        <button class="bg-white text-gray-600 border border-gray-200 px-4 py-1.5 rounded-full text-xs font-medium whitespace-nowrap">Alam</button>
        <button class="bg-white text-gray-600 border border-gray-200 px-4 py-1.5 rounded-full text-xs font-medium whitespace-nowrap">Budaya</button>
        <button class="bg-white text-gray-600 border border-gray-200 px-4 py-1.5 rounded-full text-xs font-medium whitespace-nowrap">Kuliner</button>
    </div>
</div>

<div class="px-4 pb-6 space-y-4">
    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); 
            $harga = get_post_meta(get_the_ID(), 'harga_tiket', true) ?: 0;
            $lokasi = get_post_meta(get_the_ID(), 'lokasi', true) ?: 'Desa Wisata';
            $rating = 4.8; // Placeholder
            $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'large') : 'https://via.placeholder.com/600x400?text=No+Image';
        ?>
            <!-- Wisata Card (List Style) -->
            <a href="<?php the_permalink(); ?>" class="block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group">
                <div class="h-40 bg-gray-200 relative overflow-hidden">
                    <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-lg text-xs font-bold flex items-center gap-1 shadow-sm">
                        <i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?>
                    </div>
                </div>
                <div class="p-4">
                    <div class="flex justify-between items-start mb-1">
                        <h3 class="font-bold text-gray-800 text-lg leading-tight"><?php the_title(); ?></h3>
                    </div>
                    <p class="text-xs text-gray-500 mb-3 flex items-center gap-1">
                        <i class="fas fa-map-marker-alt text-red-500"></i> <?php echo esc_html($lokasi); ?>
                    </p>
                    <div class="flex justify-between items-center border-t border-dashed border-gray-100 pt-3">
                        <div>
                            <p class="text-[10px] text-gray-400">Mulai dari</p>
                            <p class="text-emerald-600 font-bold text-base">Rp <?php echo number_format($harga, 0, ',', '.'); ?></p>
                        </div>
                        <span class="bg-emerald-50 text-emerald-600 px-3 py-1.5 rounded-lg text-xs font-medium group-hover:bg-emerald-600 group-hover:text-white transition">Lihat Detail</span>
                    </div>
                </div>
            </a>
        <?php endwhile; ?>
        
        <!-- Pagination -->
        <div class="flex justify-center mt-6">
            <?php
            the_posts_pagination(array(
                'prev_text' => '<i class="fas fa-chevron-left"></i>',
                'next_text' => '<i class="fas fa-chevron-right"></i>',
                'class'     => 'flex gap-2 text-sm'
            ));
            ?>
        </div>

    <?php else : ?>
        <div class="text-center py-10 bg-white rounded-xl">
            <i class="fas fa-map-signs text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">Belum ada wisata yang ditemukan.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Spacer agar tidak tertutup nav bar -->
<div class="h-6"></div>

<?php get_footer(); ?>