<?php
/**
 * Template Name: Halaman Akun Saya Custom
 * Description: Dashboard pusat untuk semua role (Pembeli, Pedagang, Admin Desa) dengan deteksi role yang akurat.
 */

// 1. Cek Login
if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
    exit;
}

get_header();

$current_user = wp_get_current_user();
$roles = ( array ) $current_user->roles; // Pastikan array

// --- LOGIKA DETEKSI PERAN (Sesuai kode plugin) ---
$role_label = 'Pembeli'; // Default
$role_class = 'bg-gray-100 text-gray-600';

$is_admin_kabupaten = in_array( 'administrator', $roles ) || in_array( 'admin_kabupaten', $roles );
$is_admin_desa      = in_array( 'admin_desa', $roles ); 
$is_pedagang        = in_array( 'pedagang', $roles );

if ( $is_admin_kabupaten ) {
    $role_label = 'Administrator';
    $role_class = 'bg-red-100 text-red-600';
} elseif ( $is_admin_desa ) {
    $role_label = 'Admin Desa';
    $role_class = 'bg-green-100 text-green-600';
} elseif ( $is_pedagang ) {
    $role_label = 'Pedagang';
    $role_class = 'bg-orange-100 text-orange-600';
}

// Avatar
$avatar_url = get_avatar_url( $current_user->ID, ['size' => 200] );
?>

