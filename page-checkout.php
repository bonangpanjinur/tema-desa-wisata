<?php
/**
 * Template Name: Checkout Marketplace (Complete Logic)
 * Description: Checkout dengan perbaikan penangkapan Kurir & Logika Redirect Tunai.
 */

if (!session_id()) session_start();

// Pastikan Login
if (!is_user_logged_in()) {
    $redirect = urlencode(get_permalink());
    wp_redirect(home_url('/login?redirect_to=' . $redirect));
    exit;
}

get_header();

global $wpdb;
$user_id    = get_current_user_id();
$user_wp    = wp_get_current_user();
$roles      = (array) $user_wp->roles;
$session_id = session_id();

// --- 1. PROSES PEMBUATAN PESANAN (POST HANDLER) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dw_place_order'])) {
    
    // Verifikasi Nonce Keamanan
    if (!isset($_POST['dw_checkout_nonce']) || !wp_verify_nonce($_POST['dw_checkout_nonce'], 'dw_checkout_action')) {
        wp_die('Keamanan tidak valid. Silakan coba lagi.');
    }

    $selected_cart_ids = isset($_POST['cart_ids']) ? array_map('intval', $_POST['cart_ids']) : [];
    if (empty($selected_cart_ids)) {
        wp_die('Tidak ada produk yang dipilih untuk checkout.');
    }

    // Ambil data formulir
    $nama_penerima = sanitize_text_field($_POST['nama_penerima']);
    $no_hp         = sanitize_text_field($_POST['no_hp']);
    $alamat_lengkap = sanitize_textarea_field($_POST['alamat_lengkap']);
    $provinsi      = sanitize_text_field($_POST['provinsi_nama']);
    $kabupaten     = sanitize_text_field($_POST['kabupaten_nama']);
    $kecamatan     = sanitize_text_field($_POST['kecamatan_nama']);
    $kelurahan     = sanitize_text_field($_POST['kelurahan_nama']);
    $kode_pos      = sanitize_text_field($_POST['kode_pos']);
    $metode_bayar  = sanitize_text_field($_POST['payment_method']);

    // Tangkap data kurir (Array: [toko_id => value])
    $post_kurir  = isset($_POST['kurir']) ? $_POST['kurir'] : []; 
    $post_ongkir = isset($_POST['ongkir_value']) ? $_POST['ongkir_value'] : [];

    // Ambil item keranjang yang dipilih
    $ids_placeholder = implode(',', array_fill(0, count($selected_cart_ids), '%d'));
    $cart_sql = $wpdb->prepare(
        "SELECT c.*, p.nama_produk, p.id_pedagang, p.foto_utama, p.berat_gram,
                COALESCE(v.harga_variasi, p.harga) as final_price,
                v.deskripsi_variasi
         FROM {$wpdb->prefix}dw_cart c
         JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
         LEFT JOIN {$wpdb->prefix}dw_produk_variasi v ON c.id_variasi = v.id
         WHERE c.id IN ($ids_placeholder)",
        $selected_cart_ids
    );
    $selected_items = $wpdb->get_results($cart_sql);

    if ($selected_items) {
        $total_produk = 0;
        $total_ongkir = 0;
        $biaya_layanan = 1000;
        $items_by_toko = [];

        // Kelompokkan item per toko
        foreach ($selected_items as $item) {
            $items_by_toko[$item->id_pedagang][] = $item;
            $total_produk += ($item->final_price * $item->qty);
        }

        // Hitung total ongkir dari input POST
        foreach ($post_ongkir as $ongkir_val) {
            $total_ongkir += floatval($ongkir_val);
        }

        $grand_total = $total_produk + $total_ongkir + $biaya_layanan;
        $kode_unik = 'TRX-' . strtoupper(wp_generate_password(8, false));

        // A. SIMPAN KE TABEL TRANSAKSI UTAMA (MASTER)
        $wpdb->insert("{$wpdb->prefix}dw_transaksi", [
            'kode_unik'         => $kode_unik,
            'id_pembeli'        => $user_id,
            'total_produk'      => $total_produk,
            'total_ongkir'      => $total_ongkir,
            'biaya_layanan'     => $biaya_layanan,
            'total_transaksi'   => $grand_total,
            'nama_penerima'     => $nama_penerima,
            'no_hp'             => $no_hp,
            'alamat_lengkap'    => $alamat_lengkap,
            'provinsi'          => $provinsi,
            'kabupaten'         => $kabupaten,
            'kecamatan'         => $kecamatan,
            'kelurahan'         => $kelurahan,
            'kode_pos'          => $kode_pos,
            'metode_pembayaran' => $metode_bayar,
            'status_transaksi'  => 'menunggu_pembayaran',
            'created_at'        => current_time('mysql'),
            'batas_bayar'       => date('Y-m-d H:i:s', strtotime('+24 hours'))
        ]);
        $trx_id = $wpdb->insert_id;

        // B. SIMPAN KE TABEL SUB TRANSAKSI (PER TOKO)
        foreach ($items_by_toko as $toko_id => $toko_items) {
            $sub_total_toko = 0;
            foreach ($toko_items as $ti) {
                $sub_total_toko += ($ti->final_price * $ti->qty);
            }

            $ongkir_toko = floatval($post_ongkir[$toko_id] ?? 0);
            
            // PERBAIKAN: Pastikan ID Toko sesuai untuk menangkap metode_pengiriman
            $metode_kirim_toko = isset($post_kurir[$toko_id]) ? sanitize_text_field($post_kurir[$toko_id]) : 'pickup';
            
            $nama_toko = $wpdb->get_var($wpdb->prepare("SELECT nama_toko FROM {$wpdb->prefix}dw_pedagang WHERE id = %d", $toko_id));

            $wpdb->insert("{$wpdb->prefix}dw_transaksi_sub", [
                'id_transaksi'       => $trx_id,
                'id_pedagang'        => $toko_id,
                'nama_toko'          => $nama_toko,
                'sub_total'          => $sub_total_toko,
                'ongkir'             => $ongkir_toko,
                'total_pesanan_toko' => $sub_total_toko + $ongkir_toko,
                'metode_pengiriman'  => $metode_kirim_toko, // Data sudah tertangkap disini
                'status_pesanan'     => 'menunggu_konfirmasi',
                'created_at'         => current_time('mysql')
            ]);
            $sub_id = $wpdb->insert_id;

            // C. SIMPAN KE TABEL ITEM TRANSAKSI
            foreach ($toko_items as $ti) {
                $wpdb->insert("{$wpdb->prefix}dw_transaksi_items", [
                    'id_sub_transaksi' => $sub_id,
                    'id_produk'        => $ti->id_produk,
                    'id_variasi'       => $ti->id_variasi,
                    'nama_produk'      => $ti->nama_produk,
                    'foto_snapshot'    => $ti->foto_utama,
                    'berat_snapshot'   => $ti->berat_gram,
                    'nama_variasi'     => $ti->deskripsi_variasi,
                    'harga_satuan'     => $ti->final_price,
                    'jumlah'           => $ti->qty,
                    'total_harga'      => $ti->final_price * $ti->qty
                ]);
            }
        }

        // Hapus item dari keranjang setelah berhasil checkout
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}dw_cart WHERE id IN ($ids_placeholder)", $selected_cart_ids));

        // LOGIKA REDIRECT BERDASARKAN METODE PEMBAYARAN
        if ($metode_bayar === 'tunai') {
            // Redirect ke halaman sukses dengan instruksi kasir
            wp_redirect(home_url('/terima-kasih?id=' . $kode_unik . '&status=cash'));
        } else {
            // Redirect ke halaman pembayaran online/transfer
            wp_redirect(home_url('/pembayaran?id=' . $kode_unik));
        }
        exit;
    }
}

