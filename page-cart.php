<?php
/**
 * Template Name: Halaman Cart
 */
get_header(); ?>

<div class="bg-gray-50 py-10 min-h-screen">
    <div class="container mx-auto px-4">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Keranjang Belanja</h1>

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Cart Items List -->
            <div class="lg:w-2/3">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden" id="cart-container">
                    <!-- Javascript will render items here -->
                    <div class="p-8 text-center text-gray-500" id="cart-empty-state">
                        <i class="fas fa-shopping-basket text-4xl mb-4 text-gray-300"></i>
                        <p>Keranjang belanja Anda kosong.</p>
                        <a href="<?php echo home_url('/produk'); ?>" class="text-primary font-semibold hover:underline mt-2 inline-block">Mulai Belanja</a>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="lg:w-1/3">
                <div class="bg-white p-6 rounded-lg shadow-sm sticky top-24">
                    <h3 class="font-bold text-lg mb-4 text-gray-800">Ringkasan Belanja</h3>
                    
                    <div class="flex justify-between mb-2 text-gray-600">
                        <span>Total Item</span>
                        <span id="summary-count">0 barang</span>
                    </div>
                    <div class="border-t border-gray-100 my-4"></div>
                    <div class="flex justify-between mb-6 text-lg font-bold text-gray-900">
                        <span>Total Harga</span>
                        <span id="summary-total">Rp 0</span>
                    </div>

                    <a href="<?php echo home_url('/checkout'); ?>" id="btn-checkout" class="block w-full bg-primary text-white text-center font-bold py-3 rounded-md hover:bg-secondary transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        Lanjut ke Pembayaran
                    </a>
                    
                    <button onclick="clearCart()" class="block w-full text-gray-400 text-sm mt-4 hover:text-red-500">
                        Kosongkan Keranjang
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>