<?php get_header(); ?>

<!-- Container utama -->
<div style="padding: 10px 20px; max-width: 1200px; margin: 0 auto;">
    
    <!-- Bagian Header Arsip -->
    <div class="section-header" style="padding: 10px 0 20px 0;">
        <div class="section-title">
            <h3>Jelajahi <span>Desa Wisata</span></h3>
        </div>
    </div>

    <!-- Filter Lokasi (Opsional - Bisa dikembangkan nanti) -->
    <!-- Saat ini menampilkan tombol statis untuk estetika UI -->
    <div class="explore-filters">
        <div class="filter-pill active">Semua</div>
        <div class="filter-pill">Pegunungan</div>
        <div class="filter-pill">Pantai</div>
        <div class="filter-pill">Budaya</div>
    </div>
</div>

<!-- List Desa -->
<div class="desa-list" style="padding: 0 20px; max-width: 1200px; margin: 0 auto;">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); 
        // Ambil Meta Data Desa
        $lokasi = get_post_meta( get_the_ID(), '_dw_kabupaten', true );
        $provinsi = get_post_meta( get_the_ID(), '_dw_provinsi', true );
        $deskripsi = get_the_excerpt(); 
        if ( empty($deskripsi) ) {
            $deskripsi = wp_trim_words( get_the_content(), 15, '...' );
        }
    ?>
    <div class="card-desa">
        <a href="<?php the_permalink(); ?>">
            <!-- Gambar Desa -->
            <?php if ( has_post_thumbnail() ) { 
                the_post_thumbnail('large', array('class' => 'card-desa-img')); 
            } else { 
                echo '<img src="https://via.placeholder.com/800x400?text=Desa+Wisata" class="card-desa-img" alt="Placeholder" />'; 
            } ?>
            
            <div class="card-desa-content">
                <div class="card-desa-title"><?php the_title(); ?></div>
                
                <div class="location-tag" style="margin-bottom: 10px; font-size: 0.85rem; color: var(--text-grey);">
                    <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> 
                    <?php echo esc_html( ($lokasi ? $lokasi : 'Lokasi') . ($provinsi ? ', ' . $provinsi : '') ); ?>
                </div>
                
                <div class="card-desa-desc"><?php echo esc_html($deskripsi); ?></div>
                
                <div class="card-desa-footer">
                    <!-- Rating Dummy (Bisa diintegrasikan real nanti) -->
                    <div class="desa-rating"><i class="fas fa-star"></i> 4.8</div>
                    <span class="btn-visit">Kunjungi Desa</span>
                </div>
            </div>
        </a>
    </div>
    <?php endwhile; 
        // Pagination
        echo '<div style="padding: 20px 0; text-align: center;">';
        echo paginate_links( array(
            'prev_text' => '<i class="fas fa-chevron-left"></i>',
            'next_text' => '<i class="fas fa-chevron-right"></i>',
            'type'      => 'list',
        ) ); 
        echo '</div>';
    else : ?>
        <div style="text-align: center; padding: 40px 0;">
            <img src="https://via.placeholder.com/150?text=Belum+Ada+Data" alt="Kosong" style="margin: 0 auto 15px; opacity: 0.5; width: 100px;">
            <p style="color: #777;">Belum ada desa wisata yang terdaftar.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Styling Khusus Halaman Desa -->
<style>
    .card-desa { background: var(--white); border-radius: var(--radius-m); overflow: hidden; box-shadow: var(--shadow); margin-bottom: 20px; transition: transform 0.2s; border: 1px solid #eee; }
    .card-desa:hover { transform: translateY(-3px); }
    .card-desa-img { width: 100%; height: 200px; object-fit: cover; }
    .card-desa-content { padding: 15px; }
    .card-desa-title { font-size: 1.1rem; font-weight: 700; margin-bottom: 5px; color: var(--text-dark); }
    .card-desa-desc { font-size: 0.9rem; color: var(--text-grey); line-height: 1.5; margin-bottom: 15px; }
    .card-desa-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f5f5f5; padding-top: 10px; }
    .desa-rating { color: var(--accent); font-weight: bold; font-size: 0.9rem; }
    .btn-visit { background: var(--primary-light); color: var(--primary); padding: 8px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; text-decoration: none; }
    
    /* Pagination Style */
    ul.page-numbers { display: flex; justify-content: center; gap: 8px; padding: 0; list-style: none; }
    .page-numbers { display: flex; align-items: center; justify-content: center; width: 35px; height: 35px; border-radius: 50%; background: var(--white); color: var(--text-dark); font-size: 0.9rem; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: 0.2s; }
    .page-numbers.current, .page-numbers:hover { background: var(--primary); color: var(--white); transform: translateY(-2px); }
</style>

<?php get_footer(); ?>