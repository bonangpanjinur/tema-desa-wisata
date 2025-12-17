<?php
/**
 * Template Name: Halaman Checkout
 * Description: Form checkout dan pemrosesan pesanan sesuai struktur DB dw_transaksi -> sub -> items.
 */

if (!session_id()) session_start();

$order_success = false;
$error_message = '';
$new_invoice   = '';

// --- LOGIKA PEMROSESAN PESANAN (HANDLER) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dw_place_order'])) {
    
    if (!isset($_POST['dw_checkout_nonce']) || !wp_verify_nonce($_POST['dw_checkout_nonce'], 'dw_checkout_action')) {
        $error_message = 'Validasi keamanan gagal.';
    } elseif (empty($_SESSION['dw_cart'])) {
        $error_message = 'Keranjang belanja kosong.';
    } else {
        global $wpdb;
        $table_transaksi = $wpdb->prefix . 'dw_transaksi';
        $table_sub       = $wpdb->prefix . 'dw_transaksi_sub';
        $table_items     = $wpdb->prefix . 'dw_transaksi_items';
        $table_produk    = $wpdb->prefix . 'dw_produk';
        $table_pedagang  = $wpdb->prefix . 'dw_pedagang';
        
        $user_id     = get_current_user_id(); 
        $nama        = sanitize_text_field($_POST['nama_lengkap']);
        $no_hp       = sanitize_text_field($_POST['no_hp']);
        $alamat      = sanitize_textarea_field($_POST['alamat_lengkap']);
        $catatan     = sanitize_textarea_field($_POST['catatan']);
        $metode      = sanitize_text_field($_POST['payment_method']); 
        
        // 1. Ambil Data Produk + Info Pedagang
        $cart_items = $_SESSION['dw_cart'];
        $product_ids = array_keys($cart_items);
        $ids_str = implode(',', array_map('intval', $product_ids));
        
        $sql = "SELECT p.id, p.nama_produk, p.harga, p.id_pedagang, pd.nama_toko 
                FROM $table_produk p 
                LEFT JOIN $table_pedagang pd ON p.id_pedagang = pd.id 
                WHERE p.id IN ($ids_str)";
        $db_products = $wpdb->get_results($sql);
        
        // 2. Grouping per Pedagang untuk Transaksi Sub
        $grouped_items = [];
        $total_transaksi_all = 0;
        
        foreach ($db_products as $prod) {
            $qty = intval($cart_items[$prod->id]);
            if ($qty > 0) {
                $subtotal_item = $prod->harga * $qty;
                
                if (!isset($grouped_items[$prod->id_pedagang])) {
                    $grouped_items[$prod->id_pedagang] = [
                        'nama_toko' => $prod->nama_toko,
                        'sub_total' => 0,
                        'items'     => []
                    ];
                }
                
                $grouped_items[$prod->id_pedagang]['items'][] = [
                    'id_produk'    => $prod->id,
                    'nama_produk'  => $prod->nama_produk,
                    'harga_satuan' => $prod->harga,
                    'jumlah'       => $qty,
                    'total_harga'  => $subtotal_item
                ];
                
                $grouped_items[$prod->id_pedagang]['sub_total'] += $subtotal_item;
                $total_transaksi_all += $subtotal_item;
            }
        }
        
        // 3. Insert Transaksi Utama
        $invoice_code = 'INV-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        
        $data_trx = [
            'kode_unik'         => $invoice_code,
            'id_pembeli'        => $user_id,
            'total_produk'      => $total_transaksi_all,
            'total_ongkir'      => 0, // Logic ongkir belum ada
            'total_transaksi'   => $total_transaksi_all, // + ongkir
            'nama_penerima'     => $nama,
            'no_hp'             => $no_hp,
            'alamat_lengkap'    => $alamat,
            'catatan_pembeli'   => $catatan,
            'metode_pembayaran' => $metode,
            'status_transaksi'  => 'menunggu_pembayaran',
            'created_at'        => current_time('mysql')
        ];
        
        if ($wpdb->insert($table_transaksi, $data_trx)) {
            $transaksi_id = $wpdb->insert_id;
            $new_invoice = $invoice_code;
            
            // 4. Insert Sub Transaksi & Items
            foreach ($grouped_items as $id_pedagang => $data_sub) {
                // Insert Sub Transaksi
                $wpdb->insert($table_sub, [
                    'id_transaksi'        => $transaksi_id,
                    'id_pedagang'         => $id_pedagang,
                    'nama_toko'           => $data_sub['nama_toko'],
                    'sub_total'           => $data_sub['sub_total'],
                    'ongkir'              => 0,
                    'total_pesanan_toko'  => $data_sub['sub_total'], // + ongkir per toko
                    'status_pesanan'      => 'menunggu_konfirmasi',
                    'created_at'          => current_time('mysql')
                ]);
                $sub_id = $wpdb->insert_id;
                
                // Insert Items
                foreach ($data_sub['items'] as $item) {
                    $wpdb->insert($table_items, [
                        'id_sub_transaksi' => $sub_id,
                        'id_produk'        => $item['id_produk'],
                        'nama_produk'      => $item['nama_produk'],
                        'harga_satuan'     => $item['harga_satuan'],
                        'jumlah'           => $item['jumlah'],
                        'total_harga'      => $item['total_harga']
                    ]);
                }
            }
            
            unset($_SESSION['dw_cart']);
            $order_success = true;
        } else {
            $error_message = 'Gagal menyimpan transaksi utama.';
        }
    }
}

