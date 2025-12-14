<?php
/**
 * Logika Fetch Data Header dari API
 * Mengambil pengaturan global dari Plugin Core (api-public.php).
 */
$brand_name = get_bloginfo( 'name' ); // Default (Fallback)

if ( function_exists( 'dw_fetch_api_data' ) ) {
    // Memanggil endpoint /settings yang ada di api-public.php
    $api_settings = dw_fetch_api_data( '/settings' ); 
    
    // Debug: Uncomment baris di bawah jika ingin melihat raw data di layar (untuk dev)
    // echo '<pre style="display:none">'; print_r($api_settings); echo '</pre>';

    if ( ! empty( $api_settings ) && ! isset( $api_settings['error'] ) ) {
        // [FIX] Menggunakan key 'nama_website' sesuai output api-public.php
        if ( ! empty( $api_settings['nama_website'] ) ) {
            $brand_name = $api_settings['nama_website'];
        } 
        // Fallback ke key lain jika struktur API berubah
        elseif ( ! empty( $api_settings['app_name'] ) ) {
            $brand_name = $api_settings['app_name'];
        }
    }
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Inter & FontAwesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS (Gunakan build process untuk production) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#00BA61', // Hijau Sadesa
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
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-gray-50 text-gray-800 font-sans'); ?>>

    <!-- Main Wrapper -->
    <div id="app-wrapper" class="min-h-screen flex flex-col relative">
        
        <!-- HEADER UTAMA (Sticky) -->
        <header class="bg-primary text-white shadow-sm sticky top-0 z-50 transition-colors duration-200">
            <div class="container mx-auto px-4 h-16 md:h-20 flex items-center justify-between gap-4 md:gap-8">
                
                <!-- 1. LOGO (Kiri) -->
                <a href="<?php echo home_url(); ?>" class="flex items-center gap-2 hover:opacity-90 transition shrink-0">
                    <!-- Icon Daun -->
                    <i class="fas fa-leaf text-yellow-300 text-2xl"></i>
                    <!-- Nama Brand (Dinamis dari API) -->
                    <span class="font-bold text-xl md:text-2xl tracking-tight"><?php echo esc_html( $brand_name ); ?></span>
                </a>

                <!-- 2. SEARCH BAR (Tengah - Hidden on Mobile initially) -->
                <div class="hidden md:block flex-1 max-w-2xl mx-auto">
                    <form action="<?php echo home_url('/'); ?>" method="get" class="relative w-full group">
                        <input type="text" name="s" 
                               placeholder="Cari wisata, produk, atau desa..." 
                               class="w-full h-11 pl-12 pr-4 rounded-xl text-gray-700 text-sm focus:outline-none focus:ring-4 focus:ring-white/20 shadow-sm bg-white border-none placeholder-gray-400 transition-shadow">
                        <i class="fas fa-search absolute left-4 top-3.5 text-gray-400 text-lg group-focus-within:text-primary transition-colors"></i>
                    </form>
                </div>

                <!-- 3. NAVIGASI & AKUN (Kanan) -->
                <div class="flex items-center gap-3 md:gap-6">
                    
                    <!-- Menu Links (Desktop) -->
                    <nav class="hidden md:flex items-center gap-6 font-medium text-sm">
                        <a href="<?php echo home_url(); ?>" class="hover:text-green-100 hover:bg-white/10 px-3 py-2 rounded-lg transition">Beranda</a>
                        <a href="<?php echo home_url('/wisata'); ?>" class="hover:text-green-100 hover:bg-white/10 px-3 py-2 rounded-lg transition">Wisata</a>
                        <a href="<?php echo home_url('/produk'); ?>" class="hover:text-green-100 hover:bg-white/10 px-3 py-2 rounded-lg transition">Produk</a>
                    </nav>

                    <div class="h-6 w-px bg-white/20 hidden md:block"></div>

                    <!-- Cart Icon -->
                    <a href="<?php echo home_url('/cart'); ?>" class="relative p-2 hover:bg-white/10 rounded-full transition group">
                        <i class="fas fa-shopping-cart text-xl group-hover:scale-110 transition-transform"></i>
                        <span id="header-cart-count" class="absolute top-0 right-0 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold border-2 border-primary hidden transform scale-0 transition-transform">0</span>
                    </a>

                    <!-- User Account -->
                    <?php if (is_user_logged_in()): 
                        $current_user = wp_get_current_user();
                        $display_name = explode(' ', $current_user->display_name)[0]; // Ambil nama depan saja
                    ?>
                        <a href="<?php echo home_url('/akun-saya'); ?>" class="flex items-center gap-2 bg-white/10 pl-2 pr-4 py-1.5 rounded-full hover:bg-white/20 transition backdrop-blur-sm border border-white/10">
                            <div class="w-7 h-7 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-600 overflow-hidden">
                                <?php echo get_avatar($current_user->ID, 28); ?>
                            </div>
                            <span class="text-xs font-semibold hidden md:block max-w-[100px] truncate">Hai, <?php echo esc_html($display_name); ?></span>
                        </a>
                    <?php else: ?>
                         <a href="<?php echo home_url('/login'); ?>" class="hidden md:flex items-center gap-2 bg-white text-primary px-5 py-2 rounded-full text-sm font-bold shadow-md hover:bg-gray-100 hover:shadow-lg transition transform hover:-translate-y-0.5">
                            Masuk
                         </a>
                         <!-- Mobile Login Icon -->
                         <a href="<?php echo home_url('/login'); ?>" class="md:hidden p-2">
                            <i class="fas fa-sign-in-alt text-xl"></i>
                         </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mobile Search Bar (Visible on Mobile) -->
            <div class="md:hidden px-4 pb-4">
                <form action="<?php echo home_url('/'); ?>" method="get" class="relative w-full">
                    <input type="text" name="s" 
                           placeholder="Mau cari apa hari ini?" 
                           class="w-full h-10 pl-10 pr-4 rounded-lg text-gray-700 text-sm focus:outline-none focus:ring-2 focus:ring-green-300 shadow-inner bg-white border-none">
                    <i class="fas fa-search absolute left-3.5 top-3 text-gray-400"></i>
                </form>
            </div>
        </header>

        <!-- MAIN CONTENT -->
        <main class="flex-1 w-full container mx-auto px-4 py-6 md:py-8">