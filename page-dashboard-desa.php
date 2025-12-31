<?php
/**
 * Template Name: Dashboard Desa
 * Description: Panel Frontend Desa Lengkap (CRUD Stabil + Fitur Lengkap + ID Generator).
 * Status: FINAL FIX (Layout Responsif - Sidebar Terpisah).
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

// --- LOGIC PHP UTAMA (CRUD & Handler) ---
// 1. Simpan Profil & Generate ID
if ( isset($_POST['save_profil_desa']) && check_admin_referer('save_profil_desa_action', 'profil_desa_nonce') ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' ); require_once( ABSPATH . 'wp-admin/includes/image.php' ); require_once( ABSPATH . 'wp-admin/includes/media.php' );
    $prov_txt = sanitize_text_field($_POST['provinsi_nama']); $kab_txt  = sanitize_text_field($_POST['kabupaten_nama']); $kec_txt  = sanitize_text_field($_POST['kecamatan_nama']); $kel_txt  = sanitize_text_field($_POST['kelurahan_nama']);

    // Generator ID Wilayah
    $kode_referral_save = $desa_data->kode_referral;
    if ( empty($kode_referral_save) && !empty($prov_txt) && !empty($kab_txt) && !empty($kel_txt) ) {
        $get_code = function($text){ 
            $c=trim(strtolower($text)); $c=preg_replace('/^(provinsi|kabupaten|kota|desa|kelurahan)\s+/','',$c);
            if($c=='jawa barat')return 'JAB'; if($c=='jawa tengah')return 'JTG'; if($c=='jawa timur')return 'JTM'; if(strpos($c,'jakarta')!==false)return 'DKI'; if(strpos($c,'yogyakarta')!==false)return 'DIY';
            return strtoupper(substr(str_replace(' ','',$c),0,3));
        };
        $c_prov=$get_code($prov_txt); $c_kab=$get_code($kab_txt); $c_des=$get_code($kel_txt); $rand=rand(1000,9999); 
        $calon_kode = "$c_prov-$c_kab-$c_des-$rand";
        while($wpdb->get_var($wpdb->prepare("SELECT id FROM $table_desa WHERE kode_referral=%s AND id!=%d",$calon_kode,$id_desa))){ $rand=rand(1000,9999); $calon_kode="$c_prov-$c_kab-$c_des-$rand"; }
        $kode_referral_save = $calon_kode;
    }

    $update_desa = [
        'nama_desa'=>sanitize_text_field($_POST['nama_desa']), 'deskripsi'=>wp_kses_post($_POST['deskripsi']), 'alamat_lengkap'=>sanitize_textarea_field($_POST['alamat_lengkap']), 'kode_referral'=>$kode_referral_save,
        'nama_bank_desa'=>sanitize_text_field($_POST['nama_bank_desa']), 'no_rekening_desa'=>sanitize_text_field($_POST['no_rekening_desa']), 'atas_nama_rekening_desa'=>sanitize_text_field($_POST['atas_nama_rekening_desa']),
        'api_provinsi_id'=>sanitize_text_field($_POST['api_provinsi_id']), 'api_kabupaten_id'=>sanitize_text_field($_POST['api_kabupaten_id']), 'api_kecamatan_id'=>sanitize_text_field($_POST['api_kecamatan_id']), 'api_kelurahan_id'=>sanitize_text_field($_POST['api_kelurahan_id']),
        'provinsi'=>$prov_txt, 'kabupaten'=>$kab_txt, 'kecamatan'=>$kec_txt, 'kelurahan'=>$kel_txt, 'updated_at'=>current_time('mysql')
    ];
    $files_map = ['foto_desa'=>'foto','foto_sampul'=>'foto_sampul','qris_desa'=>'qris_image_url_desa'];
    foreach($files_map as $i=>$c){ if(!empty($_FILES[$i]['name'])){ $u=wp_handle_upload($_FILES[$i],['test_form'=>false]); if(isset($u['url']))$update_desa[$c]=$u['url']; } }
    
    $wpdb->update($table_desa, $update_desa, ['id'=>$id_desa]); $msg="Profil diperbarui."; $msg_type="success"; $desa_data=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE id=%d",$id_desa));
}

// 2. Upload Bukti
if ( isset($_POST['action_upload_bukti']) && check_admin_referer('upload_bukti_action', 'upload_bukti_nonce') ) {
    if(!function_exists('wp_handle_upload')) require_once(ABSPATH.'wp-admin/includes/file.php');
    $u=$_FILES['bukti_bayar']; if(!empty($u['name'])){ $m=wp_handle_upload($u,['test_form'=>false]); if($m&&!isset($m['error'])){ $wpdb->update($table_desa,['bukti_bayar_akses'=>$m['url'],'status_akses_verifikasi'=>'pending','updated_at'=>current_time('mysql')],['id'=>$id_desa]); $msg="Bukti terkirim."; $msg_type="success"; $status_verifikasi='pending'; } }
}

// 3. Verifikasi UMKM
if ( isset($_POST['action_verifikasi']) && check_admin_referer('verifikasi_pedagang_action', 'verifikasi_nonce') ) {
    if(!$akses_premium){ $msg="Fitur Premium."; $msg_type="error"; } else {
        $st=sanitize_text_field($_POST['status_keputusan']); $upd=['status_pendaftaran'=>$st,'approved_by'=>'desa','updated_at'=>current_time('mysql')];
        if($st=='disetujui'){ $upd['status_akun']='aktif'; $opt=get_option('dw_settings'); $upd['sisa_transaksi']=isset($opt['kuota_gratis_default'])?absint($opt['kuota_gratis_default']):0; } else { $upd['status_akun']='nonaktif'; }
        $wpdb->update($table_pedagang, $upd, ['id'=>intval($_POST['id_pedagang'])]); $msg="Status diperbarui."; $msg_type="success";
    }
}

// 4. Save Wisata
if ( isset($_POST['save_wisata']) && check_admin_referer('save_wisata_action', 'wisata_nonce') ) {
    require_once(ABSPATH.'wp-admin/includes/file.php'); require_once(ABSPATH.'wp-admin/includes/image.php'); require_once(ABSPATH.'wp-admin/includes/media.php');
    $wid=isset($_POST['wisata_id'])&&is_numeric($_POST['wisata_id'])?intval($_POST['wisata_id']):0;
    if(!$akses_premium&&$wid===0){ $cnt=$wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_wisata WHERE id_desa=%d",$id_desa)); if($cnt>=2){ $msg="Kuota Penuh."; $msg_type="error"; } }
    if(empty($msg)){
        $dt=['id_desa'=>$id_desa,'nama_wisata'=>sanitize_text_field($_POST['nama_wisata']),'kategori'=>sanitize_text_field($_POST['kategori']),'deskripsi'=>wp_kses_post($_POST['deskripsi']),'harga_tiket'=>floatval($_POST['harga_tiket']),'jam_buka'=>sanitize_text_field($_POST['jam_buka']),'fasilitas'=>sanitize_textarea_field($_POST['fasilitas']),'kontak_pengelola'=>sanitize_text_field($_POST['kontak_pengelola']),'lokasi_maps'=>esc_url_raw($_POST['lokasi_maps']),'updated_at'=>current_time('mysql')];
        if(!empty($_FILES['foto_utama']['name'])){ $u=wp_handle_upload($_FILES['foto_utama'],['test_form'=>false]); if(isset($u['url'])) $dt['foto_utama']=$u['url']; }
        if($wid>0){ $wpdb->update($table_wisata,$dt,['id'=>$wid,'id_desa'=>$id_desa]); $msg="Update berhasil."; $msg_type="success"; }
        else{ $dt['slug']=sanitize_title($_POST['nama_wisata']).'-'.rand(100,999); $dt['created_at']=current_time('mysql'); $dt['status']='aktif'; $wpdb->insert($table_wisata,$dt); $msg="Wisata ditambah."; $msg_type="success"; }
    }
}

// 5. Delete Wisata
if ( isset($_GET['action']) && $_GET['action'] == 'hapus_wisata' && isset($_GET['id']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'hapus_wisata_'.$_GET['id']) ) {
    $wpdb->delete($table_wisata, ['id' => intval($_GET['id']), 'id_desa' => $id_desa]); wp_redirect( home_url('/dashboard-desa/?tab=wisata') ); exit;
}

// Data Fetching
$wisata_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE id_desa = %d AND status = 'aktif' ORDER BY created_at DESC", $id_desa));
$total_umkm = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_pedagang WHERE id_desa = %d AND status_akun = 'aktif'", $id_desa));
$total_wisata_count = count($wisata_list);
$kategori_wisata = ['Wisata Alam', 'Wisata Bahari', 'Wisata Budaya', 'Wisata Sejarah', 'Spot Foto', 'Camping Ground', 'Edukasi'];
$settings = get_option('dw_settings', []); 
$harga_premium = isset($settings['harga_premium_desa']) ? $settings['harga_premium_desa'] : 0;
$sys_bank_name = get_option('dw_bank_name', '-'); 
$sys_bank_account = get_option('dw_bank_account', '-'); 
$sys_bank_holder = get_option('dw_bank_holder', '-'); 

get_header(); // Memuat Header Website
?>

<!-- CDN Tailwind & FontAwesome -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- WRAPPER UTAMA (Responsive Positioning) -->
<!-- pt-16 (Mobile) & md:pt-20 (Desktop) memberi ruang untuk Header Utama (Navbar) agar tidak tertutup -->
<div class="bg-gray-50 min-h-screen font-sans flex flex-col md:flex-row pt-16 md:pt-20 relative">

    <!-- MOBILE MENU TOGGLE (Khusus Mobile) -->
    <!-- Muncul tepat di bawah header utama (top-16) -->
    <div class="md:hidden bg-white border-b border-gray-200 px-4 py-3 flex justify-between items-center sticky top-16 z-30 shadow-sm">
        <span class="font-bold text-gray-700 flex items-center gap-2">
            <i class="fas fa-columns text-blue-600"></i> Menu Dashboard
        </span>
        <button onclick="toggleSidebar()" class="text-gray-600 p-2 bg-gray-100 rounded-lg hover:bg-gray-200">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- PANGGIL SIDEBAR (File Terpisah) -->
    <?php get_template_part('template-parts/dashboard/sidebar', 'desa', [
        'desa_data' => $desa_data,
        'akses_premium' => $akses_premium
    ]); ?>

    <!-- MAIN CONTENT -->
    <!-- md:ml-64 memberi margin kiri selebar sidebar pada desktop -->
    <main class="flex-1 md:ml-64 p-4 md:p-8 transition-all duration-300 min-h-[80vh]">
        
        <!-- Notifikasi -->
        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl <?php echo ($msg_type=='error')?'bg-red-50 text-red-700 border-red-200':'bg-green-50 text-green-700 border-green-200'; ?> border flex items-center gap-3 shadow-sm animate-fade-in">
                <i class="fas <?php echo ($msg_type=='error')?'fa-exclamation-circle':'fa-check-circle'; ?>"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- VIEW: RINGKASAN -->
        <div id="view-ringkasan" class="tab-content animate-fade-in">
            <header class="mb-8"><h1 class="text-2xl font-bold text-gray-800">Ringkasan Desa</h1><p class="text-gray-500 text-sm">Statistik perkembangan.</p></header>
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

        <!-- VIEW: VERIFIKASI -->
        <div id="view-verifikasi" class="tab-content hidden animate-fade-in">
            <header class="mb-8"><h1 class="text-2xl font-bold text-gray-800">Verifikasi UMKM</h1><p class="text-gray-500 text-sm">Validasi pendaftaran pedagang.</p></header>
            <?php if ( ! $akses_premium ): ?>
                <div class="bg-white rounded-2xl border border-gray-200 shadow-lg p-8 text-center max-w-2xl mx-auto">
                    <div class="bg-gradient-to-r from-gray-800 to-gray-900 p-8 text-white rounded-xl mb-8">
                        <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-lock text-3xl"></i></div>
                        <h2 class="text-2xl font-bold mb-2">Fitur Terkunci</h2>
                        <p class="text-gray-300 text-sm">Upgrade ke Premium untuk membuka fitur Verifikasi UMKM.</p>
                    </div>
                    <?php if ($status_verifikasi == 'pending'): ?>
                        <div class="text-center p-4 bg-yellow-50 rounded-xl text-yellow-800 font-bold"><i class="fas fa-clock"></i> Bukti pembayaran sedang diverifikasi admin.</div>
                    <?php else: ?>
                        <div class="bg-gray-50 p-6 rounded-xl border text-left">
                            <h3 class="font-bold mb-4">Upload Bukti Pembayaran (Rp <?php echo number_format($harga_premium, 0, ',', '.'); ?>)</h3>
                            <div class="mb-4 text-sm bg-white p-3 rounded border">
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

        <!-- VIEW: DATA UMKM -->
        <div id="view-data-umkm" class="tab-content hidden animate-fade-in">
            <header class="mb-8 flex justify-between items-center">
                <div><h1 class="text-2xl font-bold text-gray-800">Data UMKM</h1><p class="text-gray-500 text-sm">Daftar pedagang aktif.</p></div>
                <div class="text-sm bg-blue-50 text-blue-700 px-4 py-2 rounded-lg border border-blue-100">Total: <strong><?php echo count($umkm_active ?? []); ?></strong> Mitra</div>
            </header>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <?php if($umkm_active): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs border-b border-gray-100"><tr><th class="px-6 py-4">Info Toko</th><th class="px-6 py-4">Pemilik & Lokasi</th><th class="px-6 py-4">Statistik</th><th class="px-6 py-4">Kontak</th><th class="px-6 py-4 text-center">Status</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                        <?php foreach($umkm_active as $u): $wa_link = 'https://wa.me/' . preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $u->nomor_wa)); $foto_url = !empty($u->foto_profil) ? esc_url($u->foto_profil) : 'https://ui-avatars.com/api/?name='.urlencode($u->nama_toko).'&background=random'; ?>
                        <tr class="hover:bg-gray-50 transition group">
                            <td class="px-6 py-4"><div class="flex items-center gap-4"><img src="<?php echo $foto_url; ?>" class="w-12 h-12 rounded-xl object-cover border border-gray-200 shadow-sm"><div><div class="font-bold text-gray-800 text-base mb-0.5"><?php echo esc_html($u->nama_toko); ?></div><div class="text-xs text-gray-500">Bergabung: <?php echo date('d M Y', strtotime($u->created_at)); ?></div></div></div></td>
                            <td class="px-6 py-4"><div class="font-medium text-gray-700 mb-1"><i class="fas fa-user-circle text-gray-400 mr-1"></i> <?php echo esc_html($u->nama_pemilik); ?></div><div class="text-xs text-gray-500 line-clamp-2 w-48" title="<?php echo esc_attr($u->alamat_lengkap); ?>"><i class="fas fa-map-marker-alt text-red-400 mr-1"></i> <?php echo esc_html($u->alamat_lengkap ?: 'Alamat belum diisi'); ?></div></td>
                            <td class="px-6 py-4"><div class="flex flex-col gap-1"><span class="text-xs font-medium text-gray-600 flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i> Rating: <strong><?php echo number_format($u->rating_toko, 1); ?></strong></span><span class="text-xs font-medium text-gray-600 flex items-center gap-1"><i class="fas fa-receipt text-blue-400"></i> Kuota: <strong><?php echo number_format($u->sisa_transaksi); ?></strong></span></div></td>
                            <td class="px-6 py-4"><a href="<?php echo $wa_link; ?>" target="_blank" class="inline-flex items-center gap-2 text-green-600 hover:text-green-700 bg-green-50 hover:bg-green-100 px-3 py-1.5 rounded-lg text-xs font-bold transition"><i class="fab fa-whatsapp text-lg"></i> Chat</a></td>
                            <td class="px-6 py-4 text-center"><span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold <?php echo ($u->status_akun=='aktif')?'bg-green-100 text-green-700':'bg-red-100 text-red-700'; ?>"><span class="w-1.5 h-1.5 rounded-full <?php echo ($u->status_akun=='aktif')?'bg-green-500':'bg-red-500'; ?>"></span> <?php echo ucfirst($u->status_akun); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?><div class="p-16 text-center text-gray-400"><div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-store-slash"></i></div><h3 class="text-gray-800 font-bold text-lg mb-1">Belum ada data</h3><p>Belum ada UMKM yang terverifikasi.</p></div><?php endif; ?>
            </div>
        </div>

        <!-- VIEW: KELOLA WISATA -->
        <div id="view-wisata" class="tab-content hidden animate-fade-in">
            <header class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
                <div><h1 class="text-2xl font-bold text-gray-800">Objek Wisata</h1><p class="text-gray-500 text-sm">Kelola destinasi wisata.</p></div>
                <?php $limit_reached = (!$akses_premium && $total_wisata_count >= 2); ?>
                <?php if($limit_reached): ?>
                    <button onclick="alert('Kuota Penuh! Upgrade ke Premium agar bisa tambahkan wisata.'); switchTab('verifikasi');" class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg transition flex items-center gap-2"><i class="fas fa-lock"></i> Tambah Wisata</button>
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
                                <button type="button" onclick="editWisata(<?php echo $index; ?>)" class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition"><i class="fas fa-pen text-xs"></i></button>
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

        <!-- VIEW: PROFIL DESA -->
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
                            <img src="<?php echo $desa_data->foto_sampul ? esc_url($desa_data->foto_sampul) : 'https://placehold.co/400x300?text=Cover'; ?>" class="w-full h-48 object-cover rounded-xl mb-4 shadow-sm border border-gray-100">
                            <div class="flex items-center gap-4 mb-4">
                                <img src="<?php echo $desa_data->foto ? esc_url($desa_data->foto) : 'https://placehold.co/100x100?text=Logo'; ?>" class="w-16 h-16 rounded-full border border-gray-200 object-cover shadow-sm">
                                <div><h2 class="text-xl font-bold text-gray-800 leading-tight"><?php echo esc_html($desa_data->nama_desa); ?></h2><p class="text-gray-500 text-xs"><?php echo esc_html($desa_data->kabupaten); ?></p></div>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 text-center mb-4">
                                <p class="text-xs font-bold text-blue-500 uppercase mb-1">Kode ID Wilayah</p>
                                <?php if($desa_data->kode_referral): ?>
                                    <div class="text-xl font-mono font-bold text-blue-800 tracking-wider"><?php echo esc_html($desa_data->kode_referral); ?></div>
                                <?php else: ?>
                                    <div class="text-sm text-red-500 italic">Belum dibuat. Silakan lengkapi wilayah.</div>
                                <?php endif; ?>
                            </div>
                            <?php if($desa_data->qris_image_url_desa): ?>
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-center"><p class="text-xs font-bold text-gray-500 uppercase mb-2">QRIS Desa</p><img src="<?php echo esc_url($desa_data->qris_image_url_desa); ?>" class="w-32 h-32 object-contain mx-auto mix-blend-multiply"></div>
                            <?php endif; ?>
                        </div>
                        <div class="w-full md:w-2/3 space-y-6">
                            <div class="bg-gray-50 p-6 rounded-xl border border-gray-100"><label class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2 block">Deskripsi Desa</label><p class="text-gray-700 leading-relaxed text-sm"><?php echo nl2br(esc_html($desa_data->deskripsi ?: 'Belum ada deskripsi.')); ?></p></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-blue-50 p-5 rounded-xl border border-blue-100"><h3 class="font-bold text-blue-800 mb-3 flex items-center gap-2"><i class="fas fa-wallet"></i> Rekening Desa</h3><div class="space-y-2 text-sm text-blue-900"><div class="flex justify-between border-b border-blue-200 pb-1"><span>Bank:</span> <strong><?php echo esc_html($desa_data->nama_bank_desa ?: '-'); ?></strong></div><div class="flex justify-between border-b border-blue-200 pb-1"><span>No. Rek:</span> <strong class="font-mono"><?php echo esc_html($desa_data->no_rekening_desa ?: '-'); ?></strong></div><div class="flex justify-between"><span>A.N:</span> <strong><?php echo esc_html($desa_data->atas_nama_rekening_desa ?: '-'); ?></strong></div></div></div>
                                <div class="bg-green-50 p-5 rounded-xl border border-green-100"><h3 class="font-bold text-green-800 mb-3 flex items-center gap-2"><i class="fas fa-map-marker-alt"></i> Lokasi</h3><div class="space-y-1 text-sm text-green-900"><p><?php echo esc_html($desa_data->alamat_lengkap ?: '-'); ?></p><p class="text-xs mt-2 opacity-75"><?php echo esc_html($desa_data->kelurahan); ?>, <?php echo esc_html($desa_data->kecamatan); ?></p><p class="text-xs opacity-75"><?php echo esc_html($desa_data->kabupaten); ?>, <?php echo esc_html($desa_data->provinsi); ?></p></div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Mode -->
            <div id="profil-edit-mode" class="hidden">
                <header class="mb-6 flex justify-between items-center"><h1 class="text-2xl font-bold text-gray-800">Edit Profil</h1><button onclick="toggleProfilMode('view')" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-xl text-sm font-bold shadow hover:bg-gray-300 transition">Batal</button></header>
                <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 space-y-8">
                    <?php wp_nonce_field('save_profil_desa_action', 'profil_desa_nonce'); ?>
                    <input type="hidden" name="save_profil_desa" value="1">
                    
                    <div class="grid md:grid-cols-3 gap-8">
                        <div class="space-y-4">
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-2">Logo Desa</label><div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:bg-gray-50 cursor-pointer relative group"><input type="file" name="foto_desa" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewImage(this, 'prev_logo')"><img id="prev_logo" src="<?php echo $desa_data->foto ? esc_url($desa_data->foto) : 'https://placehold.co/100x100?text=Logo'; ?>" class="w-20 h-20 mx-auto rounded-full object-cover mb-2"><p class="text-xs text-blue-500 font-bold group-hover:underline">Ganti Logo</p></div></div>
                            <div><label class="block text-xs font-bold text-gray-500 uppercase mb-2">QRIS (Opsional)</label><div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:bg-gray-50 cursor-pointer relative group"><input type="file" name="qris_desa" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewImage(this, 'prev_qris')"><img id="prev_qris" src="<?php echo $desa_data->qris_image_url_desa ? esc_url($desa_data->qris_image_url_desa) : 'https://placehold.co/100x100?text=QRIS'; ?>" class="w-20 h-20 mx-auto object-contain mb-2"><p class="text-xs text-blue-500 font-bold group-hover:underline">Upload QRIS</p></div></div>
                        </div>
                        <div class="md:col-span-2 space-y-6">
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Nama Desa</label><input type="text" name="nama_desa" value="<?php echo esc_attr($desa_data->nama_desa); ?>" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none"></div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Foto Sampul</label><input type="file" name="foto_sampul" class="w-full border border-gray-300 rounded-lg p-2 text-sm bg-gray-50"></div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi</label><textarea name="deskripsi" rows="4" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-500 outline-none"><?php echo esc_textarea($desa_data->deskripsi); ?></textarea></div>
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t border-gray-100">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Lokasi Wilayah</h3>
                        <div id="region-data" data-prov="<?php echo esc_attr($desa_data->api_provinsi_id); ?>" data-kota="<?php echo esc_attr($desa_data->api_kabupaten_id); ?>" data-kec="<?php echo esc_attr($desa_data->api_kecamatan_id); ?>" data-desa="<?php echo esc_attr($desa_data->api_kelurahan_id); ?>"></div>
                        <div class="grid grid-cols-2 gap-4 mb-4"><div><label class="text-xs font-bold block mb-1">Provinsi</label><select name="api_provinsi_id" id="dw_provinsi" class="w-full border rounded-lg p-2.5 bg-white"><option>Loading...</option></select></div><div><label class="text-xs font-bold block mb-1">Kabupaten</label><select name="api_kabupaten_id" id="dw_kota" class="w-full border rounded-lg p-2.5 bg-white" disabled></select></div></div>
                        <div class="grid grid-cols-2 gap-4 mb-4"><div><label class="text-xs font-bold block mb-1">Kecamatan</label><select name="api_kecamatan_id" id="dw_kecamatan" class="w-full border rounded-lg p-2.5 bg-white" disabled></select></div><div><label class="text-xs font-bold block mb-1">Kelurahan</label><select name="api_kelurahan_id" id="dw_desa" class="w-full border rounded-lg p-2.5 bg-white" disabled></select></div></div>
                        <input type="hidden" name="provinsi_nama" id="input_provinsi_nama" value="<?php echo esc_attr($desa_data->provinsi); ?>"><input type="hidden" name="kabupaten_nama" id="input_kabupaten_name" value="<?php echo esc_attr($desa_data->kabupaten); ?>"><input type="hidden" name="kecamatan_nama" id="input_kecamatan_name" value="<?php echo esc_attr($desa_data->kecamatan); ?>"><input type="hidden" name="kelurahan_nama" id="input_kelurahan_name" value="<?php echo esc_attr($desa_data->kelurahan); ?>">
                        <div><label class="text-sm font-bold block mb-1">Alamat Lengkap</label><textarea name="alamat_lengkap" rows="2" class="w-full border border-gray-300 rounded-lg p-2.5"><?php echo esc_textarea($desa_data->alamat_lengkap); ?></textarea></div>
                    </div>

                    <div class="pt-6 border-t border-gray-100"><h3 class="text-lg font-bold text-gray-800 mb-4">Rekening Penerimaan</h3><div class="grid md:grid-cols-3 gap-4"><div><label class="block text-xs font-bold uppercase text-gray-500 mb-1">Nama Bank</label><input type="text" name="nama_bank_desa" value="<?php echo esc_attr($desa_data->nama_bank_desa); ?>" class="w-full border rounded-lg p-2.5"></div><div><label class="block text-xs font-bold uppercase text-gray-500 mb-1">No. Rekening</label><input type="text" name="no_rekening_desa" value="<?php echo esc_attr($desa_data->no_rekening_desa); ?>" class="w-full border rounded-lg p-2.5 font-mono"></div><div><label class="block text-xs font-bold uppercase text-gray-500 mb-1">Atas Nama</label><input type="text" name="atas_nama_rekening_desa" value="<?php echo esc_attr($desa_data->atas_nama_rekening_desa); ?>" class="w-full border rounded-lg p-2.5"></div></div></div>
                    <div class="pt-6 text-right"><button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition flex items-center gap-2 ml-auto"><i class="fas fa-save"></i> Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    </main>
</div>

<!-- Modal Wisata & JS Scripts -->
<div id="modal-wisata" class="fixed inset-0 z-[60] hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeWisataModal()"></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-lg bg-white shadow-2xl transform transition-transform translate-x-full duration-300 flex flex-col" id="modal-wisata-panel">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white z-10"><h2 class="text-xl font-bold text-gray-800" id="mw_title">Tambah Wisata</h2><button onclick="closeWisataModal()" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times text-xl"></i></button></div>
        <div class="flex-1 overflow-y-auto p-6">
            <form method="POST" enctype="multipart/form-data" id="form-wisata">
                <?php wp_nonce_field('save_wisata_action', 'wisata_nonce'); ?><input type="hidden" name="save_wisata" value="1"><input type="hidden" name="wisata_id" id="mw_id" value="">
                <div class="space-y-4">
                    <div><label class="block text-sm font-bold mb-1">Nama Wisata</label><input type="text" name="nama_wisata" id="mw_nama" required class="w-full border rounded px-3 py-2"></div>
                    <div id="mw_preview_box" class="hidden mb-2"><label class="text-xs font-bold text-gray-500">Foto Saat Ini:</label><img id="mw_preview_img" src="" class="h-32 rounded border mt-1 object-cover w-full"></div>
                    <div><label class="block text-sm font-bold mb-1">Upload Foto</label><input type="file" name="foto_utama" class="w-full text-sm border rounded p-1"></div>
                    <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-bold mb-1">Kategori</label><select name="kategori" id="mw_kategori" class="w-full border rounded px-3 py-2"><?php foreach($kategori_wisata as $k) echo "<option value='$k'>$k</option>"; ?></select></div><div><label class="block text-sm font-bold mb-1">Harga (Rp)</label><input type="number" name="harga_tiket" id="mw_harga" class="w-full border rounded px-3 py-2"></div></div>
                    <div><label class="block text-sm font-bold mb-1">Jam Buka</label><input type="text" name="jam_buka" id="mw_jam" class="w-full border rounded px-3 py-2"></div>
                    <div><label class="block text-sm font-bold mb-1">Kontak</label><input type="text" name="kontak_pengelola" id="mw_kontak" class="w-full border rounded px-3 py-2"></div>
                    <div><label class="block text-sm font-bold mb-1">Deskripsi</label><textarea name="deskripsi" id="mw_deskripsi" rows="3" class="w-full border rounded px-3 py-2"></textarea></div>
                    <div><label class="block text-sm font-bold mb-1">Fasilitas</label><textarea name="fasilitas" id="mw_fasilitas" rows="2" class="w-full border rounded px-3 py-2"></textarea></div>
                    <div><label class="block text-sm font-bold mb-1">Link Maps</label><input type="url" name="lokasi_maps" id="mw_maps" class="w-full border rounded px-3 py-2"></div>
                </div>
                <div class="mt-6 pt-4 border-t"><button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 shadow-lg">Simpan Data</button></div>
            </form>
        </div>
    </div>
</div>

<style>
    .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .active-tab { background-color: #eff6ff; color: #2563eb; border-right: 3px solid #2563eb; }
</style>

<script>
    var wisataData = <?php echo json_encode($wisata_list); ?>;
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

    // Toggle Sidebar Function - Required for the Mobile Header Button and Backdrop
    function toggleSidebar() {
        const sidebar = document.getElementById('dashboard-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        if (sidebar && backdrop) {
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('opacity-0');
                setTimeout(() => backdrop.classList.add('hidden'), 300);
            }
        }
    }

    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active-tab'));
        const target = document.getElementById('view-' + tabName);
        const nav = document.getElementById('nav-' + tabName);
        if(target) target.classList.remove('hidden');
        if(nav) nav.classList.add('active-tab');
        if(window.innerWidth < 768) { // Auto close sidebar on mobile
             const sidebar = document.getElementById('dashboard-sidebar');
             if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
                 toggleSidebar();
             }
        }
    }

    function toggleProfilMode(mode) {
        document.getElementById('profil-view-mode').classList.toggle('hidden', mode === 'edit');
        document.getElementById('profil-edit-mode').classList.toggle('hidden', mode !== 'edit');
    }

    function previewImage(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { document.getElementById(previewId).src = e.target.result; }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Modal Logic
    const mw = document.getElementById('modal-wisata');
    const mp = document.getElementById('modal-wisata-panel');
    function openWisataModalNew() {
        resetForm();
        if(document.getElementById('mw_title')) document.getElementById('mw_title').innerText = 'Tambah Wisata';
        mw.classList.remove('hidden');
        setTimeout(() => mp.classList.remove('translate-x-full'), 10);
    }
    function editWisata(index) {
        resetForm();
        var data = wisataData[index];
        if(data) {
            const setVal = (id, val) => { const el = document.getElementById(id); if(el) el.value = val || ''; };
            if(document.getElementById('mw_title')) document.getElementById('mw_title').innerText = 'Edit Wisata';
            setVal('mw_id', data.id); setVal('mw_nama', data.nama_wisata); setVal('mw_kategori', data.kategori); setVal('mw_harga', data.harga_tiket); setVal('mw_jam', data.jam_buka); setVal('mw_kontak', data.kontak_pengelola); setVal('mw_deskripsi', data.deskripsi); setVal('mw_fasilitas', data.fasilitas); setVal('mw_maps', data.lokasi_maps);
            const previewBox = document.getElementById('mw_preview_box');
            const previewImg = document.getElementById('mw_preview_img');
            if(data.foto_utama && previewBox && previewImg) { previewBox.classList.remove('hidden'); previewImg.src = data.foto_utama; }
            mw.classList.remove('hidden'); setTimeout(() => mp.classList.remove('translate-x-full'), 10);
        }
    }
    function resetForm() {
        const form = document.getElementById('form-wisata'); if(form) form.reset();
        const idField = document.getElementById('mw_id'); if(idField) idField.value = ''; 
        const previewBox = document.getElementById('mw_preview_box'); if(previewBox) previewBox.classList.add('hidden');
    }
    function closeWisataModal() { mp.classList.add('translate-x-full'); setTimeout(() => mw.classList.add('hidden'), 300); }

    // Region Logic
    jQuery(document).ready(function($) {
        var els = { prov: $('#dw_provinsi'), kota: $('#dw_kota'), kec: $('#dw_kecamatan'), desa: $('#dw_desa') };
        var data = $('#region-data').data();
        function loadR(act, pid, el, sel, cb) {
            el.html('<option>Loading...</option>').prop('disabled', true);
            var p = { action: act };
            if(act=='dw_fetch_regencies') p.province_id = pid; if(act=='dw_fetch_districts') p.regency_id = pid; if(act=='dw_fetch_villages') p.district_id = pid;
            $.get(ajaxurl, p, function(res){
                if(res.success) {
                    var o = '<option value="">-- Pilih --</option>';
                    $.each(res.data.data||res.data, function(i,v){ var id=v.id||v.code; o+='<option value="'+id+'" '+(id==sel?'selected':'')+'>'+(v.name||v.nama)+'</option>'; });
                    el.html(o).prop('disabled', false); if(cb) cb();
                }
            });
        }
        function setText(el, target) { $(target).val($(el).find('option:selected').text()); }
        if(data.prov) {
            loadR('dw_fetch_provinces', null, els.prov, data.prov, function(){
                loadR('dw_fetch_regencies', data.prov, els.kota, data.kota, function(){
                    if(data.kota) loadR('dw_fetch_districts', data.kota, els.kec, data.kec, function(){
                        if(data.kec) loadR('dw_fetch_villages', data.kec, els.desa, data.desa);
                    });
                });
            });
        } else { loadR('dw_fetch_provinces', null, els.prov, null); }
        els.prov.change(function(){ setText(this,'#input_provinsi_nama'); loadR('dw_fetch_regencies', $(this).val(), els.kota, null); els.kota.val(''); els.kec.empty().prop('disabled',true); els.desa.empty().prop('disabled',true); });
        els.kota.change(function(){ setText(this,'#input_kabupaten_name'); loadR('dw_fetch_districts', $(this).val(), els.kec, null); els.kec.val(''); els.desa.empty().prop('disabled',true); });
        els.kec.change(function(){ setText(this,'#input_kecamatan_name'); loadR('dw_fetch_villages', $(this).val(), els.desa, null); });
        els.desa.change(function(){ setText(this,'#input_kelurahan_name'); });
    });

    document.addEventListener('DOMContentLoaded', () => { const p = new URLSearchParams(window.location.search); const tab = p.get('tab') || 'ringkasan'; switchTab(tab); });
</script>

<?php get_footer(); ?>