<?php
/**
 * Template Name: Single Detail Wisata
 * Post Type: dw_wisata
 * Description: Desain Sadesa Style dengan Hero Image dan Sidebar Info
 */

get_header();

while (have_posts()) : the_post();

    // === DATA FETCHING ===
    $post_id = get_the_ID();
    
    // Ambil Meta Data Plugin
    $lokasi      = get_post_meta($post_id, 'dw_lokasi', true) ?: 'Desa Wisata';
    $harga_tiket = get_post_meta($post_id, 'dw_harga_tiket', true) ?: 0;
    $jam_buka    = get_post_meta($post_id, 'dw_jam_buka', true) ?: '08:00 - 17:00';
    $kontak      = get_post_meta($post_id, 'dw_kontak', true);
    $fasilitas   = get_post_meta($post_id, 'dw_fasilitas', true); // Bisa string koma atau array
    $gmaps_url   = get_post_meta($post_id, 'dw_gmaps_url', true);
    
    // Format Harga
    $price_display = ($harga_tiket > 0) ? 'Rp ' . number_format($harga_tiket, 0, ',', '.') : 'Gratis';

    // Gambar Utama (Hero)
    $hero_img = get_the_post_thumbnail_url($post_id, 'full') ?: 'https://via.placeholder.com/1200x600?text=Wisata+Desa';

    // Kategori (Taxonomy)
    $kategori = 'Wisata Alam'; // Default fallback
    // Cek apakah taxonomy khusus ada, jika tidak pakai default
    $terms = get_the_terms($post_id, 'dw_kategori_wisata'); 
    if (!empty($terms) && !is_wp_error($terms)) {
        $kategori = $terms[0]->name;
    }
?>

<!-- =================================================================================
     VIEW SECTION (SADESA STYLE)
     ================================================================================= -->

<!-- HERO SECTION -->
<div class="relative w-full h-[400px] md:h-[500px] bg-gray-900 overflow-hidden">
    <!-- Background Image -->
    <img src="<?php echo esc_url($hero_img); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover opacity-60">
    
    <!-- Gradient Overlay -->
    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>
    
    <!-- Content Overlay -->
    <div class="absolute bottom-0 left-0 w-full p-6 md:p-12">
        <div class="container mx-auto">
            <span class="bg-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-3 inline-block shadow-sm">
                <?php echo esc_html($kategori); ?>
            </span>
            <h1 class="text-3xl md:text-5xl font-bold text-white mb-2 leading-tight drop-shadow-md">
                <?php the_title(); ?>
            </h1>
            <div class="flex items-center text-gray-300 text-sm md:text-base gap-4">
                <div class="flex items-center gap-1">
                    <i class="fas fa-map-marker-alt text-red-500"></i>
                    <span><?php echo esc_html($lokasi); ?></span>
                </div>
                <div class="hidden md:flex items-center gap-1">
                    <i class="fas fa-clock text-yellow-400"></i>
                    <span>Buka: <?php echo esc_html($jam_buka); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MAIN CONTENT WRAPPER -->
