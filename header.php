<?php
/**
 * Header Template
 * * Menampilkan bagian atas situs: Navigasi, Logo, dan Menu User.
 */

// Pastikan session dimulai untuk fitur keranjang
if (!session_id()) {
    session_start();
}

// Hitung jumlah item di keranjang dari session
$cart_count = 0;
if (isset($_SESSION['dw_cart']) && is_array($_SESSION['dw_cart'])) {
    $cart_count = count($_SESSION['dw_cart']);
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Tailwind CSS (via CDN for dev) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        orange: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                        }
                    }
                }
            }
        }
    </script>

    <?php wp_head(); ?>
</head>

<body <?php body_class('bg-gray-50 text-gray-800 antialiased font-sans'); ?>>

<!-- Navbar -->
<nav class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-gray-100 transition-all duration-300" id="main-navbar">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center h-20">
            
            <!-- Logo -->
            <a href="<?php echo home_url(); ?>" class="flex items-center gap-2 group">
                <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-amber-400 rounded-xl flex items-center justify-center text-white shadow-lg shadow-orange-200 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-store text-lg"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-bold tracking-tight text-gray-900 leading-none"><?php echo get_bloginfo('name'); ?></span>
                    <span class="text-[10px] text-gray-500 font-medium tracking-wide uppercase">Official Platform</span>
                </div>
            </a>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-8">
                <a href="<?php echo home_url(); ?>" class="text-sm font-medium text-gray-600 hover:text-orange-600 transition relative py-2 group">
                    Beranda
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-orange-500 transition-all duration-300 group-hover:w-full"></span>
                </a>
                <a href="<?php echo home_url('/wisata'); ?>" class="text-sm font-medium text-gray-600 hover:text-orange-600 transition relative py-2 group">
                    Destinasi
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-orange-500 transition-all duration-300 group-hover:w-full"></span>
                </a>
                <a href="<?php echo home_url('/produk'); ?>" class="text-sm font-medium text-gray-600 hover:text-orange-600 transition relative py-2 group">
                    Produk Desa
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-orange-500 transition-all duration-300 group-hover:w-full"></span>
                </a>
                <a href="<?php echo home_url('/tentang'); ?>" class="text-sm font-medium text-gray-600 hover:text-orange-600 transition relative py-2 group">
                    Tentang
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-orange-500 transition-all duration-300 group-hover:w-full"></span>
                </a>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                
                <!-- Search Icon (Mobile) -->
                <button class="md:hidden w-10 h-10 flex items-center justify-center text-gray-500 hover:bg-gray-50 rounded-full transition">
                    <i class="fas fa-search"></i>
                </button>

                <!-- Cart Icon -->
                <a href="<?php echo home_url('/cart'); ?>" class="relative w-10 h-10 flex items-center justify-center text-gray-600 hover:text-orange-600 hover:bg-orange-50 rounded-full transition-all group">
                    <i class="fas fa-shopping-bag text-lg group-hover:scale-110 transition-transform"></i>
                    
                    <!-- Badge Cart Count -->
                    <span id="header-cart-count" class="absolute top-1 right-0 w-5 h-5 bg-red-500 text-white text-[10px] font-bold flex items-center justify-center rounded-full border-2 border-white shadow-sm <?php echo $cart_count > 0 ? 'flex' : 'hidden'; ?>">
                        <?php echo $cart_count; ?>
                    </span>
                </a>

                <?php if (is_user_logged_in()) : 
                    $current_user = wp_get_current_user();
                    // Ambil inisial nama
                    $initial = strtoupper(substr($current_user->display_name, 0, 1));
                ?>
                    <!-- User Dropdown (Logged In) -->
                    <div class="relative group">
                        <a href="<?php echo home_url('/akun-saya'); ?>" class="flex items-center gap-2 pl-1 pr-3 py-1 bg-gray-50 border border-gray-200 rounded-full hover:border-orange-200 hover:bg-orange-50 transition cursor-pointer">
                            <div class="w-8 h-8 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center font-bold text-sm">
                                <?php echo $initial; ?>
                            </div>
                            <span class="text-sm font-bold text-gray-700 max-w-[80px] truncate hidden sm:block">
                                <?php echo esc_html($current_user->display_name); ?>
                            </span>
                        </a>
                    </div>
                <?php else : ?>
                    <!-- Login Button (Guest) -->
                    <a href="<?php echo home_url('/login'); ?>" class="hidden sm:inline-flex items-center justify-center px-5 py-2.5 bg-gray-900 text-white text-sm font-bold rounded-full hover:bg-orange-600 transition-all shadow-lg hover:shadow-orange-200 transform hover:-translate-y-0.5">
                        Masuk
                    </a>
                <?php endif; ?>

                <!-- Mobile Menu Button -->
                <button class="md:hidden w-10 h-10 flex items-center justify-center text-gray-600 bg-gray-50 rounded-lg hover:bg-gray-100 transition" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Drawer (Hidden by default) -->
    <div id="mobile-menu" class="hidden md:hidden bg-white border-b border-gray-100 absolute w-full left-0 top-20 shadow-lg p-4 z-40">
        <nav class="flex flex-col gap-2">
            <a href="<?php echo home_url(); ?>" class="p-3 rounded-lg hover:bg-orange-50 text-gray-700 font-medium">Beranda</a>
            <a href="<?php echo home_url('/wisata'); ?>" class="p-3 rounded-lg hover:bg-orange-50 text-gray-700 font-medium">Destinasi Wisata</a>
            <a href="<?php echo home_url('/produk'); ?>" class="p-3 rounded-lg hover:bg-orange-50 text-gray-700 font-medium">Produk UMKM</a>
            <a href="<?php echo home_url('/transaksi'); ?>" class="p-3 rounded-lg hover:bg-orange-50 text-gray-700 font-medium">Riwayat Transaksi</a>
            <?php if (!is_user_logged_in()) : ?>
                <div class="border-t border-gray-100 my-2 pt-2">
                    <a href="<?php echo home_url('/login'); ?>" class="block w-full text-center py-3 bg-gray-900 text-white rounded-lg font-bold">Masuk Sekarang</a>
                </div>
            <?php endif; ?>
        </nav>
    </div>
</nav>