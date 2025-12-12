<?php
/**
 * Template Name: Halaman Login Custom
 * Description: Template khusus untuk halaman /login
 */

// 1. Jika user sudah login, tendang ke dashboard masing-masing
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    if ( in_array( 'pedagang', (array) $current_user->roles ) ) {
        wp_redirect( home_url( '/dashboard-toko/' ) );
    } elseif ( in_array( 'pembeli', (array) $current_user->roles ) ) {
        wp_redirect( home_url( '/akun-saya/' ) );
    } else {
        wp_redirect( admin_url() );
    }
    exit;
}

get_header(); 
?>

<div class="dw-login-container">
    <div class="dw-login-box">
        <h2>Masuk ke Desa Wisata</h2>
        
        <?php
        // Menampilkan pesan error atau sukses dari URL parameter
        if ( isset( $_GET['login'] ) && $_GET['login'] == 'failed' ) {
            echo '<div class="dw-alert error">Username atau Password salah.</div>';
        }
        if ( isset( $_GET['login'] ) && $_GET['login'] == 'empty' ) {
            echo '<div class="dw-alert warning">Silakan isi username dan password.</div>';
        }
        ?>

        <!-- Form Login yang mengarah ke pemroses di functions.php -->
        <form name="dw_login_form" id="dw_login_form" action="<?php echo esc_url( home_url( '/login/' ) ); ?>" method="post">
            
            <div class="form-group">
                <label for="dw_user_login">Username atau Email</label>
                <input type="text" name="dw_user_login" id="dw_user_login" class="input" required />
            </div>

            <div class="form-group">
                <label for="dw_user_pass">Password</label>
                <input type="password" name="dw_user_pass" id="dw_user_pass" class="input" required />
            </div>

            <div class="form-group checkbox">
                <label>
                    <input name="rememberme" type="checkbox" id="rememberme" value="forever" /> Ingat Saya
                </label>
            </div>

            <div class="form-actions">
                <!-- Nonce Security -->
                <?php wp_nonce_field( 'dw_login_action', 'dw_login_nonce' ); ?>
                <input type="submit" name="dw_login_submit" id="dw_login_submit" class="button button-primary" value="Masuk Sekarang" />
            </div>
            
        </form>
        
        <p class="dw-register-link">
            Belum punya akun? <a href="<?php echo home_url('/daftar/'); ?>">Daftar di sini</a>
        </p>
    </div>
</div>

<style>
    /* Style Sederhana untuk Login */
    .dw-login-container { display: flex; justify-content: center; align-items: center; min-height: 80vh; background: #f0f2f5; }
    .dw-login-box { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
    .dw-login-box h2 { text-align: center; margin-bottom: 20px; color: #333; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
    .form-group input[type="text"], .form-group input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
    .button-primary { width: 100%; padding: 12px; background: #0073aa; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
    .button-primary:hover { background: #005177; }
    .dw-alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 14px; text-align: center; }
    .dw-alert.error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
    .dw-alert.warning { background: #fff3e0; color: #ef6c00; border: 1px solid #ffe0b2; }
    .dw-register-link { text-align: center; margin-top: 15px; font-size: 14px; }
</style>

<?php get_footer(); ?>