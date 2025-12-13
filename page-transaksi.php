<?php
/* Template Name: Halaman Transaksi */

if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url( get_permalink() ) );
    exit;
}

get_header();

$current_user_id = get_current_user_id();
global $wpdb;

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'berjalan';

// Mapping status
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
        $status_filters = ["'menunggu_pembayaran'", "'menunggu_konfirmasi'", "'diproses'", "'diantar_ojek'", "'dikirim_ekspedisi'"];
        break;
}
$status_string = implode(',', $status_filters);

// Query Database
$table_main = $wpdb->prefix . 'dw_transaksi';
$table_sub  = $wpdb->prefix . 'dw_transaksi_sub';

// Safety check
if ( $wpdb->get_var("SHOW TABLES LIKE '$table_main'") != $table_main ) {
    echo '<div style="padding:20px; text-align:center;">Sistem transaksi sedang maintenance.</div>';
    get_footer(); exit;
}

$query = "";
if ($current_tab === 'berjalan') {
    $query = "SELECT s.*, m.kode_unik, m.status_transaksi as status_bayar, m.tanggal_transaksi 
        FROM $table_sub s JOIN $table_main m ON s.id_transaksi = m.id
        WHERE m.id_pembeli = %d AND (s.status_pesanan IN ($status_string) OR (m.status_transaksi = 'menunggu_pembayaran' AND s.status_pesanan != 'dibatalkan'))
        ORDER BY m.tanggal_transaksi DESC";
} else {
    $query = "SELECT s.*, m.kode_unik, m.status_transaksi as status_bayar, m.tanggal_transaksi 
        FROM $table_sub s JOIN $table_main m ON s.id_transaksi = m.id
        WHERE m.id_pembeli = %d AND s.status_pesanan IN ($status_string)
        ORDER BY m.tanggal_transaksi DESC";
}

$orders = $wpdb->get_results( $wpdb->prepare($query, $current_user_id) );
?>

<div class="app-container" style="padding-top: 0;">
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
                <p>Belum ada pesanan di sini.</p>
                <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="btn-track" style="margin-top:15px;">Mulai Belanja</a>
            </div>
        <?php else : foreach ( $orders as $order ) : 
            // Status Logic
            $status_label = ucfirst(str_replace('_', ' ', $order->status_pesanan));
            $status_class = 'status-dikirim';
            if ($order->status_bayar === 'menunggu_pembayaran') { $status_label = 'Belum Dibayar'; $status_class = 'status-batal'; }
            elseif ($order->status_pesanan === 'selesai') { $status_class = 'status-selesai'; }
            elseif ($order->status_pesanan === 'dibatalkan') { $status_class = 'status-batal'; }

            // Item Preview
            $table_items = $wpdb->prefix . 'dw_transaksi_items';
            $first_item = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_items WHERE id_sub_transaksi = %d LIMIT 1", $order->id) );
            $img_url = 'https://via.placeholder.com/150';
            if ($first_item) {
                $thumb_id = get_post_thumbnail_id($first_item->id_produk);
                if ($thumb_id) { $src = wp_get_attachment_image_src($thumb_id, 'thumbnail'); $img_url = $src[0]; }
            }
        ?>
        <div class="card-order">
            <div class="order-header">
                <span class="order-id">#<?php echo esc_html($order->kode_unik); ?></span>
                <span class="order-status <?php echo $status_class; ?>"><?php echo esc_html($status_label); ?></span>
            </div>
            <a href="<?php echo site_url('/detail-transaksi?id=' . $order->id_transaksi); ?>" class="order-item">
                <img src="<?php echo esc_url($img_url); ?>" class="order-img" alt="Produk">
                <div class="order-info">
                    <div class="order-name"><?php echo $first_item ? esc_html($first_item->nama_produk) : 'Produk'; ?></div>
                    <div class="order-meta">
                        <i class="fas fa-store"></i> <?php echo esc_html($order->nama_toko); ?> <br>
                        Total: <strong>Rp <?php echo number_format($order->total_pesanan_toko, 0, ',', '.'); ?></strong>
                    </div>
                </div>
            </a>
            <div style="border-top: 1px solid #eee; padding-top: 10px; margin-top: 10px; text-align: right;">
                <?php if ($order->status_bayar === 'menunggu_pembayaran') : ?>
                    <a href="<?php echo site_url('/detail-transaksi?id=' . $order->id_transaksi); ?>" class="btn-track" style="background: var(--accent); color: var(--text-dark); border: none;">Bayar</a>
                <?php else : ?>
                    <a href="<?php echo site_url('/detail-transaksi?id=' . $order->id_transaksi); ?>" class="btn-track" style="color:var(--text-grey); border-color:#ddd;">Detail</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<?php get_footer(); ?>