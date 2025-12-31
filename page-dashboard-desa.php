<?php
/**
 * Template Name: Dashboard Desa
 * Description: Controller Utama Dashboard Desa (Modular Layout).
 * Status: FINAL FIX (Sidebar Terpisah + Responsif Header).
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

// --- AUTO-CREATE (Jika data desa belum ada) ---
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
$msg = ''; $msg_type = '';

// --- LOGIC PHP (Handler Form) ---
// 1. Profil & ID Gen
if ( isset($_POST['save_profil_desa']) && check_admin_referer('save_profil_desa_action', 'profil_desa_nonce') ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' ); require_once( ABSPATH . 'wp-admin/includes/image.php' ); require_once( ABSPATH . 'wp-admin/includes/media.php' );
    $prov_txt = sanitize_text_field($_POST['provinsi_nama']); $kab_txt = sanitize_text_field($_POST['kabupaten_nama']); $kec_txt = sanitize_text_field($_POST['kecamatan_nama']); $kel_txt = sanitize_text_field($_POST['kelurahan_nama']);
    
    $kode_ref = $desa_data->kode_referral;
    if ( empty($kode_ref) && !empty($prov_txt) && !empty($kab_txt) && !empty($kel_txt) ) {
        $get_code = function($t){ $c=trim(strtolower($t)); $c=preg_replace('/^(provinsi|kabupaten|kota|desa|kelurahan)\s+/','',$c); if($c=='jawa barat')return 'JAB'; if($c=='jawa tengah')return 'JTG'; if($c=='jawa timur')return 'JTM'; if(strpos($c,'jakarta')!==false)return 'DKI'; if(strpos($c,'yogyakarta')!==false)return 'DIY'; return strtoupper(substr(str_replace(' ','',$c),0,3)); };
        $c_prov=$get_code($prov_txt); $c_kab=$get_code($kab_txt); $c_des=$get_code($kel_txt); $rand=rand(1000,9999); $kode_ref="$c_prov-$c_kab-$c_des-$rand";
        while($wpdb->get_var($wpdb->prepare("SELECT id FROM $table_desa WHERE kode_referral=%s AND id!=%d",$kode_ref,$id_desa))){ $rand=rand(1000,9999); $kode_ref="$c_prov-$c_kab-$c_des-$rand"; }
    }
    $upd = ['nama_desa'=>sanitize_text_field($_POST['nama_desa']),'deskripsi'=>wp_kses_post($_POST['deskripsi']),'alamat_lengkap'=>sanitize_textarea_field($_POST['alamat_lengkap']),'kode_referral'=>$kode_ref,'nama_bank_desa'=>sanitize_text_field($_POST['nama_bank_desa']),'no_rekening_desa'=>sanitize_text_field($_POST['no_rekening_desa']),'atas_nama_rekening_desa'=>sanitize_text_field($_POST['atas_nama_rekening_desa']),'api_provinsi_id'=>sanitize_text_field($_POST['api_provinsi_id']),'api_kabupaten_id'=>sanitize_text_field($_POST['api_kabupaten_id']),'api_kecamatan_id'=>sanitize_text_field($_POST['api_kecamatan_id']),'api_kelurahan_id'=>sanitize_text_field($_POST['api_kelurahan_id']),'provinsi'=>$prov_txt,'kabupaten'=>$kab_txt,'kecamatan'=>$kec_txt,'kelurahan'=>$kel_txt,'updated_at'=>current_time('mysql')];
    foreach(['foto_desa'=>'foto','foto_sampul'=>'foto_sampul','qris_desa'=>'qris_image_url_desa'] as $i=>$c){ if(!empty($_FILES[$i]['name'])){ $u=wp_handle_upload($_FILES[$i],['test_form'=>false]); if(isset($u['url']))$upd[$c]=$u['url']; } }
    $wpdb->update($table_desa, $upd, ['id'=>$id_desa]); $msg="Profil diperbarui."; $msg_type="success"; $desa_data=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE id=%d",$id_desa));
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

get_header(); // Memuat Header Utama Theme (Navbar)
?>

<!-- CDN Styles (Tailwind) -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- DASHBOARD WRAPPER -->
<!-- 
   pt-16 (Mobile) & md:pt-20 (Desktop): Memberi ruang agar konten tidak ketutup header.
   Header di file 'header.php' tema ini fixed heightnya 16 (mobile) dan 20 (desktop).
-->
<div class="min-h-screen bg-gray-50 flex flex-col md:flex-row pt-16 md:pt-20 relative">

    <!-- MOBILE DASHBOARD TOGGLE BAR -->
    <!-- Sticky bar khusus mobile, muncul tepat di bawah header utama (top-16) -->
    <div class="md:hidden bg-white border-b border-gray-200 px-4 py-3 flex justify-between items-center sticky top-16 z-30 shadow-sm">
        <span class="font-bold text-gray-700 flex items-center gap-2"><i class="fas fa-columns text-blue-600"></i> Menu Dashboard</span>
        <button onclick="toggleSidebar()" class="text-blue-600 focus:outline-none bg-blue-50 p-2 rounded-lg border border-blue-100">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- LOAD SIDEBAR (Modular) -->
    <?php get_template_part('template-parts/dashboard/sidebar', 'desa', [
        'desa_data' => $desa_data,
        'akses_premium' => $akses_premium
    ]); ?>

    <!-- CONTENT AREA -->
    <!-- md:ml-64: Memberi margin kiri desktop selebar sidebar agar tidak ketutup -->
    <main class="flex-1 p-4 md:p-8 md:ml-64 transition-all duration-300 min-h-[80vh]">
        
        <!-- Notifikasi Global -->
        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl <?php echo ($msg_type=='error')?'bg-red-50 text-red-700 border border-red-200':'bg-green-50 text-green-700 border border-green-200'; ?> flex items-center gap-3 shadow-sm animate-fade-in">
                <i class="fas <?php echo ($msg_type=='error')?'fa-exclamation-circle':'fa-check-circle'; ?>"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- A. RINGKASAN -->
        <div id="view-ringkasan" class="tab-content animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Ringkasan Statistik</h1>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition">
                    <p class="text-gray-500 text-sm font-medium mb-1">Total UMKM</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo number_format($total_umkm); ?></h3>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition">
                    <p class="text-gray-500 text-sm font-medium mb-1">Objek Wisata</p>
                    <h3 class="text-3xl font-bold text-gray-800"><?php echo number_format($total_wisata_count); ?></h3>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition">
                    <p class="text-gray-500 text-sm font-medium mb-1">Status Akun</p>
                    <h3 class="text-xl font-bold flex items-center gap-2 <?php echo $akses_premium?'text-green-600':'text-gray-600'; ?>">
                        <?php echo $akses_premium ? '<i class="fas fa-crown"></i> Premium' : '<i class="fas fa-clock"></i> Free Plan'; ?>
                    </h3>
                </div>
            </div>
        </div>

        <!-- B. VERIFIKASI UMKM -->
        <div id="view-verifikasi" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Verifikasi UMKM</h1>
            <?php if(!$akses_premium): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-8 text-center max-w-2xl mx-auto">
                    <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-blue-500 text-3xl mx-auto mb-4 shadow-sm"><i class="fas fa-lock"></i></div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">Fitur Premium Terkunci</h3>
                    <p class="text-gray-600 mb-6">Upgrade akun desa Anda untuk membuka fitur Verifikasi UMKM dan unlimited Wisata.</p>
                    
                    <?php if($status_verifikasi == 'pending'): ?>
                        <div class="bg-yellow-100 text-yellow-800 p-4 rounded-xl font-bold flex items-center justify-center gap-2">
                            <i class="fas fa-clock"></i> Bukti pembayaran sedang diverifikasi admin.
                        </div>
                    <?php else: ?>
                        <div class="bg-white p-6 rounded-xl shadow-sm text-left border border-gray-200">
                            <p class="mb-4 font-medium text-gray-700">Silakan transfer <strong>Rp <?php echo number_format($harga_premium, 0, ',', '.'); ?></strong> ke:</p>
                            <div class="bg-gray-100 p-3 rounded-lg font-mono text-sm mb-4 border border-gray-200">
                                <?php echo esc_html($sys_bank_name); ?><br>
                                <?php echo esc_html($sys_bank_account); ?><br>
                                a.n <?php echo esc_html($sys_bank_holder); ?>
                            </div>
                            <form method="POST" enctype="multipart/form-data">
                                <?php wp_nonce_field('upload_bukti_action', 'upload_bukti_nonce'); ?>
                                <input type="hidden" name="action_upload_bukti" value="1">
                                <label class="block mb-2 text-sm font-medium text-gray-900">Upload Bukti Transfer</label>
                                <input class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none mb-4" name="bukti_bayar" type="file" required>
                                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-lg text-sm px-5 py-3 transition">Kirim Bukti</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php $umkm_pending = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'menunggu_desa' ORDER BY created_at DESC", $id_desa)); ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50"><tr><th class="px-6 py-3">Toko</th><th class="px-6 py-3">Pemilik</th><th class="px-6 py-3 text-center">Aksi</th></tr></thead>
                        <tbody>
                        <?php if($umkm_pending): foreach($umkm_pending as $u): ?>
                            <tr class="bg-white border-b hover:bg-gray-50"><td class="px-6 py-4 font-bold text-gray-900"><?php echo esc_html($u->nama_toko); ?></td><td class="px-6 py-4"><?php echo esc_html($u->nama_pemilik); ?></td><td class="px-6 py-4 text-center"><form method="POST" class="inline-flex gap-2"><?php wp_nonce_field('verifikasi_pedagang_action', 'verifikasi_nonce'); ?><input type="hidden" name="id_pedagang" value="<?php echo $u->id; ?>"><button type="submit" name="action_verifikasi" value="1" onclick="this.form.status_keputusan.value='disetujui'" class="bg-green-100 text-green-700 px-3 py-1 rounded-md font-bold hover:bg-green-200 text-xs">Terima</button><button type="submit" name="action_verifikasi" value="1" onclick="this.form.status_keputusan.value='ditolak'" class="bg-red-100 text-red-700 px-3 py-1 rounded-md font-bold hover:bg-red-200 text-xs">Tolak</button><input type="hidden" name="status_keputusan" value=""></form></td></tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">Tidak ada antrean verifikasi saat ini.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- C. DATA UMKM -->
        <div id="view-data-umkm" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Data UMKM Aktif</h1>
            <?php $umkm_active = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'disetujui' ORDER BY created_at DESC", $id_desa)); ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <table class="w-full text-sm text-left text-gray-500">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50"><tr><th class="px-6 py-3">Info Toko</th><th class="px-6 py-3">Pemilik</th><th class="px-6 py-3 text-center">Status</th></tr></thead>
                    <tbody>
                    <?php if($umkm_active): foreach($umkm_active as $u): ?>
                        <tr class="bg-white border-b hover:bg-gray-50"><td class="px-6 py-4 font-bold text-gray-900"><?php echo esc_html($u->nama_toko); ?></td><td class="px-6 py-4"><?php echo esc_html($u->nama_pemilik); ?></td><td class="px-6 py-4 text-center"><span class="bg-green-100 text-green-800 text-xs font-bold px-2.5 py-0.5 rounded-full">Aktif</span></td></tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">Belum ada UMKM aktif.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- D. KELOLA WISATA -->
        <div id="view-wisata" class="tab-content hidden animate-fade-in">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <h1 class="text-2xl font-bold text-gray-800">Kelola Wisata</h1>
                <?php $limit_reached = (!$akses_premium && $total_wisata_count >= 2); ?>
                <?php if($limit_reached): ?>
                    <button onclick="alert('Kuota Penuh (Maks 2 Wisata untuk Akun Free). Silakan Upgrade ke Premium di menu Verifikasi UMKM.'); switchTab('verifikasi');" class="bg-yellow-500 hover:bg-yellow-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md transition flex items-center gap-2 active:scale-95 w-full md:w-auto justify-center">
                        <i class="fas fa-lock"></i> Kuota Penuh (Upgrade)
                    </button>
                <?php else: ?>
                    <button onclick="openWisataModalNew()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-md transition flex items-center gap-2 w-full md:w-auto justify-center">
                        <i class="fas fa-plus"></i> Tambah Wisata
                    </button>
                <?php endif; ?>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if($wisata_list): foreach($wisata_list as $index => $w): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition group">
                    <div class="h-48 bg-gray-100 relative">
                        <img class="w-full h-full object-cover group-hover:scale-105 transition duration-500" src="<?php echo !empty($w->foto_utama) ? esc_url($w->foto_utama) : 'https://placehold.co/400x250?text=Wisata'; ?>" alt="" />
                        <span class="absolute top-2 right-2 bg-white/90 backdrop-blur text-xs font-bold px-2 py-1 rounded text-gray-700 shadow-sm"><?php echo esc_html($w->kategori); ?></span>
                    </div>
                    <div class="p-5">
                        <h5 class="mb-2 text-lg font-bold text-gray-900 truncate"><?php echo esc_html($w->nama_wisata); ?></h5>
                        <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?php echo $w->deskripsi ? strip_tags($w->deskripsi) : '-'; ?></p>
                        <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
                            <span class="text-sm font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded"><?php echo ($w->harga_tiket>0)?'Rp '.number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?></span>
                            <div class="flex gap-2">
                                <button onclick="editWisata(<?php echo $index; ?>)" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200"><i class="fas fa-pen text-xs"></i></button>
                                <?php $del_nonce = wp_create_nonce('hapus_wisata_'.$w->id); ?>
                                <a href="<?php echo home_url('/dashboard-desa/?tab=wisata&action=hapus_wisata&id='.$w->id.'&_wpnonce='.$del_nonce); ?>" onclick="return confirm('Hapus wisata ini?')" class="w-8 h-8 flex items-center justify-center rounded-full bg-red-50 text-red-500 hover:bg-red-100"><i class="fas fa-trash text-xs"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="col-span-full text-center py-16 bg-white rounded-xl border border-dashed border-gray-300 text-gray-500">
                    <i class="far fa-map text-4xl mb-3 text-gray-300 block"></i>
                    Belum ada data wisata.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- E. PROFIL DESA -->
        <div id="view-profil" class="tab-content hidden animate-fade-in">
            <!-- View Mode -->
            <div id="profil-view-mode">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Profil Desa</h1>
                    <button onclick="toggleProfilMode('edit')" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow hover:bg-blue-700 transition"><i class="fas fa-edit mr-2"></i> Edit</button>
                </div>
                
                <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="md:flex">
                        <!-- Kolom Kiri: Sampul & Logo -->
                        <div class="md:w-1/3 bg-gray-50 p-6 border-r border-gray-100">
                            <div class="mb-6 text-center">
                                <img class="w-24 h-24 rounded-full object-cover mx-auto shadow-md border-4 border-white mb-3" src="<?php echo $desa_data->foto ? esc_url($desa_data->foto) : 'https://ui-avatars.com/api/?name='.urlencode($desa_data->nama_desa); ?>">
                                <h2 class="text-xl font-bold text-gray-900"><?php echo esc_html($desa_data->nama_desa); ?></h2>
                                <p class="text-sm text-gray-500"><?php echo esc_html($desa_data->kabupaten); ?></p>
                            </div>
                            
                            <div class="bg-white p-4 rounded-xl border border-gray-200 mb-4 text-center">
                                <p class="text-[10px] uppercase font-bold text-gray-400 mb-1">Kode Wilayah</p>
                                <?php if($desa_data->kode_referral): ?>
                                    <div class="text-xl font-mono font-bold text-blue-600 tracking-wider select-all cursor-pointer"><?php echo esc_html($desa_data->kode_referral); ?></div>
                                <?php else: ?>
                                    <div class="text-sm text-red-500 italic">Belum dibuat.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Kolom Kanan: Detail -->
                        <div class="md:w-2/3 p-6">
                            <div class="mb-6">
                                <h3 class="font-bold text-gray-800 mb-2 border-b pb-2">Deskripsi</h3>
                                <p class="text-gray-600 text-sm leading-relaxed"><?php echo nl2br(esc_html($desa_data->deskripsi ?: 'Belum ada deskripsi.')); ?></p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="font-bold text-gray-800 mb-2 border-b pb-2">Alamat</h3>
                                    <p class="text-sm text-gray-600"><?php echo esc_html($desa_data->alamat_lengkap ?: '-'); ?></p>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($desa_data->kelurahan.', '.$desa_data->kecamatan); ?></p>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800 mb-2 border-b pb-2">Rekening</h3>
                                    <p class="text-sm text-gray-600"><strong><?php echo esc_html($desa_data->nama_bank_desa ?: '-'); ?></strong></p>
                                    <p class="text-sm text-gray-600 font-mono"><?php echo esc_html($desa_data->no_rekening_desa ?: '-'); ?></p>
                                    <p class="text-xs text-gray-500">a.n <?php echo esc_html($desa_data->atas_nama_rekening_desa ?: '-'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Mode -->
            <div id="profil-edit-mode" class="hidden">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Edit Profil</h1>
                    <button onclick="toggleProfilMode('view')" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-xl text-sm font-bold hover:bg-gray-200 transition">Batal</button>
                </div>
                
                <form method="POST" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 md:p-8">
                    <?php wp_nonce_field('save_profil_desa_action', 'profil_desa_nonce'); ?>
                    <input type="hidden" name="save_profil_desa" value="1">
                    
                    <div class="grid gap-6 mb-6 md:grid-cols-2">
                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Nama Desa</label>
                            <input type="text" name="nama_desa" value="<?php echo esc_attr($desa_data->nama_desa); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        </div>
                        
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Logo Desa</label>
                            <input type="file" name="foto_desa" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50">
                        </div>
                        <div>
                            <label class="block mb-2 text-sm font-medium text-gray-900">Foto Sampul</label>
                            <input type="file" name="foto_sampul" class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block mb-2 text-sm font-medium text-gray-900">Deskripsi</label>
                            <textarea name="deskripsi" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"><?php echo esc_textarea($desa_data->deskripsi); ?></textarea>
                        </div>
                    </div>

                    <!-- Dropdown Wilayah -->
                    <div class="bg-blue-50 p-6 rounded-xl border border-blue-100 mb-6">
                        <h3 class="font-bold text-blue-900 mb-4 text-sm uppercase tracking-wide">Wilayah Administratif</h3>
                        <div id="region-data" data-prov="<?php echo esc_attr($desa_data->api_provinsi_id); ?>" data-kota="<?php echo esc_attr($desa_data->api_kabupaten_id); ?>" data-kec="<?php echo esc_attr($desa_data->api_kecamatan_id); ?>" data-desa="<?php echo esc_attr($desa_data->api_kelurahan_id); ?>"></div>
                        
                        <div class="grid gap-4 mb-4 md:grid-cols-2">
                            <div><label class="text-xs font-bold text-gray-500">Provinsi</label><select name="api_provinsi_id" id="dw_provinsi" class="w-full border rounded-lg p-2.5 bg-white"><option>Loading...</option></select></div>
                            <div><label class="text-xs font-bold text-gray-500">Kabupaten</label><select name="api_kabupaten_id" id="dw_kota" class="w-full border rounded-lg p-2.5 bg-white" disabled></select></div>
                            <div><label class="text-xs font-bold text-gray-500">Kecamatan</label><select name="api_kecamatan_id" id="dw_kecamatan" class="w-full border rounded-lg p-2.5 bg-white" disabled></select></div>
                            <div><label class="text-xs font-bold text-gray-500">Kelurahan</label><select name="api_kelurahan_id" id="dw_desa" class="w-full border rounded-lg p-2.5 bg-white" disabled></select></div>
                        </div>
                        
                        <input type="hidden" name="provinsi_nama" id="input_provinsi_nama" value="<?php echo esc_attr($desa_data->provinsi); ?>">
                        <input type="hidden" name="kabupaten_nama" id="input_kabupaten_name" value="<?php echo esc_attr($desa_data->kabupaten); ?>">
                        <input type="hidden" name="kecamatan_nama" id="input_kecamatan_name" value="<?php echo esc_attr($desa_data->kecamatan); ?>">
                        <input type="hidden" name="kelurahan_nama" id="input_kelurahan_name" value="<?php echo esc_attr($desa_data->kelurahan); ?>">
                        
                        <div><label class="text-xs font-bold text-gray-500">Alamat Lengkap</label><textarea name="alamat_lengkap" rows="2" class="w-full border rounded-lg p-2.5 bg-white"><?php echo esc_textarea($desa_data->alamat_lengkap); ?></textarea></div>
                    </div>
                    
                    <!-- Bank Info -->
                    <div class="grid gap-4 md:grid-cols-3 mb-6">
                        <div><label class="text-xs font-bold text-gray-500">Nama Bank</label><input type="text" name="nama_bank_desa" value="<?php echo esc_attr($desa_data->nama_bank_desa); ?>" class="w-full border rounded-lg p-2.5"></div>
                        <div><label class="text-xs font-bold text-gray-500">No. Rekening</label><input type="text" name="no_rekening_desa" value="<?php echo esc_attr($desa_data->no_rekening_desa); ?>" class="w-full border rounded-lg p-2.5"></div>
                        <div><label class="text-xs font-bold text-gray-500">Atas Nama</label><input type="text" name="atas_nama_rekening_desa" value="<?php echo esc_attr($desa_data->atas_nama_rekening_desa); ?>" class="w-full border rounded-lg p-2.5"></div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-lg text-sm px-8 py-3 shadow-lg transition">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<!-- MODAL WISATA -->
<div id="modal-wisata" class="fixed inset-0 z-50 hidden flex justify-center items-center bg-black/50 backdrop-blur-sm p-4">
    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl h-auto max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center p-5 border-b border-gray-100">
            <h3 class="text-xl font-bold text-gray-900" id="mw_title">Tambah Wisata</h3>
            <button type="button" onclick="closeWisataModal()" class="text-gray-400 hover:text-gray-600 bg-gray-100 rounded-full p-2 hover:bg-gray-200 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto">
            <form method="POST" enctype="multipart/form-data" id="form-wisata">
                <?php wp_nonce_field('save_wisata_action', 'wisata_nonce'); ?>
                <input type="hidden" name="save_wisata" value="1">
                <input type="hidden" name="wisata_id" id="mw_id" value=""> 
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">Nama Wisata</label>
                        <input type="text" name="nama_wisata" id="mw_nama" class="w-full border border-gray-300 rounded-lg p-2.5" required>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">Kategori</label>
                        <select name="kategori" id="mw_kategori" class="w-full border border-gray-300 rounded-lg p-2.5">
                            <?php foreach($kategori_wisata as $k) echo "<option value='$k'>$k</option>"; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">Harga Tiket</label>
                        <input type="number" name="harga_tiket" id="mw_harga" class="w-full border border-gray-300 rounded-lg p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">Jam Buka</label>
                        <input type="text" name="jam_buka" id="mw_jam" class="w-full border border-gray-300 rounded-lg p-2.5">
                    </div>
                    <div>
                        <label class="block mb-2 text-sm font-bold text-gray-700">Kontak Pengelola</label>
                        <input type="text" name="kontak_pengelola" id="mw_kontak" class="w-full border border-gray-300 rounded-lg p-2.5">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">Deskripsi</label>
                        <textarea name="deskripsi" id="mw_deskripsi" rows="3" class="w-full border border-gray-300 rounded-lg p-2.5"></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">Foto Utama</label>
                        <input type="file" name="foto_utama" class="w-full border border-gray-300 rounded-lg p-2 text-sm">
                        <div id="mw_preview_box" class="hidden mt-3 p-2 bg-gray-50 rounded border border-gray-200">
                            <p class="text-xs text-gray-500 mb-1">Foto saat ini:</p>
                            <img id="mw_preview_img" src="" class="h-24 rounded object-cover">
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block mb-2 text-sm font-bold text-gray-700">Link Google Maps</label>
                        <input type="url" name="lokasi_maps" id="mw_maps" class="w-full border border-gray-300 rounded-lg p-2.5">
                    </div>
                </div>
                <div class="flex justify-end pt-4 border-t border-gray-100">
                    <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-lg text-sm px-6 py-3 transition">Simpan Wisata</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS VARIABLES -->
<script>
    var wisataData = <?php echo json_encode($wisata_list); ?>;
    var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
</script>

<script>
// Toggle Sidebar (Mobile)
function toggleSidebar() {
    const sidebar = document.getElementById('dashboard-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    if (sidebar.classList.contains('-translate-x-full')) {
        sidebar.classList.remove('-translate-x-full');
        if(backdrop) {
            backdrop.classList.remove('hidden');
            setTimeout(() => backdrop.classList.remove('opacity-0'), 10);
        }
    } else {
        sidebar.classList.add('-translate-x-full');
        if(backdrop) {
            backdrop.classList.add('opacity-0');
            setTimeout(() => backdrop.classList.add('hidden'), 300);
        }
    }
}

// Tab Switching & Sidebar Active State
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(el => {
        el.classList.remove('bg-blue-50', 'text-blue-600');
        el.classList.add('text-gray-600', 'hover:bg-gray-100');
    });
    
    const target = document.getElementById('view-' + tabName);
    const nav = document.getElementById('nav-' + tabName);
    
    if(target) target.classList.remove('hidden');
    if(nav) {
        nav.classList.remove('text-gray-600', 'hover:bg-gray-100');
        nav.classList.add('bg-blue-50', 'text-blue-600');
    }
    
    // Auto-close sidebar on mobile
    if(window.innerWidth < 768) {
        const sidebar = document.getElementById('dashboard-sidebar');
        if(!sidebar.classList.contains('-translate-x-full')) {
            toggleSidebar();
        }
    }
}

// Profil Mode
function toggleProfilMode(mode) {
    document.getElementById('profil-view-mode').classList.toggle('hidden', mode === 'edit');
    document.getElementById('profil-edit-mode').classList.toggle('hidden', mode !== 'edit');
}

// Modal Wisata Logic
function openWisataModalNew() {
    document.getElementById('form-wisata').reset();
    document.getElementById('mw_id').value = '';
    document.getElementById('mw_title').innerText = 'Tambah Wisata';
    document.getElementById('mw_preview_box').classList.add('hidden');
    document.getElementById('modal-wisata').classList.remove('hidden');
    document.getElementById('modal-wisata').classList.add('flex');
}

function editWisata(index) {
    var data = wisataData[index];
    document.getElementById('form-wisata').reset();
    document.getElementById('mw_id').value = data.id;
    document.getElementById('mw_nama').value = data.nama_wisata;
    document.getElementById('mw_kategori').value = data.kategori;
    document.getElementById('mw_harga').value = data.harga_tiket;
    document.getElementById('mw_jam').value = data.jam_buka;
    document.getElementById('mw_deskripsi').value = data.deskripsi;
    document.getElementById('mw_kontak').value = data.kontak_pengelola;
    document.getElementById('mw_maps').value = data.lokasi_maps;
    document.getElementById('mw_title').innerText = 'Edit Wisata';
    
    if(data.foto_utama) {
        document.getElementById('mw_preview_box').classList.remove('hidden');
        document.getElementById('mw_preview_img').src = data.foto_utama;
    } else {
        document.getElementById('mw_preview_box').classList.add('hidden');
    }
    document.getElementById('modal-wisata').classList.remove('hidden');
    document.getElementById('modal-wisata').classList.add('flex');
}

function closeWisataModal() {
    document.getElementById('modal-wisata').classList.add('hidden');
    document.getElementById('modal-wisata').classList.remove('flex');
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'ringkasan';
    switchTab(tab);
});

// Region Logic (jQuery)
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
</script>

<style>
    .animate-fade-in { animation: fadeIn 0.3s ease-out forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .active-tab { background-color: #eff6ff; color: #2563eb; border-left: 4px solid #2563eb; }
</style>

<?php get_footer(); ?>