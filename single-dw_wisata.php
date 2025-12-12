<?php
/**
 * Template Name: Single Wisata
 * Description: Menampilkan detail lengkap destinasi wisata.
 */

get_header(); ?>

<?php while ( have_posts() ) : the_post(); 
    $post_id = get_the_ID();
    
    // Meta Data dari Plugin
    $alamat = get_post_meta($post_id, '_dw_alamat', true);
    $harga_tiket = get_post_meta($post_id, '_dw_harga_tiket', true);
    $jam_buka = get_post_meta($post_id, '_dw_jam_buka', true);
    $kontak = get_post_meta($post_id, '_dw_kontak', true);
    $fasilitas = get_post_meta($post_id, '_dw_fasilitas', true); // Array
    $maps_url = get_post_meta($post_id, '_dw_url_google_maps', true);
    $gallery_ids = get_post_meta($post_id, '_dw_galeri_foto', true);
    $video_url = get_post_meta($post_id, '_dw_video_url', true);
    
    $thumb_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'full') : 'https://via.placeholder.com/1200x600';
?>

<!-- Hero Image -->
<div class="relative h-[400px] md:h-[500px] w-full bg-gray-900">
    <img src="<?php echo esc_url($thumb_url); ?>" class="w-full h-full object-cover opacity-60">
    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
    <div class="absolute bottom-0 left-0 right-0 p-6 md:p-12 container mx-auto">
        <span class="bg-primary text-white text-xs font-bold px-3 py-1 rounded-full mb-4 inline-block">
            <?php echo get_the_term_list($post_id, 'kategori_wisata', '', ', ', ''); ?>
        </span>
        <h1 class="text-3xl md:text-5xl font-bold text-white mb-2"><?php the_title(); ?></h1>
        <?php if($alamat): ?>
            <p class="text-gray-300 flex items-center gap-2 text-sm md:text-base">
                <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($alamat); ?>
            </p>
        <?php endif; ?>
    </div>
</div>

<div class="bg-white py-12 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            
            <!-- Konten Utama -->
            <div class="lg:col-span-2">
                
                <!-- Deskripsi -->
                <div class="mb-10">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Tentang Destinasi</h2>
                    <div class="prose max-w-none text-gray-600 leading-relaxed">
                        <?php the_content(); ?>
                    </div>
                </div>

                <!-- Galeri Foto -->
                <?php if ( ! empty( $gallery_ids ) ) : $ids = explode(',', $gallery_ids); ?>
                <div class="mb-10">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Galeri Foto</h2>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php foreach($ids as $img_id): $img_url = wp_get_attachment_image_url($img_id, 'large'); ?>
                            <a href="<?php echo esc_url($img_url); ?>" class="block h-40 rounded-lg overflow-hidden hover:opacity-90 transition bg-gray-100">
                                <img src="<?php echo esc_url($img_url); ?>" class="w-full h-full object-cover">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Fasilitas -->
                <?php if ( ! empty( $fasilitas ) && is_array($fasilitas) ) : ?>
                <div class="mb-10">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Fasilitas Tersedia</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach($fasilitas as $fas): ?>
                            <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span class="text-gray-700 font-medium"><?php echo esc_html($fas); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div>

            <!-- Sidebar Info -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 space-y-6">
                    
                    <!-- Info Box -->
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                        <h3 class="text-xl font-bold text-gray-800 mb-6 border-b pb-4">Informasi Kunjungan</h3>
                        
                        <div class="space-y-5">
                            <div>
                                <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Harga Tiket</span>
                                <div class="flex items-center gap-3 text-gray-700">
                                    <i class="fas fa-ticket-alt text-primary w-6"></i>
                                    <span class="font-bold text-lg"><?php echo $harga_tiket ? esc_html($harga_tiket) : 'Gratis'; ?></span>
                                </div>
                            </div>

                            <div>
                                <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Jam Operasional</span>
                                <div class="flex items-center gap-3 text-gray-700">
                                    <i class="far fa-clock text-primary w-6"></i>
                                    <span><?php echo $jam_buka ? esc_html($jam_buka) : 'Setiap Hari'; ?></span>
                                </div>
                            </div>

                            <?php if($kontak): ?>
                            <div>
                                <span class="block text-xs text-gray-400 uppercase tracking-wider mb-1">Kontak Informasi</span>
                                <div class="flex items-center gap-3 text-gray-700">
                                    <i class="fas fa-phone-alt text-primary w-6"></i>
                                    <span><?php echo esc_html($kontak); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if($maps_url): ?>
                        <div class="mt-8">
                            <a href="<?php echo esc_url($maps_url); ?>" target="_blank" class="block w-full bg-primary text-white text-center font-bold py-3 rounded-lg hover:bg-secondary transition shadow-md hover:shadow-lg">
                                <i class="fas fa-location-arrow mr-2"></i> Petunjuk Arah
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Share -->
                    <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 text-center">
                        <p class="text-sm text-gray-500 mb-4">Bagikan destinasi ini</p>
                        <div class="flex justify-center gap-4">
                            <button class="w-10 h-10 rounded-full bg-blue-600 text-white hover:opacity-90"><i class="fab fa-facebook-f"></i></button>
                            <button class="w-10 h-10 rounded-full bg-green-500 text-white hover:opacity-90"><i class="fab fa-whatsapp"></i></button>
                            <button class="w-10 h-10 rounded-full bg-sky-500 text-white hover:opacity-90"><i class="fab fa-twitter"></i></button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>