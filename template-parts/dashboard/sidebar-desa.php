<?php
/**
 * Sidebar Component for Dashboard Desa
 * Path: template-parts/dashboard/sidebar-desa.php
 * Args: $args['desa_data'], $args['akses_premium']
 */

$desa_data = isset($args['desa_data']) ? $args['desa_data'] : null;
$akses_premium = isset($args['akses_premium']) ? $args['akses_premium'] : false;

if (!$desa_data) return;
?>

<!-- BACKDROP (Mobile Only) -->
<div id="sidebar-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity duration-300"></div>

<!-- SIDEBAR -->
<!-- 
   LOGIC CSS POSISI:
   1. top-16 (64px) & md:top-20 (80px): Menyesuaikan tinggi header.
   2. w-64: Lebar default (akan dimanipulasi JS untuk mode mini).
   3. transition-all duration-300: Animasi halus saat resize.
-->
<aside id="dashboard-sidebar" class="fixed top-16 md:top-20 left-0 z-40 w-64 h-[calc(100vh-4rem)] md:h-[calc(100vh-5rem)] bg-white border-r border-gray-200 overflow-y-auto flex flex-col shadow-sm transition-all duration-300 transform -translate-x-full md:translate-x-0 group">
    
    <!-- Profil Singkat -->
    <div class="p-4 border-b border-gray-50">
        <div class="flex items-center gap-3 p-2 bg-blue-50 rounded-xl border border-blue-100 transition-all duration-300 overflow-hidden relative">
            <!-- Icon Avatar -->
            <div class="w-10 h-10 min-w-[2.5rem] bg-white rounded-full flex items-center justify-center text-blue-600 shadow-sm shrink-0">
                <i class="fas fa-landmark"></i>
            </div>
            
            <!-- Teks Profil (Akan disembunyikan saat mini) -->
            <div class="sidebar-text overflow-hidden transition-opacity duration-200 opacity-100 whitespace-nowrap">
                <h3 class="text-sm font-bold text-gray-800 truncate max-w-[140px]" title="<?php echo esc_attr($desa_data->nama_desa); ?>">
                    <?php echo esc_html($desa_data->nama_desa); ?>
                </h3>
                
                <?php if($akses_premium): ?>
                    <span class="inline-flex items-center gap-1 text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-bold">
                        <i class="fas fa-crown text-[8px]"></i> Premium
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 text-[10px] bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full font-bold">
                        Free Plan
                    </span>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ID Wilayah (Sembunyi saat mini) -->
        <?php if($desa_data->kode_referral): ?>
            <div class="sidebar-text mt-3 px-1 text-center transition-all duration-200 overflow-hidden whitespace-nowrap">
                <span class="text-[10px] text-gray-400 uppercase tracking-wider font-bold block mb-1">ID Wilayah</span>
                <div class="font-mono text-xs font-bold text-blue-600 bg-blue-50/50 rounded px-2 py-1 border border-blue-100 truncate">
                    <?php echo esc_html($desa_data->kode_referral); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Menu Navigasi -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto overflow-x-hidden">
        
        <?php 
        $menu_items = [
            ['id' => 'ringkasan', 'icon' => 'fa-chart-pie', 'label' => 'Ringkasan'],
            ['id' => 'verifikasi', 'icon' => 'fa-users-cog', 'label' => 'Verifikasi UMKM', 'lock' => !$akses_premium],
            ['id' => 'data-umkm', 'icon' => 'fa-store', 'label' => 'Data UMKM'],
            ['id' => 'wisata', 'icon' => 'fa-map-location-dot', 'label' => 'Kelola Wisata'],
            ['id' => 'profil', 'icon' => 'fa-id-card-clip', 'label' => 'Profil Desa'],
        ];

        foreach($menu_items as $item): 
            $is_locked = isset($item['lock']) && $item['lock'];
        ?>
            <button onclick="switchTab('<?php echo $item['id']; ?>')" id="nav-<?php echo $item['id']; ?>" 
                class="nav-item w-full flex items-center gap-3 px-3 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 transition-colors relative group/item"
                title="<?php echo $item['label']; ?>">
                
                <!-- Icon (Fixed Width) -->
                <div class="w-6 text-center shrink-0">
                    <i class="fas <?php echo $item['icon']; ?> text-lg transition-colors"></i>
                </div>
                
                <!-- Label (Hidden on Mini) -->
                <span class="sidebar-text whitespace-nowrap transition-opacity duration-200 opacity-100">
                    <?php echo $item['label']; ?>
                </span>

                <!-- Lock Icon -->
                <?php if($is_locked): ?>
                    <i class="fas fa-lock text-[10px] text-gray-400 absolute right-3 sidebar-text"></i>
                <?php endif; ?>
                
                <!-- Tooltip untuk Mode Mini (Hover) -->
                <div class="sidebar-mini-tooltip hidden absolute left-14 bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-lg whitespace-nowrap z-50 opacity-0 group-hover/item:opacity-100 transition-opacity pointer-events-none">
                    <?php echo $item['label']; ?>
                </div>
            </button>
        <?php endforeach; ?>

    </nav>

    <!-- Footer Sidebar -->
    <div class="p-3 border-t border-gray-100 bg-gray-50/50">
        <!-- Collapse Button (Desktop Only) -->
        <button onclick="toggleMiniSidebar()" class="hidden md:flex w-full items-center justify-center gap-3 px-3 py-2 text-xs font-bold text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all mb-2" title="Sembunyikan Menu">
            <i class="fas fa-chevron-left transition-transform duration-300" id="collapse-icon"></i>
            <span class="sidebar-text whitespace-nowrap">Sembunyikan</span>
        </button>

        <!-- Logout -->
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-colors w-full" title="Keluar">
            <div class="w-6 text-center shrink-0"><i class="fas fa-sign-out-alt"></i></div>
            <span class="sidebar-text whitespace-nowrap">Keluar</span>
        </a>
    </div>
