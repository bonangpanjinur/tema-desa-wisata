<?php
/**
 * Template Name: Dashboard Toko (Merchant)
 * Description: Halaman manajemen toko lengkap sesuai struktur database dw_pedagang & dw_produk.
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$user = wp_get_current_user();
// Validasi role (Opsional)
// if (!in_array('pedagang', $user->roles)) wp_redirect(home_url('/akun-saya'));

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

            <!-- Grid Produk (Data via AJAX main.js) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="merchant-product-list">
                <!-- Placeholder Loading -->
                <div class="col-span-full py-12 text-center text-gray-400">
                    <i class="fas fa-spinner fa-spin text-2xl"></i><p class="mt-2">Memuat produk...</p>
                </div>
            </div>
        </div>

        <!-- 3. TAB PESANAN (TABEL LENGKAP) -->
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
                            <!-- Data via AJAX main.js -->
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

        <!-- 4. TAB PENGATURAN (COMPLETE WITH DATABASE FIELDS) -->
        <div id="view-pengaturan" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pengaturan Toko</h1>
            
            <div class="max-w-4xl bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                <form id="form-settings-toko" enctype="multipart/form-data">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- BAGIAN 1: PROFIL TOKO -->
                        <div>
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 border-b pb-2">
                                <i class="fas fa-store text-primary"></i> Identitas Toko
                            </h3>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nama Toko</label>
                                <input type="text" id="set_nama_toko" name="nama_toko" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary" placeholder="Contoh: Keripik Singkong Barokah">
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nama Pemilik</label>
                                <input type="text" id="set_nama_pemilik" name="nama_pemilik" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary" placeholder="Nama lengkap pemilik">
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nomor WhatsApp</label>
                                <input type="text" id="set_nomor_wa" name="nomor_wa" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary" placeholder="0812...">
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Alamat Lengkap</label>
                                <textarea id="set_alamat_lengkap" name="alamat_lengkap" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary focus:border-primary" placeholder="Alamat lengkap toko"></textarea>
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
                                    <input type="text" id="set_nama_bank" name="nama_bank" placeholder="BCA/BRI" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 mb-1">Nomor Rekening</label>
                                    <input type="text" id="set_no_rekening" name="no_rekening" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Atas Nama Rekening</label>
                                <input type="text" id="set_atas_nama" name="atas_nama_rekening" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 outline-none focus:ring-primary">
                            </div>
                            
                            <!-- QRIS Upload -->
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 mb-1">QRIS (Opsional)</label>
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-20 bg-gray-100 rounded-lg border border-dashed border-gray-300 flex items-center justify-center overflow-hidden relative group">
                                        <img id="preview_qris" src="" class="w-full h-full object-cover hidden">
                                        <i class="fas fa-qrcode text-gray-400 text-2xl" id="icon_qris"></i>
                                    </div>
                                    <div class="flex-1">
                                        <input type="file" name="qris_image" id="set_qris_image" accept="image/*" class="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-green-700">
                                        <p class="text-[10px] text-gray-400 mt-1">Upload gambar QRIS agar pembeli bisa scan.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-100 text-right">
                        <button type="submit" class="bg-gray-900 text-white font-bold py-3 px-8 rounded-xl hover:bg-gray-800 transition shadow-lg flex items-center gap-2 ml-auto">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<!-- ================= MODAL PRODUK (Form Tambah/Edit Lengkap) ================= -->
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
                    
                    <!-- Nama Produk -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Nama Produk</label>
                        <input type="text" name="nama_produk" id="prod_nama" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary">
                    </div>

                    <!-- Harga & Stok -->
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

                    <!-- Berat & Kondisi (Tambahan Sesuai DB) -->
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

                    <!-- Kategori -->
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

                    <!-- Deskripsi -->
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Deskripsi</label>
                        <textarea name="deskripsi" id="prod_deskripsi" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary focus:border-primary"></textarea>
                    </div>

                    <!-- Foto Produk -->
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

<!-- ================= MODAL BUKTI BAYAR ================= -->
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

<!-- ================= SCRIPTS ================= -->
<script>
// 1. Tab Switcher
function switchTab(tabName) {
    // Hide all
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active-tab'));
    
    // Show selected
    document.getElementById('view-' + tabName).classList.remove('hidden');
    document.getElementById('nav-' + tabName).classList.add('active-tab');

    // Trigger Load Data (Functions from main.js)
    if(tabName === 'produk' && typeof loadMerchantProducts === 'function') loadMerchantProducts();
    if(tabName === 'pesanan' && typeof loadMerchantOrders === 'function') loadMerchantOrders();
    if(tabName === 'pengaturan' && typeof loadMerchantProfile === 'function') loadMerchantProfile();
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

    // Populate data if Edit mode
    if(data) {
        document.getElementById('modal-title').innerText = 'Edit Produk';
        document.getElementById('prod_id').value = data.id;
        document.getElementById('prod_nama').value = data.nama_produk;
        document.getElementById('prod_harga').value = data.harga;
        document.getElementById('prod_stok').value = data.stok;
        document.getElementById('prod_kategori').value = data.kategori;
        document.getElementById('prod_deskripsi').value = data.deskripsi;
        
        // New fields
        if(document.getElementById('prod_berat')) document.getElementById('prod_berat').value = data.berat_gram || '';
        if(document.getElementById('prod_kondisi')) document.getElementById('prod_kondisi').value = data.kondisi || 'baru';
    }
}

function closeProductModal() {
    panelP.classList.add('translate-x-full');
    setTimeout(() => modalP.classList.add('hidden'), 300);
}

// 3. Modal Bukti Logic (Called by main.js)
// Elemen HTML sudah ada di file ini, jadi main.js bisa akses window.viewProof

// 4. Init
document.addEventListener('DOMContentLoaded', () => {
    switchTab('ringkasan');
    // Load summary if main.js loaded
    if(typeof loadMerchantSummary === 'function') loadMerchantSummary();
});
</script>

<?php wp_footer(); ?>