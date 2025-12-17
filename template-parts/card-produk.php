<?php
/**
 * Template Part: Card Produk
 * Location: template-parts/card-produk.php
 * Description: Komponen kartu standar untuk item produk agar desain konsisten.
 */

// Ambil data dari $args yang dikirim via get_template_part
$p = $args['item'] ?? null;

if ( ! $p ) return;

// --- 1. LOGIKA URL / PERMALINK ---
// Mengutamakan slug dari database, jika kosong fallback ke sanitize title
$slug_produk = ! empty( $p->slug ) ? $p->slug : sanitize_title( $p->nama_produk );
$link_p      = home_url( '/produk/detail/' . $slug_produk );

// --- 2. LOGIKA GAMBAR (Cerdas: JSON / ID / URL) ---
$img_p    = 'https://via.placeholder.com/400x400?text=Produk'; // Placeholder default
$raw_foto = $p->foto_utama ?? $p->foto_produk ?? ''; // Cek kolom foto_utama (sesuai skema baru) atau foto_produk

if ( ! empty( $raw_foto ) ) {
    // Cek apakah format JSON (array ID)
    $json_data = json_decode( $raw_foto, true );
    if ( json_last_error() === JSON_ERROR_NONE && is_array( $json_data ) && ! empty( $json_data ) ) {
        $raw_foto = $json_data[0]; // Ambil item pertama
    } elseif ( is_serialized( $raw_foto ) ) {
        // Cek serialized array WP lama
        $ser_data = @unserialize( $raw_foto );
        if ( $ser_data && is_array( $ser_data ) ) {
            $raw_foto = $ser_data[0];
        }
    }

    // Resolusi ke URL Gambar
    if ( is_numeric( $raw_foto ) ) {
        // Jika ID Attachment
        $img_att = wp_get_attachment_image_src( $raw_foto, 'medium_large' );
        if ( $img_att ) {
            $img_p = $img_att[0];
        }
    } else {
        // Jika URL String
        $img_p = $raw_foto;
    }
}

// --- 3. DATA PENDUKUNG ---
$lokasi    = ! empty( $p->nama_desa ) ? 'Desa ' . $p->nama_desa : ( $p->kabupaten ?? 'Indonesia' );
$rating    = isset( $p->rating_avg ) && $p->rating_avg > 0 ? $p->rating_avg : '4.5';
$kategori  = $p->kategori ?? 'Umum';
$terjual   = $p->terjual ?? 0;
$harga     = $p->harga ?? 0;
$nama_toko = $p->nama_toko ?? 'UMKM Desa';

// Warna Badge Kategori
$cat_colors = [
    'Makanan'   => 'bg-orange-100 text-orange-700 border-orange-200',
    'Minuman'   => 'bg-blue-100 text-blue-700 border-blue-200',
    'Kerajinan' => 'bg-purple-100 text-purple-700 border-purple-200',
    'Fashion'   => 'bg-pink-100 text-pink-700 border-pink-200',
    'Pertanian' => 'bg-green-100 text-green-700 border-green-200',
];
$cat_class  = $cat_colors[ $kategori ] ?? 'bg-gray-100 text-gray-700 border-gray-200';
?>

<!-- START CARD DESIGN -->
<div class="group bg-white rounded-2xl overflow-hidden hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 flex flex-col h-full border border-gray-100 relative">
    
    <!-- Image Wrapper -->
    <div class="relative aspect-square overflow-hidden bg-gray-100">
        <a href="<?php echo esc_url( $link_p ); ?>" class="block w-full h-full">
            <img src="<?php echo esc_url( $img_p ); ?>" 
                 class="w-full h-full object-cover transition duration-700 group-hover:scale-110 group-hover:rotate-1" 
                 alt="<?php echo esc_attr( $p->nama_produk ); ?>" 
                 loading="lazy" 
                 onerror="this.src='https://via.placeholder.com/400x400?text=No+Image';">
            
            <!-- Overlay Gradient -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition duration-300"></div>
        </a>

        <!-- Category Badge -->
        <div class="absolute top-3 left-3">
            <span class="text-[10px] font-bold px-3 py-1 rounded-full border shadow-sm uppercase tracking-wide <?php echo $cat_class; ?>">
                <?php echo esc_html( $kategori ); ?>
            </span>
        </div>

        <!-- Terlaris Badge (Conditional) -->
        <?php if ( $terjual > 10 ) : ?>
        <div class="absolute top-3 right-3 bg-red-500 text-white text-[10px] font-bold px-2 py-1 rounded shadow-sm flex items-center gap-1">
            <i class="fas fa-fire"></i> Terlaris
        </div>
        <?php endif; ?>
    </div>

    <!-- Content Body -->
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
        <a href="<?php echo esc_url( $link_p ); ?>" class="block mb-1">
            <h3 class="font-bold text-gray-900 text-sm md:text-base leading-snug line-clamp-2 group-hover:text-orange-600 transition min-h-[2.5em]">
                <?php echo esc_html( $p->nama_produk ); ?>
            </h3>
        </a>

        <!-- Shop Name -->
        <div class="text-xs text-gray-400 mb-3 flex items-center gap-1">
            <i class="fas fa-store text-[10px]"></i> <span class="truncate"><?php echo esc_html( $nama_toko ); ?></span>
        </div>

        <!-- Divider & Price Action -->
        <div class="mt-auto pt-3 border-t border-dashed border-gray-100 flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-orange-600 font-bold text-lg leading-none">
                    Rp <?php echo number_format( $harga, 0, ',', '.' ); ?>
                </span>
                <span class="text-[10px] text-gray-400 mt-1"><?php echo $terjual; ?> Terjual</span>
            </div>
            
            <!-- Add Cart Button (AJAX) -->
            <button class="w-9 h-9 rounded-full bg-gray-50 text-gray-600 flex items-center justify-center hover:bg-orange-500 hover:text-white transition shadow-sm active:scale-95 btn-add-to-cart" 
                    data-product-id="<?php echo $p->id; ?>" 
                    data-is-custom="1" 
                    title="Tambah ke Keranjang">
                <i class="fas fa-cart-plus"></i>
            </button>
        </div>
    </div>
</div>