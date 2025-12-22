<?php
/**
 * Template Name: Single Produk (Custom Table)
 * Description: Menampilkan detail produk lengkap dengan UI Modern
 */

get_header();

global $wpdb;
$slug = get_query_var('dw_slug');

// 1. Query Data Lengkap (Join Produk, Pedagang, Desa)
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$table_desa     = $wpdb->prefix . 'dw_desa';

// Ambil semua kolom yang diperlukan
$sql = $wpdb->prepare(
    "SELECT p.*, 
            t.nama_toko, t.slug_toko, t.foto_profil as foto_toko, t.alamat_lengkap as alamat_toko,
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

// 3. Setup Variabel Data
$id_produk    = $produk->id;
$judul        = esc_html($produk->nama_produk);
$harga        = floatval($produk->harga);
$harga_fmt    = tema_dw_format_rupiah($harga);
$stok         = intval($produk->stok);
$deskripsi    = wp_kses_post($produk->deskripsi);
$kategori     = esc_html($produk->kategori);
$kondisi      = ucfirst(esc_html($produk->kondisi)); // Baru/Bekas
$berat        = intval($produk->berat_gram); // Dalam gram
$dilihat      = intval($produk->dilihat);
$terjual      = intval($produk->terjual);
$rating       = floatval($produk->rating_avg);

// Data Penjual
$nama_toko    = esc_html($produk->nama_toko);
$nama_desa    = esc_html($produk->nama_desa);
$lokasi_label = !empty($produk->kabupaten) ? $produk->kabupaten : $nama_desa;
$foto_toko    = !empty($produk->foto_toko) ? esc_url($produk->foto_toko) : 'https://ui-avatars.com/api/?name='.urlencode($nama_toko).'&background=random';

// Data Gambar (Utama + Galeri)
$foto_utama   = !empty($produk->foto_utama) ? esc_url($produk->foto_utama) : 'https://via.placeholder.com/800x800?text=No+Image';
$galeri_json  = !empty($produk->galeri) ? json_decode($produk->galeri, true) : [];
$list_foto    = array_merge([$foto_utama], (is_array($galeri_json) ? $galeri_json : []));

// Increment View Counter (Opsional - Logika sederhana)
// $wpdb->query("UPDATE $table_produk SET dilihat = dilihat + 1 WHERE id = $id_produk");

?>
<title><?php echo $judul; ?> | <?php echo $nama_toko; ?></title>

<!-- Styling Khusus Galeri -->
<style>
    .gallery-thumb.active { border-color: #16a34a; opacity: 1; }
    .gallery-thumb { opacity: 0.6; transition: all 0.2s; }
    .gallery-thumb:hover { opacity: 1; }
</style>

<div class="bg-gray-50 min-h-screen font-sans pb-20">
    
    <!-- Breadcrumb -->
    <div class="bg-white border-b border-gray-100 sticky top-0 z-30 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 py-3 text-xs md:text-sm text-gray-500 flex items-center gap-2 overflow-x-auto whitespace-nowrap">
            <a href="<?php echo home_url('/'); ?>" class="hover:text-primary">Beranda</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <a href="<?php echo home_url('/produk'); ?>" class="hover:text-primary">Produk</a>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-gray-400"><?php echo $nama_toko; ?></span>
            <i class="fas fa-chevron-right text-[10px]"></i>
            <span class="text-gray-800 font-medium truncate"><?php echo $judul; ?></span>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-6 md:py-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- KOLOM KIRI: GALERI FOTO (Col-5) -->
            <div class="lg:col-span-5">
                <div class="bg-white rounded-2xl p-4 shadow-sm sticky top-24">
                    <!-- Foto Utama -->
                    <div class="aspect-square rounded-xl overflow-hidden bg-gray-100 mb-4 border border-gray-100 relative group">
                        <img id="main-image" src="<?php echo $foto_utama; ?>" alt="<?php echo $judul; ?>" class="w-full h-full object-contain cursor-zoom-in transition-transform duration-500">
                        <?php if ($stok < 1): ?>
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <span class="bg-red-600 text-white font-bold px-4 py-2 rounded-full uppercase tracking-wider">Stok Habis</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Thumbnails -->
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

            <!-- KOLOM TENGAH: INFO PRODUK (Col-4) -->
            <div class="lg:col-span-4 flex flex-col gap-6">
                <div class="bg-white rounded-2xl p-6 shadow-sm">
                    <!-- Judul & Harga -->
                    <h1 class="text-xl md:text-2xl font-bold text-gray-800 leading-snug mb-2"><?php echo $judul; ?></h1>
                    
                    <div class="flex items-end gap-3 mb-4">
                        <span class="text-3xl font-bold text-primary"><?php echo $harga_fmt; ?></span>
                    </div>

                    <!-- Statistik -->
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

                    <!-- Spesifikasi -->
                    <h3 class="font-bold text-gray-800 mb-3 text-sm">Spesifikasi Produk</h3>
                    <div class="grid grid-cols-2 gap-y-2 text-sm mb-6">
                        <div class="text-gray-500">Kategori</div>
                        <div class="font-medium text-primary"><?php echo $kategori; ?></div>
                        
                        <div class="text-gray-500">Kondisi</div>
                        <div class="font-medium <?php echo ($kondisi == 'Baru') ? 'text-blue-600' : 'text-orange-600'; ?>"><?php echo $kondisi; ?></div>
                        
                        <div class="text-gray-500">Berat</div>
                        <div class="font-medium text-gray-800"><?php echo $berat; ?> gram</div>
                        
                        <div class="text-gray-500">Stok</div>
                        <div class="font-medium <?php echo ($stok > 0) ? 'text-gray-800' : 'text-red-500'; ?>"><?php echo ($stok > 0) ? $stok : 'Habis'; ?></div>
                    </div>

                    <!-- Deskripsi -->
                    <div class="border-t border-gray-100 pt-4">
                        <h3 class="font-bold text-gray-800 mb-2 text-sm">Deskripsi</h3>
                        <div class="prose prose-sm text-gray-600 max-w-none line-clamp-[10] hover:line-clamp-none transition-all">
                            <?php echo $deskripsi; ?>
                        </div>
                    </div>
                </div>

                <!-- Info Penjual (Mobile/Tablet View put here or below, currently stacked) -->
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
                    <a href="#" class="text-sm font-bold text-primary border border-primary px-4 py-1.5 rounded-full hover:bg-primary hover:text-white transition-all">Kunjungi</a>
                </div>
            </div>

            <!-- KOLOM KANAN: ACTION CARD (Col-3, Sticky Desktop) -->
            <div class="lg:col-span-3">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 sticky top-24">
                    <h3 class="font-bold text-gray-800 mb-4">Atur Pesanan</h3>
                    
                    <?php if ($stok > 0): ?>
                    <form id="dw-add-to-cart-form">
                        <input type="hidden" name="action" value="dw_add_to_cart">
                        <input type="hidden" name="product_id" value="<?php echo $id_produk; ?>">
                        
                        <div class="flex items-center justify-between bg-gray-50 rounded-lg p-2 mb-4 border border-gray-200">
                            <button type="button" onclick="updateQty(-1)" class="w-8 h-8 flex items-center justify-center bg-white rounded shadow-sm text-gray-600 hover:text-primary transition-colors font-bold">-</button>
                            <input type="number" name="qty" id="qtyInput" value="1" min="1" max="<?php echo $stok; ?>" class="w-12 text-center bg-transparent border-none p-0 font-bold text-gray-800 focus:ring-0">
                            <button type="button" onclick="updateQty(1)" class="w-8 h-8 flex items-center justify-center bg-white rounded shadow-sm text-gray-600 hover:text-primary transition-colors font-bold">+</button>
                        </div>

                        <div class="flex justify-between text-sm text-gray-500 mb-4">
                            <span>Subtotal:</span>
                            <span class="font-bold text-gray-800" id="subtotalDisplay"><?php echo $harga_fmt; ?></span>
                        </div>

                        <div class="flex flex-col gap-2">
                            <button type="submit" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg shadow-green-500/20 transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-plus"></i> Keranjang
                            </button>
                            <button type="button" class="w-full bg-white border border-primary text-primary font-bold py-3 px-4 rounded-xl hover:bg-green-50 transition-all">
                                Beli Langsung
                            </button>
                        </div>
                    </form>
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
                        <div class="flex items-center gap-1 cursor-help" title="Layanan 24 Jam"><i class="fas fa-headset"></i> 24/7</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // JS Sederhana untuk Galeri & Qty
    const basePrice = <?php echo $harga; ?>;
    
    function changeImage(src, btn) {
        document.getElementById('main-image').src = src;
        document.querySelectorAll('.gallery-thumb').forEach(b => b.classList.remove('active', 'border-green-600', 'opacity-100'));
        btn.classList.add('active', 'border-green-600', 'opacity-100');
    }

    function updateQty(change) {
        const input = document.getElementById('qtyInput');
        let newVal = parseInt(input.value) + change;
        if (newVal >= 1 && newVal <= <?php echo $stok; ?>) {
            input.value = newVal;
            updateSubtotal(newVal);
        }
    }

    function updateSubtotal(qty) {
        const subtotal = basePrice * qty;
        // Format Rupiah sederhana
        const fmt = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(subtotal);
        document.getElementById('subtotalDisplay').innerText = fmt;
    }
</script>

<?php get_footer(); ?>