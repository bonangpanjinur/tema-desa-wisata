<?php
/**
 * Template Name: Dashboard Toko
 */

// 1. Cek Login & Role
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login')); exit;
}

$user = wp_get_current_user();
if (!in_array('pedagang', $user->roles) && !in_array('administrator', $user->roles)) {
    echo "Akses Ditolak. Halaman ini khusus Pedagang.";
    exit;
}

get_header();

global $wpdb;
// 2. Ambil Data Pedagang dari Tabel dw_pedagang
$tbl_pedagang = $wpdb->prefix . 'dw_pedagang';
$pedagang = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tbl_pedagang WHERE id_user = %d", $user->ID));

if (!$pedagang) {
    echo '<div class="container py-20 text-center"><h3>Data Toko tidak ditemukan. Silakan hubungi Admin Desa.</h3></div>';
    get_footer(); exit;
}

// 3. Hitung Statistik Real dari dw_transaksi_sub
$tbl_sub = $wpdb->prefix . 'dw_transaksi_sub';
$stats = $wpdb->get_row($wpdb->prepare("
    SELECT 
        COUNT(*) as total_pesanan,
        SUM(CASE WHEN status_pesanan = 'selesai' THEN total_pesanan_toko ELSE 0 END) as omzet,
        SUM(CASE WHEN status_pesanan = 'menunggu_konfirmasi' THEN 1 ELSE 0 END) as perlu_kirim
    FROM $tbl_sub 
    WHERE id_pedagang = %d
", $pedagang->id));
?>

<div class="bg-gray-100 min-h-screen pb-20">
    <!-- Header Dashboard -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg text-gray-800 flex items-center gap-2">
                <i class="fas fa-store text-primary text-xl"></i> 
                <span><?php echo esc_html($pedagang->nama_toko); ?></span>
            </div>
            <div class="flex items-center gap-3">
                <img src="<?php echo get_avatar_url($user->ID); ?>" class="w-8 h-8 rounded-full border">
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Omzet -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                <p class="text-gray-500 text-xs font-bold uppercase mb-1">Total Pendapatan</p>
                <h3 class="text-2xl font-bold text-gray-900"><?php echo dw_format_rupiah($stats->omzet); ?></h3>
                <i class="fas fa-wallet absolute right-4 bottom-4 text-4xl text-green-100"></i>
            </div>
            <!-- Total Order -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                <p class="text-gray-500 text-xs font-bold uppercase mb-1">Total Pesanan</p>
                <h3 class="text-2xl font-bold text-gray-900"><?php echo intval($stats->total_pesanan); ?></h3>
                <i class="fas fa-shopping-bag absolute right-4 bottom-4 text-4xl text-blue-100"></i>
            </div>
            <!-- Pending -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 relative overflow-hidden">
                <p class="text-gray-500 text-xs font-bold uppercase mb-1">Perlu Konfirmasi</p>
                <h3 class="text-2xl font-bold text-orange-500"><?php echo intval($stats->perlu_kirim); ?></h3>
                <i class="fas fa-bell absolute right-4 bottom-4 text-4xl text-orange-100"></i>
            </div>
        </div>

        <!-- Tabel Pesanan Masuk -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-800">Pesanan Terbaru</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-gray-800 font-semibold uppercase text-xs border-b">
                        <tr>
                            <th class="px-6 py-3">ID Pesanan</th>
                            <th class="px-6 py-3">Tanggal</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Total</th>
                            <th class="px-6 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php
                        $orders = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tbl_sub WHERE id_pedagang = %d ORDER BY created_at DESC LIMIT 10", $pedagang->id));
                        
                        if ($orders) : foreach($orders as $order) :
                            $status_label = ucfirst(str_replace('_', ' ', $order->status_pesanan));
                            $badge_class = 'bg-gray-100 text-gray-600';
                            
                            if($order->status_pesanan == 'menunggu_konfirmasi') $badge_class = 'bg-yellow-100 text-yellow-700';
                            elseif($order->status_pesanan == 'selesai') $badge_class = 'bg-green-100 text-green-700';
                            elseif($order->status_pesanan == 'diproses') $badge_class = 'bg-blue-100 text-blue-700';
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900">#<?php echo $order->id; ?></td>
                            <td class="px-6 py-4"><?php echo date('d M Y', strtotime($order->created_at)); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $badge_class; ?>">
                                    <?php echo $status_label; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-gray-800">
                                <?php echo dw_format_rupiah($order->total_pesanan_toko); ?>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button class="text-primary hover:text-green-700 font-bold text-xs border border-primary px-3 py-1 rounded">Detail</button>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">Belum ada pesanan masuk.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>