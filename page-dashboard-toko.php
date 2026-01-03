<?php
/**
 * Template Name: Dashboard Toko (Merchant)
 * Description: Dashboard lengkap pedagang. UI/UX Pesanan Ditingkatkan (Search, Counters, Quick Actions).
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
$table_variasi       = $wpdb->prefix . 'dw_produk_variasi'; 
$table_transaksi     = $wpdb->prefix . 'dw_transaksi';
$table_transaksi_sub = $wpdb->prefix . 'dw_transaksi_sub';
$table_items         = $wpdb->prefix . 'dw_transaksi_items';
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
            <p class="text-gray-500 mb-8 leading-relaxed">Maaf, akun Anda belum terdaftar sebagai Mitra UMKM.</p>
            <a href="'.home_url('/daftar-pedagang').'" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold block w-full hover:bg-blue-700 transition shadow-lg">Daftar Sekarang</a>
        </div>
    </div>';
    get_footer();
    exit;
}

$msg = '';
$msg_type = '';

// --- HELPER: FORMAT STATUS BADGE ---
if (!function_exists('dw_get_status_badge')) {
    function dw_get_status_badge($status) {
        $colors = [
            'menunggu_pembayaran'     => 'bg-yellow-50 text-yellow-700 border-yellow-200 ring-yellow-500/20',
            'pembayaran_dikonfirmasi' => 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-emerald-500/20',
            'pembayaran_gagal'        => 'bg-red-50 text-red-700 border-red-200 ring-red-500/20',
            'menunggu_konfirmasi'     => 'bg-orange-50 text-orange-700 border-orange-200 ring-orange-500/20',
            'diproses'                => 'bg-blue-50 text-blue-700 border-blue-200 ring-blue-500/20',
            'dikirim_ekspedisi'       => 'bg-purple-50 text-purple-700 border-purple-200 ring-purple-500/20',
            'diantar_ojek'            => 'bg-purple-50 text-purple-700 border-purple-200 ring-purple-500/20',
            'siap_diambil'            => 'bg-indigo-50 text-indigo-700 border-indigo-200 ring-indigo-500/20',
            'selesai'                 => 'bg-green-50 text-green-700 border-green-200 ring-green-500/20',
            'dibatalkan'              => 'bg-gray-100 text-gray-500 border-gray-200 ring-gray-500/20',
            'menunggu_driver'         => 'bg-orange-50 text-orange-700 border-orange-200 ring-orange-500/20',
            'penawaran_driver'        => 'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200 ring-fuchsia-500/20',
            'nego'                    => 'bg-cyan-50 text-cyan-700 border-cyan-200 ring-cyan-500/20',
            'menunggu_penjemputan'    => 'bg-sky-50 text-sky-700 border-sky-200 ring-sky-500/20',
            'dalam_perjalanan'        => 'bg-teal-50 text-teal-700 border-teal-200 ring-teal-500/20',
        ];
        
        $labels = [
            'menunggu_pembayaran'     => 'Belum Bayar',
            'pembayaran_dikonfirmasi' => 'Sudah Bayar',
            'pembayaran_gagal'        => 'Gagal Bayar',
            'menunggu_konfirmasi'     => 'Perlu Konfirmasi',
            'diproses'                => 'Diproses',
            'dikirim_ekspedisi'       => 'Dikirim Kurir',
            'diantar_ojek'            => 'Ojek OTW',
            'siap_diambil'            => 'Siap Diambil',
            'selesai'                 => 'Selesai',
            'dibatalkan'              => 'Batal',
            'menunggu_driver'         => 'Cari Driver',
            'penawaran_driver'        => 'Tawaran Masuk',
            'nego'                    => 'Nego Ongkir',
            'menunggu_penjemputan'    => 'Menunggu Jemput',
            'dalam_perjalanan'        => 'Driver OTW',
        ];

        $c = isset($colors[$status]) ? $colors[$status] : 'bg-gray-50 text-gray-600 border-gray-200';
        $l = isset($labels[$status]) ? $labels[$status] : ucfirst(str_replace('_', ' ', $status));
        return "<span class='px-2.5 py-1 rounded-full text-[10px] font-semibold border ring-1 ring-inset inline-flex items-center gap-1 $c'>{$l}</span>";
    }
}

// ==========================================
// FORM HANDLERS
// ==========================================

// --- HANDLER 0: VERIFIKASI PEMBAYARAN & UPDATE STATUS ---
if (isset($_POST['dw_action']) && $_POST['dw_action'] == 'verify_payment_order') {
    if ( isset($_POST['dw_order_nonce']) && wp_verify_nonce($_POST['dw_order_nonce'], 'dw_verify_order') ) {
        $order_id       = intval($_POST['order_id']); 
        $parent_trx_id  = intval($_POST['parent_trx_id']); 
        $decision       = sanitize_text_field($_POST['decision']); 
        $current_time   = current_time('mysql');
        
        if ($decision === 'accept') {
            $wpdb->update($table_transaksi_sub, ['status_pesanan' => 'diproses'], ['id' => $order_id, 'id_pedagang' => $pedagang->id]);
            // Hanya update global jika status sebelumnya menunggu
            $wpdb->update($table_transaksi, ['status_transaksi' => 'pembayaran_dikonfirmasi', 'tanggal_pembayaran' => $current_time], ['id' => $parent_trx_id]);
            $msg = "Pembayaran diterima. Pesanan diproses."; $msg_type = "success";
        } elseif ($decision === 'reject') {
            $reason = sanitize_textarea_field($_POST['rejection_reason']);
            $wpdb->update($table_transaksi_sub, ['status_pesanan' => 'dibatalkan', 'alasan_batal' => 'Bukti Bayar Ditolak: ' . $reason], ['id' => $order_id, 'id_pedagang' => $pedagang->id]);
            $wpdb->update($table_transaksi, ['status_transaksi' => 'pembayaran_gagal'], ['id' => $parent_trx_id]);
            $msg = "Pembayaran ditolak. Pesanan dibatalkan."; $msg_type = "warning";
        } elseif ($decision === 'update_shipping') {
            $new_status = sanitize_text_field($_POST['status_pesanan']);
            $no_resi    = sanitize_text_field($_POST['no_resi']);
            
            // --- LOGIC KUOTA BERKURANG SAAT SELESAI ---
            // 1. Ambil status saat ini sebelum diupdate
            $old_data = $wpdb->get_row($wpdb->prepare("SELECT status_pesanan FROM $table_transaksi_sub WHERE id = %d AND id_pedagang = %d", $order_id, $pedagang->id));
            $old_status = $old_data ? $old_data->status_pesanan : '';

            $data_update = ['status_pesanan' => $new_status];
            if(!empty($no_resi)) { $data_update['no_resi'] = $no_resi; }
            
            $wpdb->update($table_transaksi_sub, $data_update, ['id' => $order_id, 'id_pedagang' => $pedagang->id]);
            
            // 2. Jika status berubah MENJADI 'selesai' dan SEBELUMNYA bukan 'selesai', kurangi kuota
            if ($new_status === 'selesai' && $old_status !== 'selesai') {
                $wpdb->query($wpdb->prepare("UPDATE $table_pedagang SET sisa_transaksi = sisa_transaksi - 1 WHERE id = %d", $pedagang->id));
                // Refresh data pedagang agar UI terupdate
                $pedagang = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_user = %d", $current_user_id));
            }

            $msg = "Status pesanan diperbarui."; $msg_type = "success";
        }
    }
}

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

        // Logic Relasi Desa
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
        
        // Sync User Meta
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

// --- HANDLER 2: PRODUK (SAVE/UPDATE + VARIASI + GALERI) ---
if ( isset($_POST['dw_action']) && $_POST['dw_action'] == 'save_product' ) {
    if ( isset($_POST['dw_product_nonce']) && wp_verify_nonce($_POST['dw_product_nonce'], 'dw_save_product') ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php');
        require_once( ABSPATH . 'wp-admin/includes/image.php');
        
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

        // 1. Upload Foto Utama
        if (!empty($_FILES['foto_produk']['name'])) {
            $upload = wp_handle_upload($_FILES['foto_produk'], ['test_form' => false]);
            if (isset($upload['url']) && !isset($upload['error'])) {
                $prod_data['foto_utama'] = $upload['url'];
            }
        }

        // 2. Upload Galeri (Multiple Files)
        if (!empty($_FILES['galeri_produk']['name'][0])) {
            $galeri_urls = [];
            $files = $_FILES['galeri_produk'];
            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $file = [
                        'name' => $files['name'][$key],
                        'type' => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error' => $files['error'][$key],
                        'size' => $files['size'][$key]
                    ];
                    $upload = wp_handle_upload($file, ['test_form' => false]);
                    if (!isset($upload['error']) && isset($upload['url'])) {
                        $galeri_urls[] = $upload['url'];
                    }
                }
            }
            // Simpan JSON jika ada upload baru (Replace)
            if(!empty($galeri_urls)) {
                $prod_data['galeri'] = json_encode($galeri_urls);
            }
        }

        $prod_id = 0;
        if(!empty($_POST['produk_id'])) {
            // Update
            $prod_id = intval($_POST['produk_id']);
            $wpdb->update($table_produk, $prod_data, ['id' => $prod_id, 'id_pedagang' => $pedagang->id]);
            $msg = 'Produk berhasil diperbarui.';
        } else {
            // Insert
            $prod_data['slug'] = sanitize_title($_POST['nama_produk']) . '-' . time();
            $prod_data['created_at'] = current_time('mysql');
            $wpdb->insert($table_produk, $prod_data);
            $prod_id = $wpdb->insert_id;
            $msg = 'Produk berhasil ditambahkan.';
        }

        // 3. Handle Variasi Produk
        // Hapus variasi lama jika update
        if ($prod_id > 0) {
            $wpdb->delete($table_variasi, ['id_produk' => $prod_id]);
            
            if (isset($_POST['var_nama']) && is_array($_POST['var_nama'])) {
                $var_nama  = $_POST['var_nama'];
                $var_harga = $_POST['var_harga'];
                $var_stok  = $_POST['var_stok'];
                
                foreach ($var_nama as $k => $nm) {
                    if (!empty($nm)) {
                        $wpdb->insert($table_variasi, [
                            'id_produk'          => $prod_id,
                            'deskripsi_variasi' => sanitize_text_field($nm),
                            'harga_variasi'      => floatval($var_harga[$k]),
                            'stok_variasi'       => intval($var_stok[$k]),
                            'is_default'         => ($k === 0) ? 1 : 0
                        ]);
                    }
                }
            }
        }

        $msg_type = 'success';
    }
}

// --- HANDLER 3: HAPUS PRODUK ---
if ( isset($_GET['act']) && $_GET['act'] == 'del_prod' && isset($_GET['id']) ) {
     $id_del = intval($_GET['id']);
     $wpdb->delete($table_produk, ['id' => $id_del, 'id_pedagang' => $pedagang->id]);
     $wpdb->delete($table_variasi, ['id_produk' => $id_del]); // Hapus variasi juga
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
                'persentase_komisi_referrer' => 5, // Default percentage
                'url_bukti_bayar' => $upload['url'],
                'status' => 'pending',
                'created_at' => current_time('mysql')
            ]);
            $msg = "Pembelian diajukan. Mohon tunggu verifikasi admin."; 
            $msg_type = "success";
        }
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

// --- Fetch Produk ---
$produk_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_produk WHERE id_pedagang = %d ORDER BY created_at DESC", $pedagang->id));
if ($produk_list) {
    foreach ($produk_list as $p) {
        $p->variasi = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_variasi WHERE id_produk = %d ORDER BY id ASC", $p->id));
        $p->galeri_list = !empty($p->galeri) ? json_decode($p->galeri) : [];
    }
}

// --- Fetch Order & Pre-calculate Counts ---
$order_query = "
    SELECT sub.*, 
           t.kode_unik, t.bukti_pembayaran, t.status_transaksi as global_status, 
           t.nama_penerima, t.no_hp, t.alamat_lengkap AS alamat_kirim
    FROM $table_transaksi_sub sub
    JOIN $table_transaksi t ON sub.id_transaksi = t.id
    WHERE sub.id_pedagang = %d
    ORDER BY sub.created_at DESC
";
$order_list = $wpdb->get_results($wpdb->prepare($order_query, $pedagang->id));

// Calculate counts for tabs
$order_counts = [
    'all' => count($order_list),
    'belum_bayar' => 0,
    'perlu_dikirim' => 0,
    'dikirim' => 0,
    'selesai' => 0,
    'dibatalkan' => 0
];

foreach ($order_list as $o) {
    $pay_status = $o->global_status;
    $order_status = $o->status_pesanan;
    
    if ($pay_status == 'menunggu_pembayaran') { $order_counts['belum_bayar']++; } 
    elseif (in_array($order_status, ['dibatalkan', 'pembayaran_gagal'])) { $order_counts['dibatalkan']++; } 
    elseif ($order_status == 'selesai') { $order_counts['selesai']++; } 
    elseif (in_array($order_status, ['dikirim_ekspedisi', 'diantar_ojek', 'dalam_perjalanan', 'siap_diambil'])) { $order_counts['dikirim']++; } 
    elseif (in_array($order_status, ['menunggu_konfirmasi', 'diproses', 'menunggu_driver', 'penawaran_driver', 'nego', 'menunggu_penjemputan'])) { $order_counts['perlu_dikirim']++; }
    
    // Fetch limited item summary per order for quick view
    $o->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_items WHERE id_sub_transaksi = %d", $o->id));
    $o->total_items = count($o->items);
    $o->first_item_name = !empty($o->items) ? $o->items[0]->nama_produk : 'Produk';
}

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
<!-- Google Fonts: Inter -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<!-- CDN Tailwind & Icons -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>
    tailwind.config = { 
        theme: { 
            extend: { 
                colors: { 
                    primary: '#16a34a', 
                    secondary: '#1e293b',
                    surface: '#f8fafc' 
                },
                fontFamily: {
                    sans: ['Inter', 'sans-serif'],
                }
            } 
        } 
    }
</script>
<style>
    body { font-family: 'Inter', sans-serif; }
    .tab-content { display: none; opacity: 0; transform: translateY(10px); transition: all 0.3s ease-out; }
    .tab-content.active { display: block; opacity: 1; transform: translateY(0); }
    .nav-item { transition: all 0.2s ease-in-out; border-radius: 0.75rem; margin-bottom: 0.25rem; }
    .nav-item:hover { background-color: #f1f5f9; color: #0f172a; }
    .nav-item.active { background-color: #f0fdf4; color: #16a34a; font-weight: 600; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
    .toggle-checkbox:checked { right: 0; border-color: #16a34a; }
    .toggle-checkbox:checked + .toggle-label { background-color: #16a34a; }
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    .animate-fade-in { animation: fadeIn 0.3s ease-out; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    
    /* Modern Order Tabs */
    .order-tab-btn { 
        @apply px-4 py-2.5 text-xs font-bold text-gray-500 rounded-full transition-all duration-200 hover:bg-gray-100 hover:text-gray-800 whitespace-nowrap border border-transparent flex items-center gap-2; 
    }
    .order-tab-btn.active { 
        @apply bg-gray-900 text-white shadow-md transform scale-105; 
    }
    .badge-count {
        @apply px-1.5 py-0.5 rounded-md text-[10px] bg-gray-200 text-gray-600 transition-colors;
    }
    .order-tab-btn.active .badge-count {
        @apply bg-gray-700 text-white;
    }
    
    /* Card Hover Effect */
    .dashboard-card { @apply transition-all duration-300 hover:-translate-y-1 hover:shadow-lg; }
