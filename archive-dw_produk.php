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
                                    <span class="bg-black/50 backdrop-blur-sm text-white text-[10px] font-bold px-2 py-1 rounded-md flex items-center gap-1">
                                        <i class="fas fa-map-marker-alt text-red-400"></i> <?php echo esc_html($lokasi); ?>
                                    </span>
                                </div>
                                <?php endif; ?>

                                <!-- Gambar Produk -->
                                <div class="relative h-48 sm:h-56 overflow-hidden bg-gray-200">
                                    <a href="<?php the_permalink(); ?>">
                                        <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover transform group-hover:scale-110 transition duration-500">
                                    </a>
                                    
                                    <!-- Quick Action Overlay (Desktop) -->
                                    <div class="absolute bottom-0 left-0 right-0 p-4 translate-y-full group-hover:translate-y-0 transition duration-300 hidden md:flex justify-center bg-gradient-to-t from-black/60 to-transparent">
                                        <button class="bg-white text-primary hover:bg-primary hover:text-white p-2 rounded-full shadow-lg transition transform hover:scale-110 add-to-cart-btn" 
                                                title="Tambah ke Keranjang"
                                                data-id="<?php echo $post_id; ?>" 
                                                data-title="<?php the_title(); ?>"
                                                data-price="<?php echo esc_attr($harga); ?>"
                                                data-thumb="<?php echo esc_url($thumb_url); ?>">
                                            <i class="fas fa-cart-plus"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Info Produk -->
                                <div class="p-4 flex-1 flex flex-col">
                                    <div class="text-xs text-gray-400 mb-1 flex justify-between items-center">
                                        <span><?php echo get_the_term_list($post_id, 'kategori_produk', '', ', ', ''); ?></span>
                                        <div class="flex text-yellow-400 text-[10px]">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="far fa-star"></i>
                                        </div>
                                    </div>
                                    
                                    <h3 class="font-bold text-gray-800 mb-1 leading-snug line-clamp-2 hover:text-primary transition">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    
                                    <div class="mt-auto pt-3 flex items-center justify-between border-t border-gray-50">
                                        <div>
                                            <span class="block text-xs text-gray-400">Harga</span>
                                            <span class="text-lg font-bold text-primary">Rp <?php echo number_format((float)$harga, 0, ',', '.'); ?></span>
                                        </div>
                                        
                                        <!-- Mobile Add Cart Button -->
                                        <button class="md:hidden bg-green-50 text-primary p-2 rounded-lg hover:bg-primary hover:text-white transition add-to-cart-btn"
                                                data-id="<?php echo $post_id; ?>" 
                                                data-title="<?php the_title(); ?>"
                                                data-price="<?php echo esc_attr($harga); ?>"
                                                data-thumb="<?php echo esc_url($thumb_url); ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <!-- Navigasi Halaman -->
                    <div class="mt-12">
                        <?php
                        the_posts_pagination( array(
                            'mid_size'  => 2,
                            'prev_text' => '<span class="px-4 py-2 border border-gray-300 rounded-md bg-white hover:bg-gray-50 text-gray-600 transition">Sebelumnya</span>',
                            'next_text' => '<span class="px-4 py-2 border border-gray-300 rounded-md bg-white hover:bg-gray-50 text-gray-600 transition">Selanjutnya</span>',
                            'screen_reader_text' => 'Navigasi Halaman',
                            'class' => 'flex justify-center gap-2' // Custom CSS might be needed for full styling standard WP pagination output
                        ) );
                        ?>
                    </div>

                <?php else : ?>
                    <!-- State Kosong -->
                    <div class="bg-white rounded-xl shadow-sm p-12 text-center border border-gray-100">
                        <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                            <i class="fas fa-search text-4xl text-gray-300"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">Produk Tidak Ditemukan</h3>
                        <p class="text-gray-500 mb-6">Maaf, kami tidak menemukan produk yang cocok dengan kriteria pencarian Anda.</p>
                        <a href="<?php echo get_post_type_archive_link('dw_produk'); ?>" class="inline-block bg-primary text-white px-6 py-2.5 rounded-lg hover:bg-secondary transition font-medium">
                            Lihat Semua Produk
                        </a>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</div>

<?php get_footer(); ?>