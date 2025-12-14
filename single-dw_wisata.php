<?php
/**
 * Template Name: Single Detail Wisata
 * Post Type: dw_wisata
 */

get_header();

while (have_posts()) : the_post();

    // Ambil Meta Data Plugin
    $lokasi = get_post_meta(get_the_ID(), 'dw_lokasi', true);
    $harga_tiket = get_post_meta(get_the_ID(), 'dw_harga_tiket', true);
    $jam_buka = get_post_meta(get_the_ID(), 'dw_jam_buka', true);
    $kontak = get_post_meta(get_the_ID(), 'dw_kontak', true);
    $fasilitas = get_post_meta(get_the_ID(), 'dw_fasilitas', true); // Asumsi format array atau string dipisah koma
    $gmaps_url = get_post_meta(get_the_ID(), 'dw_gmaps_url', true);
?>

<!-- Hero Image Section -->
<div class="dw-single-hero position-relative" style="height: 400px; overflow: hidden;">
    <?php if (has_post_thumbnail()) : ?>
        <img src="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'full'); ?>" class="w-100 h-100 object-fit-cover" style="object-fit: cover; filter: brightness(0.7);">
    <?php else : ?>
        <div class="bg-secondary w-100 h-100"></div>
    <?php endif; ?>
    
    <div class="position-absolute bottom-0 start-0 w-100 p-4 p-md-5 text-white bg-gradient-dark">
        <div class="container">
            <span class="badge bg-warning text-dark mb-2"><i class="fas fa-map-signs"></i> Destinasi Wisata</span>
            <h1 class="display-5 fw-bold"><?php the_title(); ?></h1>
            <p class="lead mb-0"><i class="fas fa-map-marker-alt me-2"></i> <?php echo esc_html($lokasi ? $lokasi : 'Lokasi belum ditentukan'); ?></p>
        </div>
    </div>
</div>

<div class="section-padding py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm border-0 p-4">
                    <h3 class="mb-4 border-bottom pb-2">Deskripsi</h3>
                    <div class="entry-content text-justify">
                        <?php the_content(); ?>
                    </div>

                    <!-- Fasilitas Section -->
                    <?php if (!empty($fasilitas)) : ?>
                        <div class="mt-4">
                            <h4 class="mb-3"><i class="fas fa-concierge-bell text-primary me-2"></i>Fasilitas</h4>
                            <div class="d-flex flex-wrap gap-2">
                                <?php 
                                // Jika fasilitas disimpan sebagai string dipisah koma
                                $fasilitas_array = is_array($fasilitas) ? $fasilitas : explode(',', $fasilitas);
                                foreach ($fasilitas_array as $item) : 
                                    if(trim($item) == '') continue;
                                ?>
                                    <span class="badge bg-light text-dark border p-2 fw-normal">
                                        <i class="fas fa-check-circle text-success me-1"></i> <?php echo esc_html(trim($item)); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Gallery Placeholder (Jika plugin support gallery) -->
                    <!-- Bisa ditambahkan loop attachment image di sini -->
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 sticky-top" style="top: 100px; z-index: 1;">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Informasi Wisata</h5>
                    </div>
                    <div class="card-body">
                        <!-- Harga Tiket -->
                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted d-block text-uppercase fw-bold ls-1">Harga Tiket Masuk</small>
                            <span class="h4 text-primary fw-bold">
                                <?php echo ($harga_tiket) ? dw_format_rupiah($harga_tiket) : 'Gratis'; ?>
                            </span>
                            <small class="text-muted">/ orang</small>
                        </div>

                        <!-- Jam Buka -->
                        <div class="mb-3 pb-3 border-bottom">
                            <small class="text-muted d-block text-uppercase fw-bold ls-1">Jam Operasional</small>
                            <div class="d-flex align-items-center mt-1">
                                <i class="far fa-clock fa-lg text-secondary me-3" style="width: 20px;"></i>
                                <span class="fs-6"><?php echo esc_html($jam_buka ? $jam_buka : 'Setiap Hari, 08:00 - 17:00'); ?></span>
                            </div>
                        </div>

                        <!-- Lokasi/Alamat -->
                        <div class="mb-4">
                            <small class="text-muted d-block text-uppercase fw-bold ls-1">Lokasi</small>
                            <div class="d-flex mt-1">
                                <i class="fas fa-map-marked-alt fa-lg text-secondary me-3 mt-1" style="width: 20px;"></i>
                                <span class="fs-6"><?php echo esc_html($lokasi ? $lokasi : 'Hubungi pengelola untuk detail lokasi.'); ?></span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <?php if ($kontak) : 
                                // Format nomor WA: hilangkan 0 di depan ganti 62, hilangkan spasi/-
                                $wa_number = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $kontak));
                            ?>
                                <a href="https://wa.me/<?php echo esc_attr($wa_number); ?>?text=Halo, saya ingin bertanya tentang wisata <?php echo urlencode(get_the_title()); ?>" target="_blank" class="btn btn-success">
                                    <i class="fab fa-whatsapp me-2"></i> Hubungi Pengelola
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($gmaps_url) : ?>
                                <a href="<?php echo esc_url($gmaps_url); ?>" target="_blank" class="btn btn-outline-secondary">
                                    <i class="fas fa-directions me-2"></i> Petunjuk Arah
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-dark {
    background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
}
.ls-1 { letter-spacing: 1px; font-size: 0.75rem; }
</style>

<?php endwhile; // End of the loop.
get_footer(); ?>