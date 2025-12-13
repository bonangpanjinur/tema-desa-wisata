<?php get_header(); ?>

<div style="padding: 10px 20px; max-width: 1200px; margin: 0 auto;">
    <div class="section-header" style="padding: 10px 0 15px 0;">
        <div class="section-title">
            <h3>Jelajahi <span>Wisata Alam</span></h3>
        </div>
    </div>

    <!-- Filter Wisata -->
    <div class="explore-filters">
        <a href="<?php echo get_post_type_archive_link('dw_wisata'); ?>" class="filter-pill <?php echo !isset($_GET['kategori']) ? 'active' : ''; ?>">Semua</a>
        <?php
        $terms = get_terms( array( 'taxonomy' => 'kategori_wisata', 'hide_empty' => true ) );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $is_active = ( isset($_GET['kategori']) && $_GET['kategori'] == $term->slug ) ? 'active' : '';
                $term_link = add_query_arg( 'kategori', $term->slug, get_post_type_archive_link('dw_wisata') );
                echo '<a href="' . esc_url( $term_link ) . '" class="filter-pill ' . esc_attr( $is_active ) . '">' . esc_html( $term->name ) . '</a>';
            }
        }
        ?>
    </div>
    
    <!-- List Wisata (Vertical) -->
    <div style="margin-top: 20px;">
    <?php 
    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
    $args = array(
        'post_type' => 'dw_wisata',
        'posts_per_page' => 10,
        'paged' => $paged,
        'post_status' => 'publish'
    );
    if ( isset( $_GET['kategori'] ) && !empty( $_GET['kategori'] ) ) {
        $args['tax_query'] = array( array(
            'taxonomy' => 'kategori_wisata',
            'field'    => 'slug',
            'terms'    => sanitize_text_field( $_GET['kategori'] ),
        ) );
    }
    $query = new WP_Query( $args );

    if ( $query->have_posts() ) : 
        while ( $query->have_posts() ) : $query->the_post(); 
            $harga = get_post_meta( get_the_ID(), '_dw_harga_tiket', true );
            $lokasi = get_post_meta( get_the_ID(), '_dw_kabupaten', true );
    ?>
    <div class="card-wisata-full">
        <a href="<?php the_permalink(); ?>">
            <?php if ( has_post_thumbnail() ) { the_post_thumbnail('large'); } else { echo '<img src="https://via.placeholder.com/600x300?text=Wisata" />'; } ?>
            <div class="card-wisata-content">
                <div class="card-wisata-title"><?php the_title(); ?></div>
                <div class="card-wisata-meta">
                    <span><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($lokasi); ?></span>
                    <span><i class="fas fa-ticket-alt"></i> <?php echo number_format((float)$harga, 0, ',', '.'); ?></span>
                </div>
                <div class="btn-track" style="display:inline-block; border-radius:8px;">Lihat Detail</div>
            </div>
        </a>
    </div>
    <?php endwhile; wp_reset_postdata(); else : ?>
        <p style="text-align:center; color:#777; padding:20px;">Wisata belum tersedia.</p>
    <?php endif; ?>
    </div>

    <!-- Pagination -->
    <div style="padding: 20px 0; text-align: center;">
        <?php echo paginate_links( array(
            'total' => $query->max_num_pages,
            'current' => $paged,
            'prev_text' => '<i class="fas fa-chevron-left"></i>',
            'next_text' => '<i class="fas fa-chevron-right"></i>',
            'type' => 'list'
        ) ); ?>
    </div>
</div>

<?php get_footer(); ?>