<?php
/**
 * Template part for displaying Produk Card
 */
$harga = get_post_meta(get_the_ID(), 'harga_produk', true);
$stok = get_post_meta(get_the_ID(), 'stok_produk', true);
$terjual = get_post_meta(get_the_ID(), 'terjual', true); // Optional meta
?>

<div class="bg-white border border-gray-100 rounded-lg shadow-sm hover:shadow-lg transition duration-300 flex flex-col h-full">
    <!-- Image -->
    <div class="relative aspect-square overflow-hidden rounded-t-lg bg-gray-100">
        <a href="<?php the_permalink(); ?>">
            <?php if (has_post_thumbnail()) : ?>
                <img src="<?php the_post_thumbnail_url('medium'); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover hover:opacity-90 transition">
            <?php else: ?>
                <img src="https://via.placeholder.com/300" alt="No Image" class="w-full h-full object-cover">
            <?php endif; ?>
        </a>
    </div>

    <!-- Content -->
    <div class="p-4 flex flex-col flex-grow">
        <!-- Kategori (Optional) -->
        <?php 
            $terms = get_the_terms(get_the_ID(), 'kategori_produk');
            if ($terms && !is_wp_error($terms)) :
                $cat_name = $terms[0]->name;
        ?>
            <span class="text-xs text-gray-400 mb-1"><?php echo $cat_name; ?></span>
        <?php endif; ?>

        <h3 class="text-sm md:text-base font-medium text-gray-800 mb-2 line-clamp-2 leading-snug flex-grow">
            <a href="<?php the_permalink(); ?>" class="hover:text-primary transition">
                <?php the_title(); ?>
            </a>
        </h3>

        <div class="mt-auto">
            <div class="text-lg font-bold text-gray-900 mb-2">
                <?php echo $harga ? format_rupiah($harga) : 'Hubungi Penjual'; ?>
            </div>

            <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                <span><i class="fas fa-box mr-1"></i> Stok: <?php echo $stok ? $stok : '0'; ?></span>
                <?php if($terjual): ?>
                    <span><?php echo $terjual; ?> Terjual</span>
                <?php endif; ?>
            </div>

            <button onclick="addToCart(<?php echo get_the_ID(); ?>)" class="w-full bg-green-50 text-primary border border-primary py-2 rounded text-sm font-semibold hover:bg-primary hover:text-white transition flex justify-center items-center gap-2">
                <i class="fas fa-cart-plus"></i> + Keranjang
            </button>
        </div>
    </div>
</div>