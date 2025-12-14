<?php
/**
 * Template Name: Halaman Wisata (Sadesa Style)
 */

get_header(); 

// --- 1. LOGIKA FILTER & QUERY ---

// Ambil parameter dari URL
$search_query   = isset($_GET['keyword']) ? sanitize_text_field($_GET['keyword']) : '';
$kategori_slug  = isset($_GET['kategori']) ? sanitize_text_field($_GET['kategori']) : '';
$urutkan        = isset($_GET['urutkan']) ? sanitize_text_field($_GET['urutkan']) : 'terbaru';
$paged          = (get_query_var('paged')) ? get_query_var('paged') : 1;

// Setup Query Arguments
$args = array(
    'post_type'      => 'dw_wisata',
    'posts_per_page' => 9,
    'paged'          => $paged,
    'post_status'    => 'publish',
    'tax_query'      => array(),
    'meta_query'     => array(),
);

// Filter: Pencarian
if (!empty($search_query)) {
    $args['s'] = $search_query;
}

// Filter: Kategori
if (!empty($kategori_slug)) {
    $args['tax_query'][] = array(
        'taxonomy' => 'dw_kategori_wisata',
        'field'    => 'slug',
        'terms'    => $kategori_slug,
    );
}

// Filter: Pengurutan
switch ($urutkan) {
    case 'termurah':
        $args['meta_key'] = 'dw_harga_tiket';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'ASC';
        break;
    case 'termahal':
        $args['meta_key'] = 'dw_harga_tiket';
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        break;
    default: // terbaru
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
        break;
}

$wisata_query = new WP_Query($args);

