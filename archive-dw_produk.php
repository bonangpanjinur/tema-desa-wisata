<?php get_header(); ?>

<!-- Sticky Header & Filters -->
<!-- Menggunakan top-0 karena berada di dalam container scrollable 'main' -->
<div class="glass sticky top-0 z-30 bg-white/90 backdrop-blur-md shadow-sm">
    <div class="px-5 py-3 border-b border-gray-50 flex items-center justify-between">
        <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
            <i class="ph-duotone ph-storefront text-primary text-xl"></i> Pasar Desa
        </h2>
        <!-- Keranjang dihapus sesuai permintaan (sudah ada di header utama) -->
    </div>
    
    <!-- Filter Chips (Horizontal) -->
    <div class="px-5 py-3 flex gap-2 overflow-x-auto no-scrollbar">
        <?php 
        $current_cat = get_query_var('kategori_produk'); 
        $cats = get_terms(['taxonomy' => 'kategori_produk', 'hide_empty' => false]);
        
        // FIX: Fallback aman untuk link arsip
        $archive_link = '#';
        if ( function_exists('get_post_type_archive_link') ) {
            $link = get_post_type_archive_link('dw_produk');
            $archive_link = $link ? $link : site_url('/produk');
        } else {
            $archive_link = site_url('/produk');
        }
        ?>
        
        <a href="<?php echo esc_url($archive_link); ?>" 
           class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all border <?php echo empty($current_cat) ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-500 border-gray-200'; ?>">
           Semua
        </a>

        <?php if (!is_wp_error($cats) && !empty($cats)) : foreach ($cats as $cat) : ?>
            <a href="<?php echo get_term_link($cat); ?>" 
               class="px-4 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition-all border <?php echo ($current_cat == $cat->slug) ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-500 border-gray-200'; ?>">
               <?php echo esc_html($cat->name); ?>
            </a>
        <?php endforeach; endif; ?>
    </div>
</div>

<div class="p-4 bg-gray-50 min-h-screen pb-32">
    <div class="grid grid-cols-2 gap-3">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); 
                $harga = get_post_meta(get_the_ID(), '_dw_harga', true) ?: 0;
                $stok = get_post_meta(get_the_ID(), '_dw_stok', true);
                $is_sold_out = ($stok !== '' && (int)$stok <= 0);
                
                // Placeholder Nama Toko (Nanti ambil dari relasi pedagang)
                $toko = "UMKM Lokal"; 
                
                // Format Price Helper Logic inside template for safety
                $formatted_price = function_exists('dw_format_price') ? dw_format_price($harga) : 'Rp ' . number_format((float)$harga, 0, ',', '.');
            ?>
                <!-- Product Card Modern -->
                <div class="bg-white rounded-2xl p-2 shadow-[0_2px_12px_rgba(0,0,0,0.04)] border border-gray-100 flex flex-col h-full relative overflow-hidden group">
                    
                    <!-- Image Area -->
                    <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden relative mb-2">
                        <?php if (has_post_thumbnail()) : ?>
                            <img src="<?php the_post_thumbnail_url('medium'); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105 <?php echo $is_sold_out ? 'grayscale opacity-70' : ''; ?>">
                        <?php else : ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-300"><i class="ph-duotone ph-image text-4xl"></i></div>
                        <?php endif; ?>
                        
                        <!-- Overlay Badges -->
                        <?php if ($is_sold_out) : ?>
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center">
                                <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded-md">HABIS</span>
                            </div>
                        <?php else: ?>
                            <!-- Wishlist Btn -->
                            <button class="absolute top-2 right-2 w-7 h-7 bg-white/80 backdrop-blur rounded-full flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-white shadow-sm transition-all active:scale-90">
                                <i class="ph-fill ph-heart"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 flex flex-col px-1">
                        <div class="flex items-center gap-1 mb-1 opacity-70">
                            <i class="ph-fill ph-storefront text-primary text-[10px]"></i>
                            <span class="text-[10px] text-gray-500 font-semibold truncate"><?php echo esc_html($toko); ?></span>
                        </div>
                        
                        <h3 class="text-sm font-bold text-gray-800 leading-snug mb-auto line-clamp-2">
                            <a href="<?php the_permalink(); ?>" class="hover:text-primary transition-colors">
                                <?php the_title(); ?>
                            </a>
                        </h3>
                        
                        <div class="mt-3 flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-sm font-extrabold text-gray-900"><?php echo esc_html($formatted_price); ?></span>
                            </div>
                            
                            <?php if (!$is_sold_out) : ?>
                                <!-- Add Button -->
                                <a href="<?php the_permalink(); ?>" class="w-8 h-8 rounded-full bg-gray-900 text-white flex items-center justify-center shadow-lg shadow-gray-900/20 active:scale-90 transition-transform hover:bg-primary">
                                    <i class="ph-bold ph-plus"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="col-span-2 py-20 text-center flex flex-col items-center">
                <div class="w-24 h-24 bg-white rounded-full shadow-sm border border-gray-100 flex items-center justify-center mb-4">
                    <i class="ph-duotone ph-shopping-bag-open text-4xl text-gray-300"></i>
                </div>
                <h3 class="text-base font-bold text-gray-800">Produk Kosong</h3>
                <p class="text-xs text-gray-500 mt-1 max-w-[200px]">Belum ada produk untuk kategori ini.</p>
                <a href="<?php echo esc_url($archive_link); ?>" class="mt-4 text-primary text-xs font-bold hover:underline">Lihat Semua</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Pagination -->
    <div class="mt-8 flex justify-center">
        <?php
        the_posts_pagination(array(
            'prev_text' => '<i class="ph-bold ph-caret-left"></i>',
            'next_text' => '<i class="ph-bold ph-caret-right"></i>',
            'class' => 'flex gap-2' 
        ));
        ?>
    </div>
</div>

<?php get_footer(); ?>