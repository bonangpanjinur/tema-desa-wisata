<?php
/**
 * Template part for displaying Wisata Card (Elegant Style)
 * Data Source: Plugin Database (wp_dw_wisata)
 * * @var object $args['data'] Data row dari tabel database
 */

// 1. Ambil Data
$wisata = $args['data'] ?? null;
if ( ! $wisata ) return;

// 2. Setup Variabel (Validasi Data Kosong)
$image_url   = !empty($wisata->foto_utama) ? esc_url($wisata->foto_utama) : 'https://via.placeholder.com/600x400?text=Wisata+Desa';
// Link manual karena ini bukan Post WP biasa
$link_detail = home_url('/wisata/' . ($wisata->slug ?? '#')); 
$judul       = esc_html($wisata->nama_wisata);
$desa        = isset($wisata->nama_desa) ? esc_html($wisata->nama_desa) : 'Desa Wisata';
$kategori    = !empty($wisata->kategori) ? esc_html($wisata->kategori) : 'Wisata Alam'; // Default jika kosong
$harga       = ($wisata->harga_tiket > 0) ? tema_dw_format_rupiah($wisata->harga_tiket) : 'Gratis';
$rating      = floatval($wisata->rating_avg);
$ulasan      = intval($wisata->total_ulasan);

// Warna Badge Berdasarkan Kategori (Opsional, logika sederhana)
$badge_color = 'bg-blue-600';
if (stripos($kategori, 'alam') !== false) $badge_color = 'bg-green-600';
if (stripos($kategori, 'kuliner') !== false) $badge_color = 'bg-orange-500';
if (stripos($kategori, 'budaya') !== false) $badge_color = 'bg-purple-600';
?>

<div class="group relative bg-white rounded-2xl shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 h-full flex flex-col overflow-hidden border border-gray-100">
    
    <a href="<?php echo $link_detail; ?>" class="block h-full flex flex-col">
        
        <!-- BAGIAN GAMBAR -->
        <div class="relative h-48 md:h-52 overflow-hidden">
            <!-- Gambar Utama -->
            <img src="<?php echo $image_url; ?>" 
                 alt="<?php echo $judul; ?>" 
                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                 loading="lazy">
            
            <!-- Overlay Gradient (Agar teks putih terbaca) -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-80"></div>

            <!-- BADGE KATEGORI (Permintaan 1) -->
            <div class="absolute top-3 left-3">
                <span class="<?php echo $badge_color; ?> text-white text-[10px] uppercase font-bold tracking-wider px-2.5 py-1 rounded-full shadow-sm backdrop-blur-sm bg-opacity-90">
                    <?php echo $kategori; ?>
                </span>
            </div>

            <!-- Rating (Glassmorphism) -->
            <div class="absolute top-3 right-3 flex items-center gap-1 bg-white/20 backdrop-blur-md border border-white/30 px-2 py-1 rounded-lg">
                <i class="fas fa-star text-yellow-400 text-xs"></i>
                <span class="text-white text-xs font-bold"><?php echo number_format($rating, 1); ?></span>
            </div>

            <!-- Info Lokasi di Atas Gambar -->
            <div class="absolute bottom-3 left-3 text-white">
                <div class="flex items-center gap-1 text-xs font-medium opacity-90">
                    <i class="fas fa-map-marker-alt text-red-400"></i>
                    <span><?php echo $desa; ?></span>
                </div>
            </div>
        </div>

        <!-- BAGIAN KONTEN -->
        <div class="p-4 flex flex-col flex-grow">
            <!-- Judul -->
            <h3 class="text-lg font-bold text-gray-800 mb-2 leading-tight line-clamp-2 group-hover:text-primary transition-colors">
                <?php echo $judul; ?>
            </h3>

            <!-- Deskripsi Singkat / Excerpt (Opsional) -->
            <p class="text-sm text-gray-500 line-clamp-2 mb-4 leading-relaxed">
                <?php echo wp_trim_words($wisata->deskripsi ?? '', 10, '...'); ?>
            </p>

            <!-- Footer Card: Harga & Tombol -->
            <div class="mt-auto pt-3 border-t border-gray-50 flex items-center justify-between">
                <div>
                    <span class="text-[10px] text-gray-400 block mb-0.5">Mulai dari</span>
                    <span class="text-base font-bold text-primary"><?php echo $harga; ?></span>
                </div>
                
                <div class="w-8 h-8 rounded-full bg-gray-50 text-gray-400 flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all duration-300">
                    <i class="fas fa-arrow-right text-sm transform -rotate-45 group-hover:rotate-0 transition-transform"></i>
                </div>
            </div>
        </div>
    </a>
</div>