<?php
/**
 * Template Name: Archive Produk UMKM
 * Description: Halaman daftar produk dinamis dengan filter kategori dan pencarian.
 */

get_header();

// === 1. LOGIC FILTER & SEARCH ===
// Mengamankan input paged agar selalu integer
$paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;
$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$kategori_slug = isset($_GET['kategori']) ? sanitize_text_field($_GET['kategori']) : '';

// Argument Query Dasar
$args = array(
    'post_type'      => 'dw_produk', // Pastikan slug post type sesuai
    'posts_per_page' => 12, // Menampilkan 12 produk agar grid rapi (3x4 atau 4x3)
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
            <form action="<?php echo esc_url(get_post_type_archive_link('dw_produk')); ?>" method="GET" class="relative">
                <!-- Pertahankan kategori saat mencari -->
                <?php if($kategori_slug): ?>
                    <input type="hidden" name="kategori" value="<?php echo esc_attr($kategori_slug); ?>">
                <?php endif; ?>
                
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
                // Tombol "Semua"
                $all_active = empty($kategori_slug) || $kategori_slug === 'semua';
                $base_class = "px-5 py-2 rounded-full text-sm font-bold transition-all duration-300 ";
                $active_class = "bg-green-500 text-white shadow-md transform scale-105";
                $inactive_class = "bg-white text-gray-600 hover:bg-gray-100 border border-gray-200";
                
                $link_semua = add_query_arg(array('kategori' => 'semua', 's' => $search_query), get_post_type_archive_link('dw_produk'));
                ?>
                <a href="<?php echo esc_url($link_semua); ?>" 
                   class="<?php echo $base_class . ($all_active ? $active_class : $inactive_class); ?>">
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
                        $link_term = add_query_arg(array('kategori' => $term->slug, 's' => $search_query), get_post_type_archive_link('dw_produk'));
                        ?>
                        <a href="<?php echo esc_url($link_term); ?>" 
                           class="<?php echo $base_class . ($is_active ? $active_class : $inactive_class); ?>">
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
                    
                    // Gambar dengan Fallback
                    $img_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                    if (!$img_url) {
                        $img_url = 'https://via.placeholder.com/400x300?text=Produk+UMKM'; 
                    }
                    
                    // Badge Kategori
                    $cat_terms = get_the_terms(get_the_ID(), 'dw_kategori_produk');
                    $cat_name = !empty($cat_terms) && !is_wp_error($cat_terms) ? $cat_terms[0]->name : 'Umum';
                ?>
                
                <!-- Card Produk -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 group overflow-hidden border border-gray-100 flex flex-col h-full">
                    
                    <!-- Thumbnail -->
                    <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
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
                                    <?php echo ($harga > 0) ? 'Rp ' . number_format((float)$harga, 0, ',', '.') : 'Hubungi Kami'; ?>
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

            <!-- PAGINATION FIX -->
            <div class="mt-12 flex justify-center dw-pagination">
                <?php
                // Pastikan max_num_pages valid dan minimal 1
                $total_pages = $produk_query->max_num_pages;
                
                if ($total_pages > 1) {
                    echo paginate_links(array(
                        'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'format'    => '?paged=%#%',
                        'current'   => max(1, $paged),
                        'total'     => $total_pages,
                        'prev_text' => '<span class="px-3 py-2 bg-white border rounded-full shadow-sm hover:bg-green-50"><i class="fas fa-chevron-left"></i></span>',
                        'next_text' => '<span class="px-3 py-2 bg-white border rounded-full shadow-sm hover:bg-green-50"><i class="fas fa-chevron-right"></i></span>',
                        'type'      => 'plain', // Diubah ke plain agar lebih mudah di-style container-nya
                        'mid_size'  => 2,
                    ));
                }
                ?>
            </div>
            
            <style>
                .dw-pagination .page-numbers {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 40px;
                    height: 40px;
                    margin: 0 4px;
                    border-radius: 9999px;
                    font-weight: 600;
                    color: #4b5563;
                    background-color: white;
                    border: 1px solid #e5e7eb;
                    transition: all 0.2s;
                    text-decoration: none;
                }
                .dw-pagination .page-numbers.current, 
                .dw-pagination .page-numbers:hover:not(.dots) {
                    background-color: #16a34a; /* green-600 */
                    color: white;
                    border-color: #16a34a;
                    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                }
                .dw-pagination .page-numbers.dots {
                    border: none;
                    background: transparent;
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
                    Maaf, kami tidak dapat menemukan produk 
                    <?php if($search_query) echo 'dengan kata kunci "<strong>'.esc_html($search_query).'</strong>"'; ?>
                    <?php if($kategori_slug && $kategori_slug !== 'semua') echo ' di kategori ini'; ?>.
                </p>
                <a href="<?php echo esc_url(get_post_type_archive_link('dw_produk')); ?>" class="bg-green-600 text-white px-6 py-2 rounded-full font-bold hover:bg-green-700 transition shadow-lg">
                    Reset Pencarian
                </a>
            </div>

        <?php endif; wp_reset_postdata(); ?>

    </div>
</div>

<?php get_footer(); ?>