</style>

<div class="bg-gray-50 min-h-screen font-sans flex overflow-hidden text-slate-800">
    
    <!-- HEADER MOBILE (FIXED & Z-INDEX 100) -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white/80 backdrop-blur-md border-b border-gray-200 z-[100] flex items-center justify-between px-4 shadow-sm">
        <div class="flex items-center gap-3">
            <button onclick="toggleMobileSidebar()" class="text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition-colors active:scale-95"><i class="fas fa-bars text-xl"></i></button>
            <span class="font-bold text-gray-900 flex items-center gap-2 text-sm tracking-tight"><i class="fas fa-store text-primary"></i> Merchant Panel</span>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?php echo home_url('/keranjang'); ?>" class="text-gray-600 p-2 rounded-xl hover:bg-gray-100 relative" title="Keranjang Belanja">
                <i class="fas fa-shopping-cart text-lg"></i>
            </a>
            <div class="w-9 h-9 rounded-full bg-gray-200 overflow-hidden border border-gray-200 shadow-sm">
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
    <main class="flex-1 md:ml-64 p-4 md:p-8 pt-20 md:pt-8 overflow-y-auto h-screen pb-24 bg-surface">
        
        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl border-l-4 <?php echo ($msg_type=='error')?'bg-red-50 text-red-700 border-red-500':'bg-emerald-50 text-emerald-700 border-emerald-500'; ?> flex items-center gap-3 shadow-sm animate-fade-in">
                <i class="fas <?php echo ($msg_type=='error')?'fa-exclamation-circle':'fa-check-circle'; ?> text-lg"></i> 
                <div class="text-sm font-medium"><?php echo $msg; ?></div>
            </div>
        <?php endif; ?>

        <!-- VIEW 1: RINGKASAN -->
        <div id="view-ringkasan" class="tab-content active">
             <div class="mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Ringkasan Toko</h1>
                <p class="text-gray-500 mt-1">Pantau performa dan bagikan link toko Anda.</p>
             </div>

             <!-- KARTU KODE REFERRAL -->
             <div class="bg-gradient-to-br from-indigo-600 to-violet-600 rounded-2xl p-6 mb-8 text-white shadow-xl shadow-indigo-200 relative overflow-hidden group dashboard-card">
                <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider mb-2 border border-white/10">
                            <i class="fas fa-gift"></i> Link Referral
                        </div>
                        <h2 class="text-3xl font-mono font-bold tracking-wide text-white mb-2"><?php echo !empty($pedagang->kode_referral_saya) ? esc_html($pedagang->kode_referral_saya) : '-'; ?></h2>
                        <p class="text-indigo-100 text-sm max-w-lg leading-relaxed">Bagikan kode ini kepada pembeli baru. Dapatkan komisi dari setiap transaksi mereka.</p>
                    </div>
                    <div>
                        <?php $ref_link = home_url('/register?ref=' . $pedagang->kode_referral_saya); ?>
                        <?php if(!empty($pedagang->kode_referral_saya)): ?>
                        <button onclick="copyToClipboard('<?php echo esc_js($ref_link); ?>')" class="bg-white text-indigo-600 font-bold py-3 px-6 rounded-xl shadow-lg transition-all active:scale-95 flex items-center gap-2 hover:bg-gray-50">
                            <i class="fas fa-link"></i> <span>Salin Link</span>
                        </button>
                        <?php else: ?>
                        <span class="text-xs bg-white/20 px-3 py-1 rounded-lg border border-white/20">Kode belum digenerate</span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Decoration Circles -->
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-indigo-400/20 rounded-full blur-xl"></div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Pendapatan -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm dashboard-card relative overflow-hidden">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Total Pendapatan</p>
                            <h3 class="text-2xl font-bold text-gray-900 tracking-tight">Rp <?php echo number_format($revenue?:0,0,',','.'); ?></h3>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg"><i class="fas fa-wallet"></i></div>
                    </div>
                    <div class="mt-4 flex items-center gap-1 text-xs text-emerald-600 bg-emerald-50 w-fit px-2 py-1 rounded-md font-medium">
                        <i class="fas fa-arrow-up"></i> <span>Terupdate</span>
                    </div>
                </div>
                
                <!-- Produk -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm dashboard-card relative overflow-hidden">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Total Produk</p>
                            <h3 class="text-2xl font-bold text-gray-900 tracking-tight"><?php echo $count_produk; ?> <span class="text-sm font-normal text-gray-400">Item</span></h3>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-lg"><i class="fas fa-box"></i></div>
                    </div>
                    <div class="mt-4 w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: 70%"></div>
                    </div>
                </div>

                <!-- Kuota -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm dashboard-card relative overflow-hidden">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500 mb-1">Sisa Kuota</p>
                            <h3 class="text-2xl font-bold text-gray-900 tracking-tight"><?php echo $pedagang->sisa_transaksi; ?> <span class="text-sm font-normal text-gray-400">Trx</span></h3>
                        </div>
                        <div class="w-10 h-10 rounded-lg bg-orange-50 text-orange-600 flex items-center justify-center text-lg"><i class="fas fa-ticket-alt"></i></div>
                    </div>
                    <button onclick="switchTab('paket')" class="mt-4 text-xs font-bold text-orange-600 hover:text-orange-700 flex items-center gap-1">Top Up Kuota <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>

        <!-- VIEW 2: PRODUK -->
        <div id="view-produk" class="tab-content hidden">
             <div class="flex flex-col md:flex-row justify-between items-end md:items-center mb-8 gap-4">
                 <div>
                     <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Daftar Produk</h1>
                     <p class="text-sm text-gray-500 mt-1">Kelola stok, harga, dan variasi produk Anda.</p>
                 </div>
                 <button onclick="openProductModal()" class="bg-gray-900 hover:bg-black text-white px-5 py-2.5 rounded-xl font-semibold shadow-lg shadow-gray-200 transition-all active:scale-95 flex items-center gap-2 text-sm">
                     <i class="fas fa-plus"></i> Tambah Produk
                 </button>
             </div>
             
             <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                 <?php if($produk_list): foreach($produk_list as $p): ?>
                 <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-3 group hover:shadow-xl hover:border-gray-200 transition-all duration-300 flex flex-col h-full relative">
                      <!-- Image -->
                      <div class="relative h-48 bg-gray-50 rounded-xl overflow-hidden mb-3">
                          <?php if(!empty($p->foto_utama)): ?>
                             <img src="<?php echo esc_url($p->foto_utama); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                          <?php else: ?>
                             <div class="flex items-center justify-center h-full text-gray-300 bg-gray-100"><i class="fas fa-image text-3xl"></i></div>
                          <?php endif; ?>
                          
                          <div class="absolute top-2 left-2">
                              <span class="bg-white/90 backdrop-blur px-2 py-1 rounded-md text-[10px] font-bold shadow-sm text-gray-700 border border-gray-100 uppercase tracking-wide"><?php echo $p->kondisi; ?></span>
                          </div>
                          
                          <!-- Action Overlay -->
                          <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-200 gap-2">
                              <button onclick='editProduk(<?php echo htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8'); ?>)' class="w-9 h-9 rounded-full bg-white text-gray-800 flex items-center justify-center hover:bg-primary hover:text-white transition shadow-lg"><i class="fas fa-pen"></i></button>
                              <a href="?act=del_prod&id=<?php echo $p->id; ?>" onclick="return confirm('Hapus produk ini?')" class="w-9 h-9 rounded-full bg-white text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition shadow-lg"><i class="fas fa-trash"></i></a>
                          </div>

                          <?php if($p->stok < 5): ?>
                              <div class="absolute bottom-2 right-2 bg-red-500 text-white px-2 py-1 rounded-md text-[10px] font-bold shadow-sm flex items-center gap-1">
                                  <i class="fas fa-exclamation-circle"></i> Stok <?php echo $p->stok; ?>
                              </div>
                          <?php endif; ?>
                      </div>
                      
                      <!-- Content -->
                      <div class="px-1 flex-1 flex flex-col">
                          <h4 class="font-bold text-gray-800 mb-1 leading-snug line-clamp-2 min-h-[2.5rem] group-hover:text-primary transition-colors"><?php echo esc_html($p->nama_produk); ?></h4>
                          
                          <div class="flex items-center justify-between mb-2 mt-auto">
                              <p class="text-gray-900 font-extrabold text-lg">Rp <?php echo number_format($p->harga,0,',','.'); ?></p>
                          </div>
                          
                          <div class="flex items-center gap-2 text-xs text-gray-500 border-t border-gray-50 pt-2 mt-2">
                              <span class="bg-gray-100 px-2 py-0.5 rounded text-[10px] font-medium text-gray-600">Stok: <?php echo $p->stok; ?></span>
                              <?php if(!empty($p->variasi)): ?>
                                  <span class="bg-blue-50 text-blue-600 px-2 py-0.5 rounded text-[10px] font-medium">+<?php echo count($p->variasi); ?> Var</span>
                              <?php endif; ?>
                          </div>
                      </div>
                 </div>
                 <?php endforeach; else: ?>
                    <div class="col-span-full py-20 text-center bg-white rounded-3xl border border-dashed border-gray-300">
                        <div class="inline-block p-4 rounded-full bg-gray-50 mb-4 text-gray-400"><i class="fas fa-box-open text-4xl"></i></div>
                        <h3 class="text-lg font-bold text-gray-900">Belum ada produk</h3>
                        <p class="text-gray-500 mb-6 text-sm">Toko Anda masih kosong. Mulai jualan sekarang!</p>
                        <button onclick="openProductModal()" class="text-primary font-bold hover:underline text-sm flex items-center justify-center gap-2"><i class="fas fa-plus"></i> Tambah Produk Pertama</button>
                    </div>
                 <?php endif; ?>
             </div>
        </div>

        <!-- VIEW 3: PESANAN MASUK (FILTERED TABS ENHANCED) -->
        <div id="view-pesanan" class="tab-content hidden">
            <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Pesanan Masuk</h1>
                    <p class="text-sm text-gray-500 mt-1">Kelola transaksi, verifikasi pembayaran, dan update resi.</p>
                </div>
                
                <!-- SEARCH BAR -->
                <div class="relative w-full md:w-64">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    <input type="text" id="search-orders" placeholder="Cari pesanan / nama..." class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 text-sm focus:ring-2 focus:ring-gray-200 outline-none transition" onkeyup="filterOrders(currentTab)">
                </div>
            </div>
            
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                
                <!-- MODERN TABS WITH COUNTERS -->
                <div class="border-b border-gray-100 bg-white p-2 sticky top-0 z-10 overflow-x-auto no-scrollbar">
                    <div class="flex space-x-2 min-w-max" id="order-tabs">
                        <button onclick="filterOrders('all')" class="order-tab-btn active" data-tab="all">
                            Semua <span class="badge-count"><?php echo $order_counts['all']; ?></span>
                        </button>
                        <button onclick="filterOrders('belum_bayar')" class="order-tab-btn" data-tab="belum_bayar">
                            Belum Bayar <span class="badge-count"><?php echo $order_counts['belum_bayar']; ?></span>
                        </button>
                        <button onclick="filterOrders('perlu_dikirim')" class="order-tab-btn" data-tab="perlu_dikirim">
                            Perlu Dikirim <span class="badge-count bg-orange-100 text-orange-700"><?php echo $order_counts['perlu_dikirim']; ?></span>
                        </button>
                        <button onclick="filterOrders('dikirim')" class="order-tab-btn" data-tab="dikirim">
                            Dikirim <span class="badge-count"><?php echo $order_counts['dikirim']; ?></span>
                        </button>
                        <button onclick="filterOrders('selesai')" class="order-tab-btn" data-tab="selesai">
                            Selesai <span class="badge-count bg-green-100 text-green-700"><?php echo $order_counts['selesai']; ?></span>
                        </button>
                        <button onclick="filterOrders('dibatalkan')" class="order-tab-btn" data-tab="dibatalkan">
                            Dibatalkan <span class="badge-count bg-red-100 text-red-700"><?php echo $order_counts['dibatalkan']; ?></span>
                        </button>
                    </div>
                </div>

                <?php if($order_list): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left" id="order-table">
                        <thead class="bg-gray-50/50 border-b border-gray-200">
                            <tr class="text-gray-500 uppercase text-[11px] tracking-wider font-semibold">
                                <th class="py-4 px-6">Info Transaksi</th>
                                <th class="py-4 px-6">Pelanggan</th>
                                <th class="py-4 px-6">Produk</th>
                                <th class="py-4 px-6">Total & Status</th>
                                <th class="py-4 px-6 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($order_list as $o): 
                                $pay_status = $o->global_status;
                                $order_status = $o->status_pesanan;

                                // Logic Kategori Filter
                                $filter_cat = 'all';
                                if ($pay_status == 'menunggu_pembayaran') { $filter_cat = 'belum_bayar'; } 
                                elseif (in_array($order_status, ['dibatalkan', 'pembayaran_gagal'])) { $filter_cat = 'dibatalkan'; } 
                                elseif ($order_status == 'selesai') { $filter_cat = 'selesai'; } 
                                elseif (in_array($order_status, ['dikirim_ekspedisi', 'diantar_ojek', 'dalam_perjalanan', 'siap_diambil'])) { $filter_cat = 'dikirim'; } 
                                elseif (in_array($order_status, ['menunggu_konfirmasi', 'diproses', 'menunggu_driver', 'penawaran_driver', 'nego', 'menunggu_penjemputan'])) { $filter_cat = 'perlu_dikirim'; }
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150 group order-row" data-category="<?php echo $filter_cat; ?>" data-search="<?php echo strtolower($o->kode_unik . ' ' . $o->nama_penerima); ?>">
                                <!-- COL 1: ID & DATE -->
                                <td class="py-4 px-6 align-top whitespace-nowrap">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-mono text-xs font-bold bg-gray-100 text-gray-700 px-2 py-0.5 rounded border border-gray-200"><?php echo esc_html($o->kode_unik); ?></span>
                                    </div>
                                    <div class="text-[11px] text-gray-400 font-medium"><?php echo date('d M Y, H:i', strtotime($o->created_at)); ?></div>
                                </td>
                                
                                <!-- COL 2: CUSTOMER -->
                                <td class="py-4 px-6 align-top">
                                    <div class="font-bold text-gray-900 text-sm"><?php echo esc_html($o->nama_penerima); ?></div>
                                    <div class="text-xs text-gray-500 flex items-center gap-1 mt-1 font-medium bg-green-50 text-green-700 px-2 py-0.5 rounded w-fit">
                                        <i class="fab fa-whatsapp"></i> <?php echo esc_html($o->no_hp); ?>
                                    </div>
                                </td>

                                <!-- COL 3: PRODUCT SUMMARY -->
                                <td class="py-4 px-6 align-top">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                                            <i class="fas fa-shopping-bag"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-gray-800 line-clamp-1"><?php echo esc_html($o->first_item_name); ?></p>
                                            <p class="text-[10px] text-gray-500"><?php echo $o->total_items > 1 ? '+'.($o->total_items-1).' item lainnya' : '1 item total'; ?></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- COL 4: STATUS & TOTAL -->
                                <td class="py-4 px-6 align-top whitespace-nowrap">
                                    <div class="font-bold text-gray-900 text-base mb-2">Rp <?php echo number_format($o->total_pesanan_toko, 0, ',', '.'); ?></div>
                                    <div class="flex flex-col gap-1.5 items-start">
                                        <?php echo dw_get_status_badge($pay_status); ?>
                                        <?php echo dw_get_status_badge($order_status); ?>
                                    </div>
                                </td>

                                <!-- COL 5: ACTIONS (QUICK & DETAIL) -->
                                <td class="py-4 px-6 text-right align-middle">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- QUICK ACTION BUTTONS -->
                                        <?php if($pay_status == 'menunggu_pembayaran' || $order_status == 'menunggu_konfirmasi'): ?>
                                            <button onclick='openOrderDetail(<?php echo htmlspecialchars(json_encode($o), ENT_QUOTES, 'UTF-8'); ?>)' class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-all">
                                                Verifikasi
                                            </button>
                                        <?php elseif($order_status == 'diproses'): ?>
                                            <button onclick='openOrderDetail(<?php echo htmlspecialchars(json_encode($o), ENT_QUOTES, 'UTF-8'); ?>)' class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-sm transition-all">
                                                <i class="fas fa-truck mr-1"></i> Kirim
                                            </button>
                                        <?php endif; ?>

                                        <button onclick='openOrderDetail(<?php echo htmlspecialchars(json_encode($o), ENT_QUOTES, 'UTF-8'); ?>)' class="bg-white border border-gray-200 hover:border-gray-300 text-gray-700 hover:bg-gray-50 w-8 h-8 rounded-lg flex items-center justify-center transition-all shadow-sm" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- EMPTY STATE ROW -->
                            <tr id="empty-order-state" class="hidden">
                                <td colspan="5" class="py-16 text-center">
                                    <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300 border border-gray-100"><i class="fas fa-search text-3xl"></i></div>
                                    <h3 class="text-gray-900 font-bold text-lg">Tidak ada pesanan ditemukan</h3>
                                    <p class="text-gray-500 text-sm mt-1">Coba ubah kata kunci pencarian atau filter Anda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-20">
                        <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300 border border-gray-100"><i class="fas fa-clipboard-list text-3xl"></i></div>
                        <h3 class="text-gray-900 font-bold text-lg">Belum ada pesanan</h3>
                        <p class="text-gray-500 text-sm mt-1">Pesanan yang masuk akan tampil di sini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- VIEW 4: PAKET -->
        <div id="view-paket" class="tab-content hidden">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Paket & Kuota</h1>
            <p class="text-gray-500 mb-8">Tingkatkan kapasitas toko Anda dengan paket transaksi.</p>
            
            <div class="bg-gray-900 text-white p-8 rounded-3xl shadow-2xl mb-12 relative overflow-hidden flex flex-col md:flex-row items-center justify-between gap-6 group">
                <div class="relative z-10">
                    <p class="text-gray-400 uppercase text-xs font-bold tracking-wider mb-2">Sisa Kuota Transaksi</p>
                    <div class="flex items-baseline gap-2">
                        <h2 class="text-7xl font-black text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400"><?php echo number_format($pedagang->sisa_transaksi); ?></h2>
                        <span class="text-xl font-medium text-gray-400">Trx</span>
                    </div>
                    <p class="text-gray-400 text-sm mt-3 max-w-md">Kuota akan berkurang otomatis setiap kali pesanan berhasil diselesaikan.</p>
                </div>
                <div class="relative z-10 hidden md:block">
                    <div class="w-28 h-28 rounded-full bg-white/5 backdrop-blur border border-white/10 flex items-center justify-center text-5xl shadow-lg">
                        <i class="fas fa-ticket-alt text-emerald-400"></i>
                    </div>
                </div>
                <!-- Background decoration -->
                <div class="absolute right-0 bottom-0 opacity-5 transform translate-x-10 translate-y-10 group-hover:scale-110 transition duration-700">
                    <i class="fas fa-chart-line text-[12rem]"></i>
                </div>
            </div>

            <h3 class="font-bold text-xl text-gray-800 mb-6 flex items-center gap-2"><i class="fas fa-layer-group text-primary"></i> Pilihan Paket</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                <?php if($pakets): foreach($pakets as $index => $pk): $is_popular = ($index === 1); ?>
                <div class="bg-white rounded-3xl border <?php echo $is_popular ? 'border-primary shadow-xl shadow-primary/10 relative transform md:-translate-y-4 z-10' : 'border-gray-200 shadow-sm hover:shadow-lg'; ?> transition duration-300 p-6 flex flex-col h-full">
                    <?php if($is_popular): ?><div class="absolute top-0 right-0 bg-primary text-white text-[10px] font-bold px-4 py-1.5 rounded-bl-2xl rounded-tr-2xl uppercase tracking-wide shadow-sm">Paling Laris</div><?php endif; ?>
                    <div class="mb-6">
                        <h3 class="font-bold text-xl text-gray-800 mb-2"><?php echo esc_html($pk->nama_paket); ?></h3>
                        <div class="flex items-baseline gap-1">
                            <span class="text-xs font-bold text-gray-400">Rp</span>
                            <span class="text-4xl font-extrabold text-gray-900"><?php echo number_format($pk->harga,0,',','.'); ?></span>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 font-medium">Sekali bayar, aktif selamanya.</p>
                    </div>
                    <div class="space-y-4 mb-8 flex-1">
                        <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl">
                            <div class="w-10 h-10 rounded-full bg-white border border-gray-200 text-green-600 flex items-center justify-center text-sm shadow-sm"><i class="fas fa-check"></i></div>
                            <div>
                                <span class="block font-bold text-gray-800 text-lg"><?php echo $pk->jumlah_transaksi; ?></span>
                                <span class="text-xs text-gray-500">Kuota Transaksi</span>
                            </div>
                        </div>
                    </div>
                    <button onclick="openBuyModal(<?php echo $pk->id; ?>, '<?php echo esc_js($pk->nama_paket); ?>', <?php echo $pk->harga; ?>)" class="w-full py-4 rounded-2xl font-bold transition shadow-lg hover:shadow-xl transform active:scale-95 <?php echo $is_popular ? 'bg-primary text-white hover:bg-green-700' : 'bg-gray-900 text-white hover:bg-black'; ?>">
                        Pilih Paket
                    </button>
                </div>
                <?php endforeach; endif; ?>
            </div>
            
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2"><i class="fas fa-history text-gray-400"></i> Riwayat Pembelian</h3>
                </div>
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 uppercase text-[11px] tracking-wider border-b border-gray-100">
                        <tr><th class="p-4 font-bold">Tanggal</th><th class="p-4 font-bold">Paket</th><th class="p-4 font-bold">Harga</th><th class="p-4 font-bold text-right">Status</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if($histori_paket): foreach($histori_paket as $h): 
                            $st_color = ($h->status=='disetujui') ? 'bg-green-100 text-green-700' : (($h->status=='ditolak') ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'); 
                        ?>
                        <tr class="hover:bg-gray-50/50">
                            <td class="p-4 text-gray-500 font-mono text-xs"><?php echo date('d/m/Y', strtotime($h->created_at)); ?></td>
                            <td class="p-4 font-bold text-gray-800"><?php echo esc_html($h->nama_paket_snapshot); ?></td>
                            <td class="p-4 text-gray-600">Rp <?php echo number_format($h->harga_paket,0,',','.'); ?></td>
                            <td class="p-4 text-right"><span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide <?php echo $st_color; ?>"><?php echo ucfirst($h->status); ?></span></td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="4" class="p-8 text-center text-gray-400 italic">Belum ada riwayat pembelian.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- VIEW 5: PENGATURAN -->
        <div id="view-pengaturan" class="tab-content hidden">
            <div class="max-w-6xl mx-auto">
                <h1 class="text-2xl font-bold text-gray-900 mb-6">Pengaturan Toko</h1>
                <form method="POST" enctype="multipart/form-data" id="settings-form" onsubmit="showLoading(this)">
                    <?php wp_nonce_field('dw_save_settings', 'dw_settings_nonce'); ?>
                    <input type="hidden" name="dw_action" value="save_store_settings">

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        <!-- KIRI (Identitas) -->
                        <div class="space-y-8">
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <h3 class="font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4 flex items-center gap-2 text-sm uppercase tracking-wide"><i class="fas fa-store text-primary"></i> Identitas Toko</h3>
                                <div class="space-y-5">
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Nama Toko</label><input type="text" name="nama_toko" value="<?php echo esc_attr($pedagang->nama_toko); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none"></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Kode Referral</label><div class="relative"><input type="text" value="<?php echo esc_attr($pedagang->kode_referral_saya); ?>" readonly class="w-full bg-gray-100 border border-gray-200 rounded-xl p-3 text-sm text-gray-600 font-mono"><button type="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($pedagang->kode_referral_saya); ?>'); alert('Kode disalin!');" class="absolute right-2 top-2 bg-white border border-gray-200 p-1.5 rounded-lg hover:bg-gray-50 text-gray-600 transition"><i class="fas fa-copy"></i></button></div></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Nama Pemilik</label><input type="text" name="nama_pemilik" value="<?php echo esc_attr($pedagang->nama_pemilik); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none"></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">WhatsApp</label><input type="text" name="nomor_wa" value="<?php echo esc_attr($pedagang->nomor_wa); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none"></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">NIK</label><input type="text" name="nik" value="<?php echo esc_attr($pedagang->nik); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary transition outline-none"></div>
                                    
                                    <div class="grid grid-cols-2 gap-4 pt-2">
                                        <div>
                                            <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Logo Toko</label>
                                            <div class="relative w-24 h-24 group mx-auto">
                                                <img src="<?php echo $pedagang->foto_profil ? esc_url($pedagang->foto_profil) : 'https://placehold.co/150'; ?>" class="w-full h-full rounded-full object-cover border-4 border-white shadow-md" id="prev-profil">
                                                <label class="absolute inset-0 flex items-center justify-center bg-black/50 rounded-full opacity-0 group-hover:opacity-100 cursor-pointer text-white transition-opacity"><i class="fas fa-camera"></i><input type="file" name="foto_profil" class="hidden" onchange="previewImage(this, 'prev-profil')"></label>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Foto KTP</label>
                                            <label class="block w-full h-24 border-2 border-dashed border-gray-300 rounded-xl hover:bg-gray-50 hover:border-gray-400 cursor-pointer flex flex-col items-center justify-center text-gray-400 transition">
                                                <i class="fas fa-id-card mb-1"></i><span class="text-[10px]">Upload</span>
                                                <input type="file" name="foto_ktp" class="hidden">
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="text-xs font-bold text-gray-500 uppercase block mb-2">Foto Sampul</label>
                                        <div class="relative h-32 rounded-xl bg-gray-100 overflow-hidden group border border-gray-200">
                                            <img src="<?php echo $pedagang->foto_sampul ? esc_url($pedagang->foto_sampul) : ''; ?>" id="prev-sampul" class="w-full h-full object-cover <?php echo $pedagang->foto_sampul?'':'hidden'; ?>">
                                            <label class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer text-white text-xs font-bold transition-opacity">Ganti Sampul<input type="file" name="foto_sampul" class="hidden" onchange="previewImage(this, 'prev-sampul')"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <h3 class="font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4 flex items-center gap-2 text-sm uppercase tracking-wide"><i class="fas fa-wallet text-primary"></i> Rekening & QRIS</h3>
                                <div class="space-y-5">
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Bank</label><input type="text" name="nama_bank" value="<?php echo esc_attr($pedagang->nama_bank); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none"></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">No Rekening</label><input type="text" name="no_rekening" value="<?php echo esc_attr($pedagang->no_rekening); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none"></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Atas Nama</label><input type="text" name="atas_nama_rekening" value="<?php echo esc_attr($pedagang->atas_nama_rekening); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none"></div>
                                    
                                    <!-- QRIS Preview -->
                                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200">
                                        <label class="text-xs font-bold text-gray-500 uppercase mb-2 block">Upload QRIS</label>
                                        <div class="mb-3 flex justify-center">
                                            <?php if (!empty($pedagang->qris_image_url)): ?>
                                                <img src="<?php echo esc_url($pedagang->qris_image_url); ?>" id="prev-qris" class="w-40 h-auto rounded-lg border border-gray-200 shadow-sm object-contain bg-white p-2">
                                            <?php else: ?>
                                                <img id="prev-qris" class="w-40 h-auto rounded-lg border border-gray-200 shadow-sm hidden">
                                                <div id="qris-placeholder" class="w-40 h-40 bg-white rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-xs flex-col gap-2">
                                                    <i class="fas fa-qrcode text-2xl"></i><span>Belum ada</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <input type="file" name="foto_qris" class="text-xs w-full text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 transition cursor-pointer" onchange="previewImage(this, 'prev-qris'); document.getElementById('qris-placeholder')?.classList.add('hidden');">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- KANAN (Lokasi & Ongkir) -->
                        <div class="lg:col-span-2 space-y-8">
                            <!-- LOKASI -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <h3 class="font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4 flex items-center gap-2 text-sm uppercase tracking-wide"><i class="fas fa-map-marker-alt text-primary"></i> Alamat Lengkap</h3>
                                <div id="region-data" data-prov="<?php echo esc_attr($val_prov_id); ?>" data-kota="<?php echo esc_attr($val_kota_id); ?>" data-kec="<?php echo esc_attr($val_kec_id); ?>" data-kel="<?php echo esc_attr($val_kel_id); ?>"></div>
                                <input type="hidden" name="provinsi_nama" id="input_provinsi_name" value="<?php echo esc_attr($val_prov_nama); ?>">
                                <input type="hidden" name="kabupaten_nama" id="input_kabupaten_name" value="<?php echo esc_attr($val_kota_nama); ?>">
                                <input type="hidden" name="kecamatan_nama" id="input_kecamatan_name" value="<?php echo esc_attr($val_kec_nama); ?>">
                                <input type="hidden" name="kelurahan_nama" id="input_kelurahan_name" value="<?php echo esc_attr($val_kel_nama); ?>">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Provinsi</label><select name="api_provinsi_id" id="dw_provinsi" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none"></select></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Kota/Kabupaten</label><select name="api_kabupaten_id" id="dw_kota" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none" disabled></select></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Kecamatan</label><select name="api_kecamatan_id" id="dw_kecamatan" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none" disabled></select></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Desa/Kelurahan</label><select name="api_kelurahan_id" id="dw_desa" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none" disabled></select></div>
                                </div>
                                <div class="mb-5"><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Alamat Jalan</label><textarea name="alamat_lengkap" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm h-28 focus:ring-2 focus:ring-primary/20 outline-none resize-none"><?php echo esc_textarea($val_alamat); ?></textarea></div>
                                <div class="grid grid-cols-2 gap-5">
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Kode Pos</label><input type="text" name="kode_pos" value="<?php echo esc_attr($val_kodepos); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none"></div>
                                    <div><label class="text-xs font-bold text-gray-500 uppercase mb-1.5 block">Link Google Maps</label><input type="text" name="url_gmaps" value="<?php echo esc_attr($pedagang->url_gmaps); ?>" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-primary/20 outline-none"></div>
                                </div>
                            </div>

                            <!-- ONGKIR -->
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <h3 class="font-bold text-gray-900 mb-6 border-b border-gray-100 pb-4 flex items-center gap-2 text-sm uppercase tracking-wide"><i class="fas fa-truck text-primary"></i> Pengaturan Pengiriman</h3>
                                <div class="space-y-6">
                                    <!-- Pickup -->
                                    <div class="flex justify-between items-center bg-gray-50 p-4 rounded-xl border border-gray-200 hover:border-gray-300 transition">
                                        <div><h4 class="text-sm font-bold text-gray-800">Ambil di Tempat (Pickup)</h4><p class="text-xs text-gray-500">Pembeli mengambil pesanan ke lokasi Anda.</p></div>
                                        <div class="relative inline-block w-12 align-middle select-none"><input type="checkbox" name="allow_pesan_di_tempat" id="toggle-pickup" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer" <?php checked($pedagang->allow_pesan_di_tempat, 1); ?>/><label for="toggle-pickup" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label></div>
                                    </div>
                                    
                                    <!-- Ekspedisi Nasional -->
                                    <div class="bg-yellow-50 p-5 rounded-2xl border border-yellow-100">
                                        <div class="flex justify-between items-center mb-3">
                                            <div><h4 class="text-sm font-bold text-yellow-900">Ekspedisi Nasional</h4><p class="text-xs text-yellow-700">JNE, J&T, SiCepat, dll.</p></div>
                                            <div class="relative inline-block w-12 align-middle select-none"><input type="checkbox" name="shipping_nasional_aktif" id="toggle-nasional" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer" <?php checked($pedagang->shipping_nasional_aktif, 1); ?>/><label for="toggle-nasional" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label></div>
                                        </div>
                                        <div id="nasional-settings" class="<?php echo $pedagang->shipping_nasional_aktif ? '' : 'hidden'; ?> animate-fade-in mt-4 pt-4 border-t border-yellow-200/50">
                                            <label class="text-xs font-bold text-yellow-800 mb-1 block">Tarif Dasar (Opsional)</label>
                                            <input type="number" name="shipping_nasional_harga" value="<?php echo esc_attr($pedagang->shipping_nasional_harga); ?>" class="w-full border border-yellow-200 rounded-xl p-3 text-sm bg-white focus:ring-2 focus:ring-yellow-400/30 outline-none" placeholder="Contoh: 10000">
                                        </div>
                                    </div>
                                    
                                    <!-- Ojek Lokal -->
                                    <div class="bg-blue-50 p-5 rounded-2xl border border-blue-100">
                                        <div class="flex justify-between items-center mb-4">
                                            <div><h4 class="text-sm font-bold text-blue-900">Kurir Toko (Ojek Lokal)</h4><p class="text-xs text-blue-700">Atur tarif berdasarkan zona desa/kecamatan.</p></div>
                                            <div class="relative inline-block w-12 align-middle select-none"><input type="checkbox" name="shipping_ojek_lokal_aktif" id="toggle-ojek" class="toggle-checkbox absolute block w-6 h-6 rounded-full bg-white border-4 appearance-none cursor-pointer" <?php checked($pedagang->shipping_ojek_lokal_aktif, 1); ?>/><label for="toggle-ojek" class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"></label></div>
                                        </div>
                                        
                                        <div id="ojek-settings" class="<?php echo $pedagang->shipping_ojek_lokal_aktif ? '' : 'hidden'; ?> animate-fade-in space-y-6 mt-4 pt-4 border-t border-blue-200/50">
                                            <div>
                                                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-blue-200/50"><div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold">1</div><h5 class="text-sm font-bold text-blue-900">Zona Desa (Satu Kecamatan)</h5></div>
                                                
                                                <div class="bg-white p-4 rounded-xl border border-blue-100 mb-3 shadow-sm">
                                                    <label class="text-xs font-bold uppercase text-gray-500 mb-2 block tracking-wide">Area Dekat</label>
                                                    <div class="flex flex-col md:flex-row gap-4">
                                                        <div class="md:w-1/3">
                                                            <label class="text-[10px] text-gray-400 block mb-1">Tarif (Rp)</label>
                                                            <input type="number" name="ojek_dekat_harga" value="<?php echo esc_attr($ojek_zona['satu_kecamatan']['dekat']['harga']); ?>" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm bg-gray-50 focus:bg-white transition">
                                                        </div>
                                                        <div class="md:w-2/3">
                                                            <label class="text-[10px] text-gray-400 block mb-1">Pilih Desa/Kelurahan</label>
                                                            <select name="ojek_dekat_desa_ids[]" id="sel-desa-dekat" multiple class="hidden" data-selected='<?php echo json_encode($ojek_zona['satu_kecamatan']['dekat']['desa_ids']); ?>'></select>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="bg-white p-4 rounded-xl border border-blue-100 shadow-sm">
                                                    <label class="text-xs font-bold uppercase text-gray-500 mb-2 block tracking-wide">Area Jauh</label>
                                                    <div class="flex flex-col md:flex-row gap-4">
                                                        <div class="md:w-1/3"><label class="text-[10px] text-gray-400 block mb-1">Tarif (Rp)</label><input type="number" name="ojek_jauh_harga" value="<?php echo esc_attr($ojek_zona['satu_kecamatan']['jauh']['harga']); ?>" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm bg-gray-50 focus:bg-white transition"></div>
                                                        <div class="md:w-2/3"><label class="text-[10px] text-gray-400 block mb-1">Pilih Desa/Kelurahan</label><select name="ojek_jauh_desa_ids[]" id="sel-desa-jauh" multiple class="hidden" data-selected='<?php echo json_encode($ojek_zona['satu_kecamatan']['jauh']['desa_ids']); ?>'></select></div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-blue-200/50 pt-4"><div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold">2</div><h5 class="text-sm font-bold text-blue-900">Zona Kecamatan (Satu Kabupaten)</h5></div>
                                                
                                                <div class="bg-white p-4 rounded-xl border border-blue-100 mb-3 shadow-sm">
                                                    <label class="text-xs font-bold uppercase text-gray-500 mb-2 block tracking-wide">Kecamatan Dekat</label>
                                                    <div class="flex flex-col md:flex-row gap-4">
                                                        <div class="md:w-1/3"><label class="text-[10px] text-gray-400 block mb-1">Tarif (Rp)</label><input type="number" name="ojek_beda_kec_dekat_harga" value="<?php echo esc_attr($ojek_zona['beda_kecamatan']['dekat']['harga']); ?>" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm bg-gray-50 focus:bg-white transition"></div>
                                                        <div class="md:w-2/3"><label class="text-[10px] text-gray-400 block mb-1">Pilih Kecamatan</label><select name="ojek_beda_kec_dekat_ids[]" id="sel-kec-dekat" multiple class="hidden" data-selected='<?php echo json_encode($ojek_zona['beda_kecamatan']['dekat']['kecamatan_ids']); ?>'></select></div>
                                                    </div>
                                                </div>
                                                
                                                <div class="bg-white p-4 rounded-xl border border-blue-100 shadow-sm">
                                                    <label class="text-xs font-bold uppercase text-gray-500 mb-2 block tracking-wide">Kecamatan Jauh</label>
                                                    <div class="flex flex-col md:flex-row gap-4">
                                                        <div class="md:w-1/3"><label class="text-[10px] text-gray-400 block mb-1">Tarif (Rp)</label><input type="number" name="ojek_beda_kec_jauh_harga" value="<?php echo esc_attr($ojek_zona['beda_kecamatan']['jauh']['harga']); ?>" class="w-full border border-gray-200 rounded-lg p-2.5 text-sm bg-gray-50 focus:bg-white transition"></div>
                                                        <div class="md:w-2/3"><label class="text-[10px] text-gray-400 block mb-1">Pilih Kecamatan</label><select name="ojek_beda_kec_jauh_ids[]" id="sel-kec-jauh" multiple class="hidden" data-selected='<?php echo json_encode($ojek_zona['beda_kecamatan']['jauh']['kecamatan_ids']); ?>'></select></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="sticky bottom-6 z-20 text-right">
                                <button type="submit" class="bg-gray-900 text-white font-bold py-4 px-10 rounded-2xl shadow-xl hover:bg-black transition-all transform hover:-translate-y-1 w-full md:w-auto flex items-center justify-center gap-2 ml-auto" id="btn-save-settings">
                                    <i class="fas fa-save"></i> <span>Simpan Perubahan</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- GENERATOR QR CODE MEJA -->
                <div class="mt-12 border-t border-gray-200 pt-10">
                    <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-qrcode text-primary"></i> Generator QR Code Meja</h3>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <p class="text-sm text-gray-500 mb-6">Buat QR Code unik untuk setiap meja agar pelanggan bisa memesan langsung (Dine In).</p>
                        <div class="flex flex-wrap items-end gap-4 mb-6">
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nomor Mulai</label><input type="number" id="qr-start" value="1" class="w-24 border border-gray-200 rounded-xl p-2.5 text-sm"></div>
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Sampai</label><input type="number" id="qr-end" value="10" class="w-24 border border-gray-200 rounded-xl p-2.5 text-sm"></div>
                            <button onclick="generateQRLinks()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm h-11 transition shadow-md">Generate QR</button>
                            <button onclick="printQRList()" class="bg-gray-800 hover:bg-gray-900 text-white px-6 py-2.5 rounded-xl font-bold text-sm hidden h-11 transition shadow-md" id="btn-print-qr"><i class="fas fa-print mr-2"></i> Cetak</button>
                        </div>
                        <div id="qr-result-container" class="hidden">
                            <div class="overflow-x-auto border border-gray-200 rounded-2xl">
                                <table class="w-full text-sm text-left">
                                    <thead class="bg-gray-50 text-gray-600 border-b border-gray-200"><tr><th class="p-4 font-bold">Meja</th><th class="p-4 font-bold">Link</th><th class="p-4 text-center font-bold">Preview</th><th class="p-4 text-right font-bold">Aksi</th></tr></thead>
                                    <tbody id="qr-table-body" class="divide-y divide-gray-100 bg-white"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- MODAL LIGHTBOX (Backdrop improved) -->
<div id="lightbox-modal" class="fixed inset-0 z-[70] hidden flex items-center justify-center bg-black/95 backdrop-blur-sm p-4 transition-all duration-300" onclick="this.classList.add('hidden')">
    <img id="lightbox-img" src="" class="max-w-full max-h-[90vh] rounded-lg shadow-2xl object-contain">
    <button class="absolute top-4 right-4 text-white text-3xl hover:text-gray-300"><i class="fas fa-times"></i></button>
</div>

<!-- MODAL ORDER DETAIL (Enhanced UI) -->
<div id="modal-order-detail" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeOrderDetailModal()"></div>
    <div class="absolute right-0 top-0 bottom-0 w-full max-w-4xl bg-white shadow-2xl overflow-y-auto transform transition-transform duration-300 translate-x-full flex flex-col" id="modal-order-panel">
        
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10 shadow-sm">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">Detail Pesanan</h2>
                <div class="flex items-center gap-3 mt-1">
                    <span class="text-xs text-gray-500 font-mono bg-gray-100 px-2 py-1 rounded-md border border-gray-200" id="det-kode-unik">TRX-000</span>
                    <span class="text-xs text-gray-400" id="det-tanggal">-</span>
                </div>
            </div>
            <button onclick="closeOrderDetailModal()" class="w-10 h-10 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 hover:text-gray-800 transition flex items-center justify-center"><i class="fas fa-times text-lg"></i></button>
        </div>
        
        <div class="p-8 flex-1 overflow-y-auto bg-gray-50/50">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- KIRI -->
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-sm uppercase tracking-wide border-b border-gray-100 pb-2"><i class="fas fa-user-circle text-gray-400"></i> Info Pembeli</h3>
                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between items-center"><span class="text-gray-500">Nama Penerima</span><span class="font-bold text-gray-900 text-base" id="det-penerima">-</span></div>
                            <div class="flex justify-between items-center"><span class="text-gray-500">Kontak</span><span class="font-medium text-green-600 bg-green-50 px-2 py-1 rounded" id="det-hp">-</span></div>
                            <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 mt-2">
                                <p class="text-gray-400 text-xs mb-1 font-bold uppercase">Alamat Pengiriman</p>
                                <p class="font-medium text-gray-800 leading-relaxed" id="det-alamat">-</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                            <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wide flex items-center gap-2"><i class="fas fa-shopping-basket text-primary"></i> Daftar Produk</h3>
                        </div>
                        <table class="w-full text-sm text-left">
                            <tbody id="det-items-body" class="divide-y divide-gray-100"></tbody>
                            <tfoot class="bg-gray-50 text-xs">
                                <tr><td colspan="2" class="p-4 text-right text-gray-500">Ongkos Kirim (<span id="det-kurir" class="font-semibold">-</span>)</td><td class="p-4 text-right font-bold text-gray-700" id="det-ongkir">Rp 0</td></tr>
                                <tr class="text-sm bg-gray-100"><td colspan="2" class="p-4 text-right font-bold text-gray-900">TOTAL TOTAL</td><td class="p-4 text-right font-black text-primary text-lg" id="det-total">Rp 0</td></tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- KANAN -->
                <div class="space-y-6">
                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-sm uppercase tracking-wide border-b border-gray-100 pb-2"><i class="fas fa-receipt text-gray-400"></i> Bukti Pembayaran</h3>
                        <div id="payment-proof-container" class="mb-4 flex justify-center bg-gray-50 rounded-xl p-4 min-h-[150px] items-center"></div>
                        
                        <div id="verification-actions" class="hidden animate-fade-in">
                            <p class="text-sm text-gray-600 mb-4 text-center font-medium">Apakah bukti pembayaran ini valid?</p>
                            <div class="grid grid-cols-2 gap-4">
                                <button onclick="setVerificationAction('reject')" class="bg-white text-red-600 border border-red-200 py-3 rounded-xl text-sm font-bold hover:bg-red-50 transition shadow-sm">Tolak</button>
                                <button onclick="setVerificationAction('accept')" class="bg-green-600 text-white py-3 rounded-xl text-sm font-bold hover:bg-green-700 shadow-lg shadow-green-200 transition">Terima Pembayaran</button>
                            </div>
                        </div>
                        
                        <form method="POST" id="form-verify-payment" class="hidden mt-4 pt-4 border-t border-gray-100" onsubmit="showLoading(this)">
                            <?php wp_nonce_field('dw_verify_order', 'dw_order_nonce'); ?>
                            <input type="hidden" name="dw_action" value="verify_payment_order">
                            <input type="hidden" name="order_id" id="verify-order-id">
                            <input type="hidden" name="parent_trx_id" id="verify-parent-id">
                            <input type="hidden" name="decision" id="verify-decision">
                            
                            <div id="reject-reason-box" class="hidden mb-4">
                                <label class="block text-xs font-bold text-red-600 mb-1.5 uppercase">Alasan Penolakan</label>
                                <textarea name="rejection_reason" class="w-full border border-red-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-red-200 outline-none bg-red-50" rows="2" placeholder="Contoh: Nominal transfer tidak sesuai..."></textarea>
                            </div>
                            
                            <button type="submit" class="w-full py-3.5 rounded-xl text-sm font-bold transition shadow-lg text-white" id="btn-submit-verify">Konfirmasi</button>
                            <button type="button" onclick="cancelVerification()" class="w-full mt-3 text-sm font-medium text-gray-500 hover:text-gray-800">Batal</button>
                        </form>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
                        <h3 class="font-bold text-gray-900 mb-4 text-sm uppercase tracking-wide border-b border-gray-100 pb-2">Update Status</h3>
                        <form method="POST" onsubmit="showLoading(this)">
                            <?php wp_nonce_field('dw_verify_order', 'dw_order_nonce'); ?>
                            <input type="hidden" name="dw_action" value="verify_payment_order">
                            <input type="hidden" name="decision" value="update_shipping">
                            <input type="hidden" name="order_id" id="update-order-id">
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase">Status Pesanan</label>
                                    <select name="status_pesanan" id="update-status" class="w-full border-gray-200 rounded-xl p-3 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition">
                                        <option value="menunggu_konfirmasi">Menunggu Konfirmasi</option>
                                        <option value="diproses">Diproses</option>
                                        <option value="dikirim_ekspedisi">Dikirim Ekspedisi</option>
                                        <option value="diantar_ojek">Diantar Ojek</option>
                                        <option value="siap_diambil">Siap Diambil</option>
                                        <option value="selesai">Selesai</option>
                                        <option value="dibatalkan">Dibatalkan</option>
                                        <optgroup label="Ojek Driver">
                                            <option value="menunggu_driver">Menunggu Driver</option>
                                            <option value="penawaran_driver">Penawaran Driver</option>
                                            <option value="nego">Nego</option>
                                            <option value="menunggu_penjemputan">Menunggu Penjemputan</option>
                                            <option value="dalam_perjalanan">Dalam Perjalanan</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase">Nomor Resi (Opsional)</label>
                                    <input type="text" name="no_resi" id="update-resi" class="w-full border-gray-200 rounded-xl p-3 text-sm bg-gray-50 focus:bg-white focus:ring-2 focus:ring-blue-100 outline-none transition" placeholder="Masukkan nomor resi jika ada">
                                </div>
                                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-200">Simpan Status</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PRODUK (Refined) -->
<div id="modal-produk" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeProductModal()"></div>
    <div class="absolute right-0 top-0 bottom-0 w-full max-w-lg bg-white shadow-2xl overflow-y-auto transform transition-transform duration-300 translate-x-full flex flex-col" id="modal-produk-panel">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10 shadow-sm">
            <h2 class="text-xl font-bold text-gray-900" id="modal-title">Tambah Produk</h2>
            <button onclick="closeProductModal()" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6 flex-1 overflow-y-auto bg-gray-50/50">
            <form method="POST" enctype="multipart/form-data" id="form-product" onsubmit="showLoading(this)">
                <?php wp_nonce_field('dw_save_product', 'dw_product_nonce'); ?>
                <input type="hidden" name="dw_action" value="save_product">
                <input type="hidden" name="produk_id" id="prod_id">
                
                <div class="space-y-6">
                    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Foto Utama</label>
                        <label class="flex flex-col items-center justify-center w-full h-52 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer bg-gray-50 hover:bg-gray-100 transition relative overflow-hidden group">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6 text-gray-400 group-hover:text-primary transition" id="upload-placeholder">
                                <i class="fas fa-cloud-upload-alt text-4xl mb-2"></i>
                                <p class="text-sm font-medium">Klik untuk upload</p>
                            </div>
                            <img id="prod-prev-img" class="absolute inset-0 w-full h-full object-cover hidden">
                            <input type="file" name="foto_produk" class="hidden" onchange="previewImage(this, 'prod-prev-img'); $('#upload-placeholder').addClass('hidden'); $('#prod-prev-img').removeClass('hidden');">
                        </label>
                        <div class="mt-3">
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Galeri Tambahan</label>
                            <input type="file" name="galeri_produk[]" multiple class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" onchange="previewGallery(this)">
                            <div id="prev-galeri" class="grid grid-cols-4 gap-2 mt-3 empty:hidden"></div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Nama Produk</label>
                            <input type="text" name="nama_produk" id="prod_nama" required class="w-full border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Harga (Rp)</label><input type="number" name="harga" id="prod_harga" required class="w-full border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none"></div>
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Stok</label><input type="number" name="stok" id="prod_stok" required class="w-full border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none"></div>
                        </div>
                    </div>

                    <div class="bg-gray-100 p-4 rounded-xl border border-gray-200">
                        <div class="flex justify-between items-center mb-3">
                            <label class="block text-xs font-bold text-gray-700 uppercase">Variasi (Opsional)</label>
                            <button type="button" onclick="addVariasiRow()" class="text-xs bg-white border border-gray-300 px-3 py-1.5 rounded-lg shadow-sm hover:bg-gray-50 font-bold"><i class="fas fa-plus mr-1"></i> Tambah</button>
                        </div>
                        <div id="variasi-container" class="space-y-2"></div>
                    </div>

                    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Berat (Gr)</label><input type="number" name="berat_gram" id="prod_berat" required class="w-full border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none"></div>
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Kondisi</label><select name="kondisi" id="prod_kondisi" class="w-full border-gray-200 rounded-lg p-2.5 bg-white text-sm outline-none"><option value="baru">Baru</option><option value="bekas">Bekas</option></select></div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Kategori</label>
                            <select name="kategori" id="prod_kategori" class="w-full border-gray-200 rounded-lg p-2.5 bg-white text-sm outline-none">
                                <?php foreach($kategori_list as $cat) echo "<option value='$cat'>$cat</option>"; ?>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-1.5">Deskripsi</label>
                            <textarea name="deskripsi_produk" id="prod_deskripsi" rows="4" class="w-full border-gray-200 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 pt-4 border-t border-gray-200 sticky bottom-0 bg-gray-50 py-4 -mx-6 px-6">
                    <button type="submit" class="w-full bg-gray-900 text-white font-bold py-4 rounded-xl shadow-lg hover:bg-black transition transform active:scale-95">Simpan Produk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL BUY -->
<div id="modal-buy" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeBuyModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-8 w-full max-w-sm relative shadow-2xl transform transition-all scale-100 border border-gray-100">
            <button onclick="closeBuyModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 transition"><i class="fas fa-times text-lg"></i></button>
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center text-4xl mx-auto mb-4 shadow-sm border border-blue-100"><i class="fas fa-receipt"></i></div>
                <h3 class="font-bold text-2xl text-gray-900">Konfirmasi Pembelian</h3>
                <div class="bg-gray-50 rounded-xl p-3 mt-4 border border-gray-100">
                    <p class="text-sm text-gray-500">Paket: <span id="modal-paket-name" class="font-bold text-gray-800"></span></p>
                    <p class="text-2xl font-black text-gray-900 mt-1" id="modal-paket-price"></p>
                </div>
            </div>
            <form method="post" enctype="multipart/form-data" onsubmit="showLoading(this)">
                <?php wp_nonce_field('beli_paket_action', 'paket_nonce'); ?>
                <input type="hidden" name="beli_paket" value="1">
                <input type="hidden" name="id_paket" id="modal-id-paket">
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wide mb-2 text-center">Upload Bukti Transfer</label>
                    <input type="file" name="bukti_bayar" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-600 file:text-white hover:file:bg-blue-700 cursor-pointer bg-gray-50 rounded-xl border border-gray-200">
                </div>
                <button type="submit" class="w-full bg-gray-900 text-white font-bold py-4 rounded-xl hover:bg-black transition shadow-lg transform active:scale-95">Kirim Bukti Pembayaran</button>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
    
    // UI Utilities
    function toggleMobileSidebar() {
        const sidebar = document.getElementById('dashboard-sidebar');
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
        } else {
            sidebar.classList.add('-translate-x-full');
        }
    }

    // Default tab
    let currentTab = 'all';

    function switchTab(tabName) {
        $('.tab-content').removeClass('active'); 
        
        setTimeout(() => {
            $('.tab-content').hide();
            $('#view-' + tabName).show();
            setTimeout(() => $('#view-' + tabName).addClass('active'), 50);
        }, 150);

        $('.nav-item').removeClass('active'); $('#nav-' + tabName).addClass('active');
        if (window.innerWidth < 768) {
            const sidebar = document.getElementById('dashboard-sidebar');
            if (sidebar && !sidebar.classList.contains('-translate-x-full')) toggleMobileSidebar();
        }
        const url = new URL(window.location); url.searchParams.set('tab', tabName); window.history.pushState({}, '', url);
    }
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.get('tab')) {
        $('.tab-content').hide().removeClass('active'); // Reset initial state
        $('#view-' + urlParams.get('tab')).show().addClass('active');
        $('.nav-item').removeClass('active'); $('#nav-' + urlParams.get('tab')).addClass('active');
    }

    function previewImage(input, id) { 
        if (input.files && input.files[0]) { 
            var r = new FileReader(); 
            r.onload = function(e) { $('#'+id).attr('src', e.target.result).removeClass('hidden'); }; 
            r.readAsDataURL(input.files[0]); 
        } 
    }
    function showLoading(f) { 
        const btn = $(f).find('button[type="submit"]');
        const originalText = btn.text();
        btn.prop('disabled',true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...'); 
    }
    function openLightbox(src) { $('#lightbox-img').attr('src', src); $('#lightbox-modal').removeClass('hidden'); }

    // --- ORDER FILTERING & SEARCH ---
    function filterOrders(category) {
        currentTab = category;
        $('.order-tab-btn').removeClass('active');
        $(`.order-tab-btn[data-tab="${category}"]`).addClass('active');

        const searchQuery = $('#search-orders').val().toLowerCase();
        let visibleCount = 0;

        $('.order-row').each(function() {
            const rowCat = $(this).data('category');
            const rowSearch = $(this).data('search');
            
            const matchCategory = (category === 'all' || rowCat === category);
            const matchSearch = rowSearch.includes(searchQuery);

            if (matchCategory && matchSearch) {
                $(this).removeClass('hidden');
                visibleCount++;
            } else {
                $(this).addClass('hidden');
            }
        });

        if (visibleCount === 0) {
            $('#empty-order-state').removeClass('hidden');
        } else {
            $('#empty-order-state').addClass('hidden');
        }
    }

    // --- ORDER DETAIL & VERIFICATION LOGIC ---
    function openOrderDetail(o) {
        $('#det-kode-unik').text(o.kode_unik);
        $('#det-tanggal').text(o.created_at);
        $('#det-penerima').text(o.nama_penerima || '-');
        $('#det-hp').text(o.no_hp || '-');
        $('#det-alamat').text(o.alamat_kirim || '-');
        
        // Formatting Currency
        const fmt = new Intl.NumberFormat('id-ID');
        $('#det-ongkir').text('Rp ' + fmt.format(o.ongkir));
        $('#det-total').text('Rp ' + fmt.format(o.total_pesanan_toko));
        $('#det-kurir').text(o.metode_pengiriman);

        $('#update-order-id').val(o.id);
        $('#update-status').val(o.status_pesanan);
        $('#update-resi').val(o.no_resi || '');
        $('#verify-order-id').val(o.id);
        $('#verify-parent-id').val(o.id_transaksi);

        let h = '';
        if (o.items && o.items.length) {
            o.items.forEach(i => { 
                h += `<tr class="border-b border-gray-50/50 last:border-0"><td class="p-4"><div class="font-bold text-gray-800">${i.nama_produk}</div><small class="text-gray-500">${i.nama_variasi||''}</small></td><td class="p-4 text-center text-gray-600 font-mono">x${i.jumlah}</td><td class="p-4 text-right font-medium text-gray-900">Rp ${fmt.format(i.total_harga)}</td></tr>`; 
            });
        }
        $('#det-items-body').html(h);

        const cont = $('#payment-proof-container');
        const acts = $('#verification-actions');
        $('#form-verify-payment').addClass('hidden');
        
        if (o.bukti_pembayaran) {
            cont.html(`<div class="relative group w-full h-64 cursor-pointer rounded-xl overflow-hidden shadow-sm border border-gray-200" onclick="openLightbox('${o.bukti_pembayaran}')"><img src="${o.bukti_pembayaran}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"><div class="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"><span class="bg-black/70 text-white px-3 py-1 rounded-full text-xs font-bold">Perbesar</span></div></div>`);
            if (o.global_status === 'menunggu_pembayaran' || o.global_status === 'menunggu_konfirmasi') acts.removeClass('hidden'); else acts.addClass('hidden');
        } else {
            cont.html(`<div class="flex flex-col items-center justify-center text-gray-400 gap-2"><i class="fas fa-image text-4xl opacity-20"></i><span class="text-xs">Belum ada bukti pembayaran</span></div>`);
            acts.addClass('hidden');
        }

        $('#modal-order-detail').removeClass('hidden');
        setTimeout(() => $('#modal-order-panel').removeClass('translate-x-full'), 10);
    }

    function closeOrderDetailModal() { $('#modal-order-panel').addClass('translate-x-full'); setTimeout(() => $('#modal-order-detail').addClass('hidden'), 300); }
    
    function setVerificationAction(act) {
        $('#verification-actions').addClass('hidden'); $('#form-verify-payment').removeClass('hidden'); $('#verify-decision').val(act);
        const btn = $('#btn-submit-verify'); const box = $('#reject-reason-box');
        if (act === 'accept') { 
            btn.text('Konfirmasi Terima Pembayaran').removeClass('bg-red-600').addClass('bg-green-600'); 
            box.addClass('hidden').find('textarea').prop('required', false); 
        } else { 
            btn.text('Tolak Pembayaran & Batalkan').removeClass('bg-green-600').addClass('bg-red-600'); 
            box.removeClass('hidden').find('textarea').prop('required', true); 
        }
    }
    function cancelVerification() { $('#form-verify-payment').addClass('hidden'); $('#verification-actions').removeClass('hidden'); }

    // --- PRODUCT & OTHER FUNCTIONS ---
    function openProductModal() { $('#form-product')[0].reset(); $('#prod_id').val(''); $('#modal-title').text('Tambah Produk'); $('#prod-prev-img').addClass('hidden'); $('#upload-placeholder').removeClass('hidden'); $('#variasi-container, #prev-galeri').empty(); $('#modal-produk').removeClass('hidden'); setTimeout(()=>$('#modal-produk-panel').removeClass('translate-x-full'),10); }
    function closeProductModal() { $('#modal-produk-panel').addClass('translate-x-full'); setTimeout(()=>$('#modal-produk').addClass('hidden'),300); }
    function addVariasiRow(n='',h='',s=''){ const id=Date.now(); $('#variasi-container').append(`<div class="flex gap-2 items-start animate-fade-in" id="row-${id}"><input type="text" name="var_nama[]" value="${n}" placeholder="Warna/Ukuran" class="w-1/2 border border-gray-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-primary outline-none"><input type="number" name="var_harga[]" value="${h}" placeholder="Rp" class="w-1/4 border border-gray-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-primary outline-none"><input type="number" name="var_stok[]" value="${s}" placeholder="Stok" class="w-1/4 border border-gray-200 rounded-lg p-2 text-xs focus:ring-1 focus:ring-primary outline-none"><button type="button" onclick="$('#row-${id}').remove()" class="text-red-400 hover:text-red-600 px-1"><i class="fas fa-times"></i></button></div>`); }
    function previewGallery(i){ if(i.files){ $('#prev-galeri').empty(); Array.from(i.files).forEach(f=>{ var r=new FileReader(); r.onload=function(e){ $('#prev-galeri').append(`<img src="${e.target.result}" class="w-full h-16 object-cover rounded-lg border border-gray-200">`); }; r.readAsDataURL(f); }); } }
    function editProduk(p){ openProductModal(); $('#modal-title').text('Edit Produk'); $('#prod_id').val(p.id); $('#prod_nama').val(p.nama_produk); $('#prod_harga').val(p.harga); $('#prod_stok').val(p.stok); $('#prod_berat').val(p.berat_gram); $('#prod_deskripsi').val(p.deskripsi); $('#prod_kategori').val(p.kategori); $('#prod_kondisi').val(p.kondisi); if(p.foto_utama){ $('#prod-prev-img').attr('src',p.foto_utama).removeClass('hidden'); $('#upload-placeholder').addClass('hidden'); } if(p.variasi) p.variasi.forEach(v=>addVariasiRow(v.deskripsi_variasi,v.harga_variasi,v.stok_variasi)); }
    function openBuyModal(id,n,p){ $('#modal-id-paket').val(id); $('#modal-paket-name').text(n); $('#modal-paket-price').text('Rp '+new Intl.NumberFormat('id-ID').format(p)); $('#modal-buy').removeClass('hidden'); }
    function closeBuyModal(){ $('#modal-buy').addClass('hidden'); }

    // --- MULTI-SELECT OJEK ---
    window.toggleOption = function(selId, val) { const $el = $(selId); const vals = $el.val() || []; const index = vals.indexOf(val); if (index > -1) { vals.splice(index, 1); } else { vals.push(val); } $el.val(vals).trigger('change'); };
    function setupEnhancedMultiSelect(selectId) {
        const $select = $(selectId); const containerId = selectId.replace('#', '') + '-wrapper'; $('#' + containerId).remove();
        const wrapper = `<div id="${containerId}" class="enhanced-select-wrapper border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm transition hover:border-gray-300"><div class="selected-area p-3 bg-gray-50 border-b border-gray-100 min-h-[50px] flex flex-wrap gap-2 text-sm text-gray-500"><span class="placeholder-text italic text-xs py-1">Belum ada yang dipilih...</span></div><div class="list-area"><div class="p-2 border-b border-gray-100 bg-white sticky top-0 z-10"><div class="relative"><i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i><input type="text" class="search-input w-full text-xs pl-8 p-2 border border-gray-200 rounded-lg focus:outline-none focus:border-primary transition bg-gray-50 focus:bg-white" placeholder="Cari nama wilayah..."></div></div><div class="options-list max-h-48 overflow-y-auto p-1 space-y-1 scrollbar-thin"></div></div></div>`;
        $select.after(wrapper); $select.hide();
        $(`#${containerId} .search-input`).on('keyup', function() { var val = $(this).val().toLowerCase(); $(`#${containerId} .options-list .option-item`).filter(function() { $(this).toggle($(this).find('.opt-text').text().toLowerCase().indexOf(val) > -1) }); });
        function render() {
            const $wrapper = $('#' + containerId); const $selectedArea = $wrapper.find('.selected-area'); const $listArea = $wrapper.find('.options-list'); $selectedArea.empty(); $listArea.empty(); let hasSelected = false; let optionsCount = 0;
            $select.find('option').each(function() {
                optionsCount++; const $opt = $(this); const val = $opt.val(); let text = $opt.text().replace(' (Dipilih di sebelah)', ''); const isSelected = $opt.is(':selected'); const isDisabled = $opt.is(':disabled');
                if (isSelected) { hasSelected = true; $selectedArea.append(`<div class="flex items-center gap-1 bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold animate-fade-in border border-green-200 shadow-sm"><span>${text}</span><button type="button" class="hover:text-green-900 ml-1 bg-green-200 w-4 h-4 rounded-full flex items-center justify-center transition" onclick="window.toggleOption('${selectId}', '${val}')"><i class="fas fa-times text-[10px]"></i></button></div>`); }
                let itemClass = "option-item p-2 rounded-lg text-xs cursor-pointer flex items-center justify-between transition group "; let icon = '<div class="w-4 h-4 border border-gray-300 rounded bg-white group-hover:border-primary"></div>'; let clickHandler = `onclick="window.toggleOption('${selectId}', '${val}')"`; let statusText = '';
                if (isDisabled) { itemClass += "bg-gray-50 text-gray-400 cursor-not-allowed opacity-60"; icon = '<i class="fas fa-ban text-gray-300"></i>'; clickHandler = ""; statusText = '<span class="text-[10px] italic ml-2">(Zona Lain)</span>'; } 
                else if (isSelected) { itemClass += "bg-green-50 text-green-700 font-bold border border-green-200"; icon = '<div class="w-4 h-4 bg-green-500 rounded flex items-center justify-center text-white text-[10px] shadow-sm"><i class="fas fa-check"></i></div>'; } 
                else { itemClass += "hover:bg-gray-50 text-gray-700 border border-transparent"; }
                $listArea.append(`<div class="${itemClass}" ${clickHandler}><span class="opt-text">${text} ${statusText}</span><span>${icon}</span></div>`);
            });
            if (!hasSelected) $selectedArea.html('<span class="placeholder-text italic text-xs py-1 text-gray-400 flex items-center gap-2"><i class="fas fa-info-circle"></i> Hasil pilihan akan muncul di sini...</span>');
            if (optionsCount === 0 || $select.is(':disabled')) $listArea.html('<div class="p-4 text-center text-xs text-gray-400 italic">Data belum dimuat. Silakan pilih lokasi toko terlebih dahulu.</div>');
        }
        render(); const observer = new MutationObserver(render); observer.observe($select[0], { childList: true, attributes: true }); $select.on('change render-ui', render);
    }
    function syncExclusion(sId, tId) { var sVals = $(sId).val() || []; var $target = $(tId); $target.find('option').each(function() { var val = $(this).val(); if (sVals.includes(val)) { $(this).prop('disabled', true); if (!$(this).text().includes('(Dipilih)')) $(this).text($(this).text() + ' (Dipilih di sebelah)'); } else { $(this).prop('disabled', false); $(this).text($(this).text().replace(' (Dipilih di sebelah)', '')); } }); $target.trigger('render-ui'); }

    // Init Regions
    $(document).ready(function(){
        var els={prov:$('#dw_provinsi'),kota:$('#dw_kota'),kec:$('#dw_kecamatan'),desa:$('#dw_desa')}, data=$('#region-data').data();
        function l(a,pid,el,sel,cb){ el.html('<option>Loading...</option>').prop('disabled',true); $.get(ajaxurl,{action:a,province_id:pid,regency_id:pid,district_id:pid},function(r){ if(r.success){ var o='<option value="">-- Pilih --</option>'; $.each(r.data.data||r.data,function(i,v){ var id=v.id||v.code; o+='<option value="'+id+'" '+(id==sel?'selected':'')+'>'+(v.name||v.nama)+'</option>'; }); el.html(o).prop('disabled',false); if(cb)cb(); }}); }
        function s(el,t){ var txt=$(el).find('option:selected').text(); if(txt!=='Loading...'&&txt!=='-- Pilih --')$(t).val(txt); }
        function fetchDesaForOngkir(id){ if(!id){ $('#sel-desa-dekat, #sel-desa-jauh').empty().prop('disabled',true).trigger('render-ui'); return; } $('#sel-desa-dekat, #sel-desa-jauh').prop('disabled',true).trigger('render-ui'); $.get(ajaxurl, {action:'dw_fetch_villages', district_id:id}, function(r){ if(r.success){ var els=[$('#sel-desa-dekat'),$('#sel-desa-jauh')]; els.forEach(el=>{ el.prop('disabled',false).empty(); $.each(r.data.data||r.data,function(i,v){ var val=v.id||v.code; var isSel=(el.data('selected')||[]).includes(val)?'selected':''; el.append('<option value="'+val+'" '+isSel+'>'+(v.name||v.nama)+'</option>'); }); el.trigger('render-ui'); }); syncExclusion('#sel-desa-dekat','#sel-desa-jauh'); syncExclusion('#sel-desa-jauh','#sel-desa-dekat'); } }); }
        function fetchKecForOngkir(id){ if(!id){ $('#sel-kec-dekat, #sel-kec-jauh').empty().prop('disabled',true).trigger('render-ui'); return; } $('#sel-kec-dekat, #sel-kec-jauh').prop('disabled',true).trigger('render-ui'); $.get(ajaxurl, {action:'dw_fetch_districts', regency_id:id}, function(r){ if(r.success){ var els=[$('#sel-kec-dekat'),$('#sel-kec-jauh')]; els.forEach(el=>{ el.prop('disabled',false).empty(); $.each(r.data.data||r.data,function(i,v){ var val=v.id||v.code; var isSel=(el.data('selected')||[]).includes(val)?'selected':''; el.append('<option value="'+val+'" '+isSel+'>'+(v.name||v.nama)+'</option>'); }); el.trigger('render-ui'); }); syncExclusion('#sel-kec-dekat','#sel-kec-jauh'); syncExclusion('#sel-kec-jauh','#sel-kec-dekat'); } }); }

        l('dw_fetch_provinces',null,els.prov,data.prov,function(){ 
            if(data.prov) l('dw_fetch_regencies',data.prov,els.kota,data.kota,function(){ 
                if(data.kota) { l('dw_fetch_districts',data.kota,els.kec,data.kec,function(){ 
                    if(data.kec) { l('dw_fetch_villages',data.kec,els.desa,data.desa); fetchDesaForOngkir(data.kec); } 
                }); fetchKecForOngkir(data.kota); }
            }); 
        });
        els.prov.change(function(){s(this,'#input_provinsi_name'); l('dw_fetch_regencies',$(this).val(),els.kota,null); els.kota.val(''); });
        els.kota.change(function(){s(this,'#input_kabupaten_name'); var id=$(this).val(); l('dw_fetch_districts',id,els.kec,null); fetchKecForOngkir(id); });
        els.kec.change(function(){s(this,'#input_kecamatan_name'); var id=$(this).val(); l('dw_fetch_villages',id,els.desa,null); fetchDesaForOngkir(id); });
        els.desa.change(function(){s(this,'#input_kelurahan_name');});
        
        $('#toggle-nasional').change(function() { $('#nasional-settings').toggleClass('hidden', !this.checked); });
        $('#toggle-ojek').change(function() { $('#ojek-settings').toggleClass('hidden', !this.checked); });
        
        setupEnhancedMultiSelect('#sel-desa-dekat'); setupEnhancedMultiSelect('#sel-desa-jauh'); 
        setupEnhancedMultiSelect('#sel-kec-dekat'); setupEnhancedMultiSelect('#sel-kec-jauh');
        
        $('#sel-desa-dekat, #sel-desa-jauh').on('change', function() { syncExclusion('#sel-desa-dekat', '#sel-desa-jauh'); syncExclusion('#sel-desa-jauh', '#sel-desa-dekat'); });
        $('#sel-kec-dekat, #sel-kec-jauh').on('change', function() { syncExclusion('#sel-kec-dekat', '#sel-kec-jauh'); syncExclusion('#sel-kec-jauh', '#sel-kec-dekat'); });
    });

    // QR & Clipboard
    const shopBaseUrl = "<?php echo home_url('/toko/' . $pedagang->slug_toko); ?>";
    function generateQRLinks() { const s=parseInt($('#qr-start').val())||1, e=parseInt($('#qr-end').val())||10, tb=$('#qr-table-body'); tb.empty(); if(e<s || (e-s)>100) return alert('Range invalid');
        for(let i=s; i<=e; i++) { const u=`${shopBaseUrl}?meja=${i}`, q=`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(u)}`; tb.append(`<tr class="hover:bg-gray-50 transition"><td class="p-4 font-bold text-gray-800">Meja ${i}</td><td class="p-4"><input type="text" value="${u}" readonly class="w-full text-xs border border-gray-200 rounded-lg p-2 text-gray-500 bg-gray-50"></td><td class="p-4 text-center"><div class="bg-white p-2 rounded border border-gray-200 inline-block"><img src="${q}" class="w-16 h-16"></div></td><td class="p-4 text-right"><a href="${q}" download="QR-Meja-${i}.png" target="_blank" class="bg-indigo-50 text-indigo-600 px-4 py-2 rounded-lg text-xs font-bold hover:bg-indigo-100 transition inline-flex items-center gap-1"><i class="fas fa-download"></i> Simpan</a></td></tr>`); }
        $('#qr-result-container').removeClass('hidden');
    }
    function printQRList() { const c=$('#qr-result-container').html(), w=window.open('','','height=700,width=900'); w.document.write('<html><head><title>QR Meja</title><link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet"></head><body class="p-8"><div class="mb-4 text-center"><h1 class="text-2xl font-bold">QR Code Meja</h1><p><?php echo esc_js($pedagang->nama_toko); ?></p></div>'+c+'</body></html>'); w.document.close(); setTimeout(()=>w.print(),500); }
    function copyToClipboard(t) { navigator.clipboard.writeText(t).then(()=>alert('Disalin!')).catch(()=>alert('Gagal salin')); }
</script>

<?php get_footer(); ?>