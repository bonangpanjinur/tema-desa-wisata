<?php
/**
 * Template part for displaying Wisata Card
 * Menggunakan data dari Custom Database Table Plugin
 * * @var object $args['data'] Data row dari tabel wp_dw_wisata + join desa
 */

// Ambil data
$wisata = $args['data'] ?? null;

if ( ! $wisata ) return;

// Setup Variabel
$image_url   = !empty($wisata->foto_utama) ? esc_url($wisata->foto_utama) : 'https://via.placeholder.com/600x400?text=Wisata+Desa';
$wisata_url  = home_url('/wisata/' . $wisata->slug);
$nama_wisata = esc_html($wisata->nama_wisata);
$nama_desa   = isset($wisata->nama_desa) ? esc_html($wisata->nama_desa) : 'Desa Wisata';
$harga       = $wisata->harga_tiket > 0 ? tema_dw_format_rupiah($wisata->harga_tiket) : 'Gratis';
$rating      = floatval($wisata->rating_avg);
$ulasan      = intval($wisata->total_ulasan);
?>

<div class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden h-full flex flex-col border border-gray-100 relative">
    
    <a href="<?php echo $wisata_url; ?>" class="block h-full flex flex-col">
        <!-- Image Container -->
        <div class="relative h-44 overflow-hidden">
            <img src="<?php echo $image_url; ?>" 
                 alt="<?php echo $nama_wisata; ?>" 
                 class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700 ease-in-out"
                 loading="lazy">
            
            <!-- Badge Kategori/Rating Floating -->
            <div class="absolute top-3 right-3 flex flex-col gap-1 items-end">
                <?php if ($rating > 0): ?>
                <div class="bg-white/90 backdrop-blur-sm px-2 py-1 rounded-md shadow-sm flex items-center gap-1 text-xs font-bold text-gray-800">
                    <i class="fas fa-star text-yellow-400"></i> <?php echo number_format($rating, 1); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Gradient Overlay di Bawah Gambar agar teks putih terbaca (opsional) -->
            <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-black/50 to-transparent opacity-60"></div>
        </div>

        <!-- Content -->
        <div class="p-4 flex flex-col flex-grow">
            <!-- Lokasi -->
            <div class="flex items-center gap-1.5 mb-1.5 text-xs text-secondary font-medium uppercase tracking-wide">
                <i class="fas fa-map-marked-alt"></i> <?php echo $nama_desa; ?>
            </div>

            <!-- Judul -->
            <h3 class="text-base font-bold text-gray-800 mb-2 leading-tight group-hover:text-primary transition-colors line-clamp-2">
                <?php echo $nama_wisata; ?>
            </h3>

            <div class="mt-auto pt-3 border-t border-gray-50 flex items-end justify-between">
                <div>
                    <p class="text-[10px] text-gray-400 mb-0.5">Tiket Masuk</p>
                    <p class="text-sm font-bold text-primary"><?php echo $harga; ?></p>
                </div>
                
                <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-400 group-hover:bg-primary group-hover:text-white transition-colors">
                    <i class="fas fa-arrow-right text-xs transform -rotate-45 group-hover:rotate-0 transition-transform duration-300"></i>
                </div>
            </div>
        </div>
    </a>
</div>