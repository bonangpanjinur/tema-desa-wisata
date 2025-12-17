<?php
/**
 * Template Name: Dashboard Desa (Admin Desa)
 * Description: Pusat kontrol untuk admin desa mengelola wisata, pedagang, dan profil desa.
 */

// 1. CEK AKSES & LOGIN
if (!is_user_logged_in()) {
    auth_redirect();
}

$current_user = wp_get_current_user();
global $wpdb;

// Table Definitions
$table_desa     = $wpdb->prefix . 'dw_desa';
$table_wisata   = $wpdb->prefix . 'dw_wisata';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

// Ambil Data Desa
$desa = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE id_user_desa = %d", $current_user->ID));

// Jika bukan admin desa, redirect atau tampilkan pesan
if (!$desa) {
    get_header();
    echo '<div class="container mx-auto py-20 text-center"><h2 class="text-2xl font-bold text-red-600">Akses Ditolak</h2><p>Anda belum terdaftar sebagai admin desa.</p><a href="'.home_url('/daftar-desa').'" class="btn-primary mt-4 inline-block">Daftar Sekarang</a></div>';
    get_footer();
    exit;
}

// --- LOGIC HANDLER (POST REQUESTS) ---
$message = '';
$message_type = ''; // success, error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // A. TAMBAH / EDIT WISATA
    if (isset($_POST['action']) && $_POST['action'] === 'save_wisata') {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'save_wisata_nonce')) die('Security check failed');

        $wisata_id = isset($_POST['wisata_id']) ? intval($_POST['wisata_id']) : 0;
        $nama      = sanitize_text_field($_POST['nama_wisata']);
        $kategori  = sanitize_text_field($_POST['kategori']);
        $harga     = intval($_POST['harga_tiket']);
        $desc      = wp_kses_post($_POST['deskripsi']);
        $jam_buka  = sanitize_text_field($_POST['jam_buka']);
        $kontak    = sanitize_text_field($_POST['kontak_pengelola']);
        $lokasi    = sanitize_textarea_field($_POST['lokasi_maps']);
        
        // Handle Upload Foto
        $attachment_id = '';
        if (!empty($_FILES['foto_utama']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            
            $attach_id = media_handle_upload('foto_utama', 0);
            if (!is_wp_error($attach_id)) {
                $attachment_id = $attach_id;
            }
        } else {
            $attachment_id = isset($_POST['existing_foto']) ? sanitize_text_field($_POST['existing_foto']) : '';
        }

        $data = [
            'id_desa'      => $desa->id,
            'nama_wisata'  => $nama,
            'slug'         => sanitize_title($nama),
            'kategori'     => $kategori,
            'deskripsi'    => $desc,
            'harga_tiket'  => $harga,
            'jam_buka'     => $jam_buka,
            'fasilitas'    => '', // Bisa ditambah field input fasilitas
            'kontak_pengelola' => $kontak,
            'lokasi_maps'  => $lokasi,
            'foto_utama'   => $attachment_id,
            'status'       => 'aktif',
            'updated_at'   => current_time('mysql')
        ];

        if ($wisata_id > 0) {
            // Update
            $wpdb->update($table_wisata, $data, ['id' => $wisata_id, 'id_desa' => $desa->id]);
            $message = 'Data wisata berhasil diperbarui.';
        } else {
            // Insert
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table_wisata, $data);
            $message = 'Wisata baru berhasil ditambahkan.';
        }
        $message_type = 'success';
    }

    // B. HAPUS WISATA
    if (isset($_POST['action']) && $_POST['action'] === 'delete_wisata') {
        $del_id = intval($_POST['wisata_id']);
        $wpdb->delete($table_wisata, ['id' => $del_id, 'id_desa' => $desa->id]);
        $message = 'Wisata berhasil dihapus.';
        $message_type = 'success';
    }

    // C. UPDATE PROFIL DESA
    if (isset($_POST['action']) && $_POST['action'] === 'update_desa') {
        $nama_desa = sanitize_text_field($_POST['nama_desa']);
        $desc_desa = wp_kses_post($_POST['deskripsi']);
        $alamat    = sanitize_textarea_field($_POST['alamat_lengkap']);
        
        // Upload Foto Profil Desa (Optional)
        $foto_desa_id = $desa->foto;
        if (!empty($_FILES['foto_desa']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attach_id = media_handle_upload('foto_desa', 0);
            if (!is_wp_error($attach_id)) $foto_desa_id = $attach_id;
        }

        $wpdb->update($table_desa, [
            'nama_desa'      => $nama_desa,
            'deskripsi'      => $desc_desa,
            'alamat_lengkap' => $alamat,
            'foto'           => $foto_desa_id
        ], ['id' => $desa->id]);
        
        $message = 'Profil desa berhasil diperbarui.';
        $message_type = 'success';
        // Refresh data
        $desa = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE id = %d", $desa->id));
    }

    // D. VERIFIKASI PEDAGANG
    if (isset($_POST['action']) && $_POST['action'] === 'verify_pedagang') {
        $pedagang_id = intval($_POST['pedagang_id']);
        $status_baru = sanitize_text_field($_POST['status_verifikasi']); // 'disetujui' atau 'ditolak'
        
        // Update status pendaftaran pedagang
        $wpdb->update($table_pedagang, [
            'status_pendaftaran' => $status_baru,
            'status_akun'        => ($status_baru == 'disetujui' ? 'aktif' : 'nonaktif')
        ], ['id' => $pedagang_id, 'id_desa' => $desa->id]);

        $message = 'Status pedagang berhasil diperbarui.';
        $message_type = 'success';
    }
}

