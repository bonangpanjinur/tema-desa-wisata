<?php
/**
 * Template Name: Dashboard Toko
 */

// Proteksi Halaman
if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
    exit;
}

$user = wp_get_current_user();
// Pastikan hanya pedagang atau admin yang bisa akses
if ( ! in_array( 'pedagang', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
    wp_redirect( home_url('/akun-saya') );
    exit;
}

get_header(); 
?>

<div class="bg-gray-100 min-h-screen pb-20">
    
    <!-- Top Bar -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg text-gray-800 flex items-center gap-2">
                <i class="ph-fill ph-storefront text-primary text-xl"></i> 
                <span>Dashboard Toko</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs text-gray-500">Halo,</p>
                    <p class="text-sm font-bold text-gray-800"><?php echo esc_html($user->display_name); ?></p>
                </div>
                <img src="<?php echo get_avatar_url($user->ID); ?>" class="w-9 h-9 rounded-full border border-gray-200">
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-6">
            
            <!-- Sidebar Navigation -->
            <aside class="w-full lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden sticky top-24">
                    <nav class="flex flex-col p-2 space-y-1">
                        <a href="#ringkasan" class="dash-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-gray-600 hover:bg-green-50 hover:text-primary transition-colors active-tab">
                            <i class="ph-bold ph-squares-four text-lg"></i> Ringkasan
                        </a>
                        <a href="#produk" class="dash-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-gray-600 hover:bg-green-50 hover:text-primary transition-colors">
                            <i class="ph-bold ph-package text-lg"></i> Produk Saya
                        </a>
                        <a href="#pesanan" class="dash-link flex items-center justify-between px-4 py-3 rounded-lg text-sm font-medium text-gray-600 hover:bg-green-50 hover:text-primary transition-colors">
                            <div class="flex items-center gap-3"><i class="ph-bold ph-shopping-bag text-lg"></i> Pesanan</div>
                            <span id="sidebar-order-badge" class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full hidden">0</span>
                        </a>
                        <a href="#pengaturan" class="dash-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-gray-600 hover:bg-green-50 hover:text-primary transition-colors">
                            <i class="ph-bold ph-gear text-lg"></i> Pengaturan Toko
                        </a>
                        <div class="border-t border-gray-100 my-2"></div>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors">
                            <i class="ph-bold ph-sign-out text-lg"></i> Keluar
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="flex-1 min-w-0">
                
                <!-- VIEW: RINGKASAN -->
                <div id="view-ringkasan" class="dash-view space-y-6">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                            <div class="relative z-10">
                                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-1">Total Penjualan</p>
                                <h3 class="text-2xl font-bold text-gray-900" id="stat-sales">Rp 0</h3>
                            </div>
                            <i class="ph-fill ph-currency-dollar absolute right-4 bottom-4 text-4xl text-green-100"></i>
                        </div>
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                            <div class="relative z-10">
                                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-1">Pesanan Baru</p>
                                <h3 class="text-2xl font-bold text-gray-900" id="stat-orders">0</h3>
                            </div>
                            <i class="ph-fill ph-shopping-cart absolute right-4 bottom-4 text-4xl text-blue-100"></i>
                        </div>
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                            <div class="relative z-10">
                                <p class="text-gray-500 text-xs font-medium uppercase tracking-wider mb-1">Produk Aktif</p>
                                <h3 class="text-2xl font-bold text-gray-900" id="stat-products">0</h3>
                            </div>
                            <i class="ph-fill ph-package absolute right-4 bottom-4 text-4xl text-orange-100"></i>
                        </div>
                    </div>

                    <!-- Recent Orders Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800">Pesanan Terbaru</h3>
                            <button onclick="$('a[href=\'#pesanan\']').click()" class="text-xs text-primary font-bold hover:underline">Lihat Semua</button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-gray-600">
                                <thead class="bg-gray-50 text-gray-800 font-semibold uppercase text-xs">
                                    <tr>
                                        <th class="px-6 py-3">ID Pesanan</th>
                                        <th class="px-6 py-3">Tanggal</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100" id="recent-orders-body">
                                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-400">Memuat data...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- VIEW: PRODUK -->
                <div id="view-produk" class="dash-view hidden space-y-6">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <h2 class="text-xl font-bold text-gray-800">Daftar Produk</h2>
                        <button onclick="openProductModal()" class="w-full sm:w-auto bg-primary text-white px-5 py-2.5 rounded-lg shadow hover:bg-secondary transition flex items-center justify-center gap-2">
                            <i class="ph-bold ph-plus"></i> Tambah Produk
                        </button>
                    </div>
                    
                    <!-- Product Grid Container -->
                    <div id="merchant-product-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Content loaded via JS -->
                        <div class="col-span-full py-12 text-center text-gray-400">Memuat produk...</div>
                    </div>
                </div>
                 
                <!-- VIEW: PESANAN -->
                <div id="view-pesanan" class="dash-view hidden space-y-6">
                    <h2 class="text-xl font-bold text-gray-800">Kelola Pesanan</h2>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- Tabs Status Pesanan -->
                        <div class="flex border-b border-gray-100 overflow-x-auto">
                            <button class="order-filter-btn px-6 py-3 text-sm font-medium text-primary border-b-2 border-primary whitespace-nowrap" data-status="">Semua</button>
                            <button class="order-filter-btn px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap" data-status="menunggu_konfirmasi">Perlu Konfirmasi</button>
                            <button class="order-filter-btn px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap" data-status="diproses">Diproses</button>
                            <button class="order-filter-btn px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap" data-status="dikirim_ekspedisi">Dikirim</button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-gray-600">
                                <thead class="bg-gray-50 text-gray-800 font-semibold uppercase text-xs">
                                    <tr>
                                        <th class="px-6 py-3">Info Produk</th>
                                        <th class="px-6 py-3">Pembeli</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3">Total</th>
                                        <th class="px-6 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100" id="merchant-order-list">
                                    <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Memuat pesanan...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- VIEW: PENGATURAN -->
                <div id="view-pengaturan" class="dash-view hidden max-w-2xl">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-xl font-bold text-gray-800 mb-6 border-b border-gray-100 pb-4">Pengaturan Toko</h2>
                        
                        <form id="form-settings-toko" class="space-y-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                                <input type="text" name="nama_toko" id="set_nama_toko" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Toko</label>
                                <textarea name="deskripsi_toko" id="set_deskripsi_toko" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bank</label>
                                    <input type="text" name="nama_bank" id="set_nama_bank" placeholder="Contoh: BRI" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Rekening</label>
                                    <input type="text" name="no_rekening" id="set_no_rekening" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Atas Nama Rekening</label>
                                <input type="text" name="atas_nama_rekening" id="set_atas_nama" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            </div>

                            <div class="pt-4">
                                <button type="submit" class="bg-primary text-white px-6 py-2.5 rounded-lg font-bold hover:bg-secondary transition w-full sm:w-auto">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </main>
        </div>
    </div>
</div>

<!-- MODAL: TAMBAH/EDIT PRODUK -->
<div id="modal-product" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm">
    <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
            <h3 class="text-lg font-bold text-gray-800" id="modal-title">Tambah Produk</h3>
            <button onclick="closeProductModal()" class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-red-50 hover:text-red-500 transition">
                <i class="ph-bold ph-x"></i>
            </button>
        </div>
        
        <div class="p-6">
            <form id="form-product" enctype="multipart/form-data">
                <input type="hidden" name="id" id="prod_id">
                
                <div class="space-y-4">
                    <!-- Upload Gambar -->
                    <div class="flex justify-center mb-4">
                        <div class="relative w-32 h-32 bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center overflow-hidden hover:border-primary group cursor-pointer">
                            <input type="file" name="gambar" id="prod_gambar" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer z-10" onchange="previewImage(this)">
                            <img id="preview-img" src="" class="absolute inset-0 w-full h-full object-cover hidden">
                            <i class="ph-duotone ph-image text-3xl text-gray-400 mb-1 group-hover:text-primary"></i>
                            <span class="text-[10px] text-gray-500 font-medium">Upload Foto</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nama Produk</label>
                        <input type="text" name="nama_produk" id="prod_nama" required class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-primary focus:border-primary text-sm">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Harga (Rp)</label>
                            <input type="number" name="harga_dasar" id="prod_harga" required class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-primary focus:border-primary text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Stok</label>
                            <input type="number" name="stok" id="prod_stok" required class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-primary focus:border-primary text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Deskripsi</label>
                        <textarea name="deskripsi" id="prod_deskripsi" rows="3" class="w-full border-gray-300 rounded-lg p-2.5 focus:ring-primary focus:border-primary text-sm"></textarea>
                    </div>
                </div>

                <div class="mt-8">
                    <button type="submit" class="w-full bg-primary text-white py-3 rounded-xl font-bold hover:bg-secondary transition shadow-lg shadow-primary/30 flex justify-center items-center gap-2">
                        <span id="btn-save-text">Simpan Produk</span>
                        <i id="btn-save-loader" class="ph-bold ph-spinner animate-spin hidden"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPT PENDUKUNG UI -->
<script>
    function openProductModal(editData = null) {
        const modal = document.getElementById('modal-product');
        const form = document.getElementById('form-product');
        const title = document.getElementById('modal-title');
        const preview = document.getElementById('preview-img');

        form.reset();
        preview.src = '';
        preview.classList.add('hidden');

        if (editData) {
            title.textContent = 'Edit Produk';
            document.getElementById('prod_id').value = editData.id;
            document.getElementById('prod_nama').value = editData.nama_produk;
            document.getElementById('prod_harga').value = editData.harga_dasar;
            document.getElementById('prod_stok').value = editData.stok;
            document.getElementById('prod_deskripsi').value = editData.deskripsi; // Sesuaikan key jika beda
            
            if(editData.gambar_unggulan && editData.gambar_unggulan.thumbnail) {
                preview.src = editData.gambar_unggulan.thumbnail;
                preview.classList.remove('hidden');
            }
        } else {
            title.textContent = 'Tambah Produk Baru';
            document.getElementById('prod_id').value = '';
        }

        modal.classList.remove('hidden');
    }

    function closeProductModal() {
        document.getElementById('modal-product').classList.add('hidden');
    }

    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('preview-img');
                img.src = e.target.result;
                img.classList.remove('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Tab Switcher Simple
    jQuery(document).ready(function($){
        // Tab Sidebar
        $('.dash-link').click(function(e){
            e.preventDefault();
            $('.dash-link').removeClass('bg-green-50 text-primary active-tab');
            $(this).addClass('bg-green-50 text-primary active-tab');
            
            var target = $(this).attr('href').replace('#', 'view-');
            $('.dash-view').addClass('hidden');
            $('#' + target).removeClass('hidden');

            // Trigger data load based on view
            if(target === 'view-produk') loadMerchantProducts();
            if(target === 'view-pesanan') loadMerchantOrders();
            if(target === 'view-pengaturan') loadMerchantProfile();
        });

        // Tab Filter Pesanan
        $('.order-filter-btn').click(function(){
            $('.order-filter-btn').removeClass('border-primary text-primary').addClass('text-gray-500 border-transparent');
            $(this).removeClass('text-gray-500 border-transparent').addClass('border-primary text-primary');
            loadMerchantOrders($(this).data('status'));
        });

        // Load Ringkasan on Start
        loadMerchantSummary();
    });
</script>

<?php get_footer(); ?>