</aside>

<!-- SCRIPT PENGONTROL SIDEBAR CANGGIH -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Cek LocalStorage untuk mengingat preferensi user
        const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
        if (isCollapsed && window.innerWidth >= 768) {
            setMiniSidebar(true);
        }
    });

    function toggleMiniSidebar() {
        const sidebar = document.getElementById('dashboard-sidebar');
        const isMini = sidebar.classList.contains('w-20');
        setMiniSidebar(!isMini);
    }

    function setMiniSidebar(collapse) {
        const sidebar = document.getElementById('dashboard-sidebar');
        const mainContent = document.querySelector('main');
        const texts = sidebar.querySelectorAll('.sidebar-text');
        const icon = document.getElementById('collapse-icon');
        const tooltips = sidebar.querySelectorAll('.sidebar-mini-tooltip');

        if (collapse) {
            // Mode Mini
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-20');
            
            // Sembunyikan Teks
            texts.forEach(el => {
                el.classList.add('opacity-0', 'w-0', 'hidden');
            });

            // Aktifkan Tooltips
            tooltips.forEach(el => el.classList.remove('hidden'));

            // Putar Ikon Collapse
            if(icon) icon.classList.add('rotate-180');

            // Sesuaikan Main Content Margin
            if (mainContent) {
                mainContent.classList.remove('md:ml-64');
                mainContent.classList.add('md:ml-20');
            }

            localStorage.setItem('sidebar-collapsed', 'true');

        } else {
            // Mode Normal (Expanded)
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            
            // Tampilkan Teks
            texts.forEach(el => {
                el.classList.remove('opacity-0', 'w-0', 'hidden');
            });

            // Matikan Tooltips (karena teks sudah ada)
            tooltips.forEach(el => el.classList.add('hidden'));

            // Reset Ikon Collapse
            if(icon) icon.classList.remove('rotate-180');

            // Sesuaikan Main Content Margin
            if (mainContent) {
                mainContent.classList.remove('md:ml-20');
                mainContent.classList.add('md:ml-64');
            }

            localStorage.setItem('sidebar-collapsed', 'false');
        }
    }
</script>