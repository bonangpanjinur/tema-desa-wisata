<?php
/**
 * Template Name: Halaman Depan Sadesa
 */
get_header();

global $wpdb;

// =================================================================================
// 1. DATA FETCHING (DIRECT DATABASE ACCESS)
// =================================================================================

$table_banner   = $wpdb->prefix . 'dw_banner';
$table_wisata   = $wpdb->prefix . 'dw_wisata';
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_desa     = $wpdb->prefix . 'dw_desa';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

// --- A. BANNER DATA ---
$banners = [];
// Cek keberadaan tabel untuk menghindari error fatal
if ($wpdb->get_var("SHOW TABLES LIKE '$table_banner'") == $table_banner) {
    $banners = $wpdb->get_results("SELECT * FROM $table_banner WHERE status = 'aktif' ORDER BY prioritas ASC, created_at DESC LIMIT 5");
}

// Fallback Banner jika kosong
if (empty($banners)) {
    $banners = [
        (object)[
            'gambar' => 'https://images.unsplash.com/photo-1596423736798-75b43694f540?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80',
            'judul' => 'Pesona Alam Desa', 'link' => '#'
        ],
        (object)[
            'gambar' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?ixlib=rb-1.2.1&auto=format&fit=crop&w=1200&q=80',
            'judul' => 'Kuliner Tradisional', 'link' => '#'
        ]
    ];
}

// --- B. WISATA DATA (JOIN DESA) ---
// Mengambil detail lokasi dari tabel desa dan data lengkap wisata
$query_wisata = "
    SELECT 
        w.*, 
        d.nama_desa, 
        d.kabupaten 
    FROM $table_wisata w
    LEFT JOIN $table_desa d ON w.id_desa = d.id
    WHERE w.status = 'aktif'
    ORDER BY w.created_at DESC
    LIMIT 4
";
$list_wisata = $wpdb->get_results($query_wisata);

// --- C. PRODUK DATA (JOIN PEDAGANG) ---
// Mengambil nama toko dari tabel pedagang
$query_produk = "
    SELECT 
        p.*, 
        ped.nama_toko 
    FROM $table_produk p
    LEFT JOIN $table_pedagang ped ON p.id_pedagang = ped.id
    WHERE p.status = 'aktif' AND p.stok > 0
    ORDER BY p.created_at DESC
    LIMIT 10
";
$list_produk = $wpdb->get_results($query_produk);

?>

<!-- =================================================================================
     2. VIEW SECTION
     ================================================================================= -->

<!-- SECTION 1: HERO CAROUSEL -->
<div class="mb-10 mt-4 relative group">
    <div class="overflow-hidden rounded-2xl shadow-md relative h-48 md:h-[400px]">
        
        <!-- Carousel Inner -->
        <div id="hero-carousel" class="flex transition-transform duration-500 ease-out h-full">
            <?php foreach ($banners as $index => $banner) : 
                $img = !empty($banner->gambar) ? $banner->gambar : 'https://via.placeholder.com/1200x600';
                $title = $banner->judul;
                $link = !empty($banner->link) ? $banner->link : '#';
            ?>
            <div class="min-w-full relative h-full">
                <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover" alt="<?php echo esc_attr($title); ?>">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                
                <div class="absolute bottom-6 left-6 md:bottom-12 md:left-12 max-w-lg text-white">
                    <span class="bg-primary text-white text-[10px] md:text-xs font-bold px-3 py-1 rounded-full mb-3 inline-block shadow-sm uppercase tracking-wide">
                        Unggulan
                    </span>
                    <h2 class="font-bold text-xl md:text-4xl leading-tight mb-4 drop-shadow-md">
                        <?php echo esc_html($title); ?>
                    </h2>
                    <a href="<?php echo esc_url($link); ?>" class="inline-block bg-white text-gray-800 text-xs md:text-sm font-bold px-6 py-3 rounded-xl hover:bg-gray-100 transition shadow-lg transform hover:-translate-y-1">
                        Lihat Detail
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Navigation Arrows -->
        <button id="prevBtn" class="absolute top-1/2 left-4 -translate-y-1/2 bg-white/30 hover:bg-white/80 text-white hover:text-gray-800 p-2 rounded-full backdrop-blur-sm transition hidden md:block">
            <i class="fas fa-chevron-left text-xl"></i>
        </button>
        <button id="nextBtn" class="absolute top-1/2 right-4 -translate-y-1/2 bg-white/30 hover:bg-white/80 text-white hover:text-gray-800 p-2 rounded-full backdrop-blur-sm transition hidden md:block">
            <i class="fas fa-chevron-right text-xl"></i>
        </button>

        <!-- Indicators -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
            <?php foreach ($banners as $i => $b) : ?>
                <button class="carousel-dot w-2 h-2 rounded-full bg-white/50 hover:bg-white transition-all <?php echo $i === 0 ? 'bg-white w-6' : ''; ?>" data-index="<?php echo $i; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- SECTION 2: MENU KATEGORI -->