// Helper function untuk mengambil kategori pertama (sama seperti di front-page)
if (!function_exists('get_first_category_label')) {
    function get_first_category_label($post_id) {
        $terms = get_the_terms($post_id, 'dw_kategori_wisata');
        return (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : 'Wisata';
    }
}
?>

<div class="bg-gray-50 min-h-screen">
    
    <!-- PAGE HEADER -->
    <div class="bg-white border-b border-gray-200 pt-10 pb-8 px-4">
        <div class="container mx-auto text-center max-w-3xl">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-3">Jelajahi Desa Wisata</h1>
            <p class="text-gray-500 text-lg">Temukan destinasi alam, budaya, dan pengalaman tak terlupakan.</p>
        </div>
    </div>

    <!-- FILTER SECTION (Sticky) -->
    <div class="sticky top-16 md:top-20 z-30 bg-white/90 backdrop-blur-md shadow-sm border-b border-gray-100 transition-all">
        <div class="container mx-auto px-4 py-4">
            <form action="<?php echo home_url('/wisata'); // Sesuaikan slug halaman jika perlu ?>" method="GET" class="flex flex-col md:flex-row gap-3 items-center justify-center">
                
                <!-- Input Pencarian -->
                <div class="relative w-full md:w-1/3">
                    <i class="fas fa-search absolute left-4 top-3.5 text-gray-400"></i>
                    <input type="text" name="keyword" value="<?php echo esc_attr($search_query); ?>" placeholder="Cari nama wisata..." class="w-full pl-10 pr-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white transition text-sm">
                </div>

                <!-- Dropdown Kategori -->
                <div class="relative w-full md:w-1/4">
                    <i class="fas fa-filter absolute left-4 top-3.5 text-gray-400"></i>
                    <select name="kategori" class="w-full pl-10 pr-8 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white transition text-sm appearance-none cursor-pointer">
                        <option value="">Semua Kategori</option>
                        <?php
                        $terms = get_terms(array('taxonomy' => 'dw_kategori_wisata', 'hide_empty' => true));
                        if (!is_wp_error($terms) && !empty($terms)) {
                            foreach ($terms as $term) {
                                $selected = ($kategori_slug == $term->slug) ? 'selected' : '';
                                echo '<option value="' . esc_attr($term->slug) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-4 text-xs text-gray-400 pointer-events-none"></i>
                </div>

                <!-- Dropdown Sort -->
                <div class="relative w-full md:w-1/5">
                    <i class="fas fa-sort-amount-down absolute left-4 top-3.5 text-gray-400"></i>
                    <select name="urutkan" class="w-full pl-10 pr-8 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-primary focus:bg-white transition text-sm appearance-none cursor-pointer">
                        <option value="terbaru" <?php selected($urutkan, 'terbaru'); ?>>Terbaru</option>
                        <option value="termurah" <?php selected($urutkan, 'termurah'); ?>>Harga Terendah</option>
                        <option value="termahal" <?php selected($urutkan, 'termahal'); ?>>Harga Tertinggi</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-4 text-xs text-gray-400 pointer-events-none"></i>
                </div>

                <!-- Tombol Submit -->
                <button type="submit" class="w-full md:w-auto px-6 py-3 bg-primary text-white font-bold rounded-xl hover:bg-green-700 transition shadow-md flex items-center justify-center gap-2">
                    Cari
                </button>
            </form>
        </div>
    </div>

    <!-- MAIN CONTENT GRID -->
    <div class="container mx-auto px-4 py-8">
        
        <?php if ($wisata_query->have_posts()) : ?>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php while ($wisata_query->have_posts()) : $wisata_query->the_post(); 
                    // Ambil Meta Data
                    $lokasi = get_post_meta(get_the_ID(), 'dw_lokasi', true);
                    $harga  = get_post_meta(get_the_ID(), 'dw_harga_tiket', true);
                    
                    // Format Data untuk Tampilan
                    $img_src = get_the_post_thumbnail_url(get_the_ID(), 'medium_large') ?: 'https://via.placeholder.com/400x300?text=Wisata';
                    $price_display = ($harga > 0) ? 'Rp ' . number_format($harga, 0, ',', '.') : 'Gratis';
                    $kategori_label = get_first_category_label(get_the_ID());
                ?>
                
                <!-- CARD SADESA STYLE (Sama persis dengan Front Page) -->
                <div class="card-sadesa group">
                    <div class="card-img-wrap">
                        <img src="<?php echo esc_url($img_src); ?>" alt="<?php the_title(); ?>" loading="lazy">
                        
                        <!-- Badge Rating (Dummy/Placeholder) -->
                        <div class="badge-rating"><i class="fas fa-star text-yellow-400"></i> 4.8</div>
                        
                        <!-- Badge Category -->
                        <div class="badge-category"><?php echo esc_html($kategori_label); ?></div>
                    </div>
                    
                    <div class="card-body">
                        <h3 class="card-title group-hover:text-primary transition">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        <div class="card-meta">
                            <i class="fas fa-map-marker-alt text-red-400"></i>
                            <span class="truncate"><?php echo esc_html($lokasi ?: 'Desa Wisata'); ?></span>
                        </div>
                        
                        <div class="card-footer">
                            <div>
                                <p class="price-label">Tiket Masuk</p>
                                <p class="price-tag"><?php echo $price_display; ?></p>
                            </div>
                            <a href="<?php the_permalink(); ?>" class="btn-detail">
                                Lihat Detail <i class="fas fa-arrow-right ml-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- END CARD -->

                <?php endwhile; ?>
            </div>

            <!-- PAGINATION -->
            <div class="mt-12 flex justify-center">
                <?php
                $pagination_args = array(
                    'base'         => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format'       => '?paged=%#%',
                    'current'      => max(1, get_query_var('paged')),
                    'total'        => $wisata_query->max_num_pages,
                    'prev_text'    => '<i class="fas fa-chevron-left"></i>',
                    'next_text'    => '<i class="fas fa-chevron-right"></i>',
                    'type'         => 'array',
                );

                $pages = paginate_links($pagination_args);

                if (is_array($pages)) {
                    echo '<ul class="flex gap-2 items-center bg-white p-2 rounded-xl shadow-sm border border-gray-100">';
                    foreach ($pages as $page) {
                        // Styling Pagination agar sesuai tema Tailwind
                        $page = str_replace('page-numbers', 'flex items-center justify-center w-10 h-10 rounded-lg text-sm font-bold transition hover:bg-gray-100 text-gray-600', $page);
                        $page = str_replace('current', 'bg-primary text-white hover:bg-green-700 hover:text-white', $page);
                        echo '<li>' . $page . '</li>';
                    }
                    echo '</ul>';
                }
                ?>
            </div>

        <?php else : ?>

            <!-- EMPTY STATE -->
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-300">
                    <i class="fas fa-search text-4xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Wisata Tidak Ditemukan</h3>
                <p class="text-gray-500 max-w-md mx-auto">Maaf, kami tidak dapat menemukan wisata yang cocok dengan pencarian atau filter Anda. Coba kata kunci lain atau reset filter.</p>
                <a href="<?php echo home_url('/wisata'); ?>" class="mt-6 px-6 py-2 bg-white border border-gray-300 rounded-lg text-gray-600 font-bold hover:bg-gray-50 transition">
                    Reset Filter
                </a>
            </div>

        <?php endif; wp_reset_postdata(); ?>

    </div>
</div>

<?php get_footer(); ?>