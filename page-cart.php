<?php
/* Template Name: Halaman Keranjang */
get_header(); 
?>

<!-- Header Title -->
<div class="glass sticky top-0 z-30 px-5 py-4 border-b border-gray-100 flex items-center gap-3">
    <a href="<?php echo home_url(); ?>" class="text-gray-600"><i class="ph-bold ph-arrow-left text-xl"></i></a>
    <h1 class="text-lg font-bold text-gray-800">Keranjang Belanja</h1>
</div>

<div class="p-5 pb-32 min-h-screen relative">
    
    <!-- List Produk di Keranjang (Placeholder Logic) -->
    <!-- Nanti ini harus diloop dari dw_get_user_cart() -->
    <div id="cart-items-container" class="space-y-4">
        
        <!-- Cart Item 1 -->
        <div class="bg-white p-3 rounded-2xl shadow-soft border border-gray-50 flex gap-3 relative overflow-hidden group">
            <!-- Checkbox -->
            <div class="flex items-center">
                <input type="checkbox" class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary">
            </div>
            
            <!-- Image -->
            <div class="w-20 h-20 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                <img src="https://images.unsplash.com/photo-1584555613497-9ecf9dd06f68?q=80&w=200" class="w-full h-full object-cover">
            </div>
            
            <!-- Details -->
            <div class="flex-1 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 line-clamp-1">Kripik Pisang Coklat</h3>
                    <p class="text-[10px] text-gray-500">Toko Bu Sri • 250gr</p>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-sm font-bold text-secondary">Rp 15.000</span>
                    
                    <!-- Qty Control -->
                    <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200">
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-primary">-</button>
                        <input type="text" value="1" class="w-8 h-6 text-center text-xs bg-transparent border-none p-0" readonly>
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-primary">+</button>
                    </div>
                </div>
            </div>

            <!-- Delete Button (Top Right) -->
            <button class="absolute top-2 right-2 text-gray-300 hover:text-red-500">
                <i class="ph-bold ph-trash"></i>
            </button>
        </div>

        <!-- Cart Item 2 -->
        <div class="bg-white p-3 rounded-2xl shadow-soft border border-gray-50 flex gap-3 relative overflow-hidden group">
            <div class="flex items-center">
                <input type="checkbox" class="w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary" checked>
            </div>
            <div class="w-20 h-20 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                <img src="https://images.unsplash.com/photo-1629198688000-71f23e745b6e?q=80&w=200" class="w-full h-full object-cover">
            </div>
            <div class="flex-1 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 line-clamp-1">Madu Hutan Asli</h3>
                    <p class="text-[10px] text-gray-500">Madu Jaya • 500ml</p>
                </div>
                <div class="flex justify-between items-end">
                    <span class="text-sm font-bold text-secondary">Rp 120.000</span>
                    <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200">
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-primary">-</button>
                        <input type="text" value="1" class="w-8 h-6 text-center text-xs bg-transparent border-none p-0" readonly>
                        <button class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-primary">+</button>
                    </div>
                </div>
            </div>
            <button class="absolute top-2 right-2 text-gray-300 hover:text-red-500">
                <i class="ph-bold ph-trash"></i>
            </button>
        </div>

    </div> <!-- End Cart Items -->

    <!-- Empty State (Hidden by default) -->
    <div id="cart-empty" class="hidden flex flex-col items-center justify-center py-20 text-center">
        <div class="w-32 h-32 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-300">
            <i class="ph-fill ph-shopping-cart text-5xl"></i>
        </div>
        <h3 class="text-lg font-bold text-gray-800">Keranjang Kosong</h3>
        <p class="text-sm text-gray-500 mb-6 px-10">Wah, keranjangmu masih sepi nih. Yuk cari oleh-oleh menarik!</p>
        <a href="<?php echo site_url('/produk'); ?>" class="bg-primary text-white px-6 py-3 rounded-xl font-bold shadow-lg hover:shadow-xl transition-all">
            Mulai Belanja
        </a>
    </div>

</div>

<!-- Sticky Bottom Checkout Bar -->
<div class="fixed bottom-0 w-full max-w-[440px] bg-white border-t border-gray-100 p-4 pb-8 z-40 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center gap-2">
            <input type="checkbox" id="select-all" class="w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary">
            <label for="select-all" class="text-xs text-gray-600">Pilih Semua</label>
        </div>
        <div class="text-right">
            <span class="text-xs text-gray-500 block">Total Belanja</span>
            <span class="text-lg font-bold text-primary">Rp 135.000</span>
        </div>
    </div>
    <a href="<?php echo site_url('/checkout'); ?>" class="block w-full bg-gray-900 text-white text-center font-bold py-3.5 rounded-xl shadow-lg hover:bg-gray-800 transition-all">
        Checkout (2)
    </a>
</div>

<?php 
// Load footer scripts but hide standard nav since checkout bar is present
// Alternatively, include nav but add padding-bottom to body
get_footer(); 
?>