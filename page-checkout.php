<?php
/**
 * Template Name: Halaman Checkout
 */

// Cek Login, checkout butuh login
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login?redirect_to=' . urlencode(get_permalink())));
    exit;
}

// Cek Keranjang Kosong
if (empty(dw_get_cart_items())) {
    wp_redirect(home_url('/cart'));
    exit;
}

get_header(); 
$current_user = wp_get_current_user();
?>

<div class="dw-checkout-section section-padding">
    <div class="container">
        <h1 class="page-title mb-5 text-center">Checkout & Pembayaran</h1>

        <!-- Form mengarah ke admin-post.php untuk diproses oleh dw_handle_checkout_process di functions.php -->
        <form action="<?php echo admin_url('admin-post.php'); ?>" method="POST" id="dw-checkout-form">
            <input type="hidden" name="action" value="dw_process_checkout"> 
            <?php wp_nonce_field('dw_checkout_action', 'dw_checkout_nonce'); ?>

            <div class="row">
                <!-- Form Data Pembeli -->
                <div class="col-md-7">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Informasi Pengiriman</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="billing_name" value="<?php echo esc_attr($current_user->display_name); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="billing_email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nomor WhatsApp</label>
                                    <input type="tel" class="form-control" name="billing_phone" required placeholder="08...">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Alamat Lengkap</label>
                                    <textarea class="form-control" name="billing_address" rows="3" required placeholder="Alamat jalan, RT/RW, Kelurahan, Kecamatan..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Catatan Pesanan (Opsional)</label>
                                    <textarea class="form-control" name="order_note" rows="2" placeholder="Catatan khusus untuk penjual..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan & Pembayaran -->
                <div class="col-md-5">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0">Ringkasan Pesanan</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush mb-3">
                                <?php foreach (dw_get_cart_items() as $item) : ?>
                                    <li class="list-group-item d-flex justify-content-between lh-sm">
                                        <div>
                                            <h6 class="my-0"><?php echo esc_html($item['name']); ?></h6>
                                            <small class="text-muted">Qty: <?php echo $item['quantity']; ?></small>
                                        </div>
                                        <span class="text-muted"><?php echo dw_format_rupiah($item['price'] * $item['quantity']); ?></span>
                                    </li>
                                <?php endforeach; ?>
                                <li class="list-group-item d-flex justify-content-between bg-light fw-bold">
                                    <span>Total (IDR)</span>
                                    <strong><?php echo dw_format_rupiah(dw_get_cart_total()); ?></strong>
                                </li>
                            </ul>

                            <h5 class="mb-3">Metode Pembayaran</h5>
                            <div class="payment-methods mb-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pay_transfer" value="transfer" checked>
                                    <label class="form-check-label" for="pay_transfer">
                                        Transfer Bank (Manual)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="cod">
                                    <label class="form-check-label" for="pay_cod">
                                        Bayar di Tempat (COD)
                                    </label>
                                </div>
                            </div>

                            <button class="btn btn-primary w-100 btn-lg" type="submit">Buat Pesanan</button>
                            <small class="d-block text-center mt-2 text-muted">Pastikan data pengiriman sudah benar.</small>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>