// --- 2. PENGAMBILAN DATA ALAMAT OTOMATIS BERDASARKAN ROLE ---
// (Bagian ini tetap sama seperti kode sebelumnya untuk mengisi formulir otomatis)
$data_source = null;
$role_type   = 'pembeli'; 

if (in_array('pedagang', $roles)) {
    $role_type = 'pedagang';
    $data_source = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_pedagang WHERE id_user = %d", $user_id));
} elseif (in_array('admin_desa', $roles) || in_array('editor_desa', $roles)) {
    $role_type = 'desa';
    $data_source = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_desa WHERE id_user_desa = %d", $user_id));
} elseif (in_array('verifikator_umkm', $roles)) {
    $role_type = 'verifikator';
    $data_source = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_verifikator WHERE id_user = %d", $user_id));
} else {
    $data_source = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_pembeli WHERE id_user = %d", $user_id));
}

$nama_penerima = $user_wp->display_name;
$no_hp = ''; $alamat_detail = ''; $kode_pos = '';
$prov_id = ''; $kota_id = ''; $kec_id = ''; $kel_id = '';
$prov_nm = ''; $kota_nm = ''; $kec_nm = ''; $kel_nm = '';

if ($data_source) {
    switch ($role_type) {
        case 'pedagang':
            $nama_penerima = $data_source->nama_pemilik; $no_hp = $data_source->nomor_wa;
            $alamat_detail = $data_source->alamat_lengkap;
            $prov_nm = $data_source->provinsi_nama; $kota_nm = $data_source->kabupaten_nama;
            $kec_nm = $data_source->kecamatan_nama; $kel_nm = $data_source->kelurahan_nama;
            break;
        case 'desa':
            $nama_penerima = $data_source->nama_desa; $no_hp = get_user_meta($user_id, 'billing_phone', true);
            $alamat_detail = $data_source->alamat_lengkap;
            $prov_nm = $data_source->provinsi; $kota_nm = $data_source->kabupaten;
            $kec_nm = $data_source->kecamatan; $kel_nm = $data_source->kelurahan;
            break;
        default:
            $nama_penerima = $data_source->nama_lengkap;
            $no_hp = isset($data_source->nomor_wa) ? $data_source->nomor_wa : (isset($data_source->no_hp) ? $data_source->no_hp : '');
            $alamat_detail = $data_source->alamat_lengkap;
            $prov_nm = $data_source->provinsi; $kota_nm = $data_source->kabupaten;
            $kec_nm = $data_source->kecamatan; $kel_nm = $data_source->kelurahan;
            break;
    }
    $kode_pos = $data_source->kode_pos;
    $prov_id = $data_source->api_provinsi_id; $kota_id = $data_source->api_kabupaten_id;
    $kec_id = $data_source->api_kecamatan_id; $kel_id = $data_source->api_kelurahan_id;
}

