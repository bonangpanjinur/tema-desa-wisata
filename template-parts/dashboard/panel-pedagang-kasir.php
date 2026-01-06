<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$pedagang = isset($args['pedagang']) ? $args['pedagang'] : null;
if (!$pedagang) return;

global $wpdb;
$table_produk = $wpdb->prefix . 'dw_produk';
$produk_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_produk WHERE id_pedagang = %d AND status = 'publish' ORDER BY nama_produk ASC", $pedagang->id));
?>

<div class="flex flex-col lg:flex-row gap-6 h-full">
    <!-- Left: Product Selection -->
    <div class="flex-1 bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">
        <div class="p-4 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-th-large text-primary"></i> Pilih Produk
            </h2>
            <div class="relative w-48 md:w-64">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="pos-search" placeholder="Cari produk..." class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary/20 outline-none transition-all">
            </div>
        </div>
        
        <div class="p-4 overflow-y-auto flex-1 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4" id="pos-product-grid">
            <?php if($produk_list): foreach($produk_list as $p): ?>
                <div class="pos-product-card group bg-white border border-gray-100 rounded-xl p-3 hover:border-primary/50 hover:shadow-md transition-all cursor-pointer flex flex-col" 
                     data-id="<?php echo $p->id; ?>" 
                     data-nama="<?php echo esc_attr($p->nama_produk); ?>" 
                     data-harga="<?php echo $p->harga; ?>"
                     data-stok="<?php echo $p->stok; ?>"
                     onclick="addToCart(<?php echo htmlspecialchars(json_encode([
                         'id' => $p->id,
                         'nama' => $p->nama_produk,
                         'harga' => (float)$p->harga,
                         'stok' => (int)$p->stok
                     ])); ?>)">
                    <div class="aspect-square rounded-lg bg-gray-50 mb-3 overflow-hidden relative">
                        <?php if($p->foto_utama): ?>
                            <img src="<?php echo esc_url($p->foto_utama); ?>" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-300 text-2xl"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                        <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-1 rounded-md text-[10px] font-bold text-gray-700 shadow-sm">
                            Stok: <?php echo $p->stok; ?>
                        </div>
                    </div>
                    <h3 class="text-xs font-bold text-gray-800 line-clamp-2 mb-1 flex-1"><?php echo esc_html($p->nama_produk); ?></h3>
                    <p class="text-primary font-bold text-sm">Rp <?php echo number_format($p->harga, 0, ',', '.'); ?></p>
                </div>
            <?php endforeach; else: ?>
                <div class="col-span-full py-12 text-center">
                    <div class="w-16 h-16 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"><i class="fas fa-box-open"></i></div>
                    <p class="text-gray-500 text-sm">Belum ada produk yang dipublikasikan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right: Cart & Checkout -->
    <div class="w-full lg:w-96 bg-white rounded-2xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">
        <div class="p-4 border-b border-gray-50 bg-gray-50/50">
            <h2 class="font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-shopping-cart text-primary"></i> Keranjang Kasir
            </h2>
        </div>

        <!-- Customer Info -->
        <div class="p-4 border-b border-gray-50 space-y-3">
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1 block">Nama Pelanggan</label>
                <input type="text" id="pos-customer-name" placeholder="Misal: Budi" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 outline-none">
            </div>
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1 block">No. Meja / Keterangan</label>
                <input type="text" id="pos-table-no" placeholder="Misal: Meja 05" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-primary/20 outline-none">
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3 min-h-[200px]" id="pos-cart-items">
            <!-- Empty State -->
            <div id="pos-cart-empty" class="h-full flex flex-col items-center justify-center text-center py-8">
                <div class="w-12 h-12 bg-gray-50 text-gray-300 rounded-full flex items-center justify-center mb-3"><i class="fas fa-shopping-basket"></i></div>
                <p class="text-xs text-gray-400">Keranjang masih kosong</p>
            </div>
        </div>

        <!-- Summary & Action -->
        <div class="p-4 bg-gray-50 border-t border-gray-100 space-y-4">
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="font-bold text-gray-800" id="pos-subtotal">Rp 0</span>
                </div>
                <div class="flex justify-between text-lg">
                    <span class="font-extrabold text-gray-900">Total</span>
                    <span class="font-extrabold text-primary" id="pos-total">Rp 0</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <button onclick="clearCart()" class="py-3 px-4 bg-white border border-gray-200 text-gray-600 rounded-xl text-sm font-bold hover:bg-gray-100 transition-all">Reset</button>
                <button onclick="processCheckout()" id="btn-checkout" class="py-3 px-4 bg-primary text-white rounded-xl text-sm font-bold shadow-lg shadow-primary/20 hover:bg-primaryDark transition-all disabled:opacity-50 disabled:cursor-not-allowed">Bayar Sekarang</button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo get_template_directory_uri(); ?>/assets/js/dw-pos.js?v=<?php echo time(); ?>"></script>
