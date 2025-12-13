<?php get_header(); ?>

    <!-- 1. Hero Banner (Carousel) -->
    <section class="hero-section">
        <div class="banner-card hero-carousel">
            <div class="carousel-track">
                <?php 
                $banners = dw_theme_get_banners();
                if ( ! empty( $banners ) ) : 
                    foreach ( $banners as $banner ) : 
                ?>
                    <div class="carousel-slide">
                        <a href="<?php echo esc_url( $banner->link ? $banner->link : '#' ); ?>">
                            <img src="<?php echo esc_url( $banner->gambar ); ?>" alt="Banner">
                            <div class="banner-text">
                                <h3><?php echo esc_html( $banner->judul ); ?></h3>
                            </div>
                        </a>
                    </div>
                <?php 
                    endforeach;
                else: 
                ?>
                    <!-- Fallback Banner -->
                    <div class="carousel-slide">
                        <img src="https://images.unsplash.com/photo-1533038590840-1cde6e668a91?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80" alt="Banner Default">
                        <div class="banner-text">
                            <h3>Selamat Datang di Desa Wisata</h3>
                            <p>Temukan keindahan alam dan kuliner lokal.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 2. Kategori Menu -->
    <section class="categories">
        <a href="<?php echo get_post_type_archive_link('dw_wisata'); ?>" class="cat-item">
            <div class="cat-icon" style="color: #4CAF50; background: #E8F5E9;"><i class="fas fa-mountain"></i></div>
            <span>Wisata</span>
        </a>
        <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>?kategori=makanan" class="cat-item">
            <div class="cat-icon" style="color: #FF9800; background: #FFF3E0;"><i class="fas fa-utensils"></i></div>
            <span>Kuliner</span>
        </a>
        <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>?kategori=oleh-oleh" class="cat-item">
            <div class="cat-icon" style="color: #2196F3; background: #E3F2FD;"><i class="fas fa-gift"></i></div>
            <span>Oleh-oleh</span>
        </a>
        <a href="<?php echo get_post_type_archive_link('dw_desa'); ?>" class="cat-item">
            <div class="cat-icon" style="color: #9C27B0; background: #F3E5F5;"><i class="fas fa-map-marked-alt"></i></div>
            <span>Jelajah</span>
        </a>
    </section>

    <!-- 3. Section: Wisata Populer (Horizontal Scroll) -->
    <section>
        <div class="section-header">
            <div class="section-title"><h3>Wisata <span>Populer</span></h3></div>
            <a href="<?php echo get_post_type_archive_link('dw_wisata'); ?>" class="see-all">Lihat Semua</a>
        </div>

        <div class="scroll-container">
            <?php
            $args_wisata = array( 'post_type' => 'dw_wisata', 'posts_per_page' => 5 );
            $query_wisata = new WP_Query( $args_wisata );
            if ( $query_wisata->have_posts() ) :
                while ( $query_wisata->have_posts() ) : $query_wisata->the_post();
                    $harga = get_post_meta( get_the_ID(), '_dw_harga_tiket', true );
                    $lokasi = get_post_meta( get_the_ID(), '_dw_kabupaten', true );
            ?>
            <div class="card-wisata">
                <a href="<?php the_permalink(); ?>">
                    <?php if ( has_post_thumbnail() ) { the_post_thumbnail('medium'); } else { echo '<img src="https://via.placeholder.com/300x150" />'; } ?>
                    <div class="card-wisata-body">
                        <h4><?php the_title(); ?></h4>
                        <div class="location-tag"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($lokasi ? $lokasi : 'Indonesia'); ?></div>
                        <div class="price-tag">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></div>
                    </div>
                </a>
            </div>
            <?php endwhile; wp_reset_postdata(); endif; ?>
        </div>
    </section>

    <!-- 4. Section: Produk Terbaru (Grid) -->
    <section style="margin-bottom: 30px;">
        <div class="section-header">
            <div class="section-title"><h3>Produk <span>Lokal</span></h3></div>
            <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="see-all">Lihat Semua</a>
        </div>

        <div class="grid-product">
            <?php
            $args_produk = array( 'post_type' => 'dw_produk', 'posts_per_page' => 6 );
            $query_produk = new WP_Query( $args_produk );
            if ( $query_produk->have_posts() ) :
                while ( $query_produk->have_posts() ) : $query_produk->the_post();
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
                        <!-- Tombol Tambah ke Cart (AJAX nanti) -->
                        <div class="btn-add"><i class="fas fa-plus"></i></div>
                    </div>
                </a>
            </div>
            <?php endwhile; wp_reset_postdata(); endif; ?>
        </div>
    </section>

    <!-- Script Carousel Sederhana -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const track = document.querySelector('.carousel-track');
        if(track) {
            let index = 0;
            const slides = document.querySelectorAll('.carousel-slide');
            if(slides.length > 1) {
                setInterval(() => {
                    index++;
                    if (index >= slides.length) index = 0;
                    track.style.transform = `translateX(-${index * 100}%)`;
                }, 3500);
            }
        }
    });
    </script>

<?php get_footer(); ?>