<?php
/**
 * Template Name: Akun Saya (Pembeli)
 * Description: Dashboard pembeli yang menampilkan status per toko (Sub-Transaksi).
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

$user = wp_get_current_user();
get_header();

global $wpdb;

// 1. QUERY MASTER TRANSAKSI
// Mengambil transaksi utama milik user
$sql_master = $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}dw_transaksi 
     WHERE id_pembeli = %d 
     ORDER BY created_at DESC", 
    $user->ID
);
$riwayat = $wpdb->get_results($sql_master);

// Helper Status Master (Pembayaran)
$status_master_map = [
    'menunggu_pembayaran' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'label' => 'Belum Bayar'],
    'pembayaran_dikonfirmasi' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Verifikasi Pembayaran'],
    'diproses' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Lunas'], // Master dianggap lunas jika sudah ada bukti
];

// Helper Status Sub (Pengiriman Toko)
$status_sub_map = [
    'menunggu_konfirmasi' => ['icon' => 'fa-clock', 'class' => 'text-gray-500', 'label' => 'Menunggu Konfirmasi Toko'],
    'diproses'            => ['icon' => 'fa-box-open', 'class' => 'text-blue-600', 'label' => 'Sedang Dikemas'],
    'dikirim_ekspedisi'   => ['icon' => 'fa-truck', 'class' => 'text-purple-600', 'label' => 'Dalam Pengiriman'],
    'selesai'             => ['icon' => 'fa-check-circle', 'class' => 'text-green-600', 'label' => 'Pesanan Selesai'],
    'dibatalkan'          => ['icon' => 'fa-times-circle', 'class' => 'text-red-600', 'label' => 'Dibatalkan Toko'],
];
?>

<div class="bg-gray-50 min-h-screen py-10 font-sans">
    <div class="max-w-4xl mx-auto px-4">
        
        <!-- PROFIL HEADER -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center gap-5 mb-8">
            <div class="w-16 h-16 bg-gradient-to-br from-primary to-green-300 rounded-full flex items-center justify-center text-white text-2xl font-bold shadow-lg shadow-green-500/30">
                <?php echo strtoupper(substr($user->display_name, 0, 1)); ?>
            </div>
            <div class="flex-1">
                <h1 class="text-xl font-bold text-gray-800">Halo, <?php echo esc_html($user->display_name); ?></h1>
                <p class="text-gray-500 text-sm flex items-center gap-1"><i class="far fa-envelope"></i> <?php echo esc_html($user->user_email); ?></p>
            </div>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="text-red-500 hover:bg-red-50 px-4 py-2 rounded-lg transition font-bold text-sm border border-transparent hover:border-red-100">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        </div>

        <!-- RIWAYAT PESANAN -->
        <h2 class="text-xl font-bold text-gray-800 mb-5 flex items-center gap-2">
            <i class="fas fa-history text-primary"></i> Riwayat Pesanan
        </h2>
        
        <?php if ($riwayat): ?>
            <div class="space-y-6">
                <?php foreach ($riwayat as $trx): 
                    // Tentukan style status master
                    $st_m = $status_master_map[$trx->status_transaksi] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => 'Status: ' . $trx->status_transaksi];
                    
                    // QUERY SUB TRANSAKSI (Detail per Toko)
                    // Inilah kunci agar status dari pedagang terlihat oleh pembeli
                    $subs = $wpdb->get_results($wpdb->prepare(
                        "SELECT sub.*, p.nama_toko 
                         FROM {$wpdb->prefix}dw_transaksi_sub sub
                         JOIN {$wpdb->prefix}dw_pedagang p ON sub.id_pedagang = p.id
                         WHERE sub.id_transaksi = %d", 
                        $trx->id
                    ));
                ?>
                
                <!-- KARTU TRANSAKSI -->
                <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden hover:shadow-md transition duration-300">
                    
                    <!-- HEADER TRANSAKSI (MASTER) -->
                    <div class="bg-gray-50/80 p-4 border-b border-gray-100 flex flex-col sm:flex-row justify-between sm:items-center gap-3">
                        <div class="flex items-center gap-3">
                            <span class="font-mono font-bold text-gray-700 bg-white px-2 py-1 rounded border border-gray-200 text-xs shadow-sm">
                                <?php echo $trx->kode_unik; ?>
                            </span>
                            <span class="text-xs text-gray-500 flex items-center gap-1">
                                <i class="far fa-calendar-alt"></i> <?php echo date('d M Y, H:i', strtotime($trx->created_at)); ?>
                            </span>
                        </div>
                        
                        <!-- Badge Status Pembayaran -->
                        <div class="flex items-center gap-2">
                            <?php if ($trx->status_transaksi == 'menunggu_pembayaran'): ?>
                                <span class="bg-yellow-100 text-yellow-700 text-xs px-3 py-1 rounded-full font-bold animate-pulse">Belum Bayar</span>
                            <?php else: ?>
                                <span class="<?php echo $st_m['bg'] . ' ' . $st_m['text']; ?> text-xs px-3 py-1 rounded-full font-bold">
                                    <?php echo $st_m['label']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- BODY TRANSAKSI (LIST TOKO & ITEM) -->
                    <div class="p-4 sm:p-6 space-y-6">
                        <?php foreach ($subs as $sub): 
                            // Style Status Sub (Toko)
                            $st_s = $status_sub_map[$sub->status_pesanan] ?? ['icon' => 'fa-circle', 'class' => 'text-gray-400', 'label' => $sub->status_pesanan];
                            
                            // Ambil Item Produk untuk sub ini
                            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}dw_transaksi_items WHERE id_sub_transaksi = %d", $sub->id));
                        ?>
                        <div class="relative pl-4 border-l-2 border-gray-100">
                            <!-- Header Toko & Status -->
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <h4 class="font-bold text-gray-800 text-sm flex items-center gap-2">
                                        <i class="fas fa-store text-gray-400"></i> <?php echo esc_html($sub->nama_toko); ?>
                                    </h4>
                                    <!-- STATUS DARI PEDAGANG -->
                                    <p class="text-xs font-bold mt-1 <?php echo $st_s['class']; ?> flex items-center gap-1">
                                        <i class="fas <?php echo $st_s['icon']; ?>"></i> <?php echo $st_s['label']; ?>
                                    </p>
                                    <?php if(!empty($sub->no_resi)): ?>
                                        <p class="text-[10px] text-gray-500 mt-0.5 bg-gray-100 px-1.5 py-0.5 rounded inline-block">Resi: <span class="font-mono font-bold"><?php echo esc_html($sub->no_resi); ?></span></p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- List Barang -->
                            <div class="space-y-2">
                                <?php foreach ($items as $item): ?>
                                <div class="flex justify-between items-center text-sm">
                                    <div class="flex items-center gap-2">
                                        <span class="text-gray-400 text-xs"><?php echo $item->jumlah; ?>x</span>
                                        <span class="text-gray-700"><?php echo esc_html($item->nama_produk); ?></span>
                                    </div>
                                    <span class="text-gray-500 font-medium"><?php echo tema_dw_format_rupiah($item->total_harga); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- FOOTER TRANSAKSI -->
                    <div class="bg-gray-50 p-4 border-t border-gray-100 flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-500">Total Tagihan</p>
                            <p class="text-lg font-bold text-primary"><?php echo tema_dw_format_rupiah($trx->total_transaksi); ?></p>
                        </div>
                        
                        <!-- Action Button -->
                        <?php if ($trx->status_transaksi == 'menunggu_pembayaran'): ?>
                            <a href="<?php echo home_url('/pembayaran?id='.$trx->kode_unik); ?>" class="bg-primary hover:bg-green-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-green-500/20 transition">
                                Bayar Sekarang
                            </a>
                        <?php else: ?>
                            <button class="text-gray-500 hover:text-primary text-sm font-bold flex items-center gap-1">
                                <i class="fas fa-file-invoice"></i> Lihat Detail
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            
            <!-- EMPTY STATE -->
            <div class="bg-white rounded-2xl p-12 text-center border border-dashed border-gray-300">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                    <i class="fas fa-shopping-bag text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800">Belum ada pesanan</h3>
                <p class="text-gray-500 mb-6 text-sm">Yuk, mulai jelajahi produk lokal desa wisata!</p>
                <a href="<?php echo home_url('/produk'); ?>" class="bg-primary hover:bg-green-700 text-white font-bold py-2.5 px-6 rounded-full transition shadow-lg shadow-green-500/20">
                    Mulai Belanja
                </a>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php get_footer(); ?>