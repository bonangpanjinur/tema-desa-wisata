<?php
/**
 * Template Name: Halaman Checkout (Smart Logistics v3)
 * Description: Checkout dengan urutan pengiriman di atas, data wilayah API (AJAX), dan auto-fill WA.
 */

if (!session_id()) session_start();

if (!is_user_logged_in()) {
    $redirect = urlencode(get_permalink());
    wp_redirect(home_url('/login?redirect_to=' . $redirect));
    exit;
}

get_header();

global $wpdb;
$user_id = get_current_user_id();
$user_data_wp = get_userdata($user_id);

// --- 1. AMBIL ALAMAT DARI DATABASE ---
// Prioritas: Tabel dw_user_alamat (jika ada fitur multi alamat), lalu dw_pembeli, lalu kosong.
$alamat_db = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}user_alamat WHERE user_id = %d AND is_default = 1", $user_id));

// Data Pembeli Utama (untuk backup No HP)
$pembeli_db = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pembeli WHERE id_user = %d", $user_id));

if (!$alamat_db && $pembeli_db) {
    // Fallback ke profil pembeli utama jika belum ada alamat spesifik
    $alamat_db = $pembeli_db;
}

// Siapkan variabel alamat default
$nama_penerima = $alamat_db->nama_penerima ?? $alamat_db->nama_lengkap ?? $user_data_wp->display_name;
// Ambil NO HP dari profil pembeli jika di alamat kosong
$no_hp         = !empty($alamat_db->no_hp) ? $alamat_db->no_hp : ($pembeli_db->no_hp ?? ''); 

$alamat_lengkap= $alamat_db->alamat_lengkap ?? '';
// ID wilayah dari DB (jika ada) untuk pre-select dropdown
$provinsi_id   = $alamat_db->api_provinsi_id ?? '';
$kabupaten_id  = $alamat_db->api_kabupaten_id ?? '';
$kecamatan_id  = $alamat_db->api_kecamatan_id ?? '';
$kelurahan_id  = $alamat_db->api_kelurahan_id ?? '';
$kode_pos      = $alamat_db->kode_pos ?? '';

// --- 2. AMBIL ITEM KERANJANG (GROUP BY PEDAGANG) ---
$cart_items = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, p.nama_produk, p.harga, p.berat_gram, p.foto_utama, p.id_pedagang,
            m.nama_toko, m.no_rekening, m.nama_bank, m.atas_nama_rekening, 
            m.qris_image_url, m.shipping_ojek_lokal_aktif, m.shipping_ojek_lokal_zona,
            m.shipping_nasional_aktif, m.allow_pesan_di_tempat,
            m.api_kecamatan_id as merchant_kec_id
     FROM {$wpdb->prefix}dw_cart c 
     JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
     JOIN {$wpdb->prefix}dw_pedagang m ON p.id_pedagang = m.id
     WHERE c.user_id = %d",
    $user_id
));

if (empty($cart_items)) {
    echo '<script>window.location.href="'.home_url('/keranjang').'";</script>';
    exit;
}

// Kelompokkan item berdasarkan Toko (Merchant)
$cart_by_merchant = [];
$total_berat_global = 0;
$subtotal_global = 0;

foreach ($cart_items as $item) {
    $mid = $item->id_pedagang;
    if (!isset($cart_by_merchant[$mid])) {
        $cart_by_merchant[$mid] = [
            'info' => [
                'id' => $mid,
                'nama_toko' => $item->nama_toko,
                'merchant_kec_id' => $item->merchant_kec_id, // Penting untuk logika zona 1 kecamatan
                'bank' => [
                    'bank' => $item->nama_bank,
                    'rek' => $item->no_rekening,
                    'an' => $item->atas_nama_rekening
                ],
                'qris' => $item->qris_image_url,
                'shipping' => [
                    'ojek' => $item->shipping_ojek_lokal_aktif,
                    'ojek_zona' => $item->shipping_ojek_lokal_zona, // JSON String
                    'kurir' => $item->shipping_nasional_aktif,
                    'pickup' => $item->allow_pesan_di_tempat
                ]
            ],
            'items' => [],
            'subtotal' => 0,
            'berat' => 0
        ];
    }
    
    $cart_by_merchant[$mid]['items'][] = $item;
    $cart_by_merchant[$mid]['subtotal'] += ($item->harga * $item->qty);
    $cart_by_merchant[$mid]['berat'] += ($item->berat_gram * $item->qty);
    
    $subtotal_global += ($item->harga * $item->qty);
    $total_berat_global += ($item->berat_gram * $item->qty);
}
?>

