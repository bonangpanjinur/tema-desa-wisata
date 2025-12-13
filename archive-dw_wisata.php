<?php get_header(); ?>

<div class="mt-4 mb-6">
    <div class="bg-emerald-600 rounded-2xl p-6 md:p-10 text-white mb-8 relative overflow-hidden">
        <div class="relative z-10 max-w-2xl">
            <h1 class="font-bold text-2xl md:text-4xl mb-2">Jelajah Wisata Desa</h1>
            <p class="text-emerald-100 text-sm md:text-base">Temukan surga tersembunyi, budaya lokal, dan pengalaman tak terlupakan.</p>
        </div>
        <i class="fas fa-map-marked-alt text-[150px] absolute -right-10 -bottom-10 text-white opacity-10 rotate-12"></i>
    </div>

    <!-- Filter Categories -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="bg-emerald-600 text-white px-5 py-2 rounded-full text-sm font-medium shadow-md shadow-emerald-200">Semua</button>
        <button class="bg-white text-gray-600 border border-gray-200 px-5 py-2 rounded-full text-sm font-medium hover:bg-gray-50 hover:border-emerald-200 transition">Wisata Alam</button>
        <button class="bg-white text-gray-600 border border-gray-200 px-5 py-2 rounded-full text-sm font-medium hover:bg-gray-50 hover:border-emerald-200 transition">Budaya & Seni</button>
        <button class="bg-white text-gray-600 border border-gray-200 px-5 py-2 rounded-full text-sm font-medium hover:bg-gray-50 hover:border-emerald-200 transition">Edukasi</button>
    </div>
</div>

<div class="pb-10">
    <?php if ( have_posts() ) : ?>
        <!-- Responsive Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ( have_posts() ) : the_post(); 
                $harga = get_post_meta(get_the_ID(), 'harga_tiket', true) ?: 0;
                $lokasi = get_post_meta(get_the_ID(), 'lokasi', true) ?: 'Desa Wisata';
                $rating = 4.8;
                $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'large') : 'https://via.placeholder.com/600x400?text=No+Image';
            ?>
                <!-- Wisata Card -->
                <a href="<?php the_permalink(); ?>" class="block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-xl transition duration-300">
                    <div class="h-48 md:h-56 bg-gray-200 relative overflow-hidden">
                        <img src="<?php echo esc_url($image_url); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-lg text-xs font-bold flex items-center gap-1 shadow-sm">
                            <i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?>
                        </div>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-gray-800 text-lg md:text-xl leading-tight mb-2 group-hover:text-emerald-600 transition"><?php the_title(); ?></h3>
                        <p class="text-sm text-gray-500 mb-4 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-red-500"></i> <?php echo esc_html($lokasi); ?>
                        </p>
                        <div class="flex justify-between items-center border-t border-dashed border-gray-100 pt-4">
                            <div>
                                <p class="text-xs text-gray-400 mb-0.5">Mulai dari</p>
                                <p class="text-emerald-600 font-bold text-lg">Rp <?php echo number_format($harga, 0, ',', '.'); ?></p>
                            </div>
                            <span class="bg-gray-50 text-gray-600 px-4 py-2 rounded-lg text-sm font-medium group-hover:bg-emerald-600 group-hover:text-white transition">Lihat Detail</span>
                        </div>
                    </div>
                </a>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <div class="flex justify-center mt-10">
            <?php
            the_posts_pagination(array(
                'prev_text' => '<span class="w-10 h-10 flex items-center justify-center border rounded-full hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition"><i class="fas fa-chevron-left"></i></span>',
                'next_text' => '<span class="w-10 h-10 flex items-center justify-center border rounded-full hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition"><i class="fas fa-chevron-right"></i></span>',
                'class' => 'flex gap-2'
            ));
            ?>
        </div>

    <?php else : ?>
        <div class="text-center py-20 bg-gray-50 rounded-xl">
            <i class="fas fa-map-signs text-5xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-bold text-gray-600">Belum ada wisata ditemukan</h3>
            <p class="text-gray-400">Silakan kembali lagi nanti.</p>
        </div>
    <?php endif; ?>
</div>

<?php get_footer(); ?>