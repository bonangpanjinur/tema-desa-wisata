 <?php
/**
 * Template Name: Single Transaksi
 * Digunakan untuk menampilkan detail order (Post Type: dw_transaksi)
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login'));
    exit;
}

get_header();

while (have_posts()) : the_post();
    $order_id = get_the_ID();
    $current_user_id = get_current_user_id();
    $buyer_id = get_post_meta($order_id, '_dw_order_buyer_id', true);
    
    // Keamanan: Pastikan yang melihat adalah Pembeli Ybs, Admin, atau (Nanti) Penjual Terkait
    // Untuk fase ini kita cek pembeli dan admin
    $is_authorized = ($current_user_id == $buyer_id) || current_user_can('manage_options');
    
    // Jika user adalah pedagang, cek apakah produk mereka ada di order ini (Logic kompleks, kita skip simple dulu atau allow role pedagang)
    if (!$is_authorized && dw_current_user_has_role('dw_pedagang')) {
        $is_authorized = true; // Sementara allow pedagang melihat detail (ideal filtered by item)
    }

    if (!$is_authorized) {
        echo '<div class="container py-5"><div class="alert alert-danger">Anda tidak memiliki akses ke pesanan ini.</div></div>';
        get_footer();
        exit;
    }

    // Ambil Data Meta
    $recipient_name = get_post_meta($order_id, '_dw_order_recipient_name', true);
    $phone          = get_post_meta($order_id, '_dw_order_phone', true);
    $address        = get_post_meta($order_id, '_dw_order_address', true);
    $status         = get_post_meta($order_id, '_dw_order_status', true);
    $total          = get_post_meta($order_id, '_dw_order_total', true);
    $shipping_cost  = get_post_meta($order_id, '_dw_order_shipping_cost', true);
    $items          = get_post_meta($order_id, '_dw_order_items', true);

    // Styling Badge Status
    $status_badges = [
        'pending_payment' => 'bg-warning text-dark',
        'processing'      => 'bg-info text-white',
        'ready_for_pickup'=> 'bg-primary',
        'shipping'        => 'bg-primary',
        'completed'       => 'bg-success',
        'cancelled'       => 'bg-danger'
    ];
    $badge_class = isset($status_badges[$status]) ? $status_badges[$status] : 'bg-secondary';
    $status_label = ucwords(str_replace('_', ' ', $status));
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Detail Pesanan #<?php the_ID(); ?></h1>
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>

            <!-- Status Card -->
            <div class="card shadow-sm mb-4 border-top-0 border-end-0 border-bottom-0 border-start border-5 border-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted d-block">Status Pesanan</small>
                        <span class="badge <?php echo $badge_class; ?> fs-6 mt-1"><?php echo esc_html($status_label); ?></span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Tanggal Order</small>
                        <strong><?php echo get_the_date('d F Y, H:i'); ?></strong>
                    </div>
                </div>
            </div>

            <!-- Detail Item -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Item Pesanan</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Produk</th>
                                    <th class="text-end">Harga</th>
                                    <th class="text-center">Jml</th>
                                    <th class="text-end pe-4">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $subtotal_items = 0;
                                if (is_array($items)) : 
                                    foreach ($items as $item) : 
                                        $item_total = $item['price'] * $item['quantity'];
                                        $subtotal_items += $item_total;
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <strong><?php echo esc_html($item['name']); ?></strong>
                                    </td>
                                    <td class="text-end">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                                    <td class="text-center"><?php echo esc_html($item['quantity']); ?></td>
                                    <td class="text-end pe-4 fw-bold">Rp <?php echo number_format($item_total, 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <div class="row">
                        <div class="col-md-6 offset-md-6">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal Produk</span>
                                <span>Rp <?php echo number_format($subtotal_items, 0, ',', '.'); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Ongkos Kirim</span>
                                <span>Rp <?php echo number_format($shipping_cost, 0, ',', '.'); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fs-5 fw-bold text-primary">
                                <span>Total Pembayaran</span>
                                <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Pengiriman -->
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Informasi Pengiriman</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <h6 class="text-muted text-uppercase small fw-bold">Penerima</h6>
                            <p class="mb-1 fw-bold"><?php echo esc_html($recipient_name); ?></p>
                            <p class="mb-0"><i class="fas fa-phone-alt text-muted me-2"></i> <?php echo esc_html($phone); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted text-uppercase small fw-bold">Alamat Tujuan</h6>
                            <p class="mb-0"><?php echo nl2br(esc_html($address)); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi (Opsional) -->
            <?php if ($status === 'pending_payment' && $current_user_id == $buyer_id) : ?>
                <div class="mt-4 d-grid">
                    <button class="btn btn-success btn-lg" onclick="alert('Redirect ke Payment Gateway / Info Rekening')">
                        <i class="fas fa-credit-card me-2"></i> Bayar Sekarang
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endwhile; ?>
<?php get_footer(); ?>