<div class="mb-12">
    <div class="grid grid-cols-4 md:flex md:justify-center md:gap-16 gap-4">
        <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-green-600 group-hover:text-white transition-all">
                <i class="fas fa-map-marked-alt text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-green-600 transition-colors">Wisata</span>
        </a>
        <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-orange-100 text-orange-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-orange-500 group-hover:text-white transition-all">
                <i class="fas fa-box-open text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-orange-500 transition-colors">Produk</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-blue-100 text-blue-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-blue-500 group-hover:text-white transition-all">
                <i class="fas fa-bed text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-blue-500 transition-colors">Homestay</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-3 group">
            <div class="cat-icon-wrapper w-14 h-14 md:w-16 md:h-16 bg-purple-100 text-purple-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-purple-500 group-hover:text-white transition-all">
                <i class="fas fa-utensils text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-purple-500 transition-colors">Kuliner</span>
        </a>
    </div>
</div>

<!-- SECTION 3: WISATA POPULER -->
<div class="mb-10">
    <div class="flex justify-between items-end mb-6">
        <div>
            <h3 class="font-bold text-xl md:text-2xl text-gray-800">Wisata Populer</h3>
            <p class="text-sm text-gray-500 mt-1">Destinasi favorit wisatawan</p>
        </div>
        <a href="<?php echo home_url('/wisata'); ?>" class="text-primary text-sm font-bold hover:underline flex items-center gap-1">
            Lihat Semua <i class="fas fa-arrow-right text-xs"></i>
        </a>
    </div>

    <!-- Grid Card Wisata -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        <?php if (!empty($list_wisata)) : ?>
            <?php foreach($list_wisata as $wisata): 
                $img = !empty($wisata->foto_utama) ? $wisata->foto_utama : 'https://via.placeholder.com/400x300?text=Wisata';
                $title = $wisata->nama_wisata;
                
                // Lokasi: Prioritaskan Nama Desa atau Kabupaten
                $loc = !empty($wisata->nama_desa) ? "Desa " . $wisata->nama_desa : $wisata->kabupaten;
                if(empty($loc)) $loc = 'Desa Wisata';

                $price = ($wisata->harga_tiket > 0) ? 'Rp '.number_format($wisata->harga_tiket,0,',','.') : 'Gratis';
                $rating = $wisata->rating_avg > 0 ? $wisata->rating_avg : 'New';
                $ulasan = $wisata->total_ulasan > 0 ? "({$wisata->total_ulasan})" : '';
                
                // Gunakan slug untuk link (asumsi rewrite rule WP mendukung atau fallback ke query param)
                // Jika CPT tidak sync, link ini mungkin perlu disesuaikan ke page khusus
                $link = home_url('/wisata/' . $wisata->slug); 
            ?>
            <div class="card-sadesa group">
                <div class="card-img-wrap">
                    <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                    <div class="badge-rating"><i class="fas fa-star text-yellow-400"></i> <?php echo $rating . ' ' . $ulasan; ?></div>
                    <div class="badge-category">Wisata</div>
                </div>
                
                <div class="card-body">
                    <h3 class="card-title group-hover:text-primary transition">
                        <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($title); ?></a>
                    </h3>
                    <div class="card-meta">
                        <i class="fas fa-map-marker-alt text-red-400"></i>
                        <span class="truncate"><?php echo esc_html($loc); ?></span>
                    </div>
                    
                    <div class="card-footer">
                        <div>
                            <p class="price-label">Tiket Masuk</p>
                            <p class="price-tag"><?php echo $price; ?></p>
                        </div>
                        <a href="<?php echo esc_url($link); ?>" class="btn-detail">Lihat Detail <i class="fas fa-arrow-right ml-1"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-4 text-center text-gray-500 py-10 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                <i class="fas fa-mountain text-4xl mb-3 text-gray-300"></i>
                <p>Belum ada data wisata yang tersedia.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 4: PRODUK UMKM -->
