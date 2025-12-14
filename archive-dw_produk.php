<?php
/**
 * Template Name: Arsip Produk Sadesa
 */
get_header();

$search = get_query_var('s') ?: '';
$page = get_query_var('paged') ?: 1;
$endpoint = '/produk?per_page=10&page=' . $page . ($search ? '&s='.urlencode($search) : '');

$api_data = function_exists('dw_fetch_api_data') ? dw_fetch_api_data('/wp-json/dw/v1'.$endpoint) : [];
$products = $api_data['data'] ?? [];
$total_pages = $api_data['total_pages'] ?? 1;
?>

<div class="bg-gray-50 min-h-screen pb-20">
    <!-- Header -->
    <div class="bg-white pt-4 pb-6 rounded-b-3xl shadow-sm sticky top-[60px] z-30">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Oleh-Oleh Desa</h1>
                    <p class="text-sm text-gray-500">Produk UMKM asli buatan warga</p>
                </div>
                <form class="relative w-full md:w-64">
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Cari produk..." class="w-full pl-10 pr-4 py-2.5 bg-gray-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-primary focus:bg-white transition">
                    <i class="fas fa-search absolute left-3.5 top-3 text-gray-400"></i>
                </form>
            </div>
        </div>
    </div>

    <!-- Grid -->
    <div class="container mx-auto px-4 py-6">
        <?php if(!empty($products)): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <?php foreach($products as $p): 
                    $id = $p['id'];
                    $img = $p['thumbnail'] ?? 'https://via.placeholder.com/300';
                    $title = $p['nama_produk'] ?? 'Produk';
                    $shop = $p['nama_toko'] ?? 'UMKM';
                    $price = number_format($p['harga_dasar'] ?? 0, 0, ',', '.');
                    // Fallback link detail
                    $link = get_permalink($id) ?: home_url('/?p='.$id.'&post_type=dw_produk');
                ?>
                <div class="card-sadesa group relative">
                    <a href="<?php echo esc_url($link); ?>" class="flex flex-col h-full">
                        <div class="card-img-wrap aspect-square bg-gray-100">
                            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($title); ?>">
                        </div>
                        <div class="card-body p-3">
                            <h4 class="text-sm font-bold text-gray-800 leading-snug mb-1 line-clamp-2 min-h-[2.5em] group-hover:text-primary transition"><?php echo esc_html($title); ?></h4>
                            <div class="flex items-center gap-1 mb-2 text-[10px] text-gray-500">
                                <i class="fas fa-store"></i> <span class="truncate"><?php echo esc_html($shop); ?></span>
                            </div>
                            <div class="mt-auto pt-2 border-t border-dashed border-gray-100 flex justify-between items-end">
                                <div>
                                    <p class="text-primary font-bold text-sm">Rp <?php echo $price; ?></p>
                                    <p class="text-[9px] text-gray-400">Terjual 0</p>
                                </div>
                            </div>
                        </div>
                    </a>
                    <!-- Add to Cart -->
                    <button class="btn-add-cart absolute bottom-3 right-3 shadow-sm z-10"
                            data-id="<?php echo $id; ?>" data-title="<?php echo esc_attr($title); ?>" data-price="<?php echo $p['harga_dasar']; ?>" data-thumb="<?php echo esc_url($img); ?>">
                        <i class="fas fa-cart-plus text-xs"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-8 gap-2">
                    <?php if($page > 1): ?><a href="?paged=<?php echo $page-1; ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border hover:border-primary"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
                    <span class="px-4 h-10 flex items-center justify-center bg-primary text-white rounded-lg font-bold text-sm"><?php echo $page; ?></span>
                    <?php if($page < $total_pages): ?><a href="?paged=<?php echo $page+1; ?>" class="w-10 h-10 flex items-center justify-center bg-white rounded-lg border hover:border-primary"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center py-20 text-gray-400">
                <i class="fas fa-box-open text-5xl mb-4 opacity-30"></i>
                <p>Belum ada produk ditemukan.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>