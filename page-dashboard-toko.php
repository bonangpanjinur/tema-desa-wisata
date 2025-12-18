<?php
/**
 * Template Name: Dashboard Toko (Pedagang)
 * Description: Pusat kontrol untuk pedagang mengelola produk, pesanan, dan paket secara lengkap.
 */

// Buffering output untuk mencegah error redirect
ob_start();

// 1. CEK AKSES & LOGIN
if (!is_user_logged_in()) {
    auth_redirect();
}

$current_user = wp_get_current_user();
global $wpdb;

// Table Definitions
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_transaksi_sub = $wpdb->prefix . 'dw_transaksi_sub';
$table_paket    = $wpdb->prefix . 'dw_paket_transaksi';
$table_pembelian_paket = $wpdb->prefix . 'dw_pembelian_paket';

// Ambil Data Pedagang
$pedagang = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_user = %d", $current_user->ID));

// Jika bukan pedagang, redirect atau tampilkan pesan
if (!$pedagang) {
    get_header();
    echo '<div class="container mx-auto py-20 text-center"><h2 class="text-2xl font-bold text-red-600">Akses Ditolak</h2><p>Anda belum terdaftar sebagai pedagang.</p><a href="'.home_url('/daftar-pedagang').'" class="btn-primary mt-4 inline-block">Daftar Sekarang</a></div>';
    get_footer();
    exit;
}

