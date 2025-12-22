<?php
/**
 * Template Name: Single Produk (Custom Table)
 * Description: Menampilkan detail produk dari tabel wp_dw_produk
 */

get_header();

global $wpdb;
$slug = get_query_var('dw_slug');

// 1. Query Data Produk + Toko + Desa
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_desa     = $wpdb->prefix . 'dw_desa';

// Join: Produk milik Pedagang, Pedagang ada di Desa
$sql = $wpdb->prepare(
    "SELECT p.*, t.nama_toko, t.slug_toko, d.nama_desa 
    FROM $table_produk p 
    JOIN $table_pedagang t ON p.id_pedagang = t.id 
    LEFT JOIN $table_desa d ON t.id_desa = d.id 
    WHERE p.slug = %s AND p.status = 'aktif'",
    $slug
);

$produk = $wpdb->get_row($sql);

// 2. Not Found -> 404
if (!$produk) {
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    get_template_part(404);
    exit();
}

// 3. Setup Variabel
$id_produk   = $produk->id;
$judul       = esc_html($produk->nama_produk);
$harga       = floatval($produk->harga);
$harga_fmt   = tema_dw_format_rupiah($harga);
$stok        = intval($produk->stok);
$deskripsi   = wp_kses_post($produk->deskripsi);
$image_url   = !empty($produk->foto_utama) ? esc_url($produk->foto_utama) : 'https://via.placeholder.com/600x600?text=Produk';
$nama_toko   = esc_html($produk->nama_toko);
$nama_desa   = esc_html($produk->nama_desa);
$rating      = floatval($produk->rating_avg);
$terjual     = intval($produk->terjual);
?>
<title><?php echo $judul; ?> - <?php echo $nama_toko; ?></title>

<div class="dw-container py-10 max-w-6xl mx-auto px-4">
    <!-- Breadcrumb -->
    <div class="text-sm text-gray-500 mb-6">
        <span class="hover:text-primary cursor-pointer">Beranda</span> / 
        <span class="hover:text-primary cursor-pointer"><?php echo $nama_desa; ?></span> /
        <span class="hover:text-primary cursor-pointer"><?php echo $nama_toko; ?></span> /
        <span class="text-gray-800 font-medium"><?php echo $judul; ?></span>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="md:grid md:grid-cols-12">
            
            <!-- Gambar Produk -->
            <div class="md:col-span-5 bg-gray-50 p-6 flex items-center justify-center">
                <img src="<?php echo $image_url; ?>" alt="<?php echo $judul; ?>" class="rounded-xl w-full object-cover shadow-sm">
            </div>

            <!-- Detail Info -->
            <div class="md:col-span-7 p-8 md:p-10 flex flex-col">
                
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2"><?php echo $judul; ?></h1>
                
                <div class="flex items-center gap-4 text-sm text-gray-500 mb-6 pb-6 border-b border-gray-100">
                    <span class="flex items-center gap-1 text-yellow-400">
                        <i class="fas fa-star"></i> <?php echo number_format($rating, 1); ?>
                    </span>
                    <span class="text-gray-300">|</span>
                    <span>Terjual <?php echo $terjual; ?></span>
                    <span class="text-gray-300">|</span>
                    <span class="text-primary font-medium"><?php echo $nama_toko; ?></span>
                </div>

                <div class="text-3xl font-bold text-primary mb-6">
                    <?php echo $harga_fmt; ?>
                </div>

                <div class="prose prose-sm text-gray-600 mb-8 max-w-none">
                    <h3 class="text-gray-900 font-semibold mb-2">Deskripsi Produk</h3>
                    <?php echo $deskripsi; ?>
                </div>

                <!-- Form Add to Cart -->
                <div class="mt-auto">
                    <?php if ($stok > 0): ?>
                        <form id="dw-add-to-cart-form" class="flex flex-col sm:flex-row gap-4">
                            <input type="hidden" name="action" value="dw_add_to_cart">
                            <input type="hidden" name="product_id" value="<?php echo $id_produk; ?>">
                            
                            <div class="w-32">
                                <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Jumlah</label>
                                <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                                    <button type="button" onclick="this.parentNode.querySelector('input[type=number]').stepDown()" class="px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-600">-</button>
                                    <input type="number" name="qty" value="1" min="1" max="<?php echo $stok; ?>" class="w-full text-center border-none focus:ring-0 p-2 text-gray-800 font-bold">
                                    <button type="button" onclick="this.parentNode.querySelector('input[type=number]').stepUp()" class="px-3 py-2 bg-gray-50 hover:bg-gray-100 text-gray-600">+</button>
                                </div>
                                <div class="text-xs text-gray-400 mt-1">Stok: <?php echo $stok; ?></div>
                            </div>

                            <button type="submit" class="dw-btn-add-cart flex-1 bg-primary hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-lg shadow-green-500/30 transition-all transform hover:-translate-y-1 flex items-center justify-center gap-2">
                                <i class="fas fa-shopping-cart"></i> <span>Masukkan Keranjang</span>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-red-50 text-red-500 px-4 py-3 rounded-lg font-bold text-center border border-red-100">
                            Stok Habis
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>