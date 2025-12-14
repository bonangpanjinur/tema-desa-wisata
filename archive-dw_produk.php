<?php
/**
 * Template Name: Arsip Produk
 */
get_header(); 
?>

<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4">
        <div class="text-center mb-10">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Produk UMKM Desa</h1>
            <p class="text-gray-500">Dukung ekonomi lokal dengan membeli produk asli desa</p>
        </div>

        <!-- Grid Produk -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = array(
                'post_type' => 'dw_produk',
                'posts_per_page' => 12,
                'paged' => $paged,
                'post_status' => 'publish'
            );
            $query = new WP_Query($args);

            if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
                // Meta Plugin
                $harga = get_post_meta(get_the_ID(), 'dw_harga_produk', true);
                $lokasi = get_post_meta(get_the_ID(), 'dw_lokasi_produk', true);
                $img = get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: 'https://via.placeholder.com/400';
            ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition group flex flex-col">
                <a href="<?php the_permalink(); ?>" class="block relative aspect-square bg-gray-100 overflow-hidden">
                    <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                </a>
                <div class="p-4 flex flex-col flex-1">
                    <div class="text-xs text-gray-400 mb-1 flex items-center gap-1">
                        <i class="fas fa-map-marker-alt"></i> <?php echo esc_html($lokasi ?: 'Desa Wisata'); ?>
                    </div>
                    <h3 class="font-bold text-gray-800 text-sm md:text-base line-clamp-2 mb-2 flex-1">
                        <a href="<?php the_permalink(); ?>" class="hover:text-primary transition"><?php the_title(); ?></a>
                    </h3>
                    <div class="flex justify-between items-end mt-auto">
                        <span class="font-bold text-primary text-lg"><?php echo dw_format_rupiah($harga); ?></span>
                        <button class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-primary hover:text-white transition shadow-sm add-to-cart-btn" data-product-id="<?php the_ID(); ?>">
                            <i class="fas fa-cart-plus text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
            
            <?php else: ?>
                <div class="col-span-full text-center py-20 text-gray-400">Belum ada produk.</div>
            <?php endif; wp_reset_postdata(); ?>
        </div>

        <!-- Pagination -->
        <div class="mt-10 flex justify-center">
            <?php
            echo paginate_links(array(
                'total' => $query->max_num_pages,
                'prev_text' => '<i class="fas fa-chevron-left"></i>',
                'next_text' => '<i class="fas fa-chevron-right"></i>',
            ));
            ?>
        </div>
    </div>
</div>

<style>
/* CSS Pagination Simple */
.page-numbers { display: flex; gap: 5px; }
.page-numbers a, .page-numbers span { display: block; padding: 8px 14px; background: white; border: 1px solid #eee; border-radius: 8px; text-decoration: none; color: #555; font-weight: bold; }
.page-numbers span.current, .page-numbers a:hover { background: var(--primary); color: white; border-color: var(--primary); }
</style>

<?php get_footer(); ?>