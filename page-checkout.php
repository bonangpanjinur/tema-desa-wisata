<?php
/**
 * Template Name: Halaman Checkout
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/login?redirect_to=' . urlencode(get_permalink())));
    exit;
}

if (empty(dw_get_cart_items())) {
    wp_redirect(home_url('/cart'));
    exit;
}

get_header(); 
$current_user = wp_get_current_user();
?>

<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-5xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6 text-center">Checkout & Pembayaran</h1>

        <form action="<?php echo admin_url('admin-post.php'); ?>" method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-8">
            <input type="hidden" name="action" value="dw_process_checkout"> 
            <?php wp_nonce_field('dw_checkout_action', 'dw_checkout_nonce'); ?>

            <!-- Form Data Pembeli -->
            <div class="md:col-span-7 space-y-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                    <h3 class="font-bold text-lg text-gray-800 mb-4 border-b border-gray-100 pb-3">Informasi Pengiriman</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                            <input type="text" name="billing_name" value="<?php echo esc_attr($current_user->display_name); ?>" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="billing_email" value="<?php echo esc_attr($current_user->user_email); ?>" required class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary bg-gray-50" readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor WhatsApp</label>
                                <input type="tel" name="billing_phone" required placeholder="08..." class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alamat Lengkap</label>
                            <textarea name="billing_address" rows="3" required placeholder="Jalan, RT/RW, Kelurahan, Kecamatan..." class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                            <textarea name="order_note" rows="2" placeholder="Pesan untuk penjual..." class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ringkasan & Payment -->
            <div class="md:col-span-5">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 sticky top-24">
                    <h3 class="font-bold text-lg text-gray-800 mb-4 border-b border-gray-100 pb-3">Ringkasan Pesanan</h3>
                    
                    <div class="space-y-3 mb-4 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                        <?php foreach (dw_get_cart_items() as $item) : ?>
                            <div class="flex justify-between items-start text-sm">
                                <div>
                                    <p class="font-medium text-gray-800"><?php echo esc_html($item['name']); ?></p>
                                    <p class="text-xs text-gray-500">Qty: <?php echo $item['quantity']; ?></p>
                                </div>
                                <span class="font-bold text-gray-700"><?php echo dw_format_rupiah($item['price'] * $item['quantity']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl mb-4 flex justify-between items-center">
                        <span class="font-bold text-gray-700">Total Tagihan</span>
                        <span class="font-bold text-xl text-primary"><?php echo dw_format_rupiah(dw_get_cart_total()); ?></span>
                    </div>

                    <h4 class="font-bold text-sm text-gray-700 mb-3">Metode Pembayaran</h4>
                    <div class="space-y-3 mb-6">
                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-primary transition bg-white">
                            <input type="radio" name="payment_method" value="transfer" checked class="text-primary focus:ring-primary">
                            <div>
                                <span class="block font-bold text-sm text-gray-800">Transfer Bank</span>
                                <span class="block text-xs text-gray-500">Verifikasi manual via WhatsApp</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl cursor-pointer hover:border-primary transition bg-white opacity-50">
                            <input type="radio" name="payment_method" value="cod" disabled class="text-primary focus:ring-primary">
                            <div>
                                <span class="block font-bold text-sm text-gray-800">COD (Bayar di Tempat)</span>
                                <span class="block text-xs text-gray-500">Belum tersedia untuk lokasi Anda</span>
                            </div>
                        </label>
                    </div>

                    <button type="submit" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3.5 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                        Buat Pesanan
                    </button>
                    <p class="text-center text-xs text-gray-400 mt-3">Data Anda aman bersama kami.</p>
                </div>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>