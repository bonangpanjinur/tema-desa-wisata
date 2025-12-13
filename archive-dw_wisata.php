<?php get_header(); ?>

<!-- Container utama -->
<div style="padding: 10px 20px; max-width: 1200px; margin: 0 auto;">
    
    <!-- Bagian Header Arsip -->
    <div class="section-header" style="padding: 10px 0 20px 0;">
        <div class="section-title">
            <h3>Jelajahi <span>Destinasi Wisata</span></h3>
        </div>
    </div>

    <!-- Filter Kategori Wisata (Scroll Horizontal) -->
    <div class="explore-filters">
        <!-- Filter "Semua" -->
        <a href="<?php echo get_post_type_archive_link('dw_wisata'); ?>" class="filter-pill <?php echo !isset($_GET['kategori']) ? 'active' : ''; ?>">Semua</a>
        
        <!-- Filter Kategori Dinamis -->
        <?php
        $terms = get_terms( array(
            'taxonomy' => 'kategori_wisata',
            'hide_empty' => true,
        ) );

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $is_active = ( isset($_GET['kategori']) && $_GET['kategori'] == $term->slug ) ? 'active' : '';
                $term_link = add_query_arg( 'kategori', $term->slug, get_post_type_archive_link('dw_wisata') );
                echo '<a href="' . esc_url( $term_link ) . '" class="filter-pill ' . esc_attr( $is_active ) . '">' . esc_html( $term->name ) . '</a>';
            }
        }
        ?>
    </div>
</div>

<!-- Grid Wisata -->
<!-- Menggunakan class grid-product agar layout konsisten 2 kolom di mobile -->
<div class="grid-product" style="margin-top: 10px; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <?php 
    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
    $args = array(
        'post_type' => 'dw_wisata',
        'posts_per_page' => 10,
        'paged' => $paged,
        'post_status' => 'publish'
    );

    // Filter Kategori jika ada di URL
    if ( isset( $_GET['kategori'] ) && !empty( $_GET['kategori'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'kategori_wisata',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $_GET['kategori'] ),
            ),
        );
    }

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) : 
        while ( $query->have_posts() ) : $query->the_post(); 
            // Ambil Meta Data Wisata
            $harga_tiket = get_post_meta( get_the_ID(), '_dw_harga_tiket', true );
            $kabupaten = get_post_meta( get_the_ID(), '_dw_kabupaten', true );
            $desa_id = get_post_meta( get_the_ID(), '_dw_id_desa', true );
            
            // Opsional: Ambil nama desa jika perlu
            // $nama_desa = ... (logic ambil nama desa dari DB plugin jika ada helpernya)
    ?>
    <div class="card-product"> <!-- Menggunakan style card yang sama agar rapi -->
        <a href="<?php the_permalink(); ?>">
            <div class="prod-img-wrap">
                <?php if ( has_post_thumbnail() ) { 
                    the_post_thumbnail('medium'); 
                } else { 
                    echo '<img src="https://via.placeholder.com/300x200?text=Wisata" alt="Placeholder" />'; 
                } ?>
            </div>
            
            <div class="prod-title"><?php the_title(); ?></div>
            
            <!-- Lokasi -->
            <div class="prod-shop">
                <i class="fas fa-map-marker-alt"></i> 
                <?php echo esc_html( $kabupaten ? $kabupaten : 'Lokasi tidak tersedia' ); ?>
            </div>
            
            <div class="prod-footer">
                <!-- Harga Tiket -->
                <div class="prod-price" style="color: var(--accent-orange);">
                    <?php echo ($harga_tiket > 0) ? 'Rp ' . number_format((float)$harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                </div>
                <!-- Tombol Detail -->
                <div class="btn-add" style="background: var(--primary-light); color: var(--primary); width: auto; padding: 0 12px; border-radius: 12px; font-size: 0.75rem; height: 24px;">
                    Detail
                </div>
            </div>
        </a>
    </div>
    <?php endwhile; 
        wp_reset_postdata();
    else : ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 40px 0;">
            <img src="https://via.placeholder.com/150?text=Kosong" alt="Kosong" style="margin: 0 auto 15px; opacity: 0.5; width: 100px;">
            <p style="color: #777;">Destinasi wisata belum tersedia untuk kategori ini.</p>
            <a href="<?php echo get_post_type_archive_link('dw_wisata'); ?>" class="btn-visit" style="margin-top: 10px; display: inline-block;">Lihat Semua</a>
        </div>
    <?php endif; ?>
</div>

<!-- Navigasi Halaman -->
<div style="padding: 30px 20px; text-align: center;">
    <?php 
    echo paginate_links( array(
        'total' => $query->max_num_pages,
        'current' => $paged,
        'prev_text' => '<i class="fas fa-chevron-left"></i>',
        'next_text' => '<i class="fas fa-chevron-right"></i>',
        'type' => 'list',
        'end_size' => 1,
        'mid_size' => 1
    ) ); 
    ?>
</div>

<!-- Styling Pagination (Sama dengan Produk) -->
<style>
    ul.page-numbers {
        display: flex;
        justify-content: center;
        gap: 8px;
        padding: 0;
    }
    ul.page-numbers li {
        list-style: none;
    }
    .page-numbers {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: var(--white);
        color: var(--text-dark);
        font-size: 0.9rem;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: 0.2s;
    }
    .page-numbers.current, .page-numbers:hover {
        background: var(--primary);
        color: var(--white);
        transform: translateY(-2px);
    }
    .page-numbers.dots {
        background: transparent;
        box-shadow: none;
        color: var(--text-grey);
    }
</style>

<?php get_footer(); ?>