<?php
/**
 * Template Name: Dashboard Toko (Merchant)
 * Description: Dashboard lengkap untuk pedagang dengan UI Modern, Manajemen Produk, dan Pembelian Paket.
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
$table_transaksi_sub = $wpdb->prefix . 'dw_transaksi_sub';
$table_produk        = $wpdb->prefix . 'dw_produk';
$table_desa          = $wpdb->prefix . 'dw_desa';
$table_paket         = $wpdb->prefix . 'dw_paket_transaksi';
$table_pembelian     = $wpdb->prefix . 'dw_pembelian_paket';

// Ambil Data Pedagang
$pedagang = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_user = %d", $current_user_id));

// Redirect jika belum terdaftar
if (!$pedagang) {
    echo '<div class="flex items-center justify-center h-screen bg-gray-50 font-sans">
        <div class="text-center p-8 bg-white rounded-2xl shadow-xl max-w-md w-full border border-gray-100">
            <div class="w-16 h-16 bg-red-100 text-red-500 rounded-full flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="fas fa-store-slash"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-800 mb-2">Akun Belum Terdaftar</h3>
            <p class="text-gray-500 mb-6">Anda belum terdaftar sebagai pedagang di platform ini.</p>
            <a href="' . home_url() . '" class="inline-block bg-gray-900 text-white px-6 py-3 rounded-xl font-medium hover:bg-gray-800 transition">Kembali ke Beranda</a>
        </div>
    </div>';
    exit;
}

$msg = '';
$msg_class = '';

// --- HANDLE POST REQUESTS ---

// A. Handle Beli Paket (Topup Kuota)
if (isset($_POST['beli_paket']) && wp_verify_nonce($_POST['_wpnonce'], 'beli_paket_action')) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $id_paket = intval($_POST['id_paket']);
    
    // Ambil detail paket
    $paket = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_paket WHERE id = %d", $id_paket));

    if ($paket) {
        $upload_url = '';
        
        // Handle File Upload Bukti Bayar
        if (!empty($_FILES['bukti_bayar']['name'])) {
            $uploaded = media_handle_upload('bukti_bayar', 0); // Upload ke Media Library
            if (is_wp_error($uploaded)) {
                $msg = "Upload Gagal: " . $uploaded->get_error_message();
                $msg_class = 'bg-red-100 text-red-700 border-red-200';
            } else {
                $upload_url = wp_get_attachment_url($uploaded);
            }
        }

        if ($upload_url) {
            $wpdb->insert(
                $table_pembelian,
                [
                    'id_pedagang'            => $pedagang->id,
                    'id_paket'               => $paket->id,
                    'nama_paket_snapshot'    => $paket->nama_paket,
                    'harga_paket'            => $paket->harga,
                    'jumlah_transaksi'       => $paket->jumlah_transaksi,
                    'persentase_komisi_desa' => $paket->persentase_komisi_desa,
                    'url_bukti_bayar'        => $upload_url,
                    'status'                 => 'pending',
                    'created_at'             => current_time('mysql')
                ]
            );
            $msg = "Pembelian paket berhasil diajukan! Mohon tunggu verifikasi admin.";
            $msg_class = 'bg-green-100 text-green-700 border-green-200';
        } else if (empty($msg)) {
            $msg = "Mohon upload bukti pembayaran.";
            $msg_class = 'bg-yellow-100 text-yellow-700 border-yellow-200';
        }
    }
}

// B. Handle Simpan Pengaturan Toko
if ( isset($_POST['save_toko']) && wp_verify_nonce($_POST['toko_nonce'], 'save_toko_action') ) {
    
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );

    $api_kelurahan_id = sanitize_text_field($_POST['api_kelurahan_id']); 

    // --- LOGIKA OTOMATIS HUBUNGKAN KE DESA ---
    $desa_terkait = $wpdb->get_row( $wpdb->prepare( "SELECT id, nama_desa FROM $table_desa WHERE api_kelurahan_id = %s", $api_kelurahan_id ) );
    
    $id_desa_relasi = NULL;
    $is_independent = 1; 

    if ( $desa_terkait ) {
        $id_desa_relasi = $desa_terkait->id;
        $is_independent = 0; 
    }
    // -----------------------------------------

    $data_update = [
        'nama_toko'        => sanitize_text_field($_POST['nama_toko']),
        'nama_pemilik'     => sanitize_text_field($_POST['nama_pemilik']),
        'nomor_wa'         => sanitize_text_field($_POST['nomor_wa']),
        'nik'              => sanitize_text_field($_POST['nik']),
        'alamat_lengkap'   => sanitize_textarea_field($_POST['alamat_lengkap']),
        'url_gmaps'        => esc_url_raw($_POST['url_gmaps']),
        'id_desa'          => $id_desa_relasi,
        'is_independent'   => $is_independent,
        'nama_bank'        => sanitize_text_field($_POST['nama_bank']),
        'no_rekening'      => sanitize_text_field($_POST['no_rekening']),
        'atas_nama_rekening' => sanitize_text_field($_POST['atas_nama_rekening']),
        
        // Data Wilayah
        'api_provinsi_id'  => sanitize_text_field($_POST['api_provinsi_id']),
        'api_kabupaten_id' => sanitize_text_field($_POST['api_kabupaten_id']),
        'api_kecamatan_id' => sanitize_text_field($_POST['api_kecamatan_id']),
        'api_kelurahan_id' => $api_kelurahan_id,
        'provinsi_nama'    => sanitize_text_field($_POST['provinsi_nama']),
        'kabupaten_nama'   => sanitize_text_field($_POST['kabupaten_nama']),
        'kecamatan_nama'   => sanitize_text_field($_POST['kecamatan_nama']),
        'kelurahan_nama'   => sanitize_text_field($_POST['kelurahan_nama']),
        
        'allow_pesan_di_tempat'   => isset($_POST['allow_pesan_di_tempat']) ? 1 : 0,
        'shipping_nasional_aktif' => isset($_POST['shipping_nasional_aktif']) ? 1 : 0,
        'updated_at'       => current_time('mysql')
    ];

    // Handle Uploads
    $files = ['qris_image' => 'qris_image_url', 'foto_profil' => 'foto_profil', 'foto_sampul' => 'foto_sampul', 'foto_ktp' => 'url_ktp'];
    foreach($files as $input => $col) {
        if (!empty($_FILES[$input]['name'])) {
            $up = wp_handle_upload($_FILES[$input], ['test_form' => false]);
            if (isset($up['url'])) $data_update[$col] = $up['url'];
        }
    }

    $wpdb->update($table_pedagang, $data_update, ['id' => $pedagang->id]);
    
    // Refresh Data
    $pedagang = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_user = %d", $current_user_id));
    
    $extra_msg = $desa_terkait ? " Toko Anda kini terhubung dengan Desa " . $desa_terkait->nama_desa . "." : " Toko Anda berstatus Independen.";
    $msg = 'Pengaturan berhasil disimpan.' . $extra_msg;
    $msg_class = 'bg-green-100 text-green-700 border-green-200';
}

// --- QUERY DATA UNTUK DASHBOARD ---
// 1. Statistik
$count_produk = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_produk WHERE id_pedagang = %d", $pedagang->id));
$count_order  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_transaksi_sub WHERE id_pedagang = %d", $pedagang->id));
$pending_order = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_transaksi_sub WHERE id_pedagang = %d AND status_pesanan = 'menunggu_konfirmasi'", $pedagang->id));

// Pendapatan
$revenue = $wpdb->get_var($wpdb->prepare("SELECT SUM(total_pesanan_toko) FROM $table_transaksi_sub WHERE id_pedagang = %d AND status_pesanan IN ('selesai', 'dikirim_ekspedisi')", $pedagang->id));

// 2. Paket Tersedia
$pakets = $wpdb->get_results("SELECT * FROM $table_paket WHERE status = 'aktif' AND target_role = 'pedagang' ORDER BY harga ASC");

// 3. Riwayat Pembelian Paket
$history_paket = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pembelian WHERE id_pedagang = %d ORDER BY created_at DESC LIMIT 5", $pedagang->id));

// 4. Data Produk & Pesanan (Limit 5)
$produk_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_produk WHERE id_pedagang = %d ORDER BY created_at DESC LIMIT 5", $pedagang->id));
$order_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_transaksi_sub WHERE id_pedagang = %d ORDER BY created_at DESC LIMIT 5", $pedagang->id));

// 5. Kategori Produk
$default_kategori = ['Makanan & Minuman', 'Kerajinan Tangan', 'Fashion', 'Pertanian', 'Jasa'];
$existing_cats = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_produk WHERE kategori != ''");
$kategori_list = !empty($existing_cats) ? array_unique(array_merge($default_kategori, $existing_cats)) : $default_kategori;

get_header(); 
?>

<!-- TAILWIND & FONT AWESOME -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#16a34a', // Green-600
                    secondary: '#1e293b', // Slate-800
                }
            }
        }
    }
</script>

<div class="bg-gray-50 min-h-screen font-sans flex overflow-hidden text-slate-800">
    
    <!-- ================= SIDEBAR ================= -->
    <!-- Desktop Sidebar -->
    <aside class="hidden md:flex w-64 bg-white border-r border-gray-200 flex-col fixed h-full z-20 overflow-y-auto">
        <div class="p-6 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-primary text-white rounded-xl flex items-center justify-center text-xl shadow-lg shadow-primary/30">
                <i class="fas fa-store"></i>
            </div>
            <div>
                <h2 class="font-bold text-gray-800 leading-tight">Merchant</h2>
                <p class="text-[10px] text-gray-400 uppercase tracking-wider">Panel Toko</p>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1">
            <button onclick="switchTab('ringkasan')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-gray-50 hover:text-primary transition active-tab" id="nav-ringkasan">
                <i class="fas fa-chart-pie w-5 text-center"></i> Ringkasan
            </button>
            <button onclick="switchTab('produk')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-gray-50 hover:text-primary transition" id="nav-produk">
                <i class="fas fa-box w-5 text-center"></i> Produk Saya
            </button>
            <button onclick="switchTab('pesanan')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-gray-50 hover:text-primary transition" id="nav-pesanan">
                <i class="fas fa-shopping-bag w-5 text-center"></i> Pesanan Masuk
                <?php if($pending_order > 0): ?><span class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full ml-auto shadow-sm shadow-red-500/30"><?php echo $pending_order; ?></span><?php endif; ?>
            </button>
            <button onclick="switchTab('paket')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-gray-50 hover:text-primary transition" id="nav-paket">
                <i class="fas fa-ticket-alt w-5 text-center"></i> Kuota & Paket
            </button>
            <button onclick="switchTab('pengaturan')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-gray-50 hover:text-primary transition" id="nav-pengaturan">
                <i class="fas fa-cog w-5 text-center"></i> Pengaturan Toko
            </button>
        </nav>

        <div class="p-4 border-t border-gray-100">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 rounded-xl hover:bg-red-50 transition w-full">
                <i class="fas fa-sign-out-alt w-5 text-center"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- Mobile Sidebar (Off-canvas) -->
    <div id="mobile-sidebar" class="fixed inset-0 z-40 hidden">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="toggleMobileSidebar()"></div>
        <aside class="absolute left-0 top-0 bottom-0 w-64 bg-white shadow-2xl flex flex-col h-full transform transition-transform duration-300 -translate-x-full" id="mobile-sidebar-panel">
            <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-primary text-white rounded-lg flex items-center justify-center text-lg">
                        <i class="fas fa-store"></i>
                    </div>
                    <span class="font-bold text-gray-800">Merchant</span>
                </div>
                <button onclick="toggleMobileSidebar()" class="text-gray-500"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                <button onclick="switchTab('ringkasan'); toggleMobileSidebar()" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-gray-50 hover:text-primary transition active-tab" id="mob-nav-ringkasan">
                    <i class="fas fa-chart-pie w-5 text-center"></i> Ringkasan
                </button>
                <button onclick="switchTab('produk'); toggleMobileSidebar()" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-gray-50 hover:text-primary transition" id="mob-nav-produk">
                    <i class="fas fa-box w-5 text-center"></i> Produk Saya
                </button>
                <button onclick="switchTab('pesanan'); toggleMobileSidebar()" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-gray-50 hover:text-primary transition" id="mob-nav-pesanan">
                    <i class="fas fa-shopping-bag w-5 text-center"></i> Pesanan Masuk
                </button>
                <button onclick="switchTab('paket'); toggleMobileSidebar()" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-gray-50 hover:text-primary transition" id="mob-nav-paket">
                    <i class="fas fa-ticket-alt w-5 text-center"></i> Kuota & Paket
                </button>
                <button onclick="switchTab('pengaturan'); toggleMobileSidebar()" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-gray-50 hover:text-primary transition" id="mob-nav-pengaturan">
                    <i class="fas fa-cog w-5 text-center"></i> Pengaturan Toko
                </button>
            </nav>

            <div class="p-4 border-t border-gray-100">
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 rounded-xl hover:bg-red-50 transition w-full">
                    <i class="fas fa-sign-out-alt w-5 text-center"></i> Keluar
                </a>
            </div>
        </aside>
    </div>

    <!-- ================= MOBILE HEADER ================= -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-30 flex items-center justify-between px-4 shadow-sm">
        <div class="flex items-center gap-3">
            <button onclick="toggleMobileSidebar()" class="text-gray-600 p-1">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <span class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-store text-primary"></i> Merchant
            </span>
        </div>
        <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden border border-gray-300">
             <img src="<?php echo $pedagang->foto_profil ? esc_url($pedagang->foto_profil) : 'https://placehold.co/100'; ?>" class="w-full h-full object-cover">
        </div>
    </div>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="flex-1 md:ml-64 p-4 md:p-8 pt-20 md:pt-8 overflow-y-auto h-screen pb-24 bg-gray-50">
        
        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl border <?php echo $msg_class; ?> flex items-center gap-3 shadow-sm">
                <i class="fas fa-info-circle text-lg"></i> 
                <div><?php echo $msg; ?></div>
            </div>
        <?php endif; ?>

        <!-- Warning Kuota -->
        <?php if($pedagang->sisa_transaksi <= 5): ?>
            <div class="mb-6 p-4 rounded-xl border bg-orange-50 text-orange-800 border-orange-200 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm">
                <div>
                    <strong class="flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> Kuota Menipis!</strong> 
                    <span class="text-sm mt-1 block">Sisa kuota transaksi Anda tinggal <b><?php echo $pedagang->sisa_transaksi; ?></b>. Segera beli paket agar toko tetap aktif.</span>
                </div>
                <button onclick="switchTab('paket')" class="bg-orange-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-orange-700 shadow-md shadow-orange-500/20 font-medium whitespace-nowrap">Beli Paket</button>
            </div>
        <?php endif; ?>

        <!-- 1. TAB RINGKASAN (LENGKAP) -->
        <div id="view-ringkasan" class="tab-content animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Ringkasan Penjualan</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <!-- Pendapatan -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition group">
                    <div class="w-14 h-14 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center text-2xl shrink-0 group-hover:bg-green-100 transition">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium mb-1">Total Pendapatan</p>
                        <h3 class="text-2xl font-bold text-gray-800 truncate">
                            <?php echo 'Rp ' . number_format($revenue ?? 0, 0, ',', '.'); ?>
                        </h3>
                    </div>
                </div>

                <!-- Pesanan Baru -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition group">
                    <div class="w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center text-2xl relative shrink-0 group-hover:bg-blue-100 transition">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if($pending_order > 0): ?>
                            <span class="absolute top-0 right-0 -mt-1 -mr-1 w-4 h-4 bg-red-500 rounded-full border-2 border-white animate-pulse"></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium mb-1">Pesanan Baru</p>
                        <h3 class="text-2xl font-bold text-gray-800">
                            <?php echo number_format($pending_order); ?>
                        </h3>
                    </div>
                </div>

                <!-- Total Produk -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition group">
                    <div class="w-14 h-14 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center text-2xl shrink-0 group-hover:bg-purple-100 transition">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium mb-1">Total Produk Aktif</p>
                        <h3 class="text-2xl font-bold text-gray-800">
                            <?php echo number_format($count_produk); ?>
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Pesanan Terbaru -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center bg-gray-50/50 gap-4">
                    <h3 class="font-bold text-gray-800">Pesanan Terbaru</h3>
                    <button onclick="switchTab('pesanan')" class="text-sm text-primary hover:underline font-medium">Lihat Semua</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 font-semibold">ID</th>
                                <th class="px-6 py-3 font-semibold">Tanggal</th>
                                <th class="px-6 py-3 font-semibold">Status</th>
                                <th class="px-6 py-3 font-semibold text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php if($order_list): foreach($order_list as $o): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">#<?php echo $o->id; ?></td>
                                <td class="px-6 py-4"><?php echo date('d M Y', strtotime($o->created_at)); ?></td>
                                <td class="px-6 py-4"><span class="px-2.5 py-1 bg-yellow-100 text-yellow-700 rounded-lg text-xs font-bold"><?php echo ucfirst(str_replace('_',' ',$o->status_pesanan)); ?></span></td>
                                <td class="px-6 py-4 text-right font-medium">Rp <?php echo number_format($o->total_pesanan_toko, 0, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    <i class="fas fa-inbox text-2xl mb-2 block"></i> Belum ada pesanan.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. TAB PRODUK -->
        <div id="view-produk" class="tab-content hidden animate-fade-in">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h1 class="text-2xl font-bold text-gray-800">Produk Saya</h1>
                <button onclick="openProductModal()" class="bg-primary hover:bg-green-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 w-full md:w-auto justify-center">
                    <i class="fas fa-plus"></i> Tambah Produk
                </button>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php if($produk_list): foreach($produk_list as $p): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
                    <div class="relative h-48 bg-gray-100">
                        <img src="<?php echo $p->foto_utama ? esc_url($p->foto_utama) : 'https://via.placeholder.com/300?text=No+Image'; ?>" class="w-full h-full object-cover">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-1 rounded-lg text-xs font-bold text-gray-700 shadow-sm">
                            Stok: <?php echo $p->stok; ?>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="text-xs text-gray-400 mb-1"><?php echo esc_html($p->kategori); ?></div>
                        <h4 class="font-bold text-gray-800 mb-2 truncate group-hover:text-primary transition"><?php echo esc_html($p->nama_produk); ?></h4>
                        <div class="flex justify-between items-center">
                            <span class="text-primary font-bold text-lg">Rp <?php echo number_format($p->harga,0,',','.'); ?></span>
                            <button class="text-gray-400 hover:text-gray-600"><i class="fas fa-ellipsis-v"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="col-span-full py-12 text-center text-gray-400 border-2 border-dashed border-gray-200 rounded-2xl">
                    <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                    <p>Belum ada produk. Silakan tambah produk baru.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. TAB PESANAN -->
        <div id="view-pesanan" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pesanan Masuk</h1>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left whitespace-nowrap">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Kode Trx</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if($order_list): foreach($order_list as $o): ?>
                            <tr>
                                <td class="px-6 py-4">#<?php echo $o->id; ?></td>
                                <td class="px-6 py-4 font-mono text-gray-600">TRX-<?php echo $o->id; ?></td> <!-- Asumsi kode trx -->
                                <td class="px-6 py-4"><span class="px-2.5 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs font-bold"><?php echo ucfirst($o->status_pesanan); ?></span></td>
                                <td class="px-6 py-4 font-bold text-gray-800">Rp <?php echo number_format($o->total_pesanan_toko, 0, ',', '.'); ?></td>
                                <td class="px-6 py-4 text-center">
                                    <button class="text-primary hover:text-green-700 font-medium text-xs border border-primary px-3 py-1 rounded-lg hover:bg-green-50 transition" onclick="openOrderDetail(<?php echo $o->id; ?>)">Detail</button>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">Belum ada pesanan masuk.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. TAB KUOTA & PAKET (Updated UI) -->
        <div id="view-paket" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Kuota & Paket</h1>
            <p class="text-gray-500 mb-8">Kelola kuota transaksi toko Anda agar tetap bisa berjualan.</p>
            
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm mb-8 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 font-medium mb-1">Sisa Kuota Transaksi</p>
                    <h2 class="text-4xl font-bold text-primary"><?php echo number_format($pedagang->sisa_transaksi); ?></h2>
                </div>
                <div class="w-16 h-16 bg-primary/10 text-primary rounded-full flex items-center justify-center text-3xl">
                    <i class="fas fa-ticket-alt"></i>
                </div>
            </div>

            <h3 class="text-lg font-bold text-gray-800 mb-4">Pilih Paket Top Up</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <?php if($pakets): foreach($pakets as $pk): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 hover:border-primary hover:shadow-lg transition relative overflow-hidden group flex flex-col h-full">
                    <div class="absolute top-0 right-0 bg-primary text-white text-[10px] px-3 py-1 rounded-bl-xl font-bold uppercase tracking-wider opacity-0 group-hover:opacity-100 transition">Best Value</div>
                    
                    <div class="mb-4">
                        <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html($pk->nama_paket); ?></h3>
                        <div class="text-3xl font-extrabold text-gray-900 mt-2">Rp <?php echo number_format($pk->harga,0,',','.'); ?></div>
                        <p class="text-gray-400 text-xs mt-1">Sekali bayar</p>
                    </div>
                    
                    <ul class="text-sm text-gray-600 space-y-3 mb-8 flex-1">
                        <li class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs"><i class="fas fa-check"></i></div>
                            <span><strong><?php echo $pk->jumlah_transaksi; ?></strong> Transaksi</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs"><i class="fas fa-check"></i></div>
                            <span>Masa Aktif Selamanya</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <div class="w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs"><i class="fas fa-check"></i></div>
                            <span>Prioritas Support</span>
                        </li>
                    </ul>
                    
                    <button onclick="openBuyModal(<?php echo $pk->id; ?>, '<?php echo esc_js($pk->nama_paket); ?>', <?php echo $pk->harga; ?>)" class="w-full bg-gray-900 text-white py-3 rounded-xl font-bold hover:bg-gray-800 transition shadow-lg shadow-gray-900/20">
                        Beli Sekarang
                    </button>
                </div>
                <?php endforeach; else: ?>
                <div class="col-span-3 text-center py-12 bg-white rounded-2xl border border-dashed border-gray-300 text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>Belum ada paket tersedia saat ini.</p>
                </div>
                <?php endif; ?>
            </div>

            <h3 class="text-lg font-bold text-gray-800 mb-4">Riwayat Pembelian</h3>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-600 font-semibold border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4">Tanggal</th>
                            <th class="px-6 py-4">Paket</th>
                            <th class="px-6 py-4">Harga</th>
                            <th class="px-6 py-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if($history_paket): foreach($history_paket as $h): ?>
                        <tr>
                            <td class="px-6 py-4 text-gray-600"><?php echo date('d/m/Y H:i', strtotime($h->created_at)); ?></td>
                            <td class="px-6 py-4 font-medium text-gray-800"><?php echo esc_html($h->nama_paket_snapshot); ?></td>
                            <td class="px-6 py-4 font-bold text-gray-800">Rp <?php echo number_format($h->harga_paket,0,',','.'); ?></td>
                            <td class="px-6 py-4">
                                <?php 
                                $status_colors = ['pending'=>'yellow', 'disetujui'=>'green', 'ditolak'=>'red'];
                                $c = $status_colors[$h->status] ?? 'gray';
                                echo "<span class='px-2.5 py-1 bg-{$c}-50 text-{$c}-700 border border-{$c}-200 rounded-lg text-xs font-bold capitalize'>".ucfirst($h->status)."</span>";
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Belum ada riwayat pembelian.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. TAB PENGATURAN -->
        <div id="view-pengaturan" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pengaturan Toko</h1>
            
            <form method="POST" enctype="multipart/form-data" class="max-w-5xl">
                <?php wp_nonce_field('save_toko_action', 'toko_nonce'); ?>
                <input type="hidden" name="save_toko" value="1">
                
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Kolom Foto -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 text-center">
                            <label class="block text-sm font-bold text-gray-700 mb-4 text-left">Foto Profil Toko</label>
                            <div class="relative inline-block group">
                                <img src="<?php echo $pedagang->foto_profil ? esc_url($pedagang->foto_profil) : 'https://placehold.co/150x150?text=Toko'; ?>" class="w-32 h-32 rounded-full object-cover border-4 border-gray-50 shadow-md mx-auto mb-3" id="preview_foto_profil">
                                <label for="input_foto_profil" class="absolute bottom-2 right-2 bg-white text-gray-700 p-2 rounded-full shadow-md cursor-pointer hover:bg-gray-50 border border-gray-200 transition">
                                    <i class="fas fa-camera"></i>
                                    <input type="file" name="foto_profil" id="input_foto_profil" class="hidden" accept="image/*" onchange="previewImage(this, 'preview_foto_profil')">
                                </label>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">Klik ikon kamera untuk mengganti.</p>
                        </div>

                        <!-- Info Desa -->
                        <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
                            <h4 class="font-bold text-blue-800 mb-2">Status Relasi</h4>
                            <?php if ($pedagang->id_desa && $pedagang->is_independent == 0): 
                                $nama_desa_info = $wpdb->get_var($wpdb->prepare("SELECT nama_desa FROM $table_desa WHERE id = %d", $pedagang->id_desa));
                            ?>
                                <p class="text-sm text-blue-600 mb-2">Toko Anda terhubung dengan:</p>
                                <div class="bg-white p-3 rounded-xl border border-blue-200 font-bold text-blue-800 flex items-center gap-2">
                                    <i class="fas fa-map-marked-alt"></i> <?php echo esc_html($nama_desa_info); ?>
                                </div>
                            <?php else: ?>
                                <div class="flex items-center gap-2 text-gray-600 mb-2">
                                    <span class="bg-gray-200 text-gray-700 text-xs font-bold px-2 py-1 rounded">INDEPENDENT</span>
                                </div>
                                <p class="text-xs text-gray-500">Toko Anda tidak terikat dengan Desa Wisata manapun saat ini.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kolom Form -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2 border-b border-gray-100 pb-4">
                                <i class="fas fa-store text-primary"></i> Identitas Toko
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Nama Toko</label>
                                    <input type="text" name="nama_toko" value="<?php echo esc_attr($pedagang->nama_toko); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary transition" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Nama Pemilik</label>
                                    <input type="text" name="nama_pemilik" value="<?php echo esc_attr($pedagang->nama_pemilik); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary transition" required>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nomor WhatsApp</label>
                                <input type="text" name="nomor_wa" value="<?php echo esc_attr($pedagang->nomor_wa); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary transition" required>
                            </div>
                        </div>

                        <!-- ALAMAT (OTOMATIS RELASI) -->
                        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2 border-b border-gray-100 pb-4">
                                <i class="fas fa-map-marker-alt text-primary"></i> Alamat & Lokasi
                            </h3>
                            
                            <!-- Hidden Inputs -->
                            <input type="hidden" name="provinsi_nama" id="input_provinsi_nama" value="<?php echo esc_attr($pedagang->provinsi_nama); ?>">
                            <input type="hidden" name="kabupaten_nama" id="input_kabupaten_name" value="<?php echo esc_attr($pedagang->kabupaten_nama); ?>">
                            <input type="hidden" name="kecamatan_nama" id="input_kecamatan_name" value="<?php echo esc_attr($pedagang->kecamatan_nama); ?>">
                            <input type="hidden" name="kelurahan_nama" id="input_kelurahan_name" value="<?php echo esc_attr($pedagang->kelurahan_nama); ?>">

                            <!-- Data Init -->
                            <div id="region-data" data-prov="<?php echo esc_attr($pedagang->api_provinsi_id); ?>" data-kota="<?php echo esc_attr($pedagang->api_kabupaten_id); ?>" data-kec="<?php echo esc_attr($pedagang->api_kecamatan_id); ?>" data-desa="<?php echo esc_attr($pedagang->api_kelurahan_id); ?>"></div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Provinsi</label>
                                    <select name="api_provinsi_id" id="dw_provinsi" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white outline-none focus:ring-primary focus:border-primary transition" required><option>Loading...</option></select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Kota/Kabupaten</label>
                                    <select name="api_kabupaten_id" id="dw_kota" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white outline-none focus:ring-primary focus:border-primary transition" disabled required><option>Pilih Provinsi</option></select>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Kecamatan</label>
                                    <select name="api_kecamatan_id" id="dw_kecamatan" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white outline-none focus:ring-primary focus:border-primary transition" disabled required><option>Pilih Kota</option></select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Kelurahan/Desa</label>
                                    <select name="api_kelurahan_id" id="dw_desa" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white outline-none focus:ring-primary focus:border-primary transition" disabled required><option>Pilih Kecamatan</option></select>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Alamat Lengkap</label>
                                <textarea name="alamat_lengkap" rows="2" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary transition"><?php echo esc_textarea($pedagang->alamat_lengkap); ?></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Google Maps URL</label>
                                <input type="url" name="url_gmaps" value="<?php echo esc_url($pedagang->url_gmaps); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary transition">
                            </div>
                        </div>

                        <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                            <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2 border-b border-gray-100 pb-4">
                                <i class="fas fa-wallet text-primary"></i> Rekening Bank
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Nama Bank</label>
                                    <input type="text" name="nama_bank" value="<?php echo esc_attr($pedagang->nama_bank); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary transition" placeholder="BCA/BRI">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Nomor Rekening</label>
                                    <input type="text" name="no_rekening" value="<?php echo esc_attr($pedagang->no_rekening); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary transition">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Atas Nama</label>
                                <input type="text" name="atas_nama_rekening" value="<?php echo esc_attr($pedagang->atas_nama_rekening); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary transition">
                            </div>
                        </div>

                        <div class="text-right">
                            <button type="submit" name="save_toko" class="bg-gray-900 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:bg-gray-800 transition flex items-center gap-2 ml-auto">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

    </main>
</div>

<!-- Modal Beli Paket -->
<div id="modal-buy" class="fixed inset-0 z-[60] hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeBuyModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative animate-fade-in transform scale-100 transition-all">
            <button onclick="closeBuyModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
            <h3 class="text-xl font-bold text-gray-800 mb-1">Konfirmasi Pembelian</h3>
            <p class="text-gray-500 text-sm mb-6">Silakan transfer sesuai nominal di bawah ini.</p>
            
            <div class="bg-gray-50 p-4 rounded-xl mb-6 border border-gray-100">
                <div class="flex justify-between mb-2">
                    <span class="text-gray-600">Paket:</span>
                    <strong class="text-gray-900" id="modal-paket-name">-</strong>
                </div>
                <div class="flex justify-between mb-4">
                    <span class="text-gray-600">Total Bayar:</span>
                    <strong class="text-primary text-lg" id="modal-paket-price">-</strong>
                </div>
                <div class="border-t border-gray-200 pt-3 text-center">
                    <p class="text-xs text-gray-500 mb-1">Transfer ke Bank BCA</p>
                    <p class="font-mono text-lg font-bold text-gray-800">123-456-7890</p>
                    <p class="text-xs text-gray-500">a.n PT Desa Wisata Core</p>
                </div>
            </div>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('beli_paket_action'); ?>
                <input type="hidden" name="beli_paket" value="1">
                <input type="hidden" name="id_paket" id="modal-id-paket">
                
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-500 mb-2">Upload Bukti Transfer</label>
                    <div class="border border-dashed border-gray-300 rounded-lg p-2 hover:bg-gray-50 transition">
                        <input type="file" name="bukti_bayar" accept="image/*" required class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-700 cursor-pointer">
                    </div>
                </div>

                <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-xl hover:bg-green-700 transition shadow-lg shadow-green-500/20">Kirim Bukti Pembayaran</button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Produk -->
<div id="modal-produk" class="fixed inset-0 z-[60] hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeProductModal()"></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-md bg-white shadow-2xl overflow-y-auto transform transition-transform translate-x-full duration-300" id="modal-produk-panel">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4 sticky top-0 bg-white z-10">
                <h2 class="text-xl font-bold text-gray-800" id="modal-title">Tambah Produk</h2>
                <button onclick="closeProductModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition"><i class="fas fa-times"></i></button>
            </div>
            
            <form id="form-product">
                <input type="hidden" name="id" id="prod_id">
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Nama Produk</label>
                        <input type="text" name="nama_produk" id="prod_nama" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary outline-none">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Harga (Rp)</label>
                            <input type="number" name="harga" id="prod_harga" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Stok</label>
                            <input type="number" name="stok" id="prod_stok" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Kategori</label>
                        <select name="kategori" id="prod_kategori" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:ring-primary focus:border-primary outline-none">
                            <?php foreach($kategori_list as $kat) { echo "<option value='$kat'>$kat</option>"; } ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Deskripsi</label>
                        <textarea name="deskripsi" id="prod_deskripsi" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary outline-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Foto Produk</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 transition cursor-pointer relative group">
                            <input type="file" name="foto_utama" id="prod_foto" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewImage(this, 'preview_prod_img', 'icon_prod_upload')">
                            <i id="icon_prod_upload" class="fas fa-cloud-upload-alt text-3xl text-gray-300 mb-2 group-hover:text-primary transition"></i>
                            <img id="preview_prod_img" class="hidden max-h-32 mx-auto rounded-lg shadow-sm">
                            <p class="text-xs text-gray-500 mt-2">Klik untuk upload gambar</p>
                        </div>
                    </div>
                </div>
                <div class="mt-8 border-t border-gray-100 pt-6">
                    <button type="submit" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3.5 rounded-xl transition flex justify-center gap-2 shadow-lg shadow-green-500/20">
                        <span id="btn-save-text">Simpan Produk</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Order -->
<div id="modal-order" class="fixed inset-0 z-[60] hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeOrderModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 relative animate-fade-in transform scale-100 transition-all">
            <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                <h2 class="text-xl font-bold text-gray-800">Detail Pesanan #<span id="order-detail-id"></span></h2>
                <button onclick="closeOrderModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times text-xl"></i></button>
            </div>
            
            <div id="order-detail-content" class="space-y-4">
                <div class="flex justify-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-gray-300"></i></div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-100 flex justify-end gap-3">
                <button class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition text-sm font-medium" onclick="closeOrderModal()">Tutup</button>
                <button class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-green-700 transition text-sm font-bold shadow-lg shadow-green-500/20">Proses Pesanan</button>
            </div>
        </div>
    </div>
</div>


<style>
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.active-tab { background-color: #f0fdf4; color: #16a34a; border-right: 3px solid #16a34a; }
</style>

<script>
// Define Ajax URL for Frontend
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

// 1. Sidebar Mobile Toggle
function toggleMobileSidebar() {
    const sidebar = document.getElementById('mobile-sidebar');
    const panel = document.getElementById('mobile-sidebar-panel');
    
    if (sidebar.classList.contains('hidden')) {
        sidebar.classList.remove('hidden');
        setTimeout(() => { panel.classList.remove('-translate-x-full'); }, 10);
    } else {
        panel.classList.add('-translate-x-full');
        setTimeout(() => { sidebar.classList.add('hidden'); }, 300);
    }
}

// 2. Tab Switcher
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active-tab'));
    
    document.getElementById('view-' + tabName).classList.remove('hidden');
    
    const desktopNav = document.getElementById('nav-' + tabName);
    const mobileNav = document.getElementById('mob-nav-' + tabName);
    
    if(desktopNav) desktopNav.classList.add('active-tab');
    if(mobileNav) mobileNav.classList.add('active-tab');
}

// 3. Helper: Preview Image Input
function previewImage(input, previewId, iconId = null) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var preview = document.getElementById(previewId);
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            if(iconId) document.getElementById(iconId).classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// 4. Modal Functions
function openBuyModal(id, name, price) {
    document.getElementById('modal-id-paket').value = id;
    document.getElementById('modal-paket-name').innerText = name;
    document.getElementById('modal-paket-price').innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(price);
    document.getElementById('modal-buy').classList.remove('hidden');
}
function closeBuyModal() { document.getElementById('modal-buy').classList.add('hidden'); }

const modalP = document.getElementById('modal-produk');
const panelP = document.getElementById('modal-produk-panel');

function openProductModal(data = null) {
    modalP.classList.remove('hidden');
    setTimeout(() => panelP.classList.remove('translate-x-full'), 10);
    document.getElementById('form-product').reset();
    document.getElementById('prod_id').value = '';
    document.getElementById('modal-title').innerText = 'Tambah Produk';
    document.getElementById('preview_prod_img').classList.add('hidden');
    document.getElementById('icon_prod_upload').classList.remove('hidden');
}

function closeProductModal() {
    panelP.classList.add('translate-x-full');
    setTimeout(() => modalP.classList.add('hidden'), 300);
}

// Order Modal Functions
function openOrderDetail(orderId) {
    document.getElementById('modal-order').classList.remove('hidden');
    document.getElementById('order-detail-id').innerText = orderId;
    
    // Disini Anda bisa melakukan AJAX request untuk mengambil detail pesanan
    // Untuk saat ini, kita tampilkan placeholder
    const content = document.getElementById('order-detail-content');
    content.innerHTML = `
        <div class="space-y-3">
            <div class="flex justify-between border-b border-gray-100 pb-2">
                <span class="text-gray-500">Tanggal</span>
                <span class="font-medium">24 Des 2025</span>
            </div>
            <div class="flex justify-between border-b border-gray-100 pb-2">
                <span class="text-gray-500">Status</span>
                <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-xs font-bold">Menunggu Konfirmasi</span>
            </div>
            <div class="py-2">
                <h4 class="font-bold text-gray-700 mb-2 text-sm">Item Pesanan</h4>
                <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-gray-200 rounded-md"></div>
                        <div>
                            <p class="font-medium text-sm text-gray-800">Contoh Produk A</p>
                            <p class="text-xs text-gray-500">2 x Rp 50.000</p>
                        </div>
                    </div>
                    <span class="font-bold text-gray-800">Rp 100.000</span>
                </div>
            </div>
            <div class="flex justify-between pt-2">
                <span class="font-bold text-lg">Total</span>
                <span class="font-bold text-lg text-primary">Rp 100.000</span>
            </div>
        </div>
    `;
}

function closeOrderModal() {
    document.getElementById('modal-order').classList.add('hidden');
}

// 5. Init Tab
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'ringkasan';
    switchTab(tab);
});

// 6. Address API Logic (jQuery)
jQuery(document).ready(function($) {
    var els = {
        prov: $('#dw_provinsi'),
        kota: $('#dw_kota'),
        kec: $('#dw_kecamatan'),
        desa: $('#dw_desa')
    };
    var data = $('#region-data').data();

    function loadRegion(action, parentId, el, selected) {
        el.html('<option>Loading...</option>').prop('disabled', true);
        
        var reqData = { action: action };
        if(action == 'dw_fetch_regencies') reqData.province_id = parentId;
        if(action == 'dw_fetch_districts') reqData.regency_id = parentId;
        if(action == 'dw_fetch_villages') reqData.district_id = parentId;
        if(action == 'dw_fetch_provinces') reqData = { action: 'dw_fetch_provinces' };

        $.get(ajaxurl, reqData, function(res) {
            if(res.success) {
                var opts = '<option value="">-- Pilih --</option>';
                var items = res.data.data || res.data; 
                $.each(items, function(i, v) {
                    opts += '<option value="'+(v.id||v.code)+'" '+( (v.id||v.code)==selected ? 'selected':'')+'>'+(v.name||v.nama)+'</option>';
                });
                el.html(opts).prop('disabled', false);
            }
        });
    }

    function setText(el, target) { $(target).val($(el).find('option:selected').text()); }

    loadRegion('dw_fetch_provinces', null, els.prov, data.prov);

    els.prov.change(function() {
        setText(this, '#input_provinsi_name');
        var id = $(this).val();
        els.kota.empty().prop('disabled', true); els.kec.empty().prop('disabled', true); els.desa.empty().prop('disabled', true);
        if(id) loadRegion('dw_fetch_regencies', id, els.kota, (id==data.prov?data.kota:''));
    });

    els.kota.change(function() {
        setText(this, '#input_kabupaten_name');
        var id = $(this).val();
        els.kec.empty().prop('disabled', true); els.desa.empty().prop('disabled', true);
        if(id) loadRegion('dw_fetch_districts', id, els.kec, (id==data.kota?data.kec:''));
    });

    els.kec.change(function() {
        setText(this, '#input_kecamatan_name');
        var id = $(this).val();
        els.desa.empty().prop('disabled', true);
        if(id) loadRegion('dw_fetch_villages', id, els.desa, (id==data.kec?data.desa:''));
    });

    els.desa.change(function() { setText(this, '#input_kelurahan_name'); });

    if(data.prov) els.prov.trigger('change');
});
</script>

<?php get_footer(); ?>