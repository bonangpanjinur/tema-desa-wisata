<?php
/**
 * Template Name: Halaman Akun Saya
 * Description: Halaman profil pengguna yang menyesuaikan tampilan berdasarkan role (Admin Desa, Pedagang, Pembeli).
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

get_header();

$current_user = wp_get_current_user();
$roles = $current_user->roles;
$role_label = 'Pengguna';
$dashboard_url = '';
$dashboard_label = '';

// Deteksi Role Utama
if (in_array('administrator', $roles)) {
    $role_label = 'Administrator';
    $dashboard_url = admin_url();
    $dashboard_label = 'WP Admin';
} elseif (in_array('admin_desa', $roles)) {
    $role_label = 'Admin Desa';
    $dashboard_url = home_url('/dashboard-desa');
    $dashboard_label = 'Kelola Desa';
} elseif (in_array('pedagang', $roles)) {
    $role_label = 'Pedagang';
    $dashboard_url = home_url('/dashboard-toko');
    $dashboard_label = 'Kelola Toko';
} else {
    $role_label = 'Pembeli';
    $dashboard_url = home_url('/transaksi'); // Atau halaman riwayat belanja
    $dashboard_label = 'Riwayat Belanja';
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    wp_logout();
    wp_redirect(home_url('/login'));
    exit;
}
?>

<div class="bg-gray-50 min-h-screen font-sans text-gray-800 pb-20">
    
    <!-- Header Profile (Background) -->
    <div class="bg-gradient-to-r from-orange-600 to-orange-500 h-48 relative">
        <div class="absolute bottom-0 left-0 w-full h-16 bg-gradient-to-t from-gray-50 to-transparent"></div>
    </div>

    <div class="container mx-auto px-4 -mt-20 relative z-10">
        
        <!-- Profile Card -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 md:p-8 flex flex-col md:flex-row items-center md:items-start gap-6 mb-8">
            <!-- Avatar -->
            <div class="w-24 h-24 md:w-32 md:h-32 bg-white rounded-full p-1 shadow-md">
                <div class="w-full h-full rounded-full overflow-hidden bg-gray-200">
                    <?php echo get_avatar($current_user->ID, 128, '', '', ['class' => 'w-full h-full object-cover']); ?>
                </div>
            </div>
            
            <!-- User Info -->
            <div class="text-center md:text-left flex-1">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider mb-2 <?php echo in_array('admin_desa', $roles) ? 'bg-green-100 text-green-700' : (in_array('pedagang', $roles) ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'); ?>">
                    <?php echo esc_html($role_label); ?>
                </span>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1"><?php echo esc_html($current_user->display_name); ?></h1>
                <p class="text-gray-500 text-sm mb-4"><?php echo esc_html($current_user->user_email); ?></p>
                
                <div class="flex flex-wrap justify-center md:justify-start gap-3">
                    <a href="<?php echo home_url('/edit-profil'); ?>" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-50 transition">
                        <i class="fas fa-edit mr-1"></i> Edit Profil
                    </a>
                    <a href="?action=logout" class="px-4 py-2 border border-red-200 bg-red-50 rounded-lg text-sm font-bold text-red-600 hover:bg-red-100 transition" onclick="return confirm('Yakin ingin keluar?');">
                        <i class="fas fa-sign-out-alt mr-1"></i> Keluar
                    </a>
                </div>
            </div>

            <!-- Stats / Quick Action (Optional) -->
            <div class="hidden md:block text-right">
                <p class="text-xs text-gray-400">Bergabung sejak</p>
                <p class="font-bold text-gray-800"><?php echo date('d M Y', strtotime($current_user->user_registered)); ?></p>
            </div>
        </div>

        <!-- MENU GRID -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- 1. DASHBOARD UTAMA (Role Based) -->
            <?php if ($dashboard_url) : ?>
            <a href="<?php echo esc_url($dashboard_url); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:-translate-y-1 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-24 h-24 bg-orange-50 rounded-bl-full -mr-4 -mt-4 transition group-hover:scale-110"></div>
                <div class="relative z-10 flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center text-xl shadow-sm">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 group-hover:text-orange-600 transition"><?php echo esc_html($dashboard_label); ?></h3>
                        <p class="text-xs text-gray-500">Akses halaman utama pengelolaan.</p>
                    </div>
                </div>
            </a>
            <?php endif; ?>

            <!-- 2. KHUSUS ADMIN DESA -->
            <?php if (in_array('admin_desa', $roles)) : ?>
            <a href="<?php echo home_url('/dashboard-desa?tab=wisata'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-xl shadow-sm">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 group-hover:text-green-600 transition">Kelola Wisata</h3>
                        <p class="text-xs text-gray-500">Tambah & edit destinasi wisata desa.</p>
                    </div>
                </div>
            </a>

            <a href="<?php echo home_url('/dashboard-desa?tab=pedagang'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl shadow-sm">
                        <i class="fas fa-store-alt"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 group-hover:text-blue-600 transition">Verifikasi UMKM</h3>
                        <p class="text-xs text-gray-500">Kelola pendaftaran pedagang baru.</p>
                    </div>
                </div>
            </a>
            <?php endif; ?>

            <!-- 3. KHUSUS PEDAGANG -->
            <?php if (in_array('pedagang', $roles)) : ?>
            <a href="<?php echo home_url('/dashboard-toko?tab=produk'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl shadow-sm">
                        <i class="fas fa-box"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 group-hover:text-purple-600 transition">Produk Saya</h3>
                        <p class="text-xs text-gray-500">Kelola stok dan harga produk.</p>
                    </div>
                </div>
            </a>
            <a href="<?php echo home_url('/dashboard-toko?tab=pesanan'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-xl flex items-center justify-center text-xl shadow-sm">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 group-hover:text-yellow-600 transition">Pesanan Masuk</h3>
                        <p class="text-xs text-gray-500">Cek orderan baru dari pelanggan.</p>
                    </div>
                </div>
            </a>
            <?php endif; ?>

            <!-- 4. UMUM (SEMUA USER) -->
            <a href="<?php echo home_url('/transaksi'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-xl shadow-sm">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 group-hover:text-red-600 transition">Riwayat Transaksi</h3>
                        <p class="text-xs text-gray-500">Lihat status belanjaan Anda.</p>
                    </div>
                </div>
            </a>

            <a href="<?php echo home_url('/edit-profil'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center text-xl shadow-sm">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 group-hover:text-gray-900 transition">Pengaturan Akun</h3>
                        <p class="text-xs text-gray-500">Ubah password & data diri.</p>
                    </div>
                </div>
            </a>

        </div>
    </div>
</div>

<?php get_footer(); ?>