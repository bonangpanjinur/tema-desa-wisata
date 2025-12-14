<?php
/**
 * Template Name: Halaman Detail Transaksi
 */

if ( ! is_user_logged_in() || ! isset($_GET['id']) ) {
    wp_redirect( home_url('/transaksi') );
    exit;
}

get_header(); 
global $wpdb;

$order_id = intval($_GET['id']); // Ini adalah ID Transaksi Induk (Parent)
$user_id = get_current_user_id();

// 1. Ambil Data Transaksi Utama
$tbl_transaksi = $wpdb->prefix . 'dw_transaksi';
$transaksi = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tbl_transaksi WHERE id = %d AND id_pembeli = %d", 
    $order_id, $user_id
));

if (!$transaksi) {
    echo '<div class="container py-20 text-center">Pesanan tidak ditemukan.</div>';
    get_footer(); exit;
}

// 2. Ambil Sub Transaksi (Per Toko)
$tbl_sub = $wpdb->prefix . 'dw_transaksi_sub';
$sub_transaksi = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tbl_sub WHERE id_transaksi = %d", 
    $order_id
));

// 3. Helper Status Badge
function dw_get_status_badge($status) {
    $colors = [
        'menunggu_pembayaran' => 'bg-orange-100 text-orange-700',
        'menunggu_konfirmasi' => 'bg-yellow-100 text-yellow-700',
        'diproses'            => 'bg-blue-100 text-blue-700',
        'dikirim_ekspedisi'   => 'bg-purple-100 text-purple-700',
        'selesai'             => 'bg-green-100 text-green-700',
        'dibatalkan'          => 'bg-red-100 text-red-700',
    ];
    $cls = isset($colors[$status]) ? $colors[$status] : 'bg-gray-100 text-gray-600';
    $label = ucwords(str_replace('_', ' ', $status));
    return "<span class='px-3 py-1 rounded-full text-xs font-bold $cls'>$label</span>";
}

// Decode Alamat JSON
$alamat_kirim = json_decode($transaksi->alamat_pengiriman);
// Fallback jika bukan JSON (legacy support)
$nama_penerima = is_object($alamat_kirim) ? $alamat_kirim->nama : $transaksi->nama_penerima;
$no_hp = is_object($alamat_kirim) ? $alamat_kirim->hp : $transaksi->no_hp;
$alamat_lengkap = is_object($alamat_kirim) ? $alamat_kirim->alamat : $transaksi->alamat_lengkap_snapshot;
?>

<div class="bg-gray-50 min-h-screen pb-24">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="container mx-auto px-4 h-16 flex items-center gap-4">
            <a href="<?php echo home_url('/transaksi'); ?>" class="text-gray-500 hover:text-gray-900"><i class="fas fa-arrow-left text-xl"></i></a>
            <h1 class="font-bold text-lg text-gray-800">Detail Pesanan #<?php echo esc_html($transaksi->kode_unik); ?></h1>
        </div>
    </div>

    <div class="container mx-auto px-4 py-6 max-w-2xl">
        
        <!-- Status Utama -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-4">
            <div class="flex justify-between items-center mb-4">
                <div class="text-sm text-gray-500">Tanggal: <span class="font-bold text-gray-900"><?php echo date('d M Y H:i', strtotime($transaksi->created_at)); ?></span></div>
                <?php echo dw_get_status_badge($transaksi->status_transaksi); ?>
            </div>
            
            <?php if($transaksi->status_transaksi == 'menunggu_pembayaran'): ?>
            <div class="bg-orange-50 border border-orange-100 p-4 rounded-xl mb-3">
                <p class="text-sm text-orange-800 mb-2">Silakan transfer ke rekening berikut:</p>
                <div class="font-bold text-lg text-gray-800">BCA 1234567890 <span class="text-sm font-normal text-gray-500">(a.n Desa Wisata)</span></div>
                <div class="font-bold text-lg text-gray-800">BRI 0987654321 <span class="text-sm font-normal text-gray-500">(a.n Desa Wisata)</span></div>
            </div>
            <!-- Tombol Konfirmasi (Placeholder Link WA) -->
            <a href="https://wa.me/?text=Konfirmasi%20Pesanan%20<?php echo $transaksi->kode_unik; ?>" target="_blank" class="block w-full bg-primary text-white py-3 rounded-xl font-bold shadow-lg text-center hover:bg-green-700 transition">
                Konfirmasi Pembayaran
            </a>
            <?php endif; ?>
        </div>

        <!-- Rincian Per Toko -->
        <?php foreach($sub_transaksi as $sub): 
            $tbl_items = $wpdb->prefix . 'dw_transaksi_items';
            $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $tbl_items WHERE id_sub_transaksi = %d", $sub->id));
        ?>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-4">
            <div class="flex justify-between items-center mb-3 pb-3 border-b border-dashed border-gray-100">
                <h3 class="font-bold text-sm text-gray-900 flex items-center gap-2">
                    <i class="fas fa-store text-gray-400"></i> <?php echo esc_html($sub->nama_toko); ?>
                </h3>
                <?php echo dw_get_status_badge($sub->status_pesanan); ?>
            </div>

            <div class="space-y-4">
                <?php foreach($items as $item): 
                    // Ambil gambar produk asli
                    $img_url = get_the_post_thumbnail_url($item->id_produk, 'thumbnail') ?: 'https://via.placeholder.com/150';
                ?>
                <div class="flex gap-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 border border-gray-200">
                        <img src="<?php echo esc_url($img_url); ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-gray-800 line-clamp-1"><?php echo esc_html($item->nama_produk); ?></h4>
                        <p class="text-xs text-gray-500"><?php echo $item->kuantitas; ?> x <?php echo dw_format_rupiah($item->harga_satuan); ?></p>
                    </div>
                    <div class="text-sm font-bold text-gray-900">
                        <?php echo dw_format_rupiah($item->total_harga); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-between items-center">
                <span class="text-xs text-gray-500">Subtotal Toko</span>
                <span class="font-bold text-gray-800"><?php echo dw_format_rupiah($sub->total_pesanan_toko); ?></span>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Info Pengiriman -->
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
            <h3 class="font-bold text-sm text-gray-900 mb-3">Info Pengiriman</h3>
            <div class="text-sm text-gray-600 space-y-1">
                <p><span class="font-semibold">Penerima:</span> <?php echo esc_html($nama_penerima); ?></p>
                <p><span class="font-semibold">No HP:</span> <?php echo esc_html($no_hp); ?></p>
                <p><span class="font-semibold">Alamat:</span> <?php echo esc_html($alamat_lengkap); ?></p>
            </div>
        </div>

        <!-- Total Akhir Sticky -->
        <div class="fixed bottom-0 left-0 right-0 bg-white p-4 border-t border-gray-100 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <div class="container mx-auto max-w-2xl flex justify-between items-center">
                <span class="text-gray-600 font-medium">Total Bayar</span>
                <span class="text-xl font-bold text-primary"><?php echo dw_format_rupiah($transaksi->total_akhir); ?></span>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>