// --- SETUP TABS ---
$tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

get_header(); 
?>

<div class="bg-gray-50 min-h-screen font-sans text-gray-800 pb-20">
    
    <!-- HEADER DASHBOARD -->
    <div class="bg-white border-b border-gray-200 sticky top-20 z-30">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between py-6 gap-4">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-teal-600 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-green-100 overflow-hidden">
                        <?php if($desa->foto): 
                            $img_src = is_numeric($desa->foto) ? wp_get_attachment_url($desa->foto) : $desa->foto;
                        ?>
                            <img src="<?php echo esc_url($img_src); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fas fa-landmark"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Desa <?php echo esc_html($desa->nama_desa); ?></h1>
                        <p class="text-sm text-gray-500">
                            Kabupaten: <span class="font-bold text-gray-700"><?php echo esc_html($desa->kabupaten); ?></span>
                            <span class="mx-2">•</span>
                            Status: <span class="capitalize px-2 py-0.5 rounded text-xs font-bold <?php echo $desa->status === 'aktif' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>"><?php echo $desa->status; ?></span>
                        </p>
                    </div>
                </div>
                <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
                    <a href="?tab=dashboard" class="px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition <?php echo $tab == 'dashboard' ? 'bg-green-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                    <a href="?tab=wisata" class="px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition <?php echo $tab == 'wisata' ? 'bg-green-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                        <i class="fas fa-map-marked-alt mr-1"></i> Wisata
                    </a>
                    <a href="?tab=pedagang" class="px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition <?php echo $tab == 'pedagang' ? 'bg-green-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                        <i class="fas fa-store mr-1"></i> Pedagang
                    </a>
                    <a href="?tab=pengaturan" class="px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition <?php echo $tab == 'pengaturan' ? 'bg-green-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                        <i class="fas fa-cog mr-1"></i> Profil Desa
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container mx-auto px-4 py-8">
        
        <?php if($message): ?>
            <div class="mb-6 p-4 rounded-xl <?php echo $message_type == 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <?php 
        switch($tab) {
            
            // ------------------------------------------------------------------
            // TAB 1: DASHBOARD UTAMA
            // ------------------------------------------------------------------
            case 'dashboard':
                $total_wisata   = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_wisata WHERE id_desa = %d", $desa->id));
                $total_pedagang = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'disetujui'", $desa->id));
                $pedagang_pending = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_pedagang WHERE id_desa = %d AND status_pendaftaran = 'menunggu_desa'", $desa->id));
                ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="text-gray-500 text-sm mb-1">Total Wisata</div>
                        <div class="text-3xl font-bold text-gray-800"><?php echo number_format($total_wisata); ?></div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="text-gray-500 text-sm mb-1">UMKM Terdaftar</div>
                        <div class="text-3xl font-bold text-gray-800"><?php echo number_format($total_pedagang); ?></div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
                        <div class="text-gray-500 text-sm mb-1">Menunggu Verifikasi</div>
                        <div class="text-3xl font-bold text-orange-600"><?php echo number_format($pedagang_pending); ?></div>
                        <?php if($pedagang_pending > 0): ?>
                            <a href="?tab=pedagang" class="absolute bottom-4 right-4 text-xs bg-orange-100 text-orange-700 px-3 py-1 rounded-full font-bold hover:bg-orange-200 transition">Review</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-8 bg-gradient-to-r from-green-600 to-teal-600 rounded-2xl shadow-lg text-white p-8 text-center md:text-left flex flex-col md:flex-row items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-bold mb-2">Kelola Potensi Desa Anda</h3>
                        <p class="text-green-100 max-w-xl">Tambahkan destinasi wisata baru dan bantu UMKM lokal berkembang dengan verifikasi pedagang yang cepat.</p>
                    </div>
                    <div class="mt-6 md:mt-0 flex gap-3">
                        <a href="?tab=wisata&act=add" class="bg-white text-green-700 px-6 py-3 rounded-xl font-bold hover:bg-gray-100 transition shadow-md">
                            + Tambah Wisata
                        </a>
                    </div>
                </div>
                <?php
                break;

            // ------------------------------------------------------------------
            // TAB 2: MANAJEMEN WISATA (CRUD)
            // ------------------------------------------------------------------
            case 'wisata':
                $action = isset($_GET['act']) ? $_GET['act'] : 'list';
                $edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

                // --- FORM TAMBAH / EDIT ---
                if ($action === 'add' || $action === 'edit') {
                    $w_data = null;
                    if ($action === 'edit' && $edit_id) {
                        $w_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_wisata WHERE id = %d AND id_desa = %d", $edit_id, $desa->id));
                    }
                    ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 max-w-3xl mx-auto">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800"><?php echo $action === 'add' ? 'Tambah Wisata Baru' : 'Edit Wisata'; ?></h2>
                            <a href="?tab=wisata" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i> Batal</a>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="space-y-5">
                            <?php wp_nonce_field('save_wisata_nonce'); ?>
                            <input type="hidden" name="action" value="save_wisata">
                            <?php if($w_data): ?><input type="hidden" name="wisata_id" value="<?php echo $w_data->id; ?>"><?php endif; ?>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Wisata</label>
                                <input type="text" name="nama_wisata" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" required value="<?php echo $w_data ? esc_attr($w_data->nama_wisata) : ''; ?>">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Harga Tiket (Rp)</label>
                                    <input type="number" name="harga_tiket" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" value="<?php echo $w_data ? esc_attr($w_data->harga_tiket) : '0'; ?>">
                                    <p class="text-xs text-gray-400 mt-1">Isi 0 jika gratis.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Jam Buka</label>
                                    <input type="text" name="jam_buka" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="Contoh: 08:00 - 17:00" value="<?php echo $w_data ? esc_attr($w_data->jam_buka) : ''; ?>">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Kategori</label>
                                <select name="kategori" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                    <?php 
                                    $cats = ['Alam', 'Budaya', 'Edukasi', 'Religi', 'Kuliner', 'Buatan'];
                                    foreach($cats as $c) {
                                        $sel = ($w_data && $w_data->kategori == $c) ? 'selected' : '';
                                        echo "<option value='$c' $sel>$c</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Foto Utama</label>
                                <?php if($w_data && $w_data->foto_utama): 
                                    $img_src = is_numeric($w_data->foto_utama) ? wp_get_attachment_url($w_data->foto_utama) : $w_data->foto_utama;
                                ?>
                                    <div class="mb-2">
                                        <img src="<?php echo esc_url($img_src); ?>" class="h-20 w-20 object-cover rounded border border-gray-200">
                                        <input type="hidden" name="existing_foto" value="<?php echo esc_attr($w_data->foto_utama); ?>">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="foto_utama" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 accept="image/*"">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi</label>
                                <textarea name="deskripsi" rows="5" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"><?php echo $w_data ? esc_textarea($w_data->deskripsi) : ''; ?></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Kontak Pengelola</label>
                                    <input type="text" name="kontak_pengelola" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="Nomor WA/Telp" value="<?php echo $w_data ? esc_attr($w_data->kontak_pengelola) : ''; ?>">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Link Google Maps</label>
                                    <input type="text" name="lokasi_maps" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500" placeholder="https://maps.app.goo.gl/..." value="<?php echo $w_data ? esc_attr($w_data->lokasi_maps) : ''; ?>">
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-700 transition shadow-lg">Simpan Data Wisata</button>
                            </div>
                        </form>
                    </div>
                    <?php
                } 
                // --- LIST WISATA ---
                else {
                    $wisata_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_wisata WHERE id_desa = %d ORDER BY created_at DESC", $desa->id));
                    ?>
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Daftar Wisata Desa</h2>
                        <a href="?tab=wisata&act=add" class="bg-green-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-green-700 transition flex items-center gap-2">
                            <i class="fas fa-plus"></i> Tambah
                        </a>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                                    <tr>
                                        <th class="p-4">Foto</th>
                                        <th class="p-4">Nama Wisata</th>
                                        <th class="p-4">Kategori</th>
                                        <th class="p-4">Tiket</th>
                                        <th class="p-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if($wisata_list): foreach($wisata_list as $wis): 
                                        $img_url = 'https://via.placeholder.com/50';
                                        if($wis->foto_utama) {
                                            $img_url = is_numeric($wis->foto_utama) ? wp_get_attachment_image_url($wis->foto_utama, 'thumbnail') : $wis->foto_utama;
                                        }
                                    ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="p-4">
                                            <img src="<?php echo esc_url($img_url); ?>" class="w-12 h-12 rounded object-cover border border-gray-200">
                                        </td>
                                        <td class="p-4 font-bold text-gray-800"><?php echo esc_html($wis->nama_wisata); ?></td>
                                        <td class="p-4"><span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs"><?php echo esc_html($wis->kategori); ?></span></td>
                                        <td class="p-4"><?php echo ($wis->harga_tiket > 0) ? 'Rp '.number_format($wis->harga_tiket, 0, ',', '.') : 'Gratis'; ?></td>
                                        <td class="p-4 text-right flex justify-end gap-2">
                                            <a href="?tab=wisata&act=edit&id=<?php echo $wis->id; ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded"><i class="fas fa-edit"></i></a>
                                            <form method="POST" onsubmit="return confirm('Yakin hapus wisata ini?');" class="inline">
                                                <input type="hidden" name="action" value="delete_wisata">
                                                <input type="hidden" name="wisata_id" value="<?php echo $wis->id; ?>">
                                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="5" class="p-8 text-center text-gray-400">Belum ada data wisata.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                }
                break;

            // ------------------------------------------------------------------
            // TAB 3: MANAJEMEN PEDAGANG (VERIFIKASI)
            // ------------------------------------------------------------------
            case 'pedagang':
                $pedagangs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_desa = %d ORDER BY created_at DESC", $desa->id));
                ?>
                <h2 class="text-xl font-bold text-gray-800 mb-6">Manajemen Pedagang (UMKM)</h2>
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                                <tr>
                                    <th class="p-4">Nama Toko</th>
                                    <th class="p-4">Pemilik</th>
                                    <th class="p-4">Kontak</th>
                                    <th class="p-4">Status Pendaftaran</th>
                                    <th class="p-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if($pedagangs): foreach($pedagangs as $p): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4 font-bold text-gray-800"><?php echo esc_html($p->nama_toko); ?></td>
                                    <td class="p-4"><?php echo esc_html($p->nama_pemilik); ?></td>
                                    <td class="p-4 text-gray-500"><?php echo esc_html($p->nomor_wa); ?></td>
                                    <td class="p-4">
                                        <span class="px-2 py-1 rounded text-xs font-bold capitalize <?php 
                                            echo match($p->status_pendaftaran) {
                                                'disetujui' => 'bg-green-100 text-green-700',
                                                'menunggu_desa' => 'bg-orange-100 text-orange-700',
                                                'ditolak' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-600'
                                            };
                                        ?>">
                                            <?php echo str_replace('_', ' ', $p->status_pendaftaran); ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <?php if($p->status_pendaftaran == 'menunggu_desa'): ?>
                                            <form method="POST" class="flex gap-2 justify-end">
                                                <input type="hidden" name="action" value="verify_pedagang">
                                                <input type="hidden" name="pedagang_id" value="<?php echo $p->id; ?>">
                                                <button type="submit" name="status_verifikasi" value="disetujui" class="bg-green-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-green-700">Setujui</button>
                                                <button type="submit" name="status_verifikasi" value="ditolak" class="bg-red-500 text-white px-3 py-1 rounded text-xs font-bold hover:bg-red-600" onclick="return confirm('Tolak pedagang ini?');">Tolak</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="5" class="p-8 text-center text-gray-400">Belum ada pedagang terdaftar di desa ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                break;

            // ------------------------------------------------------------------
            // TAB 4: PROFIL DESA (EDIT)
            // ------------------------------------------------------------------
            case 'pengaturan':
                ?>
                <div class="max-w-2xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-4">Edit Profil Desa</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_desa">
                        
                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Desa</label>
                                <input type="text" name="nama_desa" value="<?php echo esc_attr($desa->nama_desa); ?>" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Foto Profil Desa</label>
                                <?php if($desa->foto): 
                                    $img_src = is_numeric($desa->foto) ? wp_get_attachment_url($desa->foto) : $desa->foto;
                                ?>
                                    <img src="<?php echo esc_url($img_src); ?>" class="h-20 w-20 object-cover rounded border border-gray-200 mb-2">
                                <?php endif; ?>
                                <input type="file" name="foto_desa" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 accept="image/*"">
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi Singkat</label>
                                <textarea name="deskripsi" rows="4" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"><?php echo esc_textarea($desa->deskripsi); ?></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Alamat Kantor Desa</label>
                                <textarea name="alamat_lengkap" rows="2" class="w-full border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500"><?php echo esc_textarea($desa->alamat_lengkap); ?></textarea>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100">
                            <button type="submit" class="w-full bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-700 transition">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
                <?php
                break;
        } 
        ?>
        
    </div>
</div>

<?php get_footer(); ?>