<?php
/**
 * Front Page Template
 */
get_header();

global $wpdb;
$table_produk   = $wpdb->prefix . 'dw_produk';
$table_wisata   = $wpdb->prefix . 'dw_wisata';
$table_desa     = $wpdb->prefix . 'dw_desa';
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

// 1. Query Produk Unggulan (Top 8 by Terjual)
$produk_unggulan = $wpdb->get_results("
    SELECT p.*, pd.nama_toko, d.kabupaten, d.nama_desa 
    FROM $table_produk p 
    LEFT JOIN $table_pedagang pd ON p.id_pedagang = pd.id
    LEFT JOIN $table_desa d ON pd.id_desa = d.id
    WHERE p.status = 'aktif' 
    ORDER BY p.terjual DESC LIMIT 8
");

// 2. Query Wisata Populer (Top 4 by Rating)
$wisata_populer = $wpdb->get_results("
    SELECT w.*, d.kabupaten, d.nama_desa 
    FROM $table_wisata w
    LEFT JOIN $table_desa d ON w.id_desa = d.id
    WHERE w.status = 'aktif' 
    ORDER BY w.rating_avg DESC LIMIT 4
");
?>

<div class="font-sans text-gray-800 bg-[#FAFAFA] overflow-x-hidden">
    
    <!-- Hero Section -->
    <section class="relative py-20 lg:py-32 bg-orange-50 text-center overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-yellow-300/20 rounded-full blur-3xl -mr-20 -mt-20"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-orange-300/20 rounded-full blur-3xl -ml-20 -mb-20"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <span class="inline-block py-1 px-3 rounded-full bg-orange-100 text-orange-700 text-xs font-bold mb-6 tracking-wide uppercase">
                #BanggaBuatanDesa
            </span>
            <h1 class="text-4xl lg:text-6xl font-bold mb-6 text-gray-900 leading-tight">
                Selamat Datang di <span class="text-orange-600"><?php bloginfo('name'); ?></span>
            </h1>
            <p class="text-gray-600 text-lg lg:text-xl max-w-2xl mx-auto mb-10 leading-relaxed">
                Jelajahi potensi terbaik desa wisata Indonesia. Temukan produk UMKM autentik dan destinasi wisata tersembunyi dalam satu platform.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo home_url('/produk'); ?>" class="px-8 py-4 bg-orange-600 text-white rounded-full font-bold hover:bg-orange-700 shadow-lg shadow-orange-200 transition transform hover:-translate-y-1">
                    Belanja Produk
                </a>
                <a href="<?php echo home_url('/wisata'); ?>" class="px-8 py-4 bg-white text-gray-700 border border-gray-200 rounded-full font-bold hover:bg-gray-50 transition transform hover:-translate-y-1">
                    Cari Wisata
                </a>
            </div>
        </div>
    </section>

    <!-- SECTION: PRODUK UNGGULAN -->
    <section class="py-16 lg:py-24 container mx-auto px-4">
        <div class="flex flex-wrap justify-between items-end mb-10 gap-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Produk Terlaris</h2>
                <p class="text-gray-500">Oleh-oleh autentik kebanggaan desa.</p>
            </div>
            <a href="<?php echo home_url('/produk'); ?>" class="group flex items-center gap-2 text-orange-600 font-bold hover:underline">
                Lihat Semua <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
            </a>
        </div>

        <?php if ($produk_unggulan) : ?>
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                <?php foreach ($produk_unggulan as $p) : ?>
                    <!-- PANGGIL PARENT DESIGN: Card Produk -->
                    <?php get_template_part('template-parts/card', 'produk', array('item' => $p)); ?>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="text-center py-10 bg-white rounded-xl border border-dashed border-gray-300">
                <p class="text-gray-400">Belum ada produk unggulan ditampilkan.</p>
            </div>
        <?php endif; ?>
    </section>

    <!-- SECTION: WISATA POPULER -->
    <section class="py-16 lg:py-24 bg-white border-t border-gray-100">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap justify-between items-end mb-10 gap-4">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Destinasi Favorit</h2>
                    <p class="text-gray-500">Jelajahi keindahan alam dan budaya desa.</p>
                </div>
                <a href="<?php echo home_url('/wisata'); ?>" class="group flex items-center gap-2 text-green-600 font-bold hover:underline">
                    Lihat Semua <i class="fas fa-arrow-right transform group-hover:translate-x-1 transition"></i>
                </a>
            </div>

            <?php if ($wisata_populer) : ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach ($wisata_populer as $w) : ?>
                        <!-- PANGGIL PARENT DESIGN: Card Wisata -->
                        <?php get_template_part('template-parts/card', 'wisata', array('item' => $w)); ?>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <div class="text-center py-10 bg-gray-50 rounded-xl border border-dashed border-gray-300">
                    <p class="text-gray-400">Belum ada data wisata populer.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-20 bg-gray-900 text-white text-center relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('https://source.unsplash.com/1600x900/?village,indonesia')] bg-cover bg-center opacity-20"></div>
        <div class="container mx-auto px-4 relative z-10">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Bergabunglah Membangun Desa</h2>
            <p class="text-gray-400 max-w-xl mx-auto mb-10 text-lg">
                Daftarkan desa atau UMKM Anda sekarang dan jangkau pasar yang lebih luas melalui platform digital terintegrasi.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo home_url('/daftar-desa'); ?>" class="px-8 py-4 bg-orange-600 rounded-full font-bold hover:bg-orange-700 transition">Daftar Desa</a>
                <a href="<?php echo home_url('/daftar-pedagang'); ?>" class="px-8 py-4 bg-transparent border border-white rounded-full font-bold hover:bg-white hover:text-gray-900 transition">Daftar Pedagang</a>
            </div>
        </div>
    </section>

</div>

<?php get_footer(); ?>