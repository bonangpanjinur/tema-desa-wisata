<?php
/**
 * Template Name: Halaman Depan Sadesa
 */
get_header();

global $wpdb;

// 1. QUERY BANNER DARI DATABASE (Tabel: dw_banner)
// Pastikan tabel ini sinkron dengan plugin Anda. Jika di plugin namanya 'dw_banners', sesuaikan.
// Di functions.php Anda pakai prefix . 'dw_pedagang', jadi asumsi prefix WP standar.
$table_banner = $wpdb->prefix . 'dw_banner'; 
$banners = [];

// Cek tabel exist untuk mencegah error
if ($wpdb->get_var("SHOW TABLES LIKE '$table_banner'") == $table_banner) {
    // Mengambil banner aktif
    $banners = $wpdb->get_results("SELECT * FROM $table_banner WHERE status = 'aktif' ORDER BY prioritas ASC LIMIT 5");
}

// Fallback jika banner kosong atau tabel belum ada
if (empty($banners)) {
    $banners = [
        (object) [
            'gambar' => 'https://via.placeholder.com/1200x600?text=Selamat+Datang+di+Desa+Wisata',
            'judul' => 'Jelajahi Keindahan Desa Wisata',
            'link' => '#'
        ],
        (object) [
            'gambar' => 'https://via.placeholder.com/1200x600?text=Produk+UMKM+Unggulan',
            'judul' => 'Dukung Ekonomi Lokal dengan Produk UMKM',
            'link' => '#'
        ]
    ];
}
?>

<!-- SECTION: HERO CAROUSEL BANNER -->
<div class="relative bg-gray-900 mb-12">
    <!-- Carousel Container -->
    <div id="hero-carousel" class="relative w-full h-[400px] md:h-[500px] overflow-hidden">
        <?php foreach ($banners as $index => $banner) : 
            $active_class = ($index === 0) ? 'opacity-100 z-10' : 'opacity-0 z-0';
            $img = !empty($banner->gambar) ? $banner->gambar : 'https://via.placeholder.com/1200x600';
        ?>
        <div class="carousel-item absolute inset-0 transition-opacity duration-700 ease-in-out <?php echo $active_class; ?>" data-index="<?php echo $index; ?>">
            <!-- Image -->
            <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover opacity-60" alt="<?php echo esc_attr($banner->judul); ?>">
            
            <!-- Content Overlay -->
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center text-white px-4 max-w-3xl">
                    <h2 class="text-3xl md:text-5xl font-bold mb-4 drop-shadow-md tracking-tight leading-tight">
                        <?php echo esc_html($banner->judul); ?>
                    </h2>
                    <?php if (!empty($banner->link) && $banner->link !== '#') : ?>
                        <a href="<?php echo esc_url($banner->link); ?>" class="inline-block mt-4 bg-primary hover:bg-green-700 text-white font-bold py-3 px-8 rounded-full shadow-lg transition transform hover:-translate-y-1">
                            Lihat Detail
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Carousel Controls (Hanya muncul jika lebih dari 1 banner) -->
    <?php if (count($banners) > 1) : ?>
        <button id="prev-slide" class="absolute left-4 top-1/2 -translate-y-1/2 z-20 bg-white/20 hover:bg-white/40 text-white p-3 rounded-full backdrop-blur-sm transition">
            <i class="fas fa-chevron-left text-xl"></i>
        </button>
        <button id="next-slide" class="absolute right-4 top-1/2 -translate-y-1/2 z-20 bg-white/20 hover:bg-white/40 text-white p-3 rounded-full backdrop-blur-sm transition">
            <i class="fas fa-chevron-right text-xl"></i>
        </button>
        
        <!-- Indicators -->
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-20 flex gap-2">
            <?php foreach ($banners as $index => $banner) : 
                $dot_active = ($index === 0) ? 'bg-white w-8' : 'bg-white/50 w-2 hover:bg-white/80';
            ?>
                <button class="carousel-dot h-2 rounded-full transition-all duration-300 <?php echo $dot_active; ?>" data-index="<?php echo $index; ?>"></button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Simple Vanilla JS Carousel Script
