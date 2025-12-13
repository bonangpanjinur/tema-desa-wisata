<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php wp_title( '|', true, 'right' ); ?></title>
    <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header>
    <div class="container">
        <h1 class="site-title">
            <a href="<?php echo home_url(); ?>" style="text-decoration:none; color:inherit;">
                Client Desa Wisata
            </a>
        </h1>
        <p>Tema ini mengambil data dari server terpisah.</p>
        
        <!-- Debug Status -->
        <div class="status-box">
            <?php echo dw_api_status_check(); ?>
        </div>
    </div>
</header>

<main class="container">

    <h2>Daftar Desa (Dari API)</h2>

    <?php
    /**
     * CONTOH PENGGUNAAN API
     * Kita memanggil endpoint custom plugin Anda.
     * Pastikan endpoint ini benar di plugin core Anda.
     * Biasanya: /wp-json/dw-api/v1/desa
     */
    
    // Panggil fungsi yang kita buat di functions.php
    // Ganti endpoint sesuai struktur route di plugin Anda (includes/rest-api/api-public.php)
    $data_desa = dw_fetch_api_data( '/wp-json/dw-api/v1/public/desa' ); 

    if ( $data_desa && is_array( $data_desa ) ) : 
    ?>
        
        <div class="api-grid">
            <?php foreach ( $data_desa as $desa ) : ?>
                <div class="api-card">
                    <!-- Pastikan key array sesuai dengan output JSON plugin Anda -->
                    <h3><?php echo esc_html( $desa['nama_desa'] ?? 'Tanpa Judul' ); ?></h3>
                    
                    <?php if ( ! empty( $desa['thumbnail'] ) ) : ?>
                        <img src="<?php echo esc_url( $desa['thumbnail'] ); ?>" alt="Foto Desa" style="width:100%; height:auto; border-radius:4px;">
                    <?php endif; ?>

                    <p><?php echo esc_html( wp_trim_words( $desa['deskripsi'] ?? '', 20 ) ); ?></p>
                    
                    <a href="<?php echo home_url( '/desa/?id=' . ($desa['id'] ?? 0) ); ?>" class="button">
                        Lihat Detail
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else : ?>

        <div class="error-box">
            <h3>Gagal Mengambil Data</h3>
            <p>Kemungkinan penyebab:</p>
            <ul>
                <li>URL API di Customizer belum diisi atau salah.</li>
                <li>Plugin Desa Wisata Core di server mati.</li>
                <li>Endpoint API salah (Cek dokumentasi plugin).</li>
            </ul>
        </div>

    <?php endif; ?>

</main>

<footer style="text-align:center; padding: 20px; margin-top:50px; background:#ddd;">
    <div class="container">
        &copy; <?php echo date('Y'); ?> Desa Wisata API Theme.
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>