<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>

<body <?php body_class('bg-gray-50 font-sans text-gray-800'); ?>>
<?php wp_body_open(); ?>

<header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-20">
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center">
                <a href="<?php echo home_url(); ?>" class="flex items-center gap-2">
                    <?php if ( has_custom_logo() ) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <span class="text-2xl font-bold text-primary">Desa<span class="text-accent">Wisata</span></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Desktop Menu -->
            <nav class="hidden md:flex space-x-8">
                <a href="<?php echo home_url(); ?>" class="text-gray-600 hover:text-primary font-medium transition">Beranda</a>
                <a href="<?php echo home_url('/wisata'); ?>" class="text-gray-600 hover:text-primary font-medium transition">Destinasi</a>
                <a href="<?php echo home_url('/produk'); ?>" class="text-gray-600 hover:text-primary font-medium transition">Produk UMKM</a>
                <a href="<?php echo home_url('/tentang'); ?>" class="text-gray-600 hover:text-primary font-medium transition">Tentang Kami</a>
            </nav>

            <!-- User Actions -->
            <div class="hidden md:flex items-center space-x-4">
                <!-- Search Icon -->
                <button class="text-gray-500 hover:text-primary">
                    <i class="fas fa-search text-xl"></i>
                </button>

                <!-- Cart Icon -->
                <a href="<?php echo home_url('/cart'); ?>" class="relative text-gray-500 hover:text-primary group">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <!-- Badge Count (Update via JS nanti) -->
                    <span id="header-cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                </a>

                <?php if ( is_user_logged_in() ) : 
                    $current_user = wp_get_current_user();
                ?>
                    <!-- User Dropdown -->
                    <div class="relative group ml-4">
                        <button class="flex items-center gap-2 focus:outline-none">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center font-bold">
                                <?php echo substr($current_user->display_name, 0, 1); ?>
                            </div>
                            <span class="text-sm font-medium text-gray-700"><?php echo $current_user->display_name; ?></span>
                            <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                        </button>
                        
                        <!-- Dropdown Menu -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 border border-gray-100 hidden group-hover:block">
                            <a href="<?php echo home_url('/akun-saya'); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Akun Saya</a>
                            <?php if ( in_array( 'pedagang', (array) $current_user->roles ) ) : ?>
                                <a href="<?php echo home_url('/dashboard-toko'); ?>" class="block px-4 py-2 text-sm text-primary font-medium hover:bg-green-50">Dashboard Toko</a>
                            <?php endif; ?>
                            <a href="<?php echo wp_logout_url(home_url()); ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Keluar</a>
                        </div>
                    </div>
                <?php else : ?>
                    <div class="flex items-center space-x-2 border-l pl-4 ml-4 border-gray-200">
                        <a href="<?php echo home_url('/login'); ?>" class="text-sm font-medium text-gray-600 hover:text-primary px-3 py-2">Masuk</a>
                        <a href="<?php echo home_url('/register'); ?>" class="text-sm font-medium bg-primary text-white px-4 py-2 rounded-full hover:bg-secondary transition shadow-md hover:shadow-lg">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Button -->
            <div class="md:hidden flex items-center">
                <button id="mobile-menu-btn" class="text-gray-600 hover:text-primary focus:outline-none">
                    <i class="fas fa-bars text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Panel (Hidden by default) -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="<?php echo home_url(); ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary hover:bg-gray-50">Beranda</a>
            <a href="<?php echo home_url('/wisata'); ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary hover:bg-gray-50">Destinasi</a>
            <a href="<?php echo home_url('/produk'); ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary hover:bg-gray-50">Produk</a>
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo home_url('/akun-saya'); ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary hover:bg-gray-50">Akun Saya</a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-red-50">Keluar</a>
            <?php else: ?>
                <a href="<?php echo home_url('/login'); ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary hover:bg-gray-50">Masuk</a>
                <a href="<?php echo home_url('/register'); ?>" class="block px-3 py-2 rounded-md text-base font-medium text-primary hover:bg-green-50">Daftar Sekarang</a>
            <?php endif; ?>
        </div>
    </div>
</header>