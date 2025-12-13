<?php get_header(); ?>

<main id="main-content">
    
    <!-- 1. Carousel Banner (Integration with Plugin) -->
    <?php 
    $banners = dw_theme_get_banners(); // Fungsi dari functions.php
    if ( ! empty( $banners ) ) : 
    ?>
    <div class="container">
        <div class="hero-carousel">
            <div class="carousel-track">
                <?php foreach ( $banners as $banner ) : ?>
                    <div class="carousel-slide">
                        <a href="<?php echo esc_url( $banner->link ? $banner->link : '#' ); ?>">
                            <!-- Fallback image jika gambar kosong -->
                            <img src="<?php echo esc_url( $banner->gambar ? $banner->gambar : 'https://via.placeholder.com/1200x400?text=Desa+Wisata' ); ?>" alt="<?php echo esc_attr( $banner->judul ); ?>">
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 2. Kategori Cepat (Mobile Only Style) -->
    <div class="container">
        <!-- Anda bisa menambahkan icon kategori di sini nanti -->
    </div>

    <!-- 3. Section: Destinasi Wisata Unggulan -->
    <div class="container">
        <h2 class="section-title">Destinasi Wisata</h2>
        <div class="grid-container">
            <?php
            $args_wisata = array(
                'post_type' => 'dw_wisata',
                'posts_per_page' => 4, // Tampilkan 4 di home
                'post_status' => 'publish'
            );
            $query_wisata = new WP_Query( $args_wisata );
            
            if ( $query_wisata->have_posts() ) :
                while ( $query_wisata->have_posts() ) : $query_wisata->the_post();
                    $harga_tiket = get_post_meta( get_the_ID(), '_dw_harga_tiket', true );
                    $lokasi = get_post_meta( get_the_ID(), '_dw_kabupaten', true );
            ?>
                <div class="card">
                    <a href="<?php the_permalink(); ?>">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'medium', array( 'class' => 'card-img' ) ); ?>
                        <?php else : ?>
                            <img src="https://via.placeholder.com/300x200?text=No+Image" class="card-img" alt="<?php the_title(); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title"><?php the_title(); ?></h3>
                            <div class="card-price">Rp <?php echo number_format((float)$harga_tiket, 0, ',', '.'); ?></div>
                            <div class="card-location"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($lokasi ? $lokasi : 'Indonesia'); ?></div>
                        </div>
                    </a>
                </div>
            <?php 
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p>Belum ada data wisata.</p>';
            endif;
            ?>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="<?php echo site_url('/wisata'); ?>" class="btn-more">Lihat Semua Wisata</a>
        </div>
    </div>

    <!-- 4. Section: Produk Desa -->
    <div class="container" style="margin-bottom: 40px;">
        <h2 class="section-title">Produk Lokal</h2>
        <div class="grid-container">
            <?php
            $args_produk = array(
                'post_type' => 'dw_produk',
                'posts_per_page' => 8,
                'post_status' => 'publish'
            );
            $query_produk = new WP_Query( $args_produk );
            
            if ( $query_produk->have_posts() ) :
                while ( $query_produk->have_posts() ) : $query_produk->the_post();
                    $harga = get_post_meta( get_the_ID(), '_dw_harga_dasar', true );
                    // Ambil nama toko (author)
                    $author_id = get_post_field( 'post_author', get_the_ID() );
                    $nama_toko = get_the_author_meta( 'display_name', $author_id );
            ?>
                <div class="card">
                    <a href="<?php the_permalink(); ?>">
                        <?php if ( has_post_thumbnail() ) : ?>
                            <?php the_post_thumbnail( 'medium', array( 'class' => 'card-img' ) ); ?>
                        <?php else : ?>
                            <img src="https://via.placeholder.com/300x200?text=Produk" class="card-img" alt="<?php the_title(); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h3 class="card-title"><?php the_title(); ?></h3>
                            <div class="card-price">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></div>
                            <div class="card-location"><i class="fas fa-store"></i> <?php echo esc_html($nama_toko); ?></div>
                        </div>
                    </a>
                </div>
            <?php 
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p>Belum ada produk.</p>';
            endif;
            ?>
        </div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="<?php echo site_url('/produk'); ?>" class="btn-more">Lihat Semua Produk</a>
        </div>
    </div>

</main>

<!-- Simple JS for Carousel (Inline for now, better in main.js) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.carousel-track');
    if(track) {
        let index = 0;
        const slides = document.querySelectorAll('.carousel-slide');
        setInterval(() => {
            index++;
            if (index >= slides.length) index = 0;
            track.style.transform = `translateX(-${index * 100}%)`;
        }, 3000); // Ganti slide setiap 3 detik
    }
});
</script>

<?php get_footer(); ?>