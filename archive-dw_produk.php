<?php get_header(); ?>

<div style="padding: 10px 20px;">
    <div class="explore-filters">
        <a href="?kategori=" class="filter-pill <?php echo !isset($_GET['kategori']) ? 'active' : ''; ?>">Semua</a>
        <!-- Kategori Statis (Bisa diganti dinamis get_terms) -->
        <a href="?kategori=makanan" class="filter-pill <?php echo (isset($_GET['kategori']) && $_GET['kategori'] == 'makanan') ? 'active' : ''; ?>">Makanan</a>
        <a href="?kategori=minuman" class="filter-pill">Minuman</a>
        <a href="?kategori=kerajinan" class="filter-pill">Kerajinan</a>
    </div>
</div>

<div class="grid-product" style="margin-top: 10px;">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); 
        $harga = get_post_meta( get_the_ID(), '_dw_harga_dasar', true );
        $author_id = get_post_field( 'post_author', get_the_ID() );
        $toko = get_the_author_meta( 'display_name', $author_id );
    ?>
    <div class="card-product">
        <a href="<?php the_permalink(); ?>">
            <div class="prod-img-wrap">
                <?php if ( has_post_thumbnail() ) { the_post_thumbnail('medium'); } else { echo '<img src="https://via.placeholder.com/200" />'; } ?>
            </div>
            <div class="prod-title"><?php the_title(); ?></div>
            <div class="prod-shop"><i class="fas fa-store-alt"></i> <?php echo esc_html($toko); ?></div>
            <div class="prod-footer">
                <div class="prod-price">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></div>
                <div class="btn-add"><i class="fas fa-plus"></i></div>
            </div>
        </a>
    </div>
    <?php endwhile; else : ?>
        <p style="text-align:center; width:100%; margin-top:20px;">Belum ada produk.</p>
    <?php endif; ?>
</div>

<div style="padding: 20px; text-align: center;">
    <?php 
    the_posts_pagination( array(
        'prev_text' => '<i class="fas fa-chevron-left"></i>',
        'next_text' => '<i class="fas fa-chevron-right"></i>',
    ) ); 
    ?>
</div>

<?php get_footer(); ?>