<div class="mb-10">
    <div class="flex justify-between items-end mb-6">
        <div>
            <h3 class="font-bold text-xl md:text-2xl text-gray-800">Produk UMKM</h3>
            <p class="text-sm text-gray-500 mt-1">Oleh-oleh autentik langsung dari desa</p>
        </div>
        <a href="<?php echo home_url('/produk'); ?>" class="text-primary text-sm font-bold hover:underline flex items-center gap-1">
            Lihat Semua <i class="fas fa-arrow-right text-xs"></i>
        </a>
    </div>

    <!-- Grid Produk -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 md:gap-6">
        <?php if (!empty($list_produk)) : ?>
            <?php foreach($list_produk as $produk): 
                $id = $produk->id;
                $img = !empty($produk->foto_utama) ? $produk->foto_utama : 'https://via.placeholder.com/300x300?text=Produk';
                $title = $produk->nama_produk;
                $shop = !empty($produk->nama_toko) ? $produk->nama_toko : 'UMKM Desa';
                $price_raw = $produk->harga;
                $price = ($price_raw) ? number_format($price_raw, 0, ',', '.') : 'Hubungi';
                $terjual = $produk->terjual;
                $rating = $produk->rating_avg > 0 ? $produk->rating_avg : '';

                // Gunakan slug untuk link
                $link = home_url('/produk/' . $produk->slug);
            ?>
            <div class="card-sadesa group relative">
                <a href="<?php echo esc_url($link); ?>" class="block h-full flex flex-col">
                    <div class="card-img-wrap aspect-square bg-gray-100 relative">
                        <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>" loading="lazy">
                        
                        <?php if($rating): ?>
                        <span class="absolute top-2 left-2 bg-white/90 backdrop-blur text-[10px] px-2 py-1 rounded text-orange-500 font-bold shadow-sm flex items-center gap-1">
                            <i class="fas fa-star"></i> <?php echo $rating; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-3 flex-1">
                        <h4 class="text-sm font-bold text-gray-800 leading-snug mb-1 line-clamp-2 min-h-[2.5em] group-hover:text-primary transition"><?php echo esc_html($title); ?></h4>
                        <div class="flex items-center gap-1 mb-2 text-[10px] text-gray-500">
                            <i class="fas fa-store text-gray-400"></i> <span class="truncate"><?php echo esc_html($shop); ?></span>
                        </div>
                        <div class="mt-auto pt-2 border-t border-dashed border-gray-100 flex justify-between items-end">
                            <div>
                                <p class="text-primary font-bold text-sm">Rp <?php echo $price; ?></p>
                                <p class="text-[9px] text-gray-400"><?php echo $terjual > 0 ? "Terjual $terjual" : 'Produk Baru'; ?></p>
                            </div>
                        </div>
                    </div>
                </a>
                <!-- Add to Cart Button (Overlay) - AJAX Handler via functions.php -->
                <!-- Note: Perlu penyesuaian di functions.php untuk menerima ID dari tabel custom jika logic cart masih pakai Post ID -->
                <button type="button" 
                        class="btn-add-cart absolute bottom-3 right-3 shadow-sm z-10 btn-add-to-cart"
                        data-product-id="<?php echo $id; ?>" 
                        data-quantity="1">
                    <i class="fas fa-cart-plus text-xs"></i>
                </button>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full text-center text-gray-500 py-10 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                <p>Belum ada data produk tersedia.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- SCRIPT CAROUSEL & CART -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // === CAROUSEL LOGIC ===
    const carousel = document.getElementById('hero-carousel');
    const items = carousel.children;
    const dots = document.querySelectorAll('.carousel-dot');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    let currentIndex = 0;
    let autoPlayInterval;

    function updateCarousel() {
        const translateX = -(currentIndex * 100);
        carousel.style.transform = `translateX(${translateX}%)`;
        
        dots.forEach((dot, index) => {
            if(index === currentIndex) {
                dot.classList.add('bg-white', 'w-6');
                dot.classList.remove('bg-white/50');
            } else {
                dot.classList.remove('bg-white', 'w-6');
                dot.classList.add('bg-white/50');
            }
        });
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % items.length;
        updateCarousel();
    }

    function prevSlide() {
        currentIndex = (currentIndex - 1 + items.length) % items.length;
        updateCarousel();
    }

    // Event Listeners
    if(nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetAutoPlay(); });
    if(prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetAutoPlay(); });

    dots.forEach(dot => {
        dot.addEventListener('click', (e) => {
            currentIndex = parseInt(e.target.dataset.index);
            updateCarousel();
            resetAutoPlay();
        });
    });

    function startAutoPlay() {
        autoPlayInterval = setInterval(nextSlide, 5000);
    }

    function resetAutoPlay() {
        clearInterval(autoPlayInterval);
        startAutoPlay();
    }

    // Start
    if(items.length > 0) startAutoPlay();
});

// === CART LOGIC (jQuery) ===
jQuery(document).ready(function($) {
    $('.btn-add-to-cart').on('click', function(e) {
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
                setTimeout(function() {
                    btn.html(originalIcon).removeClass('bg-green-600 text-white').addClass('bg-gray-100 text-gray-600').prop('disabled', false);
                }, 2000);
                
                // Update Badge Cart (Optional jika ada elemennya)
                if(response.data.count) {
                    $('#header-cart-count').text(response.data.count).removeClass('hidden scale-0').addClass('flex scale-100');
                }
            } else {
                alert('Gagal: ' + (response.data.message || 'Error'));
                btn.html(originalIcon).prop('disabled', false);
            }
        });
    });
});
</script>

<?php get_footer(); ?>