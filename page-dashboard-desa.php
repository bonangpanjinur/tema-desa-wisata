<?php
/**
 * Template Name: Dashboard Desa
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login')); exit;
}

$user = wp_get_current_user();
if (!in_array('admin_desa', $user->roles) && !in_array('administrator', $user->roles)) {
    echo "Akses Ditolak. Halaman ini khusus Admin Desa.";
    exit;
}

get_header();
global $wpdb;

// 1. Data Desa
$tbl_desa = $wpdb->prefix . 'dw_desa';
$desa = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl_desa WHERE id_user_desa = %d", $user->ID));

if(!$desa) {
    echo '<div class="py-20 text-center">Data Desa belum dikonfigurasi.</div>';
    get_footer(); exit;
}

// 2. Data Pedagang (Verifikasi)
$tbl_pedagang = $wpdb->prefix . 'dw_pedagang';
$pending_pedagang = $wpdb->get_results($wpdb->prepare("
    SELECT * FROM $tbl_pedagang 
    WHERE id_desa = %d AND status_pendaftaran = 'menunggu_desa'
", $desa->id));

// 3. Stats
$total_pedagang = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tbl_pedagang WHERE id_desa = %d AND status_akun = 'aktif'", $desa->id));
?>

<div class="bg-gray-100 min-h-screen pb-20">
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg text-gray-800 flex items-center gap-2">
                <i class="fas fa-landmark text-primary text-xl"></i> 
                <span>Portal Desa <?php echo esc_html($desa->nama_desa); ?></span>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Sidebar / Ringkasan -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 text-center">
                    <?php if($desa->foto): ?>
                        <img src="<?php echo esc_url($desa->foto); ?>" class="w-24 h-24 rounded-full mx-auto mb-3 object-cover">
                    <?php endif; ?>
                    <h3 class="font-bold text-gray-800"><?php echo esc_html($desa->nama_desa); ?></h3>
                    <p class="text-sm text-gray-500"><?php echo esc_html($desa->kabupaten); ?></p>
                </div>

                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                    <h4 class="font-bold text-gray-700 mb-3 text-sm uppercase">Statistik Desa</h4>
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-500 text-sm">Pedagang Aktif</span>
                        <span class="font-bold text-gray-900"><?php echo $total_pedagang; ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-500 text-sm">Menunggu Validasi</span>
                        <span class="font-bold text-orange-500"><?php echo count($pending_pedagang); ?></span>
                    </div>
                </div>
            </div>

            <!-- Content Utama -->
            <div class="lg:col-span-3 space-y-6">
                
                <!-- Verifikasi Pedagang -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 bg-orange-50">
                        <h3 class="font-bold text-orange-800 flex items-center gap-2">
                            <i class="fas fa-user-clock"></i> Validasi Pedagang Baru
                        </h3>
                    </div>
                    
                    <?php if($pending_pedagang): ?>
                    <div class="divide-y divide-gray-100">
                        <?php foreach($pending_pedagang as $p): ?>
                        <div class="p-6 flex flex-col md:flex-row justify-between items-center gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                                    <i class="fas fa-store text-gray-400"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800"><?php echo esc_html($p->nama_toko); ?></h4>
                                    <p class="text-sm text-gray-500">Pemilik: <?php echo esc_html($p->nama_pemilik); ?> (<?php echo esc_html($p->nomor_wa); ?>)</p>
                                    <p class="text-xs text-gray-400 mt-1"><?php echo esc_html($p->alamat_lengkap); ?></p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-600 hover:bg-red-50 hover:text-red-600 hover:border-red-200">Tolak</button>
                                <button class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-bold hover:bg-green-700 shadow-md">Setujui</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="p-8 text-center text-gray-400">Tidak ada permintaan pendaftaran pedagang saat ini.</div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>