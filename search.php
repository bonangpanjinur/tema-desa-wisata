<?php
/**
 * The template for displaying search results pages
 */

get_header(); 
?>

<div class="bg-gray-100 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        
        <header class="mb-8 text-center">
            <h1 class="text-3xl font-bold text-gray-800">
                <?php
                /* translators: %s: search query. */
                printf( esc_html__( 'Hasil Pencarian: "%s"', 'tema-desa-wisata' ), '<span class="text-blue-600">' . get_search_query() . '</span>' );
                ?>
            </h1>
            <p class="text-gray-500 mt-2">Ditemukan <?php echo $wp_query->found_posts; ?> hasil</p>
        </header>

        <?php if ( have_posts() ) : ?>
            
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php
                while ( have_posts() ) :
                    the_post();

                    // Tentukan tampilan berdasarkan tipe post
                    if ( get_post_type() === 'dw_produk' ) {
                        get_template_part( 'template-parts/card', 'produk' );
                    } elseif ( get_post_type() === 'dw_wisata' ) {
                        get_template_part( 'template-parts/card', 'wisata' );
                    } elseif ( get_post_type() === 'dw_desa' ) {
                        // Jika ada template card desa
                        // get_template_part( 'template-parts/card', 'desa' ); 
                        // Fallback sederhana
                        ?>
                        <div class="bg-white rounded shadow p-4">
                            <h3 class="font-bold"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <span class="text-xs bg-gray-200 px-2 rounded">Desa Wisata</span>
                        </div>
                        <?php
                    } else {
                        // Tampilan default untuk post biasa/halaman
                        ?>
                        <div class="bg-white rounded shadow p-4 flex flex-col h-full">
                            <?php if(has_post_thumbnail()): ?>
                                <img src="<?php the_post_thumbnail_url('medium'); ?>" class="w-full h-32 object-cover rounded mb-3">
                            <?php endif; ?>
                            <h3 class="font-bold text-lg mb-2"><a href="<?php the_permalink(); ?>" class="hover:text-blue-600"><?php the_title(); ?></a></h3>
                            <div class="text-gray-600 text-sm mb-4 flex-grow">
                                <?php the_excerpt(); ?>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="text-blue-500 text-sm font-semibold mt-auto">Baca Selengkapnya &rarr;</a>
                        </div>
                        <?php
                    }

                endwhile;
                ?>
            </div>

            <!-- Pagination -->
            <div class="mt-12 flex justify-center">
                <?php
                the_posts_pagination( array(
                    'mid_size'  => 2,
                    'prev_text' => __( '&larr; Sebelumnya', 'tema-desa-wisata' ),
                    'next_text' => __( 'Selanjutnya &rarr;', 'tema-desa-wisata' ),
                    'class'     => 'flex gap-2' // Styling pagination via CSS
                ) );
                ?>
            </div>

        <?php else : ?>

            <div class="text-center bg-white p-12 rounded-lg shadow max-w-2xl mx-auto">
                <i class="fas fa-search text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Tidak ditemukan</h2>
                <p class="text-gray-600 mb-6">Maaf, tidak ada yang cocok dengan kata kunci pencarian Anda. Silakan coba kata kunci lain.</p>
                
                <div class="max-w-md mx-auto">
                    <?php get_search_form(); ?>
                </div>
            </div>

        <?php endif; ?>
        
    </div>
</div>

<?php
get_footer();