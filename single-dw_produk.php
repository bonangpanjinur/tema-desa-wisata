<?php
/**
 * Template Name: Detail Produk (Single)
 * Post Type: dw_produk
 */

get_header(); 

// Ambil ID Produk saat ini
$product_id = get_the_ID();

// --- INTEGRASI DATA DARI PLUGIN ---
// Mengambil meta data yang disimpan oleh plugin 'desa-wisata-core/includes/meta-boxes.php'
// Pastikan key meta ('_dw_harga', '_dw_stok') sesuai dengan yang ada di file meta-boxes.php plugin Anda.
$harga_produk = get_post_meta($product_id, '_dw_harga', true) ?: 0;
$stok_produk  = get_post_meta($product_id, '_dw_stok', true) ?: 0;
$terjual      = get_post_meta($product_id, '_dw_terjual', true) ?: 0;
$lokasi       = get_post_meta($product_id, '_dw_lokasi_desa', true);
$penjual_id   = get_post_field('post_author', $product_id);
$nama_penjual = get_the_author_meta('display_name', $penjual_id);
?>

<div class="dw-container py-10">
    <div class="dw-breadcrumb mb-6">
        <a href="<?php echo home_url(); ?>" class="text-gray-500 hover:text-primary">Beranda</a> / 
        <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="text-gray-500 hover:text-primary">Produk</a> / 
        <span class="text-gray-800 font-semibold"><?php the_title(); ?></span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-10 bg-white p-6 rounded-lg shadow-sm">
        <!-- Kolom Kiri: Gambar -->
        <div class="product-gallery">
            <?php if ( has_post_thumbnail() ) : ?>
                <div class="main-image rounded-lg overflow-hidden shadow-md mb-4">
                    <?php the_post_thumbnail('large', ['class' => 'w-full h-auto object-cover']); ?>
                </div>
            <?php else : ?>
                <div class="bg-gray-200 w-full h-64 flex items-center justify-center rounded-lg text-gray-400">
                    No Image
                </div>
            <?php endif; ?>
        </div>

        <!-- Kolom Kanan: Detail & Form Beli -->
        <div class="product-info">
            <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php the_title(); ?></h1>
            
            <div class="flex items-center text-sm text-gray-500 mb-4">
                <span class="mr-4"><i class="fas fa-store mr-1"></i> <?php echo esc_html($nama_penjual); ?></span>
                <span class="mr-4"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo esc_html($lokasi); ?></span>
                <span><i class="fas fa-box mr-1"></i> Terjual: <?php echo esc_html($terjual); ?></span>
            </div>

            <div class="price text-2xl font-bold text-primary mb-6">
                <?php echo dw_format_rupiah($harga_produk); ?>
            </div>

            <div class="description prose mb-8 text-gray-600">
                <?php the_content(); ?>
            </div>

            <!-- FORM INTEGRASI KE CART PLUGIN -->
            <div class="action-area border-t pt-6">
                <?php if($stok_produk > 0): ?>
                    <form id="form-add-to-cart" class="flex gap-4 items-end">
                        
                        <!-- Input Hidden untuk ID Produk (Wajib untuk Plugin) -->
                        <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>">
                        
                        <!-- Input Hidden untuk Tipe (Produk vs Wisata) -->
                        <input type="hidden" name="type" value="produk">

                        <div class="form-group">
                            <label for="qty" class="block text-sm font-medium text-gray-700 mb-1">Jumlah</label>
                            <input type="number" id="qty" name="quantity" value="1" min="1" max="<?php echo esc_attr($stok_produk); ?>" class="w-20 border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary">
                        </div>

                        <div class="flex-grow">
                            <span class="block text-sm text-gray-500 mb-1">Stok: <?php echo esc_html($stok_produk); ?></span>
                            <button type="submit" class="dw-btn-add-cart w-full bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition duration-200 flex justify-center items-center gap-2">
                                <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                            </button>
                        </div>
                    </form>
                    <div id="cart-message" class="mt-3 text-sm hidden"></div>
                <?php else: ?>
                    <div class="bg-red-100 text-red-700 p-3 rounded-md font-medium text-center">
                        Stok Habis
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>