<div class="container mx-auto px-4 py-10 -mt-8 relative z-10">
    <div class="flex flex-col lg:flex-row gap-8">
        
        <!-- LEFT COLUMN: Content Description & Facilities -->
        <div class="w-full lg:w-2/3">
            <div class="bg-white rounded-2xl shadow-sm p-6 md:p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3">Deskripsi</h2>
                <div class="prose max-w-none text-gray-600 leading-relaxed text-justify">
                    <?php the_content(); ?>
                </div>

                <!-- Fasilitas Section -->
                <?php if (!empty($fasilitas)) : ?>
                <div class="mt-8 pt-6 border-t border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-concierge-bell text-primary"></i> Fasilitas Tersedia
                    </h3>
                    <div class="flex flex-wrap gap-3">
                        <?php 
                        $fasilitas_arr = is_array($fasilitas) ? $fasilitas : explode(',', $fasilitas);
                        foreach ($fasilitas_arr as $f) : 
                            if(trim($f) == '') continue;
                        ?>
                            <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg border border-gray-100 text-sm text-gray-600">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span><?php echo esc_html(trim($f)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT COLUMN: Sidebar Info (Sticky) -->
        <div class="w-full lg:w-1/3">
            <div class="sticky top-24 space-y-6">
                
                <!-- Card Info Utama -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <!-- Pricing Header -->
                    <div class="bg-blue-600 p-4 text-white text-center">
                        <p class="text-sm font-medium opacity-90 mb-1">Harga Tiket Masuk</p>
                        <h3 class="text-3xl font-bold"><?php echo $price_display; ?></h3>
                        <p class="text-xs mt-1">per orang</p>
                    </div>
                    
                    <div class="p-6 space-y-5">
                        <!-- Jam Buka (Detail) -->
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 flex-shrink-0">
                                <i class="far fa-clock"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">Jam Operasional</h4>
                                <p class="text-sm text-gray-600"><?php echo esc_html($jam_buka); ?></p>
                            </div>
                        </div>

                        <!-- Lokasi (Detail) -->
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 flex-shrink-0">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-800">Alamat Lokasi</h4>
                                <p class="text-sm text-gray-600"><?php echo esc_html($lokasi); ?></p>
                            </div>
                        </div>

                        <!-- Tombol Aksi (CTA) -->
                        <div class="pt-4 space-y-3">
                            <?php 
                            // Logic WA Link
                            $wa_link = '#';
                            if ($kontak) {
                                $wa_num = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $kontak));
                                $wa_text = urlencode("Halo, saya ingin bertanya tentang wisata " . get_the_title());
                                $wa_link = "https://wa.me/{$wa_num}?text={$wa_text}";
                            }
                            ?>
                            
                            <?php if ($kontak) : ?>
                            <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="block w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-xl text-center transition shadow-md flex items-center justify-center gap-2 hover:-translate-y-0.5">
                                <i class="fab fa-whatsapp text-lg"></i> Hubungi Pengelola
                            </a>
                            <?php endif; ?>

                            <?php if ($gmaps_url) : ?>
                            <a href="<?php echo esc_url($gmaps_url); ?>" target="_blank" class="block w-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold py-3 px-4 rounded-xl text-center transition flex items-center justify-center gap-2">
                                <i class="fas fa-directions text-blue-500"></i> Petunjuk Arah
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Card Share Social Media -->
                <div class="bg-white rounded-2xl shadow-sm p-6 text-center border border-gray-100">
                    <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wide mb-3">Bagikan Wisata Ini</h4>
                    <div class="flex justify-center gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink(); ?>" target="_blank" class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center hover:bg-blue-600 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="https://twitter.com/intent/tweet?url=<?php the_permalink(); ?>&text=<?php the_title(); ?>" target="_blank" class="w-10 h-10 rounded-full bg-sky-100 text-sky-500 flex items-center justify-center hover:bg-sky-500 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="https://wa.me/?text=<?php echo urlencode(get_the_title() . ' - ' . get_the_permalink()); ?>" target="_blank" class="w-10 h-10 rounded-full bg-green-100 text-green-500 flex items-center justify-center hover:bg-green-500 hover:text-white transition">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>

<?php endwhile; // End loop ?>

<!-- RELATED POSTS / WISATA LAINNYA -->
<div class="bg-gray-50 py-12 mt-10 border-t border-gray-200">
    <div class="container mx-auto px-4">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">Wisata Lainnya</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <?php
            // Query Wisata Lain (Random)
            $related = new WP_Query(array(
                'post_type' => 'dw_wisata',
                'posts_per_page' => 4,
                'post__not_in' => array($post_id),
                'orderby' => 'rand'
            ));

            if ($related->have_posts()) :
                while ($related->have_posts()) : $related->the_post();
                    $r_img = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://via.placeholder.com/300x200';
                    $r_loc = get_post_meta(get_the_ID(), 'dw_lokasi', true) ?: 'Desa Wisata';
            ?>
            <a href="<?php the_permalink(); ?>" class="group bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden block">
                <div class="aspect-video relative overflow-hidden">
                    <img src="<?php echo esc_url($r_img); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                </div>
                <div class="p-4">
                    <h4 class="font-bold text-gray-800 mb-1 group-hover:text-blue-600 transition truncate"><?php the_title(); ?></h4>
                    <p class="text-xs text-gray-500 flex items-center gap-1">
                        <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($r_loc); ?>
                    </p>
                </div>
            </a>
            <?php 
                endwhile;
                wp_reset_postdata();
            endif; 
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>