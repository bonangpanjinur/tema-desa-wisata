<?php
/**
 * Template Name: Halaman Keranjang (Cart)
 */

get_header(); 
$cart_items = dw_get_cart_items(); // Mengambil data session dari helper di functions.php
?>

<div class="dw-cart-section section-padding">
    <div class="container">
        <h1 class="page-title mb-5 text-center">Keranjang Belanja</h1>

        <?php if (empty($cart_items)) : ?>
            <div class="empty-cart text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-shopping-basket fa-4x text-muted"></i>
                </div>
                <h3>Keranjang Anda Kosong</h3>
                <p class="text-muted mb-4">Ayo jelajahi produk desa wisata kami!</p>
                <a href="<?php echo home_url('/produk'); ?>" class="btn btn-primary">Belanja Sekarang</a>
            </div>
        <?php else : ?>
            <div class="row">
                <!-- List Produk -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produk</th>
                                            <th>Harga</th>
                                            <th>Jumlah</th>
                                            <th>Subtotal</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $key => $item) : 
                                            $subtotal = $item['price'] * $item['quantity'];
                                        ?>
                                        <tr data-cart-item-key="<?php echo esc_attr($key); ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if(!empty($item['image'])): ?>
                                                        <img src="<?php echo esc_url($item['image']); ?>" alt="<?php echo esc_attr($item['name']); ?>" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <?php endif; ?>
                                                    <div>
                                                        <h6 class="mb-0"><a href="<?php echo get_permalink($item['product_id']); ?>" class="text-decoration-none text-dark"><?php echo esc_html($item['name']); ?></a></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo dw_format_rupiah($item['price']); ?></td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm dw-cart-qty" style="width: 70px;" value="<?php echo esc_attr($item['quantity']); ?>" min="1">
                                            </td>
                                            <td class="fw-bold"><?php echo dw_format_rupiah($subtotal); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-danger remove-cart-item" data-key="<?php echo esc_attr($key); ?>"><i class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ringkasan Pesanan -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <h5 class="card-title mb-4">Ringkasan Pesanan</h5>
                            <div class="d-flex justify-content-between mb-3">
                                <span>Total Item</span>
                                <span><?php echo count($cart_items); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-4">
                                <span class="fw-bold">Total Belanja</span>
                                <span class="fw-bold text-primary fs-5"><?php echo dw_format_rupiah(dw_get_cart_total()); ?></span>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="<?php echo home_url('/checkout'); ?>" class="btn btn-primary btn-lg">Lanjut Pembayaran</a>
                                <a href="<?php echo home_url('/produk'); ?>" class="btn btn-outline-secondary">Lanjut Belanja</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>