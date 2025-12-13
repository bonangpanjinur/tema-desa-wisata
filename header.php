<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tambahkan Google Font Inter agar mirip -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body <?php body_class('bg-gray-50 text-gray-800'); ?>>

    <!-- Main Wrapper -->
    <div id="app-wrapper" class="min-h-screen flex flex-col relative">
        
        <!-- HEADER (Warna Hijau Cerah Sesuai Gambar) -->
        <header class="bg-[#00BA61] text-white shadow-sm sticky top-0 z-50">
            <div class="container mx-auto px-4 h-16 md:h-20 flex items-center gap-4 md:gap-8 justify-between">
                
                <!-- 1. LOGO (Kiri) -->
                <a href="<?php echo home_url(); ?>" class="flex items-center gap-2 hover:opacity-90 transition shrink-0">
                    <!-- Icon Daun Kuning -->
                    <i class="fas fa-leaf text-yellow-300 text-2xl"></i>
                    <!-- Nama Brand Putih Tebal -->
                    <span class="font-bold text-xl md:text-2xl tracking-tight">Sadesa</span>
                </a>

                <!-- 2. SEARCH BAR (Tengah - Lebar) -->
                <div class="hidden md:block flex-1 max-w-2xl">
                    <form action="<?php echo home_url('/'); ?>" method="get" class="relative w-full">
                        <input type="text" name="s" 
                               placeholder="Cari wisata, produk, atau desa..." 
                               class="w-full h-11 pl-12 pr-4 rounded-lg text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-green-300 shadow-sm bg-white border-none placeholder-gray-400">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-lg"></i>
                    </form>
                </div>

                <!-- 3. MENU NAVIGASI (Kanan) -->
                <nav class="flex items-center gap-6">
                    <!-- Menu Desktop -->
                    <div class="hidden md:flex items-center gap-6 font-medium text-sm">
                        <a href="<?php echo home_url(); ?>" class="hover:text-green-100 transition">Beranda</a>
                        <a href="<?php echo home_url('/wisata'); ?>" class="hover:text-green-100 transition">Wisata</a>
                        <a href="<?php echo home_url('/produk'); ?>" class="hover:text-green-100 transition">Produk</a>
                    </div>
                    
                    <!-- Icons (Cart & Akun) -->
                    <div class="flex items-center gap-4">
                        <a href="<?php echo home_url('/cart'); ?>" class="relative hover:text-green-100 transition">
                            <i class="fas fa-shopping-cart text-xl"></i>
                            <!-- Cart Badge Logic -->
                            <span id="header-cart-count" class="absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold hidden">0</span>
                        </a>
                        
                        <?php if (is_user_logged_in()): ?>
                            <a href="<?php echo home_url('/akun-saya'); ?>" class="flex items-center gap-2 bg-white/20 px-3 py-1.5 rounded-full hover:bg-white/30 transition backdrop-blur-sm">
                                <i class="fas fa-user-circle text-lg"></i>
                                <span class="text-sm font-medium hidden md:block">Akun Saya</span>
                            </a>
                        <?php else: ?>
                             <a href="<?php echo home_url('/login'); ?>" class="flex items-center gap-2 hover:text-green-100 font-medium text-sm">
                                <i class="fas fa-user text-lg"></i>
                                <span class="hidden md:block">Masuk</span>
                             </a>
                        <?php endif; ?>
                    </div>
                </nav>

            </div>
            
            <!-- Mobile Search Bar (Muncul di bawah header utama pada mobile) -->
            <div class="md:hidden px-4 pb-3">
                <form action="<?php echo home_url('/'); ?>" method="get" class="relative w-full">
                    <input type="text" name="s" 
                           placeholder="Cari wisata, produk..." 
                           class="w-full h-10 pl-10 pr-4 rounded-lg text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-green-300 shadow-sm bg-white border-none">
                    <i class="fas fa-search absolute left-3.5 top-3 text-gray-400"></i>
                </form>
            </div>
        </header>

        <!-- MAIN CONTENT WRAPPER -->
        <main class="flex-1 w-full container mx-auto px-4 py-6 md:py-8">