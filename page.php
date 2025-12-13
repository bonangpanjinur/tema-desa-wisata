<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>

<div class="bg-white min-h-screen pb-10">
    <!-- Page Header -->
    <div class="px-5 py-6 border-b border-gray-100">
        <h1 class="text-2xl font-bold text-gray-800"><?php the_title(); ?></h1>
    </div>

    <!-- Page Content -->
    <div class="px-5 py-6">
        <div class="prose prose-emerald text-gray-600 max-w-none">
            <?php the_content(); ?>
        </div>
    </div>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>