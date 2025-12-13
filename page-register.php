<?php 
/* Template Name: Halaman Register */
get_header(); 
?>

<div class="container">
    <div class="register-container">
        <h2 style="text-align: center; margin-bottom: 20px;">Daftar Akun Baru</h2>
        
        <!-- Pilihan Role -->
        <div class="role-selector">
            <div class="role-option active" data-role="pembeli" onclick="selectRole('pembeli')">
                <i class="fas fa-user"></i><br>Pembeli
            </div>
            <div class="role-option" data-role="pedagang" onclick="selectRole('pedagang')">
                <i class="fas fa-store"></i><br>Pedagang
            </div>
            <div class="role-option" data-role="desa" onclick="selectRole('desa')">
                <i class="fas fa-landmark"></i><br>Desa Wisata
            </div>
        </div>

        <!-- Form Pendaftaran (Untuk Pembeli & Pedagang) -->
        <form id="dw-register-form" method="post">
            <input type="hidden" name="role" id="selected_role" value="pembeli">
            <?php wp_nonce_field('dw_register_action', 'dw_register_nonce'); ?>

            <div id="common-fields">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="fullname" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Nomor HP (WhatsApp)</label>
                    <input type="text" name="phone" required placeholder="08...">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <!-- Field Khusus Pedagang (Hidden by default) -->
                <div id="pedagang-fields" class="hidden">
                    <div class="form-group">
                        <label>Nama Toko</label>
                        <input type="text" name="shop_name">
                    </div>
                </div>

                <button type="submit" name="dw_register_submit" class="btn-primary">Daftar Sekarang</button>
            </div>
        </form>

        <!-- Informasi Pendaftaran Desa (Hidden by default) -->
        <div id="desa-info" class="hidden" style="text-align: center;">
            <p>Pendaftaran akun <strong>Pengelola Desa Wisata</strong> memerlukan verifikasi manual.</p>
            <p>Silakan hubungi admin kami untuk mendaftarkan Desa Anda.</p>
            <?php echo do_shortcode('[dw_contact_admin]'); ?>
        </div>

        <p style="text-align: center; margin-top: 15px;">Sudah punya akun? <a href="<?php echo site_url('/login'); ?>" style="color: var(--primary-color);">Masuk disini</a></p>
    </div>
</div>

<script>
function selectRole(role) {
    // Update UI Selector
    document.querySelectorAll('.role-option').forEach(el => el.classList.remove('active'));
    document.querySelector(`.role-option[data-role="${role}"]`).classList.add('active');
    
    // Update Hidden Input
    document.getElementById('selected_role').value = role;

    // Logic Tampilan Form
    const commonFields = document.getElementById('common-fields');
    const pedagangFields = document.getElementById('pedagang-fields');
    const desaInfo = document.getElementById('desa-info');

    if (role === 'desa') {
        commonFields.style.display = 'none'; // Sembunyikan form
        desaInfo.classList.remove('hidden'); // Tampilkan info Desa
    } else {
        commonFields.style.display = 'block'; // Tampilkan form
        desaInfo.classList.add('hidden');
        
        if (role === 'pedagang') {
            pedagangFields.classList.remove('hidden');
            document.querySelector('input[name="shop_name"]').setAttribute('required', 'required');
        } else {
            pedagangFields.classList.add('hidden');
            document.querySelector('input[name="shop_name"]').removeAttribute('required');
        }
    }
}
</script>

<?php get_footer(); ?>