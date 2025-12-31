<?php
/**
 * Sidebar Component for Dashboard Pedagang (Toko)
 * Path: template-parts/dashboard/sidebar-pedagang.php
 */

$pedagang = isset($args['pedagang']) ? $args['pedagang'] : null;
if (!$pedagang) return;

// Hitung Pending Orders
global $wpdb;
$table_sub = $wpdb->prefix . 'dw_transaksi_sub';
$count_pending = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_sub WHERE id_pedagang = %d AND status_pesanan = 'menunggu_konfirmasi'", $pedagang->id));
?>

<!-- BACKDROP (Mobile Only) -->
<!-- z-30: Di bawah sidebar (z-40) tapi di atas konten -->
<div id="sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-black/50 z-30 hidden md:hidden transition-opacity duration-300"></div>

<!-- SIDEBAR -->
<!-- 
   PERBAIKAN Z-INDEX & POSISI:
   1. top-16 (64px): Mulai tepat di bawah header mobile.
   2. z-40: Di bawah Header (z-50) tapi di atas Backdrop (z-30).
   3. h-[calc(100vh-4rem)]: Tinggi total - tinggi header (4rem/64px).
-->
<aside id="dashboard-sidebar" class="fixed top-16 md:top-20 left-0 z-40 w-64 h-[calc(100vh-4rem)] md:h-[calc(100vh-5rem)] bg-white border-r border-gray-200 overflow-y-auto flex flex-col shadow-xl transition-transform duration-300 transform -translate-x-full md:translate-x-0 group">
    
    <!-- Profil Toko -->
    <div class="p-4 border-b border-gray-50">
        <div class="flex items-center gap-3 p-2 bg-purple-50 rounded-xl border border-purple-100 transition-all duration-300 overflow-hidden relative">
            <div class="w-10 h-10 min-w-[2.5rem] bg-white rounded-full flex items-center justify-center text-purple-600 shadow-sm shrink-0 overflow-hidden">
                <?php if(!empty($pedagang->foto_profil)): ?>
                    <img src="<?php echo esc_url($pedagang->foto_profil); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <i class="fas fa-store"></i>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-text overflow-hidden opacity-100 whitespace-nowrap">
                <h3 class="text-sm font-bold text-gray-800 truncate max-w-[140px]"><?php echo esc_html($pedagang->nama_toko); ?></h3>
                <span class="inline-flex items-center gap-1 text-[10px] <?php echo ($pedagang->status_akun == 'aktif') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> px-2 py-0.5 rounded-full font-bold">
                    <?php echo ucfirst($pedagang->status_akun); ?>
                </span>
            </div>
        </div>
        
        <!-- Sisa Kuota -->
        <div class="sidebar-text mt-3 px-1">
            <div class="flex justify-between items-center text-xs text-gray-500 mb-1">
                <span>Sisa Kuota</span>
                <span class="font-bold text-gray-800"><?php echo number_format($pedagang->sisa_transaksi); ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
                <div class="bg-purple-600 h-1.5 rounded-full" style="width: <?php echo min(100, $pedagang->sisa_transaksi); ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Menu -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <?php 
        $menu_items = [
            ['id' => 'ringkasan', 'icon' => 'fa-chart-line', 'label' => 'Ringkasan'],
            ['id' => 'produk', 'icon' => 'fa-box-open', 'label' => 'Produk Saya'],
            ['id' => 'pesanan', 'icon' => 'fa-shopping-basket', 'label' => 'Pesanan Masuk', 'badge' => $count_pending],
            ['id' => 'paket', 'icon' => 'fa-ticket-alt', 'label' => 'Kuota & Paket'],
            ['id' => 'pengaturan', 'icon' => 'fa-cog', 'label' => 'Pengaturan Toko'],
        ];

        foreach($menu_items as $item): ?>
            <button onclick="switchTab('<?php echo $item['id']; ?>')" id="nav-<?php echo $item['id']; ?>" 
                class="nav-item w-full flex items-center gap-3 px-3 py-3 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-50 transition-colors relative group/item">
                
                <div class="w-6 text-center shrink-0 relative">
                    <i class="fas <?php echo $item['icon']; ?> text-lg"></i>
                    <?php if(isset($item['badge']) && $item['badge'] > 0): ?>
                        <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-red-500 rounded-full border-2 border-white md:hidden group-[.w-20]:block"></span>
                    <?php endif; ?>
                </div>
                
                <span class="sidebar-text whitespace-nowrap opacity-100 flex-1 text-left"><?php echo $item['label']; ?></span>

                <?php if(isset($item['badge']) && $item['badge'] > 0): ?>
                    <span class="sidebar-text bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $item['badge']; ?></span>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </nav>

    <!-- Footer -->
    <div class="p-3 border-t border-gray-100 bg-gray-50/50">
        <button onclick="toggleMiniSidebar()" class="hidden md:flex w-full items-center justify-center gap-3 px-3 py-2 text-xs font-bold text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-all mb-2" title="Resize Menu">
            <i class="fas fa-chevron-left transition-transform duration-300" id="collapse-icon"></i>
            <span class="sidebar-text whitespace-nowrap">Sembunyikan</span>
        </button>

        <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-colors w-full">
            <div class="w-6 text-center shrink-0"><i class="fas fa-sign-out-alt"></i></div>
            <span class="sidebar-text whitespace-nowrap">Keluar</span>
        </a>
    </div>
</aside>

<script>
    // Script Sidebar Mini Desktop
    function toggleMiniSidebar() {
        const sidebar = document.getElementById('dashboard-sidebar');
        const isMini = sidebar.classList.contains('w-20');
        const content = document.querySelector('main');
        const texts = sidebar.querySelectorAll('.sidebar-text');
        const icon = document.getElementById('collapse-icon');

        if (!isMini) { // Go Mini
            sidebar.classList.replace('w-64', 'w-20');
            texts.forEach(el => el.classList.add('hidden'));
            if(content) { content.classList.replace('md:ml-64', 'md:ml-20'); }
            if(icon) icon.classList.add('rotate-180');
        } else { // Go Normal
            sidebar.classList.replace('w-20', 'w-64');
            texts.forEach(el => el.classList.remove('hidden'));
            if(content) { content.classList.replace('md:ml-20', 'md:ml-64'); }
            if(icon) icon.classList.remove('rotate-180');
        }
    }
</script>