<?php
/**
 * Template part for displaying Wisata Card
 * Design: Modern Card dengan Image Zoom & Floating Price
 */

$post_id = get_the_ID();
$harga = get_post_meta($post_id, 'harga_tiket', true);
$lokasi = get_post_meta($post_id, 'lokasi', true);
$rating = get_post_meta($post_id, 'rating_wisata', true) ?: 4.5; // Default fallback rating
$ulasan = rand(10, 50); // Dummy data ulasan jika belum ada
?>

<div class="group bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden flex flex-col h-full border border-gray-100">
    
    <!-- Bagian Gambar dengan Badge Kategori -->
    <div class="relative h-64 overflow-hidden">
        <a href="<?php the_permalink(); ?>" class="block h-full w-full">
            <?php if (has_post_thumbnail()) : ?>
                <img src="<?php the_post_thumbnail_url('large'); ?>" 
                     alt="<?php the_title(); ?>" 
                     class="w-full h-full object-cover transform group-hover:scale-110 transition duration-700 ease-in-out">
            <?php else: ?>
                <img src="https://via.placeholder.com/600x400?text=Wisata+Desa" 
                     alt="Placeholder" 
                     class="w-full h-full object-cover bg-gray-200">
            <?php endif; ?>
        </a>

        <!-- Badge Kategori Pojok Kiri Atas -->
        <?php 
        $terms = get_the_terms($post_id, 'kategori_wisata');
        if ($terms && !is_wp_error($terms)): ?>
            <div class="absolute top-4 left-4">
                <span class="bg-primary/90 backdrop-blur-sm text-white text-xs font-bold px-3 py-1.5 rounded-full shadow-sm">
                    <?php echo esc_html($terms[0]->name); ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- Badge Harga Melayang Pojok Kanan Bawah -->
        <div class="absolute bottom-0 right-0 bg-yellow-400 text-gray-900 px-4 py-2 rounded-tl-xl font-bold text-sm shadow-md">
            <?php echo $harga ? tema_dw_format_rupiah($harga) : 'Gratis / Donasi'; ?>
        </div>
    </div>

    <!-- Bagian Konten -->
    <div class="p-6 flex flex-col flex-grow">
        <!-- Rating & Lokasi Kecil -->
        <div class="flex justify-between items-center text-xs text-gray-500 mb-3">
            <div class="flex items-center text-yellow-500">
                <i class="fas fa-star mr-1"></i>
                <span class="font-bold text-gray-700"><?php echo $rating; ?></span>
                <span class="text-gray-400 ml-1">(<?php echo $ulasan; ?> Ulasan)</span>
            </div>
            <?php if($lokasi): ?>
            <div class="flex items-center truncate max-w-[50%]">
                <i class="fas fa-map-pin text-red-500 mr-1"></i>
                <span class="truncate"><?php echo esc_html($lokasi); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Judul -->
        <h3 class="text-xl font-bold text-gray-800 mb-3 leading-snug group-hover:text-primary transition-colors">
            <a href="<?php the_permalink(); ?>">
                <?php the_title(); ?>
            </a>
        </h3>

        <!-- Excerpt Pendek -->
        <div class="text-gray-600 text-sm line-clamp-2 mb-4 flex-grow">
            <?php echo get_the_excerpt(); ?>
        </div>

        <!-- Tombol Aksi -->
        <div class="mt-auto pt-4 border-t border-gray-100 flex items-center justify-between gap-3">
            <a href="<?php the_permalink(); ?>" class="flex-1 text-center bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200 py-2.5 rounded-lg text-sm font-semibold transition">
                Detail
            </a>
            <a href="<?php the_permalink(); ?>" class="flex-1 text-center bg-primary text-white hover:bg-green-700 py-2.5 rounded-lg text-sm font-semibold shadow-md transition transform active:scale-95">
                Pesan Tiket
            </a>
        </div>
    </div>
</div>