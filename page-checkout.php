<?php
/**
 * Template Name: Halaman Checkout (Promo & Payment Redirect)
 * Description: Form checkout dengan fitur kode promo dan redirect ke halaman pembayaran.
 */

if (!session_id()) session_start();

// 1. CEK LOGIN (Wajib)
if (!is_user_logged_in()) {
    $redirect = urlencode(get_permalink());
    wp_redirect(home_url('/login?redirect_to=' . $redirect));
    exit;
}

global $wpdb;
$user_id = get_current_user_id();
$current_user = wp_get_current_user();

// 2. CEK CART (Harus ada isinya)
$table_cart = $wpdb->prefix . 'dw_cart';
$cart_check = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_cart WHERE user_id = %d", $user_id));

if (!$cart_check || $cart_check == 0) {
    wp_redirect(home_url('/keranjang'));
    exit;
}

// --- PROSES ORDER (POST REQUEST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dw_place_order'])) {
    
    // Verifikasi Nonce
    if (!isset($_POST['dw_checkout_nonce']) || !wp_verify_nonce($_POST['dw_checkout_nonce'], 'dw_checkout_action')) {
        die('Security check failed');
    }

    // A. Ambil Data Cart Lengkap
    $sql = $wpdb->prepare(
        "SELECT c.*, 
                p.nama_produk, p.harga, p.berat_gram, 
                t.id as toko_id, t.nama_toko
         FROM $table_cart c
         JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
         JOIN {$wpdb->prefix}dw_pedagang t ON p.id_pedagang = t.id
         WHERE c.user_id = %d",
        $user_id
    );
    $cart_items = $wpdb->get_results($sql);

    if ($cart_items) {
        // B. Generate Kode Transaksi Utama (TRX-TIMESTAMP-USERID)
        $kode_unik = 'TRX-' . date('ymdHis') . '-' . $user_id;
        $total_transaksi = 0;
        
        // Data Input User
        $nama_penerima = sanitize_text_field($_POST['nama_penerima']);
        $no_hp         = sanitize_text_field($_POST['no_hp']);
        $alamat        = sanitize_textarea_field($_POST['alamat_lengkap']);
        $metode_bayar  = sanitize_text_field($_POST['payment_method']); 
        $kode_promo    = sanitize_text_field($_POST['kode_promo']);

        // Logika Promo Sederhana (Contoh)
        $diskon_ongkir = ($kode_promo === 'DISKONONGKIR') ? true : false;

        // C. Insert Master Transaksi (wp_dw_transaksi)
        $wpdb->insert(
            $wpdb->prefix . 'dw_transaksi',
            [
                'kode_unik' => $kode_unik,
                'id_pembeli' => $user_id,
                'total_transaksi' => 0, // Update nanti setelah hitung subtotal
                'nama_penerima' => $nama_penerima,
                'no_hp' => $no_hp,
                'alamat_lengkap' => $alamat,
                'status_transaksi' => 'menunggu_pembayaran',
                'metode_pembayaran' => $metode_bayar,
                'catatan_pembeli' => $kode_promo ? 'Promo: ' . $kode_promo : '',
                'created_at' => current_time('mysql')
            ]
        );
        $transaksi_id = $wpdb->insert_id;

        // D. Grouping & Split Order (wp_dw_transaksi_sub)
        $grouped = [];
        foreach ($cart_items as $item) {
            $grouped[$item->toko_id]['items'][] = $item;
            $grouped[$item->toko_id]['nama_toko'] = $item->nama_toko;
        }

        foreach ($grouped as $toko_id => $group) {
            $sub_total = 0;
            $ongkir = $diskon_ongkir ? 0 : 15000; // Flat rate sementara
            
            // Hitung Subtotal Toko
            foreach ($group['items'] as $item) {
                $sub_total += ($item->harga * $item->qty);
            }
            $total_toko = $sub_total + $ongkir;
            $total_transaksi += $total_toko;

            // Insert Sub Transaksi
            $wpdb->insert(
                $wpdb->prefix . 'dw_transaksi_sub',
                [
                    'id_transaksi' => $transaksi_id,
                    'id_pedagang' => $toko_id,
                    'nama_toko' => $group['nama_toko'],
                    'sub_total' => $sub_total,
                    'ongkir' => $ongkir,
                    'total_pesanan_toko' => $total_toko,
                    'status_pesanan' => 'menunggu_konfirmasi',
                    'metode_pengiriman' => 'Reguler'
                ]
            );
            $sub_id = $wpdb->insert_id;

            // Insert Items (wp_dw_transaksi_items)
            foreach ($group['items'] as $item) {
                $wpdb->insert(
                    $wpdb->prefix . 'dw_transaksi_items',
                    [
                        'id_sub_transaksi' => $sub_id,
                        'id_produk' => $item->id_produk,
                        'nama_produk' => $item->nama_produk,
                        'harga_satuan' => $item->harga,
                        'jumlah' => $item->qty,
                        'total_harga' => ($item->harga * $item->qty)
                    ]
                );
                
                // Kurangi Stok Produk
                $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}dw_produk SET stok = stok - %d, terjual = terjual + %d WHERE id = %d", $item->qty, $item->qty, $item->id_produk));
            }
        }

        // E. Update Total Transaksi Utama
        $wpdb->update(
            $wpdb->prefix . 'dw_transaksi',
            ['total_transaksi' => $total_transaksi],
            ['id' => $transaksi_id]
        );

        // F. Kosongkan Keranjang
        $wpdb->delete($table_cart, ['user_id' => $user_id]);

        // G. Redirect ke Halaman Pembayaran
        wp_redirect(home_url('/pembayaran?id=' . $kode_unik));
        exit;
    }
}

