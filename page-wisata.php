<?php
/**
 * Template Name: Halaman Daftar Wisata
 */

get_header(); ?>

<!-- Header Section -->
<div class="dw-page-header bg-light py-5 mb-5">
    <div class="container text-center">
        <h1 class="display-4 fw-bold">Jelajahi Desa Wisata</h1>
        <p class="lead text-muted">Temukan keindahan alam dan budaya yang memukau</p>
        
        <!-- Search Form -->
        <div class="row justify-content-center mt-4">
            <div class="col-md-6">
                <form role="search" method="get" action="<?php echo home_url('/'); ?>" class="d-flex shadow-sm">
                    <input type="hidden" name="post_type" value="dw_wisata">
                    <input type="search" class="form-control form-control-lg border-0" placeholder="Cari destinasi wisata..." value="<?php echo get_search_query(); ?>" name="s">
                    <button class="btn btn-primary px-4" type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="dw-wisata-list section-padding pb-5">
    <div class="container">
        <div class="row g-4">
            <?php
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = array(
                'post_type' => 'dw_wisata',
                'posts_per_page' => 9,
                'paged' => $paged,
                'orderby' => 'date',
                'order' => 'DESC'
            );
            
            // Jika ada pencarian
            if (isset($_GET['s']) && !empty($_GET['s'])) {
                $args['s'] = sanitize_text_field($_GET['s']);
            }

            $wisata_query = new WP_Query($args);

            if ($wisata_query->have_posts()) :
                while ($wisata_query->have_posts()) : $wisata_query->the_post();
                    // Ambil Meta Data
                    $lokasi = get_post_meta(get_the_ID(), 'dw_lokasi', true);
                    $harga_tiket = get_post_meta(get_the_ID(), 'dw_harga_tiket', true);
                    ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0 hover-lift">
                            <div class="card-img-wrapper position-relative overflow-hidden" style="height: 200px;">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium_large', ['class' => 'img-fluid w-100 h-100 object-fit-cover']); ?>
                                    <?php else : ?>
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/placeholder-wisata.jpg" class="img-fluid w-100 h-100 object-fit-cover" alt="<?php the_title(); ?>">
                                    <?php endif; ?>
                                </a>
                                <div class="category-badge position-absolute top-0 start-0 m-3">
                                    <span class="badge bg-success"><i class="fas fa-tree"></i> Wisata</span>
                                </div>
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <div class="mb-2 text-muted small">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i> 
                                    <?php echo esc_html($lokasi ? $lokasi : 'Desa Wisata'); ?>
                                </div>
                                <h5 class="card-title fw-bold mb-3">
                                    <a href="<?php the_permalink(); ?>" class="text-dark text-decoration-none"><?php the_title(); ?></a>
                                </h5>
                                <p class="card-text text-muted small flex-grow-1">
                                    <?php echo wp_trim_words(get_the_excerpt(), 15); ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                    <div>
                                        <small class="text-muted d-block">Harga Tiket</small>
                                        <span class="fw-bold text-primary">
                                            <?php echo ($harga_tiket) ? dw_format_rupiah($harga_tiket) : 'Gratis'; ?>
                                        </span>
                                    </div>
                                    <a href="<?php the_permalink(); ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">Detail</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                
                <!-- Pagination -->
                <div class="col-12 mt-5">
                    <nav aria-label="Page navigation">
                        <?php
                        echo paginate_links(array(
                            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                            'format' => '?paged=%#%',
                            'current' => max(1, get_query_var('paged')),
                            'total' => $wisata_query->max_num_pages,
                            'type' => 'list',
                            'prev_text' => '<i class="fas fa-chevron-left"></i>',
                            'next_text' => '<i class="fas fa-chevron-right"></i>',
                            'mid_size' => 1
                        ));
                        ?>
                    </nav>
                </div>

            <?php else : ?>
                <div class="col-12 text-center py-5">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/empty-state.svg" alt="Tidak ditemukan" style="max-width: 200px;" class="mb-3">
                    <h3>Belum ada destinasi wisata</h3>
                    <p class="text-muted">Coba cari dengan kata kunci lain atau kembali nanti.</p>
                </div>
            <?php endif; wp_reset_postdata(); ?>
        </div>
    </div>
</div>

<style>
/* CSS Tambahan Khusus Halaman Ini (Bisa dipindah ke style.css) */
.hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
.hover-lift:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
.object-fit-cover { object-fit: cover; }
.page-numbers { display: flex; justify-content: center; gap: 5px; list-style: none; padding: 0; }
.page-numbers li a, .page-numbers li span { display: block; padding: 8px 16px; border: 1px solid #dee2e6; border-radius: 4px; color: #0d6efd; text-decoration: none; }
.page-numbers li span.current { background-color: #0d6efd; color: white; border-color: #0d6efd; }
</style>

<?php get_footer(); ?>