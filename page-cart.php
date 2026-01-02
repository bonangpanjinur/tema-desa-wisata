<?php
/**
 * Template Name: Keranjang Belanja (Advanced)
 * Description: Mendukung Checkbox Selection, Real-time Weight Calc, & Multi-vendor
 */

if (!session_id()) session_start();
get_header();

global $wpdb;
$user_id    = get_current_user_id();
$session_id = session_id();
$table_cart = $wpdb->prefix . 'dw_cart';

// --- 1. QUERY DATA KERANJANG (Optimized) ---
// Menambahkan p.berat_gram ke select statement
$sql = $wpdb->prepare(
    "SELECT c.id as cart_id, c.qty, c.id_variasi,
            p.id as product_id, p.nama_produk, p.slug as slug_produk, p.foto_utama, p.berat_gram,
            -- Logic Harga: Prioritas Variasi
            COALESCE(v.harga_variasi, p.harga) as final_price,
            -- Logic Stok: Prioritas Variasi
            COALESCE(v.stok_variasi, p.stok) as final_stock,
            -- Logic Varian
            v.deskripsi_variasi as nama_varian, v.foto as foto_varian,
            -- Info Toko
            t.id as toko_id, t.nama_toko, t.kabupaten_nama, t.slug_toko
     FROM $table_cart c
     JOIN {$wpdb->prefix}dw_produk p ON c.id_produk = p.id
     LEFT JOIN {$wpdb->prefix}dw_produk_variasi v ON c.id_variasi = v.id
     JOIN {$wpdb->prefix}dw_pedagang t ON p.id_pedagang = t.id
     WHERE c.user_id = %d OR (c.user_id IS NULL AND c.session_id = %s)
     ORDER BY t.nama_toko ASC, c.created_at DESC",
    $user_id, $session_id
);

$cart_items = $wpdb->get_results($sql);

// Grouping logic per Toko
$grouped_items = [];
$total_count_all = 0;

foreach ($cart_items as $item) {
    $grouped_items[$item->toko_id]['info'] = [
        'nama' => $item->nama_toko,
        'slug' => $item->slug_toko,
        'lokasi' => $item->kabupaten_nama
    ];
    $grouped_items[$item->toko_id]['items'][] = $item;
    $total_count_all += $item->qty;
}
?>

