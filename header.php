<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class('bg-gray-50 font-sans text-gray-800'); ?>>
<?php wp_body_open(); ?>

<header class="bg-white shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            
            <!-- Logo -->
            <div class="flex-shrink-0 flex items-center">
                <?php if (has_custom_logo()): ?>
                    <?php the_custom_logo(); ?>
                <?php else: ?>
                    <a href="<?php echo home_url(); ?>" class="text-2xl font-bold text-primary">
                        <?php bloginfo('name'); ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Desktop Menu -->
            <nav class="hidden md:flex space-x-8 items-center">
                <a href="<?php echo home_url(); ?>" class="text-gray-600 hover:text-primary transition font-medium">Beranda</a>
                <a href="<?php echo home_url('/wisata'); // Archive Wisata ?>" class="text-gray-600 hover:text-primary transition font-medium">Wisata</a>
                <a href="<?php echo home_url('/produk'); // Archive Produk ?>" class="text-gray-600 hover:text-primary transition font-medium">Produk Desa</a>
                <a href="<?php echo home_url('/tentang'); // Page Tentang ?>" class="text-gray-600 hover:text-primary transition font-medium">Tentang</a>
                
                <!-- Logic Login/Akun -->
                <?php if (is_user_logged_in()): ?>
                    <?php 
                        $current_user = wp_get_current_user();
                        $dashboard_url = home_url('/akun-saya');
                        if(in_array('pengelola_desa', $current_user->roles)) $dashboard_url = home_url('/dashboard-desa');
                        if(in_array('pedagang', $current_user->roles)) $dashboard_url = home_url('/dashboard-toko');
                        if(in_array('ojek_wisata', $current_user->roles)) $dashboard_url = home_url('/dashboard-ojek');
                    ?>
                    <a href="<?php echo $dashboard_url; ?>" class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-sm font-semibold hover:bg-blue-200 transition">
                        Hi, <?php echo $current_user->display_name; ?>
                    </a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="text-red-500 hover:text-red-700 text-sm font-medium">Logout</a>
                <?php else: ?>
                    <a href="<?php echo home_url('/login'); ?>" class="text-gray-600 hover:text-primary transition font-medium">Login</a>
                    <a href="<?php echo home_url('/register'); ?>" class="bg-primary text-white px-5 py-2 rounded-lg hover:bg-green-700 transition shadow-sm">Daftar</a>
                <?php endif; ?>

                <!-- Cart Icon -->
                <a href="<?php echo home_url('/cart'); ?>" class="relative text-gray-600 hover:text-primary">
                    <i class="fas fa-shopping-cart text-xl"></i>
                    <?php 
                        $cart_count = isset($_SESSION['dw_cart']) ? count($_SESSION['dw_cart']) : 0;
                        if($cart_count > 0):
                    ?>
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </nav>

            <!-- Mobile Menu Button -->
            <div class="md:hidden flex items-center">
                <button id="mobile-menu-btn" class="text-gray-600 hover:text-primary focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Dropdown -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100 pb-4 shadow-lg">
        <a href="<?php echo home_url(); ?>" class="block py-2 px-4 text-gray-700 hover:bg-gray-50">Beranda</a>
        <a href="<?php echo home_url('/wisata'); ?>" class="block py-2 px-4 text-gray-700 hover:bg-gray-50">Wisata</a>
        <a href="<?php echo home_url('/produk'); ?>" class="block py-2 px-4 text-gray-700 hover:bg-gray-50">Produk Desa</a>
        <a href="<?php echo home_url('/tentang'); ?>" class="block py-2 px-4 text-gray-700 hover:bg-gray-50">Tentang</a>
        <div class="border-t border-gray-100 my-2"></div>
        <?php if (is_user_logged_in()): ?>
            <a href="<?php echo $dashboard_url; ?>" class="block py-2 px-4 text-blue-600 font-semibold">Dashboard</a>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="block py-2 px-4 text-red-500">Logout</a>
        <?php else: ?>
            <a href="<?php echo home_url('/login'); ?>" class="block py-2 px-4 text-gray-700 hover:bg-gray-50">Login</a>
            <a href="<?php echo home_url('/register'); ?>" class="block py-2 px-4 text-primary font-semibold">Daftar</a>
        <?php endif; ?>
    </div>

    <script>
        // Simple Toggle Script for Tailwind
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });
    </script>
</header>