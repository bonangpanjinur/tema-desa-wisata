<?php
/**
 * Template Name: Detail Produk Sadesa
 */
get_header();

$post_id = get_the_ID();
$api_data = null;

if (function_exists('dw_fetch_api_data')) {
    // API Call By ID
    $api_data = dw_fetch_api_data('/wp-json/dw/v1/produk/' . $post_id);
}

// Fallback
$title = get_the_title();
$price = get_post_meta($post_id, 'harga', true) ?: 0;
$img_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'large') : 'https://via.placeholder.com/600x600';
$content = get_the_content();
$shop_name = 'UMKM Desa';
$stok = 10;

if ($api_data && !isset($api_data['error'])) {
    if (!empty($api_data['nama_produk'])) $title = $api_data['nama_produk'];
    if (isset($api_data['harga_dasar'])) $price = $api_data['harga_dasar'];
    if (!empty($api_data['thumbnail'])) $img_url = $api_data['thumbnail'];
    if (!empty($api_data['deskripsi'])) $content = $api_data['deskripsi'];
    if (!empty($api_data['nama_toko'])) $shop_name = $api_data['nama_toko'];
    if (isset($api_data['stok'])) $stok = $api_data['stok'];
}
?>

<div class="bg-gray-50 min-h-screen pb-24">
    
    <!-- Mobile Header -->
    <div class="md:hidden sticky top-0 z-30 bg-white px-4 py-3 flex items-center justify-between shadow-sm border-b border-gray-100">
        <a href="javascript:history.back()" class="text-gray-600"><i class="fas fa-arrow-left text-lg"></i></a>
        <h1 class="font-bold text-gray-800 text-sm truncate max-w-[200px]"><?php echo esc_html($title); ?></h1>
        <a href="<?php echo home_url('/cart'); ?>" class="text-gray-600"><i class="fas fa-shopping-cart"></i></a>
    </div>

    <div class="container mx-auto max-w-5xl md:py-8 px-0 md:px-4">
        <div class="grid md:grid-cols-2 gap-0 md:gap-8">
            
            <!-- Image Area -->
            <div class="bg-white md:rounded-2xl overflow-hidden shadow-sm border border-gray-100">
                <div class="aspect-square bg-gray-100 relative">
                    <img src="<?php echo esc_url($img_url); ?>" class="w-full h-full object-cover">
                </div>
            </div>

            <!-- Info Area -->
            <div class="p-4 md:p-0 space-y-4">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 leading-snug mb-2"><?php echo esc_html($title); ?></h1>
                    <div class="flex items-end gap-2 mb-4">
                        <p class="text-3xl font-bold text-primary">Rp <?php echo number_format($price, 0, ',', '.'); ?></p>
                    </div>
                    <div class="flex gap-4 border-t border-gray-100 pt-4 text-sm text-gray-500">
                        <div class="flex items-center gap-1 text-yellow-500"><i class="fas fa-star"></i> 5.0</div>
                        <div class="w-px bg-gray-200"></div>
                        <div>Terjual 0</div>
                        <div class="w-px bg-gray-200"></div>
                        <div>Stok: <span class="font-bold text-gray-800"><?php echo $stok; ?></span></div>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500"><i class="fas fa-store"></i></div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-sm"><?php echo esc_html($shop_name); ?></h3>
                            <p class="text-xs text-green-600">Pedagang Terverifikasi</p>
                        </div>
                    </div>
                    <button class="border border-primary text-primary px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-green-50">Kunjungi</button>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 min-h-[150px]">
                    <h3 class="font-bold text-gray-900 mb-2 text-sm uppercase tracking-wide">Deskripsi Produk</h3>
                    <div class="prose prose-sm text-gray-600 leading-relaxed">
                        <?php echo apply_filters('the_content', $content); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sticky Action Bar -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-3 md:p-4 z-40 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
        <div class="container mx-auto max-w-5xl flex items-center justify-between md:justify-end gap-3">
            <div class="hidden md:block mr-auto">
                <p class="text-xs text-gray-500">Total Harga</p>
                <p class="text-xl font-bold text-primary">Rp <?php echo number_format($price, 0, ',', '.'); ?></p>
            </div>
            
            <div class="flex items-center border border-gray-300 rounded-xl h-11 w-28 px-2 bg-white">
                <button onclick="document.getElementById('qty-input').stepDown()" class="w-8 h-full text-gray-500 hover:text-primary font-bold">-</button>
                <input type="number" id="qty-input" value="1" min="1" max="<?php echo $stok; ?>" class="w-full text-center border-none p-0 focus:ring-0 font-bold text-gray-800 h-full">
                <button onclick="document.getElementById('qty-input').stepUp()" class="w-8 h-full text-gray-500 hover:text-primary font-bold">+</button>
            </div>

            <button id="btn-add-to-cart" 
                    data-id="<?php echo $post_id; ?>" 
                    data-title="<?php echo esc_attr($title); ?>" 
                    data-price="<?php echo $price; ?>" 
                    data-thumb="<?php echo esc_url($img_url); ?>"
                    class="bg-white border-2 border-primary text-primary h-11 px-4 rounded-xl font-bold flex items-center gap-2 hover:bg-green-50 transition">
                <i class="fas fa-cart-plus"></i> <span class="hidden sm:inline">Keranjang</span>
            </button>
            
            <button class="bg-primary text-white h-11 px-6 rounded-xl font-bold shadow-lg shadow-green-200 hover:bg-green-700 transition flex-1 md:flex-none">
                Beli Sekarang
            </button>
        </div>
    </div>
</div>

<?php get_footer(); ?>