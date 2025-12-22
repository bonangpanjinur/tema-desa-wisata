<?php
/**
 * Template Part: List Transaksi
 * Bisa dipakai untuk Pembeli atau Pedagang
 */

$current_user_id = get_current_user_id();
$context = isset($args['context']) ? $args['context'] : 'buyer'; // 'buyer' atau 'seller'

// Setup Query Transaksi
$query_args = [
    'post_type'      => 'dw_transaksi',
    'post_status'    => 'publish',
    'posts_per_page' => 10,
    'paged'          => get_query_var('paged') ? get_query_var('paged') : 1,
];

if ($context === 'buyer') {
    // Pembeli melihat order milik mereka sendiri (Author dari post order)
    $query_args['author'] = $current_user_id;
} else {
    // Pedagang melihat order yang mengandung produk mereka
    // Note: Logika real multi-vendor kompleks.
    // Di sini kita asumsikan query meta sederhana atau semua order (jika admin desa/pedagang tunggal)
    // Untuk tahap ini, kita tampilkan semua order dulu sebagai simulasi, nanti difilter by item di loop
    // Atau gunakan meta query jika struktur DB mendukung '_seller_id' di level order (jika 1 order = 1 seller)
}

$transaksi_query = new WP_Query($query_args);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">
        <?php echo ($context === 'seller') ? 'Pesanan Masuk' : 'Riwayat Belanja'; ?>
    </h4>
</div>

<?php if ($transaksi_query->have_posts()) : ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID Order</th>
                    <th>Tanggal</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($transaksi_query->have_posts()) : $transaksi_query->the_post(); 
                    $order_id = get_the_ID();
                    $total = get_post_meta($order_id, '_dw_order_total', true);
                    $status = get_post_meta($order_id, '_dw_order_status', true);
                    
                    // Format Status Badge
                    $status_class = 'bg-secondary';
                    if ($status == 'completed') $status_class = 'bg-success';
                    if ($status == 'pending_payment') $status_class = 'bg-warning text-dark';
                    if ($status == 'cancelled') $status_class = 'bg-danger';
                ?>
                <tr>
                    <td>#<?php echo $order_id; ?></td>
                    <td><?php echo get_the_date('d M Y'); ?></td>
                    <td>Rp <?php echo number_format($total, 0, ',', '.'); ?></td>
                    <td><span class="badge <?php echo $status_class; ?>"><?php echo esc_html(strtoupper(str_replace('_', ' ', $status))); ?></span></td>
                    <td>
                        <a href="<?php echo home_url('/detail-transaksi?id=' . $order_id); ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> Detail
                        </a>
                        <?php if ($context === 'seller') : ?>
                             <button class="btn btn-sm btn-success" onclick="alert('Fitur Update Status Order (Segera Hadir)');"><i class="fas fa-check"></i> Proses</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-3">
        <?php
        echo paginate_links([
            'total' => $transaksi_query->max_num_pages,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'type' => 'list',
        ]);
        ?>
    </div>

<?php else : ?>
    <div class="alert alert-light text-center border">
        Belum ada data transaksi.
    </div>
<?php endif; wp_reset_postdata(); ?>