// --- AMBIL DATA CART UNTUK DISPLAY ---
$sql_cart = $wpdb->prepare(
    "SELECT c.*, p.nama_produk, p.harga, t.nama_toko 
     FROM $table_cart c
     JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
     JOIN {$wpdb->prefix}dw_pedagang t ON p.id_pedagang = t.id
     WHERE c.user_id = %d",
    $user_id
);
$cart_display = $wpdb->get_results($sql_cart);
$cart_total = 0;
foreach($cart_display as $c) $cart_total += ($c->harga * $c->qty);

get_header();
?>

<div class="bg-gray-50 min-h-screen py-10 font-sans">
    <div class="max-w-5xl mx-auto px-4">
        
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Checkout Pengiriman</h1>

        <form method="POST" action="" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <?php wp_nonce_field('dw_checkout_action', 'dw_checkout_nonce'); ?>

            <!-- KOLOM KIRI: FORM DATA DIRI -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Alamat Pengiriman -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="font-bold text-lg text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-primary"></i> Alamat Pengiriman
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Nama Penerima</label>
                            <input type="text" name="nama_penerima" value="<?php echo esc_attr($current_user->display_name); ?>" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Nomor WhatsApp</label>
                            <input type="tel" name="no_hp" placeholder="0812..." required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" rows="3" placeholder="Nama Jalan, RT/RW, Kecamatan, Kabupaten" required class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 focus:ring-primary focus:border-primary"></textarea>
                    </div>
                </div>

                <!-- Review Barang -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="font-bold text-lg text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-box text-primary"></i> Barang yang dibeli
                    </h2>
                    <div class="space-y-4">
                        <?php foreach ($cart_display as $item): ?>
                        <div class="flex justify-between items-center text-sm border-b border-gray-50 pb-2">
                            <div>
                                <div class="font-bold text-gray-800"><?php echo esc_html($item->nama_produk); ?></div>
                                <div class="text-xs text-gray-500"><?php echo $item->qty; ?> x <?php echo tema_dw_format_rupiah($item->harga); ?></div>
                                <div class="text-[10px] text-green-600 bg-green-50 inline-block px-1 rounded">Toko: <?php echo esc_html($item->nama_toko); ?></div>
                            </div>
                            <div class="font-bold text-gray-700">
                                <?php echo tema_dw_format_rupiah($item->harga * $item->qty); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>

            <!-- KOLOM KANAN: RINGKASAN & PEMBAYARAN -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 sticky top-24">
                    
                    <!-- KODE PROMO -->
                    <div class="mb-6 pb-6 border-b border-dashed border-gray-200">
                        <label class="block text-xs font-bold text-gray-500 mb-2">Punya Kode Promo?</label>
                        <div class="flex gap-2">
                            <input type="text" name="kode_promo" id="promo-input" placeholder="Masukkan kode" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-primary uppercase">
                            <button type="button" onclick="cekPromo()" class="bg-gray-800 text-white px-3 py-2 rounded-lg text-sm font-bold hover:bg-gray-700">Gunakan</button>
                        </div>
                        <p id="promo-msg" class="text-xs mt-1 hidden"></p>
                    </div>

                    <!-- METODE BAYAR -->
                    <h3 class="font-bold text-gray-800 mb-4">Metode Pembayaran</h3>
                    <div class="space-y-3 mb-6">
                        <label class="flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-green-50 transition">
                            <input type="radio" name="payment_method" value="transfer" checked class="text-primary focus:ring-primary">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-gray-700">Transfer Bank / QRIS</span>
                                <span class="block text-xs text-gray-500">Upload bukti bayar manual</span>
                            </div>
                        </label>
                        <label class="flex items-center p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-primary has-[:checked]:border-primary has-[:checked]:bg-green-50 transition">
                            <input type="radio" name="payment_method" value="cod" class="text-primary focus:ring-primary">
                            <span class="ml-3 text-sm font-bold text-gray-700">Bayar di Tempat (COD)</span>
                        </label>
                    </div>

                    <div class="border-t border-dashed border-gray-200 pt-4 mb-6">
                        <div class="flex justify-between mb-2 text-sm text-gray-500">
                            <span>Total Harga</span>
                            <span><?php echo tema_dw_format_rupiah($cart_total); ?></span>
                        </div>
                        <div class="flex justify-between mb-2 text-sm text-gray-500">
                            <span>Ongkos Kirim (Est.)</span>
                            <span id="ongkir-display">Rp 15.000</span>
                        </div>
                        <div class="flex justify-between mb-2 text-sm text-green-600 hidden" id="diskon-row">
                            <span>Diskon Promo</span>
                            <span>- Rp 15.000</span>
                        </div>
                        <div class="flex justify-between items-end mt-4 pt-4 border-t border-gray-100">
                            <span class="font-bold text-gray-800">Total Bayar</span>
                            <span class="text-xl font-bold text-primary" id="total-bayar"><?php echo tema_dw_format_rupiah($cart_total + 15000); ?></span>
                        </div>
                    </div>

                    <button type="submit" name="dw_place_order" class="block w-full bg-primary hover:bg-green-700 text-white text-center font-bold py-3.5 rounded-xl shadow-lg shadow-green-500/30 transition-all transform active:scale-95 flex justify-center items-center gap-2">
                        <span>Buat Pesanan</span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
