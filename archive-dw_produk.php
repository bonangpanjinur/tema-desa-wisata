<?php get_header(); ?>

<!-- Container utama dengan padding agar rapi di mobile & desktop -->
<div style="padding: 10px 20px; max-width: 1200px; margin: 0 auto;">
    
    <!-- Bagian Header Arsip -->
    <div class="section-header" style="padding: 10px 0 20px 0;">
        <div class="section-title">
            <h3>Jelajahi <span>Produk Lokal</span></h3>
        </div>
    </div>

    <!-- Filter Kategori (Scroll Horizontal) -->
    <!-- Menu navigasi diperbaiki agar lebih terarah -->
    <div class="explore-filters">
        <!-- Filter "Semua" mengarah ke archive produk tanpa parameter -->
        <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="filter-pill <?php echo !isset($_GET['kategori']) ? 'active' : ''; ?>">Semua</a>
        
        <!-- Filter Kategori Dinamis dari Taxonomy 'kategori_produk' -->
        <?php
        $terms = get_terms( array(
            'taxonomy' => 'kategori_produk',
            'hide_empty' => true,
        ) );

        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $is_active = ( isset($_GET['kategori']) && $_GET['kategori'] == $term->slug ) ? 'active' : '';
                // Link mengarah ke archive dengan parameter kategori
                $term_link = add_query_arg( 'kategori', $term->slug, get_post_type_archive_link('dw_produk') );
                echo '<a href="' . esc_url( $term_link ) . '" class="filter-pill ' . esc_attr( $is_active ) . '">' . esc_html( $term->name ) . '</a>';
            }
        }
        ?>
    </div>
</div>

<!-- Grid Produk -->
<div class="grid-product" style="margin-top: 10px; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
    <?php 
    // Modifikasi query jika ada parameter kategori
    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
    $args = array(
        'post_type' => 'dw_produk',
        'posts_per_page' => 10,
        'paged' => $paged,
        'post_status' => 'publish'
    );

    // Cek parameter URL 'kategori'
    if ( isset( $_GET['kategori'] ) && !empty( $_GET['kategori'] ) ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'kategori_produk',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $_GET['kategori'] ),
            ),
        );
    }

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) : 
        while ( $query->have_posts() ) : $query->the_post(); 
            $harga = get_post_meta( get_the_ID(), '_dw_harga_dasar', true );
            $author_id = get_post_field( 'post_author', get_the_ID() );
            $toko = get_the_author_meta( 'display_name', $author_id );
            
            // Cek apakah ada label halal/asli (contoh meta, bisa disesuaikan)
            $is_halal = get_post_meta( get_the_ID(), '_dw_is_halal', true );
    ?>
    <div class="card-product">
        <a href="<?php the_permalink(); ?>">
            <div class="prod-img-wrap">
                <?php if ( $is_halal ) : ?>
                    <span class="badge-halal">Halal</span>
                <?php endif; ?>
                
                <?php if ( has_post_thumbnail() ) { 
                    the_post_thumbnail('medium'); 
                } else { 
                    echo '<img src="https://via.placeholder.com/200x200?text=Produk" alt="Placeholder" />'; 
                } ?>
            </div>
            <div class="prod-title"><?php the_title(); ?></div>
            <div class="prod-shop"><i class="fas fa-store-alt"></i> <?php echo esc_html($toko); ?></div>
            <div class="prod-footer">
                <div class="prod-price">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></div>
                <div class="btn-add"><i class="fas fa-plus"></i></div>
            </div>
        </a>
    </div>
    <?php endwhile; 
        wp_reset_postdata();
    else : ?>
        <div style="grid-column: 1 / -1; text-align: center; padding: 40px 0;">
            <img src="https://via.placeholder.com/150?text=Kosong" alt="Kosong" style="margin: 0 auto 15px; opacity: 0.5; width: 100px;">
            <p style="color: #777;">Produk tidak ditemukan untuk kategori ini.</p>
            <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="btn-visit" style="margin-top: 10px; display: inline-block;">Lihat Semua Produk</a>
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

<style>
    /* Styling Pagination Sederhana agar sesuai tema */
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