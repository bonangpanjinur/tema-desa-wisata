<?php
/**
 * Template part for displaying Produk Card
 * Menggunakan data dari Custom Database Table Plugin
 * * @var object $args['data'] Data row dari tabel wp_dw_produk
 */

// Ambil data dari argumen yang dikirim
$produk = $args['data'] ?? null;

// Jika tidak ada data, jangan tampilkan apa-apa
if ( ! $produk ) return;

// Setup Variabel Tampilan
$image_url   = !empty($produk->foto_utama) ? esc_url($produk->foto_utama) : 'https://via.placeholder.com/300x300?text=Produk+Desa';
$product_url = home_url('/produk/' . $produk->slug);
$nama_produk = esc_html($produk->nama_produk);
$harga       = tema_dw_format_rupiah($produk->harga);
$stok        = intval($produk->stok);
$terjual     = intval($produk->terjual);
$rating      = floatval($produk->rating_avg);
$nama_toko   = isset($produk->nama_toko) ? esc_html($produk->nama_toko) : 'Toko Desa';
$lokasi      = isset($produk->kabupaten_nama) ? esc_html($produk->kabupaten_nama) : '';

// Helper untuk Badge Stok
$stok_label = '';
if ($stok <= 0) {
    $stok_label = '<span class="absolute top-2 right-2 bg-gray-500 text-white text-[10px] font-bold px-2 py-1 rounded z-10">Habis</span>';
} elseif ($stok < 5) {
    $stok_label = '<span class="absolute top-2 right-2 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded z-10">Sisa ' . $stok . '</span>';
}
?>

<div class="group bg-white rounded-lg border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col h-full overflow-hidden relative">
    
    <!-- Link Pembungkus Seluruh Card -->
    <a href="<?php echo $product_url; ?>" class="block flex-grow flex flex-col h-full">
        
        <!-- Bagian Gambar -->
        <div class="relative w-full aspect-square bg-gray-50 overflow-hidden">
            <?php echo $stok_label; ?>
            <img src="<?php echo $image_url; ?>" 
                 alt="<?php echo $nama_produk; ?>" 
                 class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-500"
                 loading="lazy">
            
            <!-- Overlay Transparan saat Hover -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition duration-300"></div>
        </div>

        <!-- Bagian Konten -->
        <div class="p-3 flex flex-col flex-grow">
            <!-- Judul Produk -->
            <h3 class="text-sm font-medium text-gray-800 line-clamp-2 mb-1 leading-snug min-h-[2.5em] group-hover:text-primary transition-colors">
                <?php echo $nama_produk; ?>
            </h3>

            <!-- Harga -->
            <div class="text-base font-bold text-gray-900 mb-2">
                <?php echo $harga; ?>
            </div>

            <!-- Spacer Flexible -->
            <div class="mt-auto"></div>

            <!-- Info Lokasi & Toko -->
            <div class="flex items-center gap-1 text-[11px] text-gray-500 mb-2">
                <?php if($lokasi): ?>
                    <i class="fas fa-map-marker-alt text-gray-400 text-[10px]"></i> 
                    <span class="truncate max-w-[100px]"><?php echo $lokasi; ?></span>
                <?php else: ?>
                    <i class="fas fa-store text-gray-400 text-[10px]"></i> 
                    <span class="truncate max-w-[100px]"><?php echo $nama_toko; ?></span>
                <?php endif; ?>
            </div>

            <!-- Rating & Terjual -->
            <div class="flex items-center gap-2 border-t border-gray-50 pt-2 text-[10px]">
                <?php if ($rating > 0) : ?>
                    <div class="flex items-center gap-0.5 text-gray-800 font-bold">
                        <i class="fas fa-star text-yellow-400 text-[9px]"></i> 
                        <span><?php echo number_format($rating, 1); ?></span>
                    </div>
                    <span class="text-gray-300">|</span>
                <?php endif; ?>
                <span class="text-gray-500">Terjual <?php echo $terjual > 99 ? '99+' : $terjual; ?></span>
            </div>
        </div>
    </a>

    <!-- Tombol Keranjang (Muncul saat hover di Desktop) -->
    <?php if ($stok > 0) : ?>
    <div class="absolute bottom-3 right-3 opacity-0 translate-y-2 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300 hidden md:block">
        <button class="bg-primary hover:bg-primaryDark text-white w-8 h-8 rounded-full shadow-md flex items-center justify-center dw-add-to-cart-btn" 
                data-product-id="<?php echo $produk->id; ?>" 
                title="Tambah ke Keranjang">
            <i class="fas fa-cart-plus text-xs"></i>
        </button>
    </div>
    <?php endif; ?>
</div>