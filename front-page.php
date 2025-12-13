<?php
/**
 * The template for displaying the front page
 *
 * @package DesaWisataTheme
 */

get_header();

// --- LOGIKA BANNER DINAMIS ---
global $wpdb;
$table_banner = $wpdb->prefix . 'dw_banners';
$banners = [];

// Cek apakah tabel banner dari plugin Desa Wisata Core ada
if ($wpdb->get_var("SHOW TABLES LIKE '$table_banner'") === $table_banner) {
    // Ambil banner yang statusnya aktif (is_active = 1), urutkan dari yang terbaru
    $banners = $wpdb->get_results("SELECT * FROM $table_banner WHERE is_active = 1 ORDER BY id DESC");
}
?>

<!-- HERO SECTION / BANNER -->
<section id="hero-slider" class="hero-section">
    <?php if (!empty($banners)) : ?>
        <!-- TAMPILAN DINAMIS (JIKA ADA DATA DARI PLUGIN) -->
        <div id="carouselDesaWisata" class="carousel slide" data-bs-ride="carousel">
            
            <!-- Indicators / Titik Navigasi -->
            <div class="carousel-indicators">
                <?php foreach ($banners as $index => $banner) : ?>
                    <button type="button" 
                            data-bs-target="#carouselDesaWisata" 
                            data-bs-slide-to="<?php echo esc_attr($index); ?>" 
                            class="<?php echo ($index === 0) ? 'active' : ''; ?>" 
                            aria-current="<?php echo ($index === 0) ? 'true' : 'false'; ?>" 
                            aria-label="Slide <?php echo esc_attr($index + 1); ?>">
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Carousel Items / Gambar Banner -->
            <div class="carousel-inner">
                <?php foreach ($banners as $index => $banner) : ?>
                    <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                        <img src="<?php echo esc_url($banner->image_url); ?>" class="d-block w-100 hero-img" alt="<?php echo esc_attr($banner->title); ?>">
                        
                        <!-- Caption (Opsional, jika ada judul/deskripsi) -->
                        <?php if (!empty($banner->title) || !empty($banner->description)) : ?>
                        <div class="carousel-caption d-none d-md-block">
                            <?php if (!empty($banner->title)) : ?>
                                <h5><?php echo esc_html($banner->title); ?></h5>
                            <?php endif; ?>
                            
                            <?php if (!empty($banner->link)) : ?>
                                <a href="<?php echo esc_url($banner->link); ?>" class="btn btn-primary btn-sm mt-2">Lihat Detail</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Tombol Navigasi Kiri/Kanan -->
            <button class="carousel-control-prev" type="button" data-bs-target="#carouselDesaWisata" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#carouselDesaWisata" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>

    <?php else : ?>
        
        <!-- TAMPILAN FALLBACK (JIKA TIDAK ADA DATA BANNER) -->
        <!-- Ini akan tampil jika Anda belum mengupload banner di Plugin Desa Wisata -->
        <div id="carouselDefault" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/hero-default.jpg" class="d-block w-100 hero-img" alt="Selamat Datang di Desa Wisata">
                    <div class="carousel-caption d-none d-md-block">
                        <h5>Selamat Datang di Desa Wisata</h5>
                        <p>Jelajahi keindahan alam dan budaya desa kami.</p>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</section>

<!-- SECTION LAINNYA DI BAWAH BANNER (Example Content) -->
<main class="container py-5">
    
    <!-- Kategori / Fitur Utama -->
    <div class="row text-center mb-5">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <h3>🏞️ Paket Wisata</h3>
                <p>Temukan destinasi wisata menarik di desa kami.</p>
                <a href="<?php echo site_url('/wisata'); ?>" class="btn btn-outline-success">Lihat Wisata</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <h3>🛍️ Produk UMKM</h3>
                <p>Belanja produk lokal asli buatan warga desa.</p>
                <a href="<?php echo site_url('/produk'); ?>" class="btn btn-outline-primary">Lihat Produk</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-3">
                <h3>ℹ️ Informasi Desa</h3>
                <p>Berita dan informasi terbaru seputar desa.</p>
                <a href="<?php echo site_url('/desa'); ?>" class="btn btn-outline-info">Tentang Desa</a>
            </div>
        </div>
    </div>

    <!-- Section Produk Terbaru (Contoh Query WP) -->
    <section class="latest-products mb-5">
        <h2 class="text-center mb-4">Produk Terbaru</h2>
        <div class="row">
            <?php
            $args_produk = array(
                'post_type'      => 'dw_produk',
                'posts_per_page' => 4,
                'orderby'        => 'date',
                'order'          => 'DESC'
            );
            $query_produk = new WP_Query($args_produk);

            if ($query_produk->have_posts()) :
                while ($query_produk->have_posts()) : $query_produk->the_post();
                    // Ambil harga dari custom table atau post meta (sesuaikan dengan plugin)
                    // Disini kita pakai standard WP dulu untuk display
            ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 product-card">
                        <?php if (has_post_thumbnail()) : ?>
                            <img src="<?php the_post_thumbnail_url('medium'); ?>" class="card-img-top" alt="<?php the_title(); ?>">
                        <?php else : ?>
                            <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img-top" alt="No Image">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php the_title(); ?></h5>
                            <a href="<?php the_permalink(); ?>" class="btn btn-primary btn-sm stretched-link">Lihat Detail</a>
                        </div>
                    </div>
                </div>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p class="text-center">Belum ada produk yang ditampilkan.</p>';
            endif;
            ?>
        </div>
    </section>

</main>

<?php
get_footer();