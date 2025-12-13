<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<!-- Pembungkus Utama Aplikasi -->
<div class="app-container">

    <header class="site-header">
        <div class="header-content">
            <?php if ( is_front_page() ) : ?>
                <!-- Tampilan Header Beranda (Search Box) -->
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <form role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                        <input type="search" placeholder="Cari wisata, kuliner..." value="<?php echo get_search_query(); ?>" name="s" />
                        <input type="hidden" name="post_type" value="dw_produk" /> 
                    </form>
                </div>
                <div class="header-icons">
                    <a href="<?php echo site_url('/notifikasi'); ?>"><i class="fas fa-bell"></i></a>
                    <a href="<?php echo site_url('/pesan'); ?>"><i class="fas fa-comment-dots"></i></a>
                </div>
            <?php else : ?>
                <!-- Tampilan Header Halaman Lain (Judul Halaman) -->
                <?php 
                    // Tombol Back (Opsional, pakai JS history back)
                    if( !is_home() ) {
                        echo '<i class="fas fa-arrow-left" style="color:white; margin-right:15px; cursor:pointer;" onclick="history.back()"></i>';
                    }
                ?>
                <div class="page-title">
                    <?php 
                    if ( is_archive() ) {
                        post_type_archive_title();
                    } elseif ( is_search() ) {
                        echo 'Cari: ' . get_search_query();
                    } else {
                        the_title(); 
                    }
                    ?>
                </div>
                <div class="header-icons">
                    <a href="<?php echo site_url('/cart'); ?>"><i class="fas fa-shopping-cart"></i></a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main id="main-content">