<?php
/**
 * Template Name: Halaman Keranjang (Cart)
 */

get_header(); 
$cart_items = dw_get_cart_items(); // Fungsi dari functions.php
$total_belanja = dw_get_cart_total();
?>

<div class="dw-cart-section section-padding py-10 min-h-screen bg-gray-50">
    <div class="container mx-auto px-4 max-w-4xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Keranjang Belanja</h1>

        <?php if (empty($cart_items)) : ?>
            <div class="empty-cart text-center py-20 bg-white rounded-2xl shadow-sm">
                <div class="mb-4 text-gray-300">
                    <i class="fas fa-shopping-basket text-6xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800">Keranjang Anda Kosong</h3>
                <p class="text-gray-500 mb-6">Ayo jelajahi produk desa wisata kami!</p>
                <a href="<?php echo home_url('/produk'); ?>" class="bg-primary hover:bg-green-700 text-white px-6 py-3 rounded-xl font-bold transition">Belanja Sekarang</a>
            </div>
        <?php else : ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- List Produk -->
                <div class="lg:col-span-2 space-y-4">
                    <?php foreach ($cart_items as $key => $item) : 
                        $subtotal = $item['price'] * $item['quantity'];
                    ?>
                    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex gap-4 items-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                            <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['name']); ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-800 text-sm md:text-base line-clamp-1">
                                <a href="<?php echo get_permalink($item['product_id']); ?>"><?php echo esc_html($item['name']); ?></a>
                            </h3>
                            <p class="text-primary font-bold text-sm mt-1"><?php echo dw_format_rupiah($item['price']); ?></p>
                            
                            <div class="flex items-center justify-between mt-2">
                                <div class="text-xs text-gray-500">Qty: <?php echo $item['quantity']; ?></div>
                                <div class="font-bold text-gray-900 text-sm"><?php echo dw_format_rupiah($subtotal); ?></div>
                            </div>
                        </div>
                        <!-- Tombol Hapus (Optional, butuh implementasi JS/AJAX remove) -->
                        <button class="text-red-400 hover:text-red-600 p-2"><i class="fas fa-trash"></i></button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Ringkasan -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-24">
                        <h5 class="font-bold text-gray-800 mb-4 text-lg">Ringkasan Pesanan</h5>
                        
                        <div class="flex justify-between mb-2 text-gray-600 text-sm">
                            <span>Total Item</span>
                            <span><?php echo count($cart_items); ?></span>
                        </div>
                        
                        <hr class="border-gray-100 my-4">
                        
                        <div class="flex justify-between mb-6">
                            <span class="font-bold text-gray-800">Total Belanja</span>
                            <span class="font-bold text-xl text-primary"><?php echo dw_format_rupiah($total_belanja); ?></span>
                        </div>
                        
                        <div class="flex flex-col gap-3">
                            <a href="<?php echo home_url('/checkout'); ?>" class="bg-primary hover:bg-green-700 text-white py-3 rounded-xl font-bold text-center shadow-lg transition">
                                Lanjut Pembayaran
                            </a>
                            <a href="<?php echo home_url('/produk'); ?>" class="text-gray-500 hover:text-gray-800 text-center text-sm font-medium py-2">
                                Lanjut Belanja
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>