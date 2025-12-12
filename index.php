<?php
/**
 * Main Template File
 * * File ini wajib ada agar WordPress mengenali folder ini sebagai Theme yang valid.
 * Karena kita menggunakan Front Page custom, file ini jarang diakses, 
 * tapi berfungsi sebagai fallback.
 */

get_header();
?>

<div class="container" style="padding: 50px 20px; text-align: center;">
    <h1>Selamat Datang di Desa Wisata</h1>
    <p>Silakan login untuk mengakses fitur.</p>
    
    <?php if ( ! is_user_logged_in() ) : ?>
        <a href="<?php echo home_url('/login/'); ?>" class="button button-primary">Masuk Sekarang</a>
    <?php endif; ?>
</div>

<?php
get_footer();
?>