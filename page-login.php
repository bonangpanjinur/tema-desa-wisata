<?php
/**
 * Template Name: Halaman Login
 * Description: Halaman login kustom untuk Desa, Pedagang, Ojek, dan Verifikator.
 */

if ( is_user_logged_in() ) {
    // Redirect handled by functions.php logic usually, but as fallback:
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
                // Redirect logic based on role handled in functions.php 
                // But we add a safety redirect here just in case
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
                <input type="text" name="log" id="user_login" class="input" value="" size="20" required placeholder="Masukkan username/email">
            </div>
            <div class="form-group">
                <label for="user_pass">Password</label>
                <input type="password" name="pwd" id="user_pass" class="input" value="" size="20" required placeholder="Masukkan password">
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
                <p class="small-text">Untuk pendaftaran Ojek & Verifikator, silakan hubungi Admin Desa.</p>
            </div>
        </form>
    </div>
</div>

<style>
/* CSS Khusus Halaman Login */
.dw-auth-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f2f5;
    padding: 40px 20px;
}
.dw-auth-box {
    background: #fff;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    width: 100%;
    max-width: 420px;
}
.dw-auth-header { text-align: center; margin-bottom: 30px; }
.dw-auth-header h2 { margin: 0 0 10px; color: #1d2327; font-size: 24px; font-weight: 700; }
.dw-auth-header p { color: #646970; font-size: 14px; margin: 0; line-height: 1.5; }

.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #1d2327; font-size: 14px; }
.form-group .input { 
    width: 100%; 
    padding: 12px 15px; 
    border: 1px solid #dcdcde; 
    border-radius: 6px; 
    font-size: 14px; 
    transition: all 0.2s;
    background: #fff;
    box-sizing: border-box;
}
.form-group .input:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }

.form-options { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; font-size: 13px; color: #50575e; }
.forgot-password { color: #2271b1; text-decoration: none; }
.forgot-password:hover { text-decoration: underline; }

.btn-block { width: 100%; padding: 12px; font-size: 16px; font-weight: 600; cursor: pointer; }
.btn-primary { background-color: #2271b1; color: #fff; border: none; border-radius: 6px; transition: background 0.2s; }
.btn-primary:hover { background-color: #135e96; }

.form-footer { text-align: center; margin-top: 30px; border-top: 1px solid #f0f0f1; padding-top: 20px; font-size: 14px; color: #646970; }
.form-footer a { color: #2271b1; text-decoration: none; font-weight: 600; }
.form-footer a:hover { text-decoration: underline; }
.form-footer .small-text { font-size: 12px; margin-top: 10px; color: #8c8f94; }

.dw-alert { padding: 12px 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; line-height: 1.4; }
.dw-alert-danger { background-color: #fbeaea; color: #d63638; border: 1px solid #f5c6cb; }
</style>

<?php get_footer(); ?>