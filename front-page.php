<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Desa Wisata</title>
    <link rel="stylesheet" href="<?php echo get_stylesheet_uri(); ?>">
    <style>
        /* CSS Inline Tambahan untuk Debugging */
        .debug-panel { background: #333; color: #fff; padding: 10px; font-family: monospace; font-size: 12px; margin-bottom: 20px; }
        .error-msg { background: #ffdddd; border-left: 5px solid #f44336; padding: 15px; color: #333; margin: 20px 0; }
        .empty-msg { background: #fff3cd; border-left: 5px solid #ff9800; padding: 15px; color: #333; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .card { background: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
        .card img { width: 100%; height: 200px; object-fit: cover; background: #eee; }
        .card-body { padding: 15px; }
        .btn { display: inline-block; background: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-top: 10px; }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header style="background: white; padding: 20px; border-bottom: 1px solid #eee;">
    <div class="container">
        <h1 style="margin:0;">Desa Wisata Client</h1>
        <small style="color:gray;">Mengambil data dari: <strong>admin.bonang.my.id</strong></small>
    </div>
</header>

<main class="container">
    
    <h2>Daftar Desa</h2>

    <?php
    // 1. Tentukan Endpoint (Cek plugin Anda untuk memastikan ini benar)
    // Kemungkinan besar: /wp-json/dw-api/v1/public/desa
    $endpoint = '/wp-json/dw-api/v1/public/desa';

    // 2. Ambil Data
    if ( function_exists('dw_fetch_api_data') ) {
        $result = dw_fetch_api_data( $endpoint );
    } else {
        $result = array('error' => true, 'message' => 'Fungsi dw_fetch_api_data tidak ditemukan di functions.php');
    }

    // 3. Logika Tampilan
    if ( isset( $result['error'] ) && $result['error'] === true ) : 
        // --- JIKA ERROR ---
    ?>
        <div class="error-msg">
            <h3>Gagal Mengambil Data</h3>
            <p><strong>Pesan Error:</strong> <?php echo esc_html( $result['message'] ); ?></p>
            <?php if(isset($result['debug'])) : ?>
                <pre><?php echo esc_html( $result['debug'] ); ?></pre>
            <?php endif; ?>
            <hr>
            <p><strong>Saran Perbaikan:</strong></p>
            <ul>
                <li>Coba buka URL ini di browser Anda untuk memastikan endpoint benar: <br>
                    <a href="https://admin.bonang.my.id<?php echo $endpoint; ?>" target="_blank">
                        https://admin.bonang.my.id<?php echo $endpoint; ?>
                    </a>
                </li>
                <li>Jika browser menampilkan JSON, berarti tema bisa konek, tapi mungkin format datanya beda.</li>
            </ul>
        </div>

    <?php 
    elseif ( empty( $result ) ) : 
        // --- JIKA DATA KOSONG (Array Kosong) ---
    ?>
        <div class="empty-msg">
            <p>Koneksi Berhasil, tetapi <strong>tidak ada data desa</strong> yang ditemukan (Array Kosong).</p>
        </div>

    <?php 
    else : 
        // --- JIKA SUKSES ---
    ?>
        
        <div class="grid">
            <?php foreach ( $result as $item ) : ?>
                <div class="card">
                    <!-- Gambar Fallback jika thumbnail kosong -->
                    <?php $img_url = !empty($item['thumbnail']) ? $item['thumbnail'] : 'https://via.placeholder.com/400x250?text=No+Image'; ?>
                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($item['nama_desa'] ?? 'Desa'); ?>">
                    
                    <div class="card-body">
                        <!-- Sesuaikan key 'nama_desa', 'deskripsi' dengan JSON asli plugin Anda -->
                        <h3 style="margin-top:0;"><?php echo esc_html( $item['nama_desa'] ?? 'Tanpa Nama' ); ?></h3>
                        <p><?php echo esc_html( wp_trim_words( $item['deskripsi'] ?? '', 15 ) ); ?></p>
                        
                        <!-- Link ke Detail (Nanti kita buat file single.php) -->
                        <!-- Kita kirim ID via parameter URL ?id=123 -->
                        <a href="<?php echo home_url( '/?desa_id=' . ($item['id'] ?? 0) ); ?>" class="btn">Lihat Detail</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>

</main>

<?php wp_footer(); ?>
</body>
</html>