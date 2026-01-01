<?php
/**
 * Template Name: Custom Reset Password
 * Description: Halaman untuk input password baru setelah klik link reset (Manual/Email).
 */

// 1. Jika user sudah login, tidak perlu reset, lempar ke dashboard
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/akun-saya') );
    exit;
}

get_header();

// 2. Ambil parameter dari URL
$key   = isset( $_GET['key'] ) ? $_GET['key'] : '';
$login = isset( $_GET['login'] ) ? $_GET['login'] : '';

$message = '';
$msg_type = ''; // 'success' atau 'error'
$show_form = false;
$user = false;

// 3. LOGIC VERIFIKASI KEY (Saat halaman dimuat)
if ( empty( $key ) || empty( $login ) ) {
    $msg_type = 'error';
    $message = 'Link reset password tidak valid atau parameter kurang.';
} else {
    // Fungsi WordPress untuk cek validitas key & user
    $user = check_password_reset_key( $key, $login );

    if ( is_wp_error( $user ) ) {
        $msg_type = 'error';
        $error_code = $user->get_error_code();
        
        if ( $error_code === 'expired_key' ) {
            $message = 'Link reset password sudah kadaluarsa. Silakan request ulang di halaman Lupa Password.';
        } else {
            $message = 'Link tidak valid atau sudah digunakan.';
        }
    } else {
        // Key Valid -> Tampilkan Form
        $show_form = true;
    }
}

// 4. LOGIC SIMPAN PASSWORD BARU (Saat form disubmit)
if ( $_SERVER['REQUEST_METHOD'] == 'POST' && $show_form && isset($_POST['pass1']) ) {
    $pass1 = $_POST['pass1'];
    $pass2 = $_POST['pass2'];

    if ( empty($pass1) || empty($pass2) ) {
        $msg_type = 'error';
        $message = 'Mohon isi password baru Anda.';
    } elseif ( $pass1 !== $pass2 ) {
        $msg_type = 'error';
        $message = 'Konfirmasi password tidak cocok.';
    } elseif ( strlen($pass1) < 6 ) { // Minimal 6 karakter (opsional)
        $msg_type = 'error';
        $message = 'Password terlalu pendek (min. 6 karakter).';
    } else {
        // PROSES RESET
        reset_password( $user, $pass1 );
        
        $msg_type = 'success';
        $message = 'Password berhasil diubah! Silakan login dengan password baru.';
        $show_form = false; // Sembunyikan form setelah sukses
    }
}
?>

<div class="reset-pass-wrapper">
    <div class="reset-pass-card">
        
        <!-- Header Section -->
        <div class="reset-pass-header">
            <div class="icon-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            </div>
            <h2>Reset Password</h2>
            <?php if ($show_form): ?>
                <p>Buat password baru untuk akun <strong><?php echo esc_html($login); ?></strong></p>
            <?php endif; ?>
        </div>

        <!-- Notification Area -->
        <?php if ( ! empty( $message ) ) : ?>
            <div class="alert <?php echo ($msg_type === 'success') ? 'alert-success' : 'alert-error'; ?>">
                <div class="alert-icon">
                    <?php if ($msg_type === 'success'): ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <?php else: ?>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <?php endif; ?>
                </div>
                <div class="alert-content">
                    <?php echo $message; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Form Reset Password -->
        <?php if ( $show_form ) : ?>
            <form class="reset-form" action="" method="POST">
                
                <!-- New Password -->
                <div class="form-group">
                    <label for="pass1">Password Baru</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
                        </span>
                        <input id="pass1" name="pass1" type="password" required placeholder="Minimal 6 karakter" autocomplete="off">
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="pass2">Ulangi Password Baru</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                        </span>
                        <input id="pass2" name="pass2" type="password" required placeholder="Ketik ulang password">
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit">
                    Simpan Password Baru
                </button>
            </form>
        <?php endif; ?>

        <!-- Footer Actions -->
        <div class="reset-footer">
            <?php if ( $msg_type === 'success' ) : ?>
                <a href="<?php echo home_url('/login'); ?>" class="link-primary">
                    Login Sekarang &rarr;
                </a>
            <?php elseif ( !$show_form ) : ?>
                <a href="<?php echo home_url('/lupa-password'); ?>" class="link-secondary">
                    Request Ulang Link
                </a>
            <?php endif; ?>
            
            <?php if ( $show_form ) : ?>
                <a href="<?php echo home_url('/login'); ?>" class="link-muted">
                    Batal
                </a>
            <?php endif; ?>
        </div>

    </div>
</div>

<style>
    /* Reset CSS sederhana untuk halaman ini */
    .reset-pass-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh;
        background-color: #f3f4f6;
        padding: 20px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    .reset-pass-card {
        background: white;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        width: 100%;
        max-width: 450px;
        border: 1px solid #e5e7eb;
    }

    .reset-pass-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .reset-pass-header .icon-wrapper {
        background-color: #fff7ed;
        color: #ea580c;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
    }

    .reset-pass-header h2 {
        font-size: 24px;
        font-weight: 800;
        color: #1f2937;
        margin: 0 0 8px;
    }

    .reset-pass-header p {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }

    .input-wrapper {
        position: relative;
    }

    .input-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        display: flex;
    }

    .input-wrapper input {
        width: 100%;
        padding: 12px 12px 12px 40px; /* Space for icon */
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
        box-sizing: border-box; /* Penting agar padding tidak merusak width */
    }

    .input-wrapper input:focus {
        outline: none;
        border-color: #ea580c;
        box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
    }

    /* Button */
    .btn-submit {
        width: 100%;
        padding: 12px;
        background-color: #1f2937;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.2s;
    }

    .btn-submit:hover {
        background-color: #ea580c;
        transform: translateY(-1px);
    }

    /* Alerts */
    .alert {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        line-height: 1.5;
    }

    .alert-success {
        background-color: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .alert-error {
        background-color: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .alert-icon {
        flex-shrink: 0;
        margin-top: 2px;
    }

    /* Footer */
    .reset-footer {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #f3f4f6;
        text-align: center;
        display: flex;
        justify-content: center;
        gap: 15px;
    }

    .reset-footer a {
        font-size: 14px;
        text-decoration: none;
        font-weight: 600;
        transition: color 0.2s;
    }

    .link-primary { color: #ea580c; }
    .link-primary:hover { color: #c2410c; }

    .link-secondary { color: #6b7280; }
    .link-secondary:hover { color: #111827; }
    
    .link-muted { color: #9ca3af; }
    .link-muted:hover { color: #6b7280; }

    /* Responsive */
    @media (max-width: 480px) {
        .reset-pass-card {
            padding: 25px 20px;
        }
    }
</style>

<?php get_footer(); ?>