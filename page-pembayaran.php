<?php
/**
 * Template Name: Halaman Pembayaran
 * Description: Menampilkan detail pembayaran dan form upload bukti transfer.
 */

if (!session_id()) session_start();
if (!is_user_logged_in()) wp_redirect(home_url('/login'));

get_header();

global $wpdb;
$user_id = get_current_user_id();
$trx_id  = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

// 1. Ambil Data Transaksi
$transaksi = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}dw_transaksi WHERE kode_unik = %s AND id_pembeli = %d",
    $trx_id, $user_id
));

if (!$transaksi) {
    echo '<div class="container py-20 text-center"><h3>Transaksi tidak ditemukan</h3><a href="'.home_url('/').'">Kembali ke Home</a></div>';
    get_footer();
    exit;
}

// 2. Handle Upload Bukti Bayar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_bayar'])) {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $attachment_id = media_handle_upload('bukti_bayar', 0);

    if (is_wp_error($attachment_id)) {
        $error_msg = "Gagal upload gambar: " . $attachment_id->get_error_message();
    } else {
        $img_url = wp_get_attachment_url($attachment_id);
        
        // Update Database
        $wpdb->update(
            $wpdb->prefix . 'dw_transaksi',
            [
                'bukti_pembayaran' => $img_url, // Simpan URL
                'url_bukti_bayar' => $img_url, // Legacy column support
                'status_transaksi' => 'pembayaran_dikonfirmasi', // Atau 'menunggu_verifikasi'
                'tanggal_pembayaran' => current_time('mysql')
            ],
            ['kode_unik' => $trx_id]
        );

        // Update Sub Transaksi juga ke 'diproses' (opsional, tergantung flow bisnis)
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}dw_transaksi_sub SET status_pesanan = 'diproses' WHERE id_transaksi = %d",
            $transaksi->id
        ));

        // Refresh Page
        wp_redirect(add_query_arg('status', 'success', get_permalink()));
        exit;
    }
}

// Status Display Helper
$status_label = [
    'menunggu_pembayaran' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'label' => 'Menunggu Pembayaran'],
    'pembayaran_dikonfirmasi' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'label' => 'Sedang Diverifikasi'],
    'diproses' => ['bg' => 'bg-green-100', 'text' => 'text-green-700', 'label' => 'Pembayaran Diterima'],
];
$curr_status = $status_label[$transaksi->status_transaksi] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => $transaksi->status_transaksi];
?>

<div class="bg-gray-50 min-h-screen py-10 font-sans">
    <div class="max-w-2xl mx-auto px-4">
        
        <!-- Header Status -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Pembayaran Pesanan</h1>
            <p class="text-gray-500 text-sm">ID Transaksi: <span class="font-mono font-bold text-gray-800"><?php echo $trx_id; ?></span></p>
            
            <div class="inline-block mt-4 px-4 py-2 rounded-full text-sm font-bold <?php echo $curr_status['bg'] . ' ' . $curr_status['text']; ?>">
                <?php echo $curr_status['label']; ?>
            </div>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-6 py-8 rounded-2xl text-center mb-8 shadow-sm">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 text-green-600">
                    <i class="fas fa-check text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Terima Kasih!</h3>
                <p class="mb-4">Bukti pembayaran Anda berhasil diupload. Kami akan segera memproses pesanan Anda.</p>
                <a href="<?php echo home_url('/akun-saya'); ?>" class="inline-block bg-green-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-green-700">Ke Dashboard Saya</a>
            </div>
        <?php else: ?>

            <?php if ($transaksi->status_transaksi == 'menunggu_pembayaran'): ?>
                
                <!-- CARD TOTAL & REKENING -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="bg-gray-900 p-6 text-white text-center">
                        <p class="text-sm opacity-80 mb-1">Total yang harus dibayar</p>
                        <h2 class="text-3xl font-bold"><?php echo tema_dw_format_rupiah($transaksi->total_transaksi); ?></h2>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="font-bold text-gray-800 mb-4 border-b border-gray-100 pb-2">Silakan Transfer ke:</h3>
                        
                        <div class="space-y-4">
                            <!-- Bank BCA -->
                            <div class="flex items-center gap-4 p-4 border border-gray-100 rounded-xl hover:border-primary transition cursor-pointer group">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" class="w-16 h-auto" alt="BCA">
                                <div>
                                    <p class="text-xs text-gray-500">Bank BCA</p>
                                    <p class="font-mono font-bold text-lg text-gray-800 tracking-wide">123-456-7890</p>
                                    <p class="text-xs text-gray-500">a.n Desa Wisata Official</p>
                                </div>
                                <button class="ml-auto text-primary opacity-0 group-hover:opacity-100 text-sm font-bold" onclick="navigator.clipboard.writeText('1234567890'); alert('Disalin!')">Salin</button>
                            </div>

                            <!-- Bank BRI -->
                            <div class="flex items-center gap-4 p-4 border border-gray-100 rounded-xl hover:border-primary transition cursor-pointer group">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/6/68/BANK_BRI_logo.svg" class="w-16 h-auto" alt="BRI">
                                <div>
                                    <p class="text-xs text-gray-500">Bank BRI</p>
                                    <p class="font-mono font-bold text-lg text-gray-800 tracking-wide">0000-01-000000-50-0</p>
                                    <p class="text-xs text-gray-500">a.n Desa Wisata Official</p>
                                </div>
                            </div>
                        </div>

                        <!-- QRIS -->
                        <div class="mt-6 text-center">
                            <p class="text-sm font-bold text-gray-800 mb-2">Atau Scan QRIS</p>
                            <img src="https://upload.wikimedia.org/wikipedia/commons/d/d4/QRIS_logo.svg" class="w-24 h-auto mx-auto mb-2" alt="QRIS">
                            <div class="w-48 h-48 bg-gray-200 mx-auto rounded-lg flex items-center justify-center text-gray-400 text-xs border-2 border-dashed border-gray-300">
                                [ QR CODE PLACEHOLDER ]
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD UPLOAD BUKTI -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-camera text-primary"></i> Konfirmasi Pembayaran
                    </h3>
                    
                    <?php if (isset($error_msg)) echo '<div class="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm">'.$error_msg.'</div>'; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Upload Bukti Transfer</label>
                            <input type="file" name="bukti_bayar" accept="image/*" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 border border-gray-300 rounded-lg p-2">
                            <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG. Maks 2MB.</p>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-lg shadow-green-500/30 transition-all transform active:scale-95">
                            Kirim Bukti Pembayaran
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <!-- STATUS SUDAH DIBAYAR / MENUNGGU VERIFIKASI -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 text-center">
                    <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4 text-blue-500 animate-pulse">
                        <i class="fas fa-clock text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Pembayaran Sedang Diverifikasi</h3>
                    <p class="text-gray-500 mb-6">Terima kasih, bukti pembayaran Anda telah kami terima. Admin akan segera memverifikasi pesanan Anda dalam waktu 1x24 jam.</p>
                    
                    <?php if($transaksi->bukti_pembayaran): ?>
                        <div class="mb-6">
                            <p class="text-xs text-gray-400 mb-2">Bukti yang diupload:</p>
                            <img src="<?php echo esc_url($transaksi->bukti_pembayaran); ?>" class="h-32 mx-auto rounded-lg border border-gray-200 p-1">
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo home_url('/akun-saya'); ?>" class="text-primary font-bold hover:underline">Lihat Status Pesanan</a>
                </div>
            <?php endif; ?>

        <?php endif; ?>
        
    </div>
</div>

<?php get_footer(); ?>