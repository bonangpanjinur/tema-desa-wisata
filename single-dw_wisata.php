<?php get_header(); ?>

<div class="container single-product-container py-5">
    <?php while ( have_posts() ) : the_post(); 
        $wisata_id = get_the_ID();
        $harga_tiket = get_post_meta( $wisata_id, '_harga_tiket', true );
        $lokasi = get_post_meta( $wisata_id, '_lokasi_map', true ); // Asumsi menyimpan nama lokasi/koordinat
        $fasilitas = get_post_meta( $wisata_id, '_fasilitas', true );
        $desa_id = get_post_meta( $wisata_id, '_desa_id', true );
    ?>

    <div class="row">
        <!-- Gallery Section -->
        <div class="col-md-6">
            <div class="product-gallery">
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="main-image">
                        <?php the_post_thumbnail( 'large', ['class' => 'img-fluid rounded shadow'] ); ?>
                    </div>
                <?php endif; ?>
                <!-- Tambahkan logika galeri tambahan jika ada metabox galeri -->
            </div>
        </div>

        <!-- Info Section -->
        <div class="col-md-6">
            <div class="product-info">
                <span class="badge badge-success mb-2">Wisata Desa</span>
                <h1 class="product-title"><?php the_title(); ?></h1>
                
                <?php if($desa_id): ?>
                    <p class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo get_the_title($desa_id); ?></p>
                <?php endif; ?>

                <div class="price-box my-3">
                    <h3 class="text-primary"><?php echo dw_format_price($harga_tiket); ?> <small class="text-muted">/ orang</small></h3>
                </div>

                <div class="description mb-4">
                    <?php the_content(); ?>
                </div>

                <?php if ( !empty($fasilitas) ) : ?>
                <div class="fasilitas mb-4">
                    <h5>Fasilitas:</h5>
                    <ul>
                        <?php 
                        // Asumsi fasilitas disimpan sebagai array atau string comma-separated
                        if(is_array($fasilitas)) {
                            foreach($fasilitas as $f) echo "<li>$f</li>";
                        } else {
                            echo "<li>$fasilitas</li>";
                        }
                        ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Booking Form -->
                <div class="booking-card card p-3 bg-light border-0">
                    <h5 class="card-title">Pesan Tiket</h5>
                    <form id="add-to-cart-wisata" class="add-to-cart-form">
                        <input type="hidden" name="product_id" value="<?php echo $wisata_id; ?>">
                        <input type="hidden" name="type" value="wisata">
                        
                        <div class="form-group mb-3">
                            <label>Tanggal Kunjungan</label>
                            <input type="date" name="tanggal_kunjungan" class="form-control" required>
                        </div>

                        <div class="form-group mb-3">
                            <label>Jumlah Tiket</label>
                            <div class="quantity-control d-flex">
                                <button type="button" class="btn btn-outline-secondary minus">-</button>
                                <input type="number" name="quantity" value="1" min="1" class="form-control text-center mx-2" style="width: 80px;">
                                <button type="button" class="btn btn-outline-secondary plus">+</button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block w-100 btn-lg">
                            <i class="fas fa-ticket-alt"></i> Pesan Tiket Sekarang
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
    
    <!-- Related / Lokasi Map -->
    <div class="row mt-5">
        <div class="col-12">
            <h3>Lokasi</h3>
            <div class="map-container p-3 bg-white shadow-sm rounded">
                <!-- Integrasi Google Maps atau Text Lokasi -->
                <p><?php echo $lokasi ? esc_html($lokasi) : 'Lokasi belum disematkan.'; ?></p>
            </div>
        </div>
    </div>

    <?php endwhile; ?>
</div>

<?php get_footer(); ?>