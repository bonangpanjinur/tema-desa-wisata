<?php
/**
 * Sidebar Component for Dashboard Desa
 * Args passed: $args['desa_data'], $args['akses_premium']
 */

$desa_data = isset($args['desa_data']) ? $args['desa_data'] : null;
$akses_premium = isset($args['akses_premium']) ? $args['akses_premium'] : false;

if (!$desa_data) return;
?>

<!-- BACKDROP (Mobile Only) -->
<div id="sidebar-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity"></div>

<!-- SIDEBAR -->
<!-- Logic: Top-16 (dibawah header), z-40 (diatas konten) -->
<aside id="logo-sidebar" class="fixed top-16 left-0 z-40 w-64 h-[calc(100vh-4rem)] transition-transform -translate-x-full md:translate-x-0 bg-white border-r border-gray-200 overflow-y-auto" aria-label="Sidebar">
    <div class="h-full px-3 py-4 flex flex-col">
        
        <!-- Info Status Mobile -->
        <div class="mb-5 p-3 bg-blue-50 rounded-lg border border-blue-100 md:hidden">
            <div class="font-bold text-gray-800 mb-1"><?php echo esc_html($desa_data->nama_desa); ?></div>
            <?php if($desa_data->kode_referral): ?>
                <div class="text-xs text-gray-500">ID: <span class="font-mono font-bold text-blue-700"><?php echo esc_html($desa_data->kode_referral); ?></span></div>
            <?php endif; ?>
        </div>

        <!-- Status Member Sidebar -->
        <div class="mb-4">
            <?php if($desa_data->kode_referral): ?>
                <div class="mb-3 px-3 py-2 bg-blue-50 rounded-lg border border-blue-100 hidden md:block">
                    <p class="text-[10px] uppercase font-bold text-blue-500 mb-1">ID Wilayah</p>
                    <p class="font-mono font-bold text-blue-800 text-sm"><?php echo esc_html($desa_data->kode_referral); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if($akses_premium): ?>
                <span class="block text-center w-full px-2 py-1 rounded text-xs font-bold bg-green-100 text-green-700 border border-green-200"><i class="fas fa-crown"></i> Premium</span>
            <?php else: ?>
                <span class="block text-center w-full px-2 py-1 rounded text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200">Free Plan</span>
            <?php endif; ?>
        </div>

        <!-- Menu Navigasi -->
        <ul class="space-y-2 font-medium flex-1">
            <li>
                <button onclick="switchTab('ringkasan')" id="nav-ringkasan" class="nav-item flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group w-full text-left active-tab">
                    <i class="fas fa-chart-pie w-6 text-gray-500 transition duration-75 group-hover:text-gray-900"></i>
                    <span class="ml-3">Ringkasan</span>
                </button>
            </li>
            <li>
                <button onclick="switchTab('verifikasi')" id="nav-verifikasi" class="nav-item flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group w-full text-left">
                    <i class="fas fa-users-cog w-6 text-gray-500 transition duration-75 group-hover:text-gray-900"></i>
                    <span class="ml-3 flex-1 whitespace-nowrap">Verifikasi UMKM</span>
                    <?php if(!$akses_premium): ?><span class="inline-flex items-center justify-center px-2 ml-3 text-xs font-medium text-gray-800 bg-gray-200 rounded-full"><i class="fas fa-lock"></i></span><?php endif; ?>
                </button>
            </li>
            <li>
                <button onclick="switchTab('data-umkm')" id="nav-data-umkm" class="nav-item flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group w-full text-left">
                    <i class="fas fa-store w-6 text-gray-500 transition duration-75 group-hover:text-gray-900"></i>
                    <span class="ml-3">Data UMKM</span>
                </button>
            </li>
            <li>
                <button onclick="switchTab('wisata')" id="nav-wisata" class="nav-item flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group w-full text-left">
                    <i class="fas fa-map-location-dot w-6 text-gray-500 transition duration-75 group-hover:text-gray-900"></i>
                    <span class="ml-3">Kelola Wisata</span>
                </button>
            </li>
            <li>
                <button onclick="switchTab('profil')" id="nav-profil" class="nav-item flex items-center p-2 text-gray-900 rounded-lg hover:bg-gray-100 group w-full text-left">
                    <i class="fas fa-id-card-clip w-6 text-gray-500 transition duration-75 group-hover:text-gray-900"></i>
                    <span class="ml-3">Profil Desa</span>
                </button>
            </li>
        </ul>
        
        <!-- Tombol Keluar -->
        <div class="mt-4 pt-4 border-t border-gray-200">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center p-2 text-red-600 rounded-lg hover:bg-red-50 group">
                <i class="fas fa-sign-out-alt w-6"></i>
                <span class="ml-3">Keluar Aplikasi</span>
            </a>
        </div>
    </div>
</aside>