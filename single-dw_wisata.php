<?php
/**
 * Template Name: Single Detail Wisata
 * Post Type: dw_wisata
 * Description: Desain Modern Sadesa Style dengan Hero Image dan Sidebar Sticky
 */

get_header();

while (have_posts()) : the_post();

    // === 1. DATA FETCHING (Integrasi Plugin) ===
    $post_id = get_the_ID();
    
    // Ambil Meta Data dari Database Plugin
    $lokasi      = get_post_meta($post_id, 'dw_lokasi', true) ?: 'Lokasi belum diatur';
    $harga_tiket = get_post_meta($post_id, 'dw_harga_tiket', true);
    $jam_buka    = get_post_meta($post_id, 'dw_jam_buka', true) ?: '08:00 - 17:00';
    $kontak      = get_post_meta($post_id, 'dw_kontak', true); // No HP / WA
    $fasilitas   = get_post_meta($post_id, 'dw_fasilitas', true); // String dipisah koma
    $gmaps_url   = get_post_meta($post_id, 'dw_gmaps_url', true);
    $rating      = 4.8; // Placeholder, nanti bisa integrasi tabel reviews
    
    // Format Harga
    $price_display = ($harga_tiket > 0) ? 'Rp ' . number_format($harga_tiket, 0, ',', '.') : 'Gratis';

    // Gambar Utama (Hero)
    $hero_img = get_the_post_thumbnail_url($post_id, 'full') ?: 'https://via.placeholder.com/1200x600?text=Wisata+Desa';

    // Kategori
    $terms = get_the_terms($post_id, 'dw_kategori_wisata'); 
    $kategori = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : 'Wisata Alam';
?>

<!-- =================================================================================
     HERO SECTION
     ================================================================================= -->
<div class="relative w-full h-[50vh] md:h-[60vh] bg-gray-900 overflow-hidden group">
    <!-- Background Image dengan Zoom Effect -->
    <img src="<?php echo esc_url($hero_img); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover opacity-70 transition-transform duration-[2s] ease-out group-hover:scale-105">
    
    <!-- Gradient Overlay untuk Teks -->
    <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>
    
    <!-- Content Overlay -->
    <div class="absolute bottom-0 left-0 w-full p-6 md:p-12 z-10">
        <div class="container mx-auto">
            <div class="flex flex-col md:flex-row items-start md:items-end justify-between gap-4">
                <div class="max-w-3xl">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="bg-orange-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider shadow-sm">
                            <?php echo esc_html($kategori); ?>
                        </span>
                        <div class="flex items-center text-yellow-400 text-sm gap-1">
                            <i class="fas fa-star"></i>
                            <span class="font-bold text-white"><?php echo $rating; ?></span>
                            <span class="text-gray-300 font-normal">(Ulasan)</span>
                        </div>
                    </div>
                    
                    <h1 class="text-3xl md:text-5xl font-bold text-white mb-2 leading-tight drop-shadow-lg">
                        <?php the_title(); ?>
                    </h1>
                    
                    <div class="flex items-center text-gray-200 text-sm md:text-lg gap-2">
                        <i class="fas fa-map-marker-alt text-red-500"></i>
                        <span><?php echo esc_html($lokasi); ?></span>
                    </div>
                </div>

                <!-- Tombol Share (Mobile Only / Optional) -->
                <div class="hidden md:flex gap-2">
                    <button class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white p-3 rounded-full transition" title="Bagikan">
                        <i class="fas fa-share-alt"></i>
                    </button>
                    <button class="bg-white/10 hover:bg-white/20 backdrop-blur-md text-white p-3 rounded-full transition" title="Simpan">
                        <i class="far fa-heart"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- =================================================================================
     MAIN CONTENT
     ================================================================================= -->
