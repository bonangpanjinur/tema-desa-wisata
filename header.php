<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-gray-100 font-inter text-gray-800'); ?>>

    <!-- Main Wrapper: Full Width on Desktop, Standard on Mobile -->
    <div id="app-wrapper" class="min-h-screen flex flex-col relative">
        
        <!-- TOP HEADER & SEARCH (Sticky) -->
        <header class="bg-emerald-600 text-white shadow-md sticky top-0 z-50">
            <div class="container mx-auto px-4 py-3">
                <div class="flex justify-between items-center gap-4">
                    
                    <!-- Logo & Brand -->
                    <a href="<?php echo home_url(); ?>" class="flex items-center gap-2 hover:opacity-90 transition shrink-0">
                        <i class="fas fa-leaf text-yellow-300 text-2xl"></i>
                        <span class="font-bold text-xl tracking-wide hidden md:block"><?php bloginfo('name'); ?></span>
                        <span class="font-bold text-lg tracking-wide md:hidden">DesaWisata</span>
                    </a>

                    <!-- Search Bar (Flexible Width) -->
                    <div class="flex-1 max-w-2xl mx-auto">
                        <form action="<?php echo home_url('/'); ?>" method="get" class="relative">
                            <input type="text" name="s" placeholder="Cari wisata, produk, atau desa..." class="w-full py-2.5 pl-10 pr-4 rounded-lg text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 shadow-inner bg-emerald-50 focus:bg-white transition">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                        </form>
                    </div>

                    <!-- Desktop Navigation (Hidden on Mobile) -->
                    <nav class="hidden md:flex items-center gap-6">
                        <a href="<?php echo home_url(); ?>" class="text-sm font-medium hover:text-emerald-100 transition">Beranda</a>
                        <a href="<?php echo home_url('/wisata'); ?>" class="text-sm font-medium hover:text-emerald-100 transition">Wisata</a>
                        <a href="<?php echo home_url('/produk'); ?>" class="text-sm font-medium hover:text-emerald-100 transition">Produk</a>
                        <div class="h-4 w-px bg-emerald-500"></div>
                        <a href="<?php echo home_url('/cart'); ?>" class="relative hover:text-emerald-100 transition">
                            <i class="fas fa-shopping-cart text-lg"></i>
                            <!-- Cart Count Logic Here -->
                        </a>
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo home_url('/akun-saya'); ?>" class="flex items-center gap-2 bg-emerald-700 px-3 py-1.5 rounded-full hover:bg-emerald-800 transition">
                                <i class="fas fa-user-circle"></i>
                                <span class="text-sm">Akun Saya</span>
                            </a>
                        <?php else: ?>
                             <a href="<?php echo home_url('/login'); ?>" class="text-sm font-bold bg-white text-emerald-600 px-4 py-1.5 rounded-lg hover:bg-gray-100 transition">Masuk</a>
                        <?php endif; ?>
                    </nav>

                    <!-- Mobile Icons (Hidden on Desktop) -->
                    <div class="flex gap-4 md:hidden">
                        <a href="<?php echo home_url('/cart'); ?>" class="relative">
                            <i class="fas fa-shopping-cart text-lg"></i>
                        </a>
                        <button><i class="fas fa-bell text-lg"></i></button>
                    </div>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT WRAPPER -->
        <main class="flex-1 w-full container mx-auto px-4 py-6 md:py-8">