document.addEventListener('DOMContentLoaded', function() {
    const items = document.querySelectorAll('.carousel-item');
    const dots = document.querySelectorAll('.carousel-dot');
    const prevBtn = document.getElementById('prev-slide');
    const nextBtn = document.getElementById('next-slide');
    let currentIndex = 0;
    const totalItems = items.length;
    let autoSlideInterval;

    if (totalItems <= 1) return; // No need script if only 1 slide

    function showSlide(index) {
        // Handle wrapping
        if (index < 0) index = totalItems - 1;
        if (index >= totalItems) index = 0;
        currentIndex = index;

        // Update Items
        items.forEach(item => {
            item.classList.remove('opacity-100', 'z-10');
            item.classList.add('opacity-0', 'z-0');
        });
        items[currentIndex].classList.remove('opacity-0', 'z-0');
        items[currentIndex].classList.add('opacity-100', 'z-10');

        // Update Dots
        dots.forEach(dot => {
            dot.className = 'carousel-dot h-2 rounded-full transition-all duration-300 bg-white/50 w-2 hover:bg-white/80';
        });
        dots[currentIndex].className = 'carousel-dot h-2 rounded-full transition-all duration-300 bg-white w-8';
    }

    function nextSlide() { showSlide(currentIndex + 1); }
    function prevSlide() { showSlide(currentIndex - 1); }

    // Event Listeners
    if(nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetTimer(); });
    if(prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetTimer(); });

    dots.forEach((dot, idx) => {
        dot.addEventListener('click', () => {
            showSlide(idx);
            resetTimer();
        });
    });

    // Auto Slide
    function startTimer() { autoSlideInterval = setInterval(nextSlide, 5000); }
    function resetTimer() { clearInterval(autoSlideInterval); startTimer(); }
    
    startTimer();
});
</script>

<!-- SECTION: WISATA POPULER -->
<div class="container mx-auto px-4 mb-16">
    <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
        <div>
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Destinasi Wisata Pilihan</h2>
            <p class="text-gray-500">Temukan tempat terbaik untuk liburan Anda di desa</p>
        </div>
        <a href="<?php echo home_url('/wisata'); ?>" class="text-primary font-bold hover:text-green-700 flex items-center gap-2 transition">
            Lihat Semua Wisata <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php
        $args_wisata = array(
            'post_type'      => 'dw_wisata',
            'posts_per_page' => 3, // Tampilkan 3 wisata
            'orderby'        => 'date',
            'order'          => 'DESC',
            'post_status'    => 'publish'
        );
        $wisata_query = new WP_Query($args_wisata);

        if ($wisata_query->have_posts()) :
            while ($wisata_query->have_posts()) : $wisata_query->the_post();
                // Meta Data Plugin
                $lokasi = get_post_meta(get_the_ID(), 'dw_lokasi', true);
                $harga_tiket = get_post_meta(get_the_ID(), 'dw_harga_tiket', true);
                $img = get_the_post_thumbnail_url(get_the_ID(), 'large') ?: 'https://via.placeholder.com/600x400';
        ?>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
            <div class="relative h-64 overflow-hidden">
                <img src="<?php echo esc_url($img); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                <div class="absolute top-4 left-4 bg-white/90 backdrop-blur-md px-4 py-1.5 rounded-full text-xs font-bold text-gray-800 shadow-sm flex items-center gap-1">
                    <i class="fas fa-ticket-alt text-orange-500"></i> 
                    <?php echo ($harga_tiket) ? dw_format_rupiah($harga_tiket) : 'Gratis'; ?>
                </div>
            </div>
            <div class="p-6">
                <div class="text-xs text-gray-500 mb-2 flex items-center gap-1 uppercase tracking-wide font-semibold">
                    <i class="fas fa-map-marker-alt text-primary"></i> <?php echo esc_html($lokasi ?: 'Lokasi Belum Diatur'); ?>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3 line-clamp-2 group-hover:text-primary transition">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                <p class="text-gray-600 text-sm line-clamp-3 mb-4 leading-relaxed">
                    <?php echo wp_trim_words(get_the_excerpt(), 18); ?>
                </p>
                <a href="<?php the_permalink(); ?>" class="inline-flex items-center text-sm font-bold text-primary hover:underline">
                    Baca Selengkapnya
                </a>
            </div>
        </div>
        <?php endwhile; wp_reset_postdata(); else: ?>
            <div class="col-span-full py-12 text-center bg-gray-50 rounded-xl border border-dashed border-gray-300 text-gray-500">
                Belum ada data wisata.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION: PRODUK UMKM -->
