<?php
/**
 * Template part for displaying Wisata Card
 */
$harga = get_post_meta(get_the_ID(), 'harga_tiket', true);
$lokasi = get_post_meta(get_the_ID(), 'lokasi', true);
?>

<div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition duration-300 group flex flex-col h-full">
    <!-- Image -->
    <div class="relative h-56 overflow-hidden">
        <a href="<?php the_permalink(); ?>">
            <?php if (has_post_thumbnail()) : ?>
                <img src="<?php the_post_thumbnail_url('large'); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
            <?php else: ?>
                <img src="https://via.placeholder.com/400x300" alt="No Image" class="w-full h-full object-cover bg-gray-200">
            <?php endif; ?>
        </a>
        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-bold text-primary shadow-sm">
            Wisata
        </div>
    </div>

    <!-- Content -->
    <div class="p-5 flex-grow flex flex-col">
        <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-1">
            <a href="<?php the_permalink(); ?>" class="hover:text-primary transition"><?php the_title(); ?></a>
        </h3>
        
        <div class="text-sm text-gray-500 mb-4 flex items-center">
            <i class="fas fa-map-marker-alt mr-2 text-red-500"></i>
            <span class="truncate"><?php echo $lokasi ? $lokasi : 'Lokasi Desa'; ?></span>
        </div>

        <p class="text-gray-600 text-sm mb-4 line-clamp-2 flex-grow">
            <?php echo get_the_excerpt(); ?>
        </p>

        <div class="flex justify-between items-center pt-4 border-t border-gray-100 mt-auto">
            <div>
                <span class="text-xs text-gray-400 block">Harga Tiket</span>
                <span class="text-lg font-bold text-secondary">
                    <?php echo $harga ? format_rupiah($harga) : 'Gratis'; ?>
                </span>
            </div>
            <a href="<?php the_permalink(); ?>" class="text-primary font-semibold text-sm hover:underline">
                Detail <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    </div>
</div>