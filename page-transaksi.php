<?php
/* Template Name: Halaman Transaksi */

// 1. Cek Login
if ( ! is_user_logged_in() ) {
    wp_redirect( site_url('/login') );
    exit;
}

get_header();

$current_user_id = get_current_user_id();
global $wpdb;

// 2. Logika Tab & Filter Status
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'berjalan';

// Mapping status untuk setiap tab
$status_filters = [];
switch ($current_tab) {
    case 'selesai':
        $status_filters = ["'selesai'"];
        break;
    case 'dibatalkan':
        $status_filters = ["'dibatalkan'", "'pembayaran_gagal'", "'refunded'"];
        break;
    case 'berjalan':
    default:
        // Status berjalan mencakup menunggu bayar sampai dikirim
        $status_filters = ["'menunggu_pembayaran'", "'menunggu_konfirmasi'", "'diproses'", "'diantar_ojek'", "'dikirim_ekspedisi'"];
        break;
}
$status_string = implode(',', $status_filters);

// 3. Query Database (Join Transaksi Utama & Sub)
// Kita mengambil Sub-Transaksi (per Toko) karena status pengiriman ada di situ.
// Kecuali status 'menunggu_pembayaran' yang ada di Transaksi Utama.

$table_main = $wpdb->prefix . 'dw_transaksi';
$table_sub  = $wpdb->prefix . 'dw_transaksi_sub';

// Query yang agak kompleks untuk menangani logika status gabungan
if ($current_tab === 'berjalan') {
    // Untuk tab berjalan, kita cari yang sub-statusnya aktif ATAU main-statusnya menunggu pembayaran
    $query = "
        SELECT s.*, m.kode_unik, m.status_transaksi as status_bayar, m.tanggal_transaksi 
        FROM $table_sub s
        JOIN $table_main m ON s.id_transaksi = m.id
        WHERE m.id_pembeli = %d
        AND (
            s.status_pesanan IN ($status_string) 
            OR (m.status_transaksi = 'menunggu_pembayaran' AND s.status_pesanan != 'dibatalkan')
        )
        ORDER BY m.tanggal_transaksi DESC
    ";
} else {
    // Untuk selesai/batal, cukup cek status sub order
    $query = "
        SELECT s.*, m.kode_unik, m.status_transaksi as status_bayar, m.tanggal_transaksi 
        FROM $table_sub s
        JOIN $table_main m ON s.id_transaksi = m.id
        WHERE m.id_pembeli = %d
        AND s.status_pesanan IN ($status_string)
        ORDER BY m.tanggal_transaksi DESC
    ";
}

$orders = $wpdb->get_results( $wpdb->prepare($query, $current_user_id) );

?>

