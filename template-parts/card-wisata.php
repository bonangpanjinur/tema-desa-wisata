<?php
/**
 * Template part for displaying Wisata Card
 * Data Source: Object row dari tabel wp_dw_wisata
 */

$wisata = $args['data'] ?? null;
if ( ! $wisata ) return;

// --- LINK URL FIX ---
// Karena menggunakan tabel custom dan rewrite rule manual di functions.php
// URL menjadi: domain.com/wisata/{slug}
$link_detail = home_url('/wisata/' . $wisata->slug);

$image_url   = !empty($wisata->foto_utama) ? esc_url($wisata->foto_utama) : 'https://via.placeholder.com/600x400?text=Wisata+Desa';
$judul       = esc_html($wisata->nama_wisata);
$desa        = isset($wisata->nama_desa) ? esc_html($wisata->nama_desa) : 'Desa Wisata';
$kategori    = !empty($wisata->kategori) ? esc_html($wisata->kategori) : 'Umum';
$harga       = ($wisata->harga_tiket > 0) ? tema_dw_format_rupiah($wisata->harga_tiket) : 'Gratis';
$rating      = floatval($wisata->rating_avg);

// Logika warna badge sederhana
$badge_bg = 'bg-blue-100 text-blue-600';
if (stripos($kategori, 'alam') !== false) $badge_bg = 'bg-green-100 text-green-600';
?>

<div class="group bg-white rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 overflow-hidden flex flex-col h-full relative">
    <a href="<?php echo esc_url($link_detail); ?>" class="absolute inset-0 z-10"></a>

    <!-- Gambar -->
    <div class="relative h-48 overflow-hidden">
        <img src="<?php echo $image_url; ?>" alt="<?php echo $judul; ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition-transform duration-500">
        <span class="absolute top-3 right-3 <?php echo $badge_bg; ?> text-[10px] font-bold px-2 py-1 rounded-full uppercase z-20">
            <?php echo $kategori; ?>
        </span>
    </div>

    <!-- Konten -->
    <div class="p-4 flex flex-col flex-grow">
        <div class="text-xs text-gray-500 mb-1 flex items-center gap-1">
            <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($desa); ?>
        </div>
        
        <h3 class="text-lg font-bold text-gray-800 mb-2 leading-tight line-clamp-2 group-hover:text-primary transition-colors">
            <?php echo $judul; ?>
        </h3>

        <div class="mt-auto pt-3 border-t border-gray-50 flex items-center justify-between">
            <div>
                <span class="text-[10px] text-gray-400 block">Harga Tiket</span>
                <span class="font-bold text-primary"><?php echo esc_html($harga); ?></span>
            </div>
            <?php if ($rating > 0): ?>
            <div class="flex items-center gap-1 text-xs font-bold text-gray-700">
                <i class="fas fa-star text-yellow-400"></i> <?php echo number_format($rating, 1); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>