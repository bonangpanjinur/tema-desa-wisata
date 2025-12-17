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

global $wpdb;
$user_id = get_current_user_id();

// 2. Ambil Data Keranjang (Sama seperti page-cart, tapi kita butuh detail pedagang)
$cart_items = function_exists('dw_get_cart_items') ? dw_get_cart_items() : []; 

if (empty($cart_items)) {
    echo '<div class="container mx-auto py-20 text-center">Keranjang kosong. <a href="'.home_url('/produk').'">Belanja dulu yuk!</a></div>';
    get_footer();
    exit;
}

// 3. Grouping by Merchant (Pedagang) untuk Split Order Logic
$grouped_items = [];
$total_all = 0;
$total_weight = 0;

foreach ($cart_items as $item) {
    $merchant_id = $item['id_pedagang']; // Pastikan dw_get_cart_items() return kolom ini
    if (!isset($grouped_items[$merchant_id])) {
        $grouped_items[$merchant_id] = [
            'info' => [
                'nama_toko' => $item['nama_toko'],
                'id_pedagang' => $merchant_id,
                'slug_toko' => $item['slug_toko']
            ],
            'items' => [],
            'subtotal' => 0
        ];
    }
    
    $line_total = $item['price'] * $item['qty'];
    $grouped_items[$merchant_id]['items'][] = $item;
    $grouped_items[$merchant_id]['subtotal'] += $line_total;
    
    $total_all += $line_total;
}

// 4. Ambil Profil User untuk Auto-fill Form
$user_meta = get_user_meta($user_id);
// Mapping sederhana, sesuaikan dengan meta key Anda
$nama_user = isset($user_meta['first_name'][0]) ? $user_meta['first_name'][0] : '';
$no_hp     = isset($user_meta['billing_phone'][0]) ? $user_meta['billing_phone'][0] : '';
$alamat    = isset($user_meta['billing_address_1'][0]) ? $user_meta['billing_address_1'][0] : '';

// 5. Proses Submit (Simple PHP Handling for now)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dw_checkout_nonce']) && wp_verify_nonce($_POST['dw_checkout_nonce'], 'dw_checkout_action')) {
    // Panggil fungsi di functions.php untuk process DB transaction
    // dw_process_checkout_submission($_POST);
    // Untuk saat ini kita tampilkan UI saja.
}
?>

<div class="bg-gray-50 min-h-screen pb-20">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <h1 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
            <i class="fas fa-lock text-orange-600"></i> Checkout Aman
        </h1>

        <form action="<?php echo admin_url('admin-post.php'); ?>" method="POST" id="checkout-form">
            <input type="hidden" name="action" value="dw_handle_checkout"> <!-- Hook ke admin-post.php -->
            <?php wp_nonce_field('dw_checkout_action', 'dw_checkout_nonce'); ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- KOLOM KIRI: Alamat & Item -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- 1. Alamat Pengiriman -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <h2 class="font-bold text-gray-800 mb-4 border-b pb-2">Alamat Pengiriman</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penerima</label>
                                <input type="text" name="nama_penerima" value="<?php echo esc_attr($nama_user); ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp</label>
                                <input type="text" name="no_hp" value="<?php echo esc_attr($no_hp); ?>" required class="w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                                <textarea name="alamat_lengkap" rows="3" required class="w-full px-4 py-2 border rounded-lg focus:ring-orange-500 focus:border-orange-500 bg-gray-50"><?php echo esc_textarea($alamat); ?></textarea>
                            </div>
                            <!-- Dropdown Wilayah (Provinsi/Kab/Kec) sebaiknya menggunakan AJAX select2 -->
                            <div class="md:col-span-2">
                                <p class="text-xs text-blue-600"><i class="fas fa-info-circle"></i> Pastikan alamat lengkap untuk memudahkan pengiriman.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Detail Pesanan per Toko -->
                    <?php foreach ($grouped_items as $merchant_id => $group): ?>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex items-center gap-2 mb-4">
                            <i class="fas fa-store text-orange-500"></i>
                            <span class="font-bold text-gray-800"><?php echo esc_html($group['info']['nama_toko']); ?></span>
                        </div>

                        <!-- List Produk -->
                        <div class="space-y-3 mb-4">
                            <?php foreach ($group['items'] as $item): 
                                $img_src = 'https://via.placeholder.com/80';
                                if(!empty($item['foto_utama']) && is_numeric($item['foto_utama'])) {
                                    $att = wp_get_attachment_image_src($item['foto_utama'], 'thumbnail');
                                    if($att) $img_src = $att[0];
                                }
                            ?>
                            <div class="flex gap-4 bg-gray-50 p-3 rounded-lg">
                                <img src="<?php echo esc_url($img_src); ?>" class="w-16 h-16 object-cover rounded-md bg-white border">
                                <div class="flex-1">
                                    <h4 class="font-bold text-sm text-gray-800"><?php echo esc_html($item['nama_produk']); ?></h4>
                                    <div class="text-xs text-gray-500"><?php echo $item['qty']; ?> x Rp <?php echo number_format($item['price'],0,',','.'); ?></div>
                                </div>
                                <div class="font-bold text-sm text-gray-800">
                                    Rp <?php echo number_format($item['price'] * $item['qty'], 0, ',', '.'); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Opsi Pengiriman (Mockup) -->
                        <div class="border-t border-gray-100 pt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Pengiriman</label>
                            <select name="shipping[<?php echo $merchant_id; ?>]" class="w-full px-4 py-2 border rounded-lg bg-white text-sm">
                                <option value="cod">COD (Bayar di Tempat) - Rp 0</option>
                                <option value="jne">JNE Reguler - Rp 10.000 (Estimasi)</option>
                                <option value="ojek">Ojek Desa - Rp 5.000</option>
                            </select>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>

                <!-- KOLOM KANAN: Ringkasan & Bayar -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 sticky top-24">
                        <h3 class="font-bold text-lg mb-4">Ringkasan Pembayaran</h3>
                        
                        <div class="space-y-2 text-sm text-gray-600 mb-4">
                            <div class="flex justify-between">
                                <span>Total Harga Barang</span>
                                <span>Rp <?php echo number_format($total_all, 0, ',', '.'); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Total Ongkos Kirim</span>
                                <span>-</span> <small>(Sesuai pilihan)</small>
                            </div>
                            <div class="flex justify-between">
                                <span>Biaya Layanan</span>
                                <span>Rp 1.000</span>
                            </div>
                        </div>

                        <div class="border-t border-dashed border-gray-200 pt-4 mb-6">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-900">Total Tagihan</span>
                                <span class="font-bold text-xl text-orange-600">
                                    Rp <?php echo number_format($total_all + 1000, 0, ',', '.'); ?>*
                                </span>
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">*Belum termasuk ongkir real</p>
                        </div>

                        <h4 class="font-bold text-sm mb-2">Metode Pembayaran</h4>
                        <div class="space-y-2 mb-6">
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="transfer" checked class="text-orange-600 focus:ring-orange-500">
                                <span class="text-sm font-medium">Transfer Bank / QRIS</span>
                            </label>
                            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
                                <input type="radio" name="payment_method" value="cod" class="text-orange-600 focus:ring-orange-500">
                                <span class="text-sm font-medium">COD (Bayar di Tempat)</span>
                            </label>
                        </div>

                        <button type="submit" class="w-full bg-gray-900 text-white py-4 rounded-xl font-bold shadow-lg hover:bg-orange-600 hover:shadow-orange-200 transition-all duration-300 transform active:scale-95">
                            Buat Pesanan
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>