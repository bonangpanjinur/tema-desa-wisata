<?php
/**
 * Template Name: Halaman Checkout
 * Description: Form checkout dengan logika split-order otomatis ke database (Multi-Vendor support).
 */

if (!session_id()) session_start();

// Redirect jika keranjang kosong
if (empty($_SESSION['dw_cart'])) {
    wp_redirect(home_url('/cart'));
    exit;
}

// Redirect jika belum login
if (!is_user_logged_in()) {
    // Simpan URL checkout agar setelah login balik lagi ke sini
    wp_redirect(home_url('/login?redirect_to=' . urlencode(home_url('/checkout'))));
    exit;
}

$user_id = get_current_user_id();
$current_user = wp_get_current_user();
$error_message = '';

// --- PROSES SUBMIT CHECKOUT ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dw_place_order'])) {
    
    if (!isset($_POST['dw_checkout_nonce']) || !wp_verify_nonce($_POST['dw_checkout_nonce'], 'dw_checkout_action')) {
        $error_message = 'Validasi keamanan gagal. Silakan muat ulang.';
    } else {
        global $wpdb;
        $tbl_transaksi = $wpdb->prefix . 'dw_transaksi';
        $tbl_sub       = $wpdb->prefix . 'dw_transaksi_sub';
        $tbl_detail    = $wpdb->prefix . 'dw_detail_transaksi'; // atau dw_transaksi_items sesuai skema DB
        
        // Cek nama tabel detail yang benar (antisipasi perbedaan versi activation.php)
        if ($wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}dw_transaksi_items'") == $wpdb->prefix . 'dw_transaksi_items') {
            $tbl_detail = $wpdb->prefix . 'dw_transaksi_items';
        }

        // 1. Grouping Item berdasarkan Seller (Multi-Vendor Logic)
        // Kita butuh tahu ID pedagang untuk setiap item agar bisa bikin sub-transaksi
        $grouped_cart = [];
        $grand_total = 0;
        
        $tbl_prod = $wpdb->prefix . 'dw_produk';
        $tbl_wis = $wpdb->prefix . 'dw_wisata';

        foreach ($_SESSION['dw_cart'] as $item) {
            $pid = intval($item['product_id']);
            $qty = intval($item['quantity']);
            $subtotal = $item['price'] * $qty;
            $grand_total += $subtotal;
            
            // Cari ID Pedagang/Desa pemilik item ini
            $id_pedagang_owner = 0; // 0 artinya milik Admin/Desa Pusat jika tidak ditemukan
            
            // Cek Produk
            $cek_prod = $wpdb->get_row($wpdb->prepare("SELECT id_pedagang FROM $tbl_prod WHERE id = %d", $pid));
            if ($cek_prod) {
                $id_pedagang_owner = $cek_prod->id_pedagang;
            } else {
                // Cek Wisata (Biasanya milik Desa, kita set ID khusus atau 0)
                // Untuk simplifikasi, anggap 0 atau ambil relasi desa jika tabel support
                $id_pedagang_owner = 0; 
            }

            // Masukkan ke array group
            $grouped_cart[$id_pedagang_owner]['items'][] = $item;
            if (!isset($grouped_cart[$id_pedagang_owner]['total'])) {
                $grouped_cart[$id_pedagang_owner]['total'] = 0;
            }
            $grouped_cart[$id_pedagang_owner]['total'] += $subtotal;
        }

        // 2. Insert Parent Transaksi
        $kode_unik = 'TRX-' . strtoupper(uniqid());
        $alamat_lengkap = sanitize_textarea_field($_POST['alamat_lengkap']) . ', ' . sanitize_text_field($_POST['kota']) . ' - ' . sanitize_text_field($_POST['kodepos']);

        $wpdb->insert($tbl_transaksi, [
            'id_user_pembeli'   => $user_id,
            'kode_transaksi'    => $kode_unik,
            'total_belanja'     => $grand_total,
            'status_pembayaran' => 'menunggu_pembayaran',
            'detail_pengiriman' => $alamat_lengkap,
            'metode_pembayaran' => sanitize_text_field($_POST['payment_method']),
            'no_telepon'        => sanitize_text_field($_POST['no_telepon']),
            'catatan'           => sanitize_textarea_field($_POST['catatan']),
            'created_at'        => current_time('mysql')
        ]);
        $parent_id = $wpdb->insert_id;

        if ($parent_id) {
            // 3. Loop Group untuk Insert Sub Transaksi & Detail
            foreach ($grouped_cart as $id_pedagang => $group_data) {
                
                // Buat Sub Transaksi (Per Toko)
                // Note: Pastikan tabel dw_transaksi_sub ada kolom id_pedagang
                $wpdb->insert($tbl_sub, [
                    'id_transaksi' => $parent_id, // Link ke Parent
                    'id_pedagang'  => $id_pedagang,
                    'total_pesanan_toko' => $group_data['total'],
                    'status_pesanan' => 'menunggu_konfirmasi',
                    'created_at'   => current_time('mysql')
                ]);
                $sub_id = $wpdb->insert_id;

                // Masukkan Item ke Sub Transaksi ini
                foreach ($group_data['items'] as $item) {
                    $wpdb->insert($tbl_detail, [
                        'id_transaksi' => $sub_id, // PENTING: Link ke Sub ID (bukan Parent) agar pedagang bisa lihat itemnya
                        'id_produk'    => $item['product_id'],
                        'jenis_item'   => 'produk', // Default
                        'nama_item'    => $item['name'],
                        'harga_satuan' => $item['price'],
                        'qty'          => $item['quantity'],
                        'subtotal'     => $item['price'] * $item['quantity']
                    ]);
                }
            }

            // 4. Bersihkan Cart & Redirect
            unset($_SESSION['dw_cart']);
            
            // Redirect ke halaman transaksi atau pembayaran
            wp_redirect(home_url('/transaksi')); 
            exit;
        } else {
            $error_message = 'Gagal membuat pesanan. Silakan coba lagi.';
        }
    }
}

