<?php
/**
 * Template Name: Dashboard Desa
 * Description: Panel Desa untuk Verifikasi UMKM & Kelola Wisata dengan Kategori Dinamis.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Cek Login & Role
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url() );
    exit;
}

$current_user_id = get_current_user_id();
global $wpdb;
$table_desa     = $wpdb->prefix . 'dw_desa';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_wisata   = $wpdb->prefix . 'dw_wisata';

// Ambil ID Desa dari User yang Login
$desa_data = $wpdb->get_row( $wpdb->prepare("SELECT id, nama_desa, api_kelurahan_id FROM $table_desa WHERE id_user_desa = %d", $current_user_id) );
$id_desa = $desa_data ? $desa_data->id : 0;

// --- LOGIC 1: VERIFIKASI UMKM ---
if ( isset($_POST['action_verifikasi']) && wp_verify_nonce($_POST['verifikasi_nonce'], 'verifikasi_pedagang_action') ) {
    $id_pedagang = intval($_POST['id_pedagang']);
    $status_baru = sanitize_text_field($_POST['status_keputusan']); // 'disetujui' atau 'ditolak'
    
    // Update status pedagang
    $wpdb->update(
        $table_pedagang, 
        [
            'status_pendaftaran' => $status_baru,
            'status_akun' => ($status_baru == 'disetujui') ? 'aktif' : 'nonaktif',
            'approved_by' => 'desa', // Ditandai disetujui oleh desa
            'updated_at' => current_time('mysql')
        ], 
        ['id' => $id_pedagang]
    );
    
    $msg_verif = "Status pedagang berhasil diperbarui menjadi " . ucfirst($status_baru);
}

// --- LOGIC 2: KELOLA WISATA (TAMBAH/EDIT) ---
if ( isset($_POST['save_wisata']) && wp_verify_nonce($_POST['wisata_nonce'], 'save_wisata_action') ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    
    $wisata_id = !empty($_POST['wisata_id']) ? intval($_POST['wisata_id']) : 0;
    
    $data_wisata = [
        'id_desa'     => $id_desa,
        'nama_wisata' => sanitize_text_field($_POST['nama_wisata']),
        'kategori'    => sanitize_text_field($_POST['kategori']),
        'deskripsi'   => wp_kses_post($_POST['deskripsi']),
        'harga_tiket' => floatval($_POST['harga_tiket']),
        'jam_buka'    => sanitize_text_field($_POST['jam_buka']),
        'lokasi_maps' => esc_url_raw($_POST['lokasi_maps']),
        'updated_at'  => current_time('mysql')
    ];

    // Upload Foto Utama
    if ( ! empty($_FILES['foto_utama']['name']) ) {
        $uploaded = wp_handle_upload( $_FILES['foto_utama'], array( 'test_form' => false ) );
        if ( isset( $uploaded['url'] ) ) $data_wisata['foto_utama'] = $uploaded['url'];
    }

    if ($wisata_id > 0) {
        $wpdb->update($table_wisata, $data_wisata, ['id' => $wisata_id, 'id_desa' => $id_desa]);
        $msg_wisata = "Data wisata berhasil diperbarui.";
    } else {
        $data_wisata['slug'] = sanitize_title($_POST['nama_wisata']) . '-' . rand(100,999);
        $data_wisata['created_at'] = current_time('mysql');
        $data_wisata['status'] = 'aktif';
        $wpdb->insert($table_wisata, $data_wisata);
        $msg_wisata = "Wisata baru berhasil ditambahkan.";
    }
}

// --- LOGIC 3: HAPUS WISATA ---
if ( isset($_GET['action']) && $_GET['action'] == 'hapus_wisata' && isset($_GET['id']) ) {
    $del_id = intval($_GET['id']);
    // Soft delete (set status nonaktif)
    $wpdb->update($table_wisata, ['status' => 'nonaktif'], ['id' => $del_id, 'id_desa' => $id_desa]);
    wp_redirect( remove_query_arg(['action', 'id']) );
    exit;
}

// --- LOGIC 4: AMBIL KATEGORI DINAMIS ---
// Daftar default yang lengkap
$default_cats = [
    'Wisata Alam', 'Wisata Bahari', 'Wisata Budaya', 'Wisata Sejarah', 
    'Wisata Edukasi', 'Wisata Kuliner', 'Wisata Religi', 'Wisata Buatan',
    'Wisata Petualangan', 'Agrowisata', 'Spot Foto', 'Camping Ground'
];

// Ambil kategori yang sudah pernah disimpan di database (agar sinkron dengan backend)
$existing_cats = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_wisata WHERE kategori != '' AND kategori IS NOT NULL");

// Gabungkan dan urutkan
if (!empty($existing_cats)) {
    $kategori_wisata = array_unique(array_merge($default_cats, $existing_cats));
    sort($kategori_wisata);
} else {
    $kategori_wisata = $default_cats;
    sort($kategori_wisata);
}

get_header(); 
?>

<div class="bg-gray-50 min-h-screen font-sans flex">
    
    <!-- SIDEBAR -->
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col fixed h-full z-20">
        <div class="p-6 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center text-xl shadow-lg shadow-blue-500/30">
                <i class="fas fa-landmark"></i>
            </div>
            <div>
                <h2 class="font-bold text-gray-800 leading-tight">Admin Desa</h2>
                <p class="text-[10px] text-gray-400"><?php echo esc_html($desa_data->nama_desa ?? 'Desa Wisata'); ?></p>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1">
            <button onclick="switchTab('ringkasan')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition active-tab" id="nav-ringkasan">
                <i class="fas fa-chart-line w-5 text-center"></i> Ringkasan
            </button>
            <button onclick="switchTab('verifikasi')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition relative" id="nav-verifikasi">
                <i class="fas fa-user-check w-5 text-center"></i> Verifikasi UMKM
                <?php 
                // Badge count
                $count_pending = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'menunggu_desa'", $id_desa));
                if($count_pending > 0): 
                ?>
                <span class="absolute right-3 top-3 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                <?php endif; ?>
            </button>
            <button onclick="switchTab('wisata')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-wisata">
                <i class="fas fa-map-marked-alt w-5 text-center"></i> Kelola Wisata
            </button>
            <button onclick="switchTab('profil')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-profil">
                <i class="fas fa-cogs w-5 text-center"></i> Pengaturan Desa
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
        <span class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-landmark text-blue-600"></i> Admin Desa</span>
        <button onclick="document.querySelector('aside').classList.toggle('hidden'); document.querySelector('aside').classList.toggle('flex');" class="text-gray-600 p-2">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 md:ml-64 p-4 md:p-8 pt-20 md:pt-8 overflow-y-auto h-screen pb-24">
        
        <!-- Notifikasi Global -->
        <?php if(isset($msg_verif)): ?>
            <div class="mb-6 p-4 rounded-xl bg-green-100 text-green-700 border border-green-200 flex items-center gap-2">
                <i class="fas fa-check-circle"></i> <?php echo $msg_verif; ?>
            </div>
        <?php endif; ?>
        <?php if(isset($msg_wisata)): ?>
            <div class="mb-6 p-4 rounded-xl bg-blue-100 text-blue-700 border border-blue-200 flex items-center gap-2">
                <i class="fas fa-info-circle"></i> <?php echo $msg_wisata; ?>
            </div>
        <?php endif; ?>

        <!-- 1. TAB RINGKASAN -->
        <div id="view-ringkasan" class="tab-content animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Statistik Desa</h1>
            
            <?php 
            // Hitung Statistik Realtime
            $total_umkm = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_pedagang WHERE id_desa = %d AND status_akun = 'aktif'", $id_desa));
            $total_wisata = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_wisata WHERE id_desa = %d AND status = 'aktif'", $id_desa));
            $pending_verif = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'menunggu_desa'", $id_desa));
            ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total UMKM -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center text-2xl">
                        <i class="fas fa-store"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium mb-1">Total UMKM Aktif</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($total_umkm); ?></h3>
                    </div>
                </div>

                <!-- Total Wisata -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium mb-1">Destinasi Wisata</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($total_wisata); ?></h3>
                    </div>
                </div>

                <!-- Perlu Verifikasi -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4 border-l-4 border-l-yellow-400">
                    <div class="w-14 h-14 bg-yellow-100 text-yellow-600 rounded-2xl flex items-center justify-center text-2xl">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 font-medium mb-1">Butuh Verifikasi</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($pending_verif); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. TAB VERIFIKASI UMKM -->
        <div id="view-verifikasi" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Verifikasi Pendaftaran UMKM</h1>
            
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <?php 
                $umkm_pending = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'menunggu_desa' ORDER BY created_at DESC", $id_desa));
                ?>
                
                <?php if($umkm_pending): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">Toko</th>
                                <th class="px-6 py-4">Pemilik</th>
                                <th class="px-6 py-4">Alamat</th>
                                <th class="px-6 py-4">Dokumen</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($umkm_pending as $u): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo !empty($u->foto_profil) ? esc_url($u->foto_profil) : 'https://placehold.co/50'; ?>" class="w-10 h-10 rounded-full object-cover">
                                        <span class="font-bold text-gray-800"><?php echo esc_html($u->nama_toko); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php echo esc_html($u->nama_pemilik); ?><br>
                                    <span class="text-xs text-gray-400"><?php echo esc_html($u->nomor_wa); ?></span>
                                </td>
                                <td class="px-6 py-4 max-w-xs truncate"><?php echo esc_html($u->alamat_lengkap); ?></td>
                                <td class="px-6 py-4">
                                    <?php if($u->url_ktp): ?>
                                    <a href="<?php echo esc_url($u->url_ktp); ?>" target="_blank" class="text-blue-600 hover:underline text-xs flex items-center gap-1">
                                        <i class="fas fa-file-image"></i> Lihat KTP
                                    </a>
                                    <?php else: ?>
                                    <span class="text-gray-400 text-xs">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form method="POST" class="inline-flex gap-2">
                                        <?php wp_nonce_field('verifikasi_pedagang_action', 'verifikasi_nonce'); ?>
                                        <input type="hidden" name="id_pedagang" value="<?php echo $u->id; ?>">
                                        <button type="submit" name="action_verifikasi" value="1" onclick="this.form.status_keputusan.value='disetujui'" class="bg-green-100 text-green-700 hover:bg-green-200 px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                            <i class="fas fa-check mr-1"></i> Terima
                                        </button>
                                        <button type="submit" name="action_verifikasi" value="1" onclick="this.form.status_keputusan.value='ditolak'" class="bg-red-100 text-red-700 hover:bg-red-200 px-3 py-1.5 rounded-lg text-xs font-bold transition">
                                            <i class="fas fa-times mr-1"></i> Tolak
                                        </button>
                                        <input type="hidden" name="status_keputusan" value="">
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4 text-gray-400">
                        <i class="fas fa-clipboard-check text-2xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900">Tidak ada pengajuan baru</h3>
                    <p class="text-gray-500 text-sm mt-1">Semua pendaftaran UMKM telah diverifikasi.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. TAB KELOLA WISATA -->
        <div id="view-wisata" class="tab-content hidden animate-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Daftar Objek Wisata</h1>
                <button onclick="openWisataModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 shadow-lg shadow-blue-500/20">
                    <i class="fas fa-plus"></i> Tambah Wisata
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php 
                $wisata_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE id_desa = %d AND status = 'aktif' ORDER BY created_at DESC", $id_desa));
                
                if($wisata_list): foreach($wisata_list as $w): 
                ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-lg transition">
                    <div class="h-40 bg-gray-200 relative overflow-hidden">
                        <img src="<?php echo !empty($w->foto_utama) ? esc_url($w->foto_utama) : 'https://placehold.co/400x250?text=Wisata'; ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded-lg text-xs font-bold text-gray-700 shadow-sm">
                            <?php echo esc_html($w->kategori); ?>
                        </div>
                    </div>
                    <div class="p-5">
                        <h3 class="font-bold text-gray-900 text-lg mb-1 truncate"><?php echo esc_html($w->nama_wisata); ?></h3>
                        <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?php echo strip_tags($w->deskripsi); ?></p>
                        
                        <div class="flex items-center justify-between pt-4 border-t border-gray-50">
                            <span class="text-blue-600 font-bold text-sm">
                                <?php echo ($w->harga_tiket > 0) ? 'Rp ' . number_format($w->harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                            </span>
                            <div class="flex gap-2">
                                <button onclick='openWisataModal(<?php echo json_encode($w); ?>)' class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition">
                                    <i class="fas fa-edit text-xs"></i>
                                </button>
                                <a href="?action=hapus_wisata&id=<?php echo $w->id; ?>" onclick="return confirm('Hapus wisata ini?');" class="w-8 h-8 rounded-full bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center transition">
                                    <i class="fas fa-trash text-xs"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="col-span-full py-12 text-center text-gray-400 bg-white rounded-xl border border-dashed border-gray-300">
                    <p>Belum ada data wisata. Silakan tambah baru.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. TAB PROFIL -->
        <div id="view-profil" class="tab-content hidden animate-fade-in">
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-yellow-800">
                <i class="fas fa-info-circle mr-2"></i> Untuk mengedit profil lengkap desa (Foto, Rekening, Lokasi), silakan gunakan menu Pengaturan di sidebar.
            </div>
        </div>

    </main>
</div>

<!-- MODAL TAMBAH/EDIT WISATA -->
<div id="modal-wisata" class="fixed inset-0 z-[60] hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeWisataModal()"></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-lg bg-white shadow-2xl overflow-y-auto transform transition-transform translate-x-full duration-300" id="modal-wisata-panel">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4 sticky top-0 bg-white z-10">
                <h2 class="text-xl font-bold text-gray-800" id="mw-title">Tambah Wisata</h2>
                <button onclick="closeWisataModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 transition"><i class="fas fa-times"></i></button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <?php wp_nonce_field('save_wisata_action', 'wisata_nonce'); ?>
                <input type="hidden" name="save_wisata" value="1">
                <input type="hidden" name="wisata_id" id="mw_id">
                
                <div class="space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Nama Wisata</label>
                        <input type="text" name="nama_wisata" id="mw_nama" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-blue-600 focus:border-blue-600">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Kategori</label>
                            <select name="kategori" id="mw_kategori" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white">
                                <!-- Kategori Dinamis -->
                                <?php 
                                foreach($kategori_wisata as $kat) {
                                    echo "<option value='$kat'>$kat</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Harga Tiket (Rp)</label>
                            <input type="number" name="harga_tiket" id="mw_harga" placeholder="0 jika gratis" class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Jam Buka</label>
                        <input type="text" name="jam_buka" id="mw_jam" placeholder="Contoh: 08:00 - 17:00 WIB" class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Deskripsi</label>
                        <textarea name="deskripsi" id="mw_deskripsi" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2.5"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Link Google Maps</label>
                        <input type="url" name="lokasi_maps" id="mw_maps" placeholder="https://maps.google.com/..." class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Foto Utama</label>
                        <input type="file" name="foto_utama" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-gray-100">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition flex justify-center gap-2 shadow-lg shadow-blue-500/20">
                        <i class="fas fa-save"></i> Simpan Wisata
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.active-tab { background-color: #eff6ff; color: #2563eb; border-right: 3px solid #2563eb; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active-tab'));
    
    document.getElementById('view-' + tabName).classList.remove('hidden');
    document.getElementById('nav-' + tabName).classList.add('active-tab');
}

// Modal Wisata Logic
const mw = document.getElementById('modal-wisata');
const mp = document.getElementById('modal-wisata-panel');

function openWisataModal(data = null) {
    mw.classList.remove('hidden');
    setTimeout(() => mp.classList.remove('translate-x-full'), 10);
    
    // Reset Form
    document.getElementById('mw_id').value = '';
    document.getElementById('mw_title').innerText = 'Tambah Wisata';
    document.querySelector('form').reset();

    if(data) {
        document.getElementById('mw_title').innerText = 'Edit Wisata';
        document.getElementById('mw_id').value = data.id;
        document.getElementById('mw_nama').value = data.nama_wisata;
        document.getElementById('mw_kategori').value = data.kategori;
        document.getElementById('mw_harga').value = data.harga_tiket;
        document.getElementById('mw_jam').value = data.jam_buka;
        document.getElementById('mw_deskripsi').value = data.deskripsi; // Note: strip_tags handled in display, here raw is better but simple value works
        document.getElementById('mw_maps').value = data.lokasi_maps;
    }
}

function closeWisataModal() {
    mp.classList.add('translate-x-full');
    setTimeout(() => mw.classList.add('hidden'), 300);
}

// Init Tab from URL
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab') || 'ringkasan';
    if(document.getElementById('view-' + tab)) {
        switchTab(tab);
    } else {
        switchTab('ringkasan');
    }
});
</script>

<?php wp_footer(); ?>