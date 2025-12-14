<?php
/**
 * Template Name: Arsip Wisata
 */
get_header(); 
?>

<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4">
        <div class="text-center mb-10">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Destinasi Wisata</h1>
            <p class="text-gray-500">Jelajahi keindahan alam dan budaya desa</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php
            $args = array(
                'post_type' => 'dw_wisata',
                'posts_per_page' => 9,
                'post_status' => 'publish'
            );
            $query = new WP_Query($args);

            if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();
                // Meta Plugin
                $tiket = get_post_meta(get_the_ID(), 'dw_harga_tiket', true);
                $lokasi = get_post_meta(get_the_ID(), 'dw_lokasi', true);
                $img = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://via.placeholder.com/600x400';
            ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:-translate-y-1 hover:shadow-lg transition group">
                <div class="relative h-56 overflow-hidden">
                    <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                    <div class="absolute top-4 left-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-gray-700 shadow-sm">
                        <i class="fas fa-ticket-alt text-orange-500 mr-1"></i> 
                        <?php echo $tiket ? dw_format_rupiah($tiket) : 'Gratis'; ?>
                    </div>
                </div>
                <div class="p-6">
                    <div class="text-xs text-gray-400 mb-2 flex items-center gap-1 uppercase tracking-wide font-bold">
                        <i class="fas fa-map-marked-alt text-primary"></i> <?php echo esc_html($lokasi ?: 'Indonesia'); ?>
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 mb-3 group-hover:text-primary transition">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    <p class="text-gray-500 text-sm line-clamp-3 mb-4">
                        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                    </p>
                    <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-2 text-primary font-bold text-sm hover:underline">
                        Lihat Selengkapnya <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
            <?php endwhile; endif; wp_reset_postdata(); ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>