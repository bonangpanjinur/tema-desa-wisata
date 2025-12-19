<?php
/**
 * Template Part: Card Wisata (Updated to Match Product Card Style)
 * Location: template-parts/card-wisata.php
 * Description: Komponen kartu wisata yang disamakan gayanya dengan Card Produk (Grid Responsive).
 */

$w = $args['item'] ?? null;

if ( ! $w ) return;

// =================================================================================
// 1. LOGIKA DATA
// =================================================================================

// A. URL / PERMALINK
$slug_wisata = ! empty( $w->slug ) ? $w->slug : sanitize_title( $w->nama_wisata );
$link_w      = home_url( '/wisata/detail/' . $slug_wisata );

// B. LOGIKA GAMBAR
$img_w    = 'https://via.placeholder.com/400x300?text=Wisata';
$raw_foto = $w->foto_utama ?? '';

if ( ! empty( $raw_foto ) ) {
    // Cek JSON
    $json = json_decode( $raw_foto, true );
    if ( json_last_error() === JSON_ERROR_NONE && is_array( $json ) && !empty($json) ) {
        $raw_foto = $json[0];
    }
    
    // Resolve ID vs URL
    if ( is_numeric( $raw_foto ) ) {
        $att = wp_get_attachment_image_src( $raw_foto, 'medium_large' );
        if ( $att ) $img_w = $att[0];
    } else {
        $img_w = $raw_foto;
    }
}

// C. DATA PENDUKUNG
// Prioritas: Nama Desa -> Kabupaten -> Default
$lokasi   = ! empty( $w->nama_desa ) ? 'Desa ' . $w->nama_desa : ( $w->kabupaten ?? 'Indonesia' );
$rating   = isset( $w->rating_avg ) && $w->rating_avg > 0 ? $w->rating_avg : '4.8';
$kategori = $w->kategori ?? 'Alam';
$harga    = $w->harga_tiket ?? 0;

// D. WARNA KATEGORI
$cat_colors = [
    'Alam'    => 'bg-green-100 text-green-700 border-green-200',
    'Budaya'  => 'bg-purple-100 text-purple-700 border-purple-200',
    'Edukasi' => 'bg-blue-100 text-blue-700 border-blue-200',
    'Religi'  => 'bg-yellow-100 text-yellow-700 border-yellow-200',
];
$cat_class  = $cat_colors[ $kategori ] ?? 'bg-gray-100 text-gray-700 border-gray-200';
// $cat_slug   = sanitize_title($kategori); // Tidak terlalu dibutuhkan di grid view
?>

<!-- =================================================================================
     2. TAMPILAN DESAIN (GRID STYLE - MATCHING PRODUCT CARD)
     ================================================================================= -->

<!-- Wrapper Utama: Struktur sama persis dengan card-produk.php -->
<div class="group h-full flex flex-col bg-white rounded-xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 relative">
    
    <!-- A. IMAGE WRAPPER -->
    <!-- Note: Kita gunakan aspect-[4/3] agar foto wisata terlihat lebih luas, 
         tetapi styling wrapper disamakan dengan produk -->
    <div class="relative w-full aspect-[4/3] bg-gray-50 overflow-hidden">
        <a href="<?php echo esc_url( $link_w ); ?>" class="block w-full h-full">
            <img src="<?php echo esc_url( $img_w ); ?>" 
                 alt="<?php echo esc_attr( $w->nama_wisata ); ?>" 
                 class="w-full h-full object-cover object-center transform group-hover:scale-110 transition-transform duration-500 ease-out"
                 loading="lazy"
                 onerror="this.src='https://via.placeholder.com/400x300?text=No+Image';">
            
            <!-- Overlay Gelap Halus saat Hover (Sama seperti Produk) -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300"></div>
        </a>

        <!-- Badge Kategori (Pojok Kiri Atas) -->
        <div class="absolute top-2 left-2 z-10">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border shadow-sm uppercase tracking-wide <?php echo $cat_class; ?>">
                <?php echo esc_html( $kategori ); ?>
            </span>
        </div>
    </div>

    <!-- B. KONTEN WISATA -->
    <!-- Padding disamakan p-3 agar kompak -->
    <div class="p-3 flex flex-col flex-grow">
        
        <!-- Baris Rating & Lokasi (Style text-[10px] sama dengan produk) -->
        <div class="flex items-center justify-between mb-1.5 text-[10px] md:text-xs">
            <div class="flex items-center gap-1 text-yellow-500 font-bold bg-yellow-50 px-1.5 py-0.5 rounded">
                <i class="fas fa-star text-[9px]"></i> <?php echo $rating; ?>
            </div>
            <div class="flex items-center gap-1 text-gray-400 truncate max-w-[60%]">
                <i class="fas fa-map-marker-alt text-[9px]"></i> 
                <span class="truncate"><?php echo esc_html( $lokasi ); ?></span>
            </div>
        </div>

        <!-- Judul Wisata (Style text-sm sama dengan produk) -->
        <h3 class="text-sm font-bold text-gray-800 leading-snug mb-3 line-clamp-2 min-h-[2.5em] group-hover:text-green-600 transition-colors" title="<?php echo esc_attr( $w->nama_wisata ); ?>">
            <a href="<?php echo esc_url( $link_w ); ?>">
                <?php echo esc_html( $w->nama_wisata ); ?>
            </a>
        </h3>

        <!-- C. FOOTER: HARGA & TOMBOL -->
        <!-- mt-auto memaksa bagian ini selalu di dasar kartu -->
        <div class="mt-auto pt-3 border-t border-dashed border-gray-100 flex items-end justify-between gap-2">
            
            <!-- Harga -->
            <div class="flex flex-col">
                <span class="text-[10px] text-gray-400 mb-0.5">Tiket Masuk</span>
                <span class="text-green-600 font-extrabold text-sm md:text-base leading-none">
                    <?php echo ( $harga > 0 ) ? 'Rp ' . number_format( $harga, 0, ',', '.' ) : 'Gratis'; ?>
                </span>
            </div>
            
            <!-- Tombol Panah (Detail) -->
            <!-- Ukuran tombol disamakan dengan tombol cart produk: w-8 h-8 -->
            <a href="<?php echo esc_url( $link_w ); ?>" 
               class="w-8 h-8 md:w-9 md:h-9 rounded-full bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center hover:bg-blue-600 hover:text-white hover:shadow-md transition-all duration-300 active:scale-95 flex-shrink-0"
               aria-label="Lihat Detail">
                <i class="fas fa-arrow-right text-xs md:text-sm"></i>
            </a>
        </div>

    </div>
</div>