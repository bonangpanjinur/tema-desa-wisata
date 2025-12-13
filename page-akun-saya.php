<?php
/* Template Name: Halaman Akun Saya */

// Redirect jika belum login
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$current_user = wp_get_current_user();
get_header(); 
?>

<!-- Container Responsif -->
<div class="max-w-4xl mx-auto">

    <!-- Header Profil -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 flex flex-col md:flex-row items-center md:items-start gap-6 relative overflow-hidden">
        <!-- Background Decoration -->
        <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-50 rounded-bl-full -mr-8 -mt-8 z-0"></div>

        <!-- Avatar -->
        <div class="w-24 h-24 rounded-full bg-gray-200 border-4 border-emerald-50 shadow-md overflow-hidden relative z-10 shrink-0">
             <img src="<?php echo get_avatar_url($current_user->ID, ['size' => 200]); ?>" class="w-full h-full object-cover">
        </div>

        <!-- Info User -->
        <div class="text-center md:text-left flex-1 relative z-10">
            <h1 class="text-2xl font-bold text-gray-800"><?php echo esc_html($current_user->display_name); ?></h1>
            <p class="text-gray-500 text-sm mb-3"><?php echo esc_html($current_user->user_email); ?></p>
            
            <div class="flex flex-wrap justify-center md:justify-start gap-2">
                <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold">Member</span>
                <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-bold">
                    <i class="fas fa-coins mr-1"></i> 0 Poin
                </span>
            </div>
        </div>

        <!-- Tombol Edit -->
        <a href="<?php echo home_url('/edit-profil'); ?>" class="relative z-10 text-emerald-600 hover:text-emerald-700 text-sm font-medium border border-emerald-200 px-4 py-2 rounded-lg hover:bg-emerald-50 transition">
            <i class="fas fa-edit mr-1"></i> Edit Profil
        </a>
    </div>

    <!-- Grid Menu Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        
        <!-- Menu Transaksi -->
        <a href="<?php echo home_url('/transaksi'); ?>" class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition hover:-translate-y-1 group">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl mb-3 group-hover:bg-blue-600 group-hover:text-white transition">
                <i class="fas fa-shopping-bag"></i>
            </div>
            <h3 class="font-bold text-gray-800 mb-1">Pesanan Saya</h3>
            <p class="text-xs text-gray-500">Lihat riwayat belanja & status</p>
        </a>

        <!-- Menu Favorit -->
        <a href="#" class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition hover:-translate-y-1 group">
            <div class="w-12 h-12 bg-pink-50 text-pink-500 rounded-xl flex items-center justify-center text-xl mb-3 group-hover:bg-pink-500 group-hover:text-white transition">
                <i class="fas fa-heart"></i>
            </div>
            <h3 class="font-bold text-gray-800 mb-1">Favorit</h3>
            <p class="text-xs text-gray-500">Wisata & produk tersimpan</p>
        </a>

        <!-- Menu Alamat -->
        <a href="#" class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition hover:-translate-y-1 group">
            <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-xl flex items-center justify-center text-xl mb-3 group-hover:bg-orange-500 group-hover:text-white transition">
                <i class="fas fa-map-marker-alt"></i>
            </div>
            <h3 class="font-bold text-gray-800 mb-1">Daftar Alamat</h3>
            <p class="text-xs text-gray-500">Atur alamat pengiriman</p>
        </a>

         <!-- Menu Bantuan -->
         <a href="#" class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition hover:-translate-y-1 group">
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-xl mb-3 group-hover:bg-purple-600 group-hover:text-white transition">
                <i class="fas fa-headset"></i>
            </div>
            <h3 class="font-bold text-gray-800 mb-1">Pusat Bantuan</h3>
            <p class="text-xs text-gray-500">Butuh bantuan?</p>
        </a>

         <!-- Menu Pengaturan -->
         <a href="#" class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition hover:-translate-y-1 group">
            <div class="w-12 h-12 bg-gray-50 text-gray-600 rounded-xl flex items-center justify-center text-xl mb-3 group-hover:bg-gray-600 group-hover:text-white transition">
                <i class="fas fa-cog"></i>
            </div>
            <h3 class="font-bold text-gray-800 mb-1">Pengaturan</h3>
            <p class="text-xs text-gray-500">Password & Keamanan</p>
        </a>

        <!-- Menu Logout -->
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="bg-white p-5 rounded-xl shadow-sm border border-red-100 hover:bg-red-50 transition hover:-translate-y-1 group">
            <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center text-xl mb-3 group-hover:bg-red-500 group-hover:text-white transition">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h3 class="font-bold text-red-600 mb-1">Keluar</h3>
            <p class="text-xs text-red-400">Logout dari akun</p>
        </a>

    </div>

    <!-- Riwayat Singkat (Optional) -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-gray-800">Transaksi Terakhir</h3>
            <a href="<?php echo home_url('/transaksi'); ?>" class="text-emerald-600 text-sm font-medium hover:underline">Lihat Semua</a>
        </div>
        
        <div class="text-center py-8 text-gray-400 bg-gray-50 rounded-xl border border-dashed border-gray-200">
            <i class="fas fa-receipt text-3xl mb-2 opacity-50"></i>
            <p class="text-sm">Belum ada transaksi terbaru.</p>
        </div>
    </div>

</div>

<div class="h-10"></div>

<?php get_footer(); ?>