// --- 3. AMBIL DATA CART ---
$selected_cart_ids = isset($_POST['cart_ids']) ? array_map('intval', $_POST['cart_ids']) : [];
if (empty($selected_cart_ids)) {
    echo '<div class="bg-white min-h-screen flex flex-col items-center justify-center p-10 font-sans text-center">
            <h2 class="text-xl font-bold mb-2">Keranjang Kosong</h2>
            <a href="'.home_url('/keranjang').'" class="text-primary underline">Kembali</a>
          </div>';
    get_footer(); exit;
}

$ids_placeholder = implode(',', array_fill(0, count($selected_cart_ids), '%d'));
$sql = "SELECT c.id as cart_id, c.qty, c.id_produk, c.id_variasi,
               p.nama_produk, p.berat_gram, p.foto_utama, 
               COALESCE(v.harga_variasi, p.harga) as final_price,
               v.deskripsi_variasi,
               t.id as toko_id, t.nama_toko, t.kabupaten_nama as asal_kota, 
               t.api_kecamatan_id as merchant_kec_id,
               t.shipping_ojek_lokal_aktif, t.shipping_ojek_lokal_zona,
               t.shipping_nasional_aktif, t.allow_pesan_di_tempat
        FROM {$wpdb->prefix}dw_cart c
        JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
        LEFT JOIN {$wpdb->prefix}dw_produk_variasi v ON c.id_variasi = v.id
        JOIN {$wpdb->prefix}dw_pedagang t ON p.id_pedagang = t.id
        WHERE c.id IN ($ids_placeholder) 
        ORDER BY t.nama_toko ASC";

