<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <!-- Meta Viewport Penting untuk Responsif -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
    <div class="container header-inner">
        <!-- 1. Logo (Kiri) -->
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-logo">
            <?php 
            if ( has_custom_logo() ) {
                the_custom_logo();
            } else {
                echo 'DesaWisata'; // Fallback text
            }
            ?>
        </a>

        <!-- 2. Search Bar (Tengah - Responsive) -->
        <div class="search-bar">
            <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <input type="search" placeholder="Cari wisata atau produk..." value="<?php echo get_search_query(); ?>" name="s" />
                <input type="hidden" name="post_type" value="dw_produk" /> <!-- Default search produk -->
            </form>
        </div>

        <!-- 3. Desktop Nav (Kanan - Hide on Mobile) -->
        <nav class="desktop-nav">
            <?php
            // Menu Desktop
            if ( is_user_logged_in() ) {
                $current_user = wp_get_current_user();
                echo '<a href="' . site_url('/akun-saya') . '"><i class="fas fa-user-circle"></i> ' . esc_html($current_user->display_name) . '</a>';
                echo '<a href="' . site_url('/cart') . '" style="margin-left:15px;"><i class="fas fa-shopping-cart"></i></a>';
            } else {
                echo '<a href="' . site_url('/login') . '">Masuk</a> | <a href="' . site_url('/register') . '" class="btn-register">Daftar</a>';
            }
            ?>
        </nav>
        
        <!-- Mobile Cart Icon (Top Right) -->
        <div class="mobile-nav-icon" style="display: none;"> <!-- Bisa ditampilkan via CSS media query jika perlu -->
             <a href="<?php echo site_url('/cart'); ?>"><i class="fas fa-shopping-cart"></i></a>
        </div>
    </div>
</header>