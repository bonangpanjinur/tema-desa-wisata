<?php
/**
 * Template Name: Single Wisata (Tailwind App Style)
 */
get_header();

while ( have_posts() ) :
    the_post();
    $post_id = get_the_ID();

    // Data Meta
    $harga_tiket = get_post_meta($post_id, 'harga_tiket', true) ?: 0;
    $lokasi      = get_post_meta($post_id, 'lokasi_maps', true);
    $rating      = get_post_meta($post_id, 'rating_avg', true) ?: 0;
    $jam_buka    = get_post_meta($post_id, 'jam_buka', true) ?: '08:00 - 17:00';
    $kontak      = get_post_meta($post_id, 'kontak_hp', true);
    $img_url     = get_the_post_thumbnail_url($post_id, 'full') ?: 'https://via.placeholder.com/800x600?text=Wisata';
?>

<div class="bg-gray-50 min-h-screen pb-24 md:pb-0 font-sans">
    
    <div class="max-w-6xl mx-auto md:grid md:grid-cols-2 md:gap-8 md:py-8 md:px-4">
        
        <!-- Kolom Kiri: Gambar -->
        <div class="relative h-[300px] md:h-[450px] bg-gray-200 md:rounded-2xl overflow-hidden shadow-sm">
            <img src="<?php echo esc_url($img_url); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover">
            <!-- Overlay Gradient Mobile -->
            <div class="absolute bottom-0 left-0 w-full h-24 bg-gradient-to-t from-black/60 to-transparent md:hidden"></div>
        </div>

        <!-- Kolom Kanan: Konten -->
        <div class="relative -mt-8 md:mt-0 bg-white rounded-t-3xl md:rounded-2xl p-6 md:p-8 shadow-md md:shadow-none md:border md:border-gray-100 z-10 min-h-[50vh]">
            
            <!-- Header -->
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2 leading-tight"><?php the_title(); ?></h1>
            
            <div class="flex items-center text-gray-500 text-sm mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 text-primary mr-1">
                    <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd" />
                </svg>
                <span><?php echo !empty($lokasi) ? esc_html($lokasi) : 'Lokasi Desa Wisata'; ?></span>
            </div>

            <!-- Info Badges -->
            <div class="grid grid-cols-2 gap-3 mb-6 pb-6 border-b border-gray-100">
                <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-xl">
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-primary">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Rating</div>
                        <div class="text-sm font-bold text-gray-800"><?php echo number_format($rating, 1); ?>/5.0</div>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-xl">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                        <i class="far fa-clock"></i>
                    </div>
                    <div>
                        <div class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Buka</div>
                        <div class="text-sm font-bold text-gray-800"><?php echo esc_html($jam_buka); ?></div>
                    </div>
                </div>
            </div>

            <!-- Deskripsi -->
            <div class="prose prose-sm text-gray-600 mb-8 max-w-none">
                <?php the_content(); ?>
            </div>

            <!-- Desktop Action (Visible only on Desktop) -->
            <div class="hidden md:block bg-gray-50 rounded-xl p-5 border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-gray-500 font-medium">Harga Tiket</span>
                    <span class="text-2xl font-bold text-primary">
                        <?php echo ($harga_tiket > 0) ? 'Rp ' . number_format($harga_tiket, 0, ',', '.') : 'Gratis'; ?>
                    </span>
                </div>
                <a href="https://wa.me/<?php echo esc_attr($kontak); ?>?text=Halo, saya ingin pesan tiket wisata <?php the_title(); ?>" class="flex items-center justify-center gap-2 w-full bg-primary hover:bg-green-800 text-white font-bold py-3 px-4 rounded-xl transition-colors">
                    <i class="fab fa-whatsapp text-xl"></i> Pesan Sekarang
                </a>
            </div>

        </div>
    </div>

    <!-- STICKY BOTTOM BAR (Mobile Only) -->
    <div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-100 p-4 pb-6 flex items-center justify-between z-50 md:hidden shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        <div class="flex flex-col">
            <span class="text-xs text-gray-500">Harga Mulai</span>
            <span class="text-lg font-bold text-primary">
                <?php echo ($harga_tiket > 0) ? 'Rp ' . number_format($harga_tiket, 0, ',', '.') : 'Gratis'; ?>
            </span>
        </div>
        <a href="https://wa.me/<?php echo esc_attr($kontak); ?>?text=Halo, saya ingin pesan tiket wisata <?php the_title(); ?>" class="bg-primary hover:bg-green-800 text-white font-bold py-3 px-6 rounded-xl flex items-center gap-2 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                <path d="M21.41 11.58l-9-9C12.05 2.22 11.55 2 11 2H4c-1.1 0-2 .9-2 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM5.5 7C4.67 7 4 6.33 4 5.5S4.67 4 5.5 4 7 4.67 7 5.5 6.33 7 5.5 7z"/>
            </svg>
            Pesan Tiket
        </a>
    </div>

</div>

<?php 
endwhile;
get_footer(); 
?>