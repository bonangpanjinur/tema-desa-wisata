<?php
/**
 * Template part for displaying Produk Card (Elegant Style)
 * Data Source: Plugin Database (wp_dw_produk)
 * * @var object $args['data'] Data row dari tabel database
 */

// 1. Ambil Data
$produk = $args['data'] ?? null;
if ( ! $produk ) return;

// 2. Setup Variabel
$image_url   = !empty($produk->foto_utama) ? esc_url($produk->foto_utama) : 'https://via.placeholder.com/300x300?text=Produk';
$link_detail = home_url('/produk/' . ($produk->slug ?? '#'));
$nama_produk = esc_html($produk->nama_produk);
$harga       = tema_dw_format_rupiah($produk->harga);
$stok        = intval($produk->stok);
$terjual     = intval($produk->terjual);
$rating      = floatval($produk->rating_avg);

// LOGIKA LOKASI: Prioritaskan Nama Desa (Permintaan 2)
// Kita cek apakah query SQL mengirimkan 'nama_desa'. 
// Jika tidak, kita cek 'nama_toko'. Fallback terakhir 'Desa Wisata'.
$lokasi_label = 'Desa Wisata'; // Default
if ( !empty($produk->nama_desa) ) {
    $lokasi_label = $produk->nama_desa; // Prioritas 1: Nama Desa
} elseif ( !empty($produk->kabupaten_nama) ) {
    // Fallback jika desa kosong, tapi ada kabupaten (biar tidak blank)
    $lokasi_label = $produk->kabupaten_nama; 
} elseif ( !empty($produk->nama_toko) ) {
    $lokasi_label = $produk->nama_toko;
}

// Badge Stok Habis
$badge_stok = '';
if ($stok <= 0) {
    $badge_stok = '<div class="absolute inset-0 bg-white/60 backdrop-blur-[1px] z-10 flex items-center justify-center"><span class="bg-gray-800 text-white text-xs font-bold px-3 py-1 rounded-full">Habis</span></div>';
} elseif ($stok < 5) {
    $badge_stok = '<span class="absolute top-2 left-2 bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm z-10">Sisa ' . $stok . '</span>';
}
?>

<div class="group bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-lg hover:border-primary/20 transition-all duration-300 flex flex-col h-full overflow-hidden relative">
    
    <a href="<?php echo $link_detail; ?>" class="block flex-grow flex flex-col h-full">
        
        <!-- BAGIAN GAMBAR -->
        <div class="relative w-full aspect-square bg-gray-50 overflow-hidden">
            <?php echo $badge_stok; ?>
            
            <img src="<?php echo $image_url; ?>" 
                 alt="<?php echo $nama_produk; ?>" 
                 class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-500 mix-blend-multiply"
                 loading="lazy">
            
            <!-- Tombol Quick Action (Muncul saat Hover) -->
            <?php if ($stok > 0): ?>
            <div class="absolute bottom-0 inset-x-0 p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300 flex justify-center bg-gradient-to-t from-black/40 to-transparent pb-4">
                <button class="bg-white text-primary hover:bg-primary hover:text-white text-xs font-bold px-4 py-2 rounded-full shadow-lg flex items-center gap-2 transition-colors dw-add-to-cart-btn"
                        data-product-id="<?php echo $produk->id; ?>"
                        onclick="event.preventDefault();"> <!-- Prevent link click -->
                    <i class="fas fa-cart-plus"></i> Beli
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- BAGIAN KONTEN -->
        <div class="p-3 flex flex-col flex-grow">
            <!-- Kategori Kecil (Opsional) -->
            <div class="text-[10px] text-gray-400 mb-1 uppercase tracking-wide font-semibold">
                <?php echo !empty($produk->kategori) ? esc_html($produk->kategori) : 'Produk Lokal'; ?>
            </div>

            <!-- Judul Produk -->
            <h3 class="text-sm font-semibold text-gray-800 line-clamp-2 mb-1 leading-snug min-h-[2.5em] group-hover:text-primary transition-colors">
                <?php echo $nama_produk; ?>
            </h3>

            <!-- Harga -->
            <div class="text-base font-bold text-gray-900 mb-2">
                <?php echo $harga; ?>
            </div>

            <!-- Spacer -->
            <div class="mt-auto"></div>

            <!-- Info Lokasi (Desa) -->
            <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-2 pb-2 border-b border-gray-50">
                <i class="fas fa-map-marker-alt text-primary/70"></i> 
                <span class="truncate font-medium text-gray-600"><?php echo $lokasi_label; ?></span>
            </div>

            <!-- Rating & Terjual -->
            <div class="flex items-center justify-between text-[10px] text-gray-400">
                <div class="flex items-center gap-1">
                    <i class="fas fa-star text-yellow-400"></i>
                    <span class="text-gray-700 font-bold"><?php echo ($rating > 0) ? number_format($rating, 1) : 'Baru'; ?></span>
                </div>
                <span><?php echo ($terjual > 0) ? $terjual . ' Terjual' : ''; ?></span>
            </div>
        </div>
    </a>
</div>