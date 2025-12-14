<?php
/**
 * Template Name: Halaman Login
 */

// Jika user sudah login, redirect
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    if (in_array('dw_admin_desa', $current_user->roles)) {
        wp_redirect(home_url('/dashboard-desa'));
    } elseif (in_array('dw_pedagang', $current_user->roles)) {
        wp_redirect(home_url('/dashboard-toko'));
    } else {
        wp_redirect(home_url('/akun-saya'));
    }
    exit;
}

get_header(); 

// Proses Login Logic
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_login_submit'])) {
    $creds = array(
        'user_login'    => sanitize_text_field($_POST['username']),
        'user_password' => $_POST['password'],
        'remember'      => isset($_POST['remember']) ? true : false,
    );

    $user = wp_signon($creds, false);

    if (is_wp_error($user)) {
        $error_message = $user->get_error_message();
    } else {
        // Redirect filter di functions.php akan menangani tujuannya
        wp_redirect(home_url('/akun-saya')); 
        exit;
    }
}
?>

<div class="dw-auth-container section-padding">
    <div class="container">
        <div class="dw-auth-box">
            <h2 class="text-center mb-4">Masuk ke Desa Wisata</h2>
            
            <?php if (!empty($error_message)) : ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form method="post" action="" class="dw-form">
                <div class="form-group mb-3">
                    <label for="username">Email atau Username</label>
                    <input type="text" name="username" id="username" class="form-control" required>
                </div>
                
                <div class="form-group mb-3">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" class="form-control" required>
                </div>

                <div class="form-group mb-3 form-check">
                    <input type="checkbox" name="remember" id="remember" class="form-check-input">
                    <label for="remember" class="form-check-label">Ingat Saya</label>
                </div>

                <div class="form-group mb-4">
                    <button type="submit" name="dw_login_submit" class="btn btn-primary w-100">Masuk</button>
                </div>
                
                <div class="auth-links text-center">
                    <p>Belum punya akun? <a href="<?php echo home_url('/register'); ?>">Daftar Disini</a></p>
                    <p><a href="<?php echo home_url('/lupa-password'); ?>">Lupa Password?</a></p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php get_footer(); ?>