<div class="min-h-screen bg-gray-50 font-sans pb-20 relative overflow-hidden">
    
    <!-- Background Header (Style Tetap) -->
    <div class="bg-white pb-24 pt-12 border-b border-gray-100 relative">
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-orange-50/50 to-transparent pointer-events-none"></div>
        <div class="container mx-auto px-4 relative z-10">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Akun Saya</h1>
            <p class="text-gray-500">Kelola aktivitas dan profil Anda di satu tempat.</p>
        </div>
    </div>

    <!-- Main Content (Negative Margin for Overlap Effect) -->
    <div class="container mx-auto px-4 -mt-16 relative z-20">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- KOLOM KIRI: Profil Ringkas -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl shadow-xl shadow-gray-100 p-6 border border-gray-100 text-center sticky top-24">
                    <div class="relative inline-block mb-4">
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>" class="w-24 h-24 rounded-full object-cover border-4 border-white shadow-md">
                        <div class="absolute bottom-0 right-0 w-6 h-6 bg-green-500 border-2 border-white rounded-full" title="Online"></div>
                    </div>
                    
                    <h2 class="text-xl font-bold text-gray-900"><?php echo esc_html($current_user->display_name); ?></h2>
                    <p class="text-sm text-gray-500 mb-3"><?php echo esc_html($current_user->user_email); ?></p>
                    
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold <?php echo $role_class; ?>">
                        <?php echo $role_label; ?>
                    </span>

                    <div class="mt-6 pt-6 border-t border-gray-100 space-y-3">
                        <a href="<?php echo home_url('/edit-profil'); ?>" class="block w-full py-2 px-4 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl text-sm font-bold transition">
                            <i class="fas fa-user-edit mr-2"></i> Akun Saya
                        <a href="<?php echo wp_logout_url(home_url('/login')); ?>" class="block w-full py-2 px-4 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl text-sm font-bold transition">
                            <i class="fas fa-sign-out-alt mr-2"></i> Keluar
                        </a>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: Menu Dashboard -->
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- 1. MENU KHUSUS ADMIN DESA -->
                    <?php if ( $is_admin_desa ) : ?>
                        <a href="<?php echo home_url('/dashboard?tab=wisata'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-green-100 hover:shadow-lg hover:border-green-300 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-xl relative z-10">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <div class="relative z-10">
                                <h3 class="font-bold text-gray-900 group-hover:text-green-600 transition">Kelola Wisata</h3>
                                <p class="text-xs text-gray-500 mt-1">Tambah & edit destinasi wisata.</p>
                            </div>
                        </a>

                        <a href="<?php echo home_url('/dashboard?tab=pedagang'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-blue-100 hover:shadow-lg hover:border-blue-300 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                             <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl relative z-10">
                                <i class="fas fa-store-alt"></i>
                            </div>
                            <div class="relative z-10">
                                <h3 class="font-bold text-gray-900 group-hover:text-blue-600 transition">Verifikasi UMKM</h3>
                                <p class="text-xs text-gray-500 mt-1">Kelola pendaftaran pedagang.</p>
                            </div>
                        </a>
                        
                        <a href="<?php echo home_url('/dashboard'); ?>" class="md:col-span-2 group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-50 text-gray-600 rounded-xl flex items-center justify-center text-xl group-hover:bg-gray-100 transition">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">Dashboard Desa</h3>
                                <p class="text-xs text-gray-500 mt-1">Lihat statistik pengunjung dan laporan.</p>
                            </div>
                        </a>
                    <?php endif; ?>

                    <!-- 2. MENU KHUSUS PEDAGANG -->
                    <?php if ( $is_pedagang ) : ?>
                        <a href="<?php echo home_url('/dashboard?tab=produk'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-purple-100 hover:shadow-lg hover:border-purple-300 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                             <div class="absolute top-0 right-0 w-24 h-24 bg-purple-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-xl relative z-10">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="relative z-10">
                                <h3 class="font-bold text-gray-900 group-hover:text-purple-600 transition">Produk Saya</h3>
                                <p class="text-xs text-gray-500 mt-1">Kelola stok dan harga produk.</p>
                            </div>
                        </a>

                        <a href="<?php echo home_url('/dashboard?tab=pesanan'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-yellow-100 hover:shadow-lg hover:border-yellow-300 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                             <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-xl flex items-center justify-center text-xl relative z-10">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="relative z-10">
                                <h3 class="font-bold text-gray-900 group-hover:text-yellow-600 transition">Pesanan Masuk</h3>
                                <p class="text-xs text-gray-500 mt-1">Cek orderan baru pelanggan.</p>
                            </div>
                        </a>
                        
                         <a href="<?php echo home_url('/dashboard'); ?>" class="md:col-span-2 group bg-white p-6 rounded-2xl shadow-sm border border-orange-100 hover:shadow-lg hover:border-orange-300 transition-all duration-300 flex items-center gap-4 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-orange-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
                            <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center text-xl relative z-10">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="relative z-10">
                                <h3 class="font-bold text-gray-900 group-hover:text-orange-600 transition">Dashboard Toko</h3>
                                <p class="text-xs text-gray-500 mt-1">Pusat kontrol penjualan dan laporan.</p>
                            </div>
                        </a>
                    <?php endif; ?>
                    
                    <!-- 3. MENU ADMIN UTAMA (WP-ADMIN) -->
                    <?php if ( $is_admin_kabupaten ) : ?>
                         <a href="<?php echo admin_url(); ?>" class="md:col-span-2 group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 flex items-center gap-4">
                            <div class="w-12 h-12 bg-gray-800 text-white rounded-xl flex items-center justify-center text-xl transition">
                                <i class="fab fa-wordpress"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">WP Admin Dashboard</h3>
                                <p class="text-xs text-gray-500 mt-1">Masuk ke panel admin utama.</p>
                            </div>
                        </a>
                    <?php endif; ?>

                    <!-- 4. MENU UMUM (SEMUA USER) -->
                    
                    <!-- Separator jika role khusus aktif -->
                    <?php if ($is_admin_desa || $is_pedagang || $is_admin_kabupaten) : ?>
                    <div class="md:col-span-2 border-t border-dashed border-gray-200 my-2"></div>
                    <?php endif; ?>

                    <!-- Riwayat Transaksi -->
                    <a href="<?php echo home_url('/transaksi'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-50 text-red-600 rounded-xl flex items-center justify-center text-xl group-hover:bg-red-100 transition">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">Pesanan Saya</h3>
                            <p class="text-xs text-gray-500 mt-1">Riwayat belanja Anda.</p>
                        </div>
                    </a>

                    <!-- Favorit -->
                    <a href="<?php echo home_url('/favorit'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 flex items-center gap-4">
                        <div class="w-12 h-12 bg-pink-50 text-pink-500 rounded-xl flex items-center justify-center text-xl group-hover:bg-pink-100 transition">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">Favorit</h3>
                            <p class="text-xs text-gray-500 mt-1">Item yang Anda simpan.</p>
                        </div>
                    </a>

                    <!-- Keranjang -->
                    <a href="<?php echo home_url('/cart'); ?>" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 flex items-center gap-4">
                        <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-xl group-hover:bg-green-100 transition">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">Keranjang</h3>
                            <p class="text-xs text-gray-500 mt-1">Lihat item yang akan dibeli.</p>
                        </div>
                    </a>

                    <!-- Bantuan -->
                    <a href="https://wa.me/6281234567890" target="_blank" class="group bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 flex items-center gap-4">
                        <div class="w-12 h-12 bg-gray-50 text-gray-600 rounded-xl flex items-center justify-center text-xl group-hover:bg-gray-100 transition">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900">Bantuan</h3>
                            <p class="text-xs text-gray-500 mt-1">Hubungi admin jika ada kendala.</p>
                        </div>
                    </a>

                </div>

                <!-- Banner Promosi Kecil (Opsional) -->
                <div class="mt-8 bg-gradient-to-r from-gray-900 to-gray-800 rounded-2xl p-6 text-white relative overflow-hidden shadow-lg">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-5 rounded-full -mr-10 -mt-10 blur-2xl"></div>
                    <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-bold">Dukung UMKM Desa!</h3>
                            <p class="text-sm text-gray-300 mt-1">Setiap pembelian Anda membantu ekonomi warga desa tumbuh.</p>
                        </div>
                        <a href="<?php echo home_url('/produk'); ?>" class="px-5 py-2 bg-white text-gray-900 rounded-lg text-sm font-bold hover:bg-orange-50 transition">
                            Belanja Sekarang
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>