<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            
            <!-- LEFT COLUMN: Content (2/3 Width) -->
            <div class="w-full lg:w-2/3 space-y-8">
                
                <!-- Tab / Navigasi Internal (Opsional) -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-2 flex gap-2 overflow-x-auto no-scrollbar">
                    <a href="#deskripsi" class="px-4 py-2 bg-primary/10 text-primary rounded-lg text-sm font-bold whitespace-nowrap">Deskripsi</a>
                    <a href="#fasilitas" class="px-4 py-2 hover:bg-gray-50 text-gray-600 rounded-lg text-sm font-medium whitespace-nowrap transition">Fasilitas</a>
                    <a href="#lokasi" class="px-4 py-2 hover:bg-gray-50 text-gray-600 rounded-lg text-sm font-medium whitespace-nowrap transition">Peta Lokasi</a>
                    <a href="#ulasan" class="px-4 py-2 hover:bg-gray-50 text-gray-600 rounded-lg text-sm font-medium whitespace-nowrap transition">Ulasan</a>
                </div>

                <!-- Deskripsi -->
                <div id="deskripsi" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-info-circle text-primary"></i> Tentang Wisata
                    </h2>
                    <div class="prose prose-green max-w-none text-gray-600 leading-relaxed text-justify">
                        <?php the_content(); ?>
                    </div>
                </div>

                <!-- Fasilitas -->
                <?php if (!empty($fasilitas)) : ?>
                <div id="fasilitas" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                        <i class="fas fa-concierge-bell text-primary"></i> Fasilitas Tersedia
                    </h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php 
                        $fasilitas_arr = is_array($fasilitas) ? $fasilitas : explode(',', $fasilitas);
                        foreach ($fasilitas_arr as $f) : 
                            if(trim($f) == '') continue;
                        ?>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-100 hover:border-primary/30 hover:bg-green-50 transition">
                                <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center text-primary shadow-sm">
                                    <i class="fas fa-check text-xs"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700"><?php echo esc_html(trim($f)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Peta Lokasi -->
                <?php if ($gmaps_url) : ?>
                <div id="lokasi" class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
                        <i class="fas fa-map-marked-alt text-primary"></i> Lokasi
                    </h2>
                    <div class="aspect-video w-full rounded-xl overflow-hidden bg-gray-200 border border-gray-200 relative group">
                        <!-- Placeholder Map Image / Iframe -->
                        <div class="absolute inset-0 flex items-center justify-center bg-gray-100">
                            <a href="<?php echo esc_url($gmaps_url); ?>" target="_blank" class="text-center group-hover:scale-105 transition">
                                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg text-red-500 mx-auto mb-2">
                                    <i class="fas fa-map-marker-alt text-2xl"></i>
                                </div>
                                <span class="text-sm font-bold text-gray-600 underline">Buka di Google Maps</span>
                            </a>
                        </div>
                    </div>
                    <p class="mt-4 text-sm text-gray-500 flex items-start gap-2">
                        <i class="fas fa-map-pin mt-1 text-gray-400"></i>
                        <?php echo esc_html($lokasi); ?>
                    </p>
                </div>
                <?php endif; ?>

            </div>

            <!-- RIGHT COLUMN: Sidebar (Sticky) (1/3 Width) -->
            <div class="w-full lg:w-1/3">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Card Info Utama (Pricing & CTA) -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden relative">
                        <!-- Pita Diskon (Contoh) -->
                        <!-- <div class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-lg z-10">PROMO</div> -->

                        <div class="p-6">
                            <p class="text-sm text-gray-500 font-medium mb-1">Harga Tiket Masuk</p>
                            <div class="flex items-end gap-1 mb-6">
                                <h3 class="text-3xl font-extrabold text-primary"><?php echo $price_display; ?></h3>
                                <span class="text-sm text-gray-400 font-medium mb-1">/ orang</span>
                            </div>

                            <hr class="border-dashed border-gray-200 mb-6">

                            <!-- Jam Operasional -->
                            <div class="flex items-start gap-4 mb-4">
                                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                                    <i class="far fa-clock"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-gray-800">Jam Buka</h4>
                                    <p class="text-sm text-gray-600"><?php echo esc_html($jam_buka); ?></p>
                                </div>
                            </div>

                            <!-- Info Tambahan -->
                            <div class="flex items-start gap-4 mb-6">
                                <div class="w-10 h-10 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-gray-800">Tiket On-the-spot</h4>
                                    <p class="text-sm text-gray-600">Tersedia di loket masuk</p>
                                </div>
                            </div>

                            <!-- Tombol Aksi -->
                            <div class="space-y-3">
                                <?php 
                                // Logic Link WhatsApp
                                $wa_link = '#';
                                if ($kontak) {
                                    $wa_num = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $kontak));
                                    $wa_text = urlencode("Halo, saya ingin bertanya tentang wisata " . get_the_title());
                                    $wa_link = "https://wa.me/{$wa_num}?text={$wa_text}";
                                }
                                ?>
                                
                                <?php if ($kontak) : ?>
                                <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="block w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl text-center transition shadow-lg shadow-green-200 flex items-center justify-center gap-2 transform hover:-translate-y-0.5">
                                    <i class="fab fa-whatsapp text-lg"></i> Hubungi Pengelola
                                </a>
                                <?php endif; ?>

                                <?php if ($gmaps_url) : ?>
                                <a href="<?php echo esc_url($gmaps_url); ?>" target="_blank" class="block w-full bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold py-3.5 rounded-xl text-center transition flex items-center justify-center gap-2">
                                    <i class="fas fa-directions text-blue-500"></i> Petunjuk Arah
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-3 text-center border-t border-gray-100">
                            <p class="text-xs text-gray-400">Jaminan harga terbaik & informasi valid.</p>
                        </div>
                    </div>

                    <!-- Card Bantuan / Kontak Admin Desa -->
                    <div class="bg-blue-50 rounded-2xl p-6 border border-blue-100 text-center">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm text-blue-500">
                            <i class="fas fa-headset text-xl"></i>
                        </div>
                        <h4 class="font-bold text-blue-900 mb-1">Butuh Bantuan?</h4>
                        <p class="text-xs text-blue-700 mb-3">Hubungi admin desa untuk info grup/rombongan.</p>
                        <a href="<?php echo home_url('/kontak'); ?>" class="text-sm font-bold text-blue-600 hover:underline">Hubungi Admin</a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- =================================================================================
     RELATED SECTION
     ================================================================================= -->
