<?php
/**
 * Header Template
 * * Menampilkan bagian atas situs: Logo, Menu Navigasi, dan User Dropdown.
 * FIXED: Kembali ke desain lama + Menampilkan Foto Profil (Avatar).
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="scroll-smooth">
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class('bg-surface font-sans text-gray-700 antialiased selection:bg-primary selection:text-white pb-20 md:pb-0'); ?>>
<?php wp_body_open(); ?>

<!-- Navbar Elegant -->
<header class="bg-white/95 backdrop-blur-sm shadow-sm sticky top-0 z-50 border-b border-gray-100 transition-all duration-300">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 md:h-20">
            
            <!-- 1. Logo Area -->
            <div class="flex-shrink-0 flex items-center">
                <a href="<?php echo home_url(); ?>" class="flex items-center gap-2 group">
                    <?php if (has_custom_logo()) : ?>
                        <?php the_custom_logo(); ?>
                    <?php else : ?>
                        <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                            <i class="fas fa-leaf text-xl"></i>
                        </div>
                        <div class="flex flex-col">
                            <span class="text-xl font-bold text-gray-800 tracking-tight leading-none group-hover:text-primary transition-colors"><?php bloginfo('name'); ?></span>
                            <span class="text-[10px] text-gray-500 font-medium tracking-wide uppercase">Desa Wisata</span>
                        </div>
                    <?php endif; ?>
                </a>
            </div>

            <!-- 2. Desktop Navigation (Hidden on Mobile) -->
            <nav class="hidden md:flex items-center space-x-8">
                <?php
                $menu_items = array(
                    'Beranda' => home_url(),
                    'Wisata'  => home_url('/wisata'),
                    'Produk'  => home_url('/produk'),
                    'Tentang' => home_url('/tentang'),
                );

                foreach ($menu_items as $name => $link) :
                    // Cek aktif
                    $is_active = (is_front_page() && $name == 'Beranda') || (is_post_type_archive('dw_wisata') && $name == 'Wisata') || (is_post_type_archive('dw_produk') && $name == 'Produk');
                    $active_class = $is_active ? 'text-primary font-bold bg-green-50' : 'text-gray-600 hover:text-primary hover:bg-gray-50';
                ?>
                    <a href="<?php echo $link; ?>" class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 <?php echo $active_class; ?>">
                        <?php echo $name; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- 3. Actions Area -->
            <div class="flex items-center gap-3 md:gap-5">
                
                <!-- Keranjang (Visible on Mobile & Desktop) -->
                <?php 
                global $wpdb;
                $cart_count = 0;
                // Logic hitung cart sederhana jika tabel ada
                $table_cart = $wpdb->prefix . 'dw_cart';
                if($wpdb->get_var("SHOW TABLES LIKE '$table_cart'") == $table_cart) {
                    $user_id = get_current_user_id();
                    $session_id = session_id();
                    if($user_id) {
                        $cart_count = $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_cart WHERE user_id = %d", $user_id));
                    } else {
                        $cart_count = $wpdb->get_var($wpdb->prepare("SELECT SUM(qty) FROM $table_cart WHERE session_id = %s", $session_id));
                    }
                }
                $cart_count = intval($cart_count);
                ?>
                <a href="<?php echo home_url('/keranjang'); ?>" class="relative group p-2 rounded-full hover:bg-green-50 transition-colors" aria-label="Keranjang Belanja">
                    <i class="fas fa-shopping-bag text-xl text-primary transition-colors"></i>
                    <?php if($cart_count > 0): ?>
                        <span class="dw-cart-count absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold h-4 w-4 rounded-full flex items-center justify-center border-2 border-white transform scale-100 transition-transform">
                            <?php echo $cart_count > 9 ? '9+' : $cart_count; ?>
                        </span>
                    <?php endif; ?>
                </a>

                <!-- Akun / Login (HIDDEN ON MOBILE as requested) -->
                <div class="hidden md:flex items-center gap-3 pl-3 border-l border-gray-200">
                    <?php if (is_user_logged_in()): ?>
                        <?php 
                            $current_user = wp_get_current_user(); 
                            $roles = (array) $current_user->roles;
                            
                            // Determine Dashboard URL dynamically based on roles
                            $dashboard_url = home_url('/akun-saya'); // Default fallback
                            
                            if (in_array('administrator', $roles)) {
                                $dashboard_url = admin_url();
                            } elseif (in_array('admin_desa', $roles)) {
                                $dashboard_url = home_url('/dashboard-desa');
                            } elseif (in_array('pedagang', $roles)) {
                                $dashboard_url = home_url('/dashboard-toko');
                            } elseif (in_array('verifikator_umkm', $roles)) {
                                $dashboard_url = home_url('/dashboard-verifikator');
                            }
                        ?>
                        <div class="relative group dropdown-container">
                            <button class="flex items-center gap-2 focus:outline-none">
                                <!-- [UPDATED] Foto Profil Logic: Menggunakan get_avatar_url() -->
                                <div class="w-9 h-9 rounded-full bg-primary/10 overflow-hidden border border-primary/20 flex-shrink-0">
                                    <img src="<?php echo get_avatar_url($current_user->ID, ['size' => 100]); ?>" 
                                         alt="<?php echo esc_attr($current_user->display_name); ?>" 
                                         class="w-full h-full object-cover">
                                </div>

                                <div class="text-left hidden lg:block">
                                    <span class="block text-xs text-gray-500">Halo,</span>
                                    <span class="block text-sm font-bold text-gray-800 leading-none max-w-[100px] truncate"><?php echo esc_html($current_user->display_name); ?></span>
                                </div>
                                <i class="fas fa-chevron-down text-xs text-gray-400 ml-1"></i>
                            </button>
                            
                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 transform origin-top-right z-50">
                                <a href="<?php echo $dashboard_url; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary"><i class="fas fa-columns w-5"></i> Dashboard</a>
                                <a href="<?php echo home_url('/akun-saya'); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-primary"><i class="fas fa-user-cog w-5"></i> Akun Saya</a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="<?php echo wp_logout_url(home_url()); ?>" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50"><i class="fas fa-sign-out-alt w-5"></i> Keluar</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo home_url('/login'); ?>" class="text-sm font-semibold text-gray-600 hover:text-primary transition-colors">Masuk</a>
                        <a href="<?php echo home_url('/register'); ?>" class="bg-primary hover:bg-primaryDark text-white text-sm font-bold px-5 py-2.5 rounded-full shadow-sm hover:shadow-md transition-all transform hover:-translate-y-0.5">Daftar</a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</header>