<?php
/**
 * Template Name: Halaman Pembayaran (Multi-Vendor Dynamic)
 * Description: Menampilkan rekening tujuan berdasarkan data keuangan masing-masing toko.
 */

if (!session_id()) session_start();
if (!is_user_logged_in()) wp_redirect(home_url('/login'));

get_header();

global $wpdb;
$user_id = get_current_user_id();
$trx_id  = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

// 1. AMBIL DATA TRANSAKSI UTAMA (MASTER)
$transaksi = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}dw_transaksi WHERE kode_unik = %s AND id_pembeli = %d",
    $trx_id, $user_id
));

if (!$transaksi) {
    echo '<div class="container mx-auto px-4 py-20 text-center"><h3 class="text-xl font-bold text-gray-700">Transaksi tidak ditemukan</h3><a href="'.home_url('/').'" class="text-primary hover:underline">Kembali ke Home</a></div>';
    get_footer();
    exit;
}

// 2. AMBIL DATA SUB-TRANSAKSI & DETAIL KEUANGAN TOKO
// Kita join tabel sub-transaksi dengan tabel pedagang untuk ambil data bank/qris
$sql_details = $wpdb->prepare(
    "SELECT sub.*, 
            p.nama_bank, p.no_rekening, p.atas_nama_rekening, p.qris_image_url, p.nama_toko
     FROM {$wpdb->prefix}dw_transaksi_sub sub
     JOIN {$wpdb->prefix}dw_pedagang p ON sub.id_pedagang = p.id
     WHERE sub.id_transaksi = %d",
    $transaksi->id
);
$list_tagihan = $wpdb->get_results($sql_details);


// 3. HANDLE UPLOAD BUKTI BAYAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_bayar'])) {
    if ( ! function_exists( 'wp_handle_upload' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $attachment_id = media_handle_upload('bukti_bayar', 0);

    if (is_wp_error($attachment_id)) {
        $error_msg = "Gagal upload gambar: " . $attachment_id->get_error_message();
    } else {
        $img_url = wp_get_attachment_url($attachment_id);
        
        // Update Database Master
        $wpdb->update(
            $wpdb->prefix . 'dw_transaksi',
            [
                'bukti_pembayaran' => $img_url,
                'status_transaksi' => 'pembayaran_dikonfirmasi', // Status berubah jadi menunggu konfirmasi penjual/admin
                'tanggal_pembayaran' => current_time('mysql')
            ],
            ['kode_unik' => $trx_id]
        );

        // Update Sub Transaksi (Agar muncul di dashboard pedagang sebagai perlu diproses)
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}dw_transaksi_sub SET status_pesanan = 'menunggu_konfirmasi' WHERE id_transaksi = %d",
            $transaksi->id
        ));

        // Refresh Page dengan status success
        echo "<script>window.location.href = '" . add_query_arg('status', 'success', get_permalink()) . "?id=" . $trx_id . "';</script>";
        exit;
    }
}

// Helper Status Label
$status_map = [
    'menunggu_pembayaran' => ['color' => 'yellow', 'label' => 'Menunggu Pembayaran'],
    'pembayaran_dikonfirmasi' => ['color' => 'blue', 'label' => 'Sedang Diverifikasi'],
    'diproses' => ['color' => 'purple', 'label' => 'Sedang Diproses'],
    'dikirim' => ['color' => 'orange', 'label' => 'Dalam Pengiriman'],
    'selesai' => ['color' => 'green', 'label' => 'Selesai'],
    'dibatalkan' => ['color' => 'red', 'label' => 'Dibatalkan'],
];
$curr_status = $status_map[$transaksi->status_transaksi] ?? ['color' => 'gray', 'label' => $transaksi->status_transaksi];
?>

