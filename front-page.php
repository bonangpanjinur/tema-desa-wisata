<?php
/**
 * Template Name: Halaman Depan Sadesa (Wisata Slider + Produk Grid)
 */
get_header();

global $wpdb;

// =================================================================================
// 1. DATA FETCHING
// =================================================================================

$table_banner   = $wpdb->prefix . 'dw_banner';
$table_wisata   = $wpdb->prefix . 'dw_wisata';
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_desa     = $wpdb->prefix . 'dw_desa';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

// --- A. BANNER ---
$banners = [];
if ($wpdb->get_var("SHOW TABLES LIKE '$table_banner'") == $table_banner) {
    $banners = $wpdb->get_results("SELECT * FROM $table_banner WHERE status = 'aktif' ORDER BY prioritas ASC, created_at DESC LIMIT 5");
}
if (empty($banners)) {
    $banners = [
        (object)['gambar' => 'https://images.unsplash.com/photo-1596423736798-75b43694f540', 'judul' => 'Pesona Alam Desa', 'link' => '#'],
        (object)['gambar' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5', 'judul' => 'Kuliner Tradisional', 'link' => '#']
    ];
}

// --- B. WISATA (Limit 12 untuk Slider) ---
$limit_wisata = 12; 
$query_wisata = $wpdb->prepare("SELECT w.*, d.nama_desa, d.kabupaten FROM $table_wisata w LEFT JOIN $table_desa d ON w.id_desa = d.id WHERE w.status = 'aktif' ORDER BY w.created_at DESC LIMIT %d", $limit_wisata);
$list_wisata = $wpdb->get_results($query_wisata);

// Kategori Wisata Unik
$kategori_wisata = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_wisata WHERE status = 'aktif' AND kategori != '' ORDER BY kategori ASC");

// --- C. PRODUK (Limit 8 untuk Grid agar rapi) ---
// Kita gunakan 8 agar pas 2 baris (4 kolom desktop) atau 4 baris (2 kolom mobile)
$limit_produk = 8;
$query_produk = "SELECT p.*, ped.nama_toko, ped.slug_toko, d.kabupaten, d.nama_desa FROM $table_produk p LEFT JOIN $table_pedagang ped ON p.id_pedagang = ped.id LEFT JOIN $table_desa d ON ped.id_desa = d.id WHERE p.status = 'aktif' AND p.stok > 0 ORDER BY p.created_at DESC LIMIT $limit_produk";
$list_produk = $wpdb->get_results($query_produk);

// Kategori Produk Unik
$kategori_produk = $wpdb->get_col("SELECT DISTINCT kategori FROM $table_produk WHERE status = 'aktif' AND kategori != '' ORDER BY kategori ASC");
?>

<!-- Custom CSS Helper -->
<style>
    /* Sembunyikan Scrollbar tapi tetap bisa scroll */
    .hide-scroll::-webkit-scrollbar { display: none; }
    .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    
    /* State Aktif Tombol Filter */
    .filter-btn.active, .filter-btn-produk.active {
        background-color: #16a34a; /* green-600 */
        color: white;
        border-color: #16a34a;
    }
</style>

<div class="container mx-auto max-w-6xl pb-24 overflow-x-hidden">

    <!-- SECTION 1: HERO CAROUSEL -->
    <div class="mb-8 mt-4 md:mt-6 relative group px-3 md:px-0">
        <div class="overflow-hidden rounded-xl shadow-md relative h-[200px] md:h-[420px]">
            <div id="hero-carousel" class="flex transition-transform duration-500 ease-out h-full">
                <?php foreach ($banners as $index => $banner) : 
                    $img = !empty($banner->gambar) ? $banner->gambar : 'https://via.placeholder.com/1200x600';
                ?>
                <div class="min-w-full relative h-full">
                    <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover">
                    <!-- Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                    
                    <div class="absolute bottom-4 left-4 md:bottom-12 md:left-12 max-w-lg text-white">
                        <h2 class="font-bold text-lg md:text-4xl leading-tight mb-2 drop-shadow-md">
                            <?php echo esc_html($banner->judul); ?>
                        </h2>
                        <?php if($banner->link && $banner->link != '#'): ?>
                        <a href="<?php echo esc_url($banner->link); ?>" class="inline-block bg-green-600 text-white text-xs md:text-sm font-bold px-4 py-2 rounded-lg hover:bg-green-700 transition shadow-lg">
                            Lihat Detail
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Dots -->
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
                <?php foreach ($banners as $i => $b) : ?>
                    <button class="carousel-dot w-1.5 h-1.5 rounded-full bg-white/50 transition-all <?php echo $i === 0 ? 'bg-white w-4' : ''; ?>" data-index="<?php echo $i; ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- SECTION 2: MENU UTAMA (Body Icons) -->
    <div class="mb-10 px-3 md:px-0">
        <div class="grid grid-cols-3 gap-3 md:flex md:justify-center md:gap-20">
            <!-- Wisata -->
            <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-2 group p-2 rounded-xl active:bg-gray-50">
                <div class="w-14 h-14 md:w-20 md:h-20 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center shadow-sm border border-green-100 group-hover:bg-green-600 group-hover:text-white transition-all">
                    <i class="fas fa-map-marked-alt text-xl md:text-3xl"></i>
                </div>
                <span class="text-xs md:text-sm font-bold text-gray-700 group-hover:text-green-600">Wisata</span>
            </a>
            
            <!-- Produk -->
            <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-2 group p-2 rounded-xl active:bg-gray-50">
                <div class="w-14 h-14 md:w-20 md:h-20 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center shadow-sm border border-orange-100 group-hover:bg-orange-500 group-hover:text-white transition-all">
                    <i class="fas fa-shopping-basket text-xl md:text-3xl"></i>
                </div>
                <span class="text-xs md:text-sm font-bold text-gray-700 group-hover:text-orange-500">Produk</span>
            </a>
            
            <!-- Ojek -->
            <a href="<?php echo home_url('/ojek'); ?>" class="flex flex-col items-center gap-2 group p-2 rounded-xl active:bg-gray-50">
                <div class="w-14 h-14 md:w-20 md:h-20 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shadow-sm border border-blue-100 group-hover:bg-blue-600 group-hover:text-white transition-all">
                    <i class="fas fa-motorcycle text-xl md:text-3xl"></i>
                </div>
                <span class="text-xs md:text-sm font-bold text-gray-700 group-hover:text-blue-600">Ojek</span>
            </a>
        </div>
    </div>

    <!-- SECTION 3: JELAJAH WISATA (Slider Horizontal dengan Filter) -->
    <div class="mb-10">
        <!-- Header Section & Filter -->
        <div class="px-4 md:px-0 mb-4 flex flex-col md:flex-row justify-between items-start md:items-end gap-3">
            <div>
                <h3 class="font-bold text-lg md:text-2xl text-gray-800">Jelajahi Wisata</h3>
                <p class="text-xs md:text-sm text-gray-500">Destinasi populer desa</p>
            </div>
            
            <!-- FILTER BADGES SCROLLABLE (WISATA) -->
            <div class="w-full md:w-auto overflow-x-auto hide-scroll pb-1">
                <div class="flex gap-2 min-w-max">
                    <button class="filter-btn active px-4 py-1.5 rounded-full border border-gray-200 bg-white text-gray-600 text-xs font-semibold hover:bg-gray-50 transition-all shadow-sm whitespace-nowrap" data-filter="all">
                        Semua
                    </button>
                    <?php if(!empty($kategori_wisata)): foreach($kategori_wisata as $kat): ?>
                    <button class="filter-btn px-4 py-1.5 rounded-full border border-gray-200 bg-white text-gray-600 text-xs font-semibold hover:bg-gray-50 transition-all shadow-sm whitespace-nowrap" data-filter="<?php echo sanitize_title($kat); ?>">
                        <?php echo esc_html($kat); ?>
                    </button>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Container Slider Wisata -->
        <!-- Flex + Overflow-x-auto = SLIDER KE SAMPING -->
        <div id="wisata-container" class="flex overflow-x-auto hide-scroll gap-4 px-4 md:px-0 pb-4 snap-x snap-mandatory">
            <?php if (!empty($list_wisata)) : ?>
                <?php foreach($list_wisata as $w): 
                    $slug_kategori = sanitize_title($w->kategori ?? 'umum');
                ?>
                    <!-- Mobile: w-[70vw] agar mengintip, Desktop: w-72 -->
                    <div class="wisata-card-item flex-shrink-0 w-[70vw] sm:w-64 md:w-72 h-full snap-center transition-all duration-300 transform" data-category="<?php echo $slug_kategori; ?>">
                        <?php get_template_part('template-parts/card', 'wisata', array('item' => $w)); ?>
                    </div>
                <?php endforeach; ?>
                
                <!-- Tombol Lihat Semua di Ujung Slider -->
                <div class="flex-shrink-0 w-32 snap-center flex flex-col items-center justify-center">
                    <a href="<?php echo home_url('/wisata'); ?>" class="w-12 h-12 rounded-full bg-green-50 text-green-600 flex items-center justify-center mb-2 hover:bg-green-600 hover:text-white transition shadow-sm border border-green-100">
                        <i class="fas fa-arrow-right"></i>
                    </a>
                    <span class="text-xs font-bold text-gray-500">Lihat Semua</span>
                </div>

            <?php else: ?>
                <div class="w-full text-center py-10 text-gray-400 bg-gray-50 rounded-xl border border-dashed mx-4 md:mx-0">
                    Belum ada data wisata.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Empty State Wisata -->
        <div id="wisata-empty-state" class="hidden text-center py-10 text-gray-400">
            <p>Tidak ada wisata di kategori ini.</p>
        </div>
    </div>

    <!-- SECTION 4: PRODUK UMKM (Grid Vertical dengan Filter) -->
    <div class="mb-10">
        <!-- Header Section & Filter Produk -->
        <div class="px-4 md:px-0 mb-4 flex flex-col md:flex-row justify-between items-start md:items-end gap-3">
            <div>
                <h3 class="font-bold text-lg md:text-2xl text-gray-800">Produk Desa</h3>
                <p class="text-xs md:text-sm text-gray-500">Oleh-oleh autentik UMKM</p>
            </div>
            
            <!-- FILTER BADGES SCROLLABLE (PRODUK) -->
            <div class="w-full md:w-auto overflow-x-auto hide-scroll pb-1">
                <div class="flex gap-2 min-w-max">
                    <button class="filter-btn-produk active px-4 py-1.5 rounded-full border border-gray-200 bg-white text-gray-600 text-xs font-semibold hover:bg-gray-50 transition-all shadow-sm whitespace-nowrap" data-filter="all">
                        Semua
                    </button>
                    <?php if(!empty($kategori_produk)): foreach($kategori_produk as $kat): ?>
                    <button class="filter-btn-produk px-4 py-1.5 rounded-full border border-gray-200 bg-white text-gray-600 text-xs font-semibold hover:bg-gray-50 transition-all shadow-sm whitespace-nowrap" data-filter="<?php echo sanitize_title($kat); ?>">
                        <?php echo esc_html($kat); ?>
                    </button>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Container GRID Produk -->
        <!-- Grid cols-2 (HP) & cols-4 (Desktop) = KE BAWAH -->
        <div id="produk-container" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 md:gap-6 px-4 md:px-0">
            <?php if (!empty($list_produk)) : ?>
                <?php foreach($list_produk as $p): 
                    $slug_kategori_p = sanitize_title($p->kategori ?? 'umum');
                ?>
                    <!-- Wrapper Grid Item -->
                    <div class="produk-card-item h-full transition-all duration-300 transform" data-category="<?php echo $slug_kategori_p; ?>">
                        <?php get_template_part('template-parts/card', 'produk', array('item' => $p)); ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="w-full col-span-2 md:col-span-4 text-center py-10 text-gray-400 bg-gray-50 rounded-xl border border-dashed">
                    Belum ada produk tersedia.
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Empty State Produk -->
        <div id="produk-empty-state" class="hidden text-center py-10 text-gray-400">
            <p>Tidak ada produk di kategori ini.</p>
        </div>

        <!-- Tombol Lihat Semua Produk -->
        <div class="mt-6 text-center">
             <a href="<?php echo home_url('/produk'); ?>" class="inline-block px-6 py-2 rounded-full border border-green-600 text-green-600 text-xs font-bold hover:bg-green-600 hover:text-white transition">
                Lihat Semua Produk
             </a>
        </div>
    </div>

</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. CAROUSEL LOGIC
    const carousel = document.getElementById('hero-carousel');
    if(carousel) {
        const items = carousel.children;
        const dots = document.querySelectorAll('.carousel-dot');
        let index = 0;
        
        function showSlide(i) {
            index = i % items.length;
            carousel.style.transform = `translateX(-${index * 100}%)`;
            dots.forEach((d, idx) => {
                d.classList.toggle('w-4', idx === index);
                d.classList.toggle('bg-white', idx === index);
                d.classList.toggle('bg-white/50', idx !== index);
            });
        }
        setInterval(() => showSlide(index + 1), 5000);
        dots.forEach(dot => {
            dot.addEventListener('click', function() { showSlide(parseInt(this.dataset.index)); });
        });
    }

    // 2. FILTER LOGIC (Works for both Slider & Grid)
    function setupFilter(btnSelector, itemSelector, emptyStateId) {
        const btns = document.querySelectorAll(btnSelector);
        const items = document.querySelectorAll(itemSelector);
        const emptyState = document.getElementById(emptyStateId);

        if(btns.length > 0) {
            btns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class
                    btns.forEach(b => b.classList.remove('active'));
                    // Add active class
                    this.classList.add('active');

                    const filterValue = this.getAttribute('data-filter');
                    let visibleCount = 0;

                    items.forEach(item => {
                        const itemCategory = item.getAttribute('data-category');
                        
                        if (filterValue === 'all' || itemCategory === filterValue) {
                            item.style.display = 'block';
                            setTimeout(() => {
                                item.classList.remove('opacity-0', 'scale-95');
                                item.classList.add('opacity-100', 'scale-100');
                            }, 50);
                            visibleCount++;
                        } else {
                            item.style.display = 'none';
                            item.classList.add('opacity-0', 'scale-95');
                            item.classList.remove('opacity-100', 'scale-100');
                        }
                    });

                    // Empty State Check
                    if (emptyState) {
                        if (visibleCount === 0) {
                            emptyState.classList.remove('hidden');
                        } else {
                            emptyState.classList.add('hidden');
                        }
                    }
                });
            });
        }
    }

    // Initialize Filters
    setupFilter('.filter-btn', '.wisata-card-item', 'wisata-empty-state');
    setupFilter('.filter-btn-produk', '.produk-card-item', 'produk-empty-state');

});
</script>

<?php get_footer(); ?>