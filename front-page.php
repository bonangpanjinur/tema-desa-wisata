<?php
/**
 * Template Name: Front Page
 */

get_header(); ?>

<!-- Hero / Banner Section -->
<section class="dw-hero-section">
    <div id="dwHeroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php
            // Mengambil Banner dari Database Plugin (Custom Table)
            global $wpdb;
            $table_banner = $wpdb->prefix . 'dw_banners'; // Asumsi nama tabel
            
            // Cek apakah tabel ada untuk mencegah error
            $banners = [];
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_banner'") == $table_banner) {
                // Ambil banner yang statusnya active
                $banners = $wpdb->get_results("SELECT * FROM $table_banner WHERE status = 'active' ORDER BY id DESC LIMIT 5");
            }

            if (!empty($banners)) {
                foreach ($banners as $index => $banner) {
                    $active_class = ($index === 0) ? 'active' : '';
                    ?>
                    <div class="carousel-item <?php echo $active_class; ?>">
                        <!-- Pastikan image_url valid, jika tidak gunakan placeholder -->
                        <?php $img_src = !empty($banner->image_url) ? esc_url($banner->image_url) : get_template_directory_uri() . '/assets/img/hero-placeholder.jpg'; ?>
                        
                        <img src="<?php echo $img_src; ?>" class="d-block w-100" alt="<?php echo esc_attr($banner->title); ?>" style="height: 550px; object-fit: cover; filter: brightness(0.8);">
                        
                        <div class="carousel-caption d-none d-md-block" style="bottom: 20%;">
                            <div class="container">
                                <h2 class="display-4 fw-bold text-shadow"><?php echo esc_html($banner->title); ?></h2>
                                <p class="lead fs-4 text-shadow"><?php echo esc_html($banner->description); ?></p>
                                <?php if (!empty($banner->link)) : ?>
                                    <a href="<?php echo esc_url($banner->link); ?>" class="btn btn-primary btn-lg mt-3 px-5 rounded-pill shadow">Lihat Selengkapnya</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                // Fallback jika tidak ada data banner
                ?>
                <div class="carousel-item active">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/hero-placeholder.jpg" class="d-block w-100" alt="Selamat Datang" style="height: 550px; object-fit: cover; filter: brightness(0.8);">
                    <div class="carousel-caption d-none d-md-block" style="bottom: 20%;">
                        <div class="container">
                            <h2 class="display-4 fw-bold text-shadow">Selamat Datang di Desa Wisata</h2>
                            <p class="lead fs-4 text-shadow">Jelajahi keindahan alam dan kearifan lokal yang otentik.</p>
                            <a href="<?php echo home_url('/wisata'); ?>" class="btn btn-primary btn-lg mt-3 px-5 rounded-pill shadow">Mulai Menjelajah</a>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        
        <?php if (count($banners) > 1) : ?>
        <button class="carousel-control-prev" type="button" data-bs-target="#dwHeroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#dwHeroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
        <?php endif; ?>
    </div>
</section>

<!-- Wisata Section -->
<section class="dw-wisata-section section-padding py-5">
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8 text-center">
                <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill">Destinasi Pilihan</span>
                <h2 class="fw-bold display-6">Wisata Populer</h2>
                <p class="text-muted">Temukan tempat-tempat menarik yang wajib Anda kunjungi di desa kami.</p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php
            // Query Custom Post Type: dw_wisata
            $args_wisata = array(
                'post_type' => 'dw_wisata',
                'posts_per_page' => 3, // Tampilkan 3 wisata terbaru
                'orderby' => 'date',
                'order' => 'DESC'
            );
            $query_wisata = new WP_Query($args_wisata);
            
            if ($query_wisata->have_posts()) :
                while ($query_wisata->have_posts()) : $query_wisata->the_post();
                    $lokasi = get_post_meta(get_the_ID(), 'dw_lokasi', true);
                    $harga = get_post_meta(get_the_ID(), 'dw_harga_tiket', true);
                    ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm border-0 hover-lift">
                            <div class="card-img-wrapper overflow-hidden position-relative" style="height: 250px;">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium_large', ['class' => 'w-100 h-100 object-fit-cover transition-scale']); ?>
                                    <?php else : ?>
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/placeholder.jpg" class="w-100 h-100 object-fit-cover transition-scale" alt="<?php the_title(); ?>">
                                    <?php endif; ?>
                                </a>
                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-white text-dark shadow-sm">
                                        <i class="fas fa-tag text-primary me-1"></i> <?php echo $harga ? dw_format_rupiah($harga) : 'Gratis'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-2 text-muted small">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i> 
                                    <?php echo esc_html($lokasi ? $lokasi : 'Desa Wisata'); ?>
                                </div>
                                <h5 class="card-title fw-bold mb-3">
                                    <a href="<?php the_permalink(); ?>" class="text-decoration-none text-dark stretched-link"><?php the_title(); ?></a>
                                </h5>
                                <p class="card-text text-muted small">
                                    <?php echo wp_trim_words(get_the_excerpt(), 12); ?>
                                </p>
                            </div>
                            <div class="card-footer bg-white border-top-0 p-4 pt-0">
                                <a href="<?php the_permalink(); ?>" class="btn btn-outline-primary w-100 rounded-pill">Lihat Detail</a>
                            </div>
                        </div>
                    </div>
                    <?php
                endwhile;
                wp_reset_postdata();
            else :
                echo '<div class="col-12 text-center py-5"><img src="'.get_template_directory_uri().'/assets/img/empty.svg" style="width:100px; opacity:0.5;" class="mb-3"><p class="text-muted">Belum ada data wisata yang ditambahkan.</p></div>';
            endif;
            ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="<?php echo home_url('/wisata'); ?>" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">Jelajahi Semua Wisata <i class="fas fa-arrow-right ms-2"></i></a>
        </div>
    </div>
</section>

<!-- Produk Section -->
<section class="dw-produk-section section-padding py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <span class="badge bg-success bg-opacity-10 text-success mb-2 px-3 py-2 rounded-pill">Oleh-oleh & Kerajinan</span>
                <h2 class="fw-bold display-6">Produk Desa</h2>
            </div>
            <a href="<?php echo home_url('/produk'); ?>" class="btn btn-outline-dark d-none d-md-inline-block rounded-pill">Lihat Semua</a>
        </div>
        
        <div class="row g-4">
            <?php
            // Query Custom Post Type: dw_produk
            $args_produk = array(
                'post_type' => 'dw_produk',
                'posts_per_page' => 4, // Tampilkan 4 produk terbaru
                'orderby' => 'date',
                'order' => 'DESC'
            );
            $query_produk = new WP_Query($args_produk);
            
            if ($query_produk->have_posts()) :
                while ($query_produk->have_posts()) : $query_produk->the_post();
                    $price = dw_get_product_price(get_the_ID());
                    $stok = get_post_meta(get_the_ID(), 'dw_stok', true);
                    ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 shadow-sm border-0 product-card hover-lift">
                            <div class="card-img-top overflow-hidden position-relative" style="height: 220px;">
                                <a href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <?php the_post_thumbnail('medium', ['class' => 'w-100 h-100 object-fit-cover transition-scale']); ?>
                                    <?php else : ?>
                                        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/placeholder.jpg" class="w-100 h-100 object-fit-cover transition-scale" alt="<?php the_title(); ?>">
                                    <?php endif; ?>
                                </a>
                                <?php if ($stok <= 0) : ?>
                                    <span class="badge bg-danger position-absolute top-0 start-0 m-3 shadow-sm">Stok Habis</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <small class="text-muted d-block mb-1">UMKM Desa</small>
                                <h6 class="card-title fw-bold mb-2"><a href="<?php the_permalink(); ?>" class="text-decoration-none text-dark"><?php the_title(); ?></a></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-primary fw-bold fs-5"><?php echo dw_format_rupiah($price); ?></span>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 p-3 pt-0">
                                <button type="button" class="btn btn-success w-100 rounded-pill btn-add-to-cart" 
                                    data-product-id="<?php echo get_the_ID(); ?>"
                                    data-product-name="<?php the_title(); ?>"
                                    data-product-price="<?php echo esc_attr($price); ?>"
                                    data-product-image="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'); ?>"
                                    <?php echo ($stok <= 0) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-cart-plus me-2"></i> <?php echo ($stok <= 0) ? 'Habis' : 'Beli Sekarang'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php
                endwhile;
                wp_reset_postdata();
            else :
                 echo '<div class="col-12 text-center py-5"><p class="text-muted">Belum ada produk yang tersedia.</p></div>';
            endif;
            ?>
        </div>
        
        <div class="text-center mt-4 d-md-none">
            <a href="<?php echo home_url('/produk'); ?>" class="btn btn-outline-dark w-100 rounded-pill">Lihat Semua Produk</a>
        </div>
    </div>
</section>

<!-- Features / Info Section -->
<section class="section-padding py-5">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="p-4 border rounded-3 h-100 bg-white shadow-sm">
                    <i class="fas fa-leaf fa-3x text-success mb-3"></i>
                    <h4>Alam Asri</h4>
                    <p class="text-muted">Nikmati udara segar dan pemandangan hijau yang menenangkan jiwa.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 border rounded-3 h-100 bg-white shadow-sm">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h4>Budaya Lokal</h4>
                    <p class="text-muted">Berinteraksi langsung dengan warga desa yang ramah dan bersahaja.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-4 border rounded-3 h-100 bg-white shadow-sm">
                    <i class="fas fa-shopping-basket fa-3x text-warning mb-3"></i>
                    <h4>Produk Otentik</h4>
                    <p class="text-muted">Dapatkan oleh-oleh khas buatan tangan asli warga desa.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="dw-cta-section py-5 position-relative overflow-hidden text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
    <div class="container position-relative z-index-1 text-center py-4">
        <h2 class="fw-bold mb-3">Siap untuk Berlibur?</h2>
        <p class="lead mb-4 opacity-75">Rencanakan kunjungan Anda sekarang dan buat kenangan indah bersama kami.</p>
        <a href="<?php echo home_url('/kontak'); ?>" class="btn btn-light btn-lg px-5 text-primary fw-bold rounded-pill shadow">Hubungi Kami</a>
    </div>
</section>

<style>
/* CSS Tambahan untuk Front Page */
.text-shadow { text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
.hover-lift { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.hover-lift:hover { transform: translateY(-5px); box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important; }
.transition-scale { transition: transform 0.5s ease; }
.hover-lift:hover .transition-scale { transform: scale(1.05); }
.object-fit-cover { object-fit: cover; }
</style>

<?php get_footer(); ?>