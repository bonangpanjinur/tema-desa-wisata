<?php
/**
 * Sidebar Component for Dashboard Desa
 * Lokasi: template-parts/dashboard/sidebar-desa.php
 */

$desa_data = isset($args['desa_data']) ? $args['desa_data'] : null;
$akses_premium = isset($args['akses_premium']) ? $args['akses_premium'] : false;

if (!$desa_data) return;
?>

<!-- BACKDROP (Mobile Only) -->
<div id="sidebar-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity duration-300"></div>

<!-- SIDEBAR -->
<!-- 
   LOGIC CSS RESPONSIF:
   1. top-16 (64px) -> Sesuai tinggi header mobile di header.php
   2. md:top-20 (80px) -> Sesuai tinggi header desktop di header.php
   3. h-[calc(100vh-4rem)] -> Tinggi sisa layar mobile
   4. md:h-[calc(100vh-5rem)] -> Tinggi sisa layar desktop
   5. z-40 -> Di bawah Header (z-50) tapi di atas konten
-->
<aside id="dashboard-sidebar" class="fixed top-16 md:top-20 left-0 z-40 w-64 h-[calc(100vh-4rem)] md:h-[calc(100vh-5rem)] transition-transform -translate-x-full md:translate-x-0 bg-white border-r border-gray-200 overflow-y-auto">
    <div class="p-5">
        <!-- Profil Singkat di Sidebar -->
        <div class="flex items-center gap-3 mb-6 p-3 bg-blue-50 rounded-xl border border-blue-100">
            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-blue-600 shadow-sm shrink-0">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="overflow-hidden">
                <h3 class="text-sm font-bold text-gray-800 truncate"><?php echo esc_html($desa_data->nama_desa); ?></h3>
                <?php if($akses_premium): ?>
                    <span class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">Premium</span>
                <?php else: ?>
                    <span class="text-[10px] bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full font-bold">Free</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Menu Navigasi -->
        <nav class="space-y-1">
            <button onclick="switchTab('ringkasan')" id="nav-ringkasan" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100 transition active-tab">
                <i class="fas fa-chart-pie w-5 text-center"></i> Ringkasan
            </button>
            <button onclick="switchTab('verifikasi')" id="nav-verifikasi" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100 transition relative">
                <i class="fas fa-check-circle w-5 text-center"></i> Verifikasi UMKM
                <?php if(!$akses_premium): ?><i class="fas fa-lock text-xs text-gray-400 absolute right-4"></i><?php endif; ?>
            </button>
            <button onclick="switchTab('data-umkm')" id="nav-data-umkm" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100 transition">
                <i class="fas fa-store w-5 text-center"></i> Data UMKM
            </button>
            <button onclick="switchTab('wisata')" id="nav-wisata" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100 transition">
                <i class="fas fa-map w-5 text-center"></i> Kelola Wisata
            </button>
            <button onclick="switchTab('profil')" id="nav-profil" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-100 transition">
                <i class="fas fa-cog w-5 text-center"></i> Profil Desa
            </button>
        </nav>
    </div>

    <div class="p-4 border-t border-gray-100 mt-auto">
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition w-full">
            <i class="fas fa-sign-out-alt w-5 text-center"></i> Logout
        </a>
    </div>
</aside>