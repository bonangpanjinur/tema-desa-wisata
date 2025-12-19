<?php
/**
 * Template Name: Halaman Depan Sadesa
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

// --- B. WISATA ---
$query_wisata = "SELECT w.*, d.nama_desa, d.kabupaten FROM $table_wisata w LEFT JOIN $table_desa d ON w.id_desa = d.id WHERE w.status = 'aktif' ORDER BY w.created_at DESC LIMIT 6";
$list_wisata = $wpdb->get_results($query_wisata);

// --- C. PRODUK ---
$query_produk = "SELECT p.*, ped.nama_toko, ped.slug_toko, d.kabupaten, d.nama_desa FROM $table_produk p LEFT JOIN $table_pedagang ped ON p.id_pedagang = ped.id LEFT JOIN $table_desa d ON ped.id_desa = d.id WHERE p.status = 'aktif' AND p.stok > 0 ORDER BY p.created_at DESC LIMIT 8";
$list_produk = $wpdb->get_results($query_produk);
?>

<!-- Custom CSS untuk Perbaikan Layout -->
<style>
    /* Sembunyikan scrollbar tapi tetap bisa scroll */
    .hide-scroll::-webkit-scrollbar { display: none; }
    .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    
    /* FIX: Memaksa gambar di dalam card agar rasionya sama & tidak gepeng */
    .card-img-fix img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Rasio gambar wisata landscape */
    .wisata-img-wrapper img {
        aspect-ratio: 16/9; 
    }

    /* Rasio gambar produk kotak */
    .produk-img-wrapper img {
        aspect-ratio: 1/1;
    }
</style>

<div class="container mx-auto max-w-6xl pb-24 overflow-x-hidden">

    <!-- SECTION 1: HERO CAROUSEL -->
    <!-- Margin top disesuaikan agar tidak menempel header -->
    <div class="mb-8 mt-4 md:mt-6 relative group px-3 md:px-0">
        <div class="overflow-hidden rounded-xl shadow-md relative h-[200px] md:h-[420px]">
            <div id="hero-carousel" class="flex transition-transform duration-500 ease-out h-full">
                <?php foreach ($banners as $index => $banner) : 
                    $img = !empty($banner->gambar) ? $banner->gambar : 'https://via.placeholder.com/1200x600';
                ?>
                <div class="min-w-full relative h-full">
                    <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover">
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

    <!-- SECTION 2: MENU ICON -->
    <div class="mb-10 px-3 md:px-0">
        <div class="grid grid-cols-3 gap-3 md:flex md:justify-center md:gap-20">
            <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center gap-2 group p-2 rounded-xl active:bg-gray-50">
                <div class="w-14 h-14 md:w-20 md:h-20 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center shadow-sm border border-green-100 group-hover:bg-green-600 group-hover:text-white transition-all">
                    <i class="fas fa-map-marked-alt text-xl md:text-3xl"></i>
                </div>
                <span class="text-xs md:text-sm font-bold text-gray-700">Wisata</span>
            </a>
            <a href="<?php echo home_url('/produk'); ?>" class="flex flex-col items-center gap-2 group p-2 rounded-xl active:bg-gray-50">
                <div class="w-14 h-14 md:w-20 md:h-20 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center shadow-sm border border-orange-100 group-hover:bg-orange-500 group-hover:text-white transition-all">
                    <i class="fas fa-shopping-basket text-xl md:text-3xl"></i>
                </div>
                <span class="text-xs md:text-sm font-bold text-gray-700">Produk</span>
            </a>
            <a href="<?php echo home_url('/ojek'); ?>" class="flex flex-col items-center gap-2 group p-2 rounded-xl active:bg-gray-50">
                <div class="w-14 h-14 md:w-20 md:h-20 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shadow-sm border border-blue-100 group-hover:bg-blue-600 group-hover:text-white transition-all">
                    <i class="fas fa-motorcycle text-xl md:text-3xl"></i>
                </div>
                <span class="text-xs md:text-sm font-bold text-gray-700">Ojek</span>
            </a>
        </div>
    </div>

    <!-- SECTION 3: JELAJAH WISATA (FIXED SIZE MOBILE CARD) -->
    <div class="mb-10">
        <div class="px-4 md:px-0 mb-4 flex justify-between items-end">
            <div>
                <h3 class="font-bold text-lg md:text-2xl text-gray-800">Jelajahi Wisata</h3>
                <p class="text-xs md:text-sm text-gray-500">Destinasi populer desa</p>
            </div>
            <a href="<?php echo home_url('/wisata'); ?>" class="text-green-600 text-xs font-bold hover:underline">Lihat Semua</a>
        </div>

        <!-- 
           PERBAIKAN WISATA:
           1. Menggunakan 'flex' dan 'overflow-x-auto' untuk scroll samping.
           2. PENTING: Class 'w-64' (256px). Ini ukuran pas untuk HP.
              Tidak terlalu besar (seperti 80%) dan tidak terlalu kecil.
        -->
        <div id="wisata-container" class="flex md:grid md:grid-cols-3 lg:grid-cols-4 gap-4 overflow-x-auto md:overflow-visible px-4 md:px-0 pb-4 md:pb-0 hide-scroll snap-x snap-mandatory scroll-pl-4">
            <?php if (!empty($list_wisata)) : ?>
                <?php foreach($list_wisata as $w): ?>
                    <!-- Wrapper Card: Width fix 16rem (w-64) pada mobile -->
                    <div class="w-64 md:w-auto flex-shrink-0 snap-center h-full relative wisata-img-wrapper">
                        <!-- h-full memaksa tinggi card sama -->
                        <div class="h-full flex flex-col shadow-md rounded-xl overflow-hidden bg-white border border-gray-100">
                            <?php get_template_part('template-parts/card', 'wisata', array('item' => $w)); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="w-full text-center py-10 text-gray-400 bg-gray-50 rounded-xl border border-dashed">Belum ada data wisata.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SECTION 4: PRODUK UMKM (OPTIMIZED GRID) -->
    <div class="mb-10 px-2 md:px-0"> <!-- px-2 di mobile agar grid lebih lebar -->
        <div class="flex justify-between items-end mb-4 px-2 md:px-0">
            <div>
                <h3 class="font-bold text-lg md:text-2xl text-gray-800">Produk Desa</h3>
                <p class="text-xs md:text-sm text-gray-500">Oleh-oleh autentik UMKM</p>
            </div>
            <a href="<?php echo home_url('/produk'); ?>" class="text-green-600 text-xs font-bold hover:underline">Lihat Semua</a>
        </div>

        <!-- 
           PERBAIKAN PRODUK:
           1. Gap diperkecil jadi 'gap-3' (sebelumnya gap-4) agar kartu punya ruang lebih.
           2. Container padding diperkecil (px-2) agar grid lebih luas.
           3. Class 'produk-img-wrapper' & 'card-img-fix' (di style atas) menjaga gambar tetap kotak 1:1.
        -->
        <div id="produk-container" class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
            <?php foreach($list_produk as $p): ?>
                <!-- h-full penting agar tombol/harga di bawah sejajar jika judul panjangnya beda -->
                <div class="h-full flex flex-col bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden produk-img-wrapper card-img-fix hover:shadow-md transition-shadow">
                    <?php get_template_part('template-parts/card', 'produk', array('item' => $p)); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Carousel Logic
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
        
        // Dot click
        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                showSlide(parseInt(this.dataset.index));
            });
        });
    }
});
</script>

<?php get_footer(); ?>