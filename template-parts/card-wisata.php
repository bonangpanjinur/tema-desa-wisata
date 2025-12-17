<?php
/**
 * Template Part: Card Wisata
 * Location: template-parts/card-wisata.php
 * Description: Komponen kartu standar untuk item wisata.
 */

$w = $args['item'] ?? null;

if ( ! $w ) return;

// --- 1. SETUP DATA ---
$slug_wisata = ! empty( $w->slug ) ? $w->slug : sanitize_title( $w->nama_wisata );
$link_w      = home_url( '/wisata/detail/' . $slug_wisata );

// Gambar
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

$lokasi   = ! empty( $w->nama_desa ) ? 'Desa ' . $w->nama_desa : ( $w->kabupaten ?? 'Indonesia' );
$rating   = isset( $w->rating_avg ) && $w->rating_avg > 0 ? $w->rating_avg : '4.8';
$kategori = $w->kategori ?? 'Alam';
$harga    = $w->harga_tiket ?? 0;

// Warna Kategori
$cat_colors = [
    'Alam'    => 'bg-green-100 text-green-700 border-green-200',
    'Budaya'  => 'bg-purple-100 text-purple-700 border-purple-200',
    'Edukasi' => 'bg-blue-100 text-blue-700 border-blue-200',
    'Religi'  => 'bg-yellow-100 text-yellow-700 border-yellow-200',
];
$cat_class  = $cat_colors[ $kategori ] ?? 'bg-gray-100 text-gray-700 border-gray-200';
?>

<!-- CARD DESIGN START -->
<div class="group bg-white rounded-2xl overflow-hidden hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 flex flex-col h-full border border-gray-100 relative">
    
    <!-- Image Wrapper (Aspect Ratio 4:3) -->
    <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
        <a href="<?php echo esc_url( $link_w ); ?>" class="block w-full h-full">
            <img src="<?php echo esc_url( $img_w ); ?>" 
                 class="w-full h-full object-cover transition duration-700 group-hover:scale-110 group-hover:rotate-1" 
                 alt="<?php echo esc_attr( $w->nama_wisata ); ?>" 
                 loading="lazy"
                 onerror="this.src='https://via.placeholder.com/400x300?text=No+Image';">
            
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition duration-300"></div>
        </a>

        <!-- Category Badge -->
        <div class="absolute top-3 left-3">
            <span class="text-[10px] font-bold px-3 py-1 rounded-full border shadow-sm uppercase tracking-wide <?php echo $cat_class; ?>">
                <?php echo esc_html( $kategori ); ?>
            </span>
        </div>
    </div>

    <!-- Content -->
    <div class="p-4 flex flex-col flex-1">
        <!-- Rating & Location -->
        <div class="flex items-center justify-between mb-2 text-xs">
            <div class="flex items-center gap-1 text-yellow-500 font-bold bg-yellow-50 px-2 py-0.5 rounded-md">
                <i class="fas fa-star"></i> <?php echo $rating; ?>
            </div>
            <div class="flex items-center gap-1 text-gray-500 font-medium">
                <i class="fas fa-map-marker-alt text-red-400"></i> 
                <span class="truncate max-w-[80px] sm:max-w-[120px]"><?php echo esc_html( $lokasi ); ?></span>
            </div>
        </div>

        <!-- Title -->
        <a href="<?php echo esc_url( $link_w ); ?>" class="block mb-2">
            <h3 class="font-bold text-gray-900 text-sm md:text-base leading-snug line-clamp-2 group-hover:text-green-600 transition min-h-[2.5em]">
                <?php echo esc_html( $w->nama_wisata ); ?>
            </h3>
        </a>

        <!-- Divider & Price -->
        <div class="mt-auto pt-3 border-t border-dashed border-gray-100 flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-xs text-gray-400">Tiket Masuk</span>
                <span class="text-green-600 font-bold text-lg leading-none">
                    <?php echo ( $harga > 0 ) ? 'Rp ' . number_format( $harga, 0, ',', '.' ) : 'Gratis'; ?>
                </span>
            </div>
            
            <!-- Detail Button -->
            <a href="<?php echo esc_url( $link_w ); ?>" class="w-9 h-9 rounded-full bg-gray-50 text-gray-600 flex items-center justify-center hover:bg-green-600 hover:text-white transition shadow-sm active:scale-95" title="Lihat Detail">
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</div>