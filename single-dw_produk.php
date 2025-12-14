<?php
/**
 * Template Name: Detail Produk
 */
get_header(); 

// Data Plugin
$product_id = get_the_ID();
$price = get_post_meta($product_id, 'dw_harga_produk', true);
$stock = get_post_meta($product_id, 'dw_stok_produk', true);
$lokasi = get_post_meta($product_id, 'dw_lokasi_produk', true);

// Get Author/Pedagang Info
$author_id = get_post_field('post_author', $product_id);
$pedagang_data = dw_get_pedagang_data($author_id);
$nama_toko = $pedagang_data ? $pedagang_data->nama_toko : get_the_author_meta('display_name', $author_id);
?>

<div class="bg-white min-h-screen pb-20">
    <div class="container mx-auto px-4 py-8">
        <?php while (have_posts()) : the_post(); ?>
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Gallery -->
                <div class="w-full md:w-1/2">
                    <div class="aspect-square bg-gray-100 rounded-2xl overflow-hidden mb-4 relative">
                        <?php if (has_post_thumbnail()) : ?>
                            <img src="<?php the_post_thumbnail_url('full'); ?>" class="w-full h-full object-cover">
                        <?php else : ?>
                            <img src="https://via.placeholder.com/600" class="w-full h-full object-cover">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info Produk -->
                <div class="w-full md:w-1/2">
                    <div class="mb-4">
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2"><?php the_title(); ?></h1>
                        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                            <span class="flex items-center gap-1"><i class="fas fa-store text-orange-500"></i> <?php echo esc_html($nama_toko); ?></span>
                            <span>&bull;</span>
                            <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt text-red-500"></i> <?php echo esc_html($lokasi); ?></span>
                        </div>
                        <div class="text-3xl font-bold text-primary mb-6"><?php echo dw_format_rupiah($price); ?></div>
                    </div>

                    <!-- Add to Cart Form -->
                    <div class="bg-gray-50 p-6 rounded-2xl border border-gray-100 mb-8">
                        <div class="flex items-center gap-4 mb-4">
                            <label class="font-bold text-gray-700">Jumlah:</label>
                            <div class="flex items-center bg-white border border-gray-300 rounded-lg">
                                <button type="button" onclick="document.getElementById('qty').stepDown()" class="px-3 py-1 text-gray-500 hover:bg-gray-100 rounded-l-lg">-</button>
                                <input type="number" id="qty" value="1" min="1" max="<?php echo esc_attr($stock); ?>" class="w-12 text-center border-none focus:ring-0 p-1 text-gray-800 font-bold">
                                <button type="button" onclick="document.getElementById('qty').stepUp()" class="px-3 py-1 text-gray-500 hover:bg-gray-100 rounded-r-lg">+</button>
                            </div>
                            <span class="text-sm text-gray-500">Stok: <?php echo esc_html($stock ?: 'Tersedia'); ?></span>
                        </div>

                        <button type="button" id="btn-add-to-cart" 
                            data-product-id="<?php echo $product_id; ?>"
                            class="w-full bg-primary hover:bg-green-700 text-white font-bold py-4 rounded-xl shadow-lg transition flex items-center justify-center gap-2">
                            <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                        </button>
                        <div id="cart-message" class="mt-3 text-center text-sm font-medium hidden"></div>
                    </div>

                    <!-- Tabs Deskripsi -->
                    <div class="border-t border-gray-100 pt-6">
                        <h3 class="font-bold text-lg text-gray-800 mb-3">Deskripsi Produk</h3>
                        <div class="prose max-w-none text-gray-600 leading-relaxed">
                            <?php the_content(); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#btn-add-to-cart').click(function(e) {
        e.preventDefault();
        const btn = $(this);
        const qty = $('#qty').val();
        
        btn.prop('disabled', true).addClass('opacity-75').html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

        $.post(dw_ajax.ajax_url, {
            action: 'dw_theme_add_to_cart',
            nonce: dw_ajax.nonce,
            product_id: btn.data('product-id'),
            quantity: qty
        }, function(res) {
            btn.prop('disabled', false).removeClass('opacity-75').html('<i class="fas fa-check"></i> Berhasil!');
            if(res.success) {
                $('#cart-message').removeClass('text-red-500 hidden').addClass('text-green-600').html(res.data.message + ' <a href="'+dw_ajax.site_url+'/cart" class="underline">Lihat Keranjang</a>').fadeIn();
                setTimeout(() => { btn.html('<i class="fas fa-cart-plus"></i> Tambah ke Keranjang'); }, 2000);
            } else {
                $('#cart-message').removeClass('text-green-600 hidden').addClass('text-red-500').text(res.data.message).fadeIn();
            }
        });
    });
});
</script>

<?php get_footer(); ?>