<div class="bg-gray-50 min-h-screen py-10 font-sans">
    <div class="max-w-3xl mx-auto px-4">
        
        <!-- Header Page -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Instruksi Pembayaran</h1>
            <p class="text-gray-500 text-sm">ID Pesanan: <span class="font-mono font-bold text-gray-800"><?php echo esc_html($trx_id); ?></span></p>
            
            <span class="inline-block mt-3 px-4 py-1 rounded-full text-xs font-bold uppercase tracking-wide bg-<?php echo $curr_status['color']; ?>-100 text-<?php echo $curr_status['color']; ?>-700 border border-<?php echo $curr_status['color']; ?>-200">
                <?php echo $curr_status['label']; ?>
            </span>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <!-- SUKSES UPLOAD -->
            <div class="bg-white border border-green-200 p-8 rounded-2xl text-center shadow-sm mb-8">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 text-green-600 animate-bounce">
                    <i class="fas fa-check text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Bukti Pembayaran Terkirim!</h3>
                <p class="text-gray-600 mb-6">Terima kasih. Penjual akan segera memverifikasi pembayaran Anda dan memproses pesanan.</p>
                <div class="flex justify-center gap-3">
                    <a href="<?php echo home_url('/akun-saya'); ?>" class="bg-gray-100 text-gray-700 font-bold py-2 px-6 rounded-lg hover:bg-gray-200 transition">Ke Dashboard</a>
                    <a href="<?php echo home_url('/produk'); ?>" class="bg-primary text-white font-bold py-2 px-6 rounded-lg hover:bg-green-700 transition">Belanja Lagi</a>
                </div>
            </div>
        <?php else: ?>

            <?php if ($transaksi->status_transaksi == 'menunggu_pembayaran'): ?>
                
                <!-- TOTAL TAGIHAN -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                    <div class="bg-gray-900 p-6 text-white flex justify-between items-center">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Total yang harus dibayar</p>
                            <h2 class="text-3xl font-bold"><?php echo tema_dw_format_rupiah($transaksi->total_transaksi); ?></h2>
                        </div>
                        <i class="fas fa-wallet text-4xl text-gray-700"></i>
                    </div>
                    
                    <div class="p-6 bg-yellow-50 border-b border-yellow-100">
                        <p class="text-sm text-yellow-800 flex items-start gap-2">
                            <i class="fas fa-info-circle mt-0.5"></i>
                            <span>Karena Anda membeli dari beberapa toko, silakan transfer sesuai nominal masing-masing toko di bawah ini.</span>
                        </p>
                    </div>
                </div>

                <!-- RINCIAN REKENING PER TOKO (LOOPING) -->
                <?php foreach ($list_tagihan as $tagihan): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6 relative overflow-hidden">
                    <div class="absolute top-0 left-0 w-1 h-full bg-primary"></div>
                    
                    <div class="flex justify-between items-start mb-4 border-b border-gray-100 pb-4">
                        <div>
                            <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                                <i class="fas fa-store text-gray-400"></i> <?php echo esc_html($tagihan->nama_toko); ?>
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">Nominal Transfer:</p>
                            <p class="text-lg font-bold text-primary"><?php echo tema_dw_format_rupiah($tagihan->total_pesanan_toko); ?></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Info Rekening Bank Toko -->
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase mb-2">Transfer Bank</p>
                            <?php if (!empty($tagihan->no_rekening)): ?>
                                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 group cursor-pointer hover:border-primary transition" 
                                     onclick="copyText('<?php echo esc_attr($tagihan->no_rekening); ?>')">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-gray-700"><?php echo esc_html($tagihan->nama_bank ?: 'Bank Transfer'); ?></span>
                                        <i class="far fa-copy text-gray-400 group-hover:text-primary"></i>
                                    </div>
                                    <p class="font-mono text-xl font-bold text-gray-900 tracking-wide mb-1"><?php echo esc_html($tagihan->no_rekening); ?></p>
                                    <p class="text-xs text-gray-500">a.n <?php echo esc_html($tagihan->atas_nama_rekening ?: $tagihan->nama_toko); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="bg-red-50 p-4 rounded-xl border border-red-100 text-red-600 text-sm">
                                    <i class="fas fa-exclamation-circle"></i> Toko ini belum mengatur rekening bank. Silakan hubungi penjual.
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- QRIS Toko -->
                        <div>
                            <p class="text-xs font-bold text-gray-400 uppercase mb-2">Scan QRIS</p>
                            <?php if (!empty($tagihan->qris_image_url)): ?>
                                <div class="bg-white border border-gray-200 p-2 rounded-xl inline-block shadow-sm">
                                    <img src="<?php echo esc_url($tagihan->qris_image_url); ?>" alt="QRIS" class="w-32 h-32 object-contain mx-auto">
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1 text-center">Scan menggunakan E-Wallet</p>
                            <?php else: ?>
                                <div class="h-full bg-gray-50 rounded-xl border border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-xs p-4 text-center">
                                    Tidak ada QRIS tersedia
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- FORM UPLOAD BUKTI -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-12">
                    <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-camera text-primary"></i> Konfirmasi Pembayaran
                    </h3>
                    
                    <div class="bg-blue-50 text-blue-800 text-sm p-4 rounded-lg mb-6">
                        Jika Anda melakukan transfer terpisah ke masing-masing toko, silakan gabungkan screenshot bukti transfer menjadi satu gambar, atau upload bukti transfer total jika berlaku.
                    </div>

                    <?php if (isset($error_msg)) echo '<div class="bg-red-50 text-red-600 p-3 rounded mb-4 text-sm font-bold">'.$error_msg.'</div>'; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:bg-gray-50 transition cursor-pointer relative">
                            <input type="file" name="bukti_bayar" id="file-upload" accept="image/*" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" onchange="previewFile()">
                            
                            <div id="preview-container" class="hidden">
                                <img id="preview-img" src="#" alt="Preview" class="max-h-48 mx-auto rounded-lg shadow-sm mb-2">
                                <p class="text-xs text-green-600 font-bold">File terpilih. Klik tombol di bawah untuk kirim.</p>
                            </div>
                            
                            <div id="upload-placeholder">
                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-300 mb-2"></i>
                                <p class="text-sm font-bold text-gray-600">Klik untuk upload bukti bayar</p>
                                <p class="text-xs text-gray-400 mt-1">Format: JPG, PNG. Maks 2MB.</p>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-green-500/30 transition-all transform active:scale-95">
                            Kirim Bukti Pembayaran
                        </button>
                    </form>
                </div>

            <?php else: ?>
                
                <!-- STATUS SUDAH BAYAR -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center max-w-xl mx-auto">
                    <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6 text-blue-500 animate-pulse">
                        <i class="fas fa-clock text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Sedang Diverifikasi</h3>
                    <p class="text-gray-500 mb-8 leading-relaxed">
                        Terima kasih! Bukti pembayaran Anda sudah kami terima. Penjual akan segera memverifikasi dan mengirimkan pesanan Anda.
                    </p>
                    
                    <?php if($transaksi->bukti_pembayaran): ?>
                        <div class="mb-8">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Bukti yang diupload</p>
                            <a href="<?php echo esc_url($transaksi->bukti_pembayaran); ?>" target="_blank" class="inline-block relative group">
                                <img src="<?php echo esc_url($transaksi->bukti_pembayaran); ?>" class="h-32 rounded-lg border border-gray-200 shadow-sm transition transform group-hover:scale-105">
                                <div class="absolute inset-0 bg-black/50 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                    <i class="fas fa-eye text-white"></i>
                                </div>
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="flex flex-col gap-3">
                        <a href="<?php echo home_url('/akun-saya'); ?>" class="w-full bg-gray-900 text-white font-bold py-3 rounded-xl hover:bg-gray-800 transition">Cek Status Pesanan</a>
                        <a href="https://wa.me/?text=Halo%20Admin,%20saya%20sudah%20bayar%20pesanan%20<?php echo $trx_id; ?>" target="_blank" class="w-full bg-white border border-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-50 transition">
                            <i class="fab fa-whatsapp text-green-500 mr-1"></i> Hubungi Admin
                        </a>
                    </div>
                </div>

            <?php endif; ?>

        <?php endif; ?>
        
    </div>
</div>

<!-- Script Copy Text & Preview -->
<script>
function copyText(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Nomor rekening disalin!');
    }, function(err) {
        console.error('Gagal menyalin: ', err);
    });
}

function previewFile() {
    const preview = document.getElementById('preview-img');
    const container = document.getElementById('preview-container');
    const placeholder = document.getElementById('upload-placeholder');
    const file = document.getElementById('file-upload').files[0];
    const reader = new FileReader();

    reader.onloadend = function () {
        preview.src = reader.result;
        container.classList.remove('hidden');
        placeholder.classList.add('hidden');
    }

    if (file) {
        reader.readAsDataURL(file);
    } else {
        preview.src = "";
        container.classList.add('hidden');
        placeholder.classList.remove('hidden');
    }
}
</script>

<?php get_footer(); ?>