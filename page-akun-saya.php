<?php
/* Template Name: Halaman Akun Saya */
get_header(); 
$current_user = wp_get_current_user();
?>

<?php if ( is_user_logged_in() ) : ?>
    <div class="bg-slate-50 min-h-screen pb-24">
        
        <!-- Header Profile with Curve -->
        <div class="bg-white pb-8 rounded-b-[2.5rem] shadow-soft relative overflow-hidden">
            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-primary to-teal-800"></div>
            
            <div class="relative pt-16 px-6 flex flex-col items-center">
                <div class="w-24 h-24 rounded-full p-1 bg-white shadow-lg mb-3">
                    <div class="w-full h-full rounded-full overflow-hidden bg-gray-200">
                        <img src="<?php echo get_avatar_url($current_user->ID); ?>" class="w-full h-full object-cover">
                    </div>
                </div>
                <h2 class="font-bold text-xl text-gray-900"><?php echo esc_html($current_user->display_name); ?></h2>
                <p class="text-sm text-gray-500 mb-4"><?php echo esc_html($current_user->user_email); ?></p>
                
                <div class="flex gap-3 w-full justify-center">
                    <?php if (current_user_can('pedagang')) : ?>
                        <a href="<?php echo site_url('/dashboard-toko'); ?>" class="px-5 py-2.5 bg-gray-900 text-white text-xs font-bold rounded-xl shadow-lg shadow-gray-900/20 flex items-center gap-2">
                            <i class="ph-fill ph-storefront"></i> Toko Saya
                        </a>
                    <?php endif; ?>
                    <button class="px-5 py-2.5 bg-white border border-gray-200 text-gray-700 text-xs font-bold rounded-xl shadow-sm">
                        Edit Profil
                    </button>
                </div>
            </div>
        </div>

        <!-- Menu List -->
        <div class="mt-6 px-6 pb-8 space-y-4">
            <h3 class="text-sm font-bold text-gray-900 ml-1">Menu Utama</h3>
            
            <div class="bg-white rounded-2xl shadow-soft overflow-hidden divide-y divide-gray-50">
                <a href="<?php echo site_url('/pesanan-saya'); ?>" class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="ph-fill ph-receipt text-lg"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Riwayat Transaksi</span>
                    </div>
                    <i class="ph ph-caret-right text-gray-400"></i>
                </a>
                <a href="#" class="flex items-center justify-between px-5 py-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center gap-4">
                        <div class="w-9 h-9 rounded-full bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i class="ph-fill ph-map-pin text-lg"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700">Alamat Tersimpan</span>
                    </div>
                    <i class="ph ph-caret-right text-gray-400"></i>
                </a>
            </div>

            <a href="<?php echo wp_logout_url(home_url()); ?>" class="w-full py-3.5 rounded-2xl bg-white border border-red-100 text-red-500 text-sm font-bold hover:bg-red-50 transition-colors flex items-center justify-center gap-2 mt-4 shadow-sm">
                <i class="ph ph-sign-out text-lg"></i> Keluar Aplikasi
            </a>
        </div>
    </div>

<?php else : ?>
    <!-- State Belum Login -->
    <div class="min-h-screen flex flex-col items-center justify-center p-6 bg-white text-center">
        <div class="w-40 h-40 bg-gray-50 rounded-full flex items-center justify-center mb-6">
            <i class="ph-duotone ph-user-circle text-6xl text-gray-300"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Selamat Datang!</h2>
        <p class="text-sm text-gray-500 mb-8 max-w-xs mx-auto">Silakan masuk atau daftar untuk mulai berbelanja dan menikmati fitur lengkap.</p>
        
        <a href="<?php echo site_url('/login'); ?>" class="w-full bg-primary text-white font-bold py-3.5 rounded-xl shadow-lg mb-3">
            Masuk Akun
        </a>
        <a href="<?php echo site_url('/register'); ?>" class="w-full bg-white border border-gray-200 text-gray-700 font-bold py-3.5 rounded-xl">
            Daftar Baru
        </a>
    </div>
<?php endif; ?>

<?php get_footer(); ?>