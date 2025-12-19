<?php
/**
 * Template Part: Card Produk
 * Location: template-parts/card-produk.php
 * Description: Komponen kartu standar untuk item produk dengan desain konsisten & responsif.
 */

// Ambil data dari $args yang dikirim via get_template_part
$p = $args['item'] ?? null;

if ( ! $p ) return;

// =================================================================================
// 1. LOGIKA DATA (BACKEND LOGIC DARI FILE LAMA)
// =================================================================================

// A. URL / PERMALINK
// Mengutamakan slug dari database, jika kosong fallback ke sanitize title
$slug_produk = ! empty( $p->slug ) ? $p->slug : sanitize_title( $p->nama_produk );
$link_p      = home_url( '/produk/detail/' . $slug_produk );

// B. LOGIKA GAMBAR (Cerdas: JSON / ID / URL)
$img_p    = 'https://via.placeholder.com/400x400?text=Produk'; // Placeholder default
$raw_foto = $p->foto_utama ?? $p->foto_produk ?? ''; 

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
        $img_att = wp_get_attachment_image_src( $raw_foto, 'medium_large' ); // Gunakan size medium_large agar tidak berat
        if ( $img_att ) {
            $img_p = $img_att[0];
        }
    } else {
        // Jika URL String
        $img_p = $raw_foto;
    }
}

// C. DATA PENDUKUNG
$lokasi    = ! empty( $p->nama_desa ) ? 'Desa ' . $p->nama_desa : ( $p->kabupaten ?? 'Indonesia' );
$rating    = isset( $p->rating_avg ) && $p->rating_avg > 0 ? $p->rating_avg : '4.5';
$kategori  = $p->kategori ?? 'Umum';
$terjual   = $p->terjual ?? 0;
$harga     = $p->harga ?? 0;
$nama_toko = $p->nama_toko ?? 'UMKM Desa';
$stok      = $p->stok ?? 0;

// D. LOGIKA BADGE KATEGORI
$cat_colors = [
    'Makanan'   => 'bg-orange-100 text-orange-700 border-orange-200',
    'Minuman'   => 'bg-blue-100 text-blue-700 border-blue-200',
    'Kerajinan' => 'bg-purple-100 text-purple-700 border-purple-200',
    'Fashion'   => 'bg-pink-100 text-pink-700 border-pink-200',
    'Pertanian' => 'bg-green-100 text-green-700 border-green-200',
];
$cat_class  = $cat_colors[ $kategori ] ?? 'bg-gray-100 text-gray-700 border-gray-200';
?>

<!-- =================================================================================
     2. TAMPILAN DESAIN (UPDATED UI)
     ================================================================================= -->
     
<!-- Wrapper Utama: h-full penting agar tinggi kartu seragam dalam grid -->
<div class="group h-full flex flex-col bg-white rounded-xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 relative">
    
    <!-- A. IMAGE WRAPPER (ASPECT SQUARE 1:1) -->
    <div class="relative w-full aspect-square bg-gray-50 overflow-hidden">
        <a href="<?php echo esc_url( $link_p ); ?>" class="block w-full h-full">
            <img src="<?php echo esc_url( $img_p ); ?>" 
                 alt="<?php echo esc_attr( $p->nama_produk ); ?>" 
                 class="w-full h-full object-cover object-center transform group-hover:scale-110 transition-transform duration-500 ease-out"
                 loading="lazy">
            
            <!-- Overlay Gelap Halus saat Hover -->
            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-300"></div>
        </a>

        <!-- Badge Kategori (Pojok Kiri Atas) -->
        <div class="absolute top-2 left-2 z-10">
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border shadow-sm uppercase tracking-wide <?php echo $cat_class; ?>">
                <?php echo esc_html( $kategori ); ?>
            </span>
        </div>

        <!-- Badge Stok Habis / Terlaris (Pojok Kanan Atas) -->
        <div class="absolute top-2 right-2 z-10 flex flex-col gap-1 items-end">
            <?php if ( $stok < 1 ) : ?>
                <span class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm">HABIS</span>
            <?php elseif ( $terjual > 10 ) : ?>
                <span class="bg-orange-500 text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm flex items-center gap-1">
                    <i class="fas fa-fire"></i> Laris
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- B. KONTEN PRODUK -->
    <!-- flex-grow & flex-col penting untuk mendorong footer ke bawah -->
    <div class="p-3 flex flex-col flex-grow">
        
        <!-- Baris Rating & Lokasi -->
        <div class="flex items-center justify-between mb-1.5 text-[10px] md:text-xs">
            <div class="flex items-center gap-1 text-yellow-500 font-bold bg-yellow-50 px-1.5 py-0.5 rounded">
                <i class="fas fa-star text-[9px]"></i> <?php echo $rating; ?>
            </div>
            <div class="flex items-center gap-1 text-gray-400 truncate max-w-[50%]">
                <i class="fas fa-map-marker-alt text-[9px]"></i> 
                <span class="truncate"><?php echo esc_html( $lokasi ); ?></span>
            </div>
        </div>

        <!-- Judul Produk -->
        <h3 class="text-sm font-bold text-gray-800 leading-snug mb-1 line-clamp-2 min-h-[2.5em] group-hover:text-green-600 transition-colors" title="<?php echo esc_attr( $p->nama_produk ); ?>">
            <a href="<?php echo esc_url( $link_p ); ?>">
                <?php echo esc_html( $p->nama_produk ); ?>
            </a>
        </h3>

        <!-- Nama Toko -->
        <div class="flex items-center gap-1.5 mb-3">
            <div class="w-4 h-4 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-[8px] flex-shrink-0">
                <i class="fas fa-store"></i>
            </div>
            <span class="text-[10px] md:text-xs text-gray-500 font-medium truncate">
                <?php echo esc_html( $nama_toko ); ?>
            </span>
        </div>

        <!-- C. FOOTER: HARGA & TOMBOL -->
        <!-- mt-auto memaksa bagian ini selalu di dasar kartu -->
        <div class="mt-auto pt-3 border-t border-dashed border-gray-100 flex items-end justify-between gap-2">
            
            <!-- Harga & Terjual -->
            <div class="flex flex-col">
                <span class="text-[10px] text-gray-400 mb-0.5"><?php echo $terjual; ?> Terjual</span>
                <span class="text-orange-600 font-extrabold text-sm md:text-base leading-none">
                    Rp <?php echo number_format( $harga, 0, ',', '.' ); ?>
                </span>
            </div>
            
            <!-- Tombol Keranjang -->
            <?php if ( $stok > 0 ) : ?>
            <button type="button" 
                    class="btn-add-to-cart w-8 h-8 md:w-9 md:h-9 rounded-full bg-green-50 text-green-600 border border-green-100 flex items-center justify-center hover:bg-green-600 hover:text-white hover:shadow-md transition-all duration-300 active:scale-95 flex-shrink-0"
                    data-product-id="<?php echo esc_attr( $p->id ); ?>" 
                    data-is-custom="1" 
                    aria-label="Tambah ke Keranjang">
                <i class="fas fa-cart-plus text-xs md:text-sm"></i>
            </button>
            <?php else: ?>
            <button disabled class="w-8 h-8 rounded-full bg-gray-100 text-gray-400 cursor-not-allowed flex items-center justify-center flex-shrink-0">
                <i class="fas fa-times text-xs"></i>
            </button>
            <?php endif; ?>
        </div>

    </div>
</div>