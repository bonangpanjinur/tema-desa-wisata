<?php
/**
 * Template Name: Dashboard Desa
 */

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
    exit;
}

$user = wp_get_current_user();
if ( ! in_array( 'admin_desa', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
    wp_redirect( home_url() );
    exit;
}

get_header(); 
?>

<div class="bg-gray-100 min-h-screen">
    <!-- Header Desa -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg text-gray-700 flex items-center gap-2">
                <i class="ph-fill ph-house-line text-primary"></i> 
                <span>Portal Desa Wisata</span>
            </div>
            <div class="flex items-center gap-3">
                <img src="<?php echo get_avatar_url($user->ID); ?>" class="w-8 h-8 rounded-full">
                <span class="text-sm font-semibold"><?php echo esc_html($user->display_name); ?></span>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-blue-500">
                <div class="text-gray-500 text-xs uppercase tracking-wider font-bold mb-1">Total Kunjungan</div>
                <div class="text-2xl font-bold text-gray-800">1,240</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500">
                <div class="text-gray-500 text-xs uppercase tracking-wider font-bold mb-1">Pedagang Aktif</div>
                <div class="text-2xl font-bold text-gray-800">24</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-yellow-500">
                <div class="text-gray-500 text-xs uppercase tracking-wider font-bold mb-1">Menunggu Verifikasi</div>
                <div class="text-2xl font-bold text-gray-800">3</div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-purple-500">
                <div class="text-gray-500 text-xs uppercase tracking-wider font-bold mb-1">Pendapatan Desa</div>
                <div class="text-2xl font-bold text-gray-800">Rp 5.4jt</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Column -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Verifikasi Pedagang -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-bold text-lg text-gray-800 mb-4 border-b pb-4">Permintaan Pendaftaran Pedagang</h3>
                    <div class="space-y-4">
                        <!-- Item -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-500">
                                    <i class="ph-bold ph-storefront"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-gray-900">Warung Bu Siti</h4>
                                    <p class="text-xs text-gray-500">Siti Aminah • Kuliner</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button class="px-3 py-1.5 bg-white border border-gray-200 text-xs font-bold text-gray-600 rounded-lg hover:text-red-500">Tolak</button>
                                <button class="px-3 py-1.5 bg-primary text-white text-xs font-bold rounded-lg hover:bg-secondary">Setujui</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wisata Management -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4 border-b pb-4">
                        <h3 class="font-bold text-lg text-gray-800">Kelola Wisata</h3>
                        <button class="text-primary text-sm font-bold hover:underline">+ Tambah Wisata</button>
                    </div>
                    <!-- List Wisata akan di-load di sini -->
                    <p class="text-sm text-gray-500 italic">Daftar objek wisata di desa Anda...</p>
                </div>
            </div>

            <!-- Sidebar Column -->
            <div class="space-y-8">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-bold text-gray-800 mb-4">Profil Desa</h3>
                    <div class="text-center mb-4">
                        <img src="https://via.placeholder.com/150" class="w-24 h-24 rounded-lg object-cover mx-auto mb-2">
                        <h4 class="font-bold">Desa Wisata Karanganyar</h4>
                        <p class="text-xs text-gray-500">Magelang, Jawa Tengah</p>
                    </div>
                    <button class="w-full py-2 bg-gray-50 text-gray-600 text-sm font-bold rounded-lg hover:bg-gray-100">Edit Profil</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>