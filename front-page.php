<?php
/**
 * The template for displaying the front page
 *
 * @package DesaWisataTheme
 */

get_header();

// --- LOGIKA BANNER VIA REST API ---
// Menggunakan API endpoint internal plugin: /dw-api/v1/public/banners
// Ini lebih aman daripada query database langsung.

$banners = [];
$api_url = get_rest_url(null, 'dw-api/v1/public/banners');

// Lakukan request ke API
$response = wp_remote_get($api_url, array(
    'timeout' => 10, // Timeout 10 detik
    'sslverify' => false // Set true jika di production dengan SSL valid
));

// Cek apakah request berhasil
if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    // Cek format response API (biasanya { success: true, data: [...] } atau langsung array)
    if (isset($data['data']) && is_array($data['data'])) {
        $banners = $data['data'];
    } elseif (is_array($data)) {
        $banners = $data;
    }
}
?>

<!-- HERO SECTION / BANNER -->
<section id="hero-slider" class="hero-section">
    <?php if (!empty($banners)) : ?>
        <!-- TAMPILAN DINAMIS (DATA DARI API) -->
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
                <?php foreach ($banners as $index => $banner) : 
                    // Pastikan menghandle key array dari API (biasanya 'image_url', 'title', dll)
                    $image_url = isset($banner['image_url']) ? $banner['image_url'] : '';
                    $title = isset($banner['title']) ? $banner['title'] : '';
                    $description = isset($banner['description']) ? $banner['description'] : '';
                    $link = isset($banner['link']) ? $banner['link'] : '';
                    
                    if (empty($image_url)) continue; // Skip jika tidak ada gambar
                ?>
                    <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                        <!-- Menggunakan object-fit cover agar gambar tidak gepeng/hancur -->
                        <img src="<?php echo esc_url($image_url); ?>" 
                             class="d-block w-100" 
                             style="height: 500px; object-fit: cover;" 
                             alt="<?php echo esc_attr($title); ?>">
                        
                        <?php if (!empty($title) || !empty($description)) : ?>
                        <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3">
                            <?php if (!empty($title)) : ?>
                                <h3 class="fw-bold"><?php echo esc_html($title); ?></h3>
                            <?php endif; ?>
                            
                            <?php if (!empty($description)) : ?>
                                <p><?php echo esc_html($description); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($link)) : ?>
                                <a href="<?php echo esc_url($link); ?>" class="btn btn-primary btn-sm mt-2">Lihat Detail</a>
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
        
        <!-- TAMPILAN FALLBACK (DEFAULT) -->
        <div id="carouselDefault" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <!-- Placeholder image jika API gagal atau data kosong -->
                    <img src="<?php echo get_template_directory_uri(); ?>/assets/img/hero-default.jpg" 
                         class="d-block w-100" 
                         style="height: 500px; object-fit: cover;"
                         alt="Selamat Datang"
                         onerror="this.src='https://via.placeholder.com/1200x500?text=Desa+Wisata';">
                    <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3">
                        <h3>Selamat Datang di Desa Wisata</h3>
                        <p>Jelajahi keindahan alam dan budaya desa kami.</p>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</section>

<!-- SECTION LAINNYA DI BAWAH BANNER -->
<main class="container py-5">
    
    <!-- Kategori / Fitur Utama -->
    <div class="row text-center mb-5">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm p-3 h-100 hover-shadow">
                <div class="card-body">
                    <div class="display-4 mb-3">🏞️</div>
                    <h3>Paket Wisata</h3>
                    <p class="text-muted">Temukan destinasi wisata menarik di desa kami.</p>
                    <a href="<?php echo site_url('/wisata'); ?>" class="btn btn-outline-success stretched-link">Lihat Wisata</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm p-3 h-100 hover-shadow">
                 <div class="card-body">
                    <div class="display-4 mb-3">🛍️</div>
                    <h3>Produk UMKM</h3>
                    <p class="text-muted">Belanja produk lokal asli buatan warga desa.</p>
                    <a href="<?php echo site_url('/produk'); ?>" class="btn btn-outline-primary stretched-link">Lihat Produk</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm p-3 h-100 hover-shadow">
                 <div class="card-body">
                    <div class="display-4 mb-3">ℹ️</div>
                    <h3>Informasi Desa</h3>
                    <p class="text-muted">Berita dan informasi terbaru seputar desa.</p>
                    <a href="<?php echo site_url('/desa'); ?>" class="btn btn-outline-info stretched-link">Tentang Desa</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Produk Terbaru -->
    <section class="latest-products mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Produk Terbaru</h2>
            <a href="<?php echo site_url('/produk'); ?>" class="btn btn-link text-decoration-none">Lihat Semua &rarr;</a>
        </div>
        
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
            ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 product-card border-0 shadow-sm">
                        <div class="position-relative overflow-hidden" style="height: 200px;">
                            <?php if (has_post_thumbnail()) : ?>
                                <img src="<?php the_post_thumbnail_url('medium'); ?>" class="card-img-top w-100 h-100" style="object-fit: cover;" alt="<?php the_title(); ?>">
                            <?php else : ?>
                                <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img-top w-100 h-100" style="object-fit: cover;" alt="No Image">
                            <?php endif; ?>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-truncate" title="<?php the_title(); ?>"><?php the_title(); ?></h5>
                            <!-- Placeholder harga, nanti bisa diganti dengan meta data real -->
                            <p class="card-text text-primary fw-bold mb-auto">Rp <?php echo number_format(get_post_meta(get_the_ID(), '_price', true) ?: 0, 0, ',', '.'); ?></p>
                            <a href="<?php the_permalink(); ?>" class="btn btn-primary btn-sm mt-3 stretched-link">Lihat Detail</a>
                        </div>
                    </div>
                </div>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
                echo '<div class="col-12"><p class="text-center text-muted">Belum ada produk yang ditampilkan.</p></div>';
            endif;
            ?>
        </div>
    </section>

</main>

<?php
get_footer();