<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 */

get_header(); ?>

<!-- Default Page Header -->
<div class="bg-gray-50 border-b border-gray-200">
    <div class="container mx-auto px-4 py-12 text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2"><?php the_title(); ?></h1>
        
        <!-- Breadcrumb Simple -->
        <nav class="flex justify-center text-sm text-gray-500 mt-4">
            <a href="<?php echo home_url(); ?>" class="hover:text-primary">Beranda</a>
            <span class="mx-2">/</span>
            <span class="text-gray-800"><?php the_title(); ?></span>
        </nav>
    </div>
</div>

<div class="py-12 bg-white min-h-[50vh]">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <?php
            while ( have_posts() ) : the_post();
                ?>
                <!-- Featured Image (Optional) -->
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="mb-10 rounded-xl overflow-hidden shadow-lg">
                        <?php the_post_thumbnail('full', ['class' => 'w-full h-auto object-cover']); ?>
                    </div>
                <?php endif; ?>

                <!-- Page Content -->
                <div class="prose prose-lg max-w-none text-gray-600">
                    <?php the_content(); ?>
                </div>
                <?php
            endwhile; 
            ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>