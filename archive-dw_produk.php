<?php
/**
 * Template Name: Archive Produk UMKM
 * Description: Halaman daftar produk dinamis dengan filter kategori dan pencarian.
 */

get_header();

// === 1. LOGIC FILTER & SEARCH ===
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$kategori_slug = isset($_GET['kategori']) ? sanitize_text_field($_GET['kategori']) : '';

// Argument Query Dasar
$args = array(
    'post_type'      => 'dw_produk', // Pastikan slug post type sesuai
    'posts_per_page' => 9,
    'paged'          => $paged,
    's'              => $search_query, // Fitur search native WP
);

// Tambahan Query Tax (Kategori)
if (!empty($kategori_slug) && $kategori_slug !== 'semua') {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'dw_kategori_produk', // Pastikan slug taxonomy sesuai
            'field'    => 'slug',
            'terms'    => $kategori_slug,
        ),
    );
}

$produk_query = new WP_Query($args);
?>

<!-- =================================================================================
     HEADER SECTION (Hero Kecil)
     ================================================================================= -->
<div class="bg-green-600 py-10 text-white relative overflow-hidden">
    <!-- Pattern Overlay (Optional) -->
    <div class="absolute inset-0 opacity-10 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]"></div>
    
    <div class="container mx-auto px-4 relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl md:text-4xl font-bold mb-2">Produk UMKM</h1>
            <p class="text-green-100 text-lg">Oleh-oleh autentik langsung dari desa</p>
        </div>
        
        <!-- Search Bar -->
        <div class="w-full md:w-1/3">
            <form action="" method="GET" class="relative">
                <input type="hidden" name="kategori" value="<?php echo esc_attr($kategori_slug); ?>">
                <input type="text" name="s" value="<?php echo esc_attr($search_query); ?>" 
                       placeholder="Cari produk..." 
                       class="w-full py-3 pl-10 pr-4 rounded-full text-gray-700 focus:outline-none focus:ring-2 focus:ring-green-300 shadow-lg">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </form>
        </div>
    </div>
</div>

<!-- =================================================================================
     FILTER & LISTING SECTION
     ================================================================================= -->
<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4">

        <!-- FILTER KATEGORI (Scrollable on mobile) -->
        <div class="mb-8 overflow-x-auto pb-2">
            <div class="flex gap-3 whitespace-nowrap">
                <?php
                // Helper function untuk class tombol
                function get_filter_class($is_active) {
                    return $is_active 
                        ? 'bg-green-500 text-white shadow-md transform scale-105' 
                        : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200';
                }

                // Tombol "Semua"
                $all_active = empty($kategori_slug) || $kategori_slug === 'semua';
                ?>
                <a href="?kategori=semua&s=<?php echo urlencode($search_query); ?>" 
                   class="px-5 py-2 rounded-full text-sm font-bold transition-all duration-300 <?php echo get_filter_class($all_active); ?>">
                   Semua
                </a>

                <?php
                // Loop Kategori dari Database
                $terms = get_terms(array(
                    'taxonomy'   => 'dw_kategori_produk',
                    'hide_empty' => true,
                ));

                if (!empty($terms) && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        $is_active = ($kategori_slug === $term->slug);
                        ?>
                        <a href="?kategori=<?php echo esc_attr($term->slug); ?>&s=<?php echo urlencode($search_query); ?>" 
                           class="px-5 py-2 rounded-full text-sm font-bold transition-all duration-300 <?php echo get_filter_class($is_active); ?>">
                           <?php echo esc_html($term->name); ?>
                        </a>
                        <?php
                    }
                }
                ?>
            </div>
        </div>

        <!-- PRODUCT GRID -->
        <?php if ($produk_query->have_posts()) : ?>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php 
                while ($produk_query->have_posts()) : $produk_query->the_post(); 
                    
                    // Meta Data
                    $harga = get_post_meta(get_the_ID(), 'dw_harga_produk', true) ?: 0;
                    $lokasi_produk = get_post_meta(get_the_ID(), 'dw_lokasi_produk', true) ?: 'Desa Wisata';
                    $kontak = get_post_meta(get_the_ID(), 'dw_kontak_produk', true);
                    
                    // Gambar
                    $img_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: 'https://via.placeholder.com/400x300?text=No+Image';
                    
                    // Badge Kategori
                    $cat_terms = get_the_terms(get_the_ID(), 'dw_kategori_produk');
                    $cat_name = !empty($cat_terms) ? $cat_terms[0]->name : 'Umum';
                ?>
                
                <!-- Card Produk -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 group overflow-hidden border border-gray-100 flex flex-col h-full">
                    
                    <!-- Thumbnail -->
                    <div class="relative aspect-[4/3] overflow-hidden">
                        <img src="<?php echo esc_url($img_url); ?>" alt="<?php the_title(); ?>" 
                             class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                        <span class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm text-green-700 text-xs font-bold px-3 py-1 rounded-full shadow-sm">
                            <?php echo esc_html($cat_name); ?>
                        </span>
                    </div>

                    <!-- Content -->
                    <div class="p-5 flex flex-col flex-grow">
                        <div class="flex-grow">
                            <h3 class="text-lg font-bold text-gray-800 mb-1 group-hover:text-green-600 transition line-clamp-2">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>
                            <p class="text-xs text-gray-500 mb-3 flex items-center gap-1">
                                <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($lokasi_produk); ?>
                            </p>
                        </div>

                        <!-- Price & Action -->
                        <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                            <div>
                                <p class="text-xs text-gray-400">Harga</p>
                                <p class="text-lg font-bold text-green-600">
                                    <?php echo ($harga > 0) ? 'Rp ' . number_format($harga, 0, ',', '.') : 'Hubungi Kami'; ?>
                                </p>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="w-10 h-10 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 hover:bg-green-500 hover:text-white transition shadow-sm" title="Lihat Detail">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                </div>
                <?php endwhile; ?>
            </div>

            <!-- PAGINATION -->
            <div class="mt-12 flex justify-center">
                <?php
                echo paginate_links(array(
                    'total' => $produk_query->max_num_pages,
                    'current' => $paged,
                    'prev_text' => '<i class="fas fa-chevron-left"></i>',
                    'next_text' => '<i class="fas fa-chevron-right"></i>',
                    'type' => 'list',
                    'mid_size' => 2,
                ));
                ?>
            </div>
            
            <!-- Styling Pagination (Bisa dipindah ke CSS) -->
            <style>
                .page-numbers { display: flex; gap: 0.5rem; }
                .page-numbers li a, .page-numbers li span {
                    display: flex; align-items: center; justify-content: center;
                    width: 40px; height: 40px; border-radius: 50%;
                    background: white; border: 1px solid #eee; color: #555;
                    font-weight: bold; transition: 0.3s;
                }
                .page-numbers li a:hover, .page-numbers li span.current {
                    background: #16a34a; color: white; border-color: #16a34a;
                }
            </style>

        <?php else : ?>

            <!-- EMPTY STATE -->
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                    <i class="fas fa-box-open text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Produk Tidak Ditemukan</h3>
                <p class="text-gray-500 max-w-md mx-auto mb-6">
                    Maaf, kami tidak dapat menemukan produk dengan kata kunci 
                    "<strong><?php echo esc_html($search_query); ?></strong>" 
                    <?php if($kategori_slug) echo 'di kategori ini'; ?>.
                </p>
                <a href="?kategori=semua" class="text-green-600 font-bold hover:underline">
                    Reset Filter
                </a>
            </div>

        <?php endif; wp_reset_postdata(); ?>

    </div>
</div>

<?php get_footer(); ?>