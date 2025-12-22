<?php get_header(); ?>

<!-- Hero Section -->
<section class="relative bg-gray-900 text-white py-24 md:py-32">
    <!-- Background Image (Gunakan Featured Image Page ini jika ada, atau fallback) -->
    <?php 
        $bg_img = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'full') : 'https://via.placeholder.com/1920x800'; 
    ?>
    <div class="absolute inset-0 overflow-hidden">
        <img src="<?php echo $bg_img; ?>" alt="Desa Wisata" class="w-full h-full object-cover opacity-50">
    </div>
    
    <div class="relative container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold mb-4 leading-tight">
            Selamat Datang di <span class="text-primary"><?php bloginfo('name'); ?></span>
        </h1>
        <p class="text-lg md:text-xl text-gray-200 mb-8 max-w-2xl mx-auto">
            Jelajahi keindahan alam, budaya, dan produk lokal terbaik dari desa kami.
        </p>
        
        <!-- Search Box -->
        <form action="<?php echo home_url('/'); ?>" method="get" class="max-w-xl mx-auto flex bg-white rounded-full overflow-hidden shadow-lg p-1">
            <input type="text" name="s" placeholder="Cari wisata atau produk..." class="flex-grow px-6 py-3 text-gray-700 focus:outline-none border-none">
            <button type="submit" class="bg-primary text-white px-8 py-3 rounded-full font-semibold hover:bg-green-700 transition">
                Cari
            </button>
        </form>
    </div>
</section>

<!-- Section Wisata Unggulan -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Destinasi Wisata</h2>
            <p class="text-gray-500">Tempat terbaik untuk dikunjungi di desa kami</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php 
            $args_wisata = array(
                'post_type' => 'dw_wisata',
                'posts_per_page' => 3,
                'orderby' => 'date',
                'order' => 'DESC'
            );
            $query_wisata = new WP_Query($args_wisata);
            
            if ($query_wisata->have_posts()) :
                while ($query_wisata->have_posts()) : $query_wisata->the_post();
                    get_template_part('template-parts/card', 'wisata');
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p class="col-span-3 text-center text-gray-500">Belum ada data wisata.</p>';
            endif; 
            ?>
        </div>
        
        <div class="text-center mt-10">
            <a href="<?php echo home_url('/wisata'); ?>" class="inline-block border border-primary text-primary px-6 py-2 rounded-lg font-semibold hover:bg-primary hover:text-white transition">
                Lihat Semua Wisata
            </a>
        </div>
    </div>
</section>

<!-- Section Produk Desa (Marketplace) -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Produk Lokal</h2>
            <p class="text-gray-500">Oleh-oleh dan kerajinan tangan asli warga desa</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php 
            $args_produk = array(
                'post_type' => 'dw_produk',
                'posts_per_page' => 8,
                'orderby' => 'date',
                'order' => 'DESC'
            );
            $query_produk = new WP_Query($args_produk);
            
            if ($query_produk->have_posts()) :
                while ($query_produk->have_posts()) : $query_produk->the_post();
                    get_template_part('template-parts/card', 'produk');
                endwhile;
                wp_reset_postdata();
            else :
                echo '<p class="col-span-4 text-center text-gray-500">Belum ada produk.</p>';
            endif; 
            ?>
        </div>

        <div class="text-center mt-10">
            <a href="<?php echo home_url('/produk'); ?>" class="inline-block bg-secondary text-white px-6 py-3 rounded-lg font-semibold hover:bg-yellow-700 transition shadow-md">
                Belanja Sekarang
            </a>
        </div>
    </div>
</section>

<!-- Section Call to Action (Mitra) -->
<section class="py-16 bg-primary text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-4">Ingin Menjadi Mitra Desa?</h2>
        <p class="mb-8 text-green-100 max-w-2xl mx-auto">
            Daftarkan produk atau jasa wisata Anda sekarang. Kami membantu mempromosikan potensi lokal ke dunia luar.
        </p>
        <div class="flex justify-center space-x-4">
            <a href="<?php echo home_url('/register'); ?>" class="bg-white text-primary px-6 py-3 rounded-lg font-bold hover:bg-gray-100 transition shadow-lg">
                Daftar Sekarang
            </a>
            <a href="<?php echo home_url('/tentang'); ?>" class="border border-white text-white px-6 py-3 rounded-lg font-bold hover:bg-white hover:text-primary transition">
                Pelajari Lebih Lanjut
            </a>
        </div>
    </div>
</section>

<?php get_footer(); ?>