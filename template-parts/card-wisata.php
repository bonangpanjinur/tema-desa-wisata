<?php
/**
 * Template Part: Card Wisata
 * Digunakan dalam loop arsip wisata, hasil pencarian, atau widget wisata.
 * Fitur:
 * - Menampilkan thumbnail dengan rasio seragam.
 * - Badge harga tiket (Gratis/Bayar).
 * - Informasi Lokasi.
 * - Rating Bintang.
 */

// 1. Ambil ID Post saat ini
$id_wisata   = get_the_ID();

// 2. Ambil Meta Data Wisata
// Pastikan key meta sesuai dengan yang tersimpan di database
$harga_tiket = get_post_meta($id_wisata, 'harga_tiket', true);
$lokasi      = get_post_meta($id_wisata, 'lokasi_wisata', true);
$rating      = get_post_meta($id_wisata, 'rating_wisata', true); // Opsional
?>

<div class="col-md-6 col-lg-4 mb-4">
    <div class="card h-100 border-0 shadow-sm card-wisata-hover overflow-hidden">
        
        <!-- BAGIAN 1: GAMBAR & BADGE HARGA -->
        <div class="position-relative" style="height: 220px;">
            <a href="<?php the_permalink(); ?>" class="d-block h-100">
                <?php if ( has_post_thumbnail() ) : ?>
                    <!-- Gambar Unggulan -->
                    <img src="<?php the_post_thumbnail_url('large'); ?>" 
                         class="w-100 h-100" 
                         style="object-fit: cover; transition: transform 0.3s ease;" 
                         alt="<?php the_title_attribute(); ?>">
                <?php else : ?>
                    <!-- Placeholder jika tidak ada gambar -->
                    <div class="w-100 h-100 bg-secondary d-flex align-items-center justify-content-center text-white">
                        <i class="bi bi-camera fs-1"></i>
                    </div>
                <?php endif; ?>
            </a>
            
            <!-- Label Harga di pojok kanan bawah gambar -->
            <div class="position-absolute bottom-0 end-0 bg-white px-3 py-1 m-2 rounded shadow-sm">
                <?php if ($harga_tiket && (int)$harga_tiket > 0) : ?>
                    <span class="text-success fw-bold small">
                        Rp <?php echo number_format((float)$harga_tiket, 0, ',', '.'); ?>
                    </span>
                <?php else : ?>
                    <span class="text-success fw-bold small">Gratis</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- BAGIAN 2: INFORMASI WISATA -->
        <div class="card-body">
            <!-- Lokasi -->
            <?php if ($lokasi) : ?>
                <div class="mb-2 text-muted small text-truncate">
                    <i class="bi bi-geo-alt-fill text-danger"></i> <?php echo esc_html($lokasi); ?>
                </div>
            <?php endif; ?>

            <!-- Judul -->
            <h5 class="card-title mb-2">
                <a href="<?php the_permalink(); ?>" class="text-decoration-none text-dark stretched-link">
                    <?php the_title(); ?>
                </a>
            </h5>
            
            <!-- Deskripsi Singkat (Excerpt) -->
            <p class="card-text text-muted small">
                <?php 
                // Batasi panjang deskripsi agar rapi
                echo wp_trim_words(get_the_excerpt(), 12, '...'); 
                ?>
            </p>
        </div>

        <!-- BAGIAN 3: FOOTER CARD (RATING & TOMBOL) -->
        <div class="card-footer bg-transparent border-top-0 d-flex justify-content-between align-items-center pb-3 pt-0">
            
            <!-- Rating Bintang -->
            <div class="text-warning small d-flex align-items-center" style="z-index: 2;">
                <?php 
                $current_rating = $rating ? (int)$rating : 0;
                // Menampilkan 5 bintang
                for($i=1; $i<=5; $i++) {
                    if ($i <= $current_rating) {
                        echo '<i class="bi bi-star-fill"></i>';
                    } else {
                        echo '<i class="bi bi-star"></i>'; // Bintang kosong
                    }
                    echo ' '; // spasi antar bintang
                }
                ?>
                <span class="text-muted ms-1" style="font-size: 0.8rem;">(<?php echo $current_rating; ?>/5)</span>
            </div>

            <!-- Tombol Kunjungi (Opsional, karena stretched-link sudah membuat seluruh card bisa diklik) -->
            <!-- Kita beri z-index agar tetap bisa diklik terpisah jika perlu -->
            <a href="<?php the_permalink(); ?>" class="btn btn-sm btn-primary px-3 rounded-pill position-relative" style="z-index: 2;">
                Kunjungi <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>

    </div>
</div>