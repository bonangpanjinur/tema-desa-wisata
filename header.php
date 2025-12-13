<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-gray-200 min-h-screen flex justify-center items-start pt-0 sm:pt-10 pb-0 sm:pb-10'); ?>>

    <!-- Mobile Container Wrapper -->
    <!-- Ini membuat tampilan seperti HP meskipun di Desktop -->
    <div id="app-wrapper" class="w-full sm:max-w-md bg-gray-50 min-h-screen sm:min-h-[800px] sm:h-[850px] sm:rounded-[2rem] shadow-2xl relative overflow-hidden flex flex-col">
        
        <!-- TOP HEADER & SEARCH (Sticky) -->
        <header class="bg-emerald-600 text-white p-4 sticky top-0 z-40 rounded-b-xl shadow-md">
            <div class="flex justify-between items-center mb-3">
                <a href="<?php echo home_url(); ?>" class="flex items-center gap-2 hover:opacity-90 transition">
                    <i class="fas fa-leaf text-yellow-300 text-xl"></i>
                    <h1 class="font-bold text-lg tracking-wide"><?php bloginfo('name'); ?></h1>
                </a>
                <div class="flex gap-3">
                    <a href="<?php echo home_url('/cart'); ?>" class="relative">
                        <i class="fas fa-shopping-cart text-lg"></i>
                        <?php 
                        // Cek jumlah cart (Logic sederhana, nanti diganti dengan fungsi cart plugin)
                        $cart_count = 0; 
                        if ($cart_count > 0): 
                        ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-xs rounded-full w-4 h-4 flex items-center justify-center"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                    <button><i class="fas fa-bell text-lg"></i></button>
                </div>
            </div>
            
            <!-- Search Bar -->
            <form action="<?php echo home_url('/'); ?>" method="get" class="relative">
                <input type="text" name="s" placeholder="Cari wisata, produk, atau desa..." class="w-full py-2.5 pl-10 pr-4 rounded-lg text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 shadow-sm">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            </form>
        </header>

        <!-- MAIN SCROLLABLE CONTENT START -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden pb-24 scroll-smooth">