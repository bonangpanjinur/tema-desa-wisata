<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-gray-100 flex justify-center min-h-screen text-slate-800 font-sans'); ?>>
    
    <!-- Mobile Frame (Max 480px) -->
    <div id="app-frame" class="w-full max-w-[480px] bg-white min-h-screen relative shadow-2xl flex flex-col overflow-x-hidden">
        
        <?php 
        // Logic Header Dinamis
        $is_home = is_front_page();
        $page_title = is_archive() ? get_the_archive_title() : get_the_title();
        if (is_post_type_archive('dw_produk')) $page_title = 'Pasar Desa';
        if (is_page('wisata')) $page_title = 'Jelajah Wisata';
        ?>

        <!-- HEADER -->
        <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-gray-100 px-5 py-3 flex items-center justify-between transition-all">
            <?php if ($is_home) : ?>
                <!-- Mode Home: Lokasi & Brand -->
                <div class="flex flex-col">
                    <span class="text-[10px] text-gray-400 font-bold tracking-wide uppercase">Lokasi Anda</span>
                    <div class="flex items-center gap-1 text-primary font-bold text-sm cursor-pointer">
                        <i class="ph-fill ph-map-pin"></i>
                        <span><?php echo function_exists('dw_get_setting') ? esc_html(dw_get_setting('lokasi_utama', 'Desa Wisata')) : 'Desa Wisata'; ?></span>
                        <i class="ph-bold ph-caret-down text-xs"></i>
                    </div>
                </div>
                <div class="flex gap-3">
                    <a href="<?php echo site_url('/notifikasi'); ?>" class="relative text-gray-600 hover:text-primary"><i class="ph-bold ph-bell text-xl"></i></a>
                    <a href="<?php echo site_url('/keranjang'); ?>" class="relative text-gray-600 hover:text-primary"><i class="ph-bold ph-shopping-cart text-xl"></i></a>
                </div>
            <?php else : ?>
                <!-- Mode Halaman Lain: Tombol Back & Judul -->
                <div class="flex items-center gap-3 w-full">
                    <a href="javascript:history.back()" class="text-gray-600 hover:text-primary p-1 rounded-full active:bg-gray-100">
                        <i class="ph-bold ph-arrow-left text-xl"></i>
                    </a>
                    <h1 class="text-lg font-bold text-gray-800 truncate flex-1"><?php echo esc_html($page_title); ?></h1>
                    
                    <!-- Khusus Halaman Produk/Detail: Tampilkan Cart -->
                    <?php if (is_singular('dw_produk') || is_post_type_archive('dw_produk')) : ?>
                        <a href="<?php echo site_url('/keranjang'); ?>" class="text-gray-600 hover:text-primary"><i class="ph-bold ph-shopping-cart text-xl"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </header>

        <!-- CONTENT START -->
        <main class="flex-1 overflow-y-auto no-scrollbar pb-24 relative">