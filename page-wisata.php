<?php
/* Template Name: Halaman Jelajah Wisata */
get_header(); 
?>

<!-- Header Title & Filter -->
<div class="glass sticky top-0 z-30 px-5 py-4 border-b border-gray-100 bg-white/90 backdrop-blur-md">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-bold text-gray-800">Destinasi Wisata</h1>
        <!-- Map Toggle Button (Visual Only) -->
        <button class="w-9 h-9 bg-gray-50 rounded-full flex items-center justify-center text-gray-600 hover:bg-gray-100 hover:text-primary transition-colors">
            <i class="ph-fill ph-map-trifold text-lg"></i>
        </button>
    </div>

    <!-- Search Bar Specific for Tourism -->
    <form action="" method="get" class="relative group">
        <i class="ph ph-magnifying-glass absolute left-4 top-3.5 text-gray-400 text-lg group-focus-within:text-primary transition-colors"></i>
        <input type="text" name="s" value="<?php echo get_search_query(); ?>"
            class="w-full pl-11 pr-4 py-3 bg-gray-50 border-none rounded-2xl text-sm focus:bg-white focus:ring-1 focus:ring-primary/20 focus:shadow-soft transition-all placeholder-gray-400"
            placeholder="Cari tempat wisata...">
    </form>
</div>

<div class="p-5 pb-32 min-h-screen">

    <!-- Filter Categories (Specific to Wisata) -->
    <div class="flex gap-2 overflow-x-auto no-scrollbar mb-6 pb-1">
        <?php 
        $current_cat = isset($_GET['kategori']) ? sanitize_text_field($_GET['kategori']) : ''; 
        
        // Kategori Hardcoded atau ambil dari get_terms('kategori_wisata')
        $cats = [
            '' => 'Semua',
            'alam' => 'Alam',
            'budaya' => 'Budaya',
            'edukasi' => 'Edukasi',
            'air' => 'Wisata Air'
        ];
        ?>
        <?php foreach ($cats as $slug => $label) : ?>
            <a href="?kategori=<?php echo $slug; ?>" 
               class="px-4 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all border <?php echo ($current_cat == $slug) ? 'bg-primary text-white border-primary shadow-lg shadow-primary/20' : 'bg-white text-gray-500 border-gray-100 hover:border-gray-300'; ?>">
               <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Wisata List -->
    <div class="flex flex-col gap-5">
        <?php
        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
        $search_query = get_search_query();
        
        $args = array(
            'post_type'      => 'dw_wisata', // KHUSUS WISATA
            'posts_per_page' => 10,
            'paged'          => $paged,
            's'              => $search_query,
            'status'         => 'publish',
            'orderby'        => 'menu_order title',
            'order'          => 'ASC'
        );

        // Filter by Taxonomy (Kategori Wisata)
        if (!empty($current_cat)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'kategori_wisata', 
                    'field'    => 'slug',
                    'terms'    => $current_cat,
                ),
            );
        }

        $query = new WP_Query($args);

        if ($query->have_posts()) :
            while ($query->have_posts()) : $query->the_post();
                // Ambil Meta Data Plugin dengan pengecekan aman
                $lokasi = get_post_meta(get_the_ID(), '_dw_lokasi', true) ?: 'Lokasi belum diisi';
                $rating = get_post_meta(get_the_ID(), '_dw_rating', true) ?: '4.5';
                $tiket  = get_post_meta(get_the_ID(), '_dw_harga_tiket', true);
                
                // Gunakan fungsi helper dw_format_price jika tersedia, jika tidak pakai format manual
                $tiket_display = function_exists('dw_format_price') && $tiket 
                    ? dw_format_price($tiket) 
                    : ($tiket ? 'Rp ' . number_format((float)$tiket, 0, ',', '.') : '<span class="text-green-600">Gratis</span>');

                $img_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium_large') : '';
        ?>
            <!-- Wisata Card (Full Width Design) -->
            <a href="<?php the_permalink(); ?>" class="group block bg-white rounded-[1.5rem] shadow-soft overflow-hidden hover:shadow-lg transition-all duration-300 transform active:scale-[0.98]">
                
                <!-- Image Container -->
                <div class="h-48 w-full bg-gray-200 relative overflow-hidden">
                    <?php if ($img_url) : ?>
                        <img src="<?php echo esc_url($img_url); ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                    <?php else : ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-400 bg-gray-100"><i class="ph-duotone ph-image text-4xl"></i></div>
                    <?php endif; ?>
                    
                    <!-- Rating Badge -->
                    <div class="absolute top-4 right-4 bg-white/95 backdrop-blur-md px-2.5 py-1 rounded-lg flex items-center gap-1 shadow-md">
                        <i class="ph-fill ph-star text-yellow-400 text-xs"></i>
                        <span class="text-xs font-bold text-gray-800"><?php echo esc_html($rating); ?></span>
                    </div>

                    <!-- Kategori Badge (Visual) -->
                    <div class="absolute bottom-4 left-4 bg-black/40 backdrop-blur-sm text-white px-3 py-1 rounded-full text-[10px] font-medium border border-white/20">
                        Jelajah
                    </div>
                </div>

                <!-- Info Container -->
                <div class="p-5">
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="text-lg font-bold text-gray-900 leading-tight group-hover:text-primary transition-colors"><?php the_title(); ?></h3>
                    </div>
                    
                    <div class="flex items-center gap-1.5 text-gray-500 mb-4">
                        <i class="ph-fill ph-map-pin text-primary text-sm"></i>
                        <span class="text-xs font-medium truncate"><?php echo esc_html($lokasi); ?></span>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-dashed border-gray-100">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold mb-0.5">Harga Tiket</p>
                            <span class="text-sm font-bold text-gray-900">
                                <?php echo $tiket_display; ?>
                            </span>
                        </div>
                        <span class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center text-primary group-hover:bg-primary group-hover:text-white transition-all duration-300">
                            <i class="ph-bold ph-arrow-right text-lg"></i>
                        </span>
                    </div>
                </div>
            </a>
        <?php
            endwhile;
            
            // Pagination UI (Tailwind)
            $pagination_links = paginate_links(array(
                'base'      => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'format'    => '?paged=%#%',
                'current'   => max(1, get_query_var('paged')),
                'total'     => $query->max_num_pages,
                'prev_text' => '<i class="ph-bold ph-caret-left"></i>',
                'next_text' => '<i class="ph-bold ph-caret-right"></i>',
                'type'      => 'array',
            ));

            if ($pagination_links) {
                echo '<div class="mt-8 flex justify-center gap-2">';
                foreach ($pagination_links as $link) {
                    $link = str_replace('page-numbers', 'w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50', $link);
                    $link = str_replace('current', '!bg-gray-900 !text-white !border-gray-900', $link);
                    echo $link;
                }
                echo '</div>';
            }

            wp_reset_postdata();
        else :
        ?>
            <!-- Empty State -->
            <div class="flex flex-col items-center justify-center py-20 text-center animate-fade-in">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4 text-gray-300">
                    <i class="ph-duotone ph-map-trifold text-4xl"></i>
                </div>
                <h3 class="text-base font-bold text-gray-800">Tidak Ditemukan</h3>
                <p class="text-xs text-gray-500 px-10 mt-1 leading-relaxed">
                    Maaf, destinasi wisata untuk kategori ini belum tersedia.
                </p>
                <a href="?kategori=" class="mt-4 px-6 py-2 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-600">Lihat Semua</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php get_footer(); ?>