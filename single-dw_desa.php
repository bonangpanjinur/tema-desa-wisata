<?php get_header(); ?>

<!-- Hero Banner Desa -->
<div class="desa-hero" style="background: #f8f9fa; padding: 60px 0; text-align: center;">
    <div class="container">
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="desa-logo mb-3">
                <?php the_post_thumbnail('thumbnail', ['class' => 'rounded-circle shadow', 'style' => 'width: 150px; height: 150px; object-fit: cover;']); ?>
            </div>
        <?php endif; ?>
        <h1 class="display-4"><?php the_title(); ?></h1>
        <p class="lead"><?php echo get_post_meta(get_the_ID(), '_alamat_desa', true); ?></p>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar Info -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Tentang Desa</h5>
                    <div class="card-text">
                        <?php the_content(); ?>
                    </div>
                    <hr>
                    <div class="contact-info">
                        <p><strong>Kontak:</strong> <?php echo get_post_meta(get_the_ID(), '_kontak_desa', true); ?></p>
                        <p><strong>Kepala Desa:</strong> <?php echo get_post_meta(get_the_ID(), '_kepala_desa', true); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content: Wisata & Produk -->
        <div class="col-lg-8">
            
            <!-- List Wisata di Desa Ini -->
            <h3 class="section-title mb-4">Destinasi Wisata</h3>
            <div class="row mb-5">
                <?php
                $desa_id = get_the_ID();
                $wisata_query = new WP_Query(array(
                    'post_type' => 'dw_wisata',
                    'posts_per_page' => 4,
                    'meta_key' => '_desa_id',
                    'meta_value' => $desa_id
                ));

                if ( $wisata_query->have_posts() ) :
                    while ( $wisata_query->have_posts() ) : $wisata_query->the_post();
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-sm product-card">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if(has_post_thumbnail()): ?>
                                        <?php the_post_thumbnail('medium', ['class' => 'card-img-top', 'style' => 'height: 200px; object-fit: cover;']); ?>
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">No Image</div>
                                    <?php endif; ?>
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title"><a href="<?php the_permalink(); ?>" class="text-dark"><?php the_title(); ?></a></h5>
                                    <p class="text-primary font-weight-bold"><?php echo dw_format_price(get_post_meta(get_the_ID(), '_harga_tiket', true)); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    echo '<p class="col-12 text-muted">Belum ada data wisata.</p>';
                endif;
                ?>
            </div>

            <!-- List Produk di Desa Ini -->
            <h3 class="section-title mb-4">Produk UMKM Desa</h3>
            <div class="row">
                <?php
                $produk_query = new WP_Query(array(
                    'post_type' => 'dw_produk',
                    'posts_per_page' => 4,
                    'meta_key' => '_desa_id',
                    'meta_value' => $desa_id
                ));

                if ( $produk_query->have_posts() ) :
                    while ( $produk_query->have_posts() ) : $produk_query->the_post();
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-sm product-card">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if(has_post_thumbnail()): ?>
                                        <?php the_post_thumbnail('medium', ['class' => 'card-img-top', 'style' => 'height: 200px; object-fit: cover;']); ?>
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">No Image</div>
                                    <?php endif; ?>
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title"><a href="<?php the_permalink(); ?>" class="text-dark"><?php the_title(); ?></a></h5>
                                    <p class="text-primary font-weight-bold"><?php echo dw_format_price(get_post_meta(get_the_ID(), '_harga_produk', true)); ?></p>
                                    <a href="<?php the_permalink(); ?>" class="btn btn-sm btn-outline-primary mt-2">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                    echo '<p class="col-12 text-muted">Belum ada produk UMKM.</p>';
                endif;
                ?>
            </div>

        </div>
    </div>
</div>

<?php get_footer(); ?>