<!-- Container Halaman -->
<div class="app-container" style="padding-top: 0;">

    <!-- Header Judul (Mobile Style) -->
    <div class="section-header" style="background: var(--white); box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 0; padding-top: 20px;">
        <div class="section-title">
            <h3>Pesanan <span>Saya</span></h3>
        </div>
    </div>

    <!-- Tabs Navigasi -->
    <div class="tabs">
        <a href="?tab=berjalan" class="tab <?php echo $current_tab === 'berjalan' ? 'active' : ''; ?>">Berjalan</a>
        <a href="?tab=selesai" class="tab <?php echo $current_tab === 'selesai' ? 'active' : ''; ?>">Selesai</a>
        <a href="?tab=dibatalkan" class="tab <?php echo $current_tab === 'dibatalkan' ? 'active' : ''; ?>">Dibatalkan</a>
    </div>

    <!-- Daftar Pesanan -->
    <div class="order-list">
        <?php if ( empty($orders) ) : ?>
            <div style="text-align: center; padding: 50px 20px; color: var(--text-grey);">
                <i class="fas fa-shopping-bag" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                <p>Belum ada pesanan di tab ini.</p>
                <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="btn-visit" style="margin-top: 15px; display: inline-block;">Mulai Belanja</a>
            </div>
        <?php else : ?>
            
            <?php foreach ( $orders as $order ) : 
                // Tentukan Status Label & Warna
                $status_label = '';
                $status_class = '';
                
                // Prioritas Status: Cek Pembayaran dulu, baru Pengiriman
                if ($order->status_bayar === 'menunggu_pembayaran') {
                    $status_label = 'Belum Dibayar';
                    $status_class = 'status-batal'; // Merah muda/kuning
                } elseif ($order->status_pesanan === 'selesai') {
                    $status_label = 'Selesai';
                    $status_class = 'status-selesai';
                } elseif ($order->status_pesanan === 'dibatalkan') {
                    $status_label = 'Dibatalkan';
                    $status_class = 'status-batal';
                } else {
                    // Status pengiriman (diproses, dikirim, dll)
                    $status_label = ucfirst(str_replace('_', ' ', $order->status_pesanan));
                    $status_class = 'status-dikirim';
                }

                // Ambil 1 Item Pertama untuk Preview Gambar
                $table_items = $wpdb->prefix . 'dw_transaksi_items';
                $first_item = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_items WHERE id_sub_transaksi = %d LIMIT 1", $order->id) );
                
                // Hitung total item
                $total_items_count = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table_items WHERE id_sub_transaksi = %d", $order->id) );
                
                // Gambar Produk
                $img_url = 'https://via.placeholder.com/150';
                if ($first_item) {
                    $thumb_id = get_post_thumbnail_id($first_item->id_produk);
                    if ($thumb_id) {
                        $img_src = wp_get_attachment_image_src($thumb_id, 'thumbnail');
                        $img_url = $img_src[0];
                    }
                }
            ?>
            
            <div class="card-order">
                <!-- Header Card: ID & Status -->
                <div class="order-header">
                    <span class="order-id">#<?php echo esc_html($order->kode_unik); ?></span>
                    <span class="order-status <?php echo $status_class; ?>"><?php echo esc_html($status_label); ?></span>
                </div>

                <!-- Body Card: Item Info -->
                <a href="<?php echo site_url('/detail-transaksi?id=' . $order->id_transaksi); ?>" class="order-item">
                    <img src="<?php echo esc_url($img_url); ?>" class="order-img" alt="Produk">
                    <div class="order-info">
                        <div class="order-name">
                            <?php echo $first_item ? esc_html($first_item->nama_produk) : 'Produk dihapus'; ?>
                            <?php if ($total_items_count > 1) echo " <small>+ " . ($total_items_count - 1) . " produk lainnya</small>"; ?>
                        </div>
                        <div class="order-meta">
                            <i class="fas fa-store"></i> <?php echo esc_html($order->nama_toko); ?> <br>
                            <?php echo date_i18n('d M Y', strtotime($order->tanggal_transaksi)); ?>
                        </div>
                        <div class="order-total">
                            <span style="font-weight: 700;">Total: <?php echo dw_format_rupiah($order->total_pesanan_toko); ?></span>
                        </div>
                    </div>
                </a>

                <!-- Footer Card: Action Buttons -->
                <div style="border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px; text-align: right;">
                    
                    <?php if ($order->status_bayar === 'menunggu_pembayaran') : ?>
                        <a href="<?php echo site_url('/detail-transaksi?id=' . $order->id_transaksi); ?>" class="btn-track" style="background: var(--accent); color: var(--text-dark); border: none;">Bayar Sekarang</a>
                    
                    <?php elseif ( in_array($order->status_pesanan, ['dikirim_ekspedisi', 'diantar_ojek']) ) : ?>
                        <?php if ($order->no_resi) : ?>
                            <button class="btn-track" onclick="alert('Nomor Resi: <?php echo esc_js($order->no_resi); ?>')">Lacak Resi</button>
                        <?php else: ?>
                            <button class="btn-track">Lacak</button>
                        <?php endif; ?>
                        
                        <!-- Tombol Konfirmasi Terima (Bisa ditambahkan AJAX logic nanti) -->
                        <button class="btn-track" style="background: var(--primary); color: white; border: none;">Diterima</button>

                    <?php elseif ($order->status_pesanan === 'selesai') : ?>
                        <a href="<?php echo site_url('/tulis-ulasan?id=' . $order->id); ?>" class="btn-track">Beri Ulasan</a>
                        <a href="<?php echo get_permalink($first_item->id_produk); ?>" class="btn-track" style="background: var(--primary-light); border:none;">Beli Lagi</a>
                    
                    <?php endif; ?>

                    <a href="<?php echo site_url('/detail-transaksi?id=' . $order->id_transaksi); ?>" class="btn-track" style="border: none; color: var(--text-grey);">Detail</a>
                </div>
            </div>

            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<?php get_footer(); ?>