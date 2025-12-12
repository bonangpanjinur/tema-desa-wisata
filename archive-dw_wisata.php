<?php
/**
 * Template Name: Archive Wisata Desa
 * Description: Menampilkan daftar destinasi wisata.
 */

get_header(); ?>

<div class="bg-gray-50 py-10 min-h-screen">
    <div class="container mx-auto px-4">
        
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Destinasi Wisata Desa</h1>
            <p class="text-gray-500 max-w-2xl mx-auto">Jelajahi keindahan alam, budaya, dan kearifan lokal di desa-desa wisata pilihan kami.</p>
        </div>

        <!-- Layout Utama -->
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Sidebar Filter -->
            <aside class="w-full lg:w-1/4">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 sticky top-24">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-lg text-gray-800">Filter Wisata</h3>
                        <a href="<?php echo get_post_type_archive_link('dw_wisata'); ?>" class="text-xs text-primary hover:underline">Reset</a>
                    </div>
                    
                    <div class="mb-8">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">Kategori Wisata</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <?php
                            $terms = get_terms( array(
                                'taxonomy'   => 'kategori_wisata',
                                'hide_empty' => true,
                            ) );
                            
                            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                                foreach ( $terms as $term ) {
                                    $is_active = ( get_query_var( 'kategori_wisata' ) == $term->slug );
                                    $active_class = $is_active ? 'text-primary font-bold' : 'hover:text-primary';
                                    
                                    echo '<li>';
                                    echo '<a href="' . esc_url( get_term_link( $term ) ) . '" class="flex items-center group ' . $active_class . '">';
                                    echo '<i class="fas fa-map-signs w-5 text-gray-400"></i>';
                                    echo esc_html( $term->name ) . ' <span class="ml-auto text-xs text-gray-400">(' . $term->count . ')</span>';
                                    echo '</a>';
                                    echo '</li>';
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </aside>

            <!-- Grid Wisata -->
            <main class="w-full lg:w-3/4">
                <?php if ( have_posts() ) : ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <?php while ( have_posts() ) : the_post(); 
                            $post_id = get_the_ID();
                            $lokasi = get_post_meta($post_id, '_dw_alamat', true);
                            $harga_tiket = get_post_meta($post_id, '_dw_harga_tiket', true);
                            $thumb_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'large') : 'https://via.placeholder.com/600x400?text=Wisata';
                        ?>
                            <!-- Card Wisata Horizontal -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition duration-300 group flex flex-col">
                                <div class="relative h-56 overflow-hidden">
                                    <a href="<?php the_permalink(); ?>">
                                        <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition duration-700">
                                    </a>
                                    <div class="absolute top-4 left-4">
                                        <span class="bg-white/90 backdrop-blur text-xs font-bold px-3 py-1 rounded-full text-primary shadow-sm">
                                            <?php echo get_the_term_list($post_id, 'kategori_wisata', '', ', ', ''); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="p-6 flex-1 flex flex-col">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="text-xl font-bold text-gray-800 leading-tight group-hover:text-primary transition">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h3>
                                    </div>
                                    
                                    <?php if($lokasi): ?>
                                    <div class="text-sm text-gray-500 mb-4 flex items-start gap-2">
                                        <i class="fas fa-map-marker-alt mt-1 text-red-400"></i>
                                        <span class="line-clamp-1"><?php echo esc_html($lokasi); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <p class="text-gray-600 text-sm line-clamp-2 mb-6 flex-1">
                                        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
                                    </p>
                                    
                                    <div class="pt-4 border-t border-gray-50 flex items-center justify-between">
                                        <div class="flex flex-col">
                                            <span class="text-xs text-gray-400">Tiket Masuk</span>
                                            <span class="font-bold text-primary text-lg">
                                                <?php echo $harga_tiket ? esc_html($harga_tiket) : 'Gratis'; ?>
                                            </span>
                                        </div>
                                        <a href="<?php the_permalink(); ?>" class="bg-primary/10 text-primary px-4 py-2 rounded-lg text-sm font-bold hover:bg-primary hover:text-white transition">
                                            Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <div class="mt-12 flex justify-center">
                        <?php
                        the_posts_pagination( array(
                            'mid_size'  => 2,
                            'prev_text' => 'Prev',
                            'next_text' => 'Next',
                        ) );
                        ?>
                    </div>

                <?php else : ?>
                    <div class="text-center py-20">
                        <h3 class="text-xl font-bold text-gray-400">Belum ada data wisata.</h3>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<?php get_footer(); ?>