<?php
/**
 * Template Name: Dashboard Toko (Merchant)
 * Description: Dashboard lengkap pedagang. Fitur Ongkir Ojek Lokal (Fixed Zones), Detail Pesanan, Manajemen Paket, dan Referral.
 * Status: FINAL COMPLETE (UI/UX + Full Logic + Referral Card)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Cek Login
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url() );
    exit;
}

$current_user_id = get_current_user_id();
global $wpdb;

// Definisi Tabel
$table_pedagang      = $wpdb->prefix . 'dw_pedagang';
$table_produk        = $wpdb->prefix . 'dw_produk';
$table_transaksi     = $wpdb->prefix . 'dw_transaksi'; // Data Pembeli Utama
$table_transaksi_sub = $wpdb->prefix . 'dw_transaksi_sub'; // Data Pesanan Toko
$table_items         = $wpdb->prefix . 'dw_transaksi_items'; // Item Barang
$table_paket         = $wpdb->prefix . 'dw_paket_transaksi';
$table_pembelian     = $wpdb->prefix . 'dw_pembelian_paket';
$table_desa          = $wpdb->prefix . 'dw_desa';

// Ambil Data Pedagang
$pedagang = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_user = %d", $current_user_id));

// Redirect jika belum terdaftar
if (!$pedagang) {
    get_header();
    echo '<div class="flex items-center justify-center min-h-screen bg-gray-50 font-sans p-4">
        <div class="text-center p-8 bg-white rounded-2xl shadow-xl border border-gray-100 max-w-md w-full">
            <div class="w-20 h-20 bg-red-50 text-red-500 rounded-full flex items-center justify-center text-4xl mx-auto mb-6"><i class="fas fa-store-slash"></i></div>
            <h2 class="text-2xl font-bold text-gray-800 mb-3">Akses Ditolak</h2>
            <p class="text-gray-500 mb-8 leading-relaxed">Maaf, akun Anda belum terdaftar sebagai Mitra UMKM di platform Desa Wisata ini.</p>
            <a href="'.home_url('/daftar-pedagang').'" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold block w-full hover:bg-blue-700 transition shadow-lg shadow-blue-600/20 transform hover:-translate-y-1">Daftar Sekarang</a>
        </div>
    </div>';
    get_footer();
    exit;
}

$msg = '';
$msg_type = '';

// ==========================================
// FORM HANDLERS
// ==========================================

// --- HANDLER 1: SIMPAN PENGATURAN TOKO ---
if ( isset($_POST['dw_action']) && $_POST['dw_action'] == 'save_store_settings' ) {
    if ( isset($_POST['dw_settings_nonce']) && wp_verify_nonce($_POST['dw_settings_nonce'], 'dw_save_settings') ) {
        
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        
        // 1. Data Dasar
        $update_data = [
            'nama_toko'      => sanitize_text_field($_POST['nama_toko']),
            'nama_pemilik'   => sanitize_text_field($_POST['nama_pemilik']),
            'nomor_wa'       => sanitize_text_field($_POST['nomor_wa']),
            'updated_at'     => current_time('mysql')
        ];
        if(isset($_POST['nik'])) $update_data['nik'] = sanitize_text_field($_POST['nik']);

        // 2. Alamat & Wilayah
        $update_data['alamat_lengkap'] = sanitize_textarea_field($_POST['alamat_lengkap']);
        $update_data['kode_pos']       = sanitize_text_field($_POST['kode_pos']);
        $update_data['url_gmaps']      = esc_url_raw($_POST['url_gmaps']);
        
        $update_data['provinsi_nama']  = sanitize_text_field($_POST['provinsi_nama']);
        $update_data['kabupaten_nama'] = sanitize_text_field($_POST['kabupaten_nama']);
        $update_data['kecamatan_nama'] = sanitize_text_field($_POST['kecamatan_nama']);
        $update_data['kelurahan_nama'] = sanitize_text_field($_POST['kelurahan_nama']);
        
        $kel_id_baru = !empty($_POST['api_kelurahan_id']) ? sanitize_text_field($_POST['api_kelurahan_id']) : '';

        if(!empty($_POST['api_provinsi_id'])) $update_data['api_provinsi_id'] = sanitize_text_field($_POST['api_provinsi_id']);
        if(!empty($_POST['api_kabupaten_id'])) $update_data['api_kabupaten_id'] = sanitize_text_field($_POST['api_kabupaten_id']);
        if(!empty($_POST['api_kecamatan_id'])) $update_data['api_kecamatan_id'] = sanitize_text_field($_POST['api_kecamatan_id']);
        if($kel_id_baru) $update_data['api_kelurahan_id'] = $kel_id_baru;

        // Logic Relasi Desa (Otomatis link ke desa jika terdaftar)
        if ($kel_id_baru) {
            $desa_terkait = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $table_desa WHERE api_kelurahan_id = %s", $kel_id_baru ) );
            if ( $desa_terkait ) {
                $update_data['id_desa'] = $desa_terkait->id;
                $update_data['is_independent'] = 0; 
            } else {
                $update_data['id_desa'] = NULL;
                $update_data['is_independent'] = 1; 
            }
        }

        // 3. Keuangan & Ongkir Dasar
        $update_data['nama_bank']          = sanitize_text_field($_POST['nama_bank']);
        $update_data['no_rekening']        = sanitize_text_field($_POST['no_rekening']);
        $update_data['atas_nama_rekening'] = sanitize_text_field($_POST['atas_nama_rekening']);
        $update_data['allow_pesan_di_tempat']     = isset($_POST['allow_pesan_di_tempat']) ? 1 : 0;
        $update_data['shipping_ojek_lokal_aktif'] = isset($_POST['shipping_ojek_lokal_aktif']) ? 1 : 0;
        $update_data['shipping_nasional_aktif']   = isset($_POST['shipping_nasional_aktif']) ? 1 : 0;
        $update_data['shipping_nasional_harga']   = floatval($_POST['shipping_nasional_harga']);

        // --- LOGIKA PENYIMPANAN ZONA OJEK ---
        $safe_array_map = function($input) {
            return isset($input) && is_array($input) ? array_map('sanitize_text_field', $input) : [];
        };

        $ojek_data = [
            'satu_kecamatan' => [
                'dekat' => [
                    'harga' => floatval($_POST['ojek_dekat_harga']),
                    'desa_ids' => $safe_array_map($_POST['ojek_dekat_desa_ids'] ?? null)
                ],
                'jauh' => [
                    'harga' => floatval($_POST['ojek_jauh_harga']),
                    'desa_ids' => $safe_array_map($_POST['ojek_jauh_desa_ids'] ?? null)
                ]
            ],
            'beda_kecamatan' => [
                'dekat' => [
                    'harga' => floatval($_POST['ojek_beda_kec_dekat_harga']),
                    'kecamatan_ids' => $safe_array_map($_POST['ojek_beda_kec_dekat_ids'] ?? null)
                ],
                'jauh' => [
                    'harga' => floatval($_POST['ojek_beda_kec_jauh_harga']),
                    'kecamatan_ids' => $safe_array_map($_POST['ojek_beda_kec_jauh_ids'] ?? null)
                ]
            ]
        ];

        $update_data['shipping_ojek_lokal_zona'] = json_encode($ojek_data);

        // 4. Upload Files
        $files_map = [
            'foto_profil' => 'foto_profil', 
            'foto_sampul' => 'foto_sampul', 
            'foto_ktp'    => 'url_ktp', 
            'foto_qris'   => 'qris_image_url'
        ];
        foreach($files_map as $input_name => $db_col) {
            if ( ! empty($_FILES[$input_name]['name']) ) {
                $upload = wp_handle_upload( $_FILES[$input_name], ['test_form' => false] );
                if ( isset($upload['url']) && ! isset($upload['error']) ) {
                    $update_data[$db_col] = $upload['url'];
                }
            }
        }

        $wpdb->update($table_pedagang, $update_data, ['id' => $pedagang->id]);
        
        // Sinkronisasi User Meta WP
        update_user_meta($current_user_id, 'billing_address_1', $update_data['alamat_lengkap']);
        update_user_meta($current_user_id, 'billing_postcode', $update_data['kode_pos']);
        update_user_meta($current_user_id, 'billing_phone', $update_data['nomor_wa']);
        
        if(isset($update_data['api_provinsi_id'])) update_user_meta($current_user_id, 'api_provinsi_id', $update_data['api_provinsi_id']);
        if(isset($update_data['api_kabupaten_id'])) update_user_meta($current_user_id, 'api_kabupaten_id', $update_data['api_kabupaten_id']);
        if(isset($update_data['api_kecamatan_id'])) update_user_meta($current_user_id, 'api_kecamatan_id', $update_data['api_kecamatan_id']);
        if(isset($update_data['api_kelurahan_id'])) update_user_meta($current_user_id, 'api_kelurahan_id', $update_data['api_kelurahan_id']);

        $msg = "Pengaturan toko & ongkir berhasil diperbarui.";
        $msg_type = "success";
        
        // Refresh Data Object
        $pedagang = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_user = %d", $current_user_id));
    }
}

// --- HANDLER 2: PRODUK ---
if ( isset($_POST['dw_action']) && $_POST['dw_action'] == 'save_product' ) {
    if ( isset($_POST['dw_product_nonce']) && wp_verify_nonce($_POST['dw_product_nonce'], 'dw_save_product') ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        
        $prod_data = [
            'id_pedagang' => $pedagang->id,
            'nama_produk' => sanitize_text_field($_POST['nama_produk']),
            'harga'       => floatval($_POST['harga']),
            'stok'        => intval($_POST['stok']),
            'berat_gram'  => intval($_POST['berat_gram']),
            'deskripsi'   => wp_kses_post($_POST['deskripsi_produk']),
            'kategori'    => sanitize_text_field($_POST['kategori']),
            'kondisi'     => sanitize_text_field($_POST['kondisi']),
            'status'      => 'aktif',
            'updated_at'  => current_time('mysql')
        ];

        if (!empty($_FILES['foto_produk']['name'])) {
            $upload = wp_handle_upload($_FILES['foto_produk'], ['test_form' => false]);
            if (isset($upload['url']) && !isset($upload['error'])) {
                $prod_data['foto_utama'] = $upload['url'];
            }
        }

        if(!empty($_POST['produk_id'])) {
            $wpdb->update($table_produk, $prod_data, ['id' => intval($_POST['produk_id']), 'id_pedagang' => $pedagang->id]);
            $msg = 'Produk berhasil diperbarui.';
        } else {
            $prod_data['slug'] = sanitize_title($_POST['nama_produk']) . '-' . time();
            $prod_data['created_at'] = current_time('mysql');
            $wpdb->insert($table_produk, $prod_data);
            $msg = 'Produk berhasil ditambahkan.';
        }
        $msg_type = 'success';
    }
}

// --- HANDLER 3: HAPUS PRODUK ---
if ( isset($_GET['act']) && $_GET['act'] == 'del_prod' && isset($_GET['id']) ) {
     $wpdb->delete($table_produk, ['id' => intval($_GET['id']), 'id_pedagang' => $pedagang->id]);
     ?> <script>window.history.replaceState(null, null, window.location.pathname);</script> <?php
     $msg = 'Produk berhasil dihapus.';
     $msg_type = 'success';
}

// --- HANDLER 4: BELI PAKET ---
if (isset($_POST['beli_paket']) && isset($_POST['paket_nonce']) && wp_verify_nonce($_POST['paket_nonce'], 'beli_paket_action')) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    $id_paket = intval($_POST['id_paket']);
    $paket = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_paket WHERE id = %d", $id_paket));
    if ($paket && !empty($_FILES['bukti_bayar']['name'])) {
        $upload = wp_handle_upload($_FILES['bukti_bayar'], ['test_form' => false]);
        if (isset($upload['url']) && !isset($upload['error'])) {
            $wpdb->insert($table_pembelian, [
                'id_pedagang' => $pedagang->id,
                'id_paket'    => $paket->id,
                'nama_paket_snapshot' => $paket->nama_paket,
                'harga_paket' => $paket->harga,
                'jumlah_transaksi' => $paket->jumlah_transaksi,
                'persentase_komisi_referrer' => 5, // Default percentage or fetch from settings
                'url_bukti_bayar' => $upload['url'],
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ]);
            $msg = "Pembelian diajukan. Mohon tunggu verifikasi admin."; 
            $msg_type = "success";
        }
    }
}

// --- HANDLER 5: UPDATE STATUS PESANAN ---
if (isset($_POST['dw_action']) && $_POST['dw_action'] == 'update_order_status') {
    if ( isset($_POST['dw_order_nonce']) && wp_verify_nonce($_POST['dw_order_nonce'], 'dw_update_order') ) {
        $order_id = intval($_POST['order_id']);
        $new_status = sanitize_text_field($_POST['status_pesanan']);
        $no_resi = sanitize_text_field($_POST['no_resi']);
        
        $data_update = ['status_pesanan' => $new_status];
        if(!empty($no_resi)) {
            $data_update['no_resi'] = $no_resi;
        }
        
        $wpdb->update($table_transaksi_sub, $data_update, ['id' => $order_id, 'id_pedagang' => $pedagang->id]);
        $msg = "Status pesanan #$order_id berhasil diperbarui.";
        $msg_type = "success";
    }
}

// ==========================================
// DATA FETCHING & PREPARATION
// ==========================================

// --- Prepare Address Data with Fallback to User Meta ---
$val_prov_id = !empty($pedagang->api_provinsi_id) ? $pedagang->api_provinsi_id : get_user_meta($current_user_id, 'api_provinsi_id', true);
$val_kota_id = !empty($pedagang->api_kabupaten_id) ? $pedagang->api_kabupaten_id : get_user_meta($current_user_id, 'api_kabupaten_id', true);
$val_kec_id  = !empty($pedagang->api_kecamatan_id) ? $pedagang->api_kecamatan_id : get_user_meta($current_user_id, 'api_kecamatan_id', true);
$val_kel_id  = !empty($pedagang->api_kelurahan_id) ? $pedagang->api_kelurahan_id : get_user_meta($current_user_id, 'api_kelurahan_id', true);

$val_prov_nama = !empty($pedagang->provinsi_nama) ? $pedagang->provinsi_nama : get_user_meta($current_user_id, 'provinsi_nama', true);
$val_kota_nama = !empty($pedagang->kabupaten_nama) ? $pedagang->kabupaten_nama : get_user_meta($current_user_id, 'kabupaten_nama', true);
$val_kec_nama  = !empty($pedagang->kecamatan_nama) ? $pedagang->kecamatan_nama : get_user_meta($current_user_id, 'kecamatan_nama', true);
$val_kel_nama  = !empty($pedagang->kelurahan_nama) ? $pedagang->kelurahan_nama : get_user_meta($current_user_id, 'kelurahan_nama', true);

$val_alamat    = !empty($pedagang->alamat_lengkap) ? $pedagang->alamat_lengkap : get_user_meta($current_user_id, 'billing_address_1', true);
$val_kodepos   = !empty($pedagang->kode_pos) ? $pedagang->kode_pos : get_user_meta($current_user_id, 'billing_postcode', true);

// --- Fetch Lists ---
$produk_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_produk WHERE id_pedagang = %d ORDER BY created_at DESC", $pedagang->id));
$order_query = "
    SELECT sub.*, t.nama_penerima, t.no_hp, t.alamat_lengkap AS alamat_kirim
    FROM $table_transaksi_sub sub
    JOIN $table_transaksi t ON sub.id_transaksi = t.id
    WHERE sub.id_pedagang = %d
    ORDER BY sub.created_at DESC
";
$order_list = $wpdb->get_results($wpdb->prepare($order_query, $pedagang->id));
$pakets = $wpdb->get_results("SELECT * FROM $table_paket WHERE status = 'aktif' AND target_role = 'pedagang' ORDER BY harga ASC");
$histori_paket = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pembelian WHERE id_pedagang = %d ORDER BY created_at DESC LIMIT 10", $pedagang->id));

// Data Zona Ojek (Fixed Zones)
$saved_zones = [];
if (!empty($pedagang->shipping_ojek_lokal_zona)) {
    $decoded = json_decode($pedagang->shipping_ojek_lokal_zona, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $saved_zones = $decoded;
    }
}

// Default Structure jika kosong
$ojek_zona = isset($saved_zones['satu_kecamatan']) ? $saved_zones : [
    'satu_kecamatan' => ['dekat' => ['harga' => '', 'desa_ids' => []], 'jauh' => ['harga' => '', 'desa_ids' => []]],
    'beda_kecamatan' => ['dekat' => ['harga' => '', 'kecamatan_ids' => []], 'jauh' => ['harga' => '', 'kecamatan_ids' => []]]
];

$default_cats = ['Makanan', 'Fashion', 'Kerajinan', 'Pertanian', 'Jasa', 'Elektronik', 'Kesehatan'];
$existing_cats = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_produk WHERE kategori != ''");
$kategori_list = array_unique(array_merge($default_cats, $existing_cats ?: [])); sort($kategori_list);

$count_produk = count($produk_list);
$revenue      = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_pesanan_toko) FROM $table_transaksi_sub WHERE id_pedagang = %d AND status_pesanan IN ('selesai', 'dikirim_ekspedisi', 'lunas')", $pedagang->id));

get_header();
?>

<!-- ==========================================
     UI SECTION
========================================== -->
<!-- CDN Tailwind & Icons -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>tailwind.config = { theme: { extend: { colors: { primary: '#16a34a', secondary: '#1e293b' } } } }</script>
<style>
    .tab-content { display: none; animation: fadeIn 0.3s ease-in-out; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    .nav-item.active { background-color: #f0fdf4; color: #16a34a; border-right: 3px solid #16a34a; font-weight: 600; }
    .toggle-checkbox:checked { right: 0; border-color: #16a34a; }
    .toggle-checkbox:checked + .toggle-label { background-color: #16a34a; }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    .animate-fade-in { animation: fadeIn 0.3s ease-out; }
</style>

<div class="bg-gray-50 min-h-screen font-sans flex overflow-hidden text-slate-800">
    
    <!-- HEADER MOBILE (FIXED & Z-INDEX 100) -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-[100] flex items-center justify-between px-4 shadow-sm">
        <div class="flex items-center gap-3">
            <button onclick="toggleMobileSidebar()" class="text-gray-600 p-2 rounded-lg hover:bg-gray-100 transition-colors focus:ring-2 focus:ring-gray-200"><i class="fas fa-bars text-xl"></i></button>
            <span class="font-bold text-gray-800 flex items-center gap-2 text-sm"><i class="fas fa-store text-primary"></i> Merchant</span>
        </div>
        <div class="flex items-center gap-3">
            <!-- Keranjang Icon -->
            <a href="<?php echo home_url('/keranjang'); ?>" class="text-gray-600 p-2 rounded-lg hover:bg-gray-100 relative" title="Keranjang Belanja">
                <i class="fas fa-shopping-cart text-lg"></i>
            </a>
            
            <!-- Profil -->
            <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden border border-gray-300">
                 <img src="<?php echo $pedagang->foto_profil ? esc_url($pedagang->foto_profil) : 'https://placehold.co/100'; ?>" class="w-full h-full object-cover">
            </div>
        </div>
    </div>

    <!-- SIDEBAR -->
    <?php 
    get_template_part('template-parts/dashboard/sidebar', 'pedagang', [
        'pedagang' => $pedagang 
    ]); 
    ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 md:ml-64 p-4 md:p-8 pt-20 md:pt-8 overflow-y-auto h-screen pb-24 bg-gray-50">
        
        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl border <?php echo ($msg_type=='error')?'bg-red-50 text-red-700 border-red-200':'bg-green-50 text-green-700 border-green-200'; ?> flex items-center gap-3 shadow-sm animate-fade-in">
                <i class="fas <?php echo ($msg_type=='error')?'fa-exclamation-circle':'fa-check-circle'; ?> text-lg"></i> 
                <div><?php echo $msg; ?></div>
            </div>
        <?php endif; ?>

        <!-- VIEW 1: RINGKASAN -->
        <div id="view-ringkasan" class="tab-content active">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Ringkasan</h1>

            <!-- KARTU KODE REFERRAL TOKO -->
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 border border-purple-200 rounded-2xl p-5 mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-purple-600 uppercase tracking-wider mb-1 flex items-center gap-2">
                        <i class="fas fa-gift"></i> Link Referral Toko Saya
                    </p>
                    <h2 class="text-2xl font-mono font-bold text-gray-800 tracking-wide">
                        <?php echo !empty($pedagang->kode_referral_saya) ? esc_html($pedagang->kode_referral_saya) : '-'; ?>
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">Bagikan link ini. Pembeli yang mendaftar akan otomatis terhubung dengan Anda.</p>
                </div>
                <div class="relative z-10">
                    <?php 
                        // GENERATE LINK REGISTER OTOMATIS
                        $ref_link = home_url('/register?ref=' . $pedagang->kode_referral_saya);
                    ?>
                    <?php if(!empty($pedagang->kode_referral_saya)): ?>
                    <button onclick="copyToClipboard('<?php echo esc_js($ref_link); ?>')" class="bg-white hover:bg-purple-600 hover:text-white text-purple-700 border border-purple-200 font-bold py-2.5 px-5 rounded-xl shadow-sm transition-all active:scale-95 flex items-center gap-2">
                        <i class="fas fa-link"></i> <span>Salin Link Daftar</span>
                    </button>
                    <?php else: ?>
                    <span class="text-xs text-red-500 italic bg-red-50 px-3 py-1 rounded-lg">Kode belum digenerate</span>
                    <?php endif; ?>
                </div>
                <!-- Decoration -->
                <div class="absolute right-0 top-0 h-full w-32 bg-purple-100/30 -skew-x-12 translate-x-10 group-hover:translate-x-5 transition-transform duration-500"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Pendapatan -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition flex items-center gap-5 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-green-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="w-14 h-14 rounded-2xl bg-green-100 text-green-600 flex items-center justify-center text-2xl relative z-10 shadow-sm"><i class="fas fa-wallet"></i></div>
                    <div class="relative z-10">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Pendapatan</p>
                        <h3 class="text-2xl font-extrabold text-gray-800">Rp <?php echo number_format($revenue?:0,0,',','.'); ?></h3>
                    </div>
                </div>
                <!-- Produk -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition flex items-center gap-5 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="w-14 h-14 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center text-2xl relative z-10 shadow-sm"><i class="fas fa-box-open"></i></div>
                    <div class="relative z-10">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Produk</p>
                        <h3 class="text-2xl font-extrabold text-gray-800"><?php echo $count_produk; ?></h3>
                    </div>
                </div>
                <!-- Kuota -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition flex items-center gap-5 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 w-24 h-24 bg-orange-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                    <div class="w-14 h-14 rounded-2xl bg-orange-100 text-orange-600 flex items-center justify-center text-2xl relative z-10 shadow-sm"><i class="fas fa-ticket-alt"></i></div>
                    <div class="relative z-10">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Sisa Kuota</p>
                        <h3 class="text-2xl font-extrabold text-gray-800"><?php echo $pedagang->sisa_transaksi; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW 2: PRODUK -->
        <div id="view-produk" class="tab-content hidden">
             <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                 <div>
                     <h1 class="text-2xl font-bold text-gray-800">Produk Saya</h1>
                     <p class="text-sm text-gray-500">Kelola inventaris dan harga produk Anda.</p>
                 </div>
                 <button onclick="openProductModal()" class="bg-gray-900 hover:bg-black text-white px-6 py-3 rounded-xl font-bold shadow-lg transition transform hover:-translate-y-1 flex items-center gap-2">
                     <i class="fas fa-plus"></i> Tambah Produk
                 </button>
             </div>
             
             <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                 <?php if($produk_list): foreach($produk_list as $p): ?>
                 <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 group hover:shadow-xl hover:border-gray-200 transition duration-300 flex flex-col h-full">
                     <!-- Image -->
                     <div class="relative h-48 bg-gray-50 rounded-xl overflow-hidden mb-3">
                         <?php if(!empty($p->foto_utama)): ?>
                            <img src="<?php echo esc_url($p->foto_utama); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                         <?php else: ?>
                            <div class="flex items-center justify-center h-full text-gray-300 bg-gray-100"><i class="fas fa-image text-3xl"></i></div>
                         <?php endif; ?>
                         <div class="absolute top-2 left-2 flex gap-1">
                             <span class="bg-white/90 backdrop-blur px-2 py-1 rounded-md text-[10px] font-bold shadow-sm text-gray-700 border border-gray-100 uppercase tracking-wide"><?php echo $p->kondisi; ?></span>
                         </div>
                         <?php if($p->stok < 5): ?>
                             <div class="absolute bottom-2 right-2 bg-red-100 text-red-600 px-2 py-1 rounded-md text-[10px] font-bold shadow-sm border border-red-200 flex items-center gap-1">
                                 <i class="fas fa-exclamation-circle"></i> Stok Menipis
                             </div>
                         <?php endif; ?>
                     </div>
                     <!-- Info -->
                     <div class="px-1 flex-1 flex flex-col">
                         <h4 class="font-bold text-gray-800 mb-1 leading-tight line-clamp-2 min-h-[2.5rem]"><?php echo esc_html($p->nama_produk); ?></h4>
                         <div class="flex items-center justify-between mb-3">
                             <p class="text-primary font-extrabold text-lg">Rp <?php echo number_format($p->harga,0,',','.'); ?></p>
                             <div class="text-[10px] text-gray-400 font-medium bg-gray-50 px-2 py-1 rounded-full"><?php echo $p->stok; ?> Unit</div>
                         </div>
                         <div class="mt-auto pt-3 border-t border-dashed border-gray-100 flex gap-2">
                            <button onclick='editProduk(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>)' class="flex-1 bg-white border border-gray-200 text-gray-600 py-2 rounded-lg text-xs font-bold hover:bg-gray-50 hover:border-gray-300 transition flex items-center justify-center gap-1"><i class="fas fa-pen"></i> Edit</button>
                            <a href="?act=del_prod&id=<?php echo $p->id; ?>" onclick="return confirm('Hapus produk ini?')" class="w-10 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-100 transition"><i class="fas fa-trash"></i></a>
                         </div>
                     </div>
                 </div>
                 <?php endforeach; else: ?>
                    <div class="col-span-full py-16 text-center bg-white rounded-3xl border border-dashed border-gray-200">
                        <div class="inline-block p-6 rounded-full bg-gray-50 mb-4 text-gray-300"><i class="fas fa-box-open text-5xl"></i></div>
                        <h3 class="text-lg font-bold text-gray-700">Belum ada produk</h3>
                        <p class="text-gray-500 mb-6 text-sm">Toko Anda masih kosong. Mulai jualan sekarang!</p>
                        <button onclick="openProductModal()" class="text-primary font-bold hover:underline text-sm">Tambah Produk Pertama</button>
                    </div>
                 <?php endif; ?>
             </div>
        </div>

        <!-- VIEW 3: PESANAN -->
        <div id="view-pesanan" class="tab-content hidden">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Pesanan Masuk</h1>
            <p class="text-sm text-gray-500 mb-6">Pantau dan kelola pesanan dari pelanggan.</p>
            
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <?php if($order_list): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50/50 border-b border-gray-100">
                            <tr class="text-gray-500 uppercase text-[11px] tracking-wider">
                                <th class="py-4 px-6 font-bold">ID Order</th>
                                <th class="py-4 px-6 font-bold">Pelanggan</th>
                                <th class="py-4 px-6 font-bold">Total Belanja</th>
                                <th class="py-4 px-6 font-bold">Status</th>
                                <th class="py-4 px-6 font-bold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach($order_list as $o): 
                                $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_items WHERE id_sub_transaksi = %d", $o->id));
                                $o->items = $items; 
                                $status_map = ['menunggu_konfirmasi' => 'bg-yellow-50 text-yellow-600 border border-yellow-100', 'diproses' => 'bg-blue-50 text-blue-600 border border-blue-100', 'dikirim_ekspedisi' => 'bg-purple-50 text-purple-600 border border-purple-100', 'diantar_ojek' => 'bg-purple-50 text-purple-600 border border-purple-100', 'siap_diambil' => 'bg-indigo-50 text-indigo-600 border border-indigo-100', 'selesai' => 'bg-green-50 text-green-600 border border-green-100', 'lunas' => 'bg-green-50 text-green-600 border border-green-100', 'dibatalkan' => 'bg-red-50 text-red-600 border border-red-100'];
                                $status_color = isset($status_map[$o->status_pesanan]) ? $status_map[$o->status_pesanan] : 'bg-gray-50 text-gray-500 border border-gray-100';
                            ?>
                            <tr class="hover:bg-gray-50/80 transition group">
                                <td class="py-4 px-6"><span class="font-bold text-gray-800">#<?php echo $o->id; ?></span><div class="text-[10px] text-gray-400 mt-0.5"><?php echo date('d M Y', strtotime($o->created_at)); ?></div></td>
                                <td class="py-4 px-6"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 font-bold text-xs"><?php echo substr($o->nama_penerima, 0, 1); ?></div><div><div class="font-bold text-gray-800 text-sm"><?php echo esc_html($o->nama_penerima); ?></div><div class="text-xs text-gray-500"><?php echo esc_html($o->no_hp); ?></div></div></div></td>
                                <td class="py-4 px-6 font-bold text-gray-800">Rp <?php echo number_format($o->total_pesanan_toko, 0, ',', '.'); ?></td>
                                <td class="py-4 px-6"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide <?php echo $status_color; ?>"><?php echo str_replace('_', ' ', $o->status_pesanan); ?></span></td>
                                <td class="py-4 px-6 text-right"><button onclick='openOrderDetail(<?php echo htmlspecialchars(json_encode($o), ENT_QUOTES, 'UTF-8'); ?>)' class="text-blue-600 hover:text-white hover:bg-blue-600 border border-blue-200 hover:border-blue-600 px-3 py-1.5 rounded-lg text-xs font-bold transition">Detail Order</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-16"><div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300"><i class="fas fa-clipboard-list text-2xl"></i></div><h3 class="text-gray-800 font-bold">Belum ada pesanan</h3><p class="text-gray-500 text-sm">Pesanan baru akan muncul di sini.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- VIEW 4: PAKET & KUOTA -->
        <div id="view-paket" class="tab-content hidden">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Paket & Kuota</h1>
            <p class="text-gray-500 mb-8">Tingkatkan kapasitas toko Anda dengan paket transaksi.</p>
            <div class="bg-gray-900 text-white p-8 rounded-3xl shadow-xl mb-12 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="relative z-10"><p class="text-gray-400 uppercase text-xs font-bold tracking-wider mb-2">Sisa Kuota Transaksi</p><div class="flex items-baseline gap-2"><h2 class="text-6xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-blue-500"><?php echo number_format($pedagang->sisa_transaksi); ?></h2><span class="text-xl font-medium text-gray-400">Transaksi</span></div><p class="text-gray-500 text-sm mt-2 max-w-md">Kuota akan berkurang setiap kali pesanan berhasil diselesaikan.</p></div>
                <div class="relative z-10 hidden md:block"><div class="w-24 h-24 rounded-full bg-white/10 backdrop-blur flex items-center justify-center text-4xl border border-white/10"><i class="fas fa-ticket-alt text-green-400"></i></div></div>
                <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-10 translate-y-10"><i class="fas fa-chart-line text-9xl"></i></div>
            </div>
            <h3 class="font-bold text-xl text-gray-800 mb-6 flex items-center gap-2"><i class="fas fa-plus-circle text-primary"></i> Pilihan Paket</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <?php if($pakets): foreach($pakets as $index => $pk): $is_popular = ($index === 1); ?>
                <div class="bg-white rounded-3xl border <?php echo $is_popular ? 'border-primary shadow-xl shadow-primary/10 relative transform md:-translate-y-2' : 'border-gray-200 shadow-sm hover:shadow-lg'; ?> transition duration-300 p-6 flex flex-col h-full">
                    <?php if($is_popular): ?><div class="absolute top-0 right-0 bg-primary text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl rounded-tr-2xl uppercase tracking-wide">Paling Laris</div><?php endif; ?>
                    <div class="mb-6"><h3 class="font-bold text-xl text-gray-800 mb-2"><?php echo esc_html($pk->nama_paket); ?></h3><div class="flex items-baseline gap-1"><span class="text-xs font-bold text-gray-400">Rp</span><span class="text-4xl font-extrabold text-gray-900"><?php echo number_format($pk->harga,0,',','.'); ?></span></div><p class="text-xs text-gray-400 mt-2">Sekali bayar, aktif selamanya.</p></div>
                    <div class="space-y-4 mb-8 flex-1"><div class="flex items-center gap-3"><div class="w-8 h-8 rounded-full bg-green-50 text-green-600 flex items-center justify-center text-sm"><i class="fas fa-check"></i></div><div><span class="block font-bold text-gray-800 text-lg"><?php echo $pk->jumlah_transaksi; ?></span><span class="text-xs text-gray-500">Kuota Transaksi</span></div></div></div>
                    <button onclick="openBuyModal(<?php echo $pk->id; ?>, '<?php echo esc_js($pk->nama_paket); ?>', <?php echo $pk->harga; ?>)" class="w-full py-3.5 rounded-xl font-bold transition shadow-lg <?php echo $is_popular ? 'bg-primary text-white hover:bg-green-700' : 'bg-gray-900 text-white hover:bg-black'; ?>">Pilih Paket</button>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-xl text-gray-800 flex items-center gap-2"><i class="fas fa-history text-gray-400"></i> Riwayat Pembelian</h3></div>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"><table class="w-full text-sm text-left"><thead class="bg-gray-50 border-b border-gray-100 text-gray-500 uppercase text-[11px] tracking-wider"><tr><th class="p-4 font-bold">Tanggal</th><th class="p-4 font-bold">Paket</th><th class="p-4 font-bold">Harga</th><th class="p-4 font-bold text-right">Status</th></tr></thead><tbody class="divide-y divide-gray-50"><?php if($histori_paket): foreach($histori_paket as $h): $st=($h->status=='disetujui')?'bg-green-50 text-green-700':(($h->status=='ditolak')?'bg-red-50 text-red-700':'bg-yellow-50 text-yellow-700'); ?><tr class="hover:bg-gray-50/50"><td class="p-4 text-gray-500"><?php echo date('d M Y', strtotime($h->created_at)); ?></td><td class="p-4 font-bold"><?php echo esc_html($h->nama_paket_snapshot); ?></td><td class="p-4 text-gray-600">Rp <?php echo number_format($h->harga_paket,0,',','.'); ?></td><td class="p-4 text-right"><span class="px-2 py-1 rounded-full text-[10px] font-bold uppercase <?php echo $st; ?>"><?php echo ucfirst($h->status); ?></span></td></tr><?php endforeach; else: ?><tr><td colspan="4" class="p-8 text-center text-gray-400">Belum ada riwayat.</td></tr><?php endif; ?></tbody></table></div>
        </div>

        <!-- VIEW 5: PENGATURAN -->
        <div id="view-pengaturan" class="tab-content hidden">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pengaturan Toko</h1>
            <form method="POST" enctype="multipart/form-data" id="settings-form" onsubmit="showLoading(this)">
                <?php wp_nonce_field('dw_save_settings', 'dw_settings_nonce'); ?>
                <input type="hidden" name="dw_action" value="save_store_settings">

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- KIRI (Identitas) -->
                    <div class="space-y-8">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="font-bold text-gray-800 mb-6 border-b pb-3 flex items-center gap-2"><i class="fas fa-store text-primary"></i> Profil Toko</h3>
                            <div class="space-y-5">
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Nama Toko</label><input type="text" name="nama_toko" value="<?php echo esc_attr($pedagang->nama_toko); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm"></div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Kode Referral</label><div class="relative"><input type="text" value="<?php echo esc_attr($pedagang->kode_referral_saya); ?>" readonly class="w-full bg-gray-100 border border-gray-200 rounded-lg p-3 text-sm text-gray-600 font-mono"><button type="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($pedagang->kode_referral_saya); ?>'); alert('Kode disalin!');" class="absolute right-2 top-2 bg-white border p-1 rounded hover:bg-gray-50 text-gray-600"><i class="fas fa-copy"></i></button></div></div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Nama Pemilik</label><input type="text" name="nama_pemilik" value="<?php echo esc_attr($pedagang->nama_pemilik); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm"></div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">WhatsApp</label><input type="text" name="nomor_wa" value="<?php echo esc_attr($pedagang->nomor_wa); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm"></div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">NIK</label><input type="text" name="nik" value="<?php echo esc_attr($pedagang->nik); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm"></div>
                                <div class="grid grid-cols-2 gap-4 pt-2">
                                    <div><label class="text-xs font-bold text-gray-500 uppercase block mb-2">Logo</label><div class="relative w-24 h-24 group mx-auto"><img src="<?php echo $pedagang->foto_profil ? esc_url($pedagang->foto_profil) : 'https://placehold.co/150'; ?>" class="w-full h-full rounded-full object-cover border-4 border-gray-50 shadow-sm" id="prev-profil"><label class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-full opacity-0 group-hover:opacity-100 cursor-pointer text-white"><i class="fas fa-camera"></i><input type="file" name="foto_profil" class="hidden" onchange="previewImage(this, 'prev-profil')"></label></div></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase block mb-2">KTP</label><label class="block w-full h-24 border-2 border-dashed rounded-xl hover:bg-gray-50 cursor-pointer flex flex-col items-center justify-center text-gray-400"><i class="fas fa-id-card"></i><span class="text-[10px]">Upload</span><input type="file" name="foto_ktp" class="hidden"></label></div>
                                </div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase block mb-2">Sampul</label><div class="relative h-24 rounded-xl bg-gray-100 overflow-hidden group"><img src="<?php echo $pedagang->foto_sampul ? esc_url($pedagang->foto_sampul) : ''; ?>" id="prev-sampul" class="w-full h-full object-cover <?php echo $pedagang->foto_sampul?'':'hidden'; ?>"><label class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer text-white text-xs font-bold">Ganti Sampul<input type="file" name="foto_sampul" class="hidden" onchange="previewImage(this, 'prev-sampul')"></label></div></div>
                            </div>
                        </div>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="font-bold text-gray-800 mb-6 border-b pb-3 flex items-center gap-2"><i class="fas fa-wallet text-primary"></i> Data Bank</h3>
                            <div class="space-y-5">
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Bank</label><input type="text" name="nama_bank" value="<?php echo esc_attr($pedagang->nama_bank); ?>" class="w-full bg-gray-50 border rounded-lg p-3 text-sm"></div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">No Rekening</label><input type="text" name="no_rekening" value="<?php echo esc_attr($pedagang->no_rekening); ?>" class="w-full bg-gray-50 border rounded-lg p-3 text-sm"></div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Atas Nama</label><input type="text" name="atas_nama_rekening" value="<?php echo esc_attr($pedagang->atas_nama_rekening); ?>" class="w-full bg-gray-50 border rounded-lg p-3 text-sm"></div>
                                
                                <!-- QRIS Preview Section -->
                                <div>
                                    <label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Upload QRIS</label>
                                    <div class="mb-3">
                                        <?php if (!empty($pedagang->qris_image_url)): ?>
                                            <img src="<?php echo esc_url($pedagang->qris_image_url); ?>" id="prev-qris" class="w-32 h-auto rounded-lg border border-gray-200 shadow-sm object-contain bg-white p-1">
                                        <?php else: ?>
                                            <img id="prev-qris" class="w-32 h-auto rounded-lg border border-gray-200 shadow-sm hidden">
                                            <div id="qris-placeholder" class="w-32 h-32 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-xs"><span>Belum ada QRIS</span></div>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="foto_qris" class="text-xs w-full text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition" onchange="previewImage(this, 'prev-qris'); document.getElementById('qris-placeholder')?.classList.add('hidden');">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KANAN (Lokasi & Ongkir) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- LOKASI -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="font-bold text-gray-800 mb-6 border-b pb-3 flex items-center gap-2"><i class="fas fa-map-marker-alt text-primary"></i> Alamat & Lokasi</h3>
                            <div id="region-data" data-prov="<?php echo esc_attr($val_prov_id); ?>" data-kota="<?php echo esc_attr($val_kota_id); ?>" data-kec="<?php echo esc_attr($val_kec_id); ?>" data-kel="<?php echo esc_attr($val_kel_id); ?>"></div>
                            <input type="hidden" name="provinsi_nama" id="input_provinsi_name" value="<?php echo esc_attr($val_prov_nama); ?>">
                            <input type="hidden" name="kabupaten_nama" id="input_kabupaten_name" value="<?php echo esc_attr($val_kota_nama); ?>">
                            <input type="hidden" name="kecamatan_nama" id="input_kecamatan_name" value="<?php echo esc_attr($val_kec_nama); ?>">
                            <input type="hidden" name="kelurahan_nama" id="input_kelurahan_name" value="<?php echo esc_attr($val_kel_nama); ?>">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Provinsi</label><select name="api_provinsi_id" id="dw_provinsi" class="w-full bg-gray-50 border rounded-lg p-3 text-sm"></select></div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Kota/Kabupaten</label><select name="api_kabupaten_id" id="dw_kota" class="w-full bg-gray-50 border rounded-lg p-3 text-sm" disabled></select></div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Kecamatan</label><select name="api_kecamatan_id" id="dw_kecamatan" class="w-full bg-gray-50 border rounded-lg p-3 text-sm" disabled></select></div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Desa/Kelurahan</label><select name="api_kelurahan_id" id="dw_desa" class="w-full bg-gray-50 border rounded-lg p-3 text-sm" disabled></select></div>
                            </div>
                            <div class="mb-5"><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Alamat Lengkap</label><textarea name="alamat_lengkap" class="w-full bg-gray-50 border rounded-lg p-3 text-sm h-24"><?php echo esc_textarea($val_alamat); ?></textarea></div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Kode Pos</label><input type="text" name="kode_pos" value="<?php echo esc_attr($val_kodepos); ?>" class="w-full bg-gray-50 border rounded-lg p-3 text-sm"></div>
                                <div><label class="text-xs font-bold text-gray-500 uppercase mb-1 block">Link Gmaps</label><input type="text" name="url_gmaps" value="<?php echo esc_attr($val_gmaps); ?>" class="w-full bg-gray-50 border rounded-lg p-3 text-sm"></div>
                            </div>
                        </div>

                        <!-- ONGKIR (Fixed Zones) -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="font-bold text-gray-800 mb-6 border-b pb-3 flex items-center gap-2"><i class="fas fa-truck text-primary"></i> Metode Pengiriman</h3>
                            <div class="space-y-6">
                                <div class="flex justify-between items-center bg-gray-50 p-4 rounded-xl border border-gray-200"><div><h4 class="text-sm font-bold text-gray-800">Ambil di Tempat (Pickup)</h4><p class="text-xs text-gray-500">Pembeli datang ke toko.</p></div><div class="relative inline-block w-12 align-middle select-none"><input type="checkbox" name="allow_pesan_di_tempat" id="toggle-pickup" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer" <?php checked($pedagang->allow_pesan_di_tempat, 1); ?>/><label for="toggle-pickup" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label></div></div>
                                <div class="bg-yellow-50 p-4 rounded-xl border border-yellow-100"><div class="flex justify-between items-center mb-3"><div><h4 class="text-sm font-bold text-yellow-900">Ekspedisi Nasional</h4><p class="text-xs text-yellow-700">JNE, J&T, SiCepat, dll.</p></div><div class="relative inline-block w-12 align-middle select-none"><input type="checkbox" name="shipping_nasional_aktif" id="toggle-nasional" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer" <?php checked($pedagang->shipping_nasional_aktif, 1); ?>/><label for="toggle-nasional" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label></div></div><div id="nasional-settings" class="<?php echo $pedagang->shipping_nasional_aktif ? '' : 'hidden'; ?> animate-fade-in mt-2"><label class="text-xs font-bold text-gray-600">Tarif Dasar (Opsional)</label><input type="number" name="shipping_nasional_harga" value="<?php echo esc_attr($pedagang->shipping_nasional_harga); ?>" class="w-full border border-yellow-300 rounded-lg p-2 text-sm bg-white" placeholder="Contoh: 10000"></div></div>
                                
                                <!-- OJEK LOKAL (ZONA) -->
                                <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                                    <div class="flex justify-between items-center mb-4"><div><h4 class="text-sm font-bold text-blue-900">Ojek Lokal (Kurir Toko)</h4><p class="text-xs text-blue-700">Atur tarif berdasarkan zona dekat/jauh.</p></div><div class="relative inline-block w-12 align-middle select-none"><input type="checkbox" name="shipping_ojek_lokal_aktif" id="toggle-ojek" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer" <?php checked($pedagang->shipping_ojek_lokal_aktif, 1); ?>/><label for="toggle-ojek" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label></div></div>
                                    <div id="ojek-settings" class="<?php echo $pedagang->shipping_ojek_lokal_aktif ? '' : 'hidden'; ?> animate-fade-in space-y-6">
                                        <div>
                                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-blue-200"><div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">1</div><h5 class="text-sm font-bold text-blue-900">Zona Desa (Satu Kecamatan)</h5></div>
                                            <div class="bg-white p-4 rounded-lg border border-blue-100 mb-3 shadow-sm"><label class="text-xs font-bold uppercase text-gray-500 mb-2 block">Area Dekat</label><div class="flex flex-col md:flex-row gap-4"><div class="md:w-1/3"><label class="text-[10px] text-gray-400">Tarif (Rp)</label><input type="number" name="ojek_dekat_harga" value="<?php echo esc_attr($ojek_zona['satu_kecamatan']['dekat']['harga']); ?>" class="w-full border border-gray-300 rounded-lg p-2 text-sm"></div><div class="md:w-2/3"><label class="text-[10px] text-gray-400">Pilih Desa/Kelurahan</label><select name="ojek_dekat_desa_ids[]" id="sel-desa-dekat" multiple class="hidden" data-selected='<?php echo json_encode($ojek_zona['satu_kecamatan']['dekat']['desa_ids']); ?>'></select></div></div></div>
                                            <div class="bg-white p-4 rounded-lg border border-blue-100 shadow-sm"><label class="text-xs font-bold uppercase text-gray-500 mb-2 block">Area Jauh</label><div class="flex flex-col md:flex-row gap-4"><div class="md:w-1/3"><label class="text-[10px] text-gray-400">Tarif (Rp)</label><input type="number" name="ojek_jauh_harga" value="<?php echo esc_attr($ojek_zona['satu_kecamatan']['jauh']['harga']); ?>" class="w-full border border-gray-300 rounded-lg p-2 text-sm"></div><div class="md:w-2/3"><label class="text-[10px] text-gray-400">Pilih Desa/Kelurahan</label><select name="ojek_jauh_desa_ids[]" id="sel-desa-jauh" multiple class="hidden" data-selected='<?php echo json_encode($ojek_zona['satu_kecamatan']['jauh']['desa_ids']); ?>'></select></div></div></div>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 mb-3 pb-2 border-b border-blue-200 pt-4"><div class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">2</div><h5 class="text-sm font-bold text-blue-900">Zona Kecamatan (Satu Kabupaten)</h5></div>
                                            <div class="bg-white p-4 rounded-lg border border-blue-100 mb-3 shadow-sm"><label class="text-xs font-bold uppercase text-gray-500 mb-2 block">Kecamatan Dekat</label><div class="flex flex-col md:flex-row gap-4"><div class="md:w-1/3"><label class="text-[10px] text-gray-400">Tarif (Rp)</label><input type="number" name="ojek_beda_kec_dekat_harga" value="<?php echo esc_attr($ojek_zona['beda_kecamatan']['dekat']['harga']); ?>" class="w-full border border-gray-300 rounded-lg p-2 text-sm"></div><div class="md:w-2/3"><label class="text-[10px] text-gray-400">Pilih Kecamatan</label><select name="ojek_beda_kec_dekat_ids[]" id="sel-kec-dekat" multiple class="hidden" data-selected='<?php echo json_encode($ojek_zona['beda_kecamatan']['dekat']['kecamatan_ids']); ?>'></select></div></div></div>
                                            <div class="bg-white p-4 rounded-lg border border-blue-100 shadow-sm"><label class="text-xs font-bold uppercase text-gray-500 mb-2 block">Kecamatan Jauh</label><div class="flex flex-col md:flex-row gap-4"><div class="md:w-1/3"><label class="text-[10px] text-gray-400">Tarif (Rp)</label><input type="number" name="ojek_beda_kec_jauh_harga" value="<?php echo esc_attr($ojek_zona['beda_kecamatan']['jauh']['harga']); ?>" class="w-full border border-gray-300 rounded-lg p-2 text-sm"></div><div class="md:w-2/3"><label class="text-[10px] text-gray-400">Pilih Kecamatan</label><select name="ojek_beda_kec_jauh_ids[]" id="sel-kec-jauh" multiple class="hidden" data-selected='<?php echo json_encode($ojek_zona['beda_kecamatan']['jauh']['kecamatan_ids']); ?>'></select></div></div></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="sticky bottom-4 z-20 text-right">
                            <button type="submit" class="bg-gray-900 text-white font-bold py-4 px-10 rounded-2xl shadow-xl hover:bg-black transition transform hover:-translate-y-1 w-full md:w-auto flex items-center justify-center gap-2 ml-auto" id="btn-save-settings">
                                <i class="fas fa-save"></i> <span>Simpan Perubahan</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

<!-- ==========================================
     MODALS
========================================== -->
<div id="modal-produk" class="fixed inset-0 z-50 hidden"><div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeProductModal()"></div><div class="absolute right-0 top-0 bottom-0 w-full max-w-lg bg-white shadow-2xl overflow-y-auto transform transition-transform duration-300 translate-x-full flex flex-col" id="modal-produk-panel"><div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50"><h2 class="text-xl font-bold text-gray-800" id="modal-title">Tambah Produk</h2><button onclick="closeProductModal()" class="text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times text-xl"></i></button></div><div class="p-6 flex-1 overflow-y-auto"><form method="POST" enctype="multipart/form-data" id="form-product" onsubmit="showLoading(this)"><?php wp_nonce_field('dw_save_product', 'dw_product_nonce'); ?><input type="hidden" name="dw_action" value="save_product"><input type="hidden" name="produk_id" id="prod_id"><div class="space-y-5"><div class="flex justify-center"><div class="w-full"><label class="block text-sm font-bold text-gray-700 mb-2">Foto Utama</label><label class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition relative overflow-hidden group"><div class="flex flex-col items-center justify-center pt-5 pb-6 text-gray-500 group-hover:text-primary" id="upload-placeholder"><i class="fas fa-cloud-upload-alt text-3xl mb-2"></i><p class="text-xs">Klik untuk upload foto</p></div><img id="prod-prev-img" class="absolute inset-0 w-full h-full object-cover hidden"><input type="file" name="foto_produk" class="hidden" onchange="previewImage(this, 'prod-prev-img'); $('#upload-placeholder').addClass('hidden'); $('#prod-prev-img').removeClass('hidden');"></label></div></div><div><label class="block text-sm font-bold text-gray-700 mb-1">Nama Produk</label><input type="text" name="nama_produk" id="prod_nama" required class="w-full border-gray-300 rounded-lg p-2.5"></div><div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-bold text-gray-700 mb-1">Harga</label><input type="number" name="harga" id="prod_harga" required class="w-full border-gray-300 rounded-lg p-2.5"></div><div><label class="block text-sm font-bold text-gray-700 mb-1">Stok</label><input type="number" name="stok" id="prod_stok" required class="w-full border-gray-300 rounded-lg p-2.5"></div></div><div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-bold text-gray-700 mb-1">Berat (Gr)</label><input type="number" name="berat_gram" id="prod_berat" required class="w-full border-gray-300 rounded-lg p-2.5"></div><div><label class="block text-sm font-bold text-gray-700 mb-1">Kondisi</label><select name="kondisi" id="prod_kondisi" class="w-full border-gray-300 rounded-lg p-2.5 bg-white"><option value="baru">Baru</option><option value="bekas">Bekas</option></select></div></div><div><label class="block text-sm font-bold text-gray-700 mb-1">Kategori</label><select name="kategori" id="prod_kategori" class="w-full border-gray-300 rounded-lg p-2.5 bg-white"><?php foreach($kategori_list as $cat) echo "<option value='$cat'>$cat</option>"; ?><option value="Lainnya">Lainnya</option></select></div><div><label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi</label><textarea name="deskripsi_produk" id="prod_deskripsi" rows="4" class="w-full border-gray-300 rounded-lg p-2.5"></textarea></div></div><div class="mt-8 pt-4 border-t border-gray-100"><button type="submit" class="w-full bg-primary text-white font-bold py-3.5 rounded-xl shadow-lg hover:bg-green-700 transition">Simpan Produk</button></div></form></div></div></div>
<div id="modal-buy" class="fixed inset-0 z-50 hidden"><div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeBuyModal()"></div><div class="absolute inset-0 flex items-center justify-center p-4"><div class="bg-white rounded-3xl p-8 w-full max-w-sm relative shadow-2xl transform transition-all scale-100"><button onclick="closeBuyModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button><div class="text-center mb-6"><div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-3xl mx-auto mb-4"><i class="fas fa-receipt"></i></div><h3 class="font-bold text-xl text-gray-800">Konfirmasi Pembelian</h3><p class="text-sm text-gray-500 mt-1">Paket: <span id="modal-paket-name" class="font-bold text-gray-800"></span></p><p class="text-2xl font-bold text-primary mt-2" id="modal-paket-price"></p></div><form method="post" enctype="multipart/form-data" onsubmit="showLoading(this)"><?php wp_nonce_field('beli_paket_action', 'paket_nonce'); ?><input type="hidden" name="beli_paket" value="1"><input type="hidden" name="id_paket" id="modal-id-paket"><div class="mb-6 bg-gray-50 p-4 rounded-xl border border-dashed border-gray-300"><label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 text-center">Upload Bukti Transfer</label><input type="file" name="bukti_bayar" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"></div><button type="submit" class="w-full bg-gray-900 text-white font-bold py-3.5 rounded-xl hover:bg-black transition shadow-lg">Kirim Bukti</button></form></div></div></div>
<div id="modal-order-detail" class="fixed inset-0 z-50 hidden"><div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeOrderDetailModal()"></div><div class="absolute right-0 top-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl overflow-y-auto transform transition-transform duration-300 translate-x-full flex flex-col" id="modal-order-panel"><div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50 sticky top-0 z-10"><div><h2 class="text-xl font-bold text-gray-800">Detail Pesanan</h2><p class="text-sm text-gray-500" id="det-order-id">#</p></div><button onclick="closeOrderDetailModal()" class="text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times text-xl"></i></button></div><div class="p-6 flex-1 overflow-y-auto space-y-6"><div class="bg-blue-50 p-4 rounded-xl border border-blue-100"><h3 class="font-bold text-blue-900 mb-2 flex items-center gap-2"><i class="fas fa-user"></i> Data Pembeli</h3><div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm"><div><p class="text-gray-500 text-xs uppercase">Nama Penerima</p><p class="font-bold text-gray-800" id="det-penerima">-</p></div><div><p class="text-gray-500 text-xs uppercase">No HP</p><p class="font-bold text-gray-800" id="det-hp">-</p></div><div class="col-span-full"><p class="text-gray-500 text-xs uppercase">Alamat Pengiriman</p><p class="text-gray-800 leading-relaxed" id="det-alamat">-</p></div></div></div><div><h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2"><i class="fas fa-shopping-basket"></i> Item Dipesan</h3><div class="border border-gray-200 rounded-xl overflow-hidden"><table class="w-full text-sm text-left"><thead class="bg-gray-50 text-gray-600 border-b"><tr><th class="p-3">Produk</th><th class="p-3 text-center">Qty</th><th class="p-3 text-right">Total</th></tr></thead><tbody id="det-items-body" class="divide-y divide-gray-100"></tbody><tfoot class="bg-gray-50"><tr><td colspan="2" class="p-3 text-right font-bold">Subtotal</td><td class="p-3 text-right font-bold" id="det-subtotal">Rp 0</td></tr><tr><td colspan="2" class="p-3 text-right font-bold text-gray-600">Ongkir</td><td class="p-3 text-right font-bold text-gray-600" id="det-ongkir">Rp 0</td></tr><tr><td colspan="2" class="p-3 text-right font-bold text-primary text-lg">Total Akhir</td><td class="p-3 text-right font-bold text-primary text-lg" id="det-total">Rp 0</td></tr></tfoot></table></div></div><div class="border-t pt-6"><h3 class="font-bold text-gray-800 mb-4">Update Status Pesanan</h3><form method="POST" id="form-update-order" onsubmit="showLoading(this)"><?php wp_nonce_field('dw_update_order', 'dw_order_nonce'); ?><input type="hidden" name="dw_action" value="update_order_status"><input type="hidden" name="order_id" id="update-order-id"><div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4"><div><label class="block text-xs font-bold text-gray-600 mb-1">Status Pesanan</label><select name="status_pesanan" id="update-status" class="w-full border-gray-300 rounded-lg p-2 text-sm bg-white"><option value="menunggu_konfirmasi">Menunggu Konfirmasi</option><option value="diproses">Diproses</option><option value="dikirim_ekspedisi">Dikirim Ekspedisi</option><option value="diantar_ojek">Diantar Ojek</option><option value="siap_diambil">Siap Diambil</option><option value="selesai">Selesai</option><option value="dibatalkan">Dibatalkan</option></select></div><div><label class="block text-xs font-bold text-gray-600 mb-1">Nomor Resi</label><input type="text" name="no_resi" id="update-resi" class="w-full border-gray-300 rounded-lg p-2 text-sm"></div></div><button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition shadow-lg">Simpan Status</button></form></div></div></div></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    
    // =========================================
    // UI UTILITIES
    // =========================================
    
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('dashboard-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        
        if (sidebar) {
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                if(backdrop) backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                if(backdrop) backdrop.classList.add('hidden');
            }
        }
    }

    function switchTab(tabName) {
        $('.tab-content').removeClass('active'); $('#view-' + tabName).addClass('active');
        $('.nav-item').removeClass('active'); $('#nav-' + tabName).addClass('active');
        
        if (window.innerWidth < 768) {
            const sidebar = document.getElementById('dashboard-sidebar');
            if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
                toggleMobileSidebar();
            }
        }
        const url = new URL(window.location); url.searchParams.set('tab', tabName); window.history.pushState({}, '', url);
    }
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('tab')) switchTab(urlParams.get('tab'));

    function previewImage(input, id) { 
        if (input.files && input.files[0]) { 
            var r = new FileReader(); 
            r.onload = function(e) { $('#'+id).attr('src', e.target.result).removeClass('hidden'); }; 
            r.readAsDataURL(input.files[0]); 
        } 
    }
    function showLoading(f) { $(f).find('button[type="submit"]').prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...'); }

    // =========================================
    // MODALS
    // =========================================
    function openProductModal() { $('#form-product')[0].reset(); $('#prod_id').val(''); $('#modal-title').text('Tambah Produk'); $('#prod-prev-img').addClass('hidden'); $('#upload-placeholder').removeClass('hidden'); $('#modal-produk').removeClass('hidden'); setTimeout(()=>$('#modal-produk-panel').removeClass('translate-x-full'),10); }
    function closeProductModal() { $('#modal-produk-panel').addClass('translate-x-full'); setTimeout(()=>$('#modal-produk').addClass('hidden'),300); }
    function editProduk(p) { openProductModal(); $('#modal-title').text('Edit Produk'); $('#prod_id').val(p.id); $('#prod_nama').val(p.nama_produk); $('#prod_harga').val(p.harga); $('#prod_stok').val(p.stok); $('#prod_berat').val(p.berat_gram); $('#prod_deskripsi').val(p.deskripsi); $('#prod_kategori').val(p.kategori); $('#prod_kondisi').val(p.kondisi); if(p.foto_utama){ $('#prod-prev-img').attr('src', p.foto_utama).removeClass('hidden'); $('#upload-placeholder').addClass('hidden'); } }
    function openBuyModal(id,n,p) { $('#modal-id-paket').val(id); $('#modal-paket-name').text(n); $('#modal-paket-price').text('Rp '+new Intl.NumberFormat('id-ID').format(p)); $('#modal-buy').removeClass('hidden'); }
    function closeBuyModal() { $('#modal-buy').addClass('hidden'); }
    function openOrderDetail(o) { $('#det-order-id').text('#'+o.id); $('#det-penerima').text(o.nama_penerima||'-'); $('#det-hp').text(o.no_hp||'-'); $('#det-alamat').text(o.alamat_kirim||o.alamat_lengkap||'-'); $('#update-order-id').val(o.id); $('#update-status').val(o.status_pesanan); $('#update-resi').val(o.no_resi||''); let h=''; if(o.items){o.items.forEach(i=>{ h+=`<tr><td class="p-3"><div class="font-bold text-gray-800">${i.nama_produk}</div><div class="text-xs text-gray-500">@ Rp ${new Intl.NumberFormat('id-ID').format(i.harga_satuan)}</div></td><td class="p-3 text-center text-gray-600">x${i.jumlah}</td><td class="p-3 text-right font-bold text-gray-800">Rp ${new Intl.NumberFormat('id-ID').format(i.total_harga)}</td></tr>`; });} else { h='<tr><td colspan="3" class="p-4 text-center">No Items</td></tr>'; } $('#det-items-body').html(h); $('#det-subtotal').text('Rp '+new Intl.NumberFormat('id-ID').format(o.sub_total)); $('#det-ongkir').text('Rp '+new Intl.NumberFormat('id-ID').format(o.ongkir)); $('#det-total').text('Rp '+new Intl.NumberFormat('id-ID').format(o.total_pesanan_toko)); $('#modal-order-detail').removeClass('hidden'); setTimeout(()=>$('#modal-order-panel').removeClass('translate-x-full'),10); }
    function closeOrderDetailModal() { $('#modal-order-panel').addClass('translate-x-full'); setTimeout(()=>$('#modal-order-detail').addClass('hidden'),300); }

    // =========================================
    // MULTI-SELECT & ONGKIR LOGIC (FULL)
    // =========================================
    
    // Toggle Logic
    window.toggleOption = function(selId, val) {
        const $el = $(selId);
        const vals = $el.val() || [];
        const index = vals.indexOf(val);
        if (index > -1) { vals.splice(index, 1); } else { vals.push(val); }
        $el.val(vals).trigger('change');
    };

    // UI Builder
    function setupEnhancedMultiSelect(selectId) {
        const $select = $(selectId);
        const containerId = selectId.replace('#', '') + '-wrapper';
        $('#' + containerId).remove();
        
        const wrapper = `
            <div id="${containerId}" class="enhanced-select-wrapper border border-gray-300 rounded-xl overflow-hidden bg-white shadow-sm">
                <div class="selected-area p-3 bg-gray-50 border-b border-gray-100 min-h-[50px] flex flex-wrap gap-2 text-sm text-gray-500">
                    <span class="placeholder-text italic text-xs py-1">Belum ada yang dipilih...</span>
                </div>
                <div class="list-area">
                    <div class="p-2 border-b border-gray-100 bg-white sticky top-0 z-10">
                        <div class="relative"><i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i><input type="text" class="search-input w-full text-xs pl-8 p-2 border border-gray-200 rounded-lg focus:outline-none focus:border-primary transition bg-gray-50 focus:bg-white" placeholder="Cari nama wilayah..."></div>
                    </div>
                    <div class="options-list max-h-48 overflow-y-auto p-1 space-y-1 scrollbar-thin"></div>
                </div>
            </div>`;
        $select.after(wrapper); $select.hide();
        
        $(`#${containerId} .search-input`).on('keyup', function() {
            var val = $(this).val().toLowerCase();
            $(`#${containerId} .options-list .option-item`).filter(function() { $(this).toggle($(this).find('.opt-text').text().toLowerCase().indexOf(val) > -1) });
        });

        function render() {
            const $wrapper = $('#' + containerId);
            const $selectedArea = $wrapper.find('.selected-area');
            const $listArea = $wrapper.find('.options-list');
            $selectedArea.empty(); $listArea.empty();
            let hasSelected = false; let optionsCount = 0;
            
            $select.find('option').each(function() {
                optionsCount++;
                const $opt = $(this); const val = $opt.val(); let text = $opt.text().replace(' (Dipilih di sebelah)', '');
                const isSelected = $opt.is(':selected'); const isDisabled = $opt.is(':disabled');
                
                if (isSelected) {
                    hasSelected = true;
                    $selectedArea.append(`<div class="flex items-center gap-1 bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold animate-fade-in border border-green-200 shadow-sm"><span>${text}</span><button type="button" class="hover:text-green-900 ml-1 bg-green-200 w-4 h-4 rounded-full flex items-center justify-center transition" onclick="window.toggleOption('${selectId}', '${val}')"><i class="fas fa-times text-[10px]"></i></button></div>`);
                }
                
                let itemClass = "option-item p-2 rounded-lg text-xs cursor-pointer flex items-center justify-between transition group ";
                let icon = '<div class="w-4 h-4 border border-gray-300 rounded bg-white group-hover:border-primary"></div>';
                let clickHandler = `onclick="window.toggleOption('${selectId}', '${val}')"`;
                let statusText = '';
                
                if (isDisabled) { itemClass += "bg-gray-50 text-gray-400 cursor-not-allowed opacity-60"; icon = '<i class="fas fa-ban text-gray-300"></i>'; clickHandler = ""; statusText = '<span class="text-[10px] italic ml-2">(Zona Lain)</span>'; } 
                else if (isSelected) { itemClass += "bg-green-50 text-green-700 font-bold border border-green-200"; icon = '<div class="w-4 h-4 bg-green-500 rounded flex items-center justify-center text-white text-[10px] shadow-sm"><i class="fas fa-check"></i></div>'; } 
                else { itemClass += "hover:bg-gray-50 text-gray-700 border border-transparent"; }
                
                $listArea.append(`<div class="${itemClass}" ${clickHandler}><span class="opt-text">${text} ${statusText}</span><span>${icon}</span></div>`);
            });
            
            if (!hasSelected) $selectedArea.html('<span class="placeholder-text italic text-xs py-1 text-gray-400 flex items-center gap-2"><i class="fas fa-info-circle"></i> Hasil pilihan akan muncul di sini...</span>');
            if (optionsCount === 0 || $select.is(':disabled')) $listArea.html('<div class="p-4 text-center text-xs text-gray-400 italic">Data belum dimuat. Silakan pilih lokasi toko terlebih dahulu.</div>');
        }
        render();
        const observer = new MutationObserver(render); observer.observe($select[0], { childList: true, attributes: true });
        $select.on('change render-ui', render);
    }

    function syncExclusion(sId, tId) {
        var sVals = $(sId).val() || []; var $target = $(tId);
        $target.find('option').each(function() {
            var val = $(this).val();
            if (sVals.includes(val)) { $(this).prop('disabled', true); if (!$(this).text().includes('(Dipilih)')) $(this).text($(this).text() + ' (Dipilih di sebelah)'); } 
            else { $(this).prop('disabled', false); $(this).text($(this).text().replace(' (Dipilih di sebelah)', '')); }
        });
        $target.trigger('render-ui');
    }

    // =========================================
    // MAIN INIT
    // =========================================
    jQuery(document).ready(function($){
        // Setup Region Logic
        var els={prov:$('#dw_provinsi'),kota:$('#dw_kota'),kec:$('#dw_kecamatan'),desa:$('#dw_desa')}, data=$('#region-data').data();
        function l(a,pid,el,sel,cb){ el.html('<option>Loading...</option>').prop('disabled',true); var p={action:a}; if(a=='dw_fetch_regencies')p.province_id=pid; if(a=='dw_fetch_districts')p.regency_id=pid; if(a=='dw_fetch_villages')p.district_id=pid; $.get(ajaxurl,p,function(r){ if(r.success){ var o='<option value="">-- Pilih --</option>'; $.each(r.data.data||r.data,function(i,v){ var id=v.id||v.code; o+='<option value="'+id+'" '+(id==sel?'selected':'')+'>'+(v.name||v.nama)+'</option>'; }); el.html(o).prop('disabled',false); if(cb)cb(); }}); }
        function s(el,t){ var txt=$(el).find('option:selected').text(); if(txt!=='Loading...'&&txt!=='-- Pilih --')$(t).val(txt); }
        function fetchDesaForOngkir(id){ 
            if(!id){ $('#sel-desa-dekat, #sel-desa-jauh').empty().prop('disabled',true).trigger('render-ui'); return; }
            $('#sel-desa-dekat, #sel-desa-jauh').prop('disabled',true).trigger('render-ui');
            $.get(ajaxurl, {action:'dw_fetch_villages', district_id:id}, function(r){ if(r.success){ var els=[$('#sel-desa-dekat'),$('#sel-desa-jauh')]; els.forEach(el=>{ el.prop('disabled',false).empty(); $.each(r.data.data||r.data,function(i,v){ var val=v.id||v.code; var isSel=(el.data('selected')||[]).includes(val)?'selected':''; el.append('<option value="'+val+'" '+isSel+'>'+(v.name||v.nama)+'</option>'); }); el.trigger('render-ui'); }); syncExclusion('#sel-desa-dekat','#sel-desa-jauh'); syncExclusion('#sel-desa-jauh','#sel-desa-dekat'); } });
        }
        function fetchKecForOngkir(id){ 
            if(!id){ $('#sel-kec-dekat, #sel-kec-jauh').empty().prop('disabled',true).trigger('render-ui'); return; }
            $('#sel-kec-dekat, #sel-kec-jauh').prop('disabled',true).trigger('render-ui');
            $.get(ajaxurl, {action:'dw_fetch_districts', regency_id:id}, function(r){ if(r.success){ var els=[$('#sel-kec-dekat'),$('#sel-kec-jauh')]; els.forEach(el=>{ el.prop('disabled',false).empty(); $.each(r.data.data||r.data,function(i,v){ var val=v.id||v.code; var isSel=(el.data('selected')||[]).includes(val)?'selected':''; el.append('<option value="'+val+'" '+isSel+'>'+(v.name||v.nama)+'</option>'); }); el.trigger('render-ui'); }); syncExclusion('#sel-kec-dekat','#sel-kec-jauh'); syncExclusion('#sel-kec-jauh','#sel-kec-dekat'); } });
        }

        // Load Initial Regions
        l('dw_fetch_provinces',null,els.prov,data.prov,function(){ 
            if(data.prov) l('dw_fetch_regencies',data.prov,els.kota,data.kota,function(){ 
                if(data.kota) { l('dw_fetch_districts',data.kota,els.kec,data.kec,function(){ 
                    if(data.kec) { l('dw_fetch_villages',data.kec,els.desa,data.desa); fetchDesaForOngkir(data.kec); } 
                }); fetchKecForOngkir(data.kota); }
            }); 
        });

        // Region Events
        els.prov.change(function(){s(this,'#input_provinsi_name'); l('dw_fetch_regencies',$(this).val(),els.kota,null); els.kota.val(''); });
        els.kota.change(function(){s(this,'#input_kabupaten_name'); var id=$(this).val(); l('dw_fetch_districts',id,els.kec,null); fetchKecForOngkir(id); });
        els.kec.change(function(){s(this,'#input_kecamatan_name'); var id=$(this).val(); l('dw_fetch_villages',id,els.desa,null); fetchDesaForOngkir(id); });
        els.desa.change(function(){s(this,'#input_kelurahan_name');});
        
        // Setup Toggles
        $('#toggle-nasional').change(function() { $('#nasional-settings').toggleClass('hidden', !this.checked); });
        $('#toggle-ojek').change(function() { $('#ojek-settings').toggleClass('hidden', !this.checked); });
        
        // Setup Ongkir Multi-selects
        setupEnhancedMultiSelect('#sel-desa-dekat'); setupEnhancedMultiSelect('#sel-desa-jauh'); 
        setupEnhancedMultiSelect('#sel-kec-dekat'); setupEnhancedMultiSelect('#sel-kec-jauh');
        
        // Ongkir Sync Events
        $('#sel-desa-dekat, #sel-desa-jauh').on('change', function() { syncExclusion('#sel-desa-dekat', '#sel-desa-jauh'); syncExclusion('#sel-desa-jauh', '#sel-desa-dekat'); });
        $('#sel-kec-dekat, #sel-kec-jauh').on('change', function() { syncExclusion('#sel-kec-dekat', '#sel-kec-jauh'); syncExclusion('#sel-kec-jauh', '#sel-kec-dekat'); });
    });

    // [BARU] Fungsi Copy to Clipboard
    function copyToClipboard(text) {
        if (!text) return;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Link Pendaftaran berhasil disalin!\n\n' + text);
            }).catch(err => {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    }

    function fallbackCopy(text) {
        let textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            alert('Link Pendaftaran berhasil disalin!\n\n' + text);
        } catch (err) {
            alert('Gagal menyalin kode. Silakan salin manual.');
        }
        document.body.removeChild(textArea);
    }
</script>

<?php get_footer(); ?>