<?php
/**
 * Template Name: Halaman Lupa Password Custom
 * Description: Solusi Reset Password Smart (Auto-detect jika email gagal, langsung munculkan link ke Custom Page).
 */

// Redirect jika sudah login
if ( is_user_logged_in() ) {
    wp_redirect( home_url('/akun-saya') );
    exit;
}

get_header();

$notice_type = ''; // 'error', 'success', 'warning'
$notice_msg = '';
$manual_link = '';

// PROSES FORM
if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dw_lostpass_nonce']) && wp_verify_nonce($_POST['dw_lostpass_nonce'], 'dw_lostpass_action') ) {
    
    $user_input = trim($_POST['user_login']);

    if ( empty( $user_input ) ) {
        $notice_type = 'error';
        $notice_msg = 'Masukkan username atau email Anda.';
    } else {
        // 1. Cari User (Support Email atau Username)
        if ( strpos( $user_input, '@' ) ) {
            $user_data = get_user_by( 'email', $user_input );
        } else {
            $user_data = get_user_by( 'login', $user_input );
        }

        if ( ! $user_data ) {
            $notice_type = 'error';
            $notice_msg = 'Akun tidak ditemukan. Pastikan email atau username benar.';
        } else {
            // 2. Generate Key Reset Password (Native WP)
            $key = get_password_reset_key( $user_data );

            if ( is_wp_error( $key ) ) {
                $notice_type = 'error';
                $notice_msg = $key->get_error_message();
            } else {
                // 3. Siapkan Link & Email
                $login = $user_data->user_login;
                $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
                
                // --- PERBAIKAN PENTING DI SINI ---
                // Arahkan ke Halaman Custom (page-reset-password.php), bukan wp-login.php
                // Link ini akan mengarah ke: yoursite.com/reset-password/?key=...&login=...
                $reset_link = home_url("/reset-password/?key=$key&login=" . rawurlencode($login));

                $message = __( 'Permintaan reset password untuk:' ) . "\r\n\r\n";
                $message .= sprintf( __( 'Username: %s' ), $login ) . "\r\n\r\n";
                $message .= __( 'Klik tautan di bawah ini untuk membuat password baru:' ) . "\r\n\r\n";
                $message .= $reset_link . "\r\n";

                $title = sprintf( __( '[%s] Reset Kata Sandi' ), $site_name );

                // 4. Coba Kirim Email
                $mail_sent = false;
                try {
                    $mail_sent = wp_mail( $user_data->user_email, $title, $message );
                } catch (Exception $e) {
                    $mail_sent = false;
                }

                if ( $mail_sent ) {
                    // Skenario 1: Email Terkirim (Server Live)
                    $notice_type = 'success';
                    $notice_msg = 'Instruksi reset password telah dikirim ke email: ' . 
                                  substr($user_data->user_email, 0, 3) . '***' . strstr($user_data->user_email, '@');
                } else {
                    // Skenario 2: Email Gagal (Localhost/No SMTP) -> TAMPILKAN LINK LANGSUNG
                    $notice_type = 'warning';
                    $notice_msg = 'Server email tidak merespon (Mode Developer). Gunakan tombol di bawah untuk reset:';
                    $manual_link = $reset_link;
                }
            }
        }
    }
}
?>

<div class="auth-wrapper">
    <div class="auth-card">
        
        <div class="auth-header">
            <div class="icon-wrapper">
                <!-- Icon Key -->
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
            </div>
            <h2>Lupa Password?</h2>
            <p>Masukkan username atau email Anda, kami akan membantu memulihkan akun Anda.</p>
        </div>

        <!-- Notifikasi -->
        <?php if ( !empty($notice_msg) ) : ?>
            
            <?php if ($notice_type === 'success') : ?>
                <!-- Sukses Email -->
                <div class="alert alert-success">
                    <div class="alert-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <div class="alert-content">
                        <strong>Email Terkirim!</strong>
                        <p><?php echo esc_html($notice_msg); ?></p>
                        <a href="<?php echo home_url('/login'); ?>" class="btn-block mt-3" style="text-align:center; display:block; text-decoration:none;">Kembali ke Login</a>
                    </div>
                </div>

            <?php elseif ($notice_type === 'warning') : ?>
                <!-- Gagal Email / Manual Mode -->
                <div class="alert alert-warning">
                    <div class="alert-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    </div>
                    <div class="alert-content">
                        <strong>Mode Manual Aktif</strong>
                        <p><?php echo esc_html($notice_msg); ?></p>
                        <a href="<?php echo esc_url($manual_link); ?>" class="btn-primary mt-3">Buat Password Baru Sekarang</a>
                    </div>
                </div>

            <?php else : ?>
                <!-- Error -->
                <div class="alert alert-error">
                    <div class="alert-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <div class="alert-content">
                        <?php echo esc_html($notice_msg); ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

        <!-- Form (Hanya tampil jika belum sukses/warning) -->
        <?php if ( $notice_type !== 'success' && $notice_type !== 'warning' ) : ?>
            <form class="auth-form" action="" method="POST">
                <?php wp_nonce_field('dw_lostpass_action', 'dw_lostpass_nonce'); ?>
                
                <div class="form-group">
                    <label for="user_login">Username atau Email</label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        <input id="user_login" name="user_login" type="text" required placeholder="Contoh: user@desa.id">
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    Kirim Link Pemulihan
                </button>
            </form>

            <div class="auth-footer">
                <a href="<?php echo home_url('/login'); ?>" class="link-secondary">
                    &larr; Kembali ke Login
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
    /* Menggunakan style yang sama dengan page-reset-password.php untuk konsistensi */
    .auth-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 80vh;
        background-color: #f3f4f6;
        padding: 20px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    .auth-card {
        background: white;
        padding: 40px;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        width: 100%;
        max-width: 450px;
        border: 1px solid #e5e7eb;
    }

    .auth-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .auth-header .icon-wrapper {
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

    .auth-header h2 {
        font-size: 24px;
        font-weight: 800;
        color: #1f2937;
        margin: 0 0 8px;
    }

    .auth-header p {
        font-size: 14px;
        color: #6b7280;
        line-height: 1.5;
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
        padding: 12px 12px 12px 40px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
        box-sizing: border-box;
    }

    .input-wrapper input:focus {
        outline: none;
        border-color: #ea580c;
        box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
    }

    /* Buttons */
    .btn-submit, .btn-primary {
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
        text-align: center;
        text-decoration: none;
        display: inline-block;
        box-sizing: border-box;
    }

    .btn-submit:hover, .btn-primary:hover {
        background-color: #ea580c;
    }
    
    .btn-block {
        width: 100%;
        padding: 10px;
        background-color: #059669;
        color: white;
        border-radius: 8px;
        font-weight: 600;
    }
    .btn-block:hover {
        background-color: #047857;
    }

    /* Alerts */
    .alert {
        padding: 15px;
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
    
    .alert-warning {
        background-color: #fffbeb;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    .alert-icon {
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .mt-3 { margin-top: 12px; }

    /* Footer */
    .auth-footer {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #f3f4f6;
        text-align: center;
    }

    .link-secondary {
        font-size: 14px;
        text-decoration: none;
        font-weight: 600;
        color: #6b7280;
        transition: color 0.2s;
    }
    .link-secondary:hover { color: #111827; }

    /* Responsive */
    @media (max-width: 480px) {
        .auth-card {
            padding: 25px 20px;
        }
    }
</style>

<?php get_footer(); ?>