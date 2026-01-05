<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Tema_Desa_Wisata
 */

?>

    </div><!-- #content -->

    <?php 
    // Tampilan Footer Desktop (Sederhana)
    ?>
    <footer id="colophon" class="site-footer hidden md:block bg-white border-t mt-12 py-8">
        <div class="container mx-auto px-4">
            <div class="site-info text-center text-gray-500 text-sm">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="text-gray-900 font-bold hover:underline">
                    <?php bloginfo( 'name' ); ?>
                </a>
                <span class="sep"> | </span>
                <?php
                /* translators: 1: Theme name, 2: Theme author. */
                printf( esc_html__( 'Theme: %1$s by %2$s.', 'tema-desa-wisata' ), 'tema-desa-wisata', '<a href="#" class="hover:underline">Desa Wisata</a>' );
                ?>
                <br>
                &copy; <?php echo date('Y'); ?> All rights reserved.
            </div><!-- .site-info -->
        </div>
    </footer>

    <?php 
    // Tampilan Bottom Navigation Bar (Mobile Only)
    // Diubah menjadi grid-cols-4 karena menu favorit dihapus
    ?>
    <div class="fixed bottom-0 left-0 z-50 w-full h-16 bg-white border-t border-gray-200 md:hidden pb-safe">
        <div class="grid h-full max-w-lg grid-cols-4 mx-auto font-medium">
            
            <!-- 1. Menu Beranda -->
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" type="button" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group <?php echo is_front_page() ? 'text-blue-600' : 'text-gray-500'; ?>">
                <svg class="w-6 h-6 mb-1 <?php echo is_front_page() ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-600'; ?>" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                </svg>
                <span class="text-xs <?php echo is_front_page() ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-600'; ?>">Beranda</span>
            </a>

            <!-- 2. Menu Wisata (Explore) -->
            <?php 
            $wisata_link = get_post_type_archive_link('dw_wisata'); 
            $is_wisata = is_post_type_archive('dw_wisata') || is_singular('dw_wisata');
            ?>
            <a href="<?php echo esc_url($wisata_link); ?>" type="button" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group <?php echo $is_wisata ? 'text-blue-600' : 'text-gray-500'; ?>">
                <svg class="w-6 h-6 mb-1 <?php echo $is_wisata ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-600'; ?>" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path clip-rule="evenodd" fill-rule="evenodd" d="M4 2a2 2 0 00-2 2v11a3 3 0 106 0V4a2 2 0 00-2-2H4zm1 14a1 1 0 100-2 1 1 0 000 2zm5-1.757l4.9-4.9a2 2 0 000-2.828L13.485 5.1a2 2 0 00-2.828 0L10 5.757v8.486zM16 18H9.071l6-6H16a2 2 0 012 2v2a2 2 0 01-2 2z"></path>
                </svg>
                <span class="text-xs <?php echo $is_wisata ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-600'; ?>">Wisata</span>
            </a>

            <!-- MENU FAVORIT DIHAPUS DI SINI -->

            <!-- 3. Menu Transaksi -->
            <?php 
            // Mencari halaman Transaksi berdasarkan template atau slug
            $transaksi_page = get_pages(array(
                'meta_key' => '_wp_page_template',
                'meta_value' => 'page-transaksi.php'
            ));
            $transaksi_link = $transaksi_page ? get_permalink($transaksi_page[0]->ID) : home_url('/transaksi');
            $is_transaksi = is_page_template('page-transaksi.php') || is_singular('dw_transaksi');
            ?>
            <a href="<?php echo esc_url($transaksi_link); ?>" type="button" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group <?php echo $is_transaksi ? 'text-blue-600' : 'text-gray-500'; ?>">
                <svg class="w-6 h-6 mb-1 <?php echo $is_transaksi ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-600'; ?>" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z"></path>
                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-xs <?php echo $is_transaksi ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-600'; ?>">Transaksi</span>
            </a>

            <!-- 4. Menu Akun -->
            <?php 
            // Mencari halaman Akun Saya / Dashboard
            $akun_page = get_pages(array(
                'meta_key' => '_wp_page_template',
                'meta_value' => 'page-akun-saya.php'
            ));
            
            // Fallback jika user belum login, arahkan ke login, jika sudah ke dashboard/akun
            if ( is_user_logged_in() ) {
                $user = wp_get_current_user();
                // Cek role untuk redirect dashboard yang tepat jika diperlukan
                if ( in_array( 'dw_pedagang', (array) $user->roles ) ) {
                    $dashboard_slug = 'dashboard-toko';
                } elseif ( in_array( 'dw_verifikator', (array) $user->roles ) ) {
                    $dashboard_slug = 'dashboard-verifikator';
                } elseif ( in_array( 'dw_ojek', (array) $user->roles ) ) {
                    $dashboard_slug = 'dashboard-ojek';
                } elseif ( in_array( 'dw_desa', (array) $user->roles ) ) {
                    $dashboard_slug = 'dashboard-desa';
                } else {
                    $dashboard_slug = 'akun-saya';
                }
                $akun_link = site_url('/' . $dashboard_slug);
            } else {
                $akun_link = site_url('/login');
            }

            $is_akun = is_page('akun-saya') || is_page('login') || is_page('dashboard-toko') || is_page('dashboard-desa') || is_page('dashboard-verifikator') || is_page('dashboard-ojek');
            ?>
            <a href="<?php echo esc_url($akun_link); ?>" type="button" class="inline-flex flex-col items-center justify-center px-5 hover:bg-gray-50 group <?php echo $is_akun ? 'text-blue-600' : 'text-gray-500'; ?>">
                <svg class="w-6 h-6 mb-1 <?php echo $is_akun ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-600'; ?>" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-xs <?php echo $is_akun ? 'text-blue-600' : 'text-gray-500 group-hover:text-blue-600'; ?>">Akun</span>
            </a>

        </div>
    </div>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>