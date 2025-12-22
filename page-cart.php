<?php
/**
 * Template Name: Keranjang Belanja (AJAX Version)
 * Description: Menampilkan keranjang belanja dengan update quantity via AJAX.
 */

if (!session_id()) session_start();
get_header();

global $wpdb;
$user_id    = get_current_user_id();
$session_id = session_id();
$table_cart = $wpdb->prefix . 'dw_cart';

// --- QUERY DATA KERANJANG ---
$sql = $wpdb->prepare(
    "SELECT c.id as cart_id, c.qty, 
            p.id as product_id, p.nama_produk, p.harga, p.foto_utama, p.slug as slug_produk, p.stok,
            t.id as toko_id, t.nama_toko, t.kabupaten_nama
     FROM $table_cart c
     JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
     JOIN {$wpdb->prefix}dw_pedagang t ON p.id_pedagang = t.id
     WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)
     ORDER BY t.nama_toko ASC, c.created_at DESC",
    $user_id, $session_id
);

$cart_items = $wpdb->get_results($sql);

// Grouping by Toko
$grouped_items = [];
$grand_total = 0;
$total_count = 0;

foreach ($cart_items as $item) {
    $grouped_items[$item->toko_id]['info'] = [
        'nama' => $item->nama_toko,
        'lokasi' => $item->kabupaten_nama
    ];
    $grouped_items[$item->toko_id]['items'][] = $item;
    $grand_total += ($item->harga * $item->qty);
    $total_count += $item->qty;
}
?>

<div class="bg-gray-50 min-h-screen py-10 font-sans">
    <div class="max-w-6xl mx-auto px-4">
        
        <h1 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
            <i class="fas fa-shopping-cart text-primary"></i> Keranjang Belanja
        </h1>

        <!-- Notifikasi AJAX -->
        <div id="cart-notification" class="hidden fixed top-24 right-4 z-50 bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 transition-all">
            <i class="fas fa-info-circle"></i> <span class="msg">Updating...</span>
        </div>

        <?php if (empty($grouped_items)) : ?>
            <!-- EMPTY STATE -->
            <div id="empty-cart-state" class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-shopping-basket text-4xl text-gray-300"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Keranjang Kosong</h2>
                <p class="text-gray-500 mb-6">Wah, keranjang belanjaanmu masih kosong nih.</p>
                <a href="<?php echo home_url('/produk'); ?>" class="bg-primary hover:bg-green-700 text-white font-bold py-3 px-8 rounded-full transition-all shadow-lg">
                    Mulai Belanja
                </a>
            </div>
        <?php else : ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" id="cart-content-wrapper">
                
                <!-- LIST ITEM (KIRI) -->
                <div class="lg:col-span-2 space-y-6">
                    <?php foreach ($grouped_items as $toko_id => $group) : ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden cart-group-row">
                        
                        <!-- Header Toko -->
                        <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 flex items-center gap-2">
                            <i class="fas fa-store text-primary"></i>
                            <span class="font-bold text-gray-800"><?php echo esc_html($group['info']['nama']); ?></span>
                            <span class="text-xs text-gray-400">• <?php echo esc_html($group['info']['lokasi']); ?></span>
                        </div>

                        <!-- List Produk -->
                        <div class="divide-y divide-gray-50">
                            <?php foreach ($group['items'] as $item) : 
                                $foto = !empty($item->foto_utama) ? $item->foto_utama : 'https://via.placeholder.com/150';
                            ?>
                            <div class="p-5 flex gap-4 items-start cart-item-row" id="row-<?php echo $item->cart_id; ?>">
                                <!-- Gambar -->
                                <a href="<?php echo home_url('/produk/'.$item->slug_produk); ?>" class="shrink-0 w-20 h-20 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                    <img src="<?php echo esc_url($foto); ?>" class="w-full h-full object-cover">
                                </a>

                                <!-- Info & Kontrol -->
                                <div class="flex-grow flex flex-col justify-between">
                                    <div class="flex justify-between items-start">
                                        <h3 class="text-sm font-bold text-gray-800 line-clamp-2 w-3/4">
                                            <a href="<?php echo home_url('/produk/'.$item->slug_produk); ?>" class="hover:text-primary"><?php echo esc_html($item->nama_produk); ?></a>
                                        </h3>
                                        <!-- Tombol Hapus AJAX -->
                                        <button type="button" class="text-gray-400 hover:text-red-500 js-remove-cart-item" 
                                                data-cart-id="<?php echo $item->cart_id; ?>" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>

                                    <div class="flex justify-between items-end mt-2">
                                        <div class="text-primary font-bold"><?php echo tema_dw_format_rupiah($item->harga); ?></div>
                                        
                                        <!-- Qty Control (AJAX) -->
                                        <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden h-8 bg-white">
                                            <button type="button" class="w-8 h-full flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 js-update-qty" 
                                                    data-action="decrease" data-cart-id="<?php echo $item->cart_id; ?>">-</button>
                                            
                                            <input type="text" id="qty-<?php echo $item->cart_id; ?>" 
                                                   value="<?php echo $item->qty; ?>" 
                                                   class="w-10 text-center border-none text-xs font-bold p-0 focus:ring-0 text-gray-800" readonly>
                                            
                                            <button type="button" class="w-8 h-full flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-600 js-update-qty" 
                                                    data-action="increase" data-cart-id="<?php echo $item->cart_id; ?>">+</button>
                                        </div>
                                    </div>
                                    
                                    <!-- Indikator Loading per Item -->
                                    <div id="loader-<?php echo $item->cart_id; ?>" class="hidden text-[10px] text-gray-400 mt-1">Mengupdate...</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- RINGKASAN BELANJA (KANAN) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 sticky top-24">
                        <h3 class="font-bold text-gray-800 mb-4 text-lg">Ringkasan Belanja</h3>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Total Barang</span>
                                <span id="summary-items-count"><?php echo $total_count; ?> Barang</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Diskon</span>
                                <span class="text-green-600">Rp 0</span>
                            </div>
                        </div>

                        <div class="border-t border-dashed border-gray-200 pt-4 mb-6">
                            <div class="flex justify-between items-end">
                                <span class="font-bold text-gray-800">Total Tagihan</span>
                                <span class="text-xl font-bold text-primary" id="summary-grand-total"><?php echo tema_dw_format_rupiah($grand_total); ?></span>
                            </div>
                        </div>

                        <a href="<?php echo home_url('/checkout'); ?>" class="block w-full bg-primary hover:bg-green-700 text-white text-center font-bold py-3.5 rounded-xl shadow-lg shadow-green-500/30 transition-all transform active:scale-95">
                            Beli (<span id="btn-buy-count"><?php echo $total_count; ?></span>)
                        </a>
                        
                        <div class="mt-4 text-center">
                            <span class="text-[10px] text-gray-400 bg-gray-50 px-2 py-1 rounded">
                                <i class="fas fa-shield-alt"></i> Transaksi Aman & Terpercaya
                            </span>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>