// --- PREPARE VIEW DATA (CART DISPLAY) ---
global $wpdb;
$table_produk = $wpdb->prefix . 'dw_produk';
$cart_items = isset($_SESSION['dw_cart']) ? $_SESSION['dw_cart'] : [];
$list_produk = [];
$total_belanja = 0;

if (!empty($cart_items) && !$order_success) {
    $product_ids = array_keys($cart_items);
    $ids_placeholder = implode(',', array_fill(0, count($product_ids), '%d'));
    // Hanya perlu nama dan harga untuk ringkasan
    $sql = "SELECT id, nama_produk, harga FROM $table_produk WHERE id IN ($ids_placeholder)";
    $raw_products = $wpdb->get_results($wpdb->prepare($sql, $product_ids));
    
    foreach ($raw_products as $prod) {
        $qty = intval($cart_items[$prod->id]);
        $prod->qty = $qty;
        $prod->subtotal = $prod->harga * $qty;
        $list_produk[] = $prod;
        $total_belanja += $prod->subtotal;
    }
}

get_header();
?>

<div class="bg-[#FAFAFA] min-h-screen py-10 font-sans text-gray-800">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <?php if ($order_success) : ?>
            <!-- SUCCESS STATE -->
            <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl p-10 text-center border-t-4 border-green-500 animate-fade-in-up">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-3xl text-green-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Pesanan Berhasil Dibuat!</h1>
                <p class="text-gray-500 mb-8">Terima kasih. Pesanan Anda telah tersimpan di sistem kami.</p>
                
                <div class="bg-gray-50 rounded-xl p-6 mb-8 text-left border border-gray-200">
                    <p class="text-sm text-gray-500 mb-1 uppercase tracking-wide font-bold">Kode Invoice</p>
                    <p class="text-2xl font-bold text-gray-900 tracking-wider font-mono"><?php echo esc_html($new_invoice); ?></p>
                    <div class="border-t border-gray-200 my-4"></div>
                    <p class="text-sm text-gray-600">
                        Silakan cek menu <strong>Transaksi</strong> untuk melihat status dan instruksi pembayaran lebih lanjut.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="<?php echo home_url('/produk'); ?>" class="px-6 py-3 border border-gray-300 rounded-xl font-bold text-gray-600 hover:bg-gray-50 transition">
                        Belanja Lagi
                    </a>
                    <a href="<?php echo home_url('/transaksi'); ?>" class="px-6 py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 shadow-lg shadow-green-200 transition">
                        Lihat Transaksi
                    </a>
                </div>
            </div>

        <?php elseif (empty($cart_items)) : ?>
            <script>window.location.href = "<?php echo home_url('/cart'); ?>";</script>

        <?php else : ?>
            
            <h1 class="text-2xl md:text-3xl font-bold mb-8 text-gray-900">Checkout Pengiriman</h1>

            <?php if ($error_message) : ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-2">
                    <i class="fas fa-exclamation-circle"></i> <?php echo esc_html($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="flex flex-col lg:flex-row gap-8">
                <?php wp_nonce_field('dw_checkout_action', 'dw_checkout_nonce'); ?>

                <!-- FORM PENGIRIMAN -->
                <div class="w-full lg:w-2/3 space-y-6">
                    
                    <!-- Alamat -->
                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 border-b pb-4">
                            <i class="fas fa-map-marker-alt text-orange-500"></i> Alamat Pengiriman
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Penerima</label>
                                <input type="text" name="nama_lengkap" required class="w-full border-gray-300 rounded-xl focus:ring-orange-500 focus:border-orange-500 px-4 py-3" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Nomor WhatsApp</label>
                                <input type="tel" name="no_hp" required class="w-full border-gray-300 rounded-xl focus:ring-orange-500 focus:border-orange-500 px-4 py-3" placeholder="08..." value="">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="block text-sm font-bold text-gray-700 mb-2">Alamat Lengkap</label>
                            <textarea name="alamat_lengkap" rows="3" required class="w-full border-gray-300 rounded-xl focus:ring-orange-500 focus:border-orange-500 px-4 py-3" placeholder="Nama Jalan, RT/RW, Dusun, Desa, Kecamatan..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Catatan Pesanan (Opsional)</label>
                            <textarea name="catatan" rows="2" class="w-full border-gray-300 rounded-xl focus:ring-orange-500 focus:border-orange-500 px-4 py-3" placeholder="Contoh: Jangan dibanting, pagar warna biru..."></textarea>
                        </div>
                    </div>

                    <!-- Pembayaran -->
                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800 mb-6 flex items-center gap-2 border-b pb-4">
                            <i class="fas fa-wallet text-orange-500"></i> Metode Pembayaran
                        </h2>
                        
                        <div class="space-y-4">
                            <label class="flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-orange-500 transition bg-gray-50 has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500 has-[:checked]:ring-1 has-[:checked]:ring-orange-500">
                                <input type="radio" name="payment_method" value="transfer_bank" checked class="w-5 h-5 text-orange-600 focus:ring-orange-500 border-gray-300">
                                <div class="ml-4">
                                    <span class="block font-bold text-gray-800">Transfer Bank Manual</span>
                                    <span class="block text-xs text-gray-500 mt-1">Transfer ke rekening Desa/BUMDes, lalu upload bukti bayar.</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-orange-500 transition bg-gray-50 has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500 has-[:checked]:ring-1 has-[:checked]:ring-orange-500">
                                <input type="radio" name="payment_method" value="cod" class="w-5 h-5 text-orange-600 focus:ring-orange-500 border-gray-300">
                                <div class="ml-4">
                                    <span class="block font-bold text-gray-800">Bayar di Tempat (COD)</span>
                                    <span class="block text-xs text-gray-500 mt-1">Bayar tunai saat kurir desa sampai di lokasi Anda.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                </div>

                <!-- RINGKASAN ORDER -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-white p-6 rounded-2xl shadow-lg border border-orange-100 sticky top-24">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-4">Ringkasan Order</h3>
                        
                        <div class="space-y-3 mb-6 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                            <?php foreach ($list_produk as $item) : ?>
                            <div class="flex justify-between text-sm group">
                                <div class="flex-1 pr-4">
                                    <span class="text-gray-800 font-medium block line-clamp-1 group-hover:text-orange-600 transition"><?php echo esc_html($item->nama_produk); ?></span>
                                    <span class="text-gray-400 text-xs"><?php echo $item->qty; ?> x Rp <?php echo number_format($item->harga, 0,',','.'); ?></span>
                                </div>
                                <span class="font-bold text-gray-600">Rp <?php echo number_format($item->subtotal, 0,',','.'); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="border-t border-dashed border-gray-200 pt-4 space-y-2 mb-6">
                            <div class="flex justify-between text-gray-600">
                                <span>Subtotal</span>
                                <span>Rp <?php echo number_format($total_belanja, 0,',','.'); ?></span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Ongkos Kirim</span>
                                <span class="text-green-600 font-medium text-xs bg-green-50 px-2 py-1 rounded">Gratis (Promo Desa)</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold text-gray-900 pt-2 border-t border-gray-100 mt-2">
                                <span>Total Bayar</span>
                                <span class="text-orange-600">Rp <?php echo number_format($total_belanja, 0,',','.'); ?></span>
                            </div>
                        </div>

                        <button type="submit" name="dw_place_order" class="w-full py-4 bg-gray-900 text-white rounded-xl font-bold hover:bg-orange-600 transition-all duration-300 shadow-lg hover:shadow-orange-200 transform active:scale-[0.98]">
                            Buat Pesanan <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                        
                        <p class="text-xs text-center text-gray-400 mt-4">
                            Dengan mengklik tombol di atas, Anda menyetujui Syarat & Ketentuan kami.
                        </p>
                    </div>
                </div>

            </form>
        <?php endif; ?>
    </div>
</div>

<style>
/* Custom Scrollbar for Order Summary */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
</style>

<?php get_footer(); ?>