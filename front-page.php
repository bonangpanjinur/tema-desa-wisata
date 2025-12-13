<?php
/**
 * Template Name: Halaman Depan API
 */
get_header(); 

// --- KONFIGURASI ENDPOINT ---
// Sesuai Dokumentasi: Base URL + /desa
// Base URL: /wp-json/dw/v1
$endpoint_desa = '/wp-json/dw/v1/desa'; 

// Ambil Data
$data_desa = dw_fetch_api_data( $endpoint_desa );

// Normalisasi Data:
// Kadang API mengembalikan array murni [...], kadang object { data: [...] }
// Kita pastikan $list_desa selalu berupa array list.
$list_desa = [];
if ( isset( $data_desa['data'] ) && is_array( $data_desa['data'] ) ) {
    $list_desa = $data_desa['data'];
} elseif ( is_array( $data_desa ) && !isset( $data_desa['error'] ) ) {
    $list_desa = $data_desa;
}
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">

        <!-- Banner Section -->
        <section class="hero-section" style="background: #2c3e50; color: white; padding: 80px 0; text-align: center; margin-bottom: 50px;">
            <div class="container">
                <h1 class="entry-title" style="font-size: 3rem; margin-bottom: 1rem;">Jelajahi Desa Wisata</h1>
                <p class="lead" style="font-size: 1.2rem; opacity: 0.9;">Temukan keindahan dan kearifan lokal desa-desa terbaik kami.</p>
            </div>
        </section>

        <div class="container">

            <?php if ( isset( $data_desa['error'] ) && $data_desa['error'] === true ) : ?>
                
                <!-- TAMPILAN ERROR -->
                <div class="alert alert-danger" style="background: #fff3cd; color: #856404; padding: 20px; border: 1px solid #ffeeba; border-radius: 5px; margin-bottom:30px;">
                    <h3>⚠️ Gagal Mengambil Data</h3>
                    <p>Endpoint: <code><?php echo esc_html( $endpoint_desa ); ?></code></p>
                    <p><strong>Pesan Error:</strong> <?php echo esc_html( $data_desa['message'] ); ?></p>
                    
                    <hr>
                    <h4>🔍 Debug Routes</h4>
                    <p>Mencoba mencari route <code>/dw/v1</code> di server target:</p>
                    <?php 
                    $routes = dw_debug_find_routes();
                    if ( is_array( $routes ) && ! empty( $routes ) ) {
                        echo "<ul>";
                        foreach ( $routes as $route ) {
                            echo "<li>Found: <code>" . esc_html( $route ) . "</code></li>";
                        }
                        echo "</ul>";
                    } else {
                        echo "<p><em>" . esc_html( is_string($routes) ? $routes : 'Tidak ditemukan route dw/v1' ) . "</em></p>";
                    }
                    ?>
                </div>

            <?php elseif ( empty( $list_desa ) ) : ?>
                
                <div class="alert alert-warning" style="text-align:center; padding:50px;">
                    <h3>Belum ada data desa.</h3>
                    <p>Koneksi ke API berhasil, tetapi array data kosong.</p>
                </div>

            <?php else : ?>

                <!-- TAMPILAN GRID DESA -->
                <div class="desa-grid-wrapper" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; margin-bottom: 50px;">
                    
                    <?php foreach ( $list_desa as $desa ) : ?>
                        <article class="desa-card hentry" style="border: 1px solid #e1e4e8; border-radius: 12px; overflow: hidden; background: #fff; transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                            
                            <!-- Thumbnail -->
                            <div class="desa-thumbnail" style="height: 200px; overflow: hidden; position: relative; background: #f0f0f0;">
                                <?php 
                                // Cek key untuk gambar, sesuaikan dengan response API Anda (misal: 'thumbnail', 'foto_utama', atau 'featured_image_url')
                                // Fallback ke placeholder jika kosong
                                $img_src = !empty($desa['thumbnail']) ? $desa['thumbnail'] : 'https://via.placeholder.com/400x250?text=Desa+Wisata';
                                ?>
                                <img src="<?php echo esc_url( $img_src ); ?>" alt="<?php echo esc_attr( $desa['nama_desa'] ?? 'Desa' ); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>

                            <!-- Content -->
                            <div class="desa-content" style="padding: 25px;">
                                <h2 class="entry-title" style="margin-top: 0; margin-bottom: 10px; font-size: 1.4rem; color: #2c3e50;">
                                    <?php echo esc_html( $desa['nama_desa'] ?? $desa['title'] ?? 'Tanpa Nama' ); ?>
                                </h2>
                                
                                <div class="desa-meta" style="font-size: 0.9rem; color: #7f8c8d; margin-bottom: 15px; display:flex; align-items:center;">
                                    <span style="margin-right: 15px;">📍 <?php echo esc_html( $desa['lokasi'] ?? $desa['kota'] ?? 'Lokasi' ); ?></span>
                                </div>

                                <div class="desa-excerpt" style="color: #555; font-size: 0.95rem; margin-bottom: 20px; line-height: 1.6;">
                                    <?php 
                                    $deskripsi = $desa['deskripsi'] ?? $desa['content'] ?? '';
                                    echo wp_trim_words( strip_tags( $deskripsi ), 15, '...' ); 
                                    ?>
                                </div>

                                <div class="desa-footer">
                                    <a href="<?php echo home_url( '/?desa_id=' . ($desa['id'] ?? 0) ); ?>" class="button btn-primary" style="display:block; text-align:center; background: #3498db; color: white; padding: 10px; border-radius: 6px; text-decoration: none; font-weight: 600;">
                                        Lihat Profil Desa
                                    </a>
                                </div>
                            </div>
                            
                        </article>
                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </div><!-- .container -->
    </main>
</div>

<?php get_footer(); ?>