<style>
    /* Styling Checkout Modern */
    body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
    
    .checkout-section { background: white; border-radius: 16px; padding: 24px; margin-bottom: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
    .checkout-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 10px; }
    .checkout-title i { color: #16a34a; font-size: 1.2rem; }
    
    /* Form Elements */
    .form-group { margin-bottom: 18px; position: relative; }
    .form-label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 8px; }
    .form-input { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 0.95rem; transition: all 0.2s; background: #fff; }
    .form-input:focus { border-color: #16a34a; outline: none; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1); }
    .form-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 14px center; background-size: 16px; }

    /* Merchant Card */
    .merchant-group { border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 24px; background: #fff; position: relative; overflow: hidden; }
    .merchant-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; font-weight: 700; color: #0f172a; padding-bottom: 12px; border-bottom: 1px dashed #e2e8f0; }
    
    /* Product Item */
    .cart-item-row { display: flex; gap: 16px; margin-bottom: 16px; }
    .item-thumb { width: 70px; height: 70px; object-fit: cover; border-radius: 10px; border: 1px solid #f1f5f9; }
    
    /* Shipping Box */
    .shipping-box { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 16px; margin-bottom: 20px; } /* Margin bottom added because moved to top */
    .shipping-box select { background-color: white; }

    /* Payment Methods */
    .payment-grid { display: grid; gap: 12px; }
    .payment-card { 
        border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; cursor: pointer; transition: all 0.2s; 
        display: flex; flex-direction: column; 
    }
    .payment-card:hover { border-color: #16a34a; background: #f0fdf4; }
    .payment-card.active { border-color: #16a34a; background: #f0fdf4; ring: 1px solid #16a34a; }
    .payment-header { display: flex; align-items: center; gap: 10px; }
    .payment-radio { accent-color: #16a34a; width: 18px; height: 18px; }
    
    .payment-content { display: none; margin-top: 12px; padding-top: 12px; border-top: 1px dashed #cbd5e1; font-size: 0.9rem; color: #475569; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }

    /* Sticky Sidebar */
    .sticky-summary { position: sticky; top: 100px; }
    .summary-card { background: white; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 0.9rem; color: #64748b; }
    .summary-total { font-size: 1.25rem; font-weight: 800; color: #0f172a; border-top: 2px dashed #e2e8f0; padding-top: 15px; margin-top: 15px; display: flex; justify-content: space-between; }
    
    .btn-primary { width: 100%; padding: 16px; background: #16a34a; color: white; font-weight: 700; border-radius: 12px; border: none; cursor: pointer; transition: all 0.2s; font-size: 1rem; display: flex; justify-content: center; items-center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.2); }
    .btn-primary:hover { background: #15803d; transform: translateY(-1px); box-shadow: 0 10px 15px -3px rgba(22, 163, 74, 0.3); }
    .btn-copy { background: white; border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 6px; font-size: 0.75rem; cursor: pointer; color: #475569; transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px; }
    .btn-copy:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }

    @media (max-width: 1024px) {
        .checkout-grid { grid-template-columns: 1fr; }
        .sticky-summary { position: static; margin-top: 24px; }
    }
</style>

<div class="max-w-7xl mx-auto px-4 py-8">
    <form id="checkout-form" method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="dw_process_checkout">
        <?php wp_nonce_field('dw_checkout_action', 'dw_checkout_nonce'); ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- KOLOM KIRI: DATA & ITEM -->
            <div class="lg:col-span-8 space-y-6">
                
                <!-- 1. DATA ALAMAT (AUTO FILL) -->
                <div class="checkout-section">
                    <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-100">
                        <h2 class="checkout-title mb-0 border-0 p-0"><i class="fas fa-map-marked-alt"></i> Alamat Tujuan</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="form-group">
                            <label class="form-label">Nama Penerima</label>
                            <input type="text" name="nama_penerima" class="form-input" value="<?php echo esc_attr($nama_penerima); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">WhatsApp (Aktif)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fab fa-whatsapp"></i></span>
                                <input type="tel" name="no_hp" class="form-input pl-10" value="<?php echo esc_attr($no_hp); ?>" required placeholder="Contoh: 08123456789">
                            </div>
                            <span class="text-[10px] text-gray-400 mt-1 block">*Wajib diisi untuk konfirmasi pengiriman</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="alamat_lengkap" class="form-input" rows="2" required placeholder="Nama Jalan, No Rumah, RT/RW, Patokan"><?php echo esc_textarea($alamat_lengkap); ?></textarea>
                    </div>

                    <!-- Region Dropdowns (API Based) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="form-group">
                            <label class="form-label">Provinsi</label>
                            <select name="provinsi" id="region-provinsi" class="form-input form-select" required data-selected="<?php echo esc_attr($provinsi_id); ?>">
                                <option value="">Memuat...</option>
                            </select>
                            <input type="hidden" name="provinsi_nama" id="provinsi_nama">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kota/Kabupaten</label>
                            <select name="kabupaten" id="region-kabupaten" class="form-input form-select" required disabled data-selected="<?php echo esc_attr($kabupaten_id); ?>">
                                <option value="">Pilih Provinsi Dulu</option>
                            </select>
                            <input type="hidden" name="kabupaten_nama" id="kabupaten_nama">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kecamatan</label>
                            <select name="kecamatan" id="region-kecamatan" class="form-input form-select trigger-shipping-calc" required disabled data-selected="<?php echo esc_attr($kecamatan_id); ?>">
                                <option value="">Pilih Kabupaten Dulu</option>
                            </select>
                            <input type="hidden" name="kecamatan_nama" id="kecamatan_nama">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kelurahan</label>
                            <select name="kelurahan" id="region-kelurahan" class="form-input form-select trigger-shipping-calc" required disabled data-selected="<?php echo esc_attr($kelurahan_id); ?>">
                                <option value="">Pilih Kecamatan Dulu</option>
                            </select>
                            <input type="hidden" name="kelurahan_nama" id="kelurahan_nama">
                        </div>
                    </div>
                    <div class="form-group md:w-1/2">
                        <label class="form-label">Kode Pos</label>
                        <input type="text" name="kode_pos" id="kode_pos" class="form-input" value="<?php echo esc_attr($kode_pos); ?>" required>
                    </div>
                </div>

                <!-- 2. RINCIAN PESANAN & PENGIRIMAN (DIPINDAHKAN KE ATAS) -->
                <div class="checkout-section">
                    <h2 class="checkout-title"><i class="fas fa-shipping-fast"></i> Pengiriman & Pesanan</h2>
                    
                    <?php foreach ($cart_by_merchant as $mid => $merchant_data): ?>
                        <div class="merchant-group" data-merchant-id="<?php echo $mid; ?>">
                            <div class="merchant-header">
                                <i class="fas fa-store text-blue-500"></i>
                                <?php echo esc_html($merchant_data['info']['nama_toko']); ?>
                            </div>

                            <!-- Shipping Selector (Moved to Top) -->
                            <div class="shipping-box">
                                <label class="form-label text-xs uppercase text-green-700 mb-2 flex items-center gap-1">
                                    <i class="fas fa-truck"></i> Pilih Metode Kirim
                                </label>
                                <select name="shipping_method[<?php echo $mid; ?>]" class="form-input form-select shipping-dropdown" required 
                                        data-merchant="<?php echo $mid; ?>"
                                        data-merchant-kecamatan="<?php echo esc_attr($merchant_data['info']['merchant_kec_id']); ?>"
                                        data-ojek-zones='<?php echo htmlspecialchars($merchant_data['info']['shipping']['ojek_zona'] ?? '{}', ENT_QUOTES, 'UTF-8'); ?>'>
                                    <option value="" disabled selected>-- Pilih Kurir --</option>
                                    
                                    <?php if ($merchant_data['info']['shipping']['pickup']): ?>
                                        <option value="pickup" data-cost="0">Ambil Sendiri di Toko (Gratis)</option>
                                    <?php endif; ?>

                                    <?php if ($merchant_data['info']['shipping']['ojek']): ?>
                                        <option value="ojek" data-is-ojek="true">Diantar Ojek Lokal</option>
                                    <?php endif; ?>

                                    <?php if ($merchant_data['info']['shipping']['kurir']): ?>
                                        <option value="kurir" data-cost="0" data-manual="true">Jasa Ekspedisi (JNE/J&T/dll - Bayar Nanti)</option>
                                    <?php endif; ?>
                                </select>

                                <input type="hidden" name="shipping_cost[<?php echo $mid; ?>]" class="shipping-cost-input" value="0">
                                <div id="shipping-note-<?php echo $mid; ?>" class="text-xs mt-2 font-medium hidden"></div>
                            </div>

                            <!-- Items List -->
                            <?php foreach ($merchant_data['items'] as $item): ?>
                                <div class="cart-item-row">
                                    <img src="<?php echo esc_url(!empty($item->foto_utama) ? $item->foto_utama : 'https://via.placeholder.com/100?text=Produk'); ?>" class="item-thumb">
                                    <div class="flex-1">
                                        <h4 class="font-bold text-gray-800 text-sm mb-1 line-clamp-1"><?php echo esc_html($item->nama_produk); ?></h4>
                                        <div class="text-xs text-gray-500 mb-1">
                                            <?php echo $item->qty; ?> x Rp <?php echo number_format($item->harga,0,',','.'); ?>
                                            <span class="mx-1">•</span> <?php echo ($item->berat_gram * $item->qty)/1000; ?> kg
                                        </div>
                                        <?php if($item->id_variasi): ?>
                                            <span class="inline-block text-[10px] bg-gray-100 px-2 py-0.5 rounded text-gray-600 border border-gray-200">Var: <?php echo $item->id_variasi; ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-right font-bold text-sm text-gray-800">
                                        Rp <?php echo number_format($item->harga * $item->qty, 0, ',', '.'); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 3. METODE PEMBAYARAN -->
                <div class="checkout-section">
                    <h2 class="checkout-title"><i class="fas fa-wallet"></i> Pembayaran</h2>
                    
                    <div class="payment-grid">
                        <!-- Transfer Bank -->
                        <label class="payment-card">
                            <div class="payment-header">
                                <input type="radio" name="payment_method" value="transfer" class="payment-radio" required>
                                <div>
                                    <span class="font-bold text-gray-800 block">Transfer Bank</span>
                                    <span class="text-xs text-gray-500">Transfer manual ke rekening pedagang</span>
                                </div>
                            </div>
                            
                            <div class="payment-content" id="detail-transfer">
                                <p class="mb-3 font-medium">Rekening Tujuan:</p>
                                <?php foreach ($cart_by_merchant as $mid => $mdata): ?>
                                    <?php if(!empty($mdata['info']['bank']['rek'])): ?>
                                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-200 mb-2">
                                            <div>
                                                <span class="text-[10px] font-bold text-blue-600 uppercase tracking-wide block mb-1"><?php echo esc_html($mdata['info']['nama_toko']); ?></span>
                                                <div class="font-bold text-gray-800 text-sm"><?php echo esc_html($mdata['info']['bank']['bank']); ?></div>
                                                <div class="text-sm font-mono text-gray-600"><?php echo esc_html($mdata['info']['bank']['rek']); ?></div>
                                            </div>
                                            <button type="button" class="btn-copy" onclick="copyToClipboard('<?php echo esc_js($mdata['info']['bank']['rek']); ?>')">
                                                <i class="far fa-copy"></i> Salin
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <div class="bg-blue-50 text-blue-700 p-3 rounded text-xs mt-3">
                                    <i class="fas fa-info-circle mr-1"></i> Upload bukti transfer tersedia setelah pesanan dibuat.
                                </div>
                            </div>
                        </label>

                        <!-- QRIS -->
                        <label class="payment-card">
                            <div class="payment-header">
                                <input type="radio" name="payment_method" value="qris" class="payment-radio">
                                <div>
                                    <span class="font-bold text-gray-800 block">QRIS</span>
                                    <span class="text-xs text-gray-500">Scan barcode instan</span>
                                </div>
                            </div>
                            
                            <div class="payment-content" id="detail-qris">
                                <div class="grid grid-cols-2 gap-4">
                                    <?php foreach ($cart_by_merchant as $mid => $mdata): ?>
                                        <?php if(!empty($mdata['info']['qris'])): ?>
                                            <div class="text-center border p-3 rounded-lg bg-white">
                                                <p class="text-xs font-bold mb-2 text-gray-500"><?php echo esc_html($mdata['info']['nama_toko']); ?></p>
                                                <img src="<?php echo esc_url($mdata['info']['qris']); ?>" class="w-full h-auto max-w-[120px] mx-auto rounded">
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </label>

                        <!-- Cash -->
                        <label class="payment-card">
                            <div class="payment-header">
                                <input type="radio" name="payment_method" value="cash" class="payment-radio">
                                <div>
                                    <span class="font-bold text-gray-800 block">Bayar Tunai (COD / Di Toko)</span>
                                    <span class="text-xs text-gray-500">Bayar saat barang diterima</span>
                                </div>
                            </div>
                            
                            <div class="payment-content" id="detail-cash">
                                <div class="bg-yellow-50 text-yellow-800 p-3 rounded text-sm border border-yellow-200">
                                    <i class="fas fa-hand-holding-usd mr-1"></i> Pastikan membawa uang pas saat bertemu kurir atau pedagang.
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

            </div>

            <!-- KOLOM KANAN: RINGKASAN (STICKY) -->
            <div class="lg:col-span-4">
                <div class="sticky-summary">
                    <div class="summary-card">
                        <h2 class="text-lg font-bold text-gray-900 mb-4 pb-4 border-b border-gray-100">Ringkasan Belanja</h2>
                        
                        <div class="summary-row">
                            <span>Total Harga (<?php echo array_sum(array_column($cart_items, 'qty')); ?> barang)</span>
                            <span class="font-bold text-gray-800">Rp <?php echo number_format($subtotal_global, 0, ',', '.'); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Total Ongkos Kirim</span>
                            <span class="font-bold text-green-600" id="display-total-ongkir">Rp 0</span>
                        </div>

                        <div class="bg-orange-50 text-orange-700 p-2 rounded text-xs mt-2 hidden" id="courier-notice-row">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Ongkir Ekspedisi dibayar terpisah setelah dikonfirmasi penjual.
                        </div>

                        <div class="summary-total">
                            <span>Total Tagihan</span>
                            <span class="text-xl text-green-700" id="display-grand-total">Rp <?php echo number_format($subtotal_global, 0, ',', '.'); ?></span>
                        </div>

                        <button type="submit" name="dw_place_order" class="btn-primary mt-6">
                            Buat Pesanan Sekarang <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                        
                        <p class="text-[10px] text-center text-gray-400 mt-4 leading-relaxed">
                            Dengan melanjutkan, saya menyetujui Syarat & Ketentuan serta Kebijakan Privasi yang berlaku.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- JAVASCRIPT: LOGIC WILAYAH & ONGKIR -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- 1. REGION API LOGIC ---
    // Menggunakan AJAX WordPress untuk proxy ke API wilayah (dw_fetch_provinces, dll)
    const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    const provSelect = document.getElementById('region-provinsi');
    const kabSelect = document.getElementById('region-kabupaten');
    const kecSelect = document.getElementById('region-kecamatan');
    const kelSelect = document.getElementById('region-kelurahan');
    
    // Hidden inputs for names
    const provName = document.getElementById('provinsi_nama');
    const kabName = document.getElementById('kabupaten_nama');
    const kecName = document.getElementById('kecamatan_nama');
    const kelName = document.getElementById('kelurahan_nama');
    const kodePosInput = document.getElementById('kode_pos');

    // Helper: Fetch Data
    function fetchRegion(action, parentId, targetSelect, placeholder) {
        let url = `${ajaxUrl}?action=${action}`;
        if(parentId) url += `&${parentId.key}=${parentId.value}`;
        
        targetSelect.innerHTML = '<option value="">Memuat...</option>';
        targetSelect.disabled = true;

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if(res.success) {
                    targetSelect.innerHTML = `<option value="">${placeholder}</option>`;
                    res.data.forEach(item => {
                        const selected = targetSelect.dataset.selected == item.id ? 'selected' : '';
                        // Simpan postal code di data attribute jika ada (utk kelurahan)
                        const postalAttr = item.postal_code ? `data-postal="${item.postal_code}"` : '';
                        targetSelect.innerHTML += `<option value="${item.id}" data-name="${item.name}" ${postalAttr} ${selected}>${item.name}</option>`;
                    });
                    targetSelect.disabled = false;
                    
                    // Trigger next level if pre-selected
                    if(targetSelect.dataset.selected) {
                        targetSelect.dispatchEvent(new Event('change'));
                        // Clear selection to avoid re-trigger on manual change
                        targetSelect.dataset.selected = ''; 
                    }
                }
            });
    }

    // Load Provinces on Start
    fetchRegion('dw_fetch_provinces', null, provSelect, 'Pilih Provinsi');

    // Chain Event Listeners
    provSelect.addEventListener('change', function() {
        const id = this.value;
        const name = this.options[this.selectedIndex].dataset.name;
        provName.value = name;
        if(id) fetchRegion('dw_fetch_regencies', {key: 'province_id', value: id}, kabSelect, 'Pilih Kabupaten');
    });

    kabSelect.addEventListener('change', function() {
        const id = this.value;
        const name = this.options[this.selectedIndex].dataset.name;
        kabName.value = name;
        if(id) fetchRegion('dw_fetch_districts', {key: 'regency_id', value: id}, kecSelect, 'Pilih Kecamatan');
    });

    kecSelect.addEventListener('change', function() {
        const id = this.value;
        const name = this.options[this.selectedIndex].dataset.name;
        kecName.value = name;
        
        // Trigger Shipping Calc (Important!)
        calculateShipping();

        if(id) fetchRegion('dw_fetch_villages', {key: 'district_id', value: id}, kelSelect, 'Pilih Kelurahan');
    });
    
    kelSelect.addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const name = option.dataset.name;
        kelName.value = name;
        
        // Auto fill postal code if available
        if(option.dataset.postal) {
            kodePosInput.value = option.dataset.postal;
        }

        // Trigger Calc again when Village selected (for exact matching)
        calculateShipping();
    });


    // --- 2. SHIPPING & PAYMENT LOGIC ---
    const subtotalGlobal = <?php echo $subtotal_global; ?>;
    const shippingDropdowns = document.querySelectorAll('.shipping-dropdown');
    const displayTotalOngkir = document.getElementById('display-total-ongkir');
    const displayGrandTotal = document.getElementById('display-grand-total');
    const courierNoticeRow = document.getElementById('courier-notice-row');
    
    // Payment Toggle
    document.querySelectorAll('.payment-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.payment-content').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('active'));
            
            const detailBox = document.getElementById('detail-' + this.value);
            if(detailBox) detailBox.style.display = 'block';
            this.closest('.payment-card').classList.add('active');
        });
    });

    function calculateShipping() {
        let totalOngkir = 0;
        let hasCourier = false;
        
        // 1. Ambil ID & Nama Kecamatan User
        const userKecamatanId = kecSelect.value; 
        
        // 2. Ambil ID Kelurahan/Desa User
        const userDesaId = kelSelect.value; 

        shippingDropdowns.forEach(select => {
            const mid = select.getAttribute('data-merchant');
            const selectedOption = select.options[select.selectedIndex];
            const costInput = document.querySelector(`input[name="shipping_cost[${mid}]"]`);
            const noteDiv = document.getElementById(`shipping-note-${mid}`);
            
            // Ambil ID Kecamatan Merchant (disimpan di data-attribute saat loop PHP)
            const merchantKecId = select.getAttribute('data-merchant-kecamatan');

            let cost = 0;
            let note = '';

            // Reset UI
            noteDiv.classList.add('hidden');
            noteDiv.innerHTML = '';
            noteDiv.className = "text-xs mt-2 font-medium hidden"; // reset classes

            if (selectedOption.value === 'pickup') {
                cost = 0;
                note = '<span class="text-green-600"><i class="fas fa-check-circle"></i> Gratis - Ambil di toko</span>';
            } 
            else if (selectedOption.value === 'kurir') {
                cost = 0; 
                hasCourier = true;
                note = '<span class="text-orange-500"><i class="fas fa-clock"></i> Ongkir dihitung manual nanti.</span>';
            }
            else if (selectedOption.value === 'ojek') {
                const zonesJson = select.getAttribute('data-ojek-zones');
                let zones = {};
                try { zones = JSON.parse(zonesJson); } catch(e) {}

                let foundCost = null;

                // --- LOGIKA HITUNG ONGKIR OJEK ---
                if (userKecamatanId && merchantKecId) {
                    
                    // SKENARIO A: SATU KECAMATAN (Merchant & User di Kecamatan Sama)
                    if (String(userKecamatanId) === String(merchantKecId)) {
                        const zoneData = zones['satu_kecamatan'];
                        if (zoneData) {
                            // Cek Zona Dekat (Berdasarkan ID Desa)
                            const desaIdsDekat = zoneData['dekat']['desa_ids'] || [];
                            if (desaIdsDekat.includes(userDesaId)) {
                                foundCost = parseInt(zoneData['dekat']['harga']);
                            } 
                            // Cek Zona Jauh (Berdasarkan ID Desa)
                            else {
                                const desaIdsJauh = zoneData['jauh']['desa_ids'] || [];
                                if (desaIdsJauh.includes(userDesaId)) {
                                    foundCost = parseInt(zoneData['jauh']['harga']);
                                } else {
                                    // Default ke Jauh jika satu kecamatan tapi tidak ada di list
                                    // Atau bisa diset error jika sangat strict. Kita asumsi Jauh.
                                    foundCost = parseInt(zoneData['jauh']['harga']);
                                }
                            }
                        }
                    } 
                    // SKENARIO B: BEDA KECAMATAN (Satu Kabupaten)
                    else {
                        const zoneData = zones['beda_kecamatan'];
                        if (zoneData) {
                             // Cek Zona Dekat (Berdasarkan ID Kecamatan)
                             const kecIdsDekat = zoneData['dekat']['kecamatan_ids'] || [];
                             if (kecIdsDekat.includes(userKecamatanId)) {
                                 foundCost = parseInt(zoneData['dekat']['harga']);
                             }
                             // Cek Zona Jauh (Berdasarkan ID Kecamatan)
                             else {
                                 const kecIdsJauh = zoneData['jauh']['kecamatan_ids'] || [];
                                 if (kecIdsJauh.includes(userKecamatanId)) {
                                     foundCost = parseInt(zoneData['jauh']['harga']);
                                 }
                             }
                        }
                    }
                }

                if (foundCost !== null && !isNaN(foundCost)) {
                    cost = foundCost;
                    note = `<span class="text-green-600"><i class="fas fa-motorcycle"></i> Ojek Lokal: <b>Rp ${formatRupiah(cost)}</b></span>`;
                } else if (!userKecamatanId) {
                    cost = 0;
                    note = '<span class="text-gray-500">Pilih Kecamatan & Desa di alamat dulu.</span>';
                } else {
                    cost = 0;
                    note = '<span class="text-red-500"><i class="fas fa-times-circle"></i> Maaf, lokasi Anda diluar jangkauan Ojek toko ini.</span>';
                }
            }

            costInput.value = cost;
            if(note) {
                noteDiv.innerHTML = note;
                noteDiv.classList.remove('hidden');
            }
            
            totalOngkir += cost;
        });

        displayTotalOngkir.innerText = 'Rp ' + formatRupiah(totalOngkir);
        displayGrandTotal.innerText = 'Rp ' + formatRupiah(subtotalGlobal + totalOngkir);
        
        if (hasCourier) courierNoticeRow.classList.remove('hidden');
        else courierNoticeRow.classList.add('hidden');
    }

    shippingDropdowns.forEach(select => {
        select.addEventListener('change', calculateShipping);
    });

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID').format(angka);
    }
});

function copyToClipboard(text) {
    const temp = document.createElement('input');
    document.body.appendChild(temp);
    temp.value = text;
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
}
</script>

<?php get_footer(); ?>