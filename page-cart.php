<?php
/**
 * Template Name: Halaman Keranjang (Cart) Integrated
 */

get_header(); 

// 1. Ambil data keranjang (Pastikan user login/guest handled by plugin logic)
$user_id = get_current_user_id();
// Menggunakan fungsi dw_get_user_cart dari includes/cart.php
$cart_items = function_exists('dw_get_user_cart') ? dw_get_user_cart($user_id) : []; 
$total_belanja = 0;
?>

<div class="bg-[#FAFAFA] min-h-screen font-sans text-gray-800 pb-20 relative overflow-x-hidden">
    
    <!-- Background Decor -->
    <div class="absolute top-0 left-0 w-full h-[300px] bg-gradient-to-b from-orange-50/60 to-transparent -z-10"></div>

    <div class="container mx-auto px-4 py-8 md:py-12 max-w-6xl">
        <!-- Header Halaman -->
        <div class="flex items-center gap-3 mb-8">
            <div class="p-3 bg-white rounded-xl shadow-sm border border-gray-100 text-orange-600">
                <i class="fas fa-shopping-cart text-xl"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900">Keranjang Belanja</h1>
        </div>

        <?php if (empty($cart_items)) : ?>
            <!-- STATE: KOSONG -->
            <div class="flex flex-col items-center justify-center py-24 bg-white rounded-3xl shadow-sm border border-gray-100 text-center px-4 animate-fade-in-up">
                <div class="relative mb-6">
                    <div class="w-32 h-32 bg-orange-50 rounded-full flex items-center justify-center">
                        <i class="fas fa-shopping-basket text-5xl text-orange-300"></i>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Keranjang Belanja Kosong</h3>
                <p class="text-gray-500 mb-8 max-w-md mx-auto">Tampaknya Anda belum menambahkan produk apapun.</p>
                <a href="<?php echo home_url('/produk'); ?>" class="group px-8 py-3 bg-gray-900 text-white rounded-xl font-bold hover:bg-orange-600 transition-all duration-300 shadow-lg hover:shadow-orange-200 flex items-center gap-2">
                    <i class="fas fa-store"></i> Mulai Belanja
                </a>
            </div>
        <?php else : ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                
                <!-- KOLOM KIRI: Daftar Item -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Header List -->
                    <div class="hidden md:grid grid-cols-12 gap-4 px-4 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider">
                        <div class="col-span-6">Produk</div>
                        <div class="col-span-3 text-center">Jumlah</div>
                        <div class="col-span-3 text-right">Total</div>
                    </div>

                    <?php foreach ($cart_items as $item) : 
                        // FIX: Mapping Key Sesuai includes/cart.php
                        $product_id = isset($item['productId']) ? $item['productId'] : (isset($item['product_id']) ? $item['product_id'] : 0);
                        $qty = isset($item['quantity']) ? $item['quantity'] : (isset($item['qty']) ? $item['qty'] : 1);
                        $price = isset($item['price']) ? $item['price'] : 0;
                        $name = isset($item['name']) ? $item['name'] : 'Produk Tanpa Nama';
                        
                        // FIX: Ambil URL Gambar Langsung
                        $img_src = !empty($item['image']) ? $item['image'] : 'https://via.placeholder.com/150?text=No+Image';
                        
                        // FIX: Ambil Nama Toko dari array nested
                        $shop_name = isset($item['toko']['nama_toko']) ? $item['toko']['nama_toko'] : 'Toko Desa';

                        $subtotal = $price * $qty;
                        $total_belanja += $subtotal;
                    ?>
                    
                    <!-- ITEM CARD -->
                    <div class="cart-item-row group bg-white p-4 rounded-2xl shadow-sm border border-gray-100 transition-all hover:shadow-md relative overflow-hidden" 
                         data-cart-id="<?php echo esc_attr($item['id'] ?? ''); ?>" 
                         data-product-id="<?php echo esc_attr($product_id); ?>">
                        
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                            
                            <!-- 1. Info Produk -->
                            <div class="col-span-1 md:col-span-6 flex gap-4">
                                <!-- Gambar -->
                                <div class="w-20 h-20 md:w-24 md:h-24 bg-gray-50 rounded-xl overflow-hidden flex-shrink-0 border border-gray-100 relative">
                                    <a href="<?php echo get_permalink($product_id); ?>">
                                        <img src="<?php echo esc_url($img_src); ?>" 
                                             alt="<?php echo esc_attr($name); ?>" 
                                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                             onerror="this.src='https://via.placeholder.com/150?text=Err';">
                                    </a>
                                </div>
                                
                                <!-- Detail -->
                                <div class="flex-1 min-w-0 flex flex-col justify-center">
                                    <a href="<?php echo get_permalink($product_id); ?>" class="block">
                                        <h3 class="font-bold text-gray-800 text-sm md:text-base leading-tight mb-1 line-clamp-2 hover:text-orange-600 transition-colors">
                                            <?php echo esc_html($name); ?>
                                        </h3>
                                    </a>
                                    
                                    <!-- Nama Toko -->
                                    <div class="flex items-center gap-1 text-xs text-gray-500 mb-2">
                                        <i class="fas fa-store text-orange-400"></i> 
                                        <span><?php echo esc_html($shop_name); ?></span>
                                    </div>

                                    <!-- Harga Satuan -->
                                    <div class="text-xs text-gray-400">
                                        @ Rp <?php echo number_format($price, 0, ',', '.'); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Kontrol Jumlah -->
                            <div class="col-span-1 md:col-span-3 flex justify-between md:justify-center items-center mt-2 md:mt-0 border-t md:border-t-0 border-gray-50 pt-3 md:pt-0">
                                <span class="md:hidden text-xs font-bold text-gray-500">Jumlah:</span>
                                <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200">
                                    <button class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-orange-600 hover:bg-white rounded-l-lg transition-all qty-btn minus">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" value="<?php echo esc_attr($qty); ?>" class="w-10 text-center bg-transparent border-none text-sm font-bold text-gray-700 p-0 focus:ring-0 qty-input" readonly>
                                    <button class="w-8 h-8 flex items-center justify-center text-gray-500 hover:text-orange-600 hover:bg-white rounded-r-lg transition-all qty-btn plus">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- 3. Subtotal & Hapus -->
                            <div class="col-span-1 md:col-span-3 flex justify-between md:justify-end items-center mt-2 md:mt-0">
                                <span class="md:hidden text-xs font-bold text-gray-500">Total:</span>
                                <div class="text-right">
                                    <div class="font-bold text-orange-600 text-base md:text-lg item-subtotal">
                                        Rp <?php echo number_format($subtotal, 0, ',', '.'); ?>
                                    </div>
                                </div>
                                
                                <button class="delete-cart-item ml-4 text-gray-300 hover:text-red-500 p-2 rounded-full hover:bg-red-50 transition-all duration-200" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>

                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <a href="<?php echo home_url('/produk'); ?>" class="inline-flex items-center gap-2 text-gray-500 hover:text-orange-600 font-medium text-sm mt-4 pl-1 transition">
                        <i class="fas fa-arrow-left"></i> Lanjut Belanja
                    </a>
                </div>

                <!-- KOLOM KANAN: Ringkasan Belanja -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-2xl shadow-lg shadow-orange-50 border border-orange-100 sticky top-24">
                        <h2 class="font-bold text-gray-900 text-lg mb-6 flex items-center gap-2">
                            <i class="fas fa-receipt text-orange-500"></i> Ringkasan Pesanan
                        </h2>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-gray-600 text-sm">
                                <span>Total Item</span>
                                <span class="font-medium text-gray-900"><?php echo count($cart_items); ?> Produk</span>
                            </div>
                            <div class="flex justify-between text-gray-600 text-sm">
                                <span>Subtotal Produk</span>
                                <span class="font-medium text-gray-900 cart-grand-total">
                                    Rp <?php echo number_format($total_belanja, 0, ',', '.'); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="border-t border-dashed border-gray-200 my-4 pt-4">
                            <div class="flex justify-between items-end mb-1">
                                <span class="font-bold text-gray-800">Total Sementara</span>
                                <span class="font-bold text-2xl text-orange-600 cart-grand-total">
                                    Rp <?php echo number_format($total_belanja, 0, ',', '.'); ?>
                                </span>
                            </div>
                            <p class="text-xs text-right text-gray-400">Belum termasuk ongkir</p>
                        </div>
                        
                        <a href="<?php echo home_url('/checkout'); ?>" class="block w-full bg-gray-900 text-white py-4 rounded-xl font-bold text-center shadow-lg hover:bg-orange-600 hover:shadow-orange-200 transition-all duration-300 transform active:scale-[0.98]">
                            Checkout Sekarang <i class="fas fa-arrow-right ml-2"></i>
                        </a>

                        <div class="mt-6 flex items-center justify-center gap-2 text-xs text-gray-400 bg-gray-50 py-2 rounded-lg">
                            <i class="fas fa-shield-alt text-green-500"></i> Jaminan Transaksi Aman
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>