<div class="bg-gray-50 py-16">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Produk UMKM Lokal</h2>
                <p class="text-gray-500">Oleh-oleh autentik dan kerajinan tangan dari desa</p>
            </div>
            <a href="<?php echo home_url('/produk'); ?>" class="text-primary font-bold hover:text-green-700 flex items-center gap-2 transition">
                Belanja Sekarang <i class="fas fa-shopping-bag"></i>
            </a>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
            <?php
            $args_produk = array(
                'post_type'      => 'dw_produk',
                'posts_per_page' => 10, // Tampilkan 10 produk
                'orderby'        => 'rand', // Acak agar lebih variatif di home
                'post_status'    => 'publish'
            );
            $produk_query = new WP_Query($args_produk);

            if ($produk_query->have_posts()) :
                while ($produk_query->have_posts()) : $produk_query->the_post();
                    // Meta Data Plugin
                    $harga = get_post_meta(get_the_ID(), 'dw_harga_produk', true);
                    $lokasi_produk = get_post_meta(get_the_ID(), 'dw_lokasi_produk', true);
                    $img_prod = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: 'https://via.placeholder.com/300';
                    
                    // Author / Toko
                    $author_id = get_the_author_meta('ID');
                    $nama_toko = get_the_author_meta('display_name'); 
                    // Jika ada fungsi dw_get_pedagang_data di functions.php, bisa dipakai untuk nama toko real
                    if (function_exists('dw_get_pedagang_data')) {
                        $pedagang = dw_get_pedagang_data($author_id);
                        if ($pedagang) $nama_toko = $pedagang->nama_toko;
                    }
            ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition group flex flex-col h-full relative">
                <!-- Thumbnail -->
                <a href="<?php the_permalink(); ?>" class="block relative aspect-square bg-gray-100 overflow-hidden">
                    <img src="<?php echo esc_url($img_prod); ?>" alt="<?php the_title(); ?>" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
                    <?php if(!$harga): ?>
                        <div class="absolute top-2 right-2 bg-gray-800 text-white text-[10px] px-2 py-1 rounded">Info</div>
                    <?php endif; ?>
                </a>

                <!-- Content -->
                <div class="p-4 flex flex-col flex-1">
                    <div class="text-[10px] text-gray-400 mb-1 flex items-center gap-1">
                        <i class="fas fa-store"></i> <?php echo esc_html($nama_toko); ?>
                    </div>
                    
                    <h3 class="font-bold text-gray-800 text-sm leading-snug line-clamp-2 mb-2 flex-1 group-hover:text-primary transition">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>
                    
                    <div class="flex justify-between items-end mt-auto pt-2 border-t border-gray-50">
                        <div class="font-bold text-primary text-base">
                            <?php echo ($harga) ? dw_format_rupiah($harga) : 'Hubungi'; ?>
                        </div>
                        
                        <!-- Tombol Add to Cart (AJAX) -->
                        <button class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-primary hover:text-white transition shadow-sm btn-add-cart" 
                                data-product-id="<?php the_ID(); ?>" 
                                title="Tambah ke Keranjang">
                            <i class="fas fa-cart-plus text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; wp_reset_postdata(); else: ?>
                <div class="col-span-full py-12 text-center text-gray-400">
                    Belum ada produk yang ditampilkan.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-10">
            <a href="<?php echo home_url('/produk'); ?>" class="inline-block bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold py-3 px-8 rounded-full transition">
                Lihat Seluruh Produk
            </a>
        </div>
    </div>
</div>

<!-- SECTION: PROMO / CTA (Optional) -->
<div class="bg-primary py-16 text-white text-center">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold mb-4">Ingin Menjadi Mitra Desa Wisata?</h2>
        <p class="text-green-100 max-w-2xl mx-auto mb-8 text-lg">Bergabunglah dengan ribuan pedagang dan pengelola desa wisata lainnya. Promosikan potensi desa Anda ke seluruh dunia.</p>
        <div class="flex justify-center gap-4">
            <a href="<?php echo home_url('/register'); ?>" class="bg-white text-primary font-bold py-3 px-8 rounded-full shadow-lg hover:bg-gray-100 transition transform hover:-translate-y-1">
                Daftar Sekarang
            </a>
            <a href="<?php echo home_url('/tentang'); ?>" class="bg-transparent border-2 border-white text-white font-bold py-3 px-8 rounded-full hover:bg-white hover:text-primary transition">
                Pelajari Lebih Lanjut
            </a>
        </div>
    </div>
</div>

<script>
// Script Khusus untuk tombol add to cart di homepage (Simple AJAX Trigger)
jQuery(document).ready(function($) {
    $('.btn-add-cart').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var originalIcon = btn.html();
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin text-xs"></i>');

        $.post(dw_ajax.ajax_url, {
            action: 'dw_theme_add_to_cart',
            nonce: dw_ajax.nonce,
            product_id: btn.data('product-id'),
            quantity: 1
        }, function(response) {
            if(response.success) {
                btn.html('<i class="fas fa-check text-xs"></i>').addClass('bg-green-600 text-white').removeClass('bg-gray-100 text-gray-600');
                
                // Update badge cart di header jika ada (Optional)
                var currentCount = parseInt($('#header-cart-count').text()) || 0;
                $('#header-cart-count').text(currentCount + 1).removeClass('hidden scale-0').addClass('flex scale-100');
                
                setTimeout(function() {
                    btn.html(originalIcon).removeClass('bg-green-600 text-white').addClass('bg-gray-100 text-gray-600').prop('disabled', false);
                }, 2000);
            } else {
                alert('Gagal menambahkan ke keranjang');
                btn.html(originalIcon).prop('disabled', false);
            }
        });
    });
});
</script>

<?php get_footer(); ?>