<?php
/**
 * Template Name: Halaman Checkout
 * Description: Form checkout dan pemrosesan pesanan.
 */

if (!session_id()) session_start();

// --- LOGIKA PEMROSESAN PESANAN (HANDLER) ---
$order_success = false;
$error_message = '';
$new_order_id  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dw_place_order'])) {
    
    // 1. Validasi Nonce & Cart
    if (!isset($_POST['dw_checkout_nonce']) || !wp_verify_nonce($_POST['dw_checkout_nonce'], 'dw_checkout_action')) {
        $error_message = 'Validasi keamanan gagal. Silakan coba lagi.';
    } elseif (empty($_SESSION['dw_cart'])) {
        $error_message = 'Keranjang belanja kosong.';
    } else {
        global $wpdb;
        $table_transaksi = $wpdb->prefix . 'dw_transaksi';
        $table_detail    = $wpdb->prefix . 'dw_transaksi_detail';
        $table_produk    = $wpdb->prefix . 'dw_produk';
        
        $user_id     = get_current_user_id(); // 0 jika guest
        $nama        = sanitize_text_field($_POST['nama_lengkap']);
        $no_hp       = sanitize_text_field($_POST['no_hp']);
        $alamat      = sanitize_textarea_field($_POST['alamat_lengkap']);
        $catatan     = sanitize_textarea_field($_POST['catatan']);
        $metode_bayar= sanitize_text_field($_POST['payment_method']); // cod / transfer
        
        // 2. Hitung Total & Siapkan Data Item
        $cart_items = $_SESSION['dw_cart'];
        $total_tagihan = 0;
        $items_to_insert = [];
        
        // Ambil data produk terbaru dari DB untuk harga akurat
        $product_ids = array_keys($cart_items);
        $ids_str = implode(',', array_map('intval', $product_ids));
        $db_products = $wpdb->get_results("SELECT id, harga, id_pedagang FROM $table_produk WHERE id IN ($ids_str)");
        
        // Grouping by pedagang (opsional, di sini kita buat 1 transaksi per checkout sederhana)
        // Atau jika sistem support multi-vendor split, logicnya akan lebih kompleks.
        // Asumsi: Single Transaction ID dengan multiple items.

        foreach ($db_products as $prod) {
            $qty = intval($cart_items[$prod->id]);
            $subtotal = $prod->harga * $qty;
            $total_tagihan += $subtotal;
            
            $items_to_insert[] = [
                'id_produk' => $prod->id,
                'qty'       => $qty,
                'harga'     => $prod->harga,
                'subtotal'  => $subtotal
            ];
        }
        
        // Generate Kode Transaksi Unik
        $invoice_code = 'INV-' . date('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        
        // 3. Simpan ke Tabel Transaksi
        $data_transaksi = [
            'invoice_code'      => $invoice_code,
            'id_pembeli'        => $user_id, // atau NULL jika guest table supports
            'nama_penerima'     => $nama,
            'no_hp_penerima'    => $no_hp,
            'alamat_pengiriman' => $alamat,
            'catatan'           => $catatan,
            'total_belanja'     => $total_tagihan,
            'total_ongkir'      => 0, // Logic ongkir bisa ditambahkan
            'total_bayar'       => $total_tagihan, // + ongkir
            'status_transaksi'  => 'menunggu_pembayaran',
            'metode_pembayaran' => $metode_bayar,
            'created_at'        => current_time('mysql')
        ];
        
        $insert = $wpdb->insert($table_transaksi, $data_transaksi);
        
        if ($insert) {
            $transaksi_id = $wpdb->insert_id;
            $new_order_id = $transaksi_id;
            
            // 4. Simpan Detail Item
            foreach ($items_to_insert as $item) {
                $wpdb->insert($table_detail, [
                    'id_transaksi' => $transaksi_id,
                    'id_produk'    => $item['id_produk'],
                    'qty'          => $item['qty'],
                    'harga_satuan' => $item['harga'],
                    'subtotal'     => $item['subtotal']
                ]);
            }
            
            // 5. Bersihkan Keranjang & Set Sukses
            unset($_SESSION['dw_cart']);
            $order_success = true;
            
            // Redirect ke halaman sukses/transaksi (Opsional)
            // wp_redirect(home_url('/transaksi?id=' . $transaksi_id)); exit;
        } else {
            $error_message = 'Gagal menyimpan pesanan ke database.';
        }
    }
}

get_header();

// --- PREPARE VIEW DATA ---
global $wpdb;
$table_produk = $wpdb->prefix . 'dw_produk';
$cart_items = isset($_SESSION['dw_cart']) ? $_SESSION['dw_cart'] : [];
$list_produk = [];
$total_belanja = 0;

if (!empty($cart_items) && !$order_success) {
    $product_ids = array_keys($cart_items);
    $ids_placeholder = implode(',', array_fill(0, count($product_ids), '%d'));
    $sql = "SELECT id, nama_produk, harga, foto_produk FROM $table_produk WHERE id IN ($ids_placeholder)";
    $raw_products = $wpdb->get_results($wpdb->prepare($sql, $product_ids));
    
    foreach ($raw_products as $prod) {
        $qty = intval($cart_items[$prod->id]);
        $prod->qty = $qty;
        $prod->subtotal = $prod->harga * $qty;
        $list_produk[] = $prod;
        $total_belanja += $prod->subtotal;
    }
}
?>

