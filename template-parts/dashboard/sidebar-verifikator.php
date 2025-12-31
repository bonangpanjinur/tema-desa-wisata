<?php
/**
 * Sidebar Component for Dashboard Verifikator
 * Path: template-parts/dashboard/sidebar-verifikator.php
 */

$current_user = wp_get_current_user();
global $wpdb;
$table_pedagang    = $wpdb->prefix . 'dw_pedagang';
$table_verifikator = $wpdb->prefix . 'dw_verifikator';

// Ambil Data Verifikator (Untuk Saldo & ID)
$verifikator = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_verifikator WHERE id_user = %d", $current_user->ID));
$saldo       = $verifikator ? $verifikator->saldo_saat_ini : 0;
$verif_id    = $verifikator ? $verifikator->id : 0;

// Hitung Statistik untuk Badge
$count_pending = $wpdb->get_var("SELECT COUNT(id) FROM $table_pedagang WHERE status_pendaftaran IN ('menunggu', 'menunggu_desa')");
$count_binaan  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_pedagang WHERE id_verifikator = %d", $verif_id));
?>

<!-- BACKDROP (Mobile Only) -->
<div id="sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden transition-opacity duration-300"></div>

<!-- SIDEBAR -->
<aside id="dashboard-sidebar" class="fixed top-16 md:top-20 left-0 z-50 w-64 h-[calc(100vh-4rem)] md:h-[calc(100vh-5rem)] bg-white border-r border-gray-200 overflow-y-auto flex flex-col shadow-xl transition-all duration-300 transform -translate-x-full md:translate-x-0 group">
    
    <!-- Profil Verifikator & Saldo -->
    <div class="p-4 border-b border-gray-50">
        <div class="flex items-center gap-3 p-2 bg-indigo-50 rounded-xl border border-indigo-100 transition-all duration-300 overflow-hidden relative">
            <!-- Avatar -->
            <div class="w-10 h-10 min-w-[2.5rem] bg-white rounded-full flex items-center justify-center text-indigo-600 shadow-sm shrink-0 overflow-hidden border-2 border-indigo-100">
                <img src="<?php echo get_avatar_url($current_user->ID); ?>" class="w-full h-full object-cover">
            </div>
            
            <!-- Teks Profil -->
            <div class="sidebar-text overflow-hidden transition-opacity duration-200 opacity-100 whitespace-nowrap">
                <h3 class="text-sm font-bold text-gray-800 truncate max-w-[140px]" title="<?php echo esc_attr($current_user->display_name); ?>">
                    <?php echo esc_html($current_user->display_name); ?>
                </h3>
                <span class="inline-flex items-center gap-1 text-[10px] bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-bold">
                    <i class="fas fa-user-shield text-[9px]"></i> Verifikator
                </span>
            </div>
        </div>

        <!-- Info Saldo Komisi (New) -->
        <div class="sidebar-text mt-3 px-1 transition-all duration-200 overflow-hidden whitespace-nowrap">
            <div class="flex justify-between items-center text-xs text-gray-500 mb-1">
                <span>Saldo Komisi</span>
                <span class="font-bold text-gray-800">Rp <?php echo number_format($saldo, 0, ',', '.'); ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                <!-- Bar visualisasi statis -->
                <div class="bg-indigo-500 h-1.5 rounded-full w-full"></div>
            </div>
        </div>
    </div>

    <!-- Menu Navigasi -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto overflow-x-hidden">
        <?php 
        $menu_items = [
            ['id' => 'dashboard', 'icon' => 'fa-chart-pie', 'label' => 'Ringkasan'],
            ['id' => 'antrean', 'icon' => 'fa-clock', 'label' => 'Antrean Verifikasi', 'badge' => $count_pending, 'badge_color' => 'bg-yellow-500 text-white'],
            ['id' => 'binaan', 'icon' => 'fa-store-alt', 'label' => 'UMKM Binaan', 'badge' => $count_binaan, 'badge_color' => 'bg-indigo-100 text-indigo-600'],
            ['id' => 'riwayat', 'icon' => 'fa-history', 'label' => 'Riwayat Kerja'],
            ['id' => 'pengaturan', 'icon' => 'fa-cog', 'label' => 'Pengaturan Akun'],
        ];

        foreach($menu_items as $item): ?>
            <button onclick="switchTab('<?php echo $item['id']; ?>')" id="nav-<?php echo $item['id']; ?>" 
                class="nav-item w-full flex items-center gap-3 px-3 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 transition-colors relative group/item"
                title="<?php echo $item['label']; ?>">
                
                <!-- Icon -->
                <div class="w-6 text-center shrink-0 relative">
                    <i class="fas <?php echo $item['icon']; ?> text-lg transition-colors group-hover/item:text-indigo-600"></i>
                    <!-- Badge Mini (Dot only) -->
                    <?php if(isset($item['badge']) && $item['badge'] > 0): ?>
                        <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white md:hidden group-[.w-20]:block"></span>
                    <?php endif; ?>
                </div>
                
                <!-- Label -->
                <span class="sidebar-text whitespace-nowrap transition-opacity duration-200 opacity-100 flex-1 text-left">
                    <?php echo $item['label']; ?>
                </span>

                <!-- Badge Normal -->
                <?php if(isset($item['badge']) && $item['badge'] > 0): 
                    $b_color = isset($item['badge_color']) ? $item['badge_color'] : 'bg-red-100 text-red-600';
                ?>
                    <span class="sidebar-text <?php echo $b_color; ?> text-xs font-bold px-2 py-0.5 rounded-full shadow-sm">
                        <?php echo $item['badge']; ?>
                    </span>
                <?php endif; ?>
                
                <!-- Tooltip untuk Mode Mini -->
                <div class="sidebar-mini-tooltip hidden absolute left-14 bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-lg whitespace-nowrap z-50 opacity-0 group-hover/item:opacity-100 transition-opacity pointer-events-none">
                    <?php echo $item['label']; ?>
                </div>
            </button>
        <?php endforeach; ?>
    </nav>

    <!-- Footer Sidebar -->
    <div class="p-3 border-t border-gray-100 bg-gray-50/50">
        <!-- Collapse Button (Desktop Only) -->
        <button onclick="toggleMiniSidebar()" class="hidden md:flex w-full items-center justify-center gap-3 px-3 py-2 text-xs font-bold text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all mb-2" title="Sembunyikan Menu">
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

<!-- SCRIPT PENGONTROL SIDEBAR -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
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
            texts.forEach(el => el.classList.add('opacity-0', 'w-0', 'hidden'));
            
            // Aktifkan Tooltips
            tooltips.forEach(el => el.classList.remove('hidden'));

            if(icon) icon.classList.add('rotate-180');

            if (mainContent) {
                mainContent.classList.remove('md:ml-64');
                mainContent.classList.add('md:ml-20');
            }
            localStorage.setItem('sidebar-collapsed', 'true');
        } else {
            // Mode Normal
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            
            texts.forEach(el => el.classList.remove('opacity-0', 'w-0', 'hidden'));
            tooltips.forEach(el => el.classList.add('hidden'));

            if(icon) icon.classList.remove('rotate-180');

            if (mainContent) {
                mainContent.classList.remove('md:ml-20');
                mainContent.classList.add('md:ml-64');
            }
            localStorage.setItem('sidebar-collapsed', 'false');
        }
    }
</script>