// --- LOGIC HANDLER (POST REQUESTS) ---
// Pattern: Post-Redirect-Get (PRG) untuk mencegah submit ulang saat refresh

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $current_url = get_permalink();

    // A. TAMBAH / EDIT PRODUK
    if (isset($_POST['action']) && $_POST['action'] === 'save_product') {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'save_product_nonce')) die('Security check failed');

        $prod_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $nama    = sanitize_text_field($_POST['nama_produk']);
        $harga   = intval($_POST['harga']);
        $stok    = intval($_POST['stok']);
        $desc    = wp_kses_post($_POST['deskripsi']);
        $kategori= sanitize_text_field($_POST['kategori']);
        
        // 1. Handle Foto Utama
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

        // 2. Handle Galeri
        $galeri_ids = [];
        if (isset($_POST['existing_galeri']) && !empty($_POST['existing_galeri'])) {
            $galeri_ids = json_decode(stripslashes($_POST['existing_galeri']), true);
            if (!is_array($galeri_ids)) $galeri_ids = [];
        }

        if (!empty($_FILES['galeri']['name'][0])) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $files = $_FILES['galeri'];
            foreach ($files['name'] as $key => $value) {
                if ($files['name'][$key]) {
                    $file = array(
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key]
                    );
                    $_FILES = array("upload_file" => $file);
                    $attach_id = media_handle_upload("upload_file", 0);
                    if (!is_wp_error($attach_id)) {
                        $galeri_ids[] = $attach_id;
                    }
                }
            }
        }

        $data = [
            'id_pedagang' => $pedagang->id,
            'nama_produk' => $nama,
            'slug'        => sanitize_title($nama),
            'deskripsi'   => $desc,
            'harga'       => $harga,
            'stok'        => $stok,
            'kategori'    => $kategori,
            'foto_utama'  => $attachment_id,
            'galeri'      => json_encode($galeri_ids),
            'status'      => 'aktif',
            'updated_at'  => current_time('mysql')
        ];

        if ($prod_id > 0) {
            $wpdb->update($table_produk, $data, ['id' => $prod_id, 'id_pedagang' => $pedagang->id]);
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert($table_produk, $data);
        }
        
        // Redirect Sukses
        wp_safe_redirect(add_query_arg(['tab' => 'produk', 'msg' => 'saved'], $current_url));
        exit;
    }

    // B. HAPUS PRODUK
    if (isset($_POST['action']) && $_POST['action'] === 'delete_product') {
        $del_id = intval($_POST['product_id']);
        $wpdb->delete($table_produk, ['id' => $del_id, 'id_pedagang' => $pedagang->id]);
        
        // Redirect Sukses
        wp_safe_redirect(add_query_arg(['tab' => 'produk', 'msg' => 'deleted'], $current_url));
        exit;
    }

    // C. BELI PAKET
    if (isset($_POST['action']) && $_POST['action'] === 'buy_package') {
        $paket_id = intval($_POST['paket_id']);
        
        // Cek apakah ada request pending yang sama untuk mencegah spam klik
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_pembelian_paket WHERE id_pedagang = %d AND id_paket = %d AND status = 'pending'",
            $pedagang->id, $paket_id
        ));

        if (!$existing) {
            $paket = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_paket WHERE id = %d", $paket_id));
            if ($paket) {
                $wpdb->insert($table_pembelian_paket, [
                    'id_pedagang' => $pedagang->id,
                    'id_paket'    => $paket->id,
                    'nama_paket_snapshot' => $paket->nama_paket,
                    'harga_paket' => $paket->harga,
                    'jumlah_transaksi' => $paket->jumlah_transaksi,
                    'status'      => 'pending', 
                    'created_at'  => current_time('mysql')
                ]);
                
                // Redirect Sukses
                wp_safe_redirect(add_query_arg(['tab' => 'paket', 'msg' => 'ordered'], $current_url));
                exit;
            }
        } else {
            // Redirect Warning (Sudah ada)
            wp_safe_redirect(add_query_arg(['tab' => 'paket', 'msg' => 'duplicate_order'], $current_url));
            exit;
        }
    }

    // D. UPLOAD BUKTI BAYAR PAKET
    if (isset($_POST['action']) && $_POST['action'] === 'upload_bukti_paket') {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'upload_bukti_paket_nonce')) die('Security check failed');
        
        $pembelian_id = intval($_POST['pembelian_id']);
        $check_trx = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_pembelian_paket WHERE id = %d AND id_pedagang = %d", $pembelian_id, $pedagang->id));
        
        if ($check_trx) {
            if (!empty($_FILES['bukti_bayar']['name'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attach_id = media_handle_upload('bukti_bayar', 0);
                
                if (!is_wp_error($attach_id)) {
                    $url_bukti = wp_get_attachment_url($attach_id);
                    // Reset status ke pending jika sebelumnya ditolak, agar admin cek lagi
                    $new_status = ($check_trx->status == 'ditolak') ? 'pending' : $check_trx->status;
                    
                    $wpdb->update($table_pembelian_paket, [
                        'url_bukti_bayar' => $url_bukti,
                        'status' => $new_status // Reset status jika re-upload
                    ], ['id' => $pembelian_id]);
                    
                    wp_safe_redirect(add_query_arg(['tab' => 'paket', 'msg' => 'uploaded'], $current_url));
                    exit;
                } else {
                    wp_safe_redirect(add_query_arg(['tab' => 'paket', 'msg' => 'upload_fail'], $current_url));
                    exit;
                }
            }
        }
        wp_safe_redirect(add_query_arg(['tab' => 'paket', 'msg' => 'error'], $current_url));
        exit;
    }

    // E. UPDATE TOKO
    if (isset($_POST['action']) && $_POST['action'] === 'update_shop') {
        $nama_toko = sanitize_text_field($_POST['nama_toko']);
        $no_wa     = sanitize_text_field($_POST['nomor_wa']);
        $alamat    = sanitize_textarea_field($_POST['alamat_lengkap']);
        
        // Data Wilayah
        $prov_id   = sanitize_text_field($_POST['provinsi']);
        $kab_id    = sanitize_text_field($_POST['kabupaten']);
        $kec_id    = sanitize_text_field($_POST['kecamatan']);
        $kel_id    = sanitize_text_field($_POST['kelurahan']);
        
        // Simpan nama wilayah juga (biasanya dikirim via hidden input, tapi jika tidak, kita simpan ID saja dulu atau ambil dari API backend)
        // Di sini kita asumsikan ID yang dikirim.
        
        $nama_bank      = sanitize_text_field($_POST['nama_bank']);
        $no_rekening    = sanitize_text_field($_POST['no_rekening']);
        $atas_nama_rek  = sanitize_text_field($_POST['atas_nama_rekening']);

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $foto_profil_url = $pedagang->foto_profil;
        if (!empty($_FILES['foto_profil']['name'])) {
            $attach_id = media_handle_upload('foto_profil', 0);
            if (!is_wp_error($attach_id)) $foto_profil_url = wp_get_attachment_url($attach_id);
        }

        $qris_url = $pedagang->qris_image_url;
        if (!empty($_FILES['qris_toko']['name'])) {
            $attach_id = media_handle_upload('qris_toko', 0);
            if (!is_wp_error($attach_id)) $qris_url = wp_get_attachment_url($attach_id);
        }

        $wpdb->update($table_pedagang, [
            'nama_toko'      => $nama_toko,
            'nomor_wa'       => $no_wa,
            'alamat_lengkap' => $alamat,
            'api_provinsi_id'=> $prov_id,
            'api_kabupaten_id'=> $kab_id,
            'api_kecamatan_id'=> $kec_id,
            'api_kelurahan_id'=> $kel_id,
            'foto_profil'    => $foto_profil_url,
            'nama_bank'      => $nama_bank,
            'no_rekening'    => $no_rekening,
            'atas_nama_rekening' => $atas_nama_rek,
            'qris_image_url' => $qris_url
        ], ['id' => $pedagang->id]);
        
        wp_safe_redirect(add_query_arg(['tab' => 'pengaturan', 'msg' => 'shop_updated'], $current_url));
        exit;
    }
}

