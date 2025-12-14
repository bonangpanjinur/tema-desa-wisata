<?php
/**
 * Template Name: Halaman Depan
 * * Template ini menggunakan desain asli tema Anda.
 * Data statis telah diganti dengan data dinamis dari database.
 */

get_header(); ?>

<!-- BAGIAN HERO / BANNER -->
<section id="hero-slider" class="hero-section">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php
            // 1. LOGIC PENGAMBILAN DATA BANNER
            global $wpdb;
            $table_banner = $wpdb->prefix . 'dw_banners'; 
            
            // Cek apakah tabel banner ada (untuk menghindari error fatal jika plugin tidak aktif)
            $banners = [];
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_banner'") == $table_banner) {
                $banners = $wpdb->get_results("SELECT * FROM $table_banner WHERE status = 'active' ORDER BY id DESC LIMIT 5");
            }

            if (!empty($banners)) {
                foreach ($banners as $index => $banner) {
                    // Item Slider Dinamis
                    $active_class = ($index === 0) ? 'active' : '';
                    $img_src = !empty($banner->image_url) ? esc_url($banner->image_url) : get_template_directory_uri() . '/assets/img/hero-bg.jpg';
                    ?>
                    <div class="carousel-item <?php echo $active_class; ?>">
                        <img src="<?php echo $img_src; ?>" class="d-block w-100 hero-img" alt="<?php echo esc_attr($banner->title); ?>">
                        <div class="carousel-caption d-none d-md-block">
                            <h5 class="hero-title"><?php echo esc_html($banner->title); ?></h5>
                            <p class="hero-desc"><?php echo esc_html($banner->description); ?></p>
                            <?php if (!empty($banner->link)) : ?>
                                <a href="<?php echo esc_url($banner->link); ?>" class="btn btn-primary btn-hero">Lihat Detail</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                // Fallback Tampilan Default (Jika tidak ada banner)
                ?>
                <div class="carousel-item active">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/hero-bg.jpg" class="d-block w-100 hero-img" alt="Desa Wisata">
                    <div class="carousel-caption d-none d-md-block">
                        <h5 class="hero-title">Selamat Datang di Desa Wisata</h5>
                        <p class="hero-desc">Nikmati keindahan alam dan kearifan lokal yang otentik.</p>
                        <a href="<?php echo home_url('/wisata'); ?>" class="btn btn-primary btn-hero">Jelajahi Sekarang</a>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        
        <!-- Kontrol Slider -->
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>

<!-- BAGIAN WISATA POPULER -->
<section id="wisata" class="py-5 bg-light">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="fw-bold">Destinasi Wisata</h2>
            <p class="text-muted">Jelajahi tempat-tempat menarik di desa kami</p>
        </div>

        <div class="row g-4">
            <?php
            // 2. LOGIC PENGAMBILAN DATA WISATA
            $args_wisata = array(
                'post_type'      => 'dw_wisata',
                'posts_per_page' => 3, // Mengambil 3 wisata terbaru
                'orderby'        => 'date',
                'order'          => 'DESC'
            );
            $query_wisata = new WP_Query($args_wisata);

            if ($query_wisata->have_posts()) :
                while ($query_wisata->have_posts()) : $query_wisata->the_post();
                    // Ambil Meta Data (Lokasi & Harga)
                    $lokasi = get_post_meta(get_the_ID(), 'dw_lokasi', true);
                    $harga  = get_post_meta(get_the_ID(), 'dw_harga_tiket', true);
                    ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0 wisata-card">
                            <div class="card-img-top position-relative overflow-hidden">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium_large', ['class' => 'img-fluid w-100', 'style' => 'height: 250px; object-fit: cover;']); ?>
                                    <?php else : ?>
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/placeholder.jpg" class="img-fluid w-100" style="height: 250px; object-fit: cover;" alt="<?php the_title(); ?>">
                                    <?php endif; ?>
                                </a>
                                <div class="badge bg-primary position-absolute top-0 end-0 m-3">
                                    <?php echo ($harga) ? dw_format_rupiah($harga) : 'Gratis'; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title fw-bold">
                                    <a href="<?php the_permalink(); ?>" class="text-dark text-decoration-none"><?php the_title(); ?></a>
                                </h5>
                                <p class="card-text text-muted small mb-2">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i> <?php echo esc_html($lokasi ? $lokasi : 'Desa Wisata'); ?>
                                </p>
                                <p class="card-text">
                                    <?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white border-0 pb-4">
                                <a href="<?php the_permalink(); ?>" class="btn btn-outline-primary w-100">Lihat Detail</a>
                            </div>
                        </div>
                    </div>
                    <?php
                endwhile;
                wp_reset_postdata();
            else :
                // Tampilan jika tidak ada data wisata
                echo '<div class="col-12 text-center"><p class="text-muted">Belum ada data wisata yang ditambahkan.</p></div>';
            endif;
            ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo home_url('/wisata'); ?>" class="btn btn-primary px-4">Lihat Semua Wisata</a>
        </div>
    </div>
</section>

<!-- BAGIAN PRODUK DESA -->
<section id="produk" class="py-5">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="fw-bold">Produk UMKM Desa</h2>
            <p class="text-muted">Oleh-oleh khas dan kerajinan tangan warga</p>
        </div>

        <div class="row g-4">
            <?php
            // 3. LOGIC PENGAMBILAN DATA PRODUK
            $args_produk = array(
                'post_type'      => 'dw_produk',
                'posts_per_page' => 4, // Mengambil 4 produk terbaru
                'orderby'        => 'date',
                'order'          => 'DESC'
            );
            $query_produk = new WP_Query($args_produk);

            if ($query_produk->have_posts()) :
                while ($query_produk->have_posts()) : $query_produk->the_post();
                    // Ambil Meta Data (Harga & Stok)
                    $price = dw_get_product_price(get_the_ID());
                    $stok  = get_post_meta(get_the_ID(), 'dw_stok', true);
                    ?>
                    <div class="col-md-3">
                        <div class="card h-100 shadow-sm border-0 produk-card">
                            <div class="card-img-top position-relative overflow-hidden">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium', ['class' => 'img-fluid w-100', 'style' => 'height: 200px; object-fit: cover;']); ?>
                                    <?php else : ?>
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/placeholder.jpg" class="img-fluid w-100" style="height: 200px; object-fit: cover;" alt="<?php the_title(); ?>">
                                    <?php endif; ?>
                                </a>
                                <?php if ($stok <= 0) : ?>
                                    <span class="badge bg-danger position-absolute top-0 start-0 m-2">Stok Habis</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title fw-bold">
                                    <a href="<?php the_permalink(); ?>" class="text-dark text-decoration-none"><?php the_title(); ?></a>
                                </h6>
                                <p class="card-text text-primary fw-bold mb-2">
                                    <?php echo dw_format_rupiah($price); ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white border-0 pb-3 pt-0">
                                <!-- Tombol Beli dengan Integrasi AJAX Cart -->
                                <button type="button" class="btn btn-sm btn-success w-100 btn-add-to-cart"
                                    data-product-id="<?php echo get_the_ID(); ?>"
                                    data-product-name="<?php the_title(); ?>"
                                    data-product-price="<?php echo esc_attr($price); ?>"
                                    data-product-image="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'); ?>"
                                    <?php echo ($stok <= 0) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-shopping-cart me-1"></i> <?php echo ($stok <= 0) ? 'Habis' : 'Beli'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                endwhile;
                wp_reset_postdata();
            else :
                // Tampilan jika tidak ada data produk
                echo '<div class="col-12 text-center"><p class="text-muted">Belum ada produk yang ditambahkan.</p></div>';
            endif;
            ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?php echo home_url('/produk'); ?>" class="btn btn-outline-primary px-4">Lihat Semua Produk</a>
        </div>
    </div>
</section>

<!-- BAGIAN TENTANG / CTA -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <h2 class="fw-bold mb-3">Tentang Desa Wisata Kami</h2>
        <p class="lead mb-4 mx-auto" style="max-width: 700px;">
            Kami berkomitmen untuk melestarikan budaya dan alam, serta memberdayakan ekonomi masyarakat lokal melalui pariwisata yang berkelanjutan.
        </p>
        <a href="<?php echo home_url('/tentang'); ?>" class="btn btn-light btn-lg px-5 fw-bold text-primary">Pelajari Lebih Lanjut</a>
    </div>
</section>

<?php get_footer(); ?>