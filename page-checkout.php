<?php
/**
 * Template Name: Halaman Checkout Integrated
 */

// 1. Cek Login
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login?redirect_to=' . urlencode(get_permalink())));
    exit;
}

get_header();

$user_id = get_current_user_id();
$cart_items = function_exists('dw_get_user_cart') ? dw_get_user_cart($user_id) : [];

if (empty($cart_items)) {
    echo '<div class="container mx-auto py-20 text-center">Keranjang kosong. <a href="'.home_url('/produk').'">Belanja dulu yuk!</a></div>';
    get_footer();
    exit;
}

// 2. Grouping by Merchant (Logic Updated for correct Array Keys)
$grouped_items = [];
$total_all = 0;

foreach ($cart_items as $item) {
    // FIX: Gunakan 'sellerId' atau 'toko'['id_pedagang']
    $merchant_id = isset($item['sellerId']) ? $item['sellerId'] : (isset($item['toko']['id_pedagang']) ? $item['toko']['id_pedagang'] : 0);
    $shop_name = isset($item['toko']['nama_toko']) ? $item['toko']['nama_toko'] : 'Toko Desa';
    
    // Fallback jika ID Pedagang 0 (Error data)
    if ($merchant_id == 0) continue; 

    if (!isset($grouped_items[$merchant_id])) {
        $grouped_items[$merchant_id] = [
            'info' => [
                'nama_toko' => $shop_name,
                'id_pedagang' => $merchant_id,
            ],
            'items' => [],
            'subtotal' => 0
        ];
    }
    
    $qty = isset($item['quantity']) ? $item['quantity'] : 1;
    $price = isset($item['price']) ? $item['price'] : 0;
    
    $line_total = $price * $qty;
    
    // Tambahkan data image yang benar ke item array
    $item['image'] = !empty($item['image']) ? $item['image'] : 'https://via.placeholder.com/150?text=No+Image';
    $item['name'] = isset($item['name']) ? $item['name'] : 'Produk';
    
    $grouped_items[$merchant_id]['items'][] = $item;
    $grouped_items[$merchant_id]['subtotal'] += $line_total;
    
    $total_all += $line_total;
}

// 3. Ambil Profil User
$user_meta = get_user_meta($user_id);
$nama_user = isset($user_meta['first_name'][0]) ? $user_meta['first_name'][0] : '';
$no_hp     = isset($user_meta['billing_phone'][0]) ? $user_meta['billing_phone'][0] : '';
$alamat    = isset($user_meta['billing_address_1'][0]) ? $user_meta['billing_address_1'][0] : '';
?>

