<?php
/**
 * Template Name: Halaman Keranjang (Cart) Modern
 * Description: Redesigned cart page matching the theme's aesthetic.
 */

get_header(); 

// Pastikan fungsi ini tersedia di functions.php theme Anda
// Jika logic cart berbeda, sesuaikan pemanggilan fungsinya
$cart_items = function_exists('dw_get_cart_items') ? dw_get_cart_items() : []; 
$total_belanja = function_exists('dw_get_cart_total') ? dw_get_cart_total() : 0;
?>

<div class="bg-[#FAFAFA] min-h-screen font-sans text-gray-800 pb-20 relative overflow-x-hidden">
    
    <!-- Background Decor (Konsisten dengan Archive Produk) -->
    <div class="absolute top-0 left-0 w-full h-[400px] bg-gradient-to-b from-orange-50/60 to-transparent -z-10"></div>
    <div class="absolute top-0 right-0 w-[400px] h-[400px] bg-yellow-50/40 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 -z-10"></div>

    <div class="container mx-auto px-4 py-8 md:py-12 max-w-6xl">
        
        <!-- Page Title -->
        <div class="flex items-center gap-3 mb-8">
            <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center text-orange-600 shadow-sm">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Keranjang Belanja</h1>
        </div>

        <?php if (empty($cart_items)) : ?>
            
            <!-- Empty State Modern -->
            <div class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl shadow-sm border border-gray-100 text-center px-4">
                <div class="w-40 h-40 bg-orange-50 rounded-full flex items-center justify-center mb-6 animate-pulse">
                    <i class="fas fa-shopping-basket text-6xl text-orange-200"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Keranjang Anda Kosong</h3>
                <p class="text-gray-500 mb-8 max-w-md">Sepertinya Anda belum menambahkan produk apapun. Yuk, dukung UMKM desa dengan berbelanja sekarang!</p>
                <a href="<?php echo home_url('/produk'); ?>" class="px-8 py-3.5 bg-orange-600 text-white rounded-xl font-bold hover:bg-orange-700 hover:shadow-lg hover:shadow-orange-200 transition-all transform hover:-translate-y-1 flex items-center gap-2">
                    <i class="fas fa-store"></i> Mulai Belanja
                </a>
            </div>

        <?php else : ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                
                <!-- Left Column: Cart Items -->
                <div class="lg:col-span-2 space-y-4">
                    <div class="flex justify-between items-center mb-2 px-1">
                        <span class="text-sm font-semibold text-gray-500"><?php echo count($cart_items); ?> Item di Keranjang</span>
                        <!-- Optional: Clear Cart Button -->
                    </div>

                    <?php foreach ($cart_items as $key => $item) : 
                        $subtotal = $item['price'] * $item['quantity'];
                        // Fallback image logic
                        $img_url = !empty($item['image']) ? $item['image'] : 'https://via.placeholder.com/150x150?text=Produk';
                        if (is_numeric($img_url)) {
                            $img_src = wp_get_attachment_image_src($img_url, 'thumbnail');
                            $img_url = $img_src ? $img_src[0] : 'https://via.placeholder.com/150x150?text=Produk';
                        }
                    ?>
                    <div class="group bg-white p-4 sm:p-5 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 relative flex flex-col sm:flex-row gap-4 sm:items-center">
                        
                        <!-- Image -->
                        <div class="w-full sm:w-24 h-24 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0 relative">
                            <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($item['name']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start mb-1">
                                <h3 class="font-bold text-gray-800 text-base md:text-lg leading-tight line-clamp-2 hover:text-orange-600 transition">
                                    <a href="<?php echo get_permalink($item['product_id']); ?>"><?php echo esc_html($item['name']); ?></a>
                                </h3>
                                <!-- Delete Button (Desktop) -->
                                <button class="hidden sm:flex text-gray-300 hover:text-red-500 transition p-1 delete-cart-item" data-id="<?php echo esc_attr($key); ?>" title="Hapus Item">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                            
                            <p class="text-gray-500 text-xs mb-3 flex items-center gap-1">
                                <i class="fas fa-store text-orange-400"></i>
                                <?php echo isset($item['store_name']) ? esc_html($item['store_name']) : 'Toko Desa'; ?>
                            </p>

                            <div class="flex items-center justify-between flex-wrap gap-3">
                                <div class="font-bold text-orange-600 text-base">
                                    <?php echo function_exists('dw_format_rupiah') ? dw_format_rupiah($item['price']) : 'Rp ' . number_format($item['price'], 0, ',', '.'); ?>
                                </div>

                                <!-- Quantity Control -->
                                <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200">
                                    <button class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-orange-600 hover:bg-gray-100 rounded-l-lg transition qty-btn minus" data-key="<?php echo $key; ?>">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" value="<?php echo esc_attr($item['quantity']); ?>" class="w-10 text-center bg-transparent border-none text-sm font-bold text-gray-700 focus:ring-0 p-0 qty-input" readonly>
                                    <button class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-orange-600 hover:bg-gray-100 rounded-r-lg transition qty-btn plus" data-key="<?php echo $key; ?>">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Button (Mobile) -->
                        <button class="sm:hidden absolute top-4 right-4 text-gray-300 hover:text-red-500 transition delete-cart-item" data-id="<?php echo esc_attr($key); ?>">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <?php endforeach; ?>
                    
                    <a href="<?php echo home_url('/produk'); ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-orange-600 font-medium text-sm mt-4 transition">
                        <i class="fas fa-arrow-left"></i> Lanjut Belanja
                    </a>
                </div>

                <!-- Right Column: Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-2xl shadow-lg shadow-gray-100 border border-gray-100 sticky top-24">
                        <div class="flex items-center gap-2 mb-6">
                            <i class="fas fa-receipt text-orange-500 text-lg"></i>
                            <h2 class="font-bold text-gray-900 text-lg">Ringkasan Pesanan</h2>
                        </div>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-gray-600 text-sm">
                                <span>Total Item</span>
                                <span class="font-medium text-gray-900"><?php echo count($cart_items); ?> Produk</span>
                            </div>
                            <div class="flex justify-between text-gray-600 text-sm">
                                <span>Subtotal</span>
                                <span class="font-medium text-gray-900">
                                    <?php echo function_exists('dw_format_rupiah') ? dw_format_rupiah($total_belanja) : 'Rp ' . number_format($total_belanja, 0, ',', '.'); ?>
                                </span>
                            </div>
                            <!-- Jika ada diskon/pajak bisa ditambahkan di sini -->
                        </div>
                        
                        <div class="border-t border-dashed border-gray-200 my-4 pt-4">
                            <div class="flex justify-between items-end">
                                <span class="font-bold text-gray-800">Total Tagihan</span>
                                <span class="font-bold text-xl text-orange-600">
                                    <?php echo function_exists('dw_format_rupiah') ? dw_format_rupiah($total_belanja) : 'Rp ' . number_format($total_belanja, 0, ',', '.'); ?>
                                </span>
                            </div>
                            <p class="text-xs text-gray-400 mt-1 text-right">Belum termasuk ongkir</p>
                        </div>
                        
                        <a href="<?php echo home_url('/checkout'); ?>" class="w-full block bg-gray-900 text-white py-4 rounded-xl font-bold text-center shadow-lg hover:bg-orange-600 hover:shadow-orange-200 transition-all duration-300 transform active:scale-95 group">
                            Lanjut Pembayaran <i class="fas fa-arrow-right ml-2 group-hover:translate-x-1 transition-transform"></i>
                        </a>
                        
                        <div class="mt-4 flex items-center justify-center gap-2 text-xs text-gray-400">
                            <i class="fas fa-shield-alt"></i> Pembayaran Aman & Terpercaya
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Basic interaction for UI feedback (Actual logic needs AJAX implementation)
    const qtyBtns = document.querySelectorAll('.qty-btn');
    
    qtyBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const isPlus = this.classList.contains('plus');
            const input = this.parentElement.querySelector('input');
            let val = parseInt(input.value);
            
            if(isPlus) {
                val++;
            } else {
                if(val > 1) val--;
            }
            
            input.value = val;
            
            // TODO: Add AJAX call here to update cart in backend
            // Example: updateCart(this.dataset.key, val);
            
            // Visual feedback
            this.closest('.group').classList.add('ring-2', 'ring-orange-100');
            setTimeout(() => {
                this.closest('.group').classList.remove('ring-2', 'ring-orange-100');
            }, 300);
        });
    });

    // Delete confirmation visual
    const deleteBtns = document.querySelectorAll('.delete-cart-item');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if(confirm('Apakah Anda yakin ingin menghapus produk ini?')) {
                // TODO: Add AJAX call to remove item
                // this.closest('.group').remove();
                
                // For now, reload to let backend handle it (if linked to href)
                // or just visual remove
            }
        });
    });
});
</script>

<?php get_footer(); ?>