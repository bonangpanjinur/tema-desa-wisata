<?php
/**
 * Template Name: Dashboard Desa
 * Description: Panel Frontend Desa Lengkap (CRUD Stabil + Fitur Lengkap).
 * Status: FINAL FIX (Logic CRUD Diperbaiki Total).
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

// --- CONFIG: TABLE NAMES ---
$table_desa     = $wpdb->prefix . 'dw_desa'; 
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_wisata   = $wpdb->prefix . 'dw_wisata';

// 2. Ambil Data Desa
$desa_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_desa WHERE id_user_desa = %d", $current_user_id) );

// --- AUTO-CREATE: Jika data desa belum ada ---
if ( ! $desa_data ) {
    $nama_default = 'Desa ' . $current_user->display_name;
    $slug_default = sanitize_title($nama_default) . '-' . $current_user_id;
    
    $wpdb->insert($table_desa, [
        'id_user_desa'            => $current_user_id,
        'nama_desa'               => $nama_default,
        'slug_desa'               => $slug_default,
        'status'                  => 'pending',
        'status_akses_verifikasi' => 'locked', 
        'created_at'              => current_time('mysql'),
        'updated_at'              => current_time('mysql')
    ]);
    
    $desa_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_desa WHERE id_user_desa = %d", $current_user_id) );
}

$id_desa = $desa_data->id;
$akses_premium = ($desa_data->status_akses_verifikasi === 'active');
$status_verifikasi = $desa_data->status_akses_verifikasi; 

// --- MESSAGE HANDLER ---
$msg = '';
$msg_type = '';

// --- HANDLER 1: SIMPAN PROFIL ---
if ( isset($_POST['save_profil_desa']) && check_admin_referer('save_profil_desa_action', 'profil_desa_nonce') ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    
    $update_desa = [
        'nama_desa'               => sanitize_text_field($_POST['nama_desa']),
        'deskripsi'               => wp_kses_post($_POST['deskripsi']),
        'alamat_lengkap'          => sanitize_textarea_field($_POST['alamat_lengkap']),
        'nama_bank_desa'          => sanitize_text_field($_POST['nama_bank_desa']),
        'no_rekening_desa'        => sanitize_text_field($_POST['no_rekening_desa']),
        'atas_nama_rekening_desa' => sanitize_text_field($_POST['atas_nama_rekening_desa']),
        'api_provinsi_id'         => sanitize_text_field($_POST['api_provinsi_id']),
        'api_kabupaten_id'        => sanitize_text_field($_POST['api_kabupaten_id']),
        'api_kecamatan_id'        => sanitize_text_field($_POST['api_kecamatan_id']),
        'api_kelurahan_id'        => sanitize_text_field($_POST['api_kelurahan_id']),
        'provinsi'                => sanitize_text_field($_POST['provinsi_nama']),
        'kabupaten'               => sanitize_text_field($_POST['kabupaten_nama']),
        'kecamatan'               => sanitize_text_field($_POST['kecamatan_nama']),
        'kelurahan'               => sanitize_text_field($_POST['kelurahan_nama']),
        'updated_at'              => current_time('mysql')
    ];

    // Upload Files Logic
    $files_map = ['foto_desa' => 'foto', 'foto_sampul' => 'foto_sampul', 'qris_desa' => 'qris_image_url_desa'];
    foreach($files_map as $input => $col) {
        if ( ! empty($_FILES[$input]['name']) ) {
            $up = wp_handle_upload( $_FILES[$input], ['test_form' => false] );
            if ( isset( $up['url'] ) && ! isset( $up['error'] ) ) $update_desa[$col] = $up['url'];
        }
    }
    
    $wpdb->update($table_desa, $update_desa, ['id' => $id_desa]);
    $msg = "Profil desa berhasil diperbarui."; $msg_type = "success";
    $desa_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_desa WHERE id = %d", $id_desa) ); // Refresh
}

// --- HANDLER 2: UPLOAD BUKTI BAYAR ---
if ( isset($_POST['action_upload_bukti']) && check_admin_referer('upload_bukti_action', 'upload_bukti_nonce') ) {
    if ( ! function_exists( 'wp_handle_upload' ) ) require_once( ABSPATH . 'wp-admin/includes/file.php' );
    $uploadedfile = $_FILES['bukti_bayar'];
    if ( ! empty( $uploadedfile['name'] ) ) {
        $movefile = wp_handle_upload( $uploadedfile, ['test_form' => false] );
        if ( $movefile && ! isset( $movefile['error'] ) ) {
            $wpdb->update($table_desa, [
                'bukti_bayar_akses' => $movefile['url'],
                'status_akses_verifikasi' => 'pending',
                'updated_at' => current_time('mysql')
            ], ['id' => $id_desa]);
            $msg = "Bukti pembayaran terkirim! Mohon tunggu verifikasi admin."; $msg_type = "success";
            $status_verifikasi = 'pending';
        } else {
            $msg = "Gagal upload: " . $movefile['error']; $msg_type = "error";
        }
    }
}

// --- HANDLER 3: VERIFIKASI UMKM ---
if ( isset($_POST['action_verifikasi']) && check_admin_referer('verifikasi_pedagang_action', 'verifikasi_nonce') ) {
    if ( ! $akses_premium ) {
        $msg = "Gagal: Fitur ini memerlukan akses Premium."; $msg_type = "error";
    } else {
        $id_pedagang = intval($_POST['id_pedagang']);
        $status_baru = sanitize_text_field($_POST['status_keputusan']);
        $update_data = ['status_pendaftaran' => $status_baru, 'approved_by' => 'desa', 'updated_at' => current_time('mysql')];
        
        if ($status_baru == 'disetujui') {
            $update_data['status_akun'] = 'aktif';
            $options = get_option('dw_settings');
            $update_data['sisa_transaksi'] = isset($options['kuota_gratis_default']) ? absint($options['kuota_gratis_default']) : 0;
            // Add Role Logic if needed
        } else {
            $update_data['status_akun'] = 'nonaktif';
        }
        $wpdb->update($table_pedagang, $update_data, ['id' => $id_pedagang]);
        $msg = "Status pedagang diperbarui: " . ucfirst($status_baru); $msg_type = "success";
    }
}

// --- HANDLER 4: KELOLA WISATA (PERBAIKAN CRUD) ---
if ( isset($_POST['save_wisata']) && check_admin_referer('save_wisata_action', 'wisata_nonce') ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );

    // Validasi ID (Pastikan integer)
    $wisata_id = isset($_POST['wisata_id']) && is_numeric($_POST['wisata_id']) ? intval($_POST['wisata_id']) : 0;

    // Cek Kuota Free
    if ( ! $akses_premium && $wisata_id === 0 ) {
        $count_wisata = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_wisata WHERE id_desa = %d", $id_desa));
        if ($count_wisata >= 2) { 
            $msg = "Gagal: Akun Free maksimal 2 wisata. Upgrade ke Premium untuk unlimited."; $msg_type = "error";
        }
    }

    if ( empty($msg) || $msg_type !== 'error' ) {
        $data_wisata = [
            'id_desa'          => $id_desa, 
            'nama_wisata'      => sanitize_text_field($_POST['nama_wisata']), 
            'kategori'         => sanitize_text_field($_POST['kategori']),
            'deskripsi'        => wp_kses_post($_POST['deskripsi']), 
            'harga_tiket'      => floatval($_POST['harga_tiket']), 
            'jam_buka'         => sanitize_text_field($_POST['jam_buka']),
            'fasilitas'        => sanitize_textarea_field($_POST['fasilitas']),
            'kontak_pengelola' => sanitize_text_field($_POST['kontak_pengelola']),
            'lokasi_maps'      => esc_url_raw($_POST['lokasi_maps']), 
            'updated_at'       => current_time('mysql')
        ];

        // Handle Image
        if ( ! empty($_FILES['foto_utama']['name']) ) {
            $uploaded = wp_handle_upload( $_FILES['foto_utama'], ['test_form' => false] );
            if ( isset( $uploaded['url'] ) ) $data_wisata['foto_utama'] = $uploaded['url'];
        }

        if ( $wisata_id > 0 ) { 
            // UPDATE
            $wpdb->update($table_wisata, $data_wisata, ['id' => $wisata_id, 'id_desa' => $id_desa]); 
            $msg = "Data wisata berhasil diperbarui."; $msg_type = "success";
        } else { 
            // INSERT
            $data_wisata['slug'] = sanitize_title($_POST['nama_wisata']).'-'.rand(100,999); 
            $data_wisata['created_at'] = current_time('mysql'); 
            $data_wisata['status'] = 'aktif'; 
            $wpdb->insert($table_wisata, $data_wisata); 
            $msg = "Wisata baru berhasil ditambahkan."; $msg_type = "success";
        }
    }
}

// Handler Delete Wisata (Dengan Nonce untuk Keamanan)
if ( isset($_GET['action']) && $_GET['action'] == 'hapus_wisata' && isset($_GET['id']) && isset($_GET['_wpnonce']) ) {
    if ( wp_verify_nonce($_GET['_wpnonce'], 'hapus_wisata_'.$_GET['id']) ) {
        $wpdb->delete($table_wisata, ['id' => intval($_GET['id']), 'id_desa' => $id_desa]); 
        wp_redirect( home_url('/dashboard-desa/?tab=wisata') ); // Redirect bersih agar tidak terhapus ulang saat refresh
        exit;
    }
}

// --- FETCH DATA (UNTUK VIEW) ---
// Kita ambil data wisata di awal agar bisa di-passing ke JS
$wisata_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE id_desa = %d AND status = 'aktif' ORDER BY created_at DESC", $id_desa));

$default_cats = ['Wisata Alam', 'Wisata Bahari', 'Wisata Budaya', 'Wisata Sejarah', 'Spot Foto', 'Camping Ground', 'Edukasi'];
$existing_cats = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_wisata WHERE kategori != ''");
$kategori_wisata = array_unique(array_merge($default_cats, $existing_cats ?: [])); sort($kategori_wisata);

$sys_bank_name = get_option('dw_bank_name', '-'); 
$sys_bank_account = get_option('dw_bank_account', '-'); 
$sys_bank_holder = get_option('dw_bank_holder', '-'); 
$sys_qris_url = get_option('dw_qris_image_url', '');
$settings = get_option('dw_settings', []); 
$harga_premium = isset($settings['harga_premium_desa']) ? $settings['harga_premium_desa'] : 0;

get_header(); 
?>

<!-- CDN Tailwind & FontAwesome -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="bg-gray-50 min-h-screen font-sans flex overflow-hidden">
    
    <!-- SIDEBAR (Fixed & Hidden on Mobile) -->
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

        <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
            <button onclick="switchTab('ringkasan')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition active-tab" id="nav-ringkasan"><i class="fas fa-chart-pie w-5 text-center"></i> Ringkasan</button>
            <button onclick="switchTab('verifikasi')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition relative" id="nav-verifikasi"><i class="fas fa-users-cog w-5 text-center"></i> Verifikasi UMKM <?php if(!$akses_premium): ?><i class="fas fa-lock text-xs text-gray-400 absolute right-4"></i><?php endif; ?></button>
            <button onclick="switchTab('data-umkm')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-data-umkm"><i class="fas fa-store w-5 text-center"></i> Data UMKM</button>
            <button onclick="switchTab('wisata')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-wisata"><i class="fas fa-map-location-dot w-5 text-center"></i> Kelola Wisata</button>
            <button onclick="switchTab('profil')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-profil"><i class="fas fa-id-card-clip w-5 text-center"></i> Profil Desa</button>
        </nav>

        <div class="p-4 border-t border-gray-100">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 rounded-xl hover:bg-red-50 transition w-full"><i class="fas fa-sign-out-alt w-5 text-center"></i> Keluar</a>
        </div>
    </aside>

    <!-- MOBILE HEADER -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-30 flex items-center justify-between px-4 shadow-sm">
        <span class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-landmark text-blue-600"></i> Dashboard</span>
        <button onclick="document.querySelector('aside').classList.toggle('hidden');" class="text-gray-600"><i class="fas fa-bars text-xl"></i></button>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 md:ml-64 p-4 md:p-8 pt-20 md:pt-8 overflow-y-auto h-screen pb-24">
        
        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl <?php echo ($msg_type=='error')?'bg-red-50 text-red-700 border-red-200':'bg-green-50 text-green-700 border-green-200'; ?> border flex items-center gap-3 shadow-sm animate-fade-in">
                <i class="fas <?php echo ($msg_type=='error')?'fa-exclamation-circle':'fa-check-circle'; ?>"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- 1. TAB RINGKASAN -->
        <div id="view-ringkasan" class="tab-content animate-fade-in">
            <header class="mb-8"><h1 class="text-2xl font-bold text-gray-800">Ringkasan Desa</h1><p class="text-gray-500 text-sm">Statistik perkembangan.</p></header>
            <?php 
            $total_umkm = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_pedagang WHERE id_desa = %d AND status_akun = 'aktif'", $id_desa));
            $total_wisata_count = count($wisata_list);
            ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-store"></i></div>
                    <div><p class="text-sm text-gray-500 font-medium">Total UMKM</p><h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($total_umkm); ?></h3></div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-map-marked-alt"></i></div>
                    <div><p class="text-sm text-gray-500 font-medium">Objek Wisata</p><h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($total_wisata_count); ?></h3></div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 hover:shadow-md transition">
                    <div class="w-12 h-12 <?php echo $akses_premium ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-500'; ?> rounded-xl flex items-center justify-center text-xl"><i class="fas <?php echo $akses_premium ? 'fa-crown' : 'fa-lock'; ?>"></i></div>
                    <div><p class="text-sm text-gray-500 font-medium">Status Membership</p><h3 class="text-lg font-bold text-gray-800"><?php echo $akses_premium ? 'PREMIUM' : 'FREE PLAN'; ?></h3></div>
                </div>
            </div>
        </div>

        <!-- 2. TAB VERIFIKASI -->
        <div id="view-verifikasi" class="tab-content hidden animate-fade-in">
            <header class="mb-8"><h1 class="text-2xl font-bold text-gray-800">Verifikasi UMKM</h1><p class="text-gray-500 text-sm">Validasi pendaftaran pedagang.</p></header>
            <?php if ( ! $akses_premium ): ?>
                <div class="bg-white rounded-2xl border border-gray-200 shadow-lg p-8 text-center">
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 p-8 text-white rounded-xl mb-8">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-lock text-3xl"></i></div>
                        <h2 class="text-2xl font-bold mb-2">Fitur Terkunci</h2>
                        <p class="text-gray-300 text-sm">Upgrade ke Premium untuk membuka fitur Verifikasi UMKM.</p>
                    </div>
                    <?php if ($status_verifikasi == 'pending'): ?>
                        <div class="text-center p-4 bg-yellow-50 rounded-xl text-yellow-800 font-bold"><i class="fas fa-clock"></i> Bukti pembayaran sedang diverifikasi admin.</div>
                    <?php else: ?>
                        <!-- Form Upload Bukti -->
                        <div class="max-w-md mx-auto bg-gray-50 p-6 rounded-xl border">
                            <h3 class="font-bold mb-4">Upload Bukti Pembayaran (Rp <?php echo number_format($harga_premium, 0, ',', '.'); ?>)</h3>
                            <div class="mb-4 text-sm text-left">
                                <p><strong>Bank:</strong> <?php echo esc_html($sys_bank_name); ?></p>
                                <p><strong>Rek:</strong> <?php echo esc_html($sys_bank_account); ?> (a.n <?php echo esc_html($sys_bank_holder); ?>)</p>
                            </div>
                            <form method="POST" enctype="multipart/form-data">
                                <?php wp_nonce_field('upload_bukti_action', 'upload_bukti_nonce'); ?>
                                <input type="hidden" name="action_upload_bukti" value="1">
                                <input type="file" name="bukti_bayar" required class="block w-full text-sm mb-4 border rounded bg-white p-2">
                                <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded hover:bg-blue-700">Kirim Bukti</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Tabel Verifikasi -->
                <?php $umkm_pending = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'menunggu_desa' ORDER BY created_at DESC", $id_desa)); ?>
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <?php if($umkm_pending): ?>
                    <div class="overflow-x-auto"><table class="w-full text-sm text-left"><thead class="bg-gray-50 text-gray-500 font-bold text-xs border-b border-gray-100"><tr><th class="px-6 py-4">Toko</th><th class="px-6 py-4">Pemilik</th><th class="px-6 py-4">Alamat</th><th class="px-6 py-4 text-center">Aksi</th></tr></thead><tbody class="divide-y divide-gray-100">
                    <?php foreach($umkm_pending as $u): ?>
                    <tr class="hover:bg-gray-50"><td class="px-6 py-4 font-bold"><?php echo esc_html($u->nama_toko); ?></td><td class="px-6 py-4"><?php echo esc_html($u->nama_pemilik); ?></td><td class="px-6 py-4 truncate max-w-xs"><?php echo esc_html($u->alamat_lengkap); ?></td><td class="px-6 py-4 text-center"><form method="POST" class="inline-flex gap-2"><?php wp_nonce_field('verifikasi_pedagang_action', 'verifikasi_nonce'); ?><input type="hidden" name="id_pedagang" value="<?php echo $u->id; ?>"><button type="submit" name="action_verifikasi" value="1" onclick="this.form.status_keputusan.value='disetujui'" class="bg-green-100 text-green-700 px-3 py-1 rounded font-bold hover:bg-green-200">Terima</button><button type="submit" name="action_verifikasi" value="1" onclick="this.form.status_keputusan.value='ditolak'" class="bg-red-100 text-red-700 px-3 py-1 rounded font-bold hover:bg-red-200">Tolak</button><input type="hidden" name="status_keputusan" value=""></form></td></tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                    <?php else: ?><div class="p-10 text-center text-gray-400">Tidak ada pendaftaran baru.</div><?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. TAB DATA UMKM -->
        <div id="view-data-umkm" class="tab-content hidden animate-fade-in">
            <header class="mb-8"><h1 class="text-2xl font-bold text-gray-800">Data UMKM</h1><p class="text-gray-500 text-sm">Daftar pedagang aktif.</p></header>
            <?php $umkm_active = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'disetujui' ORDER BY nama_toko ASC", $id_desa)); ?>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <?php if($umkm_active): ?>
                <div class="overflow-x-auto"><table class="w-full text-sm text-left"><thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs border-b border-gray-100"><tr><th class="px-6 py-4">Toko</th><th class="px-6 py-4">Pemilik</th><th class="px-6 py-4">Kontak</th><th class="px-6 py-4 text-center">Status</th></tr></thead><tbody class="divide-y divide-gray-100">
                <?php foreach($umkm_active as $u): ?>
                <tr class="hover:bg-gray-50"><td class="px-6 py-4 font-bold"><?php echo esc_html($u->nama_toko); ?></td><td class="px-6 py-4"><?php echo esc_html($u->nama_pemilik); ?></td><td class="px-6 py-4"><?php echo esc_html($u->nomor_wa); ?></td><td class="px-6 py-4 text-center"><span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Aktif</span></td></tr>
                <?php endforeach; ?>
                </tbody></table></div>
                <?php else: ?><div class="p-12 text-center text-gray-400">Belum ada UMKM aktif.</div><?php endif; ?>
            </div>
        </div>

        <!-- 4. TAB KELOLA WISATA (PERBAIKAN UTAMA DI SINI) -->
        <div id="view-wisata" class="tab-content hidden animate-fade-in">
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div><h1 class="text-2xl font-bold text-gray-800">Objek Wisata</h1><p class="text-gray-500 text-sm">Kelola destinasi wisata.</p></div>
                <?php $limit_reached = (!$akses_premium && $total_wisata_count >= 2); ?>
                <?php if($limit_reached): ?>
                    <button disabled class="bg-gray-300 text-gray-500 px-5 py-2.5 rounded-xl font-bold text-sm cursor-not-allowed"><i class="fas fa-lock"></i> Kuota Penuh</button>
                <?php else: ?>
                    <button onclick="openWisataModalNew()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg transition"><i class="fas fa-plus"></i> Tambah</button>
                <?php endif; ?>
            </header>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if($wisata_list): foreach($wisata_list as $index => $w): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-lg transition relative">
                    <div class="h-40 bg-gray-100 relative overflow-hidden">
                        <img src="<?php echo !empty($w->foto_utama) ? esc_url($w->foto_utama) : 'https://placehold.co/400x250?text=Wisata'; ?>" class="w-full h-full object-cover">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-md px-2 py-1 rounded-lg text-[10px] font-bold text-gray-700 shadow-sm uppercase"><?php echo esc_html($w->kategori); ?></div>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-gray-900 text-lg mb-1 truncate"><?php echo esc_html($w->nama_wisata); ?></h3>
                        <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?php echo $w->deskripsi ? strip_tags($w->deskripsi) : '-'; ?></p>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                            <span class="text-blue-600 font-bold text-sm bg-blue-50 px-2 py-1 rounded-md"><?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?></span>
                            <div class="flex gap-2">
                                <!-- Tombol Edit Menggunakan Index Array JS -->
                                <button type="button" onclick="editWisata(<?php echo $index; ?>)" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition"><i class="fas fa-pen text-xs"></i></button>
                                
                                <!-- Tombol Hapus dengan Nonce -->
                                <?php $del_nonce = wp_create_nonce('hapus_wisata_'.$w->id); ?>
                                <a href="<?php echo home_url('/dashboard-desa/?tab=wisata&action=hapus_wisata&id='.$w->id.'&_wpnonce='.$del_nonce); ?>" onclick="return confirm('Yakin ingin menghapus wisata ini?');" class="w-8 h-8 rounded-full bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center transition"><i class="fas fa-trash text-xs"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="col-span-full py-16 text-center text-gray-400">Belum ada objek wisata.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 5. TAB PROFIL -->
        <div id="view-profil" class="tab-content hidden animate-fade-in">
            <!-- View Mode -->
            <div id="profil-view-mode">
                <header class="mb-6 flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-800">Profil Desa</h1>
                    <button onclick="toggleProfilMode('edit')" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow hover:bg-blue-700 transition"><i class="fas fa-edit"></i> Edit Profil</button>
                </header>
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden p-6">
                    <div class="flex flex-col md:flex-row gap-6">
                        <div class="w-full md:w-1/3">
                            <img src="<?php echo $desa_data->foto_sampul ? esc_url($desa_data->foto_sampul) : 'https://placehold.co/400x300?text=Cover'; ?>" class="w-full h-48 object-cover rounded-xl mb-4">
                            <h2 class="text-xl font-bold"><?php echo esc_html($desa_data->nama_desa); ?></h2>
                            <p class="text-gray-500 text-sm"><?php echo esc_html($desa_data->kabupaten); ?></p>
                        </div>
                        <div class="w-full md:w-2/3 space-y-4">
                            <div><label class="text-xs font-bold text-gray-400 uppercase">Deskripsi</label><p class="text-gray-700"><?php echo nl2br(esc_html($desa_data->deskripsi)); ?></p></div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="text-xs font-bold text-gray-400 uppercase">Bank</label><p><?php echo esc_html($desa_data->nama_bank_desa); ?></p></div>
                                <div><label class="text-xs font-bold text-gray-400 uppercase">No. Rekening</label><p><?php echo esc_html($desa_data->no_rekening_desa); ?></p></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Mode -->
            <div id="profil-edit-mode" class="hidden">
                <header class="mb-6 flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-800">Edit Profil</h1>
                    <button onclick="toggleProfilMode('view')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold shadow hover:bg-gray-300 transition">Batal</button>
                </header>
                <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 space-y-6">
                    <?php wp_nonce_field('save_profil_desa_action', 'profil_desa_nonce'); ?>
                    <input type="hidden" name="save_profil_desa" value="1">
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold mb-1">Nama Desa</label>
                            <input type="text" name="nama_desa" value="<?php echo esc_attr($desa_data->nama_desa); ?>" class="w-full border rounded p-2">
                        </div>
                        <div>
                            <label class="block text-sm font-bold mb-1">Foto Sampul</label>
                            <input type="file" name="foto_sampul" class="w-full border rounded p-1 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold mb-1">Deskripsi</label>
                        <textarea name="deskripsi" rows="4" class="w-full border rounded p-2"><?php echo esc_textarea($desa_data->deskripsi); ?></textarea>
                    </div>
                    
                    <!-- Region (Simplified for brevity, ensure JS logic below matches IDs) -->
                    <div id="region-data" data-prov="<?php echo esc_attr($desa_data->api_provinsi_id); ?>" data-kota="<?php echo esc_attr($desa_data->api_kabupaten_id); ?>" data-kec="<?php echo esc_attr($desa_data->api_kecamatan_id); ?>" data-desa="<?php echo esc_attr($desa_data->api_kelurahan_id); ?>"></div>
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="text-xs font-bold">Provinsi</label><select name="api_provinsi_id" id="dw_provinsi" class="w-full border rounded p-2"><option>Loading...</option></select></div>
                        <div><label class="text-xs font-bold">Kabupaten</label><select name="api_kabupaten_id" id="dw_kota" class="w-full border rounded p-2" disabled></select></div>
                        <div><label class="text-xs font-bold">Kecamatan</label><select name="api_kecamatan_id" id="dw_kecamatan" class="w-full border rounded p-2" disabled></select></div>
                        <div><label class="text-xs font-bold">Kelurahan</label><select name="api_kelurahan_id" id="dw_desa" class="w-full border rounded p-2" disabled></select></div>
                    </div>
                    <!-- Hidden inputs for names -->
                    <input type="hidden" name="provinsi_nama" id="input_provinsi_nama" value="<?php echo esc_attr($desa_data->provinsi); ?>">
                    <input type="hidden" name="kabupaten_nama" id="input_kabupaten_name" value="<?php echo esc_attr($desa_data->kabupaten); ?>">
                    <input type="hidden" name="kecamatan_nama" id="input_kecamatan_name" value="<?php echo esc_attr($desa_data->kecamatan); ?>">
                    <input type="hidden" name="kelurahan_nama" id="input_kelurahan_name" value="<?php echo esc_attr($desa_data->kelurahan); ?>">

                    <div class="grid md:grid-cols-3 gap-4 border-t pt-4">
                        <div><label class="block text-sm font-bold mb-1">Nama Bank</label><input type="text" name="nama_bank_desa" value="<?php echo esc_attr($desa_data->nama_bank_desa); ?>" class="w-full border rounded p-2"></div>
                        <div><label class="block text-sm font-bold mb-1">No. Rekening</label><input type="text" name="no_rekening_desa" value="<?php echo esc_attr($desa_data->no_rekening_desa); ?>" class="w-full border rounded p-2"></div>
                        <div><label class="block text-sm font-bold mb-1">Atas Nama</label><input type="text" name="atas_nama_rekening_desa" value="<?php echo esc_attr($desa_data->atas_nama_rekening_desa); ?>" class="w-full border rounded p-2"></div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="bg-gray-900 text-white px-6 py-2 rounded font-bold hover:bg-black">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<!-- MODAL WISATA (Fixed Logic) -->
<div id="modal-wisata" class="fixed inset-0 z-[60] hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeWisataModal()"></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-lg bg-white shadow-2xl transform transition-transform translate-x-full duration-300 flex flex-col" id="modal-wisata-panel">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white z-10">
            <h2 class="text-xl font-bold text-gray-800" id="mw-title">Tambah Wisata</h2>
            <button onclick="closeWisataModal()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto p-6">
            <form method="POST" enctype="multipart/form-data" id="form-wisata">
                <?php wp_nonce_field('save_wisata_action', 'wisata_nonce'); ?>
                <input type="hidden" name="save_wisata" value="1">
                
                <!-- PENTING: ID ini menentukan Edit vs Insert -->
                <input type="hidden" name="wisata_id" id="mw_id" value=""> 
                
                <div class="space-y-4">
                    <div><label class="block text-sm font-bold mb-1">Nama Wisata</label><input type="text" name="nama_wisata" id="mw_nama" required class="w-full border rounded px-3 py-2"></div>
                    
                    <div id="mw_preview_box" class="hidden mb-2">
                        <label class="text-xs font-bold text-gray-500">Foto Saat Ini:</label>
                        <img id="mw_preview_img" src="" class="h-32 rounded border mt-1">
                    </div>
                    
                    <div><label class="block text-sm font-bold mb-1">Upload Foto</label><input type="file" name="foto_utama" class="w-full text-sm border rounded p-1"></div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-sm font-bold mb-1">Kategori</label><select name="kategori" id="mw_kategori" class="w-full border rounded px-3 py-2"><?php foreach($kategori_wisata as $k) echo "<option value='$k'>$k</option>"; ?></select></div>
                        <div><label class="block text-sm font-bold mb-1">Harga (Rp)</label><input type="number" name="harga_tiket" id="mw_harga" class="w-full border rounded px-3 py-2"></div>
                    </div>
                    
                    <div><label class="block text-sm font-bold mb-1">Jam Buka</label><input type="text" name="jam_buka" id="mw_jam" class="w-full border rounded px-3 py-2"></div>
                    <div><label class="block text-sm font-bold mb-1">Kontak</label><input type="text" name="kontak_pengelola" id="mw_kontak" class="w-full border rounded px-3 py-2"></div>
                    <div><label class="block text-sm font-bold mb-1">Deskripsi</label><textarea name="deskripsi" id="mw_deskripsi" rows="3" class="w-full border rounded px-3 py-2"></textarea></div>
                    <div><label class="block text-sm font-bold mb-1">Fasilitas</label><textarea name="fasilitas" id="mw_fasilitas" rows="2" class="w-full border rounded px-3 py-2"></textarea></div>
                    <div><label class="block text-sm font-bold mb-1">Link Maps</label><input type="url" name="lokasi_maps" id="mw_maps" class="w-full border rounded px-3 py-2"></div>
                </div>
                
                <div class="mt-6 pt-4 border-t">
                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .active-tab { background-color: #eff6ff; color: #2563eb; border-right: 3px solid #2563eb; }
</style>

<!-- DATA PASSING TO JS (SOLUSI STABIL) -->
<script>
    // Menyimpan data wisata dalam variabel global JS agar aman dari error kutip/karakter
    var wisataData = <?php echo json_encode($wisata_list); ?>;
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
</script>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active-tab'));
    
    const target = document.getElementById('view-' + tabName);
    const nav = document.getElementById('nav-' + tabName);
    
    if(target) target.classList.remove('hidden');
    if(nav) nav.classList.add('active-tab');
}

function toggleProfilMode(mode) {
    document.getElementById('profil-view-mode').classList.toggle('hidden', mode === 'edit');
    document.getElementById('profil-edit-mode').classList.toggle('hidden', mode !== 'edit');
}

// --- MODAL LOGIC YANG DIPERBAIKI ---
const mw = document.getElementById('modal-wisata');
const mp = document.getElementById('modal-wisata-panel');

function openWisataModalNew() {
    // Mode Tambah Baru
    resetForm();
    document.getElementById('mw_title').innerText = 'Tambah Wisata';
    mw.classList.remove('hidden');
    setTimeout(() => mp.classList.remove('translate-x-full'), 10);
}

function editWisata(index) {
    // Mode Edit
    resetForm();
    
    // Ambil data aman dari array JS
    var data = wisataData[index];
    
    // Safety check: Pastikan data dan elemen ada sebelum diisi
    if(data) {
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if(el) el.value = val || '';
        };

        if(document.getElementById('mw_title')) document.getElementById('mw_title').innerText = 'Edit Wisata';
        
        setVal('mw_id', data.id);
        setVal('mw_nama', data.nama_wisata);
        setVal('mw_kategori', data.kategori);
        setVal('mw_harga', data.harga_tiket);
        setVal('mw_jam', data.jam_buka);
        setVal('mw_kontak', data.kontak_pengelola);
        setVal('mw_deskripsi', data.deskripsi);
        setVal('mw_fasilitas', data.fasilitas);
        setVal('mw_maps', data.lokasi_maps);
        
        const previewBox = document.getElementById('mw_preview_box');
        const previewImg = document.getElementById('mw_preview_img');
        
        if(data.foto_utama && previewBox && previewImg) {
            previewBox.classList.remove('hidden');
            previewImg.src = data.foto_utama;
        }
        
        mw.classList.remove('hidden');
        setTimeout(() => mp.classList.remove('translate-x-full'), 10);
    }
}

function resetForm() {
    const form = document.getElementById('form-wisata');
    if(form) form.reset();
    
    const idField = document.getElementById('mw_id');
    if(idField) idField.value = ''; // Reset ID jadi kosong (Insert mode)
    
    const previewBox = document.getElementById('mw_preview_box');
    if(previewBox) previewBox.classList.add('hidden');
}

function closeWisataModal() {
    mp.classList.add('translate-x-full');
    setTimeout(() => mw.classList.add('hidden'), 300);
}

// Region Logic (Simplified jQuery)
jQuery(document).ready(function($) {
    var els = { prov: $('#dw_provinsi'), kota: $('#dw_kota'), kec: $('#dw_kecamatan'), desa: $('#dw_desa') };
    var data = $('#region-data').data();

    function loadR(act, pid, el, sel, cb) {
        el.html('<option>Loading...</option>').prop('disabled', true);
        var p = { action: act };
        if(act=='dw_fetch_regencies') p.province_id = pid;
        if(act=='dw_fetch_districts') p.regency_id = pid;
        if(act=='dw_fetch_villages') p.district_id = pid;
        
        $.get(ajaxurl, p, function(res){
            if(res.success) {
                var o = '<option value="">-- Pilih --</option>';
                $.each(res.data.data||res.data, function(i,v){ 
                    var id=v.id||v.code; 
                    o+='<option value="'+id+'" '+(id==sel?'selected':'')+'>'+(v.name||v.nama)+'</option>'; 
                });
                el.html(o).prop('disabled', false);
                if(cb) cb();
            }
        });
    }
    
    function setText(el, target) { $(target).val($(el).find('option:selected').text()); }

    // Chain Load
    if(data.prov) {
        loadR('dw_fetch_provinces', null, els.prov, data.prov, function(){
            loadR('dw_fetch_regencies', data.prov, els.kota, data.kota, function(){
                if(data.kota) loadR('dw_fetch_districts', data.kota, els.kec, data.kec, function(){
                    if(data.kec) loadR('dw_fetch_villages', data.kec, els.desa, data.desa);
                });
            });
        });
    } else {
        loadR('dw_fetch_provinces', null, els.prov, null);
    }

    els.prov.change(function(){ setText(this,'#input_provinsi_nama'); loadR('dw_fetch_regencies', $(this).val(), els.kota, null); els.kota.val(''); els.kec.empty().prop('disabled',true); els.desa.empty().prop('disabled',true); });
    els.kota.change(function(){ setText(this,'#input_kabupaten_name'); loadR('dw_fetch_districts', $(this).val(), els.kec, null); els.kec.val(''); els.desa.empty().prop('disabled',true); });
    els.kec.change(function(){ setText(this,'#input_kecamatan_name'); loadR('dw_fetch_villages', $(this).val(), els.desa, null); });
    els.desa.change(function(){ setText(this,'#input_kelurahan_name'); });
});

// Init Tab
document.addEventListener('DOMContentLoaded', () => { 
    const p = new URLSearchParams(window.location.search);
    const tab = p.get('tab') || 'ringkasan'; 
    switchTab(tab); 
});
</script>

<?php get_footer(); ?>