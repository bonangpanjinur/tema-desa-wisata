<?php
/**
 * Template Name: Halaman Depan Sadesa
 */
get_header();

global $wpdb;

// =================================================================================
// 1. DATA FETCHING (LANGSUNG KE DATABASE AGAR DATA MUNCUL)
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

// --- B. WISATA (JOIN DESA) ---
$query_wisata = "
    SELECT w.*, d.nama_desa, d.kabupaten 
    FROM $table_wisata w
    LEFT JOIN $table_desa d ON w.id_desa = d.id
    WHERE w.status = 'aktif'
    ORDER BY w.created_at DESC
    LIMIT 8
";
$list_wisata = $wpdb->get_results($query_wisata);

// --- C. PRODUK ---
$query_produk = "
    SELECT p.*, ped.nama_toko 
    FROM $table_produk p
    LEFT JOIN $table_pedagang ped ON p.id_pedagang = ped.id
    WHERE p.status = 'aktif' AND p.stok > 0
    ORDER BY p.created_at DESC
    LIMIT 10
";
$list_produk = $wpdb->get_results($query_produk);

// --- D. KATEGORI WISATA (Untuk Filter Pin) ---
$kategori_wisata = get_terms([
    'taxonomy'   => 'dw_kategori_wisata',
    'hide_empty' => true,
]);

// --- E. KATEGORI PRODUK (Untuk Filter Pin) ---
$kategori_produk = get_terms([
    'taxonomy'   => 'dw_kategori_produk',
    'hide_empty' => true,
]);

?>

<!-- =================================================================================
     2. VIEW SECTION
     ================================================================================= -->

<!-- SECTION 1: HERO CAROUSEL -->
<div class="mb-8 mt-0 md:mt-4 relative group px-4 md:px-0">
    <div class="overflow-hidden rounded-2xl shadow-md relative h-48 md:h-[400px]">
        <div id="hero-carousel" class="flex transition-transform duration-500 ease-out h-full">
            <?php foreach ($banners as $index => $banner) : 
                $img = !empty($banner->gambar) ? $banner->gambar : 'https://via.placeholder.com/1200x600';
            ?>
            <div class="min-w-full relative h-full">
                <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                <div class="absolute bottom-5 left-5 md:bottom-12 md:left-12 max-w-lg text-white">
                    <h2 class="font-bold text-xl md:text-4xl leading-tight mb-2 md:mb-4 drop-shadow-md">
                        <?php echo esc_html($banner->judul); ?>
                    </h2>
                    <a href="<?php echo esc_url($banner->link ?: '#'); ?>" class="inline-block bg-white text-gray-800 text-xs md:text-sm font-bold px-4 py-2 md:px-6 md:py-3 rounded-lg hover:bg-gray-100 transition shadow-lg">
                        Lihat Detail
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- Indicators -->
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1.5">
            <?php foreach ($banners as $i => $b) : ?>
                <button class="carousel-dot w-1.5 h-1.5 rounded-full bg-white/50 transition-all <?php echo $i === 0 ? 'bg-white w-4' : ''; ?>" data-index="<?php echo $i; ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- SECTION 2: MENU UTAMA -->
<div class="mb-10 px-4 md:px-0">
    <div class="grid grid-cols-4 md:flex md:justify-center md:gap-16 gap-4">
        <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-green-600 group-hover:text-white transition-all">
                <i class="fas fa-map-marked-alt text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-green-600 transition-colors">Wisata</span>
        </a>
        <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-3 group">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-orange-100 text-orange-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-orange-500 group-hover:text-white transition-all">
                <i class="fas fa-box-open text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-orange-500 transition-colors">Produk</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-3 group">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-blue-100 text-blue-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-blue-500 group-hover:text-white transition-all">
                <i class="fas fa-bed text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-blue-500 transition-colors">Homestay</span>
        </a>
        <a href="#" class="flex flex-col items-center gap-3 group">
            <div class="w-14 h-14 md:w-16 md:h-16 bg-purple-100 text-purple-500 rounded-2xl flex items-center justify-center shadow-sm group-hover:bg-purple-500 group-hover:text-white transition-all">
                <i class="fas fa-utensils text-2xl md:text-3xl"></i>
            </div>
            <span class="text-xs md:text-sm font-bold text-gray-600 group-hover:text-purple-500 transition-colors">Kuliner</span>
        </a>
    </div>
</div>

