<?php
/**
 * Template Name: Halaman Depan Sadesa
 */
get_header();

global $wpdb;

// =================================================================================
// 1. DATA FETCHING (DIRECT DB ACCESS)
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
// FIX: Gunakan query yang sama dengan archive agar konsisten
$query_produk = "
    SELECT p.*, ped.nama_toko, ped.slug_toko, d.kabupaten, d.nama_desa 
    FROM $table_produk p
    LEFT JOIN $table_pedagang ped ON p.id_pedagang = ped.id
    LEFT JOIN $table_desa d ON ped.id_desa = d.id
    WHERE p.status = 'aktif' AND p.stok > 0
    ORDER BY p.created_at DESC
    LIMIT 10
";
$list_produk = $wpdb->get_results($query_produk);

// --- D. KATEGORI (Untuk Filter - jika diperlukan di masa depan) ---
?>

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

<!-- SECTION 3: JELAJAH WISATA -->
<div class="mb-10 px-0 md:px-0">
    <div class="px-4 md:px-0 mb-4 flex justify-between items-end">
        <div>
            <h3 class="font-bold text-lg md:text-2xl text-gray-800">Jelajahi Wisata</h3>
            <p class="text-xs md:text-sm text-gray-500">Destinasi pilihan untuk liburanmu</p>
        </div>
        <a href="<?php echo home_url('/wisata'); ?>" class="text-primary text-xs font-bold hover:underline">Lihat Semua</a>
    </div>

    <!-- Grid / Scroll Wisata -->
    <div id="wisata-container" class="flex md:grid md:grid-cols-4 gap-4 overflow-x-auto md:overflow-visible px-4 md:px-0 pb-6 md:pb-0 hide-scroll snap-x snap-mandatory">
        <?php if (!empty($list_wisata)) : ?>
            <?php foreach($list_wisata as $w): ?>
                <div class="wisata-item min-w-[75vw] md:min-w-0 md:w-auto flex-shrink-0 snap-center">
                    <!-- PANGGIL CARD WISATA -->
                    <?php get_template_part('template-parts/card', 'wisata', array('item' => $w)); ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-full py-10 text-center text-gray-400 bg-white rounded-xl border border-dashed border-gray-200">Belum ada data wisata.</div>
        <?php endif; ?>
    </div>
</div>

<!-- SECTION 4: PRODUK UMKM -->
<div class="mb-10 px-4 md:px-0">
    <div class="flex justify-between items-end mb-4">
        <div>
            <h3 class="font-bold text-lg md:text-2xl text-gray-800">Produk Desa</h3>
            <p class="text-xs md:text-sm text-gray-500">Oleh-oleh autentik UMKM</p>
        </div>
        <a href="<?php echo home_url('/produk'); ?>" class="text-primary text-xs font-bold hover:underline">Lihat Semua</a>
    </div>

    <div id="produk-container" class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-6">
        <?php foreach($list_produk as $p): ?>
            <!-- PANGGIL CARD PRODUK -->
            <?php get_template_part('template-parts/card', 'produk', array('item' => $p)); ?>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
    }
});

// Re-init Add to Cart listener for front page items (karena dipanggil via template part)
jQuery(document).ready(function($) {
    $(document).on('click', '.btn-add-to-cart', function(e) {
        e.preventDefault();
        var btn = $(this);
        var originalIcon = btn.html();
        var isCustom = btn.data('is-custom') ? 1 : 0;
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin text-xs"></i>');
        
        $.post(dw_ajax.ajax_url, {
            action: 'dw_theme_add_to_cart',
            nonce: dw_ajax.nonce,
            product_id: btn.data('product-id'),
            quantity: 1,
            is_custom_db: isCustom
        }, function(response) {
            if(response.success) {
                btn.html('<i class="fas fa-check text-xs"></i>').addClass('bg-green-600 text-white').removeClass('bg-gray-50 text-gray-600 hover:bg-orange-500');
                if(response.data.count) {
                    $('#header-cart-count, #header-cart-count-mobile').text(response.data.count).removeClass('hidden').addClass('flex');
                }
                setTimeout(function() {
                    btn.html(originalIcon).removeClass('bg-green-600 text-white').addClass('bg-gray-50 text-gray-600 hover:bg-orange-500').prop('disabled', false);
                }, 2000);
            } else {
                alert('Gagal: ' + (response.data.message || 'Error'));
                btn.html(originalIcon).prop('disabled', false);
            }
        });
    });
});
</script>

<?php get_footer(); ?>