function cekPromo() {
    const code = document.getElementById('promo-input').value.toUpperCase();
    const msg = document.getElementById('promo-msg');
    const ongkirDisplay = document.getElementById('ongkir-display');
    const diskonRow = document.getElementById('diskon-row');
    const totalDisplay = document.getElementById('total-bayar');
    
    // Logika JS Sederhana untuk Simulasi UI (Logika asli ada di PHP saat submit)
    if (code === 'DISKONONGKIR') {
        msg.innerText = 'Kode promo berhasil digunakan!';
        msg.className = 'text-xs mt-1 text-green-600 font-bold block';
        
        ongkirDisplay.style.textDecoration = 'line-through';
        ongkirDisplay.className = 'text-gray-400';
        
        diskonRow.classList.remove('hidden');
        
        // Update Total Visual (Hardcoded simulation)
        // Total asli = cart + 15000. Diskon = 15000. Jadi balik ke harga cart.
        totalDisplay.innerText = '<?php echo tema_dw_format_rupiah($cart_total); ?>'; 
    } else {
        msg.innerText = 'Kode promo tidak valid.';
        msg.className = 'text-xs mt-1 text-red-500 block';
        
        ongkirDisplay.style.textDecoration = 'none';
        ongkirDisplay.className = '';
        diskonRow.classList.add('hidden');
        totalDisplay.innerText = '<?php echo tema_dw_format_rupiah($cart_total + 15000); ?>';
    }
}
</script>

<?php get_footer(); ?>