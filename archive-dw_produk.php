<?php
/**
 * Template Name: Archive Produk Desa Wisata
 * Description: Menampilkan daftar produk dengan filter kategori dan lokasi.
 */

get_header(); ?>

<div class="bg-gray-50 py-10 min-h-screen">
    <div class="container mx-auto px-4">
        
        <!-- Header Halaman -->
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Jelajahi Produk Desa</h1>
            <p class="text-gray-500 max-w-2xl mx-auto">Temukan aneka oleh-oleh otentik, kerajinan tangan unik, dan kuliner lezat langsung dari warga desa wisata di seluruh nusantara.</p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Sidebar Filter -->
            <aside class="w-full lg:w-1/4">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 sticky top-24">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-lg text-gray-800">Filter Pencarian</h3>
                        <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="text-xs text-primary hover:underline">Reset</a>
                    </div>
                    
                    <!-- Filter Kategori -->
                    <div class="mb-8">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">Kategori</h4>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <?php
                            $terms = get_terms( array(
                                'taxonomy'   => 'kategori_produk',
                                'hide_empty' => true,
                            ) );
                            if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                                foreach ( $terms as $term ) {
                                    $is_active = ( get_query_var( 'kategori_produk' ) == $term->slug );
                                    echo '<li>';
                                    echo '<a href="' . esc_url( get_term_link( $term ) ) . '" class="flex items-center group ' . ( $is_active ? 'text-primary font-bold' : 'hover:text-primary' ) . '">';
                                    echo '<span class="w-2 h-2 rounded-full mr-2 ' . ( $is_active ? 'bg-primary' : 'bg-gray-300 group-hover:bg-primary' ) . '"></span>';
                                    echo esc_html( $term->name ) . ' <span class="ml-auto text-xs text-gray-400">(' . $term->count . ')</span>';
                                    echo '</a>';
                                    echo '</li>';
                                }
                            } else {
                                echo '<li class="text-gray-400 italic">Belum ada kategori.</li>';
                            }
                            ?>
                        </ul>
                    </div>

                    <!-- Filter Harga (UI Only for now, implementation needs complex query) -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">Rentang Harga</h4>
                        <div class="flex items-center gap-2 mb-4">
                            <input type="number" placeholder="Min" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:border-primary">
                            <span class="text-gray-400">-</span>
                            <input type="number" placeholder="Max" class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:outline-none focus:border-primary">
                        </div>
                    </div>

                    <button class="w-full bg-primary text-white py-2.5 rounded-lg hover:bg-secondary transition text-sm font-bold shadow-md hover:shadow-lg transform hover:-translate-y-0.5 duration-200">
                        Terapkan Filter
                    </button>
                </div>
            </aside>

            <!-- Grid Produk -->
            <main class="w-full lg:w-3/4">
                
                <!-- Toolbar Atas -->
                <div class="flex justify-between items-center mb-6 bg-white p-4 rounded-lg shadow-sm border border-gray-100">
                    <div class="text-sm text-gray-500">
                        Menampilkan <span class="font-bold text-gray-800"><?php echo $wp_query->post_count; ?></span> dari <span class="font-bold text-gray-800"><?php echo $wp_query->found_posts; ?></span> produk
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-gray-500 hidden sm:inline">Urutkan:</span>
                        <select class="border-none text-sm font-medium text-gray-700 focus:ring-0 cursor-pointer bg-gray-50 rounded px-2 py-1 hover:bg-gray-100" onchange="location.href=this.value">
                            <option value="?orderby=date&order=DESC">Terbaru</option>
                            <option value="?orderby=meta_value_num&meta_key=_dw_harga_dasar&order=ASC">Harga Terendah</option>
                            <option value="?orderby=meta_value_num&meta_key=_dw_harga_dasar&order=DESC">Harga Tertinggi</option>
                        </select>
                    </div>
                </div>

                <?php if ( have_posts() ) : ?>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                        <?php while ( have_posts() ) : the_post(); 
                            // Mengambil data dari Plugin Core
                            $post_id = get_the_ID();
                            $harga = get_post_meta($post_id, '_dw_harga_dasar', true);
                            $stok = get_post_meta($post_id, '_dw_stok', true);
                            $lokasi = get_post_meta($post_id, '_dw_kabupaten', true); // Asumsi meta key lokasi
                            $rating = 0; // Default rating (bisa diintegrasikan dengan sistem review nanti)
                            
                            // Gambar Thumbnail
                            $thumb_url = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'medium_large') : 'https://via.placeholder.com/400x400?text=No+Image';
                        ?>
                            <!-- Kartu Produk -->
                            <div class="bg-white border border-gray-100 rounded-xl hover:shadow-xl transition duration-300 group flex flex-col h-full relative overflow-hidden">
                                
                                <!-- Badge Lokasi -->
                                <?php if($lokasi): ?>
                                <div class="absolute top-3 left-3 z-10">
                                    <span class="bg-black/50 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1