// --- SETUP MESSAGE DISPLAY ---
$message = '';
$message_type = '';

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'saved': $message = 'Produk berhasil disimpan.'; $message_type = 'success'; break;
        case 'deleted': $message = 'Produk berhasil dihapus.'; $message_type = 'success'; break;
        case 'ordered': $message = 'Permintaan paket berhasil dibuat. Silakan upload bukti bayar.'; $message_type = 'success'; break;
        case 'duplicate_order': $message = 'Anda sudah memiliki permintaan paket pending untuk paket ini.'; $message_type = 'warning'; break;
        case 'uploaded': $message = 'Bukti pembayaran berhasil diupload. Mohon tunggu verifikasi.'; $message_type = 'success'; break;
        case 'upload_fail': $message = 'Gagal mengupload gambar. Pastikan format sesuai.'; $message_type = 'error'; break;
        case 'shop_updated': $message = 'Pengaturan toko berhasil disimpan.'; $message_type = 'success'; break;
        case 'error': $message = 'Terjadi kesalahan sistem.'; $message_type = 'error'; break;
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
                    <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-orange-100 overflow-hidden relative">
                        <?php if($pedagang->foto_profil): ?>
                            <img src="<?php echo esc_url($pedagang->foto_profil); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <?php echo strtoupper(substr($pedagang->nama_toko, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo esc_html($pedagang->nama_toko); ?></h1>
                        <p class="text-sm text-gray-500">
                            Sisa Kuota: <span class="font-bold text-orange-600"><?php echo $pedagang->sisa_transaksi; ?> Transaksi</span>
                            <span class="mx-2">•</span>
                            Status: <span class="capitalize px-2 py-0.5 rounded text-xs font-bold <?php echo $pedagang->status_akun === 'aktif' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>"><?php echo $pedagang->status_akun; ?></span>
                        </p>
                    </div>
                </div>
                <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
                    <a href="?tab=dashboard" class="px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition <?php echo $tab == 'dashboard' ? 'bg-orange-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                        <i class="fas fa-home mr-1"></i> Dashboard
                    </a>
                    <a href="?tab=produk" class="px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition <?php echo $tab == 'produk' ? 'bg-orange-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                        <i class="fas fa-box mr-1"></i> Produk
                    </a>
                    <a href="?tab=pesanan" class="px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition <?php echo $tab == 'pesanan' ? 'bg-orange-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                        <i class="fas fa-receipt mr-1"></i> Pesanan
                    </a>
                    <a href="?tab=paket" class="px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition <?php echo $tab == 'paket' ? 'bg-orange-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                        <i class="fas fa-gem mr-1"></i> Beli Paket
                    </a>
                    <a href="?tab=pengaturan" class="px-4 py-2 rounded-lg font-medium text-sm whitespace-nowrap transition <?php echo $tab == 'pengaturan' ? 'bg-orange-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                        <i class="fas fa-cog mr-1"></i> Pengaturan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container mx-auto px-4 py-8">
        
        <?php if($message): ?>
            <div class="mb-6 p-4 rounded-xl <?php echo $message_type == 'success' ? 'bg-green-50 text-green-700 border border-green-200' : ($message_type == 'warning' ? 'bg-yellow-50 text-yellow-700 border border-yellow-200' : 'bg-red-50 text-red-700 border border-red-200'); ?>">
                <?php echo esc_html($message); ?>
            </div>
        <?php endif; ?>

        <?php 
        switch($tab) {
            
            // ------------------------------------------------------------------
            // TAB 1: DASHBOARD UTAMA (STATS)
            // ------------------------------------------------------------------
            case 'dashboard':
                $total_produk = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_produk WHERE id_pedagang = %d", $pedagang->id));
                $total_order  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_transaksi_sub WHERE id_pedagang = %d", $pedagang->id));
                $pendapatan   = $wpdb->get_var($wpdb->prepare("SELECT SUM(sub_total) FROM $table_transaksi_sub WHERE id_pedagang = %d AND status_pesanan = 'selesai'", $pedagang->id));
                ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="text-gray-500 text-sm mb-1">Total Produk</div>
                        <div class="text-3xl font-bold text-gray-800"><?php echo number_format($total_produk); ?></div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="text-gray-500 text-sm mb-1">Total Pesanan</div>
                        <div class="text-3xl font-bold text-gray-800"><?php echo number_format($total_order); ?></div>
                    </div>
                    <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                        <div class="text-gray-500 text-sm mb-1">Total Pendapatan</div>
                        <div class="text-3xl font-bold text-green-600">Rp <?php echo number_format($pendapatan, 0, ',', '.'); ?></div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-500 to-orange-600 p-6 rounded-2xl shadow-lg text-white">
                        <div class="text-orange-100 text-sm mb-1">Sisa Kuota</div>
                        <div class="text-3xl font-bold"><?php echo number_format($pedagang->sisa_transaksi); ?></div>
                        <a href="?tab=paket" class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1 rounded inline-block mt-2 transition">Tambah Kuota</a>
                    </div>
                </div>
                
                <!-- Recent Orders Preview -->
                <div class="mt-8 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-lg text-gray-800 mb-4">Pesanan Terbaru</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500 font-bold">
                                <tr>
                                    <th class="p-3 rounded-l-lg">ID</th>
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Total</th>
                                    <th class="p-3 rounded-r-lg">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php 
                                $recent_orders = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_transaksi_sub WHERE id_pedagang = %d ORDER BY created_at DESC LIMIT 5", $pedagang->id));
                                if($recent_orders):
                                    foreach($recent_orders as $order): ?>
                                    <tr>
                                        <td class="p-3 font-mono">#<?php echo $order->id; ?></td>
                                        <td class="p-3"><?php echo date('d M Y', strtotime($order->created_at)); ?></td>
                                        <td class="p-3">Rp <?php echo number_format($order->sub_total, 0,',','.'); ?></td>
                                        <td class="p-3"><span class="px-2 py-1 bg-gray-100 rounded text-xs font-bold"><?php echo $order->status_pesanan; ?></span></td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="p-4 text-center text-gray-400">Belum ada pesanan.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                break;

            // ------------------------------------------------------------------
            // TAB 2: MANAJEMEN PRODUK (CRUD)
            // ------------------------------------------------------------------
            case 'produk':
                $action = isset($_GET['act']) ? $_GET['act'] : 'list';
                $edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

                // --- FORM TAMBAH / EDIT ---
                if ($action === 'add' || $action === 'edit') {
                    $p_data = null;
                    if ($action === 'edit' && $edit_id) {
                        $p_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_produk WHERE id = %d AND id_pedagang = %d", $edit_id, $pedagang->id));
                    }
                    ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 max-w-4xl mx-auto">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold text-gray-800"><?php echo $action === 'add' ? 'Tambah Produk Baru' : 'Edit Produk'; ?></h2>
                            <a href="?tab=produk" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i> Batal</a>
                        </div>
                        
                        <form method="POST" enctype="multipart/form-data" class="space-y-6">
                            <?php wp_nonce_field('save_product_nonce'); ?>
                            <input type="hidden" name="action" value="save_product">
                            <?php if($p_data): ?><input type="hidden" name="product_id" value="<?php echo $p_data->id; ?>"><?php endif; ?>

                            <!-- Info Dasar -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Produk</label>
                                    <input type="text" name="nama_produk" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" required value="<?php echo $p_data ? esc_attr($p_data->nama_produk) : ''; ?>">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Kategori</label>
                                    <select name="kategori" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                        <?php 
                                        $kategori_produk_db = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_produk WHERE kategori IS NOT NULL AND kategori != ''");
                                        $cats_default = ['Makanan', 'Minuman', 'Kerajinan', 'Fashion', 'Pertanian', 'Jasa'];
                                        $cats = array_unique(array_merge($cats_default, $kategori_produk_db));
                                        sort($cats);

                                        foreach($cats as $c) {
                                            $sel = ($p_data && $p_data->kategori == $c) ? 'selected' : '';
                                            echo "<option value='$c' $sel>$c</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Stok Awal</label>
                                    <input type="number" name="stok" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" required value="<?php echo $p_data ? esc_attr($p_data->stok) : '1'; ?>">
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Harga (Rp)</label>
                                    <input type="number" name="harga" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors" required value="<?php echo $p_data ? esc_attr($p_data->harga) : ''; ?>">
                                </div>
                            </div>

                            <!-- Detail Info -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi Produk</label>
                                <textarea name="deskripsi" rows="5" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors"><?php echo $p_data ? esc_textarea($p_data->deskripsi) : ''; ?></textarea>
                            </div>

                            <!-- Media -->
                            <div class="border-t border-gray-100 pt-6">
                                <h3 class="font-bold text-gray-800 mb-4">Media & Foto</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Foto Utama (Sampul)</label>
                                        <?php if($p_data && $p_data->foto_utama): 
                                            $img_src = is_numeric($p_data->foto_utama) ? wp_get_attachment_url($p_data->foto_utama) : $p_data->foto_utama;
                                        ?>
                                            <div class="mb-2">
                                                <img src="<?php echo esc_url($img_src); ?>" class="h-32 w-full object-cover rounded border border-gray-200">
                                                <input type="hidden" name="existing_foto" value="<?php echo esc_attr($p_data->foto_utama); ?>">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="foto_utama" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100" accept="image/*">
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-2">Galeri Foto (Upload Banyak)</label>
                                        <?php if($p_data && $p_data->galeri): 
                                            $gallery = json_decode($p_data->galeri, true);
                                            if($gallery && is_array($gallery)):
                                        ?>
                                            <div class="flex gap-2 mb-2 overflow-x-auto pb-2">
                                                <?php foreach($gallery as $img_id): 
                                                    $g_src = is_numeric($img_id) ? wp_get_attachment_url($img_id) : $img_id;
                                                ?>
                                                <img src="<?php echo esc_url($g_src); ?>" class="h-16 w-16 object-cover rounded border border-gray-200 flex-shrink-0">
                                                <?php endforeach; ?>
                                            </div>
                                            <input type="hidden" name="existing_galeri" value="<?php echo esc_attr($p_data->galeri); ?>">
                                        <?php endif; endif; ?>
                                        <input type="file" name="galeri[]" multiple class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*">
                                        <p class="text-xs text-gray-400 mt-1">Tekan Ctrl/Cmd untuk memilih banyak foto sekaligus.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="pt-6 border-t border-gray-100 flex justify-end">
                                <button type="submit" class="bg-gray-900 text-white px-8 py-3 rounded-xl font-bold hover:bg-orange-600 transition shadow-lg flex items-center gap-2">
                                    <i class="fas fa-save"></i> Simpan Produk
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php
                } 
                // --- LIST PRODUK ---
                else {
                    $products = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_produk WHERE id_pedagang = %d ORDER BY created_at DESC", $pedagang->id));
                    ?>
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Daftar Produk</h2>
                        <a href="?tab=produk&act=add" class="bg-orange-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-orange-700 transition flex items-center gap-2">
                            <i class="fas fa-plus"></i> Tambah
                        </a>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                                    <tr>
                                        <th class="p-4">Foto</th>
                                        <th class="p-4">Nama Produk</th>
                                        <th class="p-4">Harga</th>
                                        <th class="p-4 text-center">Stok</th>
                                        <th class="p-4 text-center">Terjual</th>
                                        <th class="p-4 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if($products): foreach($products as $prod): 
                                        $img_url = 'https://via.placeholder.com/50';
                                        if($prod->foto_utama) {
                                            $img_url = is_numeric($prod->foto_utama) ? wp_get_attachment_image_url($prod->foto_utama, 'thumbnail') : $prod->foto_utama;
                                        }
                                    ?>
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="p-4">
                                            <img src="<?php echo esc_url($img_url); ?>" class="w-12 h-12 rounded object-cover border border-gray-200">
                                        </td>
                                        <td class="p-4 font-bold text-gray-800"><?php echo esc_html($prod->nama_produk); ?></td>
                                        <td class="p-4">Rp <?php echo number_format($prod->harga, 0, ',', '.'); ?></td>
                                        <td class="p-4 text-center">
                                            <span class="px-2 py-1 rounded text-xs font-bold <?php echo $prod->stok > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                                <?php echo $prod->stok; ?>
                                            </span>
                                        </td>
                                        <td class="p-4 text-center"><?php echo $prod->terjual; ?></td>
                                        <td class="p-4 text-right flex justify-end gap-2">
                                            <a href="?tab=produk&act=edit&id=<?php echo $prod->id; ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded"><i class="fas fa-edit"></i></a>
                                            <form method="POST" onsubmit="return confirm('Yakin hapus produk ini?');" class="inline">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="product_id" value="<?php echo $prod->id; ?>">
                                                <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="6" class="p-8 text-center text-gray-400">Belum ada produk. Silakan tambah produk baru.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php
                }
                break;

            // ------------------------------------------------------------------
            // TAB 3: MANAJEMEN PESANAN
            // ------------------------------------------------------------------
            case 'pesanan':
                $orders = $wpdb->get_results($wpdb->prepare("
                    SELECT s.*, t.nama_penerima, t.alamat_lengkap, t.kode_unik 
                    FROM $table_transaksi_sub s
                    JOIN {$wpdb->prefix}dw_transaksi t ON s.id_transaksi = t.id
                    WHERE s.id_pedagang = %d 
                    ORDER BY s.created_at DESC", $pedagang->id));
                ?>
                <h2 class="text-xl font-bold text-gray-800 mb-6">Daftar Pesanan Masuk</h2>
                <div class="space-y-6">
                    <?php if($orders): foreach($orders as $order): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <div class="flex flex-col md:flex-row justify-between md:items-center border-b border-gray-100 pb-4 mb-4 gap-4">
                                <div>
                                    <div class="flex items-center gap-3">
                                        <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded text-xs font-bold font-mono">#<?php echo $order->id; ?></span>
                                        <span class="text-sm text-gray-500"><?php echo date('d M Y H:i', strtotime($order->created_at)); ?></span>
                                    </div>
                                    <div class="font-bold text-gray-800 mt-1">Invoice Utama: <?php echo esc_html($order->kode_unik); ?></div>
                                </div>
                                <div>
                                    <?php
                                    $status_colors = [
                                        'menunggu_konfirmasi' => 'bg-yellow-100 text-yellow-700',
                                        'diproses'            => 'bg-blue-100 text-blue-700',
                                        'selesai'             => 'bg-green-100 text-green-700',
                                    ];
                                    $bg_class = isset($status_colors[$order->status_pesanan]) ? $status_colors[$order->status_pesanan] : 'bg-gray-100 text-gray-600';
                                    ?>
                                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide <?php echo $bg_class; ?>">
                                        <?php echo str_replace('_', ' ', $order->status_pesanan); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Penerima</h4>
                                    <p class="font-bold text-gray-800"><?php echo esc_html($order->nama_penerima); ?></p>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo esc_html($order->alamat_lengkap); ?></p>
                                </div>
                                <div class="text-right">
                                    <h4 class="text-xs font-bold text-gray-400 uppercase mb-2">Total Pesanan Toko</h4>
                                    <p class="text-2xl font-bold text-orange-600">Rp <?php echo number_format($order->sub_total, 0, ',', '.'); ?></p>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-50">Lihat Detail</button>
                                <?php if($order->status_pesanan == 'menunggu_konfirmasi'): ?>
                                    <button class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold hover:bg-green-700">Terima Pesanan</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="text-center py-12 text-gray-400">Belum ada pesanan masuk.</div>
                    <?php endif; ?>
                </div>
                <?php
                break;

            // ------------------------------------------------------------------
            // TAB 4: BELI PAKET TRANSAKSI
            // ------------------------------------------------------------------
            case 'paket':
                $pakets = $wpdb->get_results("SELECT * FROM $table_paket WHERE status = 'aktif' ORDER BY harga ASC");
                ?>
                <div class="text-center max-w-2xl mx-auto mb-10">
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Tambah Kuota Transaksi</h2>
                    <p class="text-gray-500">Pilih paket yang sesuai kebutuhan toko Anda. Tanpa biaya bulanan, bayar saat butuh saja.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php if($pakets): foreach($pakets as $pk): ?>
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden relative group hover:-translate-y-1 transition duration-300">
                        <div class="p-6 text-center">
                            <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html($pk->nama_paket); ?></h3>
                            <div class="my-4">
                                <span class="text-3xl font-bold text-gray-900">Rp <?php echo number_format($pk->harga / 1000, 0); ?>rb</span>
                            </div>
                            <div class="bg-orange-50 text-orange-700 py-2 px-4 rounded-lg font-bold inline-block mb-4">
                                <?php echo $pk->jumlah_transaksi; ?> Transaksi
                            </div>
                            <p class="text-sm text-gray-500"><?php echo esc_html($pk->deskripsi); ?></p>
                        </div>
                        <div class="p-4 bg-gray-50 border-t border-gray-100">
                            <form method="POST">
                                <input type="hidden" name="action" value="buy_package">
                                <input type="hidden" name="paket_id" value="<?php echo $pk->id; ?>">
                                <button type="submit" class="w-full bg-gray-900 text-white font-bold py-3 rounded-xl hover:bg-orange-600 transition" onclick="return confirm('Beli paket ini?');">
                                    Beli Sekarang
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                
                <div class="mt-12">
                    <h3 class="font-bold text-lg mb-4">Riwayat Pembelian</h3>
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500">
                                <tr>
                                    <th class="p-4">Paket</th>
                                    <th class="p-4">Harga</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4">Bukti Bayar</th>
                                    <th class="p-4 text-right">Tanggal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php 
                                $histories = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pembelian_paket WHERE id_pedagang = %d ORDER BY created_at DESC LIMIT 10", $pedagang->id));
                                if($histories): foreach($histories as $h): ?>
                                <tr>
                                    <td class="p-4 font-bold"><?php echo esc_html($h->nama_paket_snapshot); ?></td>
                                    <td class="p-4">Rp <?php echo number_format($h->harga_paket, 0, ',', '.'); ?></td>
                                    <td class="p-4">
                                        <span class="px-2 py-1 rounded text-xs font-bold capitalize <?php echo $h->status == 'disetujui' ? 'bg-green-100 text-green-700' : ($h->status == 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                                            <?php echo $h->status; ?>
                                        </span>
                                        <?php if($h->status == 'ditolak' && !empty($h->catatan_admin)): ?>
                                            <div class="mt-1 text-xs text-red-600 bg-red-50 p-2 rounded border border-red-100 max-w-xs">
                                                <strong>Alasan:</strong><br>
                                                <?php echo esc_html($h->catatan_admin); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4">
                                        <?php if($h->status == 'pending' || $h->status == 'ditolak'): ?>
                                            <?php if($h->url_bukti_bayar && $h->status == 'pending'): ?>
                                                <a href="<?php echo esc_url($h->url_bukti_bayar); ?>" target="_blank" class="text-blue-600 underline text-xs">Lihat Bukti</a>
                                                <br><span class="text-[10px] text-gray-400">Menunggu Verifikasi</span>
                                            <?php else: ?>
                                                <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-1">
                                                    <?php wp_nonce_field('upload_bukti_paket_nonce'); ?>
                                                    <input type="hidden" name="action" value="upload_bukti_paket">
                                                    <input type="hidden" name="pembelian_id" value="<?php echo $h->id; ?>">
                                                    <input type="file" name="bukti_bayar" class="text-[10px]" required accept="image/*">
                                                    <button type="submit" class="bg-blue-600 text-white px-2 py-1 rounded text-xs hover:bg-blue-700 w-fit">
                                                        <?php echo ($h->status == 'ditolak') ? 'Upload Ulang' : 'Upload'; ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php elseif($h->status == 'disetujui'): ?>
                                            <span class="text-green-600 text-xs"><i class="fas fa-check-circle"></i> Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="p-4 text-right text-gray-500"><?php echo date('d/m/Y', strtotime($h->created_at)); ?></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="5" class="p-4 text-center text-gray-400">Belum ada riwayat pembelian.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php
                break;

            // ------------------------------------------------------------------
            // TAB 5: PENGATURAN TOKO (LENGKAP)
            // ------------------------------------------------------------------
            case 'pengaturan':
                ?>
                <div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-4">Pengaturan Toko</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_shop">
                        
                        <!-- Bagian 1: Profil Toko -->
                        <div class="mb-8">
                            <h3 class="text-lg font-bold text-gray-700 mb-4">Profil Toko</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Toko</label>
                                    <input type="text" name="nama_toko" value="<?php echo esc_attr($pedagang->nama_toko); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Nomor WhatsApp (Aktif)</label>
                                    <input type="text" name="nomor_wa" value="<?php echo esc_attr($pedagang->nomor_wa); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Foto Profil Toko</label>
                                    <?php if($pedagang->foto_profil): ?>
                                        <div class="mb-2">
                                            <img src="<?php echo esc_url($pedagang->foto_profil); ?>" class="h-20 w-20 object-cover rounded-full border border-gray-200">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="foto_profil" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100" accept="image/*">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Alamat Lengkap Toko</label>
                                    <textarea name="alamat_lengkap" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors"><?php echo esc_textarea($pedagang->alamat_lengkap); ?></textarea>
                                </div>

                                <!-- DATA WILAYAH (AJAX) -->
                                <div class="md:col-span-2 border-t border-gray-100 pt-6 mt-2">
                                    <h4 class="text-sm font-bold text-gray-700 mb-3">Lokasi Wilayah (Untuk Ongkir)</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Provinsi</label>
                                            <select name="provinsi" id="dw-provinsi" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white" data-selected="<?php echo esc_attr($pedagang->api_provinsi_id); ?>">
                                                <option value="">Pilih Provinsi...</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Kabupaten/Kota</label>
                                            <select name="kabupaten" id="dw-kabupaten" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white disabled:bg-gray-100" disabled data-selected="<?php echo esc_attr($pedagang->api_kabupaten_id); ?>">
                                                <option value="">Pilih Kabupaten...</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Kecamatan</label>
                                            <select name="kecamatan" id="dw-kecamatan" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white disabled:bg-gray-100" disabled data-selected="<?php echo esc_attr($pedagang->api_kecamatan_id); ?>">
                                                <option value="">Pilih Kecamatan...</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-600 mb-1">Kelurahan</label>
                                            <select name="kelurahan" id="dw-kelurahan" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white disabled:bg-gray-100" disabled data-selected="<?php echo esc_attr($pedagang->api_kelurahan_id); ?>">
                                                <option value="">Pilih Kelurahan...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bagian 2: Info Bank & Pembayaran -->
                        <div class="border-t border-gray-100 pt-8">
                            <h3 class="text-lg font-bold text-gray-700 mb-4">Informasi Pembayaran (Bank & QRIS)</h3>
                            <p class="text-sm text-gray-500 mb-6">Data ini akan digunakan untuk menerima pembayaran dari pelanggan jika memilih metode transfer langsung ke toko.</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Nama Bank</label>
                                    <input type="text" name="nama_bank" value="<?php echo esc_attr($pedagang->nama_bank); ?>" placeholder="Contoh: BCA / Mandiri" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Nomor Rekening</label>
                                    <input type="text" name="no_rekening" value="<?php echo esc_attr($pedagang->no_rekening); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Atas Nama Rekening</label>
                                    <input type="text" name="atas_nama_rekening" value="<?php echo esc_attr($pedagang->atas_nama_rekening); ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 text-gray-900 bg-white focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-colors">
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Upload QRIS Toko</label>
                                    <?php if($pedagang->qris_image_url): ?>
                                        <div class="mb-2">
                                            <img src="<?php echo esc_url($pedagang->qris_image_url); ?>" class="h-32 object-contain border border-gray-200 rounded">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="qris_toko" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*">
                                    <p class="text-xs text-gray-400 mt-1">Upload gambar QRIS agar pelanggan bisa scan bayar.</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-100 flex justify-end">
                            <button type="submit" class="bg-gray-900 text-white px-8 py-3 rounded-xl font-bold hover:bg-orange-600 transition shadow-lg flex items-center gap-2">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Script AJAX Wilayah -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const selectProv = document.getElementById('dw-provinsi');
                    const selectKab  = document.getElementById('dw-kabupaten');
                    const selectKec  = document.getElementById('dw-kecamatan');
                    const selectKel  = document.getElementById('dw-kelurahan');
                    const ajaxUrl    = '<?php echo admin_url('admin-ajax.php'); ?>';

                    // Load Data Awal
                    if(selectProv) loadWilayah('dw_get_provinsi', selectProv, selectProv.getAttribute('data-selected'));

                    // Event Listeners
                    if(selectProv) {
                        selectProv.addEventListener('change', function() {
                            resetSelect(selectKab); resetSelect(selectKec); resetSelect(selectKel);
                            if(this.value) loadWilayah('dw_get_kabupaten', selectKab, null, {prov_id: this.value});
                        });
                    }
                    if(selectKab) {
                        selectKab.addEventListener('change', function() {
                            resetSelect(selectKec); resetSelect(selectKel);
                            if(this.value) loadWilayah('dw_get_kecamatan', selectKec, null, {kab_id: this.value});
                        });
                    }
                    if(selectKec) {
                        selectKec.addEventListener('change', function() {
                            resetSelect(selectKel);
                            if(this.value) loadWilayah('dw_get_kelurahan', selectKel, null, {kec_id: this.value});
                        });
                    }

                    function resetSelect(el) {
                        el.innerHTML = '<option value="">Pilih...</option>';
                        el.disabled = true;
                    }

                    function loadWilayah(action, targetEl, selectedId = null, extraParams = {}) {
                        let formData = new FormData();
                        formData.append('action', action);
                        for (let key in extraParams) formData.append(key, extraParams[key]);

                        fetch(ajaxUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if(data.success && data.data) {
                                targetEl.disabled = false;
                                let html = '<option value="">Pilih...</option>';
                                data.data.forEach(item => {
                                    let isSel = (selectedId && (item.id == selectedId || item.kode == selectedId)) ? 'selected' : '';
                                    html += `<option value="${item.id || item.kode}" ${isSel}>${item.nama}</option>`;
                                });
                                targetEl.innerHTML = html;
                                // Trigger change otomatis jika ada selectedId untuk load chain berikutnya
                                if(selectedId) targetEl.dispatchEvent(new Event('change'));
                            }
                        })
                        .catch(err => console.error('Error loading wilayah:', err));
                    }
                });
                </script>
                <?php
                break;
        } 
        ?>
        
    </div>
</div>

<?php get_footer(); ?>