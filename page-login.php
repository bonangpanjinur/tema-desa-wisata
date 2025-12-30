<?php
/**
 * Template Name: Halaman Login
 * Description: Halaman login kustom untuk Desa, Pedagang, Ojek, dan Verifikator.
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/dashboard' ) );
    exit;
}

get_header(); 
?>

<div class="dw-auth-container">
    <div class="dw-auth-box">
        <div class="dw-auth-header">
            <h2>Masuk ke Akun Anda</h2>
            <p>Silakan masuk untuk mengelola Desa, Toko, Ojek, atau Verifikasi UMKM.</p>
        </div>

        <?php
        $login_error = '';
        if ( isset( $_POST['dw_login_submit'] ) ) {
            $creds = array(
                'user_login'    => sanitize_text_field( $_POST['log'] ),
                'user_password' => $_POST['pwd'],
                'remember'      => isset( $_POST['rememberme'] ),
            );

            $user = wp_signon( $creds, false );

            if ( is_wp_error( $user ) ) {
                $login_error = $user->get_error_message();
            } else {
                // Redirect based on role handled in functions.php or specific dashboard page
                wp_redirect( home_url( '/dashboard' ) );
                exit;
            }
        }
        ?>

        <?php if ( ! empty( $login_error ) ) : ?>
            <div class="dw-alert dw-alert-danger">
                <?php echo $login_error; ?>
            </div>
        <?php endif; ?>

        <form name="loginform" id="loginform" action="" method="post" class="dw-auth-form">
            <div class="form-group">
                <label for="user_login">Username atau Email</label>
                <input type="text" name="log" id="user_login" class="input" value="" size="20" required>
            </div>
            <div class="form-group">
                <label for="user_pass">Password</label>
                <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" required>
            </div>
            
            <div class="form-options">
                <label class="remember-me">
                    <input name="rememberme" type="checkbox" id="rememberme" value="forever"> Ingat Saya
                </label>
                <a href="<?php echo home_url('/lupa-password'); ?>" class="forgot-password">Lupa Password?</a>
            </div>

            <div class="form-action">
                <button type="submit" name="dw_login_submit" class="btn btn-primary btn-block">Masuk Sekarang</button>
            </div>
            
            <div class="form-footer">
                <p>Belum punya akun? <a href="<?php echo home_url('/register'); ?>">Daftar sebagai Pedagang</a></p>
                <p><small>Atau hubungi Admin Desa untuk pendaftaran Ojek & Verifikator.</small></p>
            </div>
        </form>
    </div>
</div>

<style>
/* Simple Inline CSS for Login Page Consistency */
.dw-auth-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f4f6f9;
    padding: 20px;
}
.dw-auth-box {
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    width: 100%;
    max-width: 450px;
}
.dw-auth-header { text-align: center; margin-bottom: 30px; }
.dw-auth-header h2 { margin: 0 0 10px; color: #333; }
.dw-auth-header p { color: #666; font-size: 14px; margin: 0; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #444; }
.form-group .input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; transition: all 0.3s; }
.form-group .input:focus { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.1); outline: none; }
.form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; font-size: 13px; }
.btn-block { width: 100%; padding: 12px; font-size: 16px; }
.form-footer { text-align: center; margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px; }
.dw-alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
.dw-alert-danger { background-color: #fdecea; color: #dc3545; border: 1px solid #fccac7; }
</style>

<?php get_footer(); ?>