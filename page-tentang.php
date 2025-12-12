<?php
/**
 * Template Name: Halaman Tentang
 */

get_header(); ?>

<?php while ( have_posts() ) : the_post(); 
    // Ambil data dinamis dari editor
    $thumb_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'full') : 'https://images.unsplash.com/photo-1533038590840-1cde6e668a91?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80';
    $subtitle = has_excerpt() ? get_the_excerpt() : 'Platform digital yang menghubungkan potensi desa wisata Indonesia dengan dunia.';
?>

<!-- Hero Section (Dinamis dari Featured Image & Title) -->
<div class="relative bg-gray-900 py-24 md:py-32 overflow-hidden">
    <div class="absolute inset-0">
        <img src="<?php echo esc_url($thumb_url); ?>" class="w-full h-full object-cover opacity-30">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>
    </div>
    <div class="container mx-auto px-4 relative z-10 text-center">
        <h1 class="text-4xl md:text-6xl font-bold text-white mb-6 leading-tight"><?php the_title(); ?></h1>
        <p class="text-xl md:text-2xl text-gray-200 max-w-3xl mx-auto font-light"><?php echo esc_html($subtitle); ?></p>
    </div>
</div>

<!-- Main Content (Diedit dari Editor WordPress) -->
<div class="py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- 
            Tips untuk Admin:
            Gunakan Block Editor (Gutenberg) untuk membuat layout kolom (Misi, Tim, dll).
            Gunakan class Tailwind di bagian "Advanced > Additional CSS Class" jika perlu styling khusus,
            atau cukup gunakan block bawaan WordPress (Columns, Image, Heading).
        -->
        <div class="prose prose-lg max-w-none text-gray-600">
            <?php the_content(); ?>
        </div>
    </div>
</div>

<!-- Section Kontak (Hardcoded sebagai Call to Action tetap) -->
<div class="py-16 bg-gray-50 border-t border-gray-200">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold text-gray-800 mb-6">Ingin Berkolaborasi?</h2>
        <p class="text-gray-600 mb-8 max-w-xl mx-auto text-lg">Apakah Anda pengelola desa wisata atau ingin menjadi mitra kami? Jangan ragu untuk menghubungi tim kami.</p>
        <a href="<?php echo home_url('/kontak'); ?>" class="inline-flex items-center gap-2 bg-primary text-white px-8 py-4 rounded-full hover:bg-secondary transition font-bold shadow-lg transform hover:-translate-y-1">
            <i class="fas fa-envelope"></i> Hubungi Kami
        </a>
    </div>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>