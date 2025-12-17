<?php
/**
 * The template for displaying the footer
 */
?>

<!-- === FOOTER DESKTOP (Sederhana) === -->
<footer class="bg-white border-t border-gray-100 pt-10 pb-24 md:pb-10 mt-auto">
    <div class="container mx-auto px-4">
        <div class="text-center text-gray-500 text-sm">
            &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. All rights reserved.
        </div>
    </div>
</footer>

<!-- === MOBILE BOTTOM NAVIGATION (FIXED) === -->
<div class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-50 pb-safe">
    <div class="grid grid-cols-5 h-16">
        <!-- Home -->
        <a href="<?php echo home_url('/'); ?>" class="flex flex-col items-center justify-center text-gray-400 hover:text-green-600 <?php echo is_front_page() ? 'text-green-600' : ''; ?>">
            <i class="fas fa-home text-lg mb-1"></i>
            <span class="text-[10px] font-medium">Beranda</span>
        </a>

        <!-- Wisata -->
        <a href="<?php echo home_url('/wisata'); ?>" class="flex flex-col items-center justify-center text-gray-400 hover:text-green-600 <?php echo is_page('wisata') || is_post_type_archive('dw_wisata') ? 'text-green-600' : ''; ?>">
            <i class="fas fa-compass text-lg mb-1"></i>
            <span class="text-[10px] font-medium">Wisata</span>
        </a>

        <!-- Tengah (Cart/Scan) -->
        <div class="relative flex justify-center items-end pb-2">
            <a href="<?php echo home_url('/cart'); ?>" class="absolute -top-5 w-14 h-14 bg-green-600 rounded-full flex items-center justify-center text-white shadow-lg shadow-green-200 border-4 border-white transform transition active:scale-95">
                <i class="fas fa-shopping-basket text-xl"></i>
                <?php 
                $cart_count = function_exists('dw_get_cart_count') ? dw_get_cart_count() : 0;
                if($cart_count > 0): 
                ?>
                <span class="absolute top-0 right-0 w-4 h-4 bg-red-500 text-white text-[9px] font-bold flex items-center justify-center rounded-full border-2 border-green-600">
                    <?php echo $cart_count; ?>
                </span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Favorit (Pengganti Transaksi) -->
        <a href="<?php echo home_url('/favorit'); ?>" class="flex flex-col items-center justify-center text-gray-400 hover:text-green-600 <?php echo get_query_var('dw_is_favorit') ? 'text-green-600' : ''; ?>">
            <i class="fas fa-heart text-lg mb-1"></i>
            <span class="text-[10px] font-medium">Favorit</span>
        </a>

        <!-- Akun -->
        <a href="<?php echo home_url('/akun-saya'); ?>" class="flex flex-col items-center justify-center text-gray-400 hover:text-green-600 <?php echo is_page('akun-saya') ? 'text-green-600' : ''; ?>">
            <i class="fas fa-user text-lg mb-1"></i>
            <span class="text-[10px] font-medium">Akun</span>
        </a>
    </div>
</div>

<style>
/* Safe area for iPhone home bar */
.pb-safe { padding-bottom: env(safe-area-inset-bottom); }
</style>

<?php wp_footer(); ?>
</body>
</html>