<?php
/* Template Name: Halaman Transaksi */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_user_logged_in() ) {
    wp_safe_redirect( wp_login_url( get_permalink() ) );
    exit;
}

get_header();
global $wpdb;
$user_id = get_current_user_id();

$table_transaksi = $wpdb->prefix . 'dw_transaksi';
$table_sub = $wpdb->prefix . 'dw_transaksi_sub';

// 1. Whitelist Input (Validasi status yang diperbolehkan)
$allowed_status = ['menunggu_pembayaran', 'pembayaran_dikonfirmasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
$status_filter = isset($_GET['status']) && in_array($_GET['status'], $allowed_status) ? $_GET['status'] : '';

// 2. Query dengan Prepare
if ( $status_filter ) {
    $query = $wpdb->prepare( 
        "SELECT * FROM $table_transaksi WHERE status_transaksi = %s AND id_pembeli = %d ORDER BY created_at DESC", 
        $status_filter, 
        $user_id 
    );
} else {
    $query = $wpdb->prepare( 
        "SELECT * FROM $table_transaksi WHERE id_pembeli = %d ORDER BY created_at DESC", 
        $user_id 
    );
}

$transactions = $wpdb->get_results( $query );
?>

<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-3xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Riwayat Belanja</h1>

        <?php if($transactions): foreach($transactions as $trx): 
            // Ambil detail sub transaksi (toko)
            $subs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_sub WHERE id_transaksi = %d", $trx->id));
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 overflow-hidden">
            <!-- Header Transaksi -->
            <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex justify-between items-center flex-wrap gap-2">
                <div class="text-sm">
                    <span class="font-bold text-gray-700 block md:inline md:mr-3"><?php echo esc_html(date('d M Y', strtotime($trx->created_at))); ?></span>
                    <span class="text-gray-500 font-mono text-xs"><?php echo esc_html($trx->kode_unik); ?></span>
                </div>
                <div>
                    <?php 
                    $status_cls = ($trx->status_transaksi == 'menunggu_pembayaran') ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700';
                    ?>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?php echo esc_attr($status_cls); ?>">
                        <?php echo esc_html(str_replace('_', ' ', $trx->status_transaksi)); ?>
                    </span>
                </div>
            </div>

            <!-- List Toko dalam Transaksi ini -->
            <div class="p-6 space-y-4">
                <?php foreach($subs as $sub): ?>
                <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2 border-b border-dashed border-gray-100 pb-3 last:pb-0 last:border-0">
                    <div>
                        <div class="flex items-center gap-2 font-bold text-gray-800 text-sm">
                            <i class="fas fa-store text-gray-400"></i> <?php echo esc_html($sub->nama_toko); ?>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Status Toko: <?php echo esc_html(ucfirst(str_replace('_', ' ', $sub->status_pesanan))); ?></p>
                    </div>
                    <div class="text-right">
                        <span class="block font-bold text-gray-700 text-sm"><?php echo esc_html(tema_dw_format_rupiah($sub->total_pesanan_toko)); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Footer & Action -->
            <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t border-gray-200">
                <div>
                    <span class="text-xs text-gray-500 block">Total Tagihan</span>
                    <span class="text-lg font-bold text-primary"><?php echo esc_html(tema_dw_format_rupiah($trx->total_akhir)); ?></span>
                </div>
                <?php if($trx->status_transaksi == 'menunggu_pembayaran'): ?>
                    <a href="<?php echo esc_url(home_url('/pembayaran?id='.$trx->id)); ?>" class="bg-primary text-white px-5 py-2 rounded-lg font-bold text-sm hover:bg-green-700 shadow-sm">Bayar Sekarang</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; else: ?>
            <div class="text-center py-16">
                <i class="fas fa-shopping-bag text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">Belum ada transaksi.</p>
                <a href="<?php echo esc_url(home_url('/produk')); ?>" class="text-primary font-bold hover:underline mt-2 inline-block">Mulai Belanja</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
