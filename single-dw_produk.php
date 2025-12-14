<?php
/**
 * Template untuk menampilkan detail produk (CPT: dw_produk)
 */
get_header(); 

// Ambil data dari plugin meta
$product_id = get_the_ID();
$price = dw_get_product_price($product_id);
$stock = get_post_meta($product_id, 'dw_stok', true);
$lokasi = get_post_meta($product_id, 'dw_lokasi', true); // Asumsi key
$pedagang_id = get_post_field('post_author', $product_id);
$nama_pedagang = get_the_author_meta('display_name', $pedagang_id);
?>

<div class="dw-single-product-section section-padding">
    <div class="container">
        <?php while (have_posts()) : the_post(); ?>
            <div class="row">
                <!-- Kolom Gambar -->
                <div class="col-md-6 mb-4">
                    <div class="product-gallery">
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="main-image mb-3">
                                <?php the_post_thumbnail('large', ['class' => 'img-fluid rounded shadow-sm']); ?>
                            </div>
                        <?php else : ?>
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/placeholder.jpg" class="img-fluid rounded" alt="No Image">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Kolom Detail -->
                <div class="col-md-6">
                    <div class="product-details">
                        <span class="badge bg-secondary mb-2">Produk Desa</span>
                        <h1 class="product-title mb-2"><?php the_title(); ?></h1>
                        
                        <div class="product-meta text-muted mb-3">
                            <small><i class="fas fa-store"></i> <?php echo esc_html($nama_pedagang); ?></small> | 
                            <small><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($lokasi ? $lokasi : 'Desa Wisata'); ?></small>
                        </div>

                        <h2 class="product-price text-primary mb-3">
                            <?php echo dw_format_rupiah($price); ?>
                        </h2>

                        <div class="product-description mb-4">
                            <?php the_content(); ?>
                        </div>

                        <div class="product-actions border-top pt-4">
                            <form class="add-to-cart-form" id="form-add-to-cart">
                                <div class="row g-2 align-items-center">
                                    <div class="col-auto">
                                        <label for="quantity" class="visually-hidden">Jumlah</label>
                                        <input type="number" id="quantity" name="quantity" class="form-control" value="1" min="1" max="<?php echo esc_attr($stock); ?>">
                                    </div>
                                    <div class="col">
                                        <!-- Tombol ini mentrigger assets/js/ajax-cart.js -->
                                        <button type="button" class="btn btn-primary w-100 btn-add-to-cart" 
                                            data-product-id="<?php echo get_the_ID(); ?>"
                                            data-product-name="<?php the_title(); ?>"
                                            data-product-price="<?php echo esc_attr($price); ?>"
                                            data-product-image="<?php echo get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'); ?>">
                                            <i class="fas fa-shopping-cart me-2"></i> Tambah Keranjang
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <div class="mt-2">
                                <small class="text-muted">Stok tersedia: <?php echo esc_html($stock ? $stock : 'Tak Terbatas'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<?php get_footer(); ?>