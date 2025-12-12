<?php
/* Template Name: Halaman Checkout */
get_header(); 
?>

<!-- Header -->
<div class="glass sticky top-0 z-30 px-5 py-4 border-b border-gray-100 flex items-center gap-3">
    <a href="javascript:history.back()" class="text-gray-600"><i class="ph-bold ph-arrow-left text-xl"></i></a>
    <h1 class="text-lg font-bold text-gray-800">Pengiriman</h1>
</div>

<div class="p-5 pb-32 min-h-screen">
    
    <!-- 1. Alamat Pengiriman -->
    <div class="bg-white p-4 rounded-2xl shadow-soft mb-4">
        <h3 class="text-sm font-bold text-gray-800 mb-2 flex items-center gap-2">
            <i class="ph-fill ph-map-pin text-primary"></i> Alamat Pengiriman
        </h3>
        <div class="pl-6 border-l-2 border-gray-100">
            <p class="text-sm font-bold text-gray-900">Budi Santoso <span class="text-gray-500 font-normal">| 08123456789</span></p>
            <p class="text-xs text-gray-600 mt-1 leading-relaxed">
                Jl. Merpati No. 45, RT 02/RW 05, Desa Cibuntu, Kec. Pasawahan, Kab. Kuningan, Jawa Barat
            </p>
        </div>
        <button class="mt-3 w-full py-2 border border-gray-200 rounded-lg text-xs font-bold text-gray-600 hover:bg-gray-50">
            Ganti Alamat
        </button>
    </div>

    <!-- 2. Ringkasan Pesanan (Per Toko) -->
    <div class="bg-white p-4 rounded-2xl shadow-soft mb-4">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Pesanan 1</h3>
        
        <!-- Toko Header -->
        <div class="flex items-center gap-2 mb-3 pb-3 border-b border-gray-50">
            <i class="ph-fill ph-storefront text-primary"></i>
            <span class="text-sm font-bold text-gray-800">Toko Bu Sri</span>
        </div>

        <!-- Items -->
        <div class="flex gap-3 mb-3">
            <div class="w-14 h-14 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0"><img src="https://images.unsplash.com/photo-1584555613497-9ecf9dd06f68?q=80&w=150" class="w-full h-full object-cover"></div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-gray-800 line-clamp-1">Kripik Pisang Coklat</h4>
                <div class="flex justify-between mt-1">
                    <span class="text-xs text-gray-500">1 x Rp 15.000</span>
                    <span class="text-xs font-bold text-gray-800">Rp 15.000</span>
                </div>
            </div>
        </div>

        <!-- Opsi Pengiriman -->
        <div class="mt-4 bg-gray-50 p-3 rounded-xl">
            <label class="text-xs text-gray-500 block mb-1">Metode Pengiriman</label>
            <select class="w-full bg-white border border-gray-200 text-sm rounded-lg p-2 focus:ring-primary focus:border-primary">
                <option>JNE Reguler (Rp 10.000)</option>
                <option>J&T Express (Rp 11.000)</option>
                <option>Ojek Desa (Rp 5.000)</option>
            </select>
        </div>
    </div>

    <!-- 3. Ringkasan Pembayaran -->
    <div class="bg-white p-4 rounded-2xl shadow-soft mb-4">
        <h3 class="text-sm font-bold text-gray-800 mb-3">Ringkasan Pembayaran</h3>
        <div class="space-y-2 text-sm text-gray-600">
            <div class="flex justify-between">
                <span>Total Harga (2 Barang)</span>
                <span>Rp 135.000</span>
            </div>
            <div class="flex justify-between">
                <span>Total Ongkos Kirim</span>
                <span>Rp 15.000</span>
            </div>
            <div class="flex justify-between">
                <span>Biaya Layanan</span>
                <span>Rp 1.000</span>
            </div>
            <div class="border-t border-gray-100 my-2 pt-2 flex justify-between font-bold text-base text-gray-900">
                <span>Total Tagihan</span>
                <span class="text-primary">Rp 151.000</span>
            </div>
        </div>
    </div>

</div>

<!-- Sticky Bottom Payment -->
<div class="fixed bottom-0 w-full max-w-[440px] bg-white border-t border-gray-100 p-4 pb-8 z-40 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
    <div class="flex gap-3">
        <div class="flex-1">
            <span class="text-xs text-gray-500 block">Total Bayar</span>
            <span class="text-lg font-bold text-primary">Rp 151.000</span>
        </div>
        <button class="flex-[2] bg-primary text-white font-bold py-3 rounded-xl shadow-lg hover:bg-teal-800 transition-all">
            Bayar Sekarang
        </button>
    </div>
</div>

<?php get_footer(); ?>