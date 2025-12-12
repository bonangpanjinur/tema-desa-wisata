<?php
/**
 * Template Name: Halaman Checkout
 */

// Proteksi: Hanya user login yang bisa checkout
if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login?redirect_to=' . urlencode( get_permalink() )) );
    exit;
}

get_header(); 
$user = wp_get_current_user();
?>

<div class="bg-gray-50 py-10 min-h-screen">
    <div class="container mx-auto px-4">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Checkout & Pembayaran</h1>

        <form id="checkout-form" class="flex flex-col lg:flex-row gap-8">
            
            <!-- Left Column: Address & Details -->
            <div class="lg:w-2/3 space-y-6">
                
                <!-- Alamat Pengiriman -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-primary"></i> Alamat Pengiriman
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penerima</label>
                            <input type="text" name="nama_penerima" value="<?php echo esc_attr($user->display_name); ?>" required class="w-full border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">No. WhatsApp</label>
                            <input type="text" name="no_hp" required placeholder="08..." class="w-full border-gray-300 rounded-md focus:ring-primary focus:border-primary">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                            <textarea name="alamat" rows="2" required class="w-full border-gray-300 rounded-md focus:ring-primary focus:border-primary"></textarea>
                        </div>
                        <!-- Dropdown Wilayah (Dummy for now, in real app fetch via API) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Provinsi</label>
                            <select class="w-full border-gray-300 rounded-md text-gray-500">
                                <option>Jawa Tengah</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kota/Kabupaten</label>
                            <select class="w-full border-gray-300 rounded-md text-gray-500">
                                <option>Semarang</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Review Items (Render via JS) -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="font-bold text-lg mb-4">Rincian Pesanan</h3>
                    <div id="checkout-items-container" class="space-y-4">
                        <!-- Items injected by JS -->
                        <div class="animate-pulse flex space-x-4">
                            <div class="bg-gray-200 h-16 w-16 rounded"></div>
                            <div class="flex-1 space-y-2 py-1">
                                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Column: Payment & Summary -->
            <div class="lg:w-1/3">
                <div class="bg-white p-6 rounded-lg shadow-sm sticky top-24">
                    <h3 class="font-bold text-lg mb-4">Ringkasan Pembayaran</h3>
                    
                    <div class="space-y-2 text-sm text-gray-600 mb-4">
                        <div class="flex justify-between">
                            <span>Subtotal Produk</span>
                            <span id="checkout-subtotal">Rp 0</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Biaya Layanan</span>
                            <span>Rp 2.000</span>
                        </div>
                        <div class="flex justify-between font-semibold text-primary">
                            <span>Ongkos Kirim</span>
                            <span>Menunggu Konfirmasi</span>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-100 my-4"></div>
                    
                    <div class="flex justify-between mb-6 text-lg font-bold text-gray-900">
                        <span>Total Tagihan</span>
                        <span id="checkout-total">Rp 0</span>
                    </div>

                    <div class="bg-yellow-50 p-3 rounded text-xs text-yellow-800 mb-4 border border-yellow-200">
                        <i class="fas fa-info-circle mr-1"></i> Ongkir akan dihitung setelah pesanan dibuat dan dikonfirmasi oleh penjual.
                    </div>

                    <button type="submit" id="btn-place-order" class="w-full bg-primary text-white font-bold py-3 rounded-md hover:bg-secondary transition shadow-md flex justify-center items-center">
                        <span>Buat Pesanan</span>
                        <i class="fas fa-spinner fa-spin ml-2 hidden" id="checkout-loader"></i>
                    </button>
                    
                    <p class="text-xs text-center text-gray-400 mt-4">
                        Dengan memesan, Anda setuju dengan Syarat & Ketentuan kami.
                    </p>
                </div>
            </div>
        </form>

        <!-- Success Modal (Hidden) -->
        <div id="order-success-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6 text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 text-green-500 text-3xl">
                    <i class="fas fa-check"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Pesanan Berhasil!</h2>
                <p class="text-gray-600 mb-6">Terima kasih. Pesanan Anda telah diteruskan ke pedagang desa.</p>
                <div class="flex gap-3 justify-center">
                    <a href="<?php echo home_url('/dashboard-toko'); // Seharusnya ke halaman riwayat pesanan pembeli ?>" class="bg-primary text-white px-6 py-2 rounded-md hover:bg-secondary">Lihat Pesanan</a>
                    <a href="<?php echo home_url(); ?>" class="text-gray-500 px-4 py-2 hover:text-gray-800">Ke Beranda</a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php get_footer(); ?>