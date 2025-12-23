<?php
/**
 * Template Name: Dashboard Toko (Merchant)
 * Description: Halaman manajemen toko lengkap dengan Integrasi API Wilayah & Database.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Cek Login
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url() );
    exit;
}

$current_user_id = get_current_user_id();
global $wpdb;
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

// 2. Handle Form Submit (Simpan Pengaturan Toko)
$msg = '';
$msg_class = '';

if ( isset($_POST['save_toko']) && wp_verify_nonce($_POST['toko_nonce'], 'save_toko_action') ) {
    
    // a. Siapkan Data Text
    $data = array(
        'nama_toko'         => sanitize_text_field($_POST['nama_toko']),
        'nama_pemilik'      => sanitize_text_field($_POST['nama_pemilik']),
        'nomor_wa'          => sanitize_text_field($_POST['nomor_wa']),
        'alamat_lengkap'    => sanitize_textarea_field($_POST['alamat_lengkap']),
        
        // Data Bank
        'nama_bank'          => sanitize_text_field($_POST['nama_bank']),
        'no_rekening'        => sanitize_text_field($_POST['no_rekening']),
        'atas_nama_rekening' => sanitize_text_field($_POST['atas_nama_rekening']),

        // Wilayah (ID dari API)
        'api_provinsi_id'    => sanitize_text_field($_POST['api_provinsi_id']),
        'api_kabupaten_id'   => sanitize_text_field($_POST['api_kabupaten_id']),
        'api_kecamatan_id'   => sanitize_text_field($_POST['api_kecamatan_id']),
        'api_kelurahan_id'   => sanitize_text_field($_POST['api_kelurahan_id']),

        // Wilayah (Nama Text)
        'provinsi_nama'      => sanitize_text_field($_POST['provinsi_nama']),
        'kabupaten_nama'     => sanitize_text_field($_POST['kabupaten_nama']),
        'kecamatan_nama'     => sanitize_text_field($_POST['kecamatan_nama']),
        'kelurahan_nama'     => sanitize_text_field($_POST['kelurahan_nama']),
        
        'updated_at'         => current_time('mysql')
    );

    // b. Handle Upload QRIS (Jika ada)
    if ( ! empty($_FILES['qris_image']['name']) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        $uploaded = wp_handle_upload( $_FILES['qris_image'], array( 'test_form' => false ) );
        if ( isset( $uploaded['url'] ) ) {
            $data['qris_image_url'] = $uploaded['url'];
        }
    }

    // c. Simpan ke Database
    $exist_id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM $table_pedagang WHERE id_user = %d", $current_user_id) );

    if ($exist_id) {
        $wpdb->update($table_pedagang, $data, ['id' => $exist_id]);
        $msg = 'Profil Toko berhasil diperbarui!';
        $msg_class = 'bg-green-100 text-green-700 border-green-200';
    } else {
        $data['id_user'] = $current_user_id;
        $data['slug_toko'] = sanitize_title($_POST['nama_toko']) . '-' . rand(100,999);
        $data['status_pendaftaran'] = 'menunggu_desa'; 
        $data['created_at'] = current_time('mysql');
        
        $wpdb->insert($table_pedagang, $data);
        $msg = 'Toko berhasil dibuat! Menunggu persetujuan.';
        $msg_class = 'bg-green-100 text-green-700 border-green-200';
    }
}

// 3. Ambil Data Toko (Untuk Pre-fill Form)
$toko = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_user = %d", $current_user_id) );

get_header(); 
?>

<div class="bg-gray-50 min-h-screen font-sans flex">
    
    <!-- ================= SIDEBAR ================= -->
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col fixed h-full z-20">
        <div class="p-6 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-primary text-white rounded-xl flex items-center justify-center text-xl shadow-lg shadow-primary/30">
                <i class="fas fa-store"></i>
            </div>
            <div>
                <h2 class="font-bold text-gray-800 leading-tight">Merchant</h2>
                <p class="text-[10px] text-gray-400">Panel Toko</p>
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
                <span id="sidebar-order-badge" class="hidden bg-red-500 text-white text-[10px] px-1.5 py-0.5 rounded-full ml-auto">0</span>
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

    <!-- ================= MOBILE HEADER ================= -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-30 flex items-center justify-between px-4 shadow-sm">
        <span class="font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-store text-primary"></i> Merchant Panel
        </span>
        <button onclick="document.querySelector('aside').classList.toggle('hidden'); document.querySelector('aside').classList.toggle('flex');" class="text-gray-600 p-2">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="flex-1 md:ml-64 p-4 md:p-8 pt-20 md:pt-8 overflow-y-auto h-screen pb-24">
        
        <!-- Notifikasi PHP -->
        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl border <?php echo $msg_class; ?>">
                <i class="fas fa-info-circle mr-2"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- 1. TAB RINGKASAN -->
        <div id="view-ringkasan" class="tab-content animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Ringkasan Penjualan</h1>
            
            <!-- Cards Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xl"><i class="fas fa-wallet"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Pendapatan</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="stat-sales">Rp 0</h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl"><i class="fas fa-shopping-cart"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Pesanan Baru</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="stat-orders">0</h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center text-xl"><i class="fas fa-box-open"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Produk Habis</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="stat-products">0</h3>
                    </div>
                </div>
            </div>

            <!-- Tabel Ringkasan Pesanan -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-gray-800">Pesanan Terbaru</h3>
                    <button onclick="switchTab('pesanan')" class="text-sm text-primary hover:underline font-medium">Lihat Semua</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 font-semibold">ID</th>
                                <th class="px-6 py-3 font-semibold">Tanggal</th>
                                <th class="px-6 py-3 font-semibold">Status</th>
                                <th class="px-6 py-3 font-semibold text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="recent-orders-body" class="divide-y divide-gray-50">
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. TAB PRODUK -->
        <div id="view-produk" class="tab-content hidden animate-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Produk Saya</h1>
                <button onclick="openProductModal()" class="bg-primary hover:bg-green-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 shadow-lg shadow-primary/20 transition hover:-translate-y-0.5">
                    <i class="fas fa-plus"></i> Tambah Produk
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="merchant-product-list">
                <div class="col-span-full py-12 text-center text-gray-400">
                    <i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2">Memuat produk...</p>
                </div>
            </div>
        </div>

        <!-- 3. TAB PESANAN -->
        <div id="view-pesanan" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pesanan Masuk</h1>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Kode Trx</th>
                                <th class="px-6 py-4">Pembeli</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Bukti Bayar</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="merchant-order-list" class="divide-y divide-gray-100">
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-400">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> Memuat pesanan...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 4. TAB PENGATURAN (UPDATED WITH REGION API & PHP FORM) -->
        <div id="view-pengaturan" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pengaturan Toko</h1>
            
            <div class="max-w-4xl bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('save_toko_action', 'toko_nonce'); ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- BAGIAN 1: PROFIL TOKO -->
                        <div>
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 border-b pb-2">
                                <i class="fas fa-store text-primary"></i> Identitas Toko
                            </h3>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nama Toko</label>
                                <input type="text" name="nama_toko" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary" 
                                       placeholder="Contoh: Keripik Singkong Barokah" value="<?php echo isset($toko->nama_toko) ? esc_attr($toko->nama_toko) : ''; ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nama Pemilik</label>
                                <input type="text" name="nama_pemilik" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary" 
                                       placeholder="Nama lengkap pemilik" value="<?php echo isset($toko->nama_pemilik) ? esc_attr($toko->nama_pemilik) : ''; ?>" required>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nomor WhatsApp</label>
                                <input type="text" name="nomor_wa" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary" 
                                       placeholder="0812..." value="<?php echo isset($toko->nomor_wa) ? esc_attr($toko->nomor_wa) : ''; ?>" required>
                            </div>
                            
                            <!-- ALAMAT & WILAYAH (INTEGRASI BARU) -->
                            <div class="mt-6">
                                <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 border-b pb-2">
                                    <i class="fas fa-map-marker-alt text-primary"></i> Alamat & Lokasi
                                </h3>
                                
                                <div class="mb-3">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Provinsi</label>
                                    <select name="api_provinsi_id" id="dw_provinsi" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary bg-white" required
                                            data-selected="<?php echo isset($toko->api_provinsi_id) ? esc_attr($toko->api_provinsi_id) : ''; ?>">
                                        <option value="">Memuat...</option>
                                    </select>
                                    <input type="hidden" name="provinsi_nama" id="input_provinsi_nama" value="<?php echo isset($toko->provinsi_nama) ? esc_attr($toko->provinsi_nama) : ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Kota/Kabupaten</label>
                                    <select name="api_kabupaten_id" id="dw_kota" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary bg-white" required disabled
                                            data-selected="<?php echo isset($toko->api_kabupaten_id) ? esc_attr($toko->api_kabupaten_id) : ''; ?>">
                                        <option value="">Pilih Provinsi Dulu</option>
                                    </select>
                                    <input type="hidden" name="kabupaten_nama" id="input_kabupaten_nama" value="<?php echo isset($toko->kabupaten_nama) ? esc_attr($toko->kabupaten_nama) : ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Kecamatan</label>
                                    <select name="api_kecamatan_id" id="dw_kecamatan" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary bg-white" required disabled
                                            data-selected="<?php echo isset($toko->api_kecamatan_id) ? esc_attr($toko->api_kecamatan_id) : ''; ?>">
                                        <option value="">Pilih Kota Dulu</option>
                                    </select>
                                    <input type="hidden" name="kecamatan_nama" id="input_kecamatan_nama" value="<?php echo isset($toko->kecamatan_nama) ? esc_attr($toko->kecamatan_nama) : ''; ?>">
                                </div>

                                <div class="mb-3">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Desa/Kelurahan</label>
                                    <select name="api_kelurahan_id" id="dw_desa" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary bg-white" required disabled
                                            data-selected="<?php echo isset($toko->api_kelurahan_id) ? esc_attr($toko->api_kelurahan_id) : ''; ?>">
                                        <option value="">Pilih Kecamatan Dulu</option>
                                    </select>
                                    <input type="hidden" name="kelurahan_nama" id="input_kelurahan_nama" value="<?php echo isset($toko->kelurahan_nama) ? esc_attr($toko->kelurahan_nama) : ''; ?>">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Alamat Lengkap</label>
                                    <textarea name="alamat_lengkap" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary" 
                                              placeholder="Nama Jalan, RT/RW, Patokan"><?php echo isset($toko->alamat_lengkap) ? esc_textarea($toko->alamat_lengkap) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- BAGIAN 2: KEUANGAN -->
                        <div>
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 border-b pb-2">
                                <i class="fas fa-wallet text-primary"></i> Rekening & Pembayaran
                            </h3>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Nama Bank</label>
                                    <input type="text" name="nama_bank" placeholder="BCA/BRI" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary"
                                           value="<?php echo isset($toko->nama_bank) ? esc_attr($toko->nama_bank) : ''; ?>">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Nomor Rekening</label>
                                    <input type="text" name="no_rekening" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary"
                                           value="<?php echo isset($toko->no_rekening) ? esc_attr($toko->no_rekening) : ''; ?>">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Atas Nama Rekening</label>
                                <input type="text" name="atas_nama_rekening" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary"
                                       value="<?php echo isset($toko->atas_nama_rekening) ? esc_attr($toko->atas_nama_rekening) : ''; ?>">
                            </div>
                            
                            <!-- QRIS Upload -->
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">QRIS (Opsional)</label>
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-20 bg-gray-100 rounded-lg border border-dashed border-gray-300 flex items-center justify-center overflow-hidden relative group">
                                        <?php if(!empty($toko->qris_image_url)): ?>
                                            <img id="preview_qris" src="<?php echo esc_url($toko->qris_image_url); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-qrcode text-gray-400 text-2xl" id="icon_qris"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" name="qris_image" accept="image/*" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-green-700">
                                        <p class="text-[10px] text-gray-400 mt-1">Upload gambar QRIS agar pembeli bisa scan.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-100 text-right">
                        <button type="submit" name="save_toko" class="bg-gray-900 text-white font-bold py-3 px-8 rounded-xl hover:bg-gray-800 transition shadow-lg flex items-center gap-2 ml-auto">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<!-- ================= MODAL PRODUK & BUKTI (TETAP SAMA SEPERTI ASLINYA) ================= -->
<div id="modal-produk" class="fixed inset-0 z-[50] hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeProductModal()"></div>
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
                        <input type="text" name="nama_produk" id="prod_nama" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Harga (Rp)</label>
                            <input type="number" name="harga" id="prod_harga" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Stok</label>
                            <input type="number" name="stok" id="prod_stok" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Berat (Gram)</label>
                            <input type="number" name="berat_gram" id="prod_berat" placeholder="500" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 mb-1">Kondisi</label>
                            <select name="kondisi" id="prod_kondisi" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:ring-primary focus:border-primary">
                                <option value="baru">Baru</option>
                                <option value="bekas">Bekas</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Kategori</label>
                        <select name="kategori" id="prod_kategori" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 bg-white focus:ring-primary focus:border-primary">
                            <option value="Makanan">Makanan & Minuman</option>
                            <option value="Kerajinan">Kerajinan Tangan</option>
                            <option value="Fashion">Fashion & Aksesoris</option>
                            <option value="Pertanian">Hasil Tani</option>
                            <option value="Souvenir">Souvenir</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Deskripsi</label>
                        <textarea name="deskripsi" id="prod_deskripsi" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Foto Produk</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:bg-gray-50 transition cursor-pointer relative">
                            <input type="file" name="foto_utama" id="prod_foto" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 mb-2"></i>
                            <p class="text-xs text-gray-500">Klik untuk upload gambar</p>
                        </div>
                    </div>
                </div>
                <div class="mt-8 border-t border-gray-100 pt-6">
                    <button type="submit" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3.5 rounded-xl transition flex justify-center gap-2 shadow-lg shadow-green-500/20">
                        <span id="btn-save-text">Simpan Produk</span>
                        <i id="btn-save-loader" class="fas fa-spinner fa-spin hidden"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modal-bukti" class="fixed inset-0 z-[60] hidden transition-opacity duration-300">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity" onclick="closeProofModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col pointer-events-auto transform transition-all scale-95 opacity-0" id="modal-bukti-content">
            <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-white rounded-t-2xl z-10">
                <h3 class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-receipt text-primary"></i> Bukti Pembayaran</h3>
                <button onclick="closeProofModal()" class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-500 transition"><i class="fas fa-times"></i></button>
            </div>
            <div class="flex-1 overflow-auto p-4 bg-gray-100 flex items-center justify-center">
                <img id="img-bukti-bayar" src="" alt="Bukti Pembayaran" class="max-w-full max-h-full object-contain rounded-lg shadow-sm">
            </div>
            <div class="p-4 border-t border-gray-100 bg-white rounded-b-2xl text-center">
                <a id="link-download-bukti" href="#" target="_blank" class="inline-flex items-center gap-2 text-sm text-primary hover:text-green-700 font-bold px-4 py-2 rounded-lg hover:bg-green-50 transition">
                    <i class="fas fa-external-link-alt"></i> Buka Ukuran Asli
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Utilities */
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.active-tab { background-color: #f0fdf4; color: #16a34a; border-right: 3px solid #16a34a; }
</style>

<script>
// 1. Tab Switcher
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active-tab'));
    
    document.getElementById('view-' + tabName).classList.remove('hidden');
    document.getElementById('nav-' + tabName).classList.add('active-tab');

    // Trigger Load Data
    if(tabName === 'produk' && typeof loadMerchantProducts === 'function') loadMerchantProducts();
    if(tabName === 'pesanan' && typeof loadMerchantOrders === 'function') loadMerchantOrders();
    // if(tabName === 'pengaturan') // Pengaturan sekarang dihandle PHP langsung, tidak butuh AJAX load profile
}

