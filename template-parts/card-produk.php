<?php
/**
 * Template part for displaying Produk Card
 * Design: E-commerce Standard (Tokopedia/Shopee style)
 */

$post_id = get_the_ID();
$harga = get_post_meta($post_id, 'harga_produk', true);
$stok = get_post_meta($post_id, 'stok_produk', true);
$terjual = get_post_meta($post_id, 'terjual', true) ?: 0;
$lokasi_toko = "Pusat Oleh-oleh"; // Bisa diambil dari user meta pedagang
?>

<div class="group bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-xl hover:border-primary/30 transition-all duration-300 flex flex-col h-full overflow-hidden relative">
    
    <!-- Image Square -->
    <div class="relative aspect-square bg-gray-100 overflow-hidden">
        <a href="<?php the_permalink(); ?>" class="block w-full h-full">
            <?php if (has_post_thumbnail()) : ?>
                <img src="<?php the_post_thumbnail_url('medium'); ?>" 
                     alt="<?php the_title(); ?>" 
                     class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-500">
            <?php else: ?>
                <img src="https://via.placeholder.com/300x300?text=Produk" 
                     alt="No Image" 
                     class="w-full h-full object-cover opacity-50">
            <?php endif; ?>
        </a>
        
        <!-- Quick Action (Hover only on Desktop) -->
        <div class="absolute bottom-0 left-0 right-0 bg-black/60 backdrop-blur-sm p-2 translate-y-full group-hover:translate-y-0 transition duration-300 flex justify-center space-x-3 md:flex hidden">
            <button onclick="quickView(<?php echo $post_id; ?>)" class="text-white hover:text-secondary text-sm" title="Lihat Cepat"><i class="far fa-eye"></i></button>
            <button onclick="addToWishlist(<?php echo $post_id; ?>)" class="text-white hover:text-red-500 text-sm" title="Simpan"><i class="far fa-heart"></i></button>
        </div>
    </div>

    <!-- Body -->
    <div class="p-3 md:p-4 flex flex-col flex-grow">
        <!-- Judul -->
        <h3 class="text-sm md:text-base font-medium text-gray-800 line-clamp-2 mb-1 min-h-[40px] leading-snug" title="<?php the_title(); ?>">
            <a href="<?php the_permalink(); ?>" class="group-hover:text-primary transition">
                <?php the_title(); ?>
            </a>
        </h3>

        <!-- Harga -->
        <div class="mb-2">
            <span class="text-base md:text-lg font-bold text-gray-900 block">
                <?php echo $harga ? tema_dw_format_rupiah($harga) : 'Hubungi Penjual'; ?>
            </span>
        </div>

        <!-- Info Toko & Terjual -->
        <div class="flex items-center text-xs text-gray-500 mb-3 space-x-1">
            <i class="fas fa-store text-gray-400"></i>
            <span class="truncate max-w-[80px]"><?php echo $lokasi_toko; ?></span>
            <span class="mx-1">•</span>
            <span class="text-gray-600">Terjual <?php echo $terjual; ?></span>
        </div>

        <!-- Spacer agar tombol di bawah rata -->
        <div class="mt-auto"></div>

        <!-- Tombol Add to Cart -->
        <?php if ($stok > 0) : ?>
            <button class="add-to-cart-btn w-full py-2 px-3 bg-white border border-primary text-primary rounded-lg text-sm font-semibold hover:bg-primary hover:text-white transition-colors duration-200 flex items-center justify-center gap-2"
                    data-id="<?php echo $post_id; ?>">
                <i class="fas fa-plus"></i> Keranjang
            </button>
        <?php else : ?>
            <button class="w-full py-2 px-3 bg-gray-100 text-gray-400 rounded-lg text-sm font-semibold cursor-not-allowed border border-gray-200" disabled>
                Stok Habis
            </button>
        <?php endif; ?>
    </div>
</div>