<?php
/**
 * Template Name: Dashboard Toko (Merchant)
 * Description: Panel untuk pedagang mengelola toko, produk, dan pesanan.
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$user = wp_get_current_user();
// Jika perlu validasi role khusus:
// if (!in_array('pedagang', $user->roles)) wp_redirect(home_url('/akun-saya'));

get_header(); 
?>

<div class="bg-gray-50 min-h-screen font-sans flex">
    
    <!-- SIDEBAR -->
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

    <!-- MOBILE HEADER -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-30 flex items-center justify-between px-4 shadow-sm">
        <span class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-store text-primary"></i> Merchant Panel</span>
        <button onclick="document.querySelector('aside').classList.toggle('hidden'); document.querySelector('aside').classList.toggle('flex');" class="text-gray-600 p-2">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 md:ml-64 p-4 md:p-8 pt-20 md:pt-8 overflow-y-auto h-screen">
        
        <!-- TAB: RINGKASAN -->
        <div id="view-ringkasan" class="tab-content animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Ringkasan Penjualan</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Stat Cards -->
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

            <!-- Tabel Pesanan Terbaru -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-gray-800">Pesanan Terbaru</h3>
                    <button onclick="switchTab('pesanan')" class="text-sm text-primary hover:underline font-medium">Lihat Semua</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-3 font-semibold">ID Pesanan</th>
                                <th class="px-6 py-3 font-semibold">Tanggal</th>
                                <th class="px-6 py-3 font-semibold">Status</th>
                                <th class="px-6 py-3 font-semibold text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="recent-orders-body" class="divide-y divide-gray-50">
                            <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: PRODUK -->
        <div id="view-produk" class="tab-content hidden animate-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Produk Saya</h1>
                <button onclick="openProductModal()" class="bg-primary hover:bg-green-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 shadow-lg shadow-primary/20 transition hover:-translate-y-0.5">
                    <i class="fas fa-plus"></i> Tambah Produk
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="merchant-product-list">
                <!-- Data Produk AJAX -->
            </div>
        </div>

        <!-- TAB: PESANAN -->
        <div id="view-pesanan" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pesanan Masuk</h1>
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-500 uppercase font-bold text-xs border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">ID</th>
                                <th class="px-6 py-4">Pembeli</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Total</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="merchant-order-list" class="divide-y divide-gray-100">
                            <!-- Data AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: PENGATURAN -->
        <div id="view-pengaturan" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Pengaturan Toko</h1>
            
            <div class="max-w-2xl bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                <form id="form-settings-toko">
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nama Toko</label>
                        <input type="text" id="set_nama_toko" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary transition outline-none">
                    </div>
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi Singkat</label>
                        <textarea id="set_deskripsi_toko" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-primary focus:border-primary transition outline-none"></textarea>
                    </div>
                    
                    <div class="border-t border-gray-100 pt-6 mt-6">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-credit-card text-primary"></i> Informasi Pembayaran (Transfer)</h3>
                        <p class="text-xs text-gray-500 mb-4">Informasi ini akan ditampilkan kepada pembeli saat checkout.</p>
                        
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nama Bank</label>
                                <input type="text" id="set_nama_bank" placeholder="BCA/BRI" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nomor Rekening</label>
                                <input type="text" id="set_no_rekening" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-xs font-bold text-gray-500 mb-1">Atas Nama</label>
                            <input type="text" id="set_atas_nama" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-gray-900 text-white font-bold py-3.5 rounded-xl hover:bg-gray-800 transition shadow-lg mt-4">Simpan Perubahan</button>
                </form>
            </div>
        </div>

    </main>
</div>

<!-- MODAL TAMBAH/EDIT PRODUK -->
<div id="modal-produk" class="fixed inset-0 z-50 hidden transition-opacity duration-300">
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
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nama Produk</label>
                        <input type="text" name="nama_produk" id="prod_nama" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Harga (Rp)</label>
                            <input type="number" name="harga" id="prod_harga" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-1">Stok</label>
                            <input type="number" name="stok" id="prod_stok" required class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Kategori</label>
                        <select name="kategori" id="prod_kategori" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary bg-white">
                            <option value="Makanan">Makanan & Minuman</option>
                            <option value="Kerajinan">Kerajinan Tangan</option>
                            <option value="Fashion">Fashion & Aksesoris</option>
                            <option value="Pertanian">Hasil Tani</option>
                            <option value="Souvenir">Souvenir</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi</label>
                        <textarea name="deskripsi" id="prod_deskripsi" rows="4" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-primary"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Foto Produk</label>
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

<style>
/* Animasi Fade In */
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
/* Active Tab Style */
.active-tab { background-color: #f0fdf4; color: #16a34a; border-right: 3px solid #16a34a; }
</style>

<script>
// Script Sederhana untuk Tab Switching & Modal
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active-tab'));
    
    document.getElementById('view-' + tabName).classList.remove('hidden');
    document.getElementById('nav-' + tabName).classList.add('active-tab');

    // Load Data
    if(tabName === 'produk') loadMerchantProducts();
    if(tabName === 'pesanan') loadMerchantOrders();
    if(tabName === 'pengaturan') loadMerchantProfile();
}

// Modal
const modal = document.getElementById('modal-produk');
const panel = document.getElementById('modal-produk-panel');

function openProductModal(data = null) {
    modal.classList.remove('hidden');
    setTimeout(() => panel.classList.remove('translate-x-full'), 10);
    
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
    }
}

function closeProductModal() {
    panel.classList.add('translate-x-full');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    switchTab('ringkasan');
    if(typeof loadMerchantSummary === 'function') loadMerchantSummary();
});
</script>

<?php wp_footer(); ?>