// 2. Modal Produk Logic
const modalP = document.getElementById('modal-produk');
const panelP = document.getElementById('modal-produk-panel');

function openProductModal(data = null) {
    modalP.classList.remove('hidden');
    setTimeout(() => panelP.classList.remove('translate-x-full'), 10);
    
    document.getElementById('form-product').reset();
    document.getElementById('prod_id').value = '';
    document.getElementById('modal-title').innerText = 'Tambah Produk';

    if(data) {
        document.getElementById('modal-title').innerText = 'Edit Produk';
        document.getElementById('prod_id').value = data.id;
        document.getElementById('prod_nama').value = data.nama_produk;
        document.getElementById('prod_harga').value = data.harga;
        document.getElementById('prod_stok').value = data.stok;
        document.getElementById('prod_kategori').value = data.kategori;
        document.getElementById('prod_deskripsi').value = data.deskripsi;
        
        if(document.getElementById('prod_berat')) document.getElementById('prod_berat').value = data.berat_gram || '';
        if(document.getElementById('prod_kondisi')) document.getElementById('prod_kondisi').value = data.kondisi || 'baru';
    }
}

function closeProductModal() {
    panelP.classList.add('translate-x-full');
    setTimeout(() => modalP.classList.add('hidden'), 300);
}

// 3. Init
document.addEventListener('DOMContentLoaded', () => {
    switchTab('ringkasan');
    if(typeof loadMerchantSummary === 'function') loadMerchantSummary();
});
</script>

<?php wp_footer(); ?>