get_header();

// Hitung total awal untuk display
$cart_display_total = 0;
foreach ($_SESSION['dw_cart'] as $c) {
    $cart_display_total += ($c['price'] * $c['quantity']);
}
?>

<div class="min-h-screen bg-gray-50 py-8 lg:py-12 font-sans">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <div class="mb-8 text-center lg:text-left">
            <h1 class="text-3xl font-extrabold text-gray-900">Checkout</h1>
            <p class="text-gray-500 mt-2">Lengkapi data pengiriman untuk menyelesaikan pesanan.</p>
        </div>

        <?php if ($error_message): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="flex flex-col lg:flex-row gap-8">
            <?php wp_nonce_field('dw_checkout_action', 'dw_checkout_nonce'); ?>

            <!-- KOLOM KIRI: Form Data -->
            <div class="flex-1 space-y-6">
                
                <!-- Section Alamat -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-sm">1</span>
                        Informasi Pengiriman
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" value="<?php echo esc_attr($current_user->display_name); ?>" required
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">No. WhatsApp / Telepon</label>
                            <input type="tel" name="no_telepon" placeholder="08..." required
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-colors">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" rows="3" required placeholder="Nama jalan, RT/RW, No. Rumah"
                                  class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-colors"></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Kota / Kabupaten</label>
                            <input type="text" name="kota" required
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Kode Pos</label>
                            <input type="text" name="kodepos" required
                                   class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-colors">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                        <input type="text" name="catatan" placeholder="Misal: Titipkan di pos satpam"
                               class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-colors">
                    </div>
                </div>

                <!-- Section Pembayaran -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <span class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-sm">2</span>
                        Metode Pembayaran
                    </h3>
                    
                    <div class="space-y-3">
                        <label class="flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-orange-50 hover:border-orange-200 transition-all group">
                            <input type="radio" name="payment_method" value="transfer_bank" checked class="w-5 h-5 text-orange-600 focus:ring-orange-500 border-gray-300">
                            <div class="ml-4 flex-1">
                                <span class="block font-bold text-gray-900 group-hover:text-orange-700">Transfer Bank / QRIS</span>
                                <span class="block text-sm text-gray-500">Bayar via transfer manual atau scan QRIS Desa</span>
                            </div>
                            <i class="fas fa-university text-gray-400 group-hover:text-orange-500"></i>
                        </label>

                        <label class="flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:bg-orange-50 hover:border-orange-200 transition-all group">
                            <input type="radio" name="payment_method" value="cod" class="w-5 h-5 text-orange-600 focus:ring-orange-500 border-gray-300">
                            <div class="ml-4 flex-1">
                                <span class="block font-bold text-gray-900 group-hover:text-orange-700">Bayar di Tempat (COD)</span>
                                <span class="block text-sm text-gray-500">Bayar tunai saat barang sampai</span>
                            </div>
                            <i class="fas fa-money-bill-wave text-gray-400 group-hover:text-orange-500"></i>
                        </label>
                    </div>
                </div>

            </div>

            <!-- KOLOM KANAN: Ringkasan Pesanan -->
            <div class="w-full lg:w-96 flex-shrink-0">
                <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 sticky top-24">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Ringkasan Pesanan</h3>
                    
                    <div class="max-h-60 overflow-y-auto pr-2 custom-scrollbar space-y-3 mb-6">
                        <?php foreach ($_SESSION['dw_cart'] as $item): ?>
                            <div class="flex gap-3">
                                <div class="w-12 h-12 bg-gray-100 rounded-md overflow-hidden flex-shrink-0">
                                    <?php if($item['image']): ?>
                                        <img src="<?php echo esc_url($item['image']); ?>" class="w-full h-full object-cover">
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-bold text-gray-800 line-clamp-1"><?php echo esc_html($item['name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo $item['quantity']; ?> x Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="text-sm font-bold text-gray-700">
                                    Rp <?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="border-t border-dashed border-gray-200 pt-4 space-y-2">
                        <div class="flex justify-between text-gray-600">
                            <span>Total Item</span>
                            <span>Rp <?php echo number_format($cart_display_total, 0, ',', '.'); ?></span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Ongkos Kirim</span>
                            <span class="text-green-600 font-bold text-xs bg-green-50 px-2 py-1 rounded">Gratis (Promo)</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 mt-2 border-t border-gray-100">
                            <span class="font-bold text-gray-900 text-lg">Total Bayar</span>
                            <span class="font-bold text-2xl text-orange-600">Rp <?php echo number_format($cart_display_total, 0, ',', '.'); ?></span>
                        </div>
                    </div>

                    <button type="submit" name="dw_place_order" class="w-full mt-6 py-4 bg-gray-900 hover:bg-orange-600 text-white font-bold rounded-xl shadow-lg hover:shadow-orange-200 transition-all duration-300 transform hover:-translate-y-1 flex justify-center items-center gap-2">
                        <span>Buat Pesanan</span>
                        <i class="fas fa-check-circle"></i>
                    </button>
                    
                    <p class="text-xs text-gray-400 text-center mt-4">
                        Dengan menekan tombol di atas, Anda menyetujui syarat & ketentuan Desa Wisata.
                    </p>
                </div>
            </div>

        </form>
    </div>
</div>

<style>
/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { bg: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 20px; }
</style>

<?php get_footer(); ?>