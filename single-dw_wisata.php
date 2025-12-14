<?php
/**
 * Template Name: Single Detail Wisata (Modern UX)
 * Post Type: dw_wisata
 */

get_header();

while (have_posts()) : the_post();

    // === 1. DATA FETCHING ===
    $post_id = get_the_ID();
    
    // Meta Data
    $lokasi      = get_post_meta($post_id, 'dw_lokasi', true) ?: 'Lokasi belum diatur';
    $harga_tiket = get_post_meta($post_id, 'dw_harga_tiket', true);
    $jam_buka    = get_post_meta($post_id, 'dw_jam_buka', true) ?: '08:00 - 17:00';
    $kontak      = get_post_meta($post_id, 'dw_kontak', true); 
    $fasilitas   = get_post_meta($post_id, 'dw_fasilitas', true); 
    $gmaps_url   = get_post_meta($post_id, 'dw_gmaps_url', true);
    $rating      = 4.8; // Placeholder nilai rating
    $ulasan_count= 124; // Placeholder jumlah ulasan
    
    // Logic Tampilan Harga
    $price_display = ($harga_tiket > 0) ? '<span class="text-xs text-gray-500 font-normal">Mulai dari</span> <span class="text-primary font-bold text-xl">Rp ' . number_format($harga_tiket, 0, ',', '.') . '</span>' : '<span class="text-primary font-bold text-xl">Gratis</span>';

    // Gambar Utama
    $hero_img = get_the_post_thumbnail_url($post_id, 'full') ?: 'https://via.placeholder.com/1200x600?text=Wisata+Desa';
    
    // Kategori Label
    $terms = get_the_terms($post_id, 'dw_kategori_wisata'); 
    $kategori = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : 'Wisata Alam';

    // Link WhatsApp
    $wa_link = '#';
    if ($kontak) {
        $wa_num = preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $kontak));
        $wa_text = urlencode("Halo, saya tertarik berkunjung ke " . get_the_title() . ". Boleh info lebih lanjut?");
        $wa_link = "https://wa.me/{$wa_num}?text={$wa_text}";
    }
?>

