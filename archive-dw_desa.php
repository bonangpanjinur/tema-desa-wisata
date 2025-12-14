<?php
/**
 * Template Name: Archive Desa (Custom Table)
 */
get_header(); 
global $wpdb;
$tbl_desa = $wpdb->prefix . 'dw_desa';

// Query Desa Aktif
$desa_list = $wpdb->get_results("SELECT * FROM $tbl_desa WHERE status = 'aktif' ORDER BY created_at DESC");
?>

<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-gray-800">Jelajahi Desa Wisata</h1>
            <p class="text-gray-500 mt-2">Temukan keunikan desa-desa di sekitar kita</p>
        </div>

        <?php if($desa_list): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach($desa_list as $desa): 
                $img = $desa->foto ? $desa->foto : 'https://via.placeholder.com/600x400?text=Desa+Wisata';
            ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:-translate-y-1 hover:shadow-md transition duration-300">
                <div class="h-48 relative overflow-hidden">
                    <img src="<?php echo esc_url($img); ?>" class="w-full h-full object-cover">
                    <div class="absolute top-4 right-4 bg-white/90 backdrop-blur px-3 py-1 rounded-full text-xs font-bold text-gray-700 shadow-sm">
                        <i class="fas fa-map-marker-alt text-red-500 mr-1"></i> <?php echo esc_html($desa->kabupaten); ?>
                    </div>
                </div>
                <div class="p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-2"><?php echo esc_html($desa->nama_desa); ?></h2>
                    <p class="text-gray-500 text-sm line-clamp-3 mb-4">
                        <?php echo esc_html(wp_trim_words($desa->deskripsi, 20)); ?>
                    </p>
                    <a href="<?php echo home_url('/profil-desa?id=' . $desa->id); ?>" class="block w-full text-center bg-gray-50 text-primary font-bold py-2 rounded-lg hover:bg-primary hover:text-white transition">
                        Kunjungi Desa
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
            <div class="text-center py-20 bg-white rounded-xl shadow-sm">
                <p class="text-gray-400">Belum ada desa yang terdaftar.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>