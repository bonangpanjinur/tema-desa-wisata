<?php
/**
 * Logika Fetch Data Header dari API / DB
 */
$brand_name = get_bloginfo( 'name' );
$current_user = wp_get_current_user();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- Font Inter & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#00BA61', 
                        'primary-dark': '#00964E',
                        'primary-light': '#E0F7EB',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        /* Sembunyikan Scrollbar Horizontal tapi tetap bisa swipe */
        .hide-scroll::-webkit-scrollbar { display: none; }
        .hide-scroll { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-gray-50 text-gray-800 font-sans pb-16 md:pb-0'); // Add padding bottom for mobile nav ?>>

    <!-- Main Wrapper -->
    <div id="app-wrapper" class="min-h-screen flex flex-col relative">
        
        <!-- HEADER UTAMA (Sticky) -->
        <header class="bg-primary text-white shadow-sm sticky top-0 z-50 transition-colors duration-200">
            <div class="container mx-auto px-4 h-16 md:h-20 flex items-center justify-between">
                
                <!-- === MOBILE LAYOUT (Cart - Logo - Search) === -->
                <!-- Layout ini HANYA muncul di Mobile (md:hidden) -->
                <div class="md:hidden flex items-center justify-between w-full relative">
                    
                    <!-- 1. CART (Kiri) -->
                    <a href="<?php echo home_url('/cart'); ?>" class="relative p-2 -ml-2">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="header-cart-count-mobile" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold border border-white hidden">0</span>
                    </a>

                    <!-- 2. LOGO (Tengah - Absolute Center) -->
                    <a href="<?php echo home_url(); ?>" class="absolute left-1/2 transform -translate-x-1/2 flex items-center gap-2 font-bold text-lg tracking-tight">
                        <i class="fas fa-leaf text-yellow-300"></i>
                        <span><?php echo esc_html($brand_name); ?></span>
                    </a>

                    <!-- 3. SEARCH (Kanan) -->
                    <button onclick="document.getElementById('mobile-search-bar').classList.toggle('hidden')" class="p-2 -mr-2">
                        <i class="fas fa-search text-xl"></i>
                    </button>
                    
                    <!-- NOTE: Tidak ada Icon Akun di sini untuk Mobile -->
                </div>

                <!-- === DESKTOP LAYOUT === -->
                <!-- Elemen ini HANYA muncul di Desktop (hidden md:flex) -->
                
                <!-- Logo Desktop -->
                <a href="<?php echo home_url(); ?>" class="hidden md:flex items-center gap-2 hover:opacity-90 transition shrink-0">
                    <i class="fas fa-leaf text-yellow-300 text-2xl"></i>
                    <span class="font-bold text-2xl tracking-tight"><?php echo esc_html($brand_name); ?></span>
                </a>

                <!-- Search Desktop -->
                <div class="hidden md:block flex-1 max-w-2xl mx-auto px-8">
                    <form action="<?php echo home_url('/'); ?>" method="get" class="relative w-full group">
                        <input type="text" name="s" placeholder="Cari wisata atau produk..." class="w-full h-11 pl-12 pr-4 rounded-xl text-gray-700 text-sm focus:outline-none focus:ring-4 focus:ring-white/20 shadow-sm bg-white border-none transition-shadow">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-lg group-focus-within:text-primary transition-colors"></i>
                    </form>
                </div>

                <!-- Nav Desktop & Akun -->
                <div class="hidden md:flex items-center gap-6">
                    <nav class="flex items-center gap-6 font-medium text-sm">
                        <a href="<?php echo home_url(); ?>" class="hover:text-green-100">Beranda</a>
                        <a href="<?php echo home_url('/wisata'); ?>" class="hover:text-green-100">Wisata</a>
                        <a href="<?php echo home_url('/produk'); ?>" class="hover:text-green-100">Produk</a>
                    </nav>

                    <div class="h-6 w-px bg-white/20"></div>

                    <!-- Cart Desktop -->
                    <a href="<?php echo home_url('/cart'); ?>" class="relative group">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="header-cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold border-2 border-primary hidden">0</span>
                    </a>

                    <!-- Account Desktop (Hanya muncul di Desktop) -->
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo home_url('/akun-saya'); ?>" class="flex items-center gap-2 bg-white/10 pl-2 pr-4 py-1.5 rounded-full hover:bg-white/20 transition backdrop-blur-sm border border-white/10">
                            <div class="w-7 h-7 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 overflow-hidden">
                                <?php echo get_avatar($current_user->ID, 28); ?>
                            </div>
                            <span class="text-xs font-semibold max-w-[80px] truncate"><?php echo explode(' ', $current_user->display_name)[0]; ?></span>
                        </a>
                    <?php else: ?>
                         <a href="<?php echo home_url('/login'); ?>" class="bg-white text-primary px-5 py-2 rounded-full text-sm font-bold shadow hover:bg-gray-100 transition">Masuk</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mobile Search Bar Toggle (Expandable) -->
            <div id="mobile-search-bar" class="hidden md:hidden bg-white px-4 py-3 shadow-md border-b border-gray-100 absolute w-full top-16 left-0 z-40 animate-fade-in-down">
                <form action="<?php echo home_url('/'); ?>" method="get" class="relative w-full">
                    <input type="text" name="s" placeholder="Mau cari apa?" class="w-full h-10 pl-10 pr-4 rounded-lg bg-gray-100 text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-primary border-none">
                    <i class="fas fa-search absolute left-3.5 top-3 text-gray-400"></i>
                </form>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 w-full container mx-auto px-0 md:px-4 py-0 md:py-8">