<div class="bg-gray-50 min-h-screen pb-32 lg:pb-10 font-sans">
    <div class="max-w-6xl mx-auto px-4 pt-8">
        
        <!-- Header & Title -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-shopping-cart text-primary"></i> Keranjang
            </h1>
            <?php if (!empty($grouped_items)) : ?>
            <button onclick="bulkDelete()" class="text-sm text-red-500 font-medium hover:text-red-700 transition">
                Hapus Pilihan
            </button>
            <?php endif; ?>
        </div>

        <!-- Notification Toast -->
        <div id="cart-toast" class="fixed top-5 right-5 z-50 transform transition-all duration-300 translate-y-[-150%] opacity-0">
            <div class="bg-gray-800 text-white px-6 py-3 rounded-lg shadow-xl flex items-center gap-3">
                <i id="toast-icon" class="fas fa-check-circle text-green-400"></i>
                <span id="toast-message" class="font-medium text-sm">Update berhasil</span>
            </div>
        </div>

        <?php if (empty($grouped_items)) : ?>
            <!-- EMPTY STATE -->
            <div class="bg-white rounded-2xl p-16 text-center shadow-sm border border-gray-100 flex flex-col items-center">
                <img src="<?php echo get_template_directory_uri(); ?>/assets/img/empty-cart.svg" onerror="this.src='https://via.placeholder.com/200?text=Empty+Cart'" class="w-48 mb-6 opacity-80" alt="Keranjang Kosong">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Keranjang Belanjamu Kosong</h2>
                <p class="text-gray-500 mb-8 max-w-md">Sepertinya kamu belum menambahkan produk apapun. Yuk, dukung UMKM desa dengan belanja sekarang!</p>
                <a href="<?php echo home_url('/produk'); ?>" class="bg-primary hover:bg-green-700 text-white font-bold py-3 px-10 rounded-full transition-all shadow-lg shadow-green-200 transform hover:-translate-y-1">
                    Mulai Belanja
                </a>
            </div>
        <?php else : ?>

            <form id="cart-form" action="<?php echo home_url('/checkout'); ?>" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8 relative">
                
                <!-- KOLOM KIRI: LIST PRODUK -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- GLOBAL CHECKBOX -->
                    <div class="bg-white px-6 py-4 rounded-xl shadow-sm border border-gray-100 flex items-center gap-4">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="check-all" class="form-checkbox w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary transition duration-150 ease-in-out">
                            <span class="ml-3 text-gray-700 font-medium select-none">Pilih Semua (<?php echo $total_count_all; ?>)</span>
                        </label>
                    </div>

                    <!-- LOOP TOKO -->
                    <?php foreach ($grouped_items as $toko_id => $group) : ?>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden cart-store-group">
                        
                        <!-- Header Toko -->
                        <div class="bg-gray-50/50 px-6 py-3 border-b border-gray-100 flex items-center gap-3">
                            <input type="checkbox" class="check-store form-checkbox w-4 h-4 text-primary rounded border-gray-300 focus:ring-primary" data-target="store-<?php echo $toko_id; ?>">
                            <div class="flex items-center gap-2">
                                <a href="<?php echo home_url('/toko/'.$group['info']['slug']); ?>" class="font-bold text-gray-800 text-sm hover:text-primary transition">
                                    <i class="fas fa-store mr-1 text-gray-400"></i> <?php echo esc_html($group['info']['nama']); ?>
                                </a>
                                <span class="bg-gray-200 text-gray-600 text-[10px] px-2 py-0.5 rounded-full">
                                    <?php echo esc_html($group['info']['lokasi']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Loop Items -->
                        <div class="divide-y divide-gray-50">
                            <?php foreach ($group['items'] as $item) : 
                                $foto_url = !empty($item->foto_varian) ? $item->foto_varian : (!empty($item->foto_utama) ? $item->foto_utama : 'https://via.placeholder.com/150');
                                $is_out_of_stock = $item->final_stock <= 0;
                            ?>
                            <div class="p-4 sm:p-6 flex gap-4 relative group transition-colors hover:bg-gray-50/80 store-<?php echo $toko_id; ?>-item">
                                
                                <!-- Checkbox Item -->
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           name="cart_ids[]" 
                                           value="<?php echo $item->cart_id; ?>" 
                                           class="check-item form-checkbox w-5 h-5 text-primary rounded border-gray-300 focus:ring-primary disabled:bg-gray-100 disabled:cursor-not-allowed"
                                           data-price="<?php echo $item->final_price; ?>"
                                           data-weight="<?php echo $item->berat_gram; ?>"
                                           data-qty="<?php echo $item->qty; ?>"
                                           data-store="<?php echo $toko_id; ?>"
                                           <?php echo $is_out_of_stock ? 'disabled' : ''; ?>
                                    >
                                </div>

                                <!-- Gambar -->
                                <a href="<?php echo home_url('/produk/'.$item->slug_produk); ?>" class="shrink-0 w-20 h-20 sm:w-24 sm:h-24 bg-gray-100 rounded-lg overflow-hidden border border-gray-200 relative">
                                    <img src="<?php echo esc_url($foto_url); ?>" class="w-full h-full object-cover <?php echo $is_out_of_stock ? 'grayscale opacity-50' : ''; ?>">
                                    <?php if($is_out_of_stock): ?>
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/50">
                                            <span class="text-white text-xs font-bold px-2 py-1 bg-red-600 rounded">Habis</span>
                                        </div>
                                    <?php endif; ?>
                                </a>

                                <!-- Detail -->
                                <div class="flex-grow flex flex-col justify-between">
                                    <div>
                                        <a href="<?php echo home_url('/produk/'.$item->slug_produk); ?>" class="font-medium text-gray-800 line-clamp-2 hover:text-primary transition mb-1">
                                            <?php echo esc_html($item->nama_produk); ?>
                                        </a>
                                        
                                        <?php if($item->id_variasi > 0): ?>
                                            <div class="text-xs text-gray-500 bg-gray-100 inline-block px-2 py-1 rounded mb-2">
                                                Variasi: <?php echo esc_html($item->nama_varian); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="font-bold text-primary">
                                            <?php echo tema_dw_format_rupiah($item->final_price); ?>
                                        </div>
                                    </div>

                                    <!-- Actions: Qty & Delete -->
                                    <div class="flex justify-between items-end mt-2">
                                        <?php if(!$is_out_of_stock): ?>
                                        <div class="flex items-center border border-gray-300 rounded-lg bg-white h-8 w-28 shadow-sm">
                                            <button type="button" class="btn-qty w-8 h-full flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-l-lg transition" data-action="decrease" data-id="<?php echo $item->cart_id; ?>">-</button>
                                            <input type="number" 
                                                   class="input-qty w-full h-full text-center border-none text-sm font-bold text-gray-800 p-0 focus:ring-0 appearance-none" 
                                                   value="<?php echo $item->qty; ?>" 
                                                   min="1" 
                                                   max="<?php echo $item->final_stock; ?>"
                                                   data-id="<?php echo $item->cart_id; ?>"
                                                   data-stock="<?php echo $item->final_stock; ?>"
                                                   readonly
                                            >
                                            <button type="button" class="btn-qty w-8 h-full flex items-center justify-center text-gray-600 hover:bg-gray-100 rounded-r-lg transition" data-action="increase" data-id="<?php echo $item->cart_id; ?>">+</button>
                                        </div>
                                        <?php else: ?>
                                            <div class="text-xs text-red-500 italic">Stok habis</div>
                                        <?php endif; ?>

                                        <button type="button" onclick="deleteCartItem(<?php echo $item->cart_id; ?>)" class="text-gray-400 hover:text-red-500 transition p-2">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- KOLOM KANAN: RINGKASAN (Sticky Desktop) -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 sticky top-24 hidden lg:block">
                        <h3 class="font-bold text-gray-800 text-lg mb-4">Ringkasan Belanja</h3>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Total Item Dipilih</span>
                                <span class="font-medium" id="summary-total-item">0</span>
                            </div>
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>Total Berat</span>
                                <span class="font-medium" id="summary-total-weight">0 gram</span>
                            </div>
                        </div>

                        <div class="border-t border-dashed border-gray-200 pt-4 mb-6">
                            <div class="flex justify-between items-end">
                                <span class="font-bold text-gray-800">Total Harga</span>
                                <span class="text-2xl font-bold text-primary" id="summary-grand-total">Rp 0</span>
                            </div>
                        </div>

                        <button type="submit" id="btn-checkout-desktop" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-green-500/30 transition-all disabled:bg-gray-300 disabled:shadow-none disabled:cursor-not-allowed" disabled>
                            Beli (<span id="btn-count-desktop">0</span>)
                        </button>
                    </div>
                </div>

                <!-- MOBILE STICKY BOTTOM BAR -->
                <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-[0_-5px_15px_rgba(0,0,0,0.05)] lg:hidden z-40 flex items-center justify-between safe-area-pb">
                    <div class="flex flex-col">
                        <span class="text-xs text-gray-500">Total Harga</span>
                        <span class="font-bold text-primary text-lg" id="mobile-grand-total">Rp 0</span>
                    </div>
                    <button type="submit" id="btn-checkout-mobile" class="bg-primary hover:bg-green-700 text-white font-bold py-2.5 px-8 rounded-lg shadow-md transition-all disabled:bg-gray-300 disabled:cursor-not-allowed" disabled>
                        Checkout (<span id="btn-count-mobile">0</span>)
                    </button>
                </div>

            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Load Script Handler (UPDATED to ajax-cart.js) -->
<script src="<?php echo get_template_directory_uri(); ?>/assets/js/ajax-cart.js?v=<?php echo time(); ?>"></script>
<script>
    // Pass PHP vars to JS
    const dwCartConfig = {
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce("dw_cart_nonce"); ?>',
        homeUrl: '<?php echo home_url(); ?>'
    };
</script>

<?php get_footer(); ?>