<div class="bg-gray-50 min-h-screen pb-20 font-sans text-gray-800">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <h1 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <i class="fas fa-lock text-orange-600"></i> Checkout Aman
        </h1>

        <form action="<?php echo admin_url('admin-post.php'); ?>" method="POST" id="checkout-form">
            <input type="hidden" name="action" value="dw_handle_checkout">
            <?php wp_nonce_field('dw_checkout_action', 'dw_checkout_nonce'); ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- KOLOM KIRI: Alamat & Item -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- 1. Alamat Pengiriman -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <h2 class="font-bold text-gray-800 mb-4 border-b pb-2 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-orange-500"></i> Alamat Pengiriman
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Nama Penerima</label>
                                <input type="text" name="nama_penerima" value="<?php echo esc_attr($nama_user); ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Nomor WhatsApp</label>
                                <input type="text" name="no_hp" value="<?php echo esc_attr($no_hp); ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Alamat Lengkap</label>
                                <textarea name="alamat_lengkap" rows="3" required class="w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50" placeholder="Jalan, RT/RW, Nomor Rumah..."><?php echo esc_textarea($alamat); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Detail Pesanan per Toko -->
                    <?php foreach ($grouped_items as $merchant_id => $group): ?>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                        <!-- Header Toko -->
                        <div class="flex items-center gap-2 mb-4 bg-orange-50 p-3 rounded-lg -mx-2 -mt-2">
                            <div class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-orange-500 shadow-sm">
                                <i class="fas fa-store"></i>
                            </div>
                            <span class="font-bold text-gray-800"><?php echo esc_html($group['info']['nama_toko']); ?></span>
                        </div>

                        <!-- List Produk -->
                        <div class="space-y-3 mb-4">
                            <?php foreach ($group['items'] as $item): 
                                $qty = isset($item['quantity']) ? $item['quantity'] : 1;
                                $price = isset($item['price']) ? $item['price'] : 0;
                            ?>
                            <div class="flex gap-4 bg-white p-2 rounded-lg border border-gray-50 hover:border-gray-200 transition">
                                <!-- FIX: Gambar Produk -->
                                <img src="<?php echo esc_url($item['image']); ?>" 
                                     class="w-16 h-16 object-cover rounded-md bg-gray-100 border"
                                     onerror="this.src='https://via.placeholder.com/150?text=Err';">
                                
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-sm text-gray-800 line-clamp-1"><?php echo esc_html($item['name']); ?></h4>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?php echo $qty; ?> x Rp <?php echo number_format($price,0,',','.'); ?>
                                    </div>
                                </div>
                                <div class="font-bold text-sm text-gray-800 self-center">
                                    Rp <?php echo number_format($price * $qty, 0, ',', '.'); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Opsi Pengiriman -->
                        <div class="border-t border-gray-100 pt-4 mt-4">
                            <label class="block text-xs font-bold text-gray-500 mb-2 uppercase">Pilih Kurir</label>
                            <div class="relative">
                                <select name="shipping[<?php echo $merchant_id; ?>]" class="w-full pl-10 pr-4 py-2 border rounded-lg bg-gray-50 text-sm appearance-none focus:ring-orange-500 focus:bg-white transition cursor-pointer">
                                    <option value="cod">COD (Bayar di Tempat)</option>
                                    <option value="jne">JNE Reguler (Cek Ongkir)</option>
                                    <option value="ojek">Ojek Desa Lokal</option>
                                </select>
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-truck"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>

                <!-- KOLOM KANAN: Ringkasan & Bayar -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 sticky top-24">
                        <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                            <i class="fas fa-file-invoice-dollar text-orange-500"></i> Ringkasan
                        </h3>
                        
                        <div class="space-y-3 text-sm text-gray-600 mb-6">
                            <div class="flex justify-between">
                                <span>Total Harga Barang</span>
                                <span class="font-medium text-gray-900">Rp <?php echo number_format($total_all, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Biaya Layanan</span>
                                <span class="font-medium text-gray-900">Rp 1.000</span>
                            </div>
                            <div class="flex justify-between text-orange-500">
                                <span>Ongkos Kirim</span>
                                <span>(Menunggu Kurir)</span>
                            </div>
                        </div>

                        <div class="border-t border-dashed border-gray-200 pt-4 mb-6">
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-bold text-gray-900">Total Tagihan</span>
                                <span class="font-bold text-xl text-orange-600">
                                    Rp <?php echo number_format($total_all + 1000, 0, ',', '.'); ?>
                                </span>
                            </div>
                        </div>

                        <h4 class="font-bold text-xs text-gray-500 uppercase mb-3">Metode Pembayaran</h4>
                        <div class="space-y-2 mb-6">
                            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-orange-50 hover:border-orange-200 transition group has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500">
                                <input type="radio" name="payment_method" value="transfer" checked class="text-orange-600 focus:ring-orange-500">
                                <div class="flex-1">
                                    <span class="block text-sm font-bold text-gray-800">Transfer Bank / QRIS</span>
                                    <span class="block text-xs text-gray-500">Verifikasi Otomatis</span>
                                </div>
                                <i class="fas fa-qrcode text-gray-400 group-hover:text-orange-500"></i>
                            </label>
                            <label class="flex items-center gap-3 p-3 border rounded-xl cursor-pointer hover:bg-orange-50 hover:border-orange-200 transition group has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500">
                                <input type="radio" name="payment_method" value="cod" class="text-orange-600 focus:ring-orange-500">
                                <div class="flex-1">
                                    <span class="block text-sm font-bold text-gray-800">COD</span>
                                    <span class="block text-xs text-gray-500">Bayar saat barang sampai</span>
                                </div>
                                <i class="fas fa-hand-holding-usd text-gray-400 group-hover:text-orange-500"></i>
                            </label>
                        </div>

                        <button type="submit" class="w-full bg-gray-900 text-white py-4 rounded-xl font-bold shadow-lg hover:bg-orange-600 hover:shadow-orange-200 transition-all duration-300 transform active:scale-95 flex justify-center items-center gap-2">
                            <i class="fas fa-check-circle"></i> Buat Pesanan
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>