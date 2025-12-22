<?php
/**
 * Template Part: Card Produk
 * Digunakan dalam loop arsip produk, widget produk, atau hasil pencarian.
 * * Fitur:
 * - Menampilkan thumbnail dengan rasio seragam.
 * - Badge stok (Habis/Sisa Sedikit).
 * - Format harga Rupiah.
 * - Lokasi Desa/Penjual.
 * - Jumlah Terjual.
 */

// 1. Ambil ID Post saat ini
$id_produk = get_the_ID();

// 2. Ambil Meta Data Produk
// Pastikan key meta sesuai dengan yang tersimpan di database saat input produk
$harga     = get_post_meta($id_produk, 'harga_produk', true);
$stok      = get_post_meta($id_produk, 'stok_produk', true);
$terjual   = get_post_meta($id_produk, 'terjual', true);
$lokasi    = get_post_meta($id_produk, 'lokasi_desa', true); 

// 3. Logika Stok
// Cek jika stok kosong atau 0
$is_out_of_stock = ($stok !== '' && (int)$stok <= 0);
// Cek jika stok menipis (misal di bawah 5)
$is_low_stock    = ($stok !== '' && (int)$stok > 0 && (int)$stok < 5);
?>

<div class="col-6 col-md-4 col-lg-3 mb-4">
    <div class="card h-100 border-0 shadow-sm card-hover-effect">
        
        <!-- BAGIAN 1: GAMBAR & BADGE -->
        <div class="card-img-wrapper position-relative overflow-hidden" style="height: 200px;">
            <a href="<?php the_permalink(); ?>" class="d-block h-100">
                <?php if ( has_post_thumbnail() ) : ?>
                    <!-- Gambar Unggulan -->
                    <img src="<?php the_post_thumbnail_url('medium'); ?>" 
                         class="card-img-top w-100 h-100" 
                         style="object-fit: cover; transition: transform 0.3s ease;" 
                         alt="<?php the_title_attribute(); ?>">
                <?php else : ?>
                    <!-- Placeholder jika tidak ada gambar -->
                    <div class="w-100 h-100 bg-light d-flex align-items-center justify-content-center text-secondary">
                        <i class="bi bi-bag fs-1"></i>
                    </div>
                <?php endif; ?>
            </a>

            <!-- Badge Status Stok -->
            <?php if ($is_out_of_stock) : ?>
                <span class="position-absolute top-0 start-0 badge bg-danger m-2 shadow-sm">Habis</span>
            <?php elseif ($is_low_stock) : ?>
                <span class="position-absolute top-0 start-0 badge bg-warning text-dark m-2 shadow-sm">
                    Sisa <?php echo esc_html($stok); ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- BAGIAN 2: INFORMASI PRODUK -->
        <div class="card-body d-flex flex-column p-3">
            
            <!-- Lokasi / Asal Desa -->
            <?php if ($lokasi) : ?>
                <div class="mb-1">
                    <small class="text-muted text-truncate d-block">
                        <i class="bi bi-geo-alt-fill text-danger small"></i> <?php echo esc_html($lokasi); ?>
                    </small>
                </div>
            <?php endif; ?>

            <!-- Judul Produk -->
            <h6 class="card-title mb-2 lh-base">
                <a href="<?php the_permalink(); ?>" class="text-decoration-none text-dark stretched-link">
                    <?php 
                    // Batasi panjang judul agar tampilan rapi
                    echo wp_trim_words(get_the_title(), 8, '...'); 
                    ?>
                </a>
            </h6>

            <!-- Harga & Terjual (Posisi di bawah/bottom) -->
            <div class="mt-auto pt-2">
                <?php if ($harga) : ?>
                    <h6 class="text-primary fw-bold mb-0">
                        Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?>
                    </h6>
                <?php else : ?>
                    <h6 class="text-success fw-bold mb-0 small">Gratis / Hubungi</h6>
                <?php endif; ?>

                <!-- Info Terjual -->
                <?php if ($terjual && (int)$terjual > 0) : ?>
                    <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                        <i class="bi bi-bag-check"></i> <?php echo esc_html($terjual); ?> Terjual
                    </small>
                <?php endif; ?>
            </div>
        </div>

        <!-- BAGIAN 3: FOOTER CARD (OPSIONAL) -->
        <!-- Tombol ini bisa disembunyikan jika ingin clean look, karena seluruh card sudah bisa diklik (stretched-link) -->
        <div class="card-footer bg-white border-top-0 p-3 pt-0">
            <a href="<?php the_permalink(); ?>" class="btn btn-outline-primary btn-sm w-100 rounded-pill position-relative" style="z-index: 2;">
                Lihat Detail
            </a>
        </div>

    </div>
</div>