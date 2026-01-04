<?php
/**
 * Template Name: Single Produk (Fixed)
 * Description: Template produk dengan ID tombol yang sinkron dengan ajax-cart.js
 */

get_header();

global $wpdb;
$slug = get_query_var('dw_slug');

// 1. Query Data Lengkap (Sesuai kode Anda)
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_desa     = $wpdb->prefix . 'dw_desa';

$sql = $wpdb->prepare(
    "SELECT p.*, 
            t.id as id_pedagang, t.nama_toko, t.slug_toko, t.foto_profil as foto_toko, t.alamat_lengkap as alamat_toko,
            d.nama_desa, d.slug_desa, d.provinsi, d.kabupaten
    FROM $table_produk p 
    JOIN $table_pedagang t ON p.id_pedagang = t.id 
    LEFT JOIN $table_desa d ON t.id_desa = d.id 
    WHERE p.slug = %s AND p.status = 'aktif'",
    $slug
);

$produk = $wpdb->get_row($sql);

// 2. 404 Handler
if (!$produk) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit();
}

// 3. Setup Variabel
$id_produk    = $produk->id;
$id_pedagang  = $produk->id_pedagang; // ID Pedagang untuk query related
$judul        = esc_html($produk->nama_produk);
$harga        = floatval($produk->harga);
$harga_fmt    = 'Rp ' . number_format($harga, 0, ',', '.');
$stok         = intval($produk->stok);
$deskripsi    = wp_kses_post($produk->deskripsi);
$kategori     = esc_html($produk->kategori);
$kondisi      = ucfirst(esc_html($produk->kondisi));
$berat        = intval($produk->berat_gram);
$dilihat      = intval($produk->dilihat);
$terjual      = intval($produk->terjual);
$rating       = floatval($produk->rating_avg);

$nama_toko    = esc_html($produk->nama_toko);
$foto_toko    = !empty($produk->foto_toko) ? esc_url($produk->foto_toko) : 'https://ui-avatars.com/api/?name='.urlencode($nama_toko).'&background=random';
$lokasi_label = !empty($produk->kabupaten) ? $produk->kabupaten : $produk->nama_desa;

$foto_utama   = !empty($produk->foto_utama) ? esc_url($produk->foto_utama) : 'https://via.placeholder.com/800x800?text=No+Image';
$galeri_json  = !empty($produk->galeri) ? json_decode($produk->galeri, true) : [];
$list_foto    = array_merge([$foto_utama], (is_array($galeri_json) ? $galeri_json : []));

// --- 4. QUERY RELATED PRODUCTS (TOKO YANG SAMA) ---
$related_products = $wpdb->get_results($wpdb->prepare(
    "SELECT id, nama_produk, slug, foto_utama, harga, rating_avg, terjual 
     FROM $table_produk 
     WHERE status = 'aktif' AND id_pedagang = %d AND id != %d 
     ORDER BY RAND() LIMIT 4",
    $id_pedagang, $id_produk
));
?>
<title><?php echo $judul; ?> | <?php echo $nama_toko; ?></title>