<!-- === HERO SECTION & GALLERY GRID === -->
<div class="bg-white pt-6 pb-8">
    <div class="container mx-auto px-4">
        
        <!-- Breadcrumb & Judul -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-2">
                <a href="<?php echo home_url(); ?>" class="hover:text-primary">Beranda</a>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <a href="<?php echo home_url('/wisata'); ?>" class="hover:text-primary">Wisata</a>
                <i class="fas fa-chevron-right text-[10px]"></i>
                <span class="text-gray-800 font-medium truncate"><?php the_title(); ?></span>
            </div>
            <h1 class="text-2xl md:text-4xl font-extrabold text-gray-900 mb-2 leading-tight"><?php the_title(); ?></h1>
            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt text-red-500"></i> <?php echo esc_html($lokasi); ?></span>
                <span class="hidden md:inline text-gray-300">|</span>
                <span class="flex items-center gap-1"><i class="fas fa-star text-yellow-400"></i> <b><?php echo $rating; ?></b> (<?php echo $ulasan_count; ?> ulasan)</span>
                <span class="hidden md:inline text-gray-300">|</span>
                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded font-bold"><?php echo esc_html($kategori); ?></span>
            </div>
        </div>

        <!-- Gallery Grid (Desktop: 1 Besar + 2 Kecil, Mobile: 1 Besar) -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-2 md:gap-4 h-[300px] md:h-[450px] rounded-2xl overflow-hidden relative group cursor-pointer">
            <!-- Gambar Utama (Kiri) -->
            <div class="md:col-span-3 h-full relative overflow-hidden">
                <img src="<?php echo esc_url($hero_img); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-105" alt="Main Image">
            </div>
            <!-- Gambar Samping (Kanan - Dummy/Placeholder karena WP default cuma 1 thumbnail) -->
            <div class="hidden md:flex flex-col gap-2 md:gap-4 h-full">
                <div class="h-1/2 relative overflow-hidden rounded-tr-none md:rounded-tr-2xl">
                    <img src="<?php echo esc_url($hero_img); ?>" class="w-full h-full object-cover opacity-90 hover:opacity-100 transition" style="filter: brightness(0.9);">
                </div>
                <div class="h-1/2 relative overflow-hidden">
                    <img src="<?php echo esc_url($hero_img); ?>" class="w-full h-full object-cover opacity-90 hover:opacity-100 transition" style="filter: brightness(0.8);">
                    <!-- Overlay "Lihat Semua Foto" -->
                    <div class="absolute inset-0 bg-black/30 flex items-center justify-center hover:bg-black/20 transition">
                        <button class="bg-white/20 backdrop-blur-md border border-white/50 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-white hover:text-gray-900 transition">
                            <i class="fas fa-th mr-2"></i> Lihat Foto
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- === MAIN CONTENT === -->
<div class="bg-gray-50 min-h-screen relative">
    
    <!-- Sticky Sub-Navigation (Desktop Only) -->
    <div class="hidden md:block sticky top-0 md:top-16 z-20 bg-white border-b border-gray-200 shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex gap-8 text-sm font-medium text-gray-600">
                <a href="#ikhtisar" class="py-4 border-b-2 border-primary text-primary">Ikhtisar</a>
                <a href="#fasilitas" class="py-4 border-b-2 border-transparent hover:text-primary hover:border-gray-300 transition">Fasilitas</a>
                <a href="#lokasi" class="py-4 border-b-2 border-transparent hover:text-primary hover:border-gray-300 transition">Lokasi</a>
                <a href="#ulasan" class="py-4 border-b-2 border-transparent hover:text-primary hover:border-gray-300 transition">Ulasan</a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
            
            <!-- KOLOM KIRI (Konten) -->
            <div class="w-full lg:w-2/3 space-y-8">
                
                <!-- Section: Deskripsi -->
                <div id="ikhtisar" class="scroll-mt-32">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Tentang Tempat Ini</h2>
                    <div class="prose prose-green max-w-none text-gray-600 leading-relaxed">
                        <?php the_content(); ?>
                    </div>
                </div>

                <hr class="border-gray-200">

                <!-- Section: Fasilitas -->
                <?php if (!empty($fasilitas)) : ?>
                <div id="fasilitas" class="scroll-mt-32">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Fasilitas Tersedia</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php 
                        $fasilitas_arr = is_array($fasilitas) ? $fasilitas : explode(',', $fasilitas);
                        foreach ($fasilitas_arr as $f) : 
                            if(trim($f) == '') continue;
                        ?>
                            <div class="flex items-center gap-3 p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition">
                                <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-primary flex-shrink-0">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700"><?php echo esc_html(trim($f)); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <hr class="border-gray-200">
                <?php endif; ?>

                <!-- Section: Lokasi (Map Embed Placeholder) -->
                <div id="lokasi" class="scroll-mt-32">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">Lokasi & Peta</h2>
                    <p class="text-gray-600 mb-4 flex items-start gap-2">
                        <i class="fas fa-map-pin mt-1 text-primary"></i> 
                        <?php echo esc_html($lokasi); ?>
                    </p>
                    
                    <div class="relative w-full h-[300px] bg-gray-200 rounded-2xl overflow-hidden group">
                        <!-- Static Map Image Background -->
                        <div class="absolute inset-0 bg-[url('https://maps.googleapis.com/maps/api/staticmap?center=-6.200000,106.816666&zoom=13&size=800x400&sensor=false&key=YOUR_API_KEY_HERE')] bg-cover bg-center grayscale group-hover:grayscale-0 transition duration-500 opacity-50"></div>
                        <div class="absolute inset-0 bg-gray-100/50"></div>
                        
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-lg text-red-500 mb-3 animate-bounce">
                                <i class="fas fa-map-marker-alt text-3xl"></i>
                            </div>
                            <?php if ($gmaps_url) : ?>
                                <a href="<?php echo esc_url($gmaps_url); ?>" target="_blank" class="bg-primary hover:bg-green-700 text-white px-6 py-3 rounded-full font-bold shadow-lg transition transform hover:scale-105 flex items-center gap-2">
                                    <i class="fas fa-location-arrow"></i> Buka Google Maps
                                </a>
                            <?php else : ?>
                                <span class="text-gray-500 font-medium">Koordinat peta belum diatur</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- KOLOM KANAN (Sidebar Sticky) -->
            <div class="w-full lg:w-1/3 relative">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Card Booking / Info -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                        <div class="p-6">
                            <div class="flex justify-between items-end mb-6">
                                <div><?php echo $price_display; ?></div>
                                <div class="text-xs font-bold text-gray-400 uppercase">Per Orang</div>
                            </div>

                            <!-- Jam Operasional -->
                            <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-bold text-gray-700">Jam Operasional</span>
                                    <span class="text-xs font-bold text-green-600 bg-green-100 px-2 py-0.5 rounded-full">Buka Hari Ini</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <i class="far fa-clock"></i> <?php echo esc_html($jam_buka); ?> WIB
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-3">
                                <?php if ($kontak) : ?>
                                <a href="<?php echo esc_url($wa_link); ?>" target="_blank" class="flex items-center justify-center w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-green-200 gap-2">
                                    <i class="fab fa-whatsapp text-xl"></i> Reservasi via WhatsApp
                                </a>
                                <?php endif; ?>
                                
                                <button class="flex items-center justify-center w-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold py-3.5 rounded-xl transition gap-2">
                                    <i class="fas fa-share-alt"></i> Bagikan Wisata
                                </button>
                            </div>
                        </div>
                        
                        <!-- Footer Card -->
                        <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 text-center">
                            <p class="text-xs text-gray-500">
                                <i class="fas fa-shield-alt text-gray-400 mr-1"></i> Informasi diverifikasi oleh Admin Desa
                            </p>
                        </div>
                    </div>

                    <!-- Card Bantuan -->
                    <div class="hidden lg:block bg-blue-50 rounded-2xl p-6 border border-blue-100 text-center">
                        <p class="text-sm text-blue-800 font-medium mb-1">Butuh bantuan perjalanan?</p>
                        <a href="<?php echo home_url('/kontak'); ?>" class="text-xs font-bold text-blue-600 hover:underline">Hubungi Pusat Informasi Desa &rarr;</a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- === RELATED POSTS (Desain Card Sadesa) === -->