<div class="bg-[#FAFAFA] min-h-screen py-10 font-sans text-gray-800">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <?php if ($order_success) : ?>
            <!-- SUCCESS STATE -->
            <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl p-10 text-center border-t-4 border-green-500">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-3xl text-green-600"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Pesanan Berhasil Dibuat!</h1>
                <p class="text-gray-500 mb-8">Terima kasih telah berbelanja produk desa. Pesanan Anda sedang kami proses.</p>
                
                <div class="bg-gray-50 rounded-xl p-6 mb-8 text-left">
                    <p class="text-sm text-gray-500 mb-1">Nomor Order:</p>
                    <p class="text-xl font-bold text-gray-900">#<?php echo $new_order_id; ?></p>
                    <div class="border-t border-gray-200 my-4"></div>
                    <p class="text-sm text-gray-500">Silakan lakukan pembayaran dan konfirmasi melalui halaman Transaksi.</p>
                </div>

                <div class="flex gap-4 justify-center">
                    <a href="<?php echo home_url('/produk'); ?>" class="px-6 py-3 border border-gray-300 rounded-lg font-bold text-gray-600 hover:bg-gray-50">Belanja Lagi</a>
                    <a href="<?php echo home_url('/transaksi'); // Asumsi ada page ini ?>" class="px-6 py-3 bg-green-600 text-white rounded-lg font-bold hover:bg-green-700">Lihat Pesanan</a>
                </div>
            </div>

        <?php elseif (empty($cart_items)) : ?>
            <!-- EMPTY CART REDIRECT INFO -->
            <script>window.location.href = "<?php echo home_url('/cart'); ?>";</script>

        <?php else : ?>
            
            <h1 class="text-2xl font-bold mb-8 text-gray-900">Checkout Pengiriman</h1>

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
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-orange-500"></i> Alamat Pengiriman
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" required class="w-full border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Nomor WhatsApp</label>
                                <input type="tel" name="no_hp" required class="w-full border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500" placeholder="08..." value="">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Alamat Lengkap</label>
                            <textarea name="alamat_lengkap" rows="3" required class="w-full border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500" placeholder="Nama Jalan, RT/RW, Desa, Kecamatan..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Catatan Pesanan (Opsional)</label>
                            <textarea name="catatan" rows="2" class="w-full border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500" placeholder="Contoh: Jangan dibanting, rumah pagar biru..."></textarea>
                        </div>
                    </div>

                    <!-- Pembayaran -->
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <i class="fas fa-wallet text-orange-500"></i> Metode Pembayaran
                        </h2>
                        
                        <div class="space-y-3">
                            <label class="flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-orange-500 transition bg-gray-50 has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500">
                                <input type="radio" name="payment_method" value="transfer_bank" checked class="w-5 h-5 text-orange-600 focus:ring-orange-500 border-gray-300">
                                <div class="ml-4">
                                    <span class="block font-bold text-gray-800">Transfer Bank</span>
                                    <span class="block text-sm text-gray-500">BCA, Mandiri, BRI, BNI (Manual Konfirmasi)</span>
                                </div>
                            </label>
                            
                            <label class="flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-orange-500 transition bg-gray-50 has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500">
                                <input type="radio" name="payment_method" value="cod" class="w-5 h-5 text-orange-600 focus:ring-orange-500 border-gray-300">
                                <div class="ml-4">
                                    <span class="block font-bold text-gray-800">Bayar di Tempat (COD)</span>
                                    <span class="block text-sm text-gray-500">Bayar tunai saat kurir desa sampai.</span>
                                </div>
                            </label>
                        </div>
                    </div>

                </div>

                <!-- RINGKASAN ORDER -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-white p-6 rounded-2xl shadow-lg border border-orange-100 sticky top-24">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-4">Ringkasan Order</h3>
                        
                        <div class="space-y-3 mb-4 max-h-60 overflow-y-auto pr-1 custom-scrollbar">
                            <?php foreach ($list_produk as $item) : ?>
                            <div class="flex justify-between text-sm">
                                <div class="flex-1 pr-4">
                                    <span class="text-gray-800 font-medium block"><?php echo esc_html($item->nama_produk); ?></span>
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
                                <span class="text-green-600 font-medium">Gratis (Promo)</span>
                            </div>
                            <div class="flex justify-between text-lg font-bold text-gray-900 pt-2">
                                <span>Total Bayar</span>
                                <span class="text-orange-600">Rp <?php echo number_format($total_belanja, 0,',','.'); ?></span>
                            </div>
                        </div>

                        <button type="submit" name="dw_place_order" class="w-full py-4 bg-orange-600 text-white rounded-xl font-bold hover:bg-orange-700 transition shadow-lg shadow-orange-500/20">
                            Buat Pesanan
                        </button>
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