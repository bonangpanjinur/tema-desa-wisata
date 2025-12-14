<?php
/**
 * Template Name: Profil Desa
 * Description: Menampilkan data dari tabel dw_desa
 */
get_header(); 
global $wpdb;

$desa = null;
$table_desa = $wpdb->prefix . 'dw_desa';

// Logika 1: Cek via Parameter URL ID (Dari Archive yg pake DB)
if (isset($_GET['id'])) {
    $desa_id = intval($_GET['id']);
    $desa = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE id = %d", $desa_id));
} 
// Logika 2: Cek via Single Post (Permalinks WP)
elseif (is_singular('dw_desa')) {
    $author_id = get_post_field('post_author', get_the_ID());
    $desa = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_desa WHERE id_user_desa = %d", $author_id));
}

// Fallback tampilan jika data tidak ditemukan
if (!$desa) {
    echo '<div class="container py-20 text-center text-gray-500">Data Desa tidak ditemukan dalam database.</div>';
    get_footer(); exit;
}
?>

<!-- Hero Header Desa -->
<div class="relative bg-gray-900 h-[300px] md:h-[400px]">
    <?php $bg_img = !empty($desa->foto) ? $desa->foto : 'https://via.placeholder.com/1200x600'; ?>
    <img src="<?php echo esc_url($bg_img); ?>" class="w-full h-full object-cover opacity-40">
    <div class="absolute inset-0 flex items-center justify-center">
        <div class="text-center text-white px-4">
            <h1 class="text-3xl md:text-5xl font-bold mb-2"><?php echo esc_html($desa->nama_desa); ?></h1>
            <p class="text-lg opacity-90"><i class="fas fa-map-marker-alt text-red-500 mr-2"></i><?php echo esc_html($desa->kabupaten . ', ' . $desa->provinsi); ?></p>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-10 -mt-20 relative z-10">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Sidebar Info -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-100 sticky top-24">
                <h3 class="font-bold text-gray-800 text-lg mb-4 border-b pb-2">Informasi Desa</h3>
                <div class="space-y-4 text-sm text-gray-600">
                    <div>
                        <span class="block font-bold text-gray-700">Kecamatan</span>
                        <?php echo esc_html($desa->kecamatan); ?>
                    </div>
                    <div>
                        <span class="block font-bold text-gray-700">Kelurahan</span>
                        <?php echo esc_html($desa->kelurahan); ?>
                    </div>
                    <div>
                        <span class="block font-bold text-gray-700">Alamat Lengkap</span>
                        <?php echo esc_html($desa->alamat_lengkap); ?>
                    </div>
                    <hr class="border-gray-100">
                    <div>
                        <span class="block font-bold text-gray-700">Bergabung Sejak</span>
                        <?php echo date('d M Y', strtotime($desa->created_at)); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Tentang Desa</h2>
                <div class="prose max-w-none text-gray-600 leading-relaxed">
                    <?php echo wpautop(esc_html($desa->deskripsi)); ?>
                </div>
            </div>

            <!-- Produk Desa (Query Related) -->
            <div>
                <h3 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
                    <i class="fas fa-box-open text-primary"></i> Produk UMKM Desa Ini
                </h3>
                
                <?php
                // Ambil produk yg authornya adalah pedagang di desa ini
                // Query complex: Cari pedagang di tabel dw_pedagang where id_desa = desa->id, lalu ambil post produk mereka
                // Simplifikasi: Ambil semua produk where meta dw_lokasi LIKE nama desa (jika sistem meta sync)
                // Atau tampilkan placeholder jika belum ada relasi advance
                ?>
                <div class="p-6 bg-blue-50 rounded-xl border border-blue-100 text-blue-800 text-center">
                    <p>Jelajahi produk-produk unggulan dari <?php echo esc_html($desa->nama_desa); ?> di halaman Produk.</p>
                    <a href="<?php echo home_url('/produk'); ?>" class="inline-block mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-blue-700">Lihat Produk</a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php get_footer(); ?>