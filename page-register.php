<?php
/**
 * Template Name: Halaman Pendaftaran
 * Description: Form registrasi custom untuk Pedagang dan Pembeli
 */

// Jika sudah login, redirect ke dashboard
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    if ( in_array( 'pedagang', (array) $current_user->roles ) ) {
        wp_redirect( home_url( '/dashboard-toko/' ) );
    } else {
        wp_redirect( home_url( '/akun-saya/' ) );
    }
    exit;
}

get_header(); ?>

<div class="dw-login-container" style="padding: 60px 20px; background: #f4f6f8; min-height: 80vh;">
    <div class="dw-login-box" style="background: white; max-width: 500px; margin: 0 auto; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        <h2 style="text-align: center; margin-bottom: 30px; color: var(--primary-dark);">Buat Akun Baru</h2>

        <?php
        // Menampilkan pesan error/sukses
        if ( isset( $_GET['register'] ) ) {
            if ( $_GET['register'] == 'success' ) {
                echo '<div class="dw-alert success" style="background: #e8f5e9; color: #2e7d32; padding: 15px; margin-bottom: 20px; border-radius: 4px;">Pendaftaran berhasil! Silakan cek email atau <a href="'.home_url('/login/').'">Login di sini</a>.</div>';
            } elseif ( $_GET['register'] == 'failed' ) {
                echo '<div class="dw-alert error" style="background: #ffebee; color: #c62828; padding: 15px; margin-bottom: 20px; border-radius: 4px;">Pendaftaran gagal. Username atau Email mungkin sudah terdaftar.</div>';
            } elseif ( $_GET['register'] == 'empty' ) {
                echo '<div class="dw-alert warning" style="background: #fff3e0; color: #ef6c00; padding: 15px; margin-bottom: 20px; border-radius: 4px;">Harap isi semua kolom wajib.</div>';
            }
        }
        ?>

        <form action="" method="post" id="dw-register-form">
            
            <!-- Nama Lengkap -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="dw_fullname" style="display: block; margin-bottom: 8px; font-weight: 500;">Nama Lengkap</label>
                <input type="text" name="dw_fullname" id="dw_fullname" class="input" required placeholder="Nama sesuai KTP">
            </div>

            <!-- Username -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="dw_username" style="display: block; margin-bottom: 8px; font-weight: 500;">Username</label>
                <input type="text" name="dw_username" id="dw_username" class="input" required placeholder="Tanpa spasi, cth: tokobudi">
            </div>

            <!-- Email -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="dw_email" style="display: block; margin-bottom: 8px; font-weight: 500;">Alamat Email</label>
                <input type="email" name="dw_email" id="dw_email" class="input" required placeholder="email@contoh.com">
            </div>

            <!-- Password -->
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="dw_password" style="display: block; margin-bottom: 8px; font-weight: 500;">Password</label>
                <input type="password" name="dw_password" id="dw_password" class="input" required minlength="6">
            </div>

            <!-- Pilihan Peran (Role) -->
            <div class="form-group" style="margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 500;">Saya ingin mendaftar sebagai:</label>
                <div style="display: flex; gap: 20px;">
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="dw_role" value="pembeli" checked> 
                        <span>Pembeli / Wisatawan</span>
                    </label>
                    <label style="cursor: pointer; display: flex; align-items: center; gap: 8px;">
                        <input type="radio" name="dw_role" value="pedagang"> 
                        <span>Pedagang Desa</span>
                    </label>
                </div>
                <small style="display: block; margin-top: 5px; color: #666;">*Pedagang memerlukan verifikasi admin desa nanti.</small>
            </div>

            <!-- Tombol Daftar -->
            <div class="form-actions">
                <?php wp_nonce_field( 'dw_register_action', 'dw_register_nonce' ); ?>
                <button type="submit" name="dw_register_submit" class="btn-primary" style="width: 100%; padding: 12px;">Daftar Sekarang</button>
            </div>

        </form>

        <p style="text-align: center; margin-top: 20px; color: #666;">
            Sudah punya akun? <a href="<?php echo home_url('/login/'); ?>" style="color: var(--primary-color); font-weight: 600;">Masuk di sini</a>
        </p>
    </div>
</div>

<?php get_footer(); ?>