<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-gray-50 text-slate-800 font-sans antialiased'); ?>>
    
    <!-- Wrapper Utama: Full Width Responsive -->
    <div id="page-wrapper" class="flex flex-col min-h-screen">
        
        <?php 
        // Data Branding dari Plugin
        $brand_name = function_exists('dw_get_setting') ? dw_get_setting('nama_website', get_bloginfo('name')) : get_bloginfo('name');
        $brand_logo = function_exists('dw_get_setting') ? dw_get_setting('logo_frontend', '') : '';
        ?>

        <!-- HEADER RESPONSIVE -->
        <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-gray-200 shadow-sm transition-all">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                
                <!-- Logo Area -->
                <a href="<?php echo home_url(); ?>" class="flex items-center gap-3 group">
                    <?php if ( ! empty( $brand_logo ) ) : ?>
                        <img src="<?php echo esc_url( $brand_logo ); ?>" alt="<?php echo esc_attr( $brand_name ); ?>" class="h-10 w-auto object-contain transition-transform group-hover:scale-105">
                    <?php else : ?>
                        <div class="flex flex-col">
                            <span class="text-[10px] text-gray-500 font-bold tracking-wider uppercase leading-none mb-0.5">Desa Wisata</span>
                            <span class="text-primary font-extrabold text-xl leading-none tracking-tight group-hover:text-secondary transition-colors">
                                <?php echo esc_html( $brand_name ); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </a>

                <!-- Desktop Navigation (Hidden on Mobile) -->
                <nav class="hidden md:flex items-center gap-8">
                    <?php
                    $menu_items = [
                        'Beranda' => home_url(),
                        'Jelajah Wisata' => site_url('/wisata'),
                        'Pasar Desa' => function_exists('get_post_type_archive_link') ? get_post_type_archive_link('dw_produk') : site_url('/produk'),
                        'Tentang' => site_url('/tentang'),
                    ];
                    foreach($menu_items as $label => $link):
                        // Cek aktif sederhana
                        $active_class = (get_permalink() == $link || (is_home() && $label == 'Beranda')) ? 'text-primary font-bold' : 'text-gray-600 hover:text-primary font-medium';
                    ?>
                        <a href="<?php echo esc_url($link); ?>" class="text-sm transition-colors <?php echo $active_class; ?>">
                            <?php echo $label; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <!-- Header Actions -->
                <div class="flex items-center gap-3 sm:gap-4">
                    <!-- Cart Icon -->
                    <a href="<?php echo site_url('/keranjang'); ?>" class="relative p-2 text-gray-600 hover:text-primary transition-colors group">
                        <i class="ph-bold ph-shopping-cart text-2xl group-hover:scale-110 transition-transform"></i>
                        <span id="header-cart-count" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center border border-white transform scale-0 transition-transform duration-200">0</span>
                    </a>

                    <!-- User Menu / Login Button -->
                    <?php if (is_user_logged_in()): 
                        $current_user = wp_get_current_user();
                    ?>
                        <a href="<?php echo site_url('/akun-saya'); ?>" class="flex items-center gap-2 pl-2 border-l border-gray-200 hover:opacity-80 transition-opacity">
                            <img src="<?php echo get_avatar_url($current_user->ID); ?>" class="w-8 h-8 rounded-full border border-gray-200 object-cover">
                            <span class="hidden sm:block text-sm font-bold text-gray-700 max-w-[100px] truncate">
                                <?php echo esc_html($current_user->display_name); ?>
                            </span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo site_url('/login'); ?>" class="hidden md:inline-flex items-center justify-center bg-primary text-white px-5 py-2 rounded-full text-sm font-bold hover:bg-secondary transition-all shadow-md hover:shadow-lg transform active:scale-95">
                            Masuk
                        </a>
                        <!-- Mobile Login Icon -->
                        <a href="<?php echo site_url('/login'); ?>" class="md:hidden p-2 text-gray-600 hover:text-primary">
                            <i class="ph-bold ph-sign-in text-2xl"></i>
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </header>

        <!-- CONTENT START -->
        <main class="flex-1 w-full">