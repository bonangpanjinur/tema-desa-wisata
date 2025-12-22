<!DOCTYPE html>
<html <?php language_attributes(); ?> class="scroll-smooth">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class('bg-surface font-sans text-gray-700 antialiased selection:bg-primary selection:text-white'); ?>>
<?php wp_body_open(); ?>

<!-- Navbar -->
<header class="bg-white shadow-sm sticky top-0 z-50 border-b border-gray-100">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 md:h-20">
            
            <!-- Logo Area -->
            <div class="flex-shrink-0 flex items-center gap-3">
                <!-- Mobile Menu Button (Left) -->
                <button id="mobile-menu-btn" class="md:hidden p-2 rounded-md text-gray-500 hover:text-primary hover:bg-gray-50 focus:outline-none">
                    <i class="fas fa-bars text-xl"></i>
                </button>

                <!-- Logo Image/Text -->
                <?php if (has_custom_logo()): ?>
                    <div class="w-32 md:w-40">
                        <?php the_custom_logo(); ?>
                    </div>
                <?php else: ?>
                    <a href="<?php echo home_url(); ?>" class="flex items-center gap-2 group">
                        <span class="bg-primary text-white p-2 rounded-lg group-hover:bg-primaryDark transition"><i class="fas fa-leaf"></i></span>
                        <span class="text-xl md:text-2xl font-bold text-gray-800 tracking-tight group-hover:text-primary transition"><?php bloginfo('name'); ?></span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex space-x-8 items-center">
                <?php 
                $menu_items = [
                    'Beranda' => home_url(),
                    'Wisata' => home_url('/wisata'),
                    'Produk Desa' => home_url('/produk'),
                    'Tentang' => home_url('/tentang'),
                ];
                
                foreach($menu_items as $name => $link): 
                    $active_class = (is_page($name) || (is_archive() && strpos($link, get_post_type()) !== false)) ? 'text-primary font-bold' : 'text-gray-600 font-medium hover:text-primary';
                ?>
                    <a href="<?php echo $link; ?>" class="<?php echo $active_class; ?> transition text-sm uppercase tracking-wide">
                        <?php echo $name; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Right Action Area (Cart & Auth) -->
            <div class="flex items-center gap-3 md:gap-4">
                
                <!-- Search Icon (Mobile/Desktop) -->
                <button class="text-gray-500 hover:text-primary p-2 transition">
                    <i class="fas fa-search"></i>
                </button>

                <!-- Cart Icon -->
                <a href="<?php echo home_url('/cart'); ?>" class="relative text-gray-500 hover:text-primary p-2 transition group">
                    <i class="fas fa-shopping-cart text-lg"></i>
                    <?php 
                        $cart_count = isset($_SESSION['dw_cart']) ? array_sum($_SESSION['dw_cart']) : 0;
                    ?>
                    <span id="header-cart-count" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center <?php echo $cart_count > 0 ? '' : 'hidden'; ?>">
                        <?php echo $cart_count; ?>
                    </span>
                </a>

                <!-- Divider -->
                <div class="h-6 w-px bg-gray-200 hidden md:block"></div>

                <!-- Auth Buttons -->
                <?php if (is_user_logged_in()): ?>
                    <?php 
                        $current_user = wp_get_current_user();
                        // Tentukan dashboard link
                        $dashboard_url = home_url('/akun-saya');
                        if(in_array('pengelola_desa', $current_user->roles)) $dashboard_url = home_url('/dashboard-desa');
                        if(in_array('pedagang', $current_user->roles)) $dashboard_url = home_url('/dashboard-toko');
                    ?>
                    <div class="relative group">
                        <a href="<?php echo $dashboard_url; ?>" class="hidden md:flex items-center gap-2 pl-2 pr-4 py-1.5 rounded-full border border-gray-200 hover:border-primary hover:bg-green-50 transition">
                            <img src="<?php echo get_avatar_url($current_user->ID); ?>" class="w-8 h-8 rounded-full border border-gray-300">
                            <span class="text-sm font-semibold text-gray-700 max-w-[100px] truncate"><?php echo $current_user->display_name; ?></span>
                        </a>
                        <!-- Dropdown (Simple CSS Hover) -->
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 hidden group-hover:block">
                            <a href="<?php echo $dashboard_url; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Dashboard</a>
                            <a href="<?php echo home_url('/edit-profil'); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Edit Profil</a>
                            <div class="border-t border-gray-100 my-1"></div>
                            <a href="<?php echo wp_logout_url(home_url()); ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Keluar</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="hidden md:flex items-center gap-3">
                        <a href="<?php echo home_url('/login'); ?>" class="text-sm font-bold text-gray-600 hover:text-primary">Masuk</a>
                        <a href="<?php echo home_url('/register'); ?>" class="bg-primary text-white px-5 py-2 rounded-full text-sm font-bold shadow-md hover:bg-green-700 hover:shadow-lg transition transform hover:-translate-y-0.5">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Overlay (Hidden by default) -->
    <div id="mobile-menu" class="hidden md:hidden absolute top-16 left-0 w-full bg-white shadow-lg border-t border-gray-100 z-40 transition-all duration-300">
        <div class="flex flex-col p-4 space-y-3">
            <a href="<?php echo home_url(); ?>" class="block px-4 py-3 rounded-lg hover:bg-green-50 text-gray-700 font-semibold">Beranda</a>
            <a href="<?php echo home_url('/wisata'); ?>" class="block px-4 py-3 rounded-lg hover:bg-green-50 text-gray-700 font-semibold">Wisata</a>
            <a href="<?php echo home_url('/produk'); ?>" class="block px-4 py-3 rounded-lg hover:bg-green-50 text-gray-700 font-semibold">Produk Desa</a>
            <hr class="border-gray-100">
            <?php if (!is_user_logged_in()): ?>
                <a href="<?php echo home_url('/login'); ?>" class="block px-4 py-3 text-center border border-gray-300 rounded-lg font-bold text-gray-600">Masuk</a>
                <a href="<?php echo home_url('/register'); ?>" class="block px-4 py-3 text-center bg-primary text-white rounded-lg font-bold">Daftar Sekarang</a>
            <?php else: ?>
                <a href="<?php echo $dashboard_url; ?>" class="block px-4 py-3 bg-blue-50 text-blue-700 rounded-lg font-bold text-center">Dashboard Saya</a>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="block px-4 py-2 text-center text-red-500 font-medium">Keluar</a>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Inline script untuk toggle menu agar langsung jalan
        document.getElementById('mobile-menu-btn').addEventListener('click', function() {
            var menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
</header>