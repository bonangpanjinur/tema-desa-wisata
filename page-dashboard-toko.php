<?php
/**
 * Template Name: Single Produk App Style
 */
get_header();

while ( have_posts() ) : the_post();
    $product_id = get_the_ID();
    
    // Ambil Data dari Plugin
    $price = get_post_meta( $product_id, '_dw_harga_dasar', true );
    $stock = get_post_meta( $product_id, '_dw_stok', true );
    $gallery = get_post_meta( $product_id, '_dw_galeri_foto', true ); // ID dipisah koma
    
    // Data Penjual
    $author_id = get_the_author_meta('ID');
    $author_name = get_the_author_meta('display_name');
    
    // Cek apakah ada galeri
    $gallery_ids = !empty($gallery) ? explode(',', $gallery) : [];
    $main_image = get_the_post_thumbnail_url($product_id, 'full');
?>

<div id="product-detail" class="h-full bg-white relative flex flex-col pb-24">
    
    <!-- Navbar Overlay Absolute -->
    <div class="absolute top-0 left-0 right-0 p-5 flex justify-between items-center z-20">
        <a href="javascript:history.back()" class="w-10 h-10 bg-white/70 backdrop-blur-md rounded-full flex items-center justify-center text-dark shadow-sm">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <a href="<?php echo home_url('/keranjang/'); ?>" class="w-10 h-10 bg-white/70 backdrop-blur-md rounded-full flex items-center justify-center text-dark shadow-sm relative">
            <i class="fa-solid fa-bag-shopping"></i>
            <?php 
                $cart = get_user_meta(get_current_user_id(), '_dw_cart_items', true);
                if(is_array($cart) && count($cart) > 0) echo '<span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full"></span>';
            ?>
        </a>
    </div>

    <!-- Image Area (Full Bleed Top) -->
    <div class="h-[45vh] bg-gray-100 relative overflow-hidden">
        <img src="<?php echo $main_image ? esc_url($main_image) : 'https://via.placeholder.com/600'; ?>" class="w-full h-full object-cover">
    </div>

    <!-- Details Sheet -->
    <div class="flex-1 bg-white -mt-8 rounded-t-[2rem] relative z-10 flex flex-col shadow-[0_-5px_20px_rgba(0,0,0,0.05)]">
        <div class="p-6">
            <!-- Handle -->
            <div class="w-12 h-1.5 bg-gray-200 rounded-full mx-auto mb-6"></div>

            <!-- Title & Price -->
            <div class="flex justify-between items-start mb-4">
                <div class="flex-1 pr-4">
                    <span class="text-[10px] font-bold text-blue-500 bg-blue-50 px-2 py-1 rounded-md uppercase tracking-wider mb-2 inline-block">Produk Desa</span>
                    <h1 class="text-xl font-bold text-dark leading-snug"><?php the_title(); ?></h1>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-primary">Rp <?php echo number_format((float)$price, 0, ',', '.'); ?></div>
                    <div class="text-[10px] text-gray-400">Stok: <?php echo esc_html($stock); ?></div>
                </div>
            </div>

            <!-- Rating Mockup -->
            <div class="flex items-center gap-4 mb-6 border-b border-gray-50 pb-6">
                <div class="flex items-center gap-1">
                    <i class="fa-solid fa-star text-yellow-400 text-sm"></i>
                    <span class="font-bold text-sm text-dark">4.8</span>
                    <span class="text-xs text-subtle">(Ulasan)</span>
                </div>
            </div>

            <!-- Penjual -->
            <div class="flex items-center gap-3 p-3 rounded-2xl bg-surface border border-gray-100 mb-6">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                    <?php echo substr($author_name, 0, 1); ?>
                </div>
                <div class="flex-1">
                    <h4 class="font-bold text-sm text-dark"><?php echo esc_html($author_name); ?></h4>
                    <p class="text-xs text-subtle">Penjual Terverifikasi</p>
                </div>
                <button class="text-xs font-bold text-primary border border-primary/20 px-3 py-1.5 rounded-full hover:bg-primary hover:text-white transition">Lihat Toko</button>
            </div>

            <!-- Deskripsi -->
            <div class="mb-6">
                <h3 class="text-sm font-bold text-dark mb-2">Deskripsi</h3>
                <div class="text-sm text-gray-500 leading-relaxed">
                    <?php the_content(); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Action Bar (Sticky) -->
    <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-50 flex gap-3 pb-6 z-50 max-w-[480px] mx-auto">
        <a href="#" class="w-12 h-12 flex items-center justify-center rounded-2xl border border-gray-200 text-subtle hover:text-primary hover:border-primary transition">
            <i class="fa-regular fa-comment-dots text-lg"></i>
        </a>
        
        <!-- Form Add to Cart (AJAX) -->
        <div class="flex-1 flex gap-3">
            <input type="hidden" id="qty" value="1">
            <button id="btn-add-to-cart" data-product-id="<?php echo $product_id; ?>" class="flex-1 bg-primaryLight text-primary font-bold rounded-2xl text-sm hover:bg-emerald-200 transition py-3">
                + Keranjang
            </button>
            <a href="#" class="flex-1 bg-primary text-white font-bold rounded-2xl text-sm shadow-glow hover:bg-emerald-700 transition flex items-center justify-center">
                Beli Langsung
            </a>
        </div>
    </div>
    
    <!-- Toast Notification Container -->
    <div id="cart-message" style="display:none; position: fixed; top: 100px; left: 50%; transform: translateX(-50%); z-index: 100; background: rgba(0,0,0,0.8); color: white; padding: 10px 20px; border-radius: 20px; font-size: 12px;"></div>

</div>

<?php endwhile; get_footer(); ?>