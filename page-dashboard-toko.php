<?php
/**
 * Template Name: Dashboard Toko
 */

// Proteksi Halaman: Hanya user login & role pedagang
if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
    exit;
}

$user = wp_get_current_user();
// Cek Role (Sederhana)
if ( ! in_array( 'pedagang', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
    // Jika user login tapi bukan pedagang, arahkan ke akun saya (pembeli)
    // wp_redirect( home_url('/akun-saya') ); // Enable jika halaman akun saya sudah ada
}

get_header(); 
?>

<div class="bg-gray-100 min-h-screen">
    
    <!-- Top Bar -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg text-gray-700">
                <i class="fas fa-store text-primary mr-2"></i> Dashboard Toko
            </div>
            <div class="text-sm text-gray-500">
                Halo, <span class="font-semibold text-gray-800"><?php echo esc_html($user->display_name); ?></span>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col md:flex-row gap-6">
            
            <!-- Sidebar Navigation -->
            <aside class="w-full md:w-64 flex-shrink-0">
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <nav class="flex flex-col">
                        <a href="#ringkasan" class="dash-link px-4 py-3 border-l-4 border-primary bg-green-50 text-primary font-medium hover:bg-gray-50 transition">
                            <i class="fas fa-home w-6"></i> Ringkasan
                        </a>
                        <a href="#produk" class="dash-link px-4 py-3 border-l-4 border-transparent text-gray-600 hover:text-primary hover:bg-gray-50 transition">
                            <i class="fas fa-box w-6"></i> Produk Saya
                        </a>
                        <a href="#pesanan" class="dash-link px-4 py-3 border-l-4 border-transparent text-gray-600 hover:text-primary hover:bg-gray-50 transition flex justify-between items-center">
                            <span><i class="fas fa-shopping-bag w-6"></i> Pesanan Masuk</span>
                            <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">2</span>
                        </a>
                        <a href="#pengaturan" class="dash-link px-4 py-3 border-l-4 border-transparent text-gray-600 hover:text-primary hover:bg-gray-50 transition">
                            <i class="fas fa-cog w-6"></i> Pengaturan Toko
                        </a>
                        <a href="<?php echo wp_logout_url(home_url()); ?>" class="px-4 py-3 border-t border-gray-100 text-red-600 hover:bg-red-50 transition">
                            <i class="fas fa-sign-out-alt w-6"></i> Keluar
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Main Content Area -->
            <main class="flex-1">
                
                <!-- CONTENT: RINGKASAN -->
                <div id="view-ringkasan" class="dash-view">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <!-- Stat Card -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-blue-500">
                            <div class="text-gray-500 text-sm mb-1">Total Penjualan</div>
                            <div class="text-2xl font-bold text-gray-800">Rp 1.500.000</div>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-green-500">
                            <div class="text-gray-500 text-sm mb-1">Pesanan Selesai</div>
                            <div class="text-2xl font-bold text-gray-800">12</div>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow-sm border-l-4 border-yellow-500">
                            <div class="text-gray-500 text-sm mb-1">Perlu Dikirim</div>
                            <div class="text-2xl font-bold text-gray-800">2</div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="font-bold text-lg text-gray-800 mb-4">Pesanan Terbaru</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-gray-600">
                                <thead class="bg-gray-50 text-gray-800 font-semibold uppercase text-xs">
                                    <tr>
                                        <th class="px-4 py-3">ID Pesanan</th>
                                        <th class="px-4 py-3">Tanggal</th>
                                        <th class="px-4 py-3">Pelanggan</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3 text-right">Total</th>
                                        <th class="px-4 py-3">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <!-- Dummy Data -->
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">#ORD-001</td>
                                        <td class="px-4 py-3">12 Des 2024</td>
                                        <td class="px-4 py-3">Budi Santoso</td>
                                        <td class="px-4 py-3"><span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded text-xs">Perlu Dikirim</span></td>
                                        <td class="px-4 py-3 text-right">Rp 150.000</td>
                                        <td class="px-4 py-3"><button class="text-primary hover:underline">Detail</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- CONTENT: PRODUK (Placeholder) -->
                <div id="view-produk" class="dash-view hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Produk Saya</h2>
                        <button class="bg-primary text-white px-4 py-2 rounded shadow hover:bg-secondary">
                            <i class="fas fa-plus mr-1"></i> Tambah Produk
                        </button>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-10 text-center text-gray-500">
                        <p>Fitur manajemen produk akan muncul di sini (load via AJAX).</p>
                    </div>
                </div>
                 
                 <!-- CONTENT: PESANAN (Placeholder) -->
                <div id="view-pesanan" class="dash-view hidden">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Semua Pesanan</h2>
                    <div class="bg-white rounded-lg shadow-sm p-10 text-center text-gray-500">
                         <p>List pesanan lengkap akan muncul di sini.</p>
                    </div>
                </div>

            </main>
        </div>
    </div>
</div>

<script>
// Simple Tab Switcher Logic
jQuery(document).ready(function($){
    $('.dash-link').click(function(e){
        e.preventDefault();
        
        // UI Tabs
        $('.dash-link').removeClass('border-primary bg-green-50 text-primary font-medium')
                       .addClass('border-transparent text-gray-600 hover:bg-gray-50');
        $(this).removeClass('border-transparent text-gray-600 hover:bg-gray-50')
               .addClass('border-primary bg-green-50 text-primary font-medium');

        // View Switching
        var target = $(this).attr('href').replace('#', 'view-');
        $('.dash-view').addClass('hidden');
        $('#' + target).removeClass('hidden');
    });
});
</script>

<?php get_footer(); ?>