<!-- SECTION 3: JELAJAH WISATA (Dengan Pin Filter) -->
<div class="mb-10 px-0 md:px-0">
    
    <div class="px-4 md:px-0 mb-4 flex justify-between items-end">
        <div>
            <h3 class="font-bold text-lg md:text-2xl text-gray-800">Jelajahi Wisata</h3>
            <p class="text-xs md:text-sm text-gray-500">Destinasi pilihan untuk liburanmu</p>
        </div>
        <a href="<?php echo home_url('/wisata'); ?>" class="text-primary text-xs font-bold hover:underline">Lihat Semua</a>
    </div>

    <!-- Sticky Category Filter (PINS WISATA) -->
    <div class="sticky top-16 z-30 bg-gray-50/95 backdrop-blur-sm py-2 mb-4">
        <div class="flex gap-2 overflow-x-auto hide-scroll px-4 md:px-0 pb-2 snap-x">
            <!-- Default All -->
            <button onclick="filterContent('wisata', 'all', this)" class="cat-pin-wisata snap-start px-4 py-1.5 rounded-full text-xs font-bold bg-primary text-white shadow-sm border border-transparent whitespace-nowrap transition-all active-pin">
                Semua
            </button>
            
            <!-- Dynamic Terms -->
            <?php foreach($kategori_wisata as $term): ?>
            <button onclick="filterContent('wisata', '<?php echo $term->slug; ?>', this)" class="cat-pin-wisata snap-start px-4 py-1.5 rounded-full text-xs font-bold bg-white text-gray-600 border border-gray-200 hover:border-primary hover:text-primary whitespace-nowrap transition-all">
                <?php echo esc_html($term->name); ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- GRID / SCROLL WISATA -->
    <div id="wisata-container" class="flex md:grid md:grid-cols-4 gap-4 overflow-x-auto md:overflow-visible px-4 md:px-0 pb-6 md:pb-0 hide-scroll snap-x snap-mandatory">
        
        <?php if (!empty($list_wisata)) : ?>
            <?php foreach($list_wisata as $wisata): 
                $img = !empty($wisata->foto_utama) ? $wisata->foto_utama : 'https://via.placeholder.com/400x300';
                $title = $wisata->nama_wisata;
                $loc = !empty($wisata->nama_desa) ? $wisata->nama_desa : $wisata->kabupaten;
                $price = ($wisata->harga_tiket > 0) ? 'Rp '.number_format($wisata->harga_tiket,0,',','.') : 'Gratis';
                $rating = $wisata->rating_avg > 0 ? $wisata->rating_avg : '4.5';
                $link = home_url('/wisata/?id=' . $wisata->id);

                // Ambil Kategori Dinamis untuk Label di Foto & Filter
                $terms = get_the_terms($wisata->id, 'dw_kategori_wisata');
                $cat_name = !empty($terms) && !is_wp_error($terms) ? $terms[0]->name : 'Wisata';
                $cat_slug = !empty($terms) && !is_wp_error($terms) ? $terms[0]->slug : 'all';
            ?>
            <!-- Item Card -->
            <div class="wisata-item min-w-[75vw] md:min-w-0 md:w-auto flex-shrink-0 snap-center group relative bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition-all hover:shadow-md" data-category="<?php echo esc_attr($cat_slug); ?>">
                
                <!-- Image Wrapper -->
                <div class="relative h-40 md:h-48 bg-gray-200 overflow-hidden">
                    <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover transition duration-700 group-hover:scale-110">
                    
                    <!-- Rating Badge -->
                    <div class="absolute top-2 right-2 bg-white/90 backdrop-blur px-2 py-0.5 rounded-lg text-[10px] font-bold shadow-sm flex items-center gap-1">
                        <i class="fas fa-star text-yellow-400"></i> <?php echo $rating; ?>
                    </div>
                    
                    <!-- Dynamic Category Badge (Fix #2: Ganti "Wisata" jadi Kategori Asli) -->
                    <div class="absolute bottom-2 left-2 bg-black/60 backdrop-blur text-white text-[10px] px-2 py-0.5 rounded-md font-medium uppercase tracking-wider">
                        <?php echo esc_html($cat_name); ?>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-3 md:p-4 flex flex-col h-[130px] md:h-auto">
                    <h3 class="font-bold text-sm md:text-base text-gray-800 line-clamp-1 mb-1 group-hover:text-primary transition">
                        <a href="<?php echo esc_url($link); ?>"><?php echo esc_html($title); ?></a>
                    </h3>
                    <div class="text-xs text-gray-500 mb-3 flex items-center gap-1">
                        <i class="fas fa-map-marker-alt text-red-400"></i>
                        <span class="truncate"><?php echo esc_html($loc); ?></span>
                    </div>
                    
                    <div class="mt-auto flex justify-between items-center border-t border-dashed border-gray-100 pt-2">
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase font-bold">Tiket</p>
                            <p class="text-sm font-bold text-primary"><?php echo $price; ?></p>
                        </div>
                        <!-- Fix #3: Ganti Tombol Arrow dengan Teks Detail tapi tetap Compact -->
                        <a href="<?php echo esc_url($link); ?>" class="px-3 py-1.5 rounded-lg bg-gray-50 text-gray-600 text-[10px] font-bold hover:bg-primary hover:text-white transition flex items-center gap-1">
                            Detail <i class="fas fa-arrow-right text-[8px]"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full py-10 text-center text-gray-400 bg-white rounded-xl border border-dashed border-gray-200">
                Belum ada data wisata.
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 4: PRODUK UMKM (Dengan Pin Filter) -->
<div class="mb-10 px-4 md:px-0">
    <div class="flex justify-between items-end mb-4">
        <div>
            <h3 class="font-bold text-lg md:text-2xl text-gray-800">Produk Desa</h3>
            <p class="text-xs md:text-sm text-gray-500">Oleh-oleh autentik UMKM</p>
        </div>
        <a href="<?php echo home_url('/produk'); ?>" class="text-primary text-xs font-bold hover:underline">Lihat Semua</a>
    </div>

    <!-- Sticky Category Filter (PINS PRODUK) -->
    <div class="sticky top-16 z-30 bg-gray-50/95 backdrop-blur-sm py-2 mb-4">
        <div class="flex gap-2 overflow-x-auto hide-scroll pb-2 snap-x">
            <!-- Default All -->
            <button onclick="filterContent('produk', 'all', this)" class="cat-pin-produk snap-start px-4 py-1.5 rounded-full text-xs font-bold bg-primary text-white shadow-sm border border-transparent whitespace-nowrap transition-all active-pin">
                Semua
            </button>
            
            <!-- Dynamic Terms -->
            <?php foreach($kategori_produk as $term): ?>
            <button onclick="filterContent('produk', '<?php echo $term->slug; ?>', this)" class="cat-pin-produk snap-start px-4 py-1.5 rounded-full text-xs font-bold bg-white text-gray-600 border border-gray-200 hover:border-primary hover:text-primary whitespace-nowrap transition-all">
                <?php echo esc_html($term->name); ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="produk-container" class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-6">
        <?php foreach($list_produk as $produk): 
            $img = !empty($produk->foto_utama) ? $produk->foto_utama : 'https://via.placeholder.com/300';
            $price = number_format($produk->harga, 0, ',', '.');
            $link = home_url('/produk/?id=' . $produk->id);

            // Ambil Kategori Dinamis untuk Filter
            $terms = get_the_terms($produk->id, 'dw_kategori_produk');
            $cat_slug = !empty($terms) && !is_wp_error($terms) ? $terms[0]->slug : 'all';
        ?>
        <div class="produk-item bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden group relative" data-category="<?php echo esc_attr($cat_slug); ?>">
            <a href="<?php echo esc_url($link); ?>">
                <div class="aspect-square bg-gray-100 relative overflow-hidden">
                    <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                </div>
                <div class="p-3">
                    <h4 class="text-xs md:text-sm font-bold text-gray-800 line-clamp-2 mb-1 min-h-[2.5em]"><?php echo esc_html($produk->nama_produk); ?></h4>
                    <p class="text-xs text-gray-400 mb-2 truncate"><?php echo esc_html($produk->nama_toko ?: 'UMKM Desa'); ?></p>
                    <div class="font-bold text-primary text-sm">Rp <?php echo $price; ?></div>
                </div>
            </a>
            <button class="absolute bottom-3 right-3 w-7 h-7 bg-primary text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition btn-add-to-cart" data-product-id="<?php echo $produk->id; ?>">
                <i class="fas fa-plus text-xs"></i>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- SCRIPTS: Carousel & Filter Logic -->
<script>
// 1. CAROUSEL AUTO-PLAY
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.getElementById('hero-carousel');
    if(!carousel) return;
    
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
});

// 2. FILTER KATEGORI (General Function)
function filterContent(type, category, btn) {
    // Determine Selector Class based on type (wisata or produk)
    const pinClass = type === 'wisata' ? '.cat-pin-wisata' : '.cat-pin-produk';
    const itemClass = type === 'wisata' ? '.wisata-item' : '.produk-item';
    
    // Style Button
    document.querySelectorAll(pinClass).forEach(b => {
        b.classList.remove('bg-primary', 'text-white', 'active-pin');
        b.classList.add('bg-white', 'text-gray-600', 'border-gray-200');
    });
    btn.classList.remove('bg-white', 'text-gray-600', 'border-gray-200');
    btn.classList.add('bg-primary', 'text-white', 'active-pin');

    // Filter Logic
    const items = document.querySelectorAll(itemClass);
    items.forEach(item => {
        if (category === 'all' || item.dataset.category === category) {
            item.style.display = 'block'; 
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

<?php get_footer(); ?>