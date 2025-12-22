<?php
/**
 * Template part for displaying Produk Card
 * Data Source: Object row dari tabel wp_dw_produk
 * Variable expected: $args['data']
 */

// 1. Validasi Data Masuk
$produk = $args['data'] ?? null;
if ( ! $produk ) return;

// 2. Setup Link URL (Wajib sesuai Rewrite Rule functions.php)
// Format: domain.com/produk/slug-produk
$slug_produk = !empty($produk->slug) ? $produk->slug : sanitize_title($produk->nama_produk);
$link_detail = home_url('/produk/' . $slug_produk);

// 3. Setup Variabel Tampilan
$image_url   = !empty($produk->foto_utama) ? esc_url($produk->foto_utama) : 'https://via.placeholder.com/300x300?text=Produk';
$nama_produk = esc_html($produk->nama_produk);
$harga       = tema_dw_format_rupiah($produk->harga ?? 0);
$stok        = intval($produk->stok ?? 0);
$terjual     = intval($produk->terjual ?? 0);
$rating      = floatval($produk->rating_avg ?? 0);
$kategori    = !empty($produk->kategori) ? esc_html($produk->kategori) : 'Umum';

// 4. Logika Lokasi (Fallback Aman)
// Mencoba mencari nama desa, jika tidak ada, nama toko, jika tidak ada, default.
$lokasi = 'Desa Wisata'; 
if (!empty($produk->nama_desa)) {
    $lokasi = $produk->nama_desa;
} elseif (!empty($produk->nama_toko)) {
    $lokasi = $produk->nama_toko;
} elseif (!empty($produk->kabupaten_kota)) {
    $lokasi = $produk->kabupaten_kota;
}
?>

<div class="group bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col h-full relative">
    
    <!-- Link Pembungkus Utama (Agar seluruh card bisa diklik) -->
    <a href="<?php echo esc_url($link_detail); ?>" class="absolute inset-0 z-10" aria-label="<?php echo $nama_produk; ?>"></a>

    <!-- Gambar Produk -->
    <div class="relative aspect-square bg-gray-50 overflow-hidden">
        <img src="<?php echo $image_url; ?>" alt="<?php echo $nama_produk; ?>" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500">
        
        <!-- Badge Stok Habis -->
        <?php if ($stok < 1) : ?>
            <div class="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-20">
                <span class="bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider">Habis</span>
            </div>
        <?php endif; ?>
        
        <!-- Badge Kategori (Pojok Kiri Atas) -->
        <span class="absolute top-2 left-2 bg-black/40 backdrop-blur-md text-white text-[10px] px-2 py-0.5 rounded-md font-medium z-20">
            <?php echo $kategori; ?>
        </span>
    </div>

    <!-- Konten Info -->
    <div class="p-3 flex flex-col flex-grow">
        
        <!-- Judul Produk -->
        <h3 class="text-sm font-semibold text-gray-800 line-clamp-2 mb-1 leading-snug min-h-[2.5em] group-hover:text-primary transition-colors">
            <?php echo $nama_produk; ?>
        </h3>

        <!-- Harga -->
        <div class="text-base font-bold text-primary mb-2">
            <?php echo $harga; ?>
        </div>

        <!-- Spacer (Agar elemen bawah rata) -->
        <div class="mt-auto"></div>

        <!-- Info Lokasi -->
        <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-2 pb-2 border-b border-gray-50">
            <i class="fas fa-map-marker-alt text-red-400"></i> 
            <span class="truncate font-medium text-gray-600 w-full"><?php echo esc_html($lokasi); ?></span>
        </div>

        <!-- Rating & Terjual -->
        <div class="flex items-center justify-between text-[10px] text-gray-400">
            <div class="flex items-center gap-1">
                <i class="fas fa-star text-yellow-400"></i>
                <span class="text-gray-700 font-medium"><?php echo ($rating > 0) ? number_format($rating, 1) : '-'; ?></span>
            </div>
            <div>
                Terjual <span class="text-gray-700 font-medium"><?php echo $terjual; ?></span>
            </div>
        </div>
    </div>
</div>