$params = $selected_cart_ids;
$items = $wpdb->get_results($wpdb->prepare($sql, $params));

$grouped_checkout = [];
$grand_total_barang = 0;

foreach ($items as $item) {
    $subtotal = $item->qty * $item->final_price;
    $grouped_checkout[$item->toko_id]['toko'] = [
        'nama' => $item->nama_toko,
        'kota' => $item->asal_kota,
        'kec_id' => $item->merchant_kec_id,
        'ojek_aktif' => $item->shipping_ojek_lokal_aktif,
        'ojek_zona' => $item->shipping_ojek_lokal_zona,
        'nasional_aktif' => $item->shipping_nasional_aktif,
        'pickup_aktif' => $item->allow_pesan_di_tempat
    ];
    $grouped_checkout[$item->toko_id]['items'][] = $item;
    $grand_total_barang += $subtotal;
}
?>

<div class="bg-gray-50 min-h-screen py-10 font-sans">
    <div class="max-w-6xl mx-auto px-4">
        
        <h1 class="text-2xl font-bold mb-6 text-gray-800 flex items-center gap-2">
            <span class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm"><i class="fas fa-check"></i></span>
            Konfirmasi Pesanan
        </h1>

        <form id="form-checkout" action="" method="POST" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            <?php wp_nonce_field('dw_checkout_action', 'dw_checkout_nonce'); ?>
            <input type="hidden" name="dw_place_order" value="1">
            
            <?php foreach ($selected_cart_ids as $cid) : ?>
                <input type="hidden" name="cart_ids[]" value="<?php echo $cid; ?>">
            <?php endforeach; ?>

            <div class="lg:col-span-8 space-y-6">
                <!-- Alamat Pengiriman -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 border-b pb-3">
                        <i class="fas fa-map-marker-alt text-primary"></i> Alamat Pengiriman
                    </h3>
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nama Penerima</label>
                                <input type="text" name="nama_penerima" value="<?php echo esc_attr($nama_penerima); ?>" class="w-full border-gray-300 rounded-lg text-sm" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">No. WhatsApp</label>
                                <input type="text" name="no_hp" value="<?php echo esc_attr($no_hp); ?>" class="w-full border-gray-300 rounded-lg text-sm" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Alamat Lengkap</label>
                            <textarea name="alamat_lengkap" rows="2" class="w-full border-gray-300 rounded-lg text-sm" required><?php echo esc_textarea($alamat_detail); ?></textarea>
                        </div>
                        <!-- Dropdown Wilayah (API) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Provinsi</label>
                                <select name="provinsi_id" id="region-provinsi" class="w-full border-gray-300 rounded-lg text-sm" data-selected="<?php echo esc_attr($prov_id); ?>" required><option value="">Memuat...</option></select>
                                <input type="hidden" name="provinsi_nama" id="provinsi_nama" value="<?php echo esc_attr($prov_nm); ?>">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Kota/Kabupaten</label>
                                <select name="kota_id" id="region-kabupaten" class="w-full border-gray-300 rounded-lg text-sm" data-selected="<?php echo esc_attr($kota_id); ?>" disabled required><option value="">Pilih Provinsi Dulu</option></select>
                                <input type="hidden" name="kabupaten_nama" id="kabupaten_nama" value="<?php echo esc_attr($kota_nm); ?>">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Kecamatan</label>
                                <select name="kecamatan_id" id="region-kecamatan" class="w-full border-gray-300 rounded-lg text-sm" data-selected="<?php echo esc_attr($kec_id); ?>" disabled required><option value="">Pilih Kota Dulu</option></select>
                                <input type="hidden" name="kecamatan_nama" id="kecamatan_nama" value="<?php echo esc_attr($kec_nm); ?>">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Kelurahan / Desa</label>
                                <select name="kelurahan_id" id="region-kelurahan" class="w-full border-gray-300 rounded-lg text-sm" data-selected="<?php echo esc_attr($kel_id); ?>" disabled required><option value="">Pilih Kecamatan Dulu</option></select>
                                <input type="hidden" name="kelurahan_nama" id="kelurahan_nama" value="<?php echo esc_attr($kel_nm); ?>">
                            </div>
                        </div>
                        <div class="md:w-1/3">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Kode Pos</label>
                            <input type="text" name="kode_pos" id="kode_pos" value="<?php echo esc_attr($kode_pos); ?>" class="w-full border-gray-300 rounded-lg text-sm">
                        </div>
                    </div>
                </div>

                <!-- List Produk per Toko -->
                <?php foreach ($grouped_checkout as $toko_id => $group): $toko = $group['toko']; ?>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="border-b border-gray-100 pb-3 mb-4 flex justify-between items-center">
                        <h4 class="font-bold text-gray-700 flex items-center gap-2"><i class="fas fa-store text-blue-500"></i> <?php echo esc_html($toko['nama']); ?></h4>
                        <span class="text-xs bg-gray-100 text-gray-500 px-2 py-1 rounded">Asal: <?php echo esc_html($toko['kota']); ?></span>
                    </div>

                    <div class="space-y-4 mb-5">
                        <?php foreach ($group['items'] as $item): ?>
                            <div class="flex gap-4 items-center">
                                <img src="<?php echo esc_url($item->foto_utama); ?>" class="w-16 h-16 bg-gray-100 rounded-lg object-cover shrink-0">
                                <div class="flex-grow">
                                    <h5 class="text-sm font-bold text-gray-800 line-clamp-1"><?php echo esc_html($item->nama_produk); ?></h5>
                                    <div class="flex justify-between items-center mt-1">
                                        <span class="text-xs text-gray-500"><?php echo $item->qty; ?> x <?php echo tema_dw_format_rupiah($item->final_price); ?></span>
                                        <span class="text-sm font-bold text-gray-700"><?php echo tema_dw_format_rupiah($item->qty * $item->final_price); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Pilih Metode Pengiriman</label>
                        <!-- NAME kurir[id_toko] adalah kunci agar data tertangkap per toko -->
                        <select name="kurir[<?php echo $toko_id; ?>]" 
                                class="w-full text-sm border-gray-300 rounded-lg cursor-pointer shipping-dropdown" 
                                required
                                data-merchant="<?php echo $toko_id; ?>"
                                data-merchant-kecamatan="<?php echo esc_attr($toko['kec_id']); ?>"
                                data-ojek-zones='<?php echo htmlspecialchars($toko['ojek_zona'] ?? '{}', ENT_QUOTES, 'UTF-8'); ?>'>
                            <option value="">-- Pilih Kurir --</option>
                            <?php if($toko['pickup_aktif']): ?><option value="pickup">Ambil Sendiri (Gratis)</option><?php endif; ?>
                            <?php if($toko['ojek_aktif']): ?><option value="ojek">Ojek Lokal</option><?php endif; ?>
                            <?php if($toko['nasional_aktif']): ?><option value="ekspedisi">Ekspedisi (JNE/POS)</option><?php endif; ?>
                        </select>
                        <div class="mt-3 flex justify-between items-center border-t border-slate-200 pt-2">
                            <span class="text-xs text-gray-500" id="info-text-<?php echo $toko_id; ?>">Ongkir:</span>
                            <div class="text-sm font-bold text-primary" id="ongkir-display-<?php echo $toko_id; ?>">Rp 0</div>
                        </div>
                        <input type="hidden" name="ongkir_value[<?php echo $toko_id; ?>]" id="ongkir-value-<?php echo $toko_id; ?>" value="0">
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Metode Pembayaran -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 border-b pb-3"><i class="fas fa-wallet text-primary"></i> Metode Pembayaran</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Transfer -->
                        <label class="border-2 border-gray-200 p-4 rounded-xl cursor-pointer hover:bg-blue-50 hover:border-blue-200 flex flex-col gap-2 transition">
                            <input type="radio" name="payment_method" value="transfer" required checked class="w-4 h-4 text-primary">
                            <div><span class="font-bold block text-sm">Transfer / QRIS</span><span class="text-[10px] text-gray-500">Konfirmasi via Upload Bukti</span></div>
                        </label>
                        <!-- Tunai -->
                        <label class="border-2 border-gray-200 p-4 rounded-xl cursor-pointer hover:bg-yellow-50 hover:border-yellow-200 flex flex-col gap-2 transition">
                            <input type="radio" name="payment_method" value="tunai" class="w-4 h-4 text-primary">
                            <div><span class="font-bold block text-sm">Tunai (Bayar Kasir)</span><span class="text-[10px] text-gray-500">Bayar langsung di kasir toko</span></div>
                        </label>
                        <!-- COD -->
                        <label class="border-2 border-gray-200 p-4 rounded-xl cursor-pointer hover:bg-gray-50 flex flex-col gap-2 transition opacity-60">
                            <input type="radio" name="payment_method" value="cod" class="w-4 h-4 text-primary">
                            <div><span class="font-bold block text-sm">COD</span><span class="text-[10px] text-gray-500">Bayar saat barang sampai</span></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Summary Samping -->
            <div class="lg:col-span-4">
                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 sticky top-24">
                    <h3 class="font-bold text-lg mb-4">Ringkasan Biaya</h3>
                    <div class="space-y-3 text-sm text-gray-600 mb-6">
                        <div class="flex justify-between"><span>Total Harga</span><span class="font-bold text-gray-800"><?php echo tema_dw_format_rupiah($grand_total_barang); ?></span></div>
                        <div class="flex justify-between"><span>Ongkos Kirim</span><span class="font-bold text-gray-800" id="summary-ongkir">Rp 0</span></div>
                        <div class="flex justify-between"><span>Biaya Layanan</span><span class="font-bold text-gray-800">Rp 1.000</span></div>
                    </div>
                    <div class="border-t border-dashed border-gray-300 pt-4 mb-6">
                        <div class="flex justify-between items-end">
                            <span class="font-bold text-gray-800 text-base">Total Tagihan</span>
                            <span class="text-2xl font-extrabold text-primary" id="summary-grand-total"><?php echo tema_dw_format_rupiah($grand_total_barang + 1000); ?></span>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-4 rounded-xl shadow-lg transition-all transform active:scale-95">Buat Pesanan <i class="fas fa-arrow-right ml-2"></i></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const baseTotalBarang = <?php echo $grand_total_barang; ?>;
    const biayaLayanan = 1000;
    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';

    document.addEventListener('DOMContentLoaded', function() {
        
        // --- LOGIKA FORM WILAYAH ---
        function loadRegion(action, parentId, target, ph) {
            let url = `${ajaxUrl}?action=${action}`;
            if(parentId) url += `&${parentId.key}=${parentId.value}`;
            target.innerHTML = '<option value="">Memuat...</option>'; target.disabled = true;
            fetch(url).then(r=>r.json()).then(res=>{
                if(res.success) {
                    target.innerHTML = `<option value="">${ph}</option>`;
                    res.data.forEach(item => {
                        const sel = target.dataset.selected == item.id ? 'selected' : '';
                        target.innerHTML += `<option value="${item.id}" data-name="${item.name}" ${sel}>${item.name}</option>`;
                    });
                    target.disabled = false;
                    if(target.dataset.selected) { target.dispatchEvent(new Event('change')); target.dataset.selected=''; }
                }
            });
        }

        const prov = document.getElementById('region-provinsi');
        const kab = document.getElementById('region-kabupaten');
        const kec = document.getElementById('region-kecamatan');
        const kel = document.getElementById('region-kelurahan');

        loadRegion('dw_fetch_provinces', null, prov, 'Pilih Provinsi');

        prov.addEventListener('change', function(){ 
            document.getElementById('provinsi_nama').value = this.options[this.selectedIndex].text;
            if(this.value) loadRegion('dw_fetch_regencies', {key:'province_id', value:this.value}, kab, 'Pilih Kota'); 
        });
        kab.addEventListener('change', function(){ 
            document.getElementById('kabupaten_nama').value = this.options[this.selectedIndex].text;
            if(this.value) loadRegion('dw_fetch_districts', {key:'regency_id', value:this.value}, kec, 'Pilih Kecamatan'); 
        });
        kec.addEventListener('change', function(){ 
            document.getElementById('kecamatan_nama').value = this.options[this.selectedIndex].text;
            calculateShippingAll(); 
            if(this.value) loadRegion('dw_fetch_villages', {key:'district_id', value:this.value}, kel, 'Pilih Desa'); 
        });
        kel.addEventListener('change', function(){ 
            document.getElementById('kelurahan_nama').value = this.options[this.selectedIndex].text;
            calculateShippingAll();
        });

        // --- LOGIKA ONGKIR ---
        const dropdowns = document.querySelectorAll('.shipping-dropdown');
        const dispOngkir = document.getElementById('summary-ongkir');
        const dispTotal = document.getElementById('summary-grand-total');

        function formatRupiah(num) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num);
        }

        function calculateShippingAll() {
            let totalOngkir = 0;
            const userKecId = kec.value; 
            const userKelId = kel.value;

            dropdowns.forEach(sel => {
                const mid = sel.dataset.merchant;
                const merchKec = sel.dataset.merchantKecamatan;
                const costDisplay = document.getElementById(`ongkir-display-${mid}`);
                const costInput = document.getElementById(`ongkir-value-${mid}`);
                
                let cost = 0;
                let note = 'Gratis';

                if(sel.value === 'pickup') {
                    cost = 0;
                    note = '<span class="text-green-600">Ambil Sendiri</span>';
                } 
                else if(sel.value === 'ekspedisi') {
                    cost = 0; 
                    note = '<span class="text-orange-500">Bayar di Tempat</span>';
                }
                else if(sel.value === 'ojek') {
                    const zones = JSON.parse(sel.dataset.ojekZones);
                    let foundCost = null;

                    if (userKecId && merchKec) {
                        if (String(userKecId) === String(merchKec)) {
                            // Cek desa terdekat
                            const z = zones.satu_kecamatan;
                            if(z) {
                                if (z.dekat && z.dekat.desa_ids && z.dekat.desa_ids.includes(userKelId)) {
                                    foundCost = parseInt(z.dekat.harga);
                                } else {
                                    foundCost = parseInt(z.jauh?.harga || 0); 
                                }
                            }
                        } else {
                            // Cek beda kecamatan
                            const z = zones.beda_kecamatan;
                            if(z) {
                                if (z.dekat && z.dekat.kecamatan_ids && z.dekat.kecamatan_ids.includes(userKecId)) {
                                    foundCost = parseInt(z.dekat.harga);
                                } else if (z.jauh && z.jauh.kecamatan_ids && z.jauh.kecamatan_ids.includes(userKecId)) {
                                    foundCost = parseInt(z.jauh.harga);
                                }
                            }
                        }
                    }

                    if (foundCost !== null && foundCost > 0) {
                        cost = foundCost;
                        note = formatRupiah(cost);
                    } else if(!userKecId) {
                        note = '<span class="text-red-500 text-[10px]">Pilih Alamat Dulu</span>';
                    } else {
                        note = '<span class="text-red-500 text-[10px]">Diluar Jangkauan</span>';
                    }
                }

                costDisplay.innerHTML = note;
                costInput.value = cost;
                totalOngkir += cost;
            });

            dispOngkir.innerText = formatRupiah(totalOngkir);
            dispTotal.innerText = formatRupiah(baseTotalBarang + totalOngkir + biayaLayanan);
        }

        dropdowns.forEach(s => s.addEventListener('change', calculateShippingAll));
    });
</script>

<?php get_footer(); ?>