<style>
    .gallery-thumb.active { border-color: #16a34a; opacity: 1; }
    .gallery-thumb { opacity: 0.6; transition: all 0.2s; }
    .gallery-thumb:hover { opacity: 1; }
    
    /* Loading State Styles */
    .btn-loading { position: relative; pointer-events: none; opacity: 0.8; }
    .btn-loading span { opacity: 0; }
    .btn-loading::after {
        content: ''; position: absolute; top: 50%; left: 50%; width: 1.2em; height: 1.2em;
        border: 2px solid transparent; border-top-color: currentColor; border-radius: 50%;
        animation: spin 0.6s linear infinite; margin-top: -0.6em; margin-left: -0.6em;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
</style>

<div class="bg-gray-50 min-h-screen font-sans pb-20">
    
    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-100 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3 text-xs md:text-sm text-gray-500 flex items-center gap-2 overflow-x-auto whitespace-nowrap">
            <a href="<?php echo home_url('/'); ?>" class="hover:text-green-600">Beranda</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <a href="<?php echo home_url('/produk'); ?>" class="hover:text-green-600">Produk</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-gray-400"><?php echo $nama_toko; ?></span>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-gray-800 font-medium truncate"><?php echo $judul; ?></span>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6 md:py-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- GALERI FOTO -->
            <div class="lg:col-span-5">
                <div class="bg-white rounded-2xl p-4 shadow-sm sticky top-24">
                    <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 mb-4 border border-gray-100 relative group">
                        <img id="main-image" src="<?php echo $foto_utama; ?>" alt="<?php echo $judul; ?>" class="w-full h-full object-contain cursor-zoom-in transition-transform duration-500">
                        <?php if ($stok < 1): ?>
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <span class="bg-red-600 text-white font-bold px-4 py-2 rounded-full uppercase tracking-wider">Stok Habis</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if (count($list_foto) > 1): ?>
                    <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide">
                        <?php foreach ($list_foto as $index => $foto): ?>
                            <button onclick="changeImage('<?php echo esc_url($foto); ?>', this)" 
                                    class="gallery-thumb flex-shrink-0 w-16 h-16 rounded-lg border-2 border-transparent overflow-hidden <?php echo ($index === 0) ? 'active' : ''; ?>">
                                <img src="<?php echo esc_url($foto); ?>" class="w-full h-full object-cover">
                            </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- INFO PRODUK -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 leading-snug mb-2"><?php echo $judul; ?></h1>
                    
                    <div class="flex items-end gap-3 mb-4">
                        <span class="text-3xl font-bold text-green-600"><?php echo $harga_fmt; ?></span>
                    </div>

                    <div class="flex items-center gap-4 text-sm text-gray-500 border-b border-gray-100 pb-4 mb-4">
                        <div class="flex items-center gap-1">
                            <i class="fas fa-star text-yellow-400"></i>
                            <span class="font-bold text-gray-800"><?php echo number_format($rating, 1); ?></span>
                        </div>
                        <span class="w-px h-4 bg-gray-300"></span>
                        <div>Terjual <span class="font-bold text-gray-800"><?php echo $terjual; ?></span></div>
                        <span class="w-px h-4 bg-gray-300"></span>
                        <div>Dilihat <span class="font-bold text-gray-800"><?php echo $dilihat; ?></span></div>
                    </div>

                    <h3 class="font-bold text-gray-800 mb-3 text-sm">Spesifikasi Produk</h3>
                    <div class="grid grid-cols-2 gap-y-2 text-sm mb-6">
                        <div class="text-gray-500">Kategori</div>
                        <div class="font-medium text-green-600"><?php echo $kategori; ?></div>
                        <div class="text-gray-500">Kondisi</div>
                        <div class="font-medium <?php echo ($kondisi == 'Baru') ? 'text-blue-600' : 'text-orange-600'; ?>"><?php echo $kondisi; ?></div>
                        <div class="text-gray-500">Berat</div>
                        <div class="font-medium text-gray-800"><?php echo $berat; ?> gram</div>
                        <div class="text-gray-500">Stok</div>
                        <div class="font-medium <?php echo ($stok > 0) ? 'text-gray-800' : 'text-red-500'; ?>"><?php echo ($stok > 0) ? $stok : 'Habis'; ?></div>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <h3 class="font-bold text-gray-800 mb-2 text-sm">Deskripsi</h3>
                        <div class="prose prose-sm text-gray-600 max-w-none">
                            <?php echo nl2br($deskripsi); ?>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-4 shadow-sm flex items-center gap-4">
                    <img src="<?php echo $foto_toko; ?>" alt="Toko" class="w-14 h-14 rounded-full border border-gray-100 object-cover">
                    <div class="flex-grow">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-bold text-gray-800"><?php echo $nama_toko; ?></span>
                            <span class="bg-green-100 text-green-700 text-[10px] px-2 py-0.5 rounded-full font-bold">Official</span>
                        </div>
                        <div class="flex items-center gap-1 text-xs text-gray-500">
                            <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo $lokasi_label; ?>
                        </div>
                    </div>
                    <a href="<?php echo home_url('/toko/'.$produk->slug_toko); ?>" class="text-sm font-bold text-green-600 border border-green-600 px-4 py-1.5 rounded-full hover:bg-green-600 hover:text-white transition-all">Kunjungi</a>
                </div>
            </div>

            <!-- ACTION CARD (Sticky) -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 sticky top-24">
                    <h3 class="font-bold text-gray-800 mb-4">Atur Pesanan</h3>
                    
                    <?php if ($stok > 0): ?>
                    <form id="dw-add-to-cart-form" method="post">
                        <!-- SECURITY NONCE -->
                        <?php wp_nonce_field('dw_cart_action', 'dw_cart_nonce'); ?>
                        
                        <input type="hidden" name="action" value="dw_add_to_cart">
                        <input type="hidden" name="product_id" value="<?php echo $id_produk; ?>">
                        
                        <!-- PENTING: ID 'quantity' agar dibaca JS -->
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2 mb-4 border border-gray-200">
                            <button type="button" onclick="updateQty(-1)" class="w-8 h-8 flex items-center justify-center bg-white rounded shadow-sm text-gray-600 hover:text-green-600 transition-colors font-bold">-</button>
                            <input type="number" name="qty" id="quantity" value="1" min="1" max="<?php echo $stok; ?>" class="w-12 text-center bg-transparent border-none p-0 font-bold text-gray-800 focus:ring-0">
                            <button type="button" onclick="updateQty(1)" class="w-8 h-8 flex items-center justify-center bg-white rounded shadow-sm text-gray-600 hover:text-green-600 transition-colors font-bold">+</button>
                        </div>

                        <div class="flex justify-between text-sm text-gray-500 mb-4">
                            <span>Subtotal:</span>
                            <span class="font-bold text-gray-800" id="subtotalDisplay"><?php echo $harga_fmt; ?></span>
                        </div>

                        <div class="flex flex-col gap-2">
                            <!-- PENTING: ID 'add-to-cart' dan data-id agar dibaca JS -->
                            <button type="submit" id="add-to-cart" data-id="<?php echo $id_produk; ?>" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-green-500/20 transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-cart-plus"></i> <span>Masuk Keranjang</span>
                            </button>
                            
   	                            <!-- PENTING: ID 'buy-now' dan data-id agar dibaca JS -->
                            <button type="button" id="buy-now" data-id="<?php echo $id_produk; ?>" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-orange-500/20 transition-all flex items-center justify-center gap-2">
                                <span>Beli Sekarang</span>
                            </button>

                            <!-- Favorite Button -->
                            <?php 
                            $is_fav = false;
                            if (is_user_logged_in()) {
                                if (!class_exists('DW_Favorites')) {
                                    require_once WP_PLUGIN_DIR . '/desa-wisata-core/includes/class-dw-favorites.php';
                                }
                                $fav_obj = new DW_Favorites();
                                $is_fav = $fav_obj->is_favorited(get_current_user_id(), $id_produk, 'produk');
                            }
                            ?>
                            <button type="button" 
                                    class="js-toggle-favorite w-full border-2 border-gray-200 hover:border-red-500 hover:text-red-500 text-gray-600 font-bold py-3 px-4 rounded-xl transition-all flex items-center justify-center gap-2"
                                    data-id="<?php echo $id_produk; ?>"
                                    data-type="produk">
                                <i class="<?php echo $is_fav ? 'fas text-red-500' : 'far'; ?> fa-heart"></i>
                                <span>Favoritkan</span>
                            </button>
                        </div>
                    </form>m>
                    <?php else: ?>
                        <div class="bg-red-50 border border-red-100 rounded-xl p-4 text-center">
                            <i class="fas fa-box-open text-red-400 text-2xl mb-2"></i>
                            <p class="text-red-600 font-bold text-sm">Stok Habis</p>
                            <p class="text-xs text-red-400 mt-1">Silakan hubungi penjual untuk restock.</p>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-center gap-4 text-gray-400 text-sm">
                        <div class="flex items-center gap-1 cursor-help" title="Aman"><i class="fas fa-shield-alt"></i> Aman</div>
                        <div class="flex items-center gap-1 cursor-help" title="Respon Cepat"><i class="fas fa-bolt"></i> Cepat</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- 5. RELATED PRODUCTS SECTION -->
        <?php if ($related_products): ?>
        <div class="mt-12 pt-8 border-t border-gray-200">
            <h3 class="text-xl md:text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <i class="fas fa-store text-green-600"></i> Produk Lain dari <?php echo $nama_toko; ?>
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($related_products as $rp): 
                    $rp_img = !empty($rp->foto_utama) ? $rp->foto_utama : 'https://via.placeholder.com/300';
                    $rp_harga = 'Rp ' . number_format($rp->harga, 0, ',', '.');
                ?>
                <a href="<?php echo home_url('/produk/' . $rp->slug); ?>" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition group">
                    <div class="aspect-square bg-gray-100 relative overflow-hidden">
                        <img src="<?php echo esc_url($rp_img); ?>" alt="<?php echo esc_attr($rp->nama_produk); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    </div>
                    <div class="p-4">
                        <h4 class="font-bold text-gray-800 text-sm mb-1 truncate group-hover:text-green-600 transition"><?php echo esc_html($rp->nama_produk); ?></h4>
                        <div class="flex items-center justify-between mt-2">
                            <span class="font-bold text-green-600 text-sm"><?php echo $rp_harga; ?></span>
                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                <i class="fas fa-star text-yellow-400"></i> <?php echo number_format($rp->rating_avg, 1); ?>
                            </span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
    const basePrice = <?php echo $harga; ?>;
    const stockQty  = <?php echo $stok; ?>;
    
    // JS Fallback untuk Galeri & Qty (Hanya UI, Logika Add to Cart ada di ajax-cart.js)
    function changeImage(src, btn) {
        document.getElementById('main-image').src = src;
        document.querySelectorAll('.gallery-thumb').forEach(b => b.classList.remove('active', 'border-green-600', 'opacity-100'));
        btn.classList.add('active', 'border-green-600', 'opacity-100');
    }

    function updateQty(change) {
        const input = document.getElementById('quantity');
        let newVal = parseInt(input.value) + change;
        if (newVal >= 1 && newVal <= stockQty) {
            input.value = newVal;
            updateSubtotal(newVal);
        }
    }

    function updateSubtotal(qty) {
        const subtotal = basePrice * qty;
        const fmt = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(subtotal);
        document.getElementById('subtotalDisplay').innerText = fmt;
    }
</script>

<?php get_footer(); ?>