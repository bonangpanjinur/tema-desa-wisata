<?php
/**
 * Template Name: Dashboard Desa
 * Description: Panel Frontend Desa untuk Verifikasi UMKM, Kelola Wisata, Data UMKM, & Profil Desa.
 * Integrasi: Profil Desa, Upload Bukti Bayar, Cek Premium.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Cek Login
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url() );
    exit;
}

$current_user_id = get_current_user_id();
$current_user    = wp_get_current_user();
global $wpdb;

// --- CONFIG: TABLE NAMES (SESUAI SCHEMA ACTIVATION.PHP) ---
$table_desa     = $wpdb->prefix . 'dw_desa'; 
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_wisata   = $wpdb->prefix . 'dw_wisata';

// 2. Ambil Data Desa
$desa_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_desa WHERE id_user_desa = %d", $current_user_id) );

// --- AUTO-CREATE: Jika data desa belum ada, buat otomatis agar dashboard bisa diakses ---
if ( ! $desa_data ) {
    $nama_default = 'Desa ' . $current_user->display_name;
    $slug_default = sanitize_title($nama_default) . '-' . $current_user_id;
    
    $wpdb->insert($table_desa, [
        'id_user_desa'            => $current_user_id,
        'nama_desa'               => $nama_default,
        'slug_desa'               => $slug_default,
        'status'                  => 'pending',
        'status_akses_verifikasi' => 'locked', // Default: Free
        'created_at'              => current_time('mysql'),
        'updated_at'              => current_time('mysql')
    ]);
    
    // Ambil ulang data setelah insert
    $desa_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_desa WHERE id_user_desa = %d", $current_user_id) );
}

$id_desa = $desa_data->id;

// LOGIC PREMIUM: Cek Status di Database
$akses_premium     = ($desa_data->status_akses_verifikasi === 'active');
$status_verifikasi = $desa_data->status_akses_verifikasi; 

// --- HANDLER 1: SIMPAN PROFIL ---
$msg_profil = '';
$msg_type_profil = '';
if ( isset($_POST['save_profil_desa']) && wp_verify_nonce($_POST['profil_desa_nonce'], 'save_profil_desa_action') ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    $update_desa = [
        'nama_desa' => sanitize_text_field($_POST['nama_desa']),
        'deskripsi' => wp_kses_post($_POST['deskripsi']),
        'alamat_lengkap' => sanitize_textarea_field($_POST['alamat_lengkap']),
        'nama_bank_desa' => sanitize_text_field($_POST['nama_bank_desa']),
        'no_rekening_desa' => sanitize_text_field($_POST['no_rekening_desa']),
        'atas_nama_rekening_desa' => sanitize_text_field($_POST['atas_nama_rekening_desa']),
        'updated_at' => current_time('mysql')
    ];
    if ( ! empty($_FILES['foto_desa']['name']) ) {
        $uploaded = wp_handle_upload( $_FILES['foto_desa'], array( 'test_form' => false ) );
        if ( isset( $uploaded['url'] ) && ! isset( $uploaded['error'] ) ) $update_desa['foto'] = $uploaded['url'];
    }
    if ( ! empty($_FILES['qris_desa']['name']) ) {
        $uploaded_qris = wp_handle_upload( $_FILES['qris_desa'], array( 'test_form' => false ) );
        if ( isset( $uploaded_qris['url'] ) && ! isset( $uploaded_qris['error'] ) ) $update_desa['qris_image_url_desa'] = $uploaded_qris['url'];
    }
    $wpdb->update($table_desa, $update_desa, ['id' => $id_desa]);
    $msg_profil = "Profil desa berhasil diperbarui."; $msg_type_profil = "success";
    $desa_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_desa WHERE id = %d", $id_desa) );
}

// --- HANDLER 2: UPLOAD BUKTI BAYAR PREMIUM ---
$msg_payment = '';
$msg_type_payment = '';
if ( isset($_POST['action_upload_bukti']) && wp_verify_nonce($_POST['upload_bukti_nonce'], 'upload_bukti_action') ) {
    if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
    $uploadedfile = $_FILES['bukti_bayar'];
    if ( ! empty( $uploadedfile['name'] ) ) {
        $movefile = wp_handle_upload( $uploadedfile, array( 'test_form' => false ) );
        if ( $movefile && ! isset( $movefile['error'] ) ) {
            $wpdb->update($table_desa, [
                'bukti_bayar_akses'       => $movefile['url'],
                'status_akses_verifikasi' => 'pending',
                'alasan_penolakan'        => null,
                'updated_at'              => current_time('mysql')
            ], ['id' => $id_desa]);
            $msg_payment = "Bukti pembayaran terkirim! Mohon tunggu verifikasi admin.";
            $msg_type_payment = "success";
            $status_verifikasi = 'pending';
            $desa_data->bukti_bayar_akses = $movefile['url'];
        } else {
            $msg_payment = "Gagal upload: " . $movefile['error']; $msg_type_payment = "error";
        }
    }
}

// --- HANDLER 3: VERIFIKASI UMKM (Hanya untuk Premium) ---
$msg_verif = '';
if ( isset($_POST['action_verifikasi']) && wp_verify_nonce($_POST['verifikasi_nonce'], 'verifikasi_pedagang_action') ) {
    if ( ! $akses_premium ) {
        $msg_verif = "Gagal: Fitur ini memerlukan akses Premium.";
    } else {
        $id_pedagang = intval($_POST['id_pedagang']);
        $status_baru = sanitize_text_field($_POST['status_keputusan']);
        $update_data = ['status_pendaftaran' => $status_baru, 'approved_by' => 'desa', 'updated_at' => current_time('mysql')];
        
        if ($status_baru == 'disetujui') {
            $update_data['status_akun'] = 'aktif';
            $options = get_option('dw_settings');
            $update_data['sisa_transaksi'] = isset($options['kuota_gratis_default']) ? absint($options['kuota_gratis_default']) : 0;
            $pedagang_user = $wpdb->get_var($wpdb->prepare("SELECT id_user FROM $table_pedagang WHERE id = %d", $id_pedagang));
            if($pedagang_user) { $u = new WP_User($pedagang_user); $u->add_role('pedagang'); }
        } else {
            $update_data['status_akun'] = 'nonaktif';
        }
        $wpdb->update($table_pedagang, $update_data, ['id' => $id_pedagang]);
        $msg_verif = "Status pedagang diperbarui: " . ucfirst($status_baru);
    }
}

// --- HANDLER 4: KELOLA WISATA (UPDATE SESUAI SCHEMA LENGKAP) ---
$msg_wisata = '';
if ( isset($_POST['save_wisata']) && wp_verify_nonce($_POST['wisata_nonce'], 'save_wisata_action') ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    // Batasan Free Plan
    if ( ! $akses_premium && empty($_POST['wisata_id']) ) {
        $count_wisata = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_wisata WHERE id_desa = %d", $id_desa));
        if ($count_wisata >= 2) { 
            echo "<script>alert('Gagal: Akun Free maksimal 2 wisata. Upgrade Premium untuk akses tanpa batas.'); window.history.back();</script>"; 
            exit; 
        }
    }
    $wisata_id = !empty($_POST['wisata_id']) ? intval($_POST['wisata_id']) : 0;
    
    // Mapping Data sesuai tabel dw_wisata
    $data_wisata = [
        'id_desa'          => $id_desa, 
        'nama_wisata'      => sanitize_text_field($_POST['nama_wisata']), 
        'kategori'         => sanitize_text_field($_POST['kategori']),
        'deskripsi'        => wp_kses_post($_POST['deskripsi']), 
        'harga_tiket'      => floatval($_POST['harga_tiket']), 
        'jam_buka'         => sanitize_text_field($_POST['jam_buka']),
        'fasilitas'        => sanitize_textarea_field($_POST['fasilitas']),        // New field from Schema
        'kontak_pengelola' => sanitize_text_field($_POST['kontak_pengelola']),     // New field from Schema
        'lokasi_maps'      => esc_url_raw($_POST['lokasi_maps']), 
        'updated_at'       => current_time('mysql')
    ];

    if ( ! empty($_FILES['foto_utama']['name']) ) {
        $uploaded = wp_handle_upload( $_FILES['foto_utama'], array( 'test_form' => false ) );
        if ( isset( $uploaded['url'] ) ) $data_wisata['foto_utama'] = $uploaded['url'];
    }

    if ($wisata_id > 0) { 
        $wpdb->update($table_wisata, $data_wisata, ['id' => $wisata_id, 'id_desa' => $id_desa]); 
        $msg_wisata = "Wisata diperbarui."; 
    } else { 
        $data_wisata['slug'] = sanitize_title($_POST['nama_wisata']).'-'.rand(100,999); 
        $data_wisata['created_at'] = current_time('mysql'); 
        $data_wisata['status'] = 'aktif'; 
        $wpdb->insert($table_wisata, $data_wisata); 
        $msg_wisata = "Wisata ditambahkan."; 
    }
}

if ( isset($_GET['action']) && $_GET['action'] == 'hapus_wisata' && isset($_GET['id']) ) {
    $wpdb->delete($table_wisata, ['id' => intval($_GET['id']), 'id_desa' => $id_desa]); 
    wp_redirect( remove_query_arg(['action', 'id']) ); exit;
}

// DATA PENDUKUNG
$default_cats = ['Wisata Alam', 'Wisata Bahari', 'Wisata Budaya', 'Wisata Sejarah', 'Wisata Edukasi', 'Wisata Kuliner', 'Spot Foto', 'Camping Ground'];
$existing_cats = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_wisata WHERE kategori != ''");
$kategori_wisata = array_unique(array_merge($default_cats, $existing_cats ?: [])); sort($kategori_wisata);

// Info Rekening Admin
$sys_bank_name    = get_option('dw_bank_name', '-');
$sys_bank_account = get_option('dw_bank_account', '-');
$sys_bank_holder  = get_option('dw_bank_holder', '-');
$sys_qris_url     = get_option('dw_qris_image_url', '');
$settings         = get_option('dw_settings', []);
$harga_premium    = isset($settings['harga_premium_desa']) ? $settings['harga_premium_desa'] : 0;

get_header(); 
?>

<!-- CDN Tailwind & FontAwesome -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="bg-gray-50 min-h-screen font-sans flex overflow-hidden">
    
    <!-- SIDEBAR -->
    <aside class="hidden md:flex w-64 bg-white border-r border-gray-200 flex-col fixed h-full z-20">
        <div class="p-6 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center text-xl shadow-lg shadow-blue-500/30">
                <i class="fas fa-landmark"></i>
            </div>
            <div>
                <h2 class="font-bold text-gray-800 leading-tight">Dashboard Desa</h2>
                <p class="text-[11px] text-gray-400 font-medium truncate max-w-[120px]"><?php echo esc_html($desa_data->nama_desa); ?></p>
                <?php if($akses_premium): ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 mt-1"><i class="fas fa-crown text-[8px]"></i> Premium</span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 mt-1">Free Plan</span>
                <?php endif; ?>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1">
            <button onclick="switchTab('ringkasan')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition active-tab" id="nav-ringkasan">
                <i class="fas fa-chart-pie w-5 text-center"></i> Ringkasan
            </button>
            <button onclick="switchTab('verifikasi')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition relative" id="nav-verifikasi">
                <i class="fas fa-users-cog w-5 text-center"></i> Verifikasi UMKM
                <?php if(!$akses_premium): ?>
                    <i class="fas fa-lock text-xs text-gray-400 absolute right-4"></i>
                <?php else: 
                    $count_pending = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'menunggu_desa'", $id_desa));
                    if($count_pending > 0): ?>
                    <span class="absolute right-3 top-3 bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full"><?php echo $count_pending; ?></span>
                <?php endif; endif; ?>
            </button>
            <button onclick="switchTab('data-umkm')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-data-umkm">
                <i class="fas fa-store w-5 text-center"></i> Data UMKM
            </button>
            <button onclick="switchTab('wisata')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-wisata">
                <i class="fas fa-map-location-dot w-5 text-center"></i> Kelola Wisata
            </button>
            <button onclick="switchTab('profil')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-profil">
                <i class="fas fa-id-card-clip w-5 text-center"></i> Profil Desa
            </button>
        </nav>

        <div class="p-4 border-t border-gray-100">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 rounded-xl hover:bg-red-50 transition w-full">
                <i class="fas fa-sign-out-alt w-5 text-center"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- MOBILE HEADER -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-30 flex items-center justify-between px-4 shadow-sm">
        <span class="font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-landmark text-blue-600"></i> Dashboard
        </span>
        <button onclick="document.querySelector('aside').classList.toggle('hidden');" class="text-gray-600">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 md:ml-64 p-4 md:p-8 pt-20 md:pt-8 overflow-y-auto h-screen pb-24">
        
        <!-- Notifikasi -->
        <?php if($msg_verif): ?><div class="mb-6 p-4 rounded-xl <?php echo strpos($msg_verif, 'Gagal') !== false ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'; ?> border flex items-center gap-3 shadow-sm animate-fade-in"><i class="fas fa-<?php echo strpos($msg_verif, 'Gagal') !== false ? 'exclamation-circle' : 'check-circle'; ?> text-lg"></i> <?php echo $msg_verif; ?></div><?php endif; ?>
        <?php if($msg_wisata): ?><div class="mb-6 p-4 rounded-xl bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-3 shadow-sm animate-fade-in"><i class="fas fa-info-circle text-lg"></i> <?php echo $msg_wisata; ?></div><?php endif; ?>
        <?php if($msg_payment): ?><div class="mb-6 p-4 rounded-xl <?php echo $msg_type_payment == 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?> border flex items-center gap-3 shadow-sm animate-fade-in"><i class="fas fa-<?php echo $msg_type_payment == 'success' ? 'check-circle' : 'exclamation-circle'; ?> text-lg"></i> <?php echo $msg_payment; ?></div><?php endif; ?>
        <?php if($msg_profil): ?><div class="mb-6 p-4 rounded-xl <?php echo $msg_type_profil == 'success' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'; ?> border flex items-center gap-3 shadow-sm animate-fade-in"><i class="fas fa-check-circle text-lg"></i> <?php echo $msg_profil; ?></div><?php endif; ?>

        <!-- 1. TAB RINGKASAN -->
        <div id="view-ringkasan" class="tab-content animate-fade-in">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Ringkasan Desa</h1>
                <p class="text-gray-500 text-sm">Statistik perkembangan ekonomi & wisata desa Anda.</p>
            </header>
            
            <?php 
            $total_umkm = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_pedagang WHERE id_desa = %d AND status_akun = 'aktif'", $id_desa));
            $total_wisata = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_wisata WHERE id_desa = %d AND status = 'aktif'", $id_desa));
            ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card 1 -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-store"></i></div>
                    <div><p class="text-sm text-gray-500 font-medium">Total UMKM</p><h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($total_umkm); ?></h3></div>
                </div>
                <!-- Card 2 -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-map-marked-alt"></i></div>
                    <div><p class="text-sm text-gray-500 font-medium">Objek Wisata</p><h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($total_wisata); ?></h3></div>
                </div>
                <!-- Card 3 -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 <?php echo $akses_premium ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-500'; ?> rounded-xl flex items-center justify-center text-xl"><i class="fas <?php echo $akses_premium ? 'fa-crown' : 'fa-lock'; ?>"></i></div>
                    <div><p class="text-sm text-gray-500 font-medium">Status Membership</p><h3 class="text-lg font-bold text-gray-800"><?php echo $akses_premium ? 'PREMIUM' : 'FREE PLAN'; ?></h3></div>
                </div>
            </div>
        </div>

        <!-- 2. TAB VERIFIKASI -->
        <div id="view-verifikasi" class="tab-content hidden animate-fade-in">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Verifikasi UMKM</h1>
                <p class="text-gray-500 text-sm">Validasi pendaftaran pedagang baru di desa Anda.</p>
            </header>

            <?php if ( ! $akses_premium ): ?>
                <!-- TAMPILAN LOCK: FORM UPGRADE PREMIUM -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden max-w-4xl mx-auto">
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 p-8 text-center text-white relative overflow-hidden">
                        <div class="absolute inset-0 bg-white/5 backdrop-blur-sm"></div>
                        <div class="relative z-10">
                            <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-md">
                                <i class="fas fa-lock text-3xl"></i>
                            </div>
                            <h2 class="text-2xl font-bold mb-2">Fitur Terkunci</h2>
                            <p class="text-gray-300 text-sm max-w-md mx-auto">Untuk memverifikasi UMKM dan membuka fitur penuh, silakan Upgrade ke Premium.</p>
                        </div>
                    </div>
                    
                    <div class="p-8">
                        <!-- Alert Penolakan -->
                        <?php if(!empty($desa_data->alasan_penolakan)): ?>
                            <div class="mb-8 bg-red-50 border border-red-200 p-4 rounded-xl flex gap-3 text-red-800">
                                <i class="fas fa-exclamation-circle text-xl mt-0.5"></i>
                                <div><h4 class="font-bold">Pembayaran Ditolak Admin:</h4><p class="text-sm"><?php echo esc_html($desa_data->alasan_penolakan); ?></p></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($status_verifikasi == 'pending'): ?>
                            <div class="text-center p-8 bg-yellow-50 rounded-xl border border-yellow-200 text-yellow-800">
                                <i class="fas fa-clock text-4xl mb-4 text-yellow-600"></i>
                                <h3 class="font-bold text-xl mb-2">Menunggu Verifikasi Admin</h3>
                                <p class="text-sm mb-4">Bukti pembayaran sedang diverifikasi.</p>
                                <?php if($desa_data->bukti_bayar_akses): ?><a href="<?php echo esc_url($desa_data->bukti_bayar_akses); ?>" target="_blank" class="underline text-xs">Lihat Bukti</a><?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="grid md:grid-cols-2 gap-8 items-start">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg border-b pb-2 mb-4">Pembayaran</h3>
                                    <div class="bg-blue-50 p-5 rounded-xl border border-blue-100 mb-4">
                                        <p class="text-xs uppercase font-bold mb-1">Transfer Bank</p>
                                        <p class="text-lg font-bold text-gray-800 mb-2"><?php echo esc_html($sys_bank_name); ?></p>
                                        <p class="text-xl font-mono font-bold text-blue-600"><?php echo esc_html($sys_bank_account); ?></p>
                                        <p class="text-xs text-gray-500">a.n <?php echo esc_html($sys_bank_holder); ?></p>
                                    </div>
                                    <div class="text-center"><h3 class="font-bold text-sm mb-3">Atau Scan QRIS</h3><?php if($sys_qris_url): ?><img src="<?php echo esc_url($sys_qris_url); ?>" class="w-40 h-40 object-contain mx-auto border p-2 bg-white rounded-xl"><?php else: ?><div class="w-40 h-40 bg-gray-100 rounded-xl flex items-center justify-center mx-auto text-xs text-gray-400">QRIS n/a</div><?php endif; ?></div>
                                </div>
                                <div class="bg-gray-50 p-6 rounded-xl border border-gray-200">
                                    <h3 class="font-bold text-lg mb-4 flex items-center gap-2"><i class="fas fa-upload text-blue-600"></i> Konfirmasi</h3>
                                    <div class="mb-4 p-4 bg-blue-100 border border-blue-200 rounded-xl text-center"><p class="text-xs font-bold opacity-70">Tagihan</p><p class="text-2xl font-bold">Rp <?php echo number_format($harga_premium, 0, ',', '.'); ?></p></div>
                                    <form method="POST" enctype="multipart/form-data">
                                        <?php wp_nonce_field('upload_bukti_action', 'upload_bukti_nonce'); ?>
                                        <input type="hidden" name="action_upload_bukti" value="1">
                                        <div class="mb-4"><label class="block text-sm font-bold mb-2">Upload Bukti</label><input type="file" name="bukti_bayar" accept="image/*" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-600 file:text-white cursor-pointer bg-white border border-gray-300 rounded-lg p-1"></div>
                                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition flex justify-center gap-2"><i class="fas fa-paper-plane"></i> Kirim</button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- TAMPILAN PREMIUM: TABEL VERIFIKASI -->
                <?php $umkm_pending = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'menunggu_desa' ORDER BY created_at DESC", $id_desa)); ?>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center"><h3 class="font-bold text-gray-700">Antrean Pendaftaran</h3><span class="bg-blue-100 text-blue-700 text-xs px-2 py-1 rounded-full font-bold"><?php echo count($umkm_pending); ?> Menunggu</span></div>
                    <?php if($umkm_pending): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left"><thead class="bg-gray-50 text-gray-500 font-bold text-xs border-b border-gray-100"><tr><th class="px-6 py-4">Toko</th><th class="px-6 py-4">Pemilik</th><th class="px-6 py-4">Alamat</th><th class="px-6 py-4 text-center">Aksi</th></tr></thead><tbody class="divide-y divide-gray-100">
                        <?php foreach($umkm_pending as $u): ?>
                        <tr class="hover:bg-gray-50 transition"><td class="px-6 py-4 font-bold text-gray-800"><?php echo esc_html($u->nama_toko); ?></td><td class="px-6 py-4"><?php echo esc_html($u->nama_pemilik); ?><br><span class="text-xs text-gray-400"><?php echo esc_html($u->nomor_wa); ?></span></td><td class="px-6 py-4 truncate max-w-xs"><?php echo esc_html($u->alamat_lengkap); ?></td><td class="px-6 py-4 text-center"><form method="POST" class="inline-flex gap-2"><?php wp_nonce_field('verifikasi_pedagang_action', 'verifikasi_nonce'); ?><input type="hidden" name="id_pedagang" value="<?php echo $u->id; ?>"><button type="submit" name="action_verifikasi" value="1" onclick="this.form.status_keputusan.value='disetujui'" class="bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1.5 rounded-lg text-xs font-bold transition">Terima</button><button type="submit" name="action_verifikasi" value="1" onclick="this.form.status_keputusan.value='ditolak'" class="bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1.5 rounded-lg text-xs font-bold transition">Tolak</button><input type="hidden" name="status_keputusan" value=""></form></td></tr>
                        <?php endforeach; ?>
                        </tbody></table>
                    </div>
                    <?php else: ?><div class="p-10 text-center text-gray-400"><p>Tidak ada pendaftaran baru.</p></div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. TAB DATA UMKM (BARU: LIST PEDAGANG APPROVED) -->
        <div id="view-data-umkm" class="tab-content hidden animate-fade-in">
            <header class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Data UMKM Desa</h1>
                <p class="text-gray-500 text-sm">Daftar pedagang yang telah terverifikasi dan aktif di desa ini.</p>
            </header>
            
            <?php 
            $umkm_active = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'disetujui' ORDER BY nama_toko ASC", $id_desa)); 
            ?>
            
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <?php if($umkm_active): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">Nama Toko</th>
                                <th class="px-6 py-4">Pemilik</th>
                                <th class="px-6 py-4">WhatsApp</th>
                                <th class="px-6 py-4 text-center">Status Akun</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($umkm_active as $u): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo !empty($u->foto_profil) ? esc_url($u->foto_profil) : 'https://placehold.co/40?text=IMG'; ?>" class="w-10 h-10 rounded-full object-cover border border-gray-100">
                                        <span class="font-bold text-gray-800"><?php echo esc_html($u->nama_toko); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><?php echo esc_html($u->nama_pemilik); ?></td>
                                <td class="px-6 py-4">
                                    <a href="https://wa.me/<?php echo preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $u->nomor_wa)); ?>" target="_blank" class="text-green-600 hover:text-green-700 font-medium flex items-center gap-1">
                                        <i class="fab fa-whatsapp"></i> <?php echo esc_html($u->nomor_wa); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $u->status_akun == 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?php echo ucfirst($u->status_akun); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="p-12 text-center text-gray-400">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4 text-gray-300"><i class="fas fa-store-slash text-2xl"></i></div>
                        <p>Belum ada UMKM yang terdaftar di desa ini.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. TAB KELOLA WISATA -->
        <div id="view-wisata" class="tab-content hidden animate-fade-in">
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div><h1 class="text-2xl font-bold text-gray-800">Objek Wisata</h1><p class="text-gray-500 text-sm">Kelola destinasi wisata desa.</p></div>
                <?php 
                $count_existing = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_wisata WHERE id_desa = %d", $id_desa)); 
                $limit_reached = (!$akses_premium && $count_existing >= 2); 
                ?>
                <?php if($limit_reached): ?>
                    <button disabled class="bg-gray-300 text-gray-500 px-5 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 cursor-not-allowed"><i class="fas fa-lock"></i> Kuota Penuh (Max 2)</button>
                <?php else: ?>
                    <button onclick="openWisataModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 shadow-lg shadow-blue-500/20 transition"><i class="fas fa-plus"></i> Tambah Wisata</button>
                <?php endif; ?>
            </header>
            
            <?php if($limit_reached): ?><div class="bg-orange-50 border border-orange-200 text-orange-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> <span>Akun <b>Free</b> max 2 wisata. <a href="javascript:switchTab('verifikasi')" class="underline font-bold">Upgrade Premium</a>.</span></div><?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php $wisata_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE id_desa = %d AND status = 'aktif' ORDER BY created_at DESC", $id_desa)); ?>
                <?php if($wisata_list): foreach($wisata_list as $w): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-lg transition">
                    <div class="h-40 bg-gray-100 relative overflow-hidden">
                        <img src="<?php echo !empty($w->foto_utama) ? esc_url($w->foto_utama) : 'https://placehold.co/400x250?text=Wisata'; ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-md px-2 py-1 rounded-lg text-[10px] font-bold text-gray-700 shadow-sm uppercase tracking-wide"><?php echo esc_html($w->kategori); ?></div>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-gray-900 text-lg mb-1 truncate"><?php echo esc_html($w->nama_wisata); ?></h3>
                        <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?php echo $w->deskripsi ? strip_tags($w->deskripsi) : '-'; ?></p>
                        <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                            <span class="text-blue-600 font-bold text-sm bg-blue-50 px-2 py-1 rounded-md"><?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?></span>
                            <div class="flex gap-2">
                                <button onclick='openWisataModal(<?php echo json_encode($w); ?>)' class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition"><i class="fas fa-pen text-xs"></i></button>
                                <a href="?action=hapus_wisata&id=<?php echo $w->id; ?>" onclick="return confirm('Hapus?');" class="w-8 h-8 rounded-full bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center transition"><i class="fas fa-trash text-xs"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                    <div class="col-span-full py-16 text-center"><div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4 text-gray-300"><i class="fas fa-camera text-3xl"></i></div><p class="text-gray-400">Belum ada objek wisata.</p></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 5. TAB PROFIL DESA -->
        <div id="view-profil" class="tab-content hidden animate-fade-in">
            <header class="mb-8"><h1 class="text-2xl font-bold text-gray-800">Profil & Pengaturan</h1><p class="text-gray-500 text-sm">Informasi umum dan pembayaran.</p></header>
            <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <?php wp_nonce_field('save_profil_desa_action', 'profil_desa_nonce'); ?>
                <input type="hidden" name="save_profil_desa" value="1">
                <div class="grid md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                    <div class="p-8 md:col-span-1 bg-gray-50/50">
                        <div class="mb-6 text-center">
                            <label class="block mb-4 relative group cursor-pointer w-32 h-32 mx-auto rounded-full overflow-hidden border-4 border-white shadow-md">
                                <img src="<?php echo !empty($desa_data->foto) ? esc_url($desa_data->foto) : 'https://placehold.co/150?text=Logo'; ?>" class="w-full h-full object-cover group-hover:opacity-75 transition">
                                <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition text-white text-xs font-bold">Ubah</div>
                                <input type="file" name="foto_desa" accept="image/*" class="hidden">
                            </label>
                        </div>
                        <div class="space-y-4">
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Desa</label><input type="text" name="nama_desa" value="<?php echo esc_attr($desa_data->nama_desa); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Deskripsi</label><textarea name="deskripsi" rows="4" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"><?php echo esc_textarea($desa_data->deskripsi); ?></textarea></div>
                        </div>
                    </div>
                    <div class="p-8 md:col-span-2 space-y-8">
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg border-b border-gray-100 pb-2 mb-4">Lokasi</h3>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div><label class="block text-xs text-gray-400 mb-1">Provinsi</label><input type="text" value="<?php echo esc_attr($desa_data->provinsi); ?>" disabled class="w-full bg-gray-100 border-0 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed"></div>
                                <div><label class="block text-xs text-gray-400 mb-1">Kab/Kota</label><input type="text" value="<?php echo esc_attr($desa_data->kabupaten); ?>" disabled class="w-full bg-gray-100 border-0 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed"></div>
                                <div><label class="block text-xs text-gray-400 mb-1">Kecamatan</label><input type="text" value="<?php echo esc_attr($desa_data->kecamatan); ?>" disabled class="w-full bg-gray-100 border-0 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed"></div>
                                <div><label class="block text-xs text-gray-400 mb-1">Kelurahan</label><input type="text" value="<?php echo esc_attr($desa_data->kelurahan); ?>" disabled class="w-full bg-gray-100 border-0 rounded-lg px-3 py-2 text-sm text-gray-500 cursor-not-allowed"></div>
                            </div>
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Alamat Lengkap</label><textarea name="alamat_lengkap" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"><?php echo esc_textarea($desa_data->alamat_lengkap); ?></textarea></div>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg border-b border-gray-100 pb-2 mb-4">Rekening Penerimaan Desa</h3>
                            <div class="grid md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Bank</label><input type="text" name="nama_bank_desa" value="<?php echo esc_attr($desa_data->nama_bank_desa); ?>" placeholder="Contoh: BPD / BRI" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
                                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">No. Rekening</label><input type="text" name="no_rekening_desa" value="<?php echo esc_attr($desa_data->no_rekening_desa); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono"></div>
                                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Atas Nama</label><input type="text" name="atas_nama_rekening_desa" value="<?php echo esc_attr($desa_data->atas_nama_rekening_desa); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase mb-2">QRIS</label>
                                    <div class="flex items-start gap-4">
                                        <?php if(!empty($desa_data->qris_image_url_desa)): ?><div class="w-24 h-24 border border-gray-200 p-1 rounded-lg bg-white shrink-0"><img src="<?php echo esc_url($desa_data->qris_image_url_desa); ?>" class="w-full h-full object-contain"></div><?php endif; ?>
                                        <div class="flex-1"><input type="file" name="qris_desa" accept="image/*" class="block w-full text-xs text-gray-500 file:mr-2 file:py-2 file:px-3 file:rounded-full file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"><p class="text-[10px] text-gray-400 mt-1">Ganti gambar QRIS.</p></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-gray-100 flex justify-end"><button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-lg shadow-blue-500/20 transition flex items-center gap-2"><i class="fas fa-save"></i> Simpan Profil</button></div>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

<!-- MODAL WISATA -->
<div id="modal-wisata" class="fixed inset-0 z-[60] hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeWisataModal()"></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-lg bg-white shadow-2xl transform transition-transform translate-x-full duration-300 flex flex-col" id="modal-wisata-panel">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white z-10"><h2 class="text-xl font-bold text-gray-800" id="mw-title">Tambah Wisata</h2><button onclick="closeWisataModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 transition text-gray-500"><i class="fas fa-times"></i></button></div>
        <div class="flex-1 overflow-y-auto p-6">
            <form method="POST" enctype="multipart/form-data" id="form-wisata">
                <?php wp_nonce_field('save_wisata_action', 'wisata_nonce'); ?>
                <input type="hidden" name="save_wisata" value="1"><input type="hidden" name="wisata_id" id="mw_id">
                <div class="space-y-5">
                    <div><label class="block text-sm font-bold text-gray-700 mb-2">Nama Wisata</label><input type="text" name="nama_wisata" id="mw_nama" required class="w-full border border-gray-300 rounded-xl px-4 py-3"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-bold text-gray-700 mb-2">Kategori</label><select name="kategori" id="mw_kategori" class="w-full border border-gray-300 rounded-xl px-4 py-3 bg-white"><?php foreach($kategori_wisata as $kat) { echo "<option value='$kat'>$kat</option>"; } ?></select></div>
                        <div><label class="block text-sm font-bold text-gray-700 mb-2">Harga (Rp)</label><input type="number" name="harga_tiket" id="mw_harga" class="w-full border border-gray-300 rounded-xl px-4 py-3" placeholder="0"></div>
                    </div>
                    <div><label class="block text-sm font-bold text-gray-700 mb-2">Jam Buka</label><input type="text" name="jam_buka" id="mw_jam" class="w-full border border-gray-300 rounded-xl px-4 py-3" placeholder="08:00 - 17:00"></div>
                    <div><label class="block text-sm font-bold text-gray-700 mb-2">Kontak Pengelola</label><input type="text" name="kontak_pengelola" id="mw_kontak" class="w-full border border-gray-300 rounded-xl px-4 py-3" placeholder="No. HP / WA"></div>
                    <div><label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi</label><textarea name="deskripsi" id="mw_deskripsi" rows="3" class="w-full border border-gray-300 rounded-xl px-4 py-3"></textarea></div>
                    <div><label class="block text-sm font-bold text-gray-700 mb-2">Fasilitas</label><textarea name="fasilitas" id="mw_fasilitas" rows="2" class="w-full border border-gray-300 rounded-xl px-4 py-3" placeholder="Parkir luas, Toilet, Musholla..."></textarea></div>
                    <div><label class="block text-sm font-bold text-gray-700 mb-2">Link Maps</label><input type="url" name="lokasi_maps" id="mw_maps" class="w-full border border-gray-300 rounded-xl px-4 py-3" placeholder="https://maps.google.com/..."></div>
                    <div><label class="block text-sm font-bold text-gray-700 mb-2">Foto Utama</label><input type="file" name="foto_utama" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-600 file:text-white hover:file:bg-blue-700"></div>
                </div>
            </form>
        </div>
        <div class="p-6 border-t border-gray-100 bg-gray-50"><button onclick="document.getElementById('form-wisata').submit()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-500/20 flex items-center justify-center gap-2"><i class="fas fa-save"></i> Simpan Data</button></div>
    </div>
</div>

<style>
::-webkit-scrollbar { width: 6px; } ::-webkit-scrollbar-track { background: #f1f1f1; } ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.active-tab { background-color: #eff6ff; color: #2563eb; border-right: 3px solid #2563eb; }
.animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active-tab'));
    const target = document.getElementById('view-' + tabName); if(target) target.classList.remove('hidden');
    const nav = document.getElementById('nav-' + tabName); if(nav) nav.classList.add('active-tab');
}

const mw = document.getElementById('modal-wisata'), mp = document.getElementById('modal-wisata-panel');
function openWisataModal(data = null) {
    mw.classList.remove('hidden'); setTimeout(() => mp.classList.remove('translate-x-full'), 10);
    document.getElementById('mw_id').value = ''; document.getElementById('mw_title').innerText = 'Tambah Wisata'; document.getElementById('form-wisata').reset();
    if(data) {
        document.getElementById('mw_title').innerText = 'Edit Wisata'; document.getElementById('mw_id').value = data.id;
        document.getElementById('mw_nama').value = data.nama_wisata; document.getElementById('mw_kategori').value = data.kategori;
        document.getElementById('mw_harga').value = data.harga_tiket; document.getElementById('mw_jam').value = data.jam_buka;
        document.getElementById('mw_kontak').value = data.kontak_pengelola; // New
        document.getElementById('mw_deskripsi').value = data.deskripsi;
        document.getElementById('mw_fasilitas').value = data.fasilitas; // New
        document.getElementById('mw_maps').value = data.lokasi_maps;
    }
}
function closeWisataModal() { mp.classList.add('translate-x-full'); setTimeout(() => mw.classList.add('hidden'), 300); }
document.addEventListener('DOMContentLoaded', () => { const tab = new URLSearchParams(window.location.search).get('tab') || 'ringkasan'; switchTab(tab); });
</script>

<?php wp_footer(); ?>