<div class="bg-white py-12 border-t border-gray-100 mb-16 md:mb-0">
    <div class="container mx-auto px-4">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">Wisata Serupa Lainnya</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <?php
            $related = new WP_Query(array(
                'post_type' => 'dw_wisata',
                'posts_per_page' => 4,
                'post__not_in' => array($post_id),
                'orderby' => 'rand'
            ));

            if ($related->have_posts()) :
                while ($related->have_posts()) : $related->the_post();
                    // Ambil Data untuk Card Style
                    $r_img = get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: 'https://via.placeholder.com/400x300';
                    $r_loc = get_post_meta(get_the_ID(), 'dw_lokasi', true);
                    $r_price = get_post_meta(get_the_ID(), 'dw_harga_tiket', true);
                    $r_price_display = ($r_price > 0) ? 'Rp ' . number_format($r_price, 0, ',', '.') : 'Gratis';
                    
                    // Ambil kategori label
                    $r_terms = get_the_terms(get_the_ID(), 'dw_kategori_wisata');
                    $r_cat = (!empty($r_terms) && !is_wp_error($r_terms)) ? $r_terms[0]->name : 'Wisata';
            ?>
            <!-- CARD SADESA STYLE -->
            <div class="card-sadesa group">
                <div class="card-img-wrap">
                    <img src="<?php echo esc_url($r_img); ?>" alt="<?php the_title(); ?>" loading="lazy">
                    <div class="badge-rating"><i class="fas fa-star text-yellow-400"></i> 4.5</div>
                    <div class="badge-category"><?php echo esc_html($r_cat); ?></div>
                </div>
                
                <div class="card-body">
                    <h3 class="card-title group-hover:text-primary transition">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    <div class="card-meta">
                        <i class="fas fa-map-marker-alt text-red-400"></i>
                        <span class="truncate"><?php echo esc_html($r_loc ?: 'Desa Wisata'); ?></span>
                    </div>
                    
                    <div class="card-footer">
                        <div>
                            <p class="price-label">Tiket Masuk</p>
                            <p class="price-tag"><?php echo $r_price_display; ?></p>
                        </div>
                        <a href="<?php the_permalink(); ?>" class="btn-detail">Lihat <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
                wp_reset_postdata();
            endif; 
            ?>
        </div>
    </div>
</div>

<!-- === MOBILE FLOATING ACTION BAR (Hanya di HP) === -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 z-50 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)] pb-safe">
    <div class="flex gap-3 items-center">
        <div class="flex-1">
            <div class="text-xs text-gray-500">Harga Tiket</div>
            <div class="font-bold text-primary text-lg leading-tight">
                <?php echo ($harga_tiket > 0) ? 'Rp ' . number_format($harga_tiket, 0, ',', '.') : 'Gratis'; ?>
            </div>
        </div>
        <div class="flex-1">
            <?php if ($kontak) : ?>
            <a href="<?php echo esc_url($wa_link); ?>" class="flex items-center justify-center w-full bg-green-600 text-white font-bold py-3 rounded-xl shadow-md text-sm">
                Hubungi Admin
            </a>
            <?php else: ?>
            <button disabled class="w-full bg-gray-300 text-white font-bold py-3 rounded-xl text-sm">Info Kontak N/A</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Spacer untuk floating bar di mobile agar konten tidak tertutup -->
<div class="h-20 md:hidden"></div>

<?php endwhile; ?>

<?php get_footer(); ?>