<div class="bg-white py-16 border-t border-gray-100">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-8">
            <h3 class="text-2xl font-bold text-gray-800">Wisata Lainnya</h3>
            <a href="<?php echo home_url('/wisata'); ?>" class="text-primary font-bold text-sm hover:underline">Lihat Semua</a>
        </div>

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
                    $r_img = get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: 'https://via.placeholder.com/400x300';
                    $r_loc = get_post_meta(get_the_ID(), 'dw_lokasi', true);
                    $r_price = get_post_meta(get_the_ID(), 'dw_harga_tiket', true);
            ?>
            <a href="<?php the_permalink(); ?>" class="group block bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition hover:-translate-y-1">
                <div class="aspect-[4/3] relative overflow-hidden bg-gray-100">
                    <img src="<?php echo esc_url($r_img); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <?php if($r_price): ?>
                        <span class="absolute top-3 left-3 bg-black/60 text-white text-[10px] font-bold px-2 py-1 rounded backdrop-blur-sm">
                            <?php echo dw_format_rupiah($r_price); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="p-4">
                    <h4 class="font-bold text-gray-800 mb-1 group-hover:text-primary transition line-clamp-1"><?php the_title(); ?></h4>
                    <p class="text-xs text-gray-500 flex items-center gap-1">
                        <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($r_loc ?: 'Wisata Desa'); ?>
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

<?php endwhile; ?>

<?php get_footer(); ?>