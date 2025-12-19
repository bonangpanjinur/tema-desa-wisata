<?php
/**
 * Template Name: Dashboard Ojek
 * * Halaman manajemen khusus untuk Driver Ojek.
 */

// 1. Cek Login
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login?redirect_to=' . urlencode(get_permalink())));
    exit;
}

$user_id = get_current_user_id();
global $wpdb;
$table_ojek = $wpdb->prefix . 'dw_ojek';

// 2. Handler: Update Status & Profil (Logic PHP langsung di sini agar tidak perlu edit functions.php)
$msg_success = '';
$msg_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dw_ojek_action'])) {
    
    // Verifikasi Nonce (Keamanan sederhana)
    // if (!isset($_POST['dw_nonce']) || !wp_verify_nonce($_POST['dw_nonce'], 'dw_ojek_action')) { ... }

    if ($_POST['dw_ojek_action'] === 'toggle_status') {
        $current_status = intval($_POST['current_status']);
        $new_status = ($current_status === 1) ? 0 : 1;
        
        $wpdb->update(
            $table_ojek,
            ['is_online' => $new_status],
            ['id_user' => $user_id]
        );
        $msg_success = ($new_status === 1) ? 'Status sekarang ONLINE. Siap menerima order!' : 'Status sekarang OFFLINE.';
    }

    if ($_POST['dw_ojek_action'] === 'update_profile') {
        $no_hp = sanitize_text_field($_POST['no_hp']);
        $motor = sanitize_text_field($_POST['merk_motor']);
        $plat  = sanitize_text_field($_POST['plat_nomor']);
        
        $wpdb->update(
            $table_ojek,
            [
                'no_hp' => $no_hp,
                'merk_motor' => $motor,
                'plat_nomor' => $plat
            ],
            ['id_user' => $user_id]
        );
        $msg_success = 'Profil berhasil diperbarui.';
    }
}

// 3. Ambil Data Driver
$driver = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_ojek WHERE id_user = %d", $user_id));

// Jika user login tapi belum terdaftar sebagai ojek
if (!$driver) {
    wp_redirect(home_url('/ojek')); // Redirect ke halaman daftar
    exit;
}

get_header();
?>

<style>
    /* VARIABLES (Konsisten dengan Front Page) */
    :root {
        --primary: #1976D2; /* Biru Ojek */
        --primary-dark: #1565C0;
        --success: #4CAF50;
        --danger: #F44336;
        --bg-body: #F5F7FA;
        --white: #FFFFFF;
        --text-main: #212121;
        --text-sub: #757575;
        --radius-md: 12px;
        --shadow: 0 4px 20px rgba(0,0,0,0.05);
        --header-height: 70px;
        --safe-top-padding: calc(var(--header-height) + 20px);
    }

    body {
        background-color: var(--bg-body);
        color: var(--text-main);
    }

    .app-wrapper {
        max-width: 600px;
        margin: 0 auto;
        padding: 0 16px;
        padding-top: var(--safe-top-padding);
        padding-bottom: 80px;
        min-height: 100vh;
    }

    /* HEADER */
    .dash-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 24px;
    }
    .dash-title h1 { font-size: 22px; font-weight: 800; margin: 0; }
    .dash-title p { font-size: 13px; color: var(--text-sub); margin: 0; }
    .logout-btn {
        font-size: 12px; font-weight: 600; color: var(--danger);
        text-decoration: none; padding: 6px 12px;
        background: #ffebee; border-radius: 20px;
    }

    /* STATUS CARD */
    .status-card {
        background: var(--white);
        border-radius: var(--radius-md);
        padding: 20px;
        box-shadow: var(--shadow);
        text-align: center;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
    }
    
    .status-indicator {
        width: 80px; height: 80px;
        margin: 0 auto 16px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 32px;
        transition: 0.3s;
    }
    
    /* Dynamic Style based on status */
    .is-online .status-indicator { background: #e8f5e9; color: var(--success); box-shadow: 0 0 0 8px #f1f8e9; }
    .is-offline .status-indicator { background: #ffebee; color: var(--danger); box-shadow: 0 0 0 8px #fffde7; }
    
    .status-text { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
    .status-sub { font-size: 12px; color: var(--text-sub); margin-bottom: 20px; }

    .btn-toggle {
        display: inline-block;
        padding: 12px 30px;
        border-radius: 30px;
        font-weight: 700;
        font-size: 14px;
        border: none;
        cursor: pointer;
        transition: 0.2s;
        width: 100%;
    }
    .btn-go-online { background: var(--success); color: white; box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3); }
    .btn-go-offline { background: #ef5350; color: white; box-shadow: 0 4px 10px rgba(244, 67, 54, 0.3); }

    /* INFO CARD */
    .info-card {
        background: var(--white);
        border-radius: var(--radius-md);
        padding: 20px;
        box-shadow: var(--shadow);
        margin-bottom: 20px;
    }
    .card-head {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 16px; border-bottom: 1px solid #eee; padding-bottom: 10px;
    }
    .card-head h3 { margin: 0; font-size: 16px; font-weight: 700; }
    
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 12px; font-weight: 600; color: #555; margin-bottom: 6px; }
    .form-control {
        width: 100%; padding: 12px;
        border: 1px solid #e0e0e0; border-radius: 8px;
        font-size: 14px; background: #fafafa;
    }
    .form-control:focus { background: #fff; border-color: var(--primary); outline: none; }

    .btn-save {
        width: 100%; padding: 14px;
        background: var(--primary); color: white;
        border: none; border-radius: 10px;
        font-weight: 700; cursor: pointer;
    }

    /* ALERT */
    .alert {
        padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: center;
    }
    .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
</style>

<main class="app-wrapper">
    
    <!-- HEADER -->
    <div class="dash-header">
        <div class="dash-title">
            <h1>Halo, <?php echo esc_html(explode(' ', $driver->nama)[0]); ?>!</h1>
            <p>Dashboard Driver Ojek</p>
        </div>
        <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Keluar
        </a>
    </div>

    <!-- NOTIFICATION -->
    <?php if ($msg_success) : ?>
        <div class="alert alert-success"><?php echo esc_html($msg_success); ?></div>
    <?php endif; ?>

    <!-- 1. STATUS SWITCHER -->
    <div class="status-card <?php echo ($driver->is_online == 1) ? 'is-online' : 'is-offline'; ?>">
        <div class="status-indicator">
            <i class="fas fa-power-off"></i>
        </div>
        
        <?php if ($driver->is_online == 1) : ?>
            <div class="status-text text-green-600">Anda Sedang Online</div>
            <div class="status-sub">Siap menerima pesanan dari warga</div>
            
            <form method="POST">
                <input type="hidden" name="dw_ojek_action" value="toggle_status">
                <input type="hidden" name="current_status" value="1">
                <button type="submit" class="btn-toggle btn-go-offline">Matikan (Offline)</button>
            </form>
        <?php else : ?>
            <div class="status-text text-red-500">Anda Sedang Offline</div>
            <div class="status-sub">Tidak muncul di pencarian warga</div>
            
            <form method="POST">
                <input type="hidden" name="dw_ojek_action" value="toggle_status">
                <input type="hidden" name="current_status" value="0">
                <button type="submit" class="btn-toggle btn-go-online">Nyalakan (Online)</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- 2. EDIT PROFILE -->
    <div class="info-card">
        <div class="card-head">
            <h3>Informasi Kendaraan</h3>
            <i class="fas fa-motorcycle text-gray-400"></i>
        </div>

        <form method="POST">
            <input type="hidden" name="dw_ojek_action" value="update_profile">
            
            <div class="form-group">
                <label class="form-label">Merk Motor</label>
                <input type="text" name="merk_motor" class="form-control" value="<?php echo esc_attr($driver->merk_motor); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Plat Nomor</label>
                <input type="text" name="plat_nomor" class="form-control" value="<?php echo esc_attr($driver->plat_nomor); ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Nomor WhatsApp (Aktif)</label>
                <input type="text" name="no_hp" class="form-control" value="<?php echo esc_attr($driver->no_hp); ?>" required>
            </div>

            <button type="submit" class="btn-save">Simpan Perubahan</button>
        </form>
    </div>

    <!-- 3. VERIFICATION STATUS -->
    <div class="info-card">
        <div class="card-head">
            <h3>Status Akun</h3>
            <i class="fas fa-shield-alt text-gray-400"></i>
        </div>
        <div style="font-size:13px; color:#555;">
            <?php if ($driver->status === 'aktif') : ?>
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <span><strong>Terverifikasi:</strong> Akun Anda aktif dan dapat digunakan.</span>
                </div>
            <?php else : ?>
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fas fa-clock text-orange-500"></i>
                    <span><strong>Menunggu Verifikasi:</strong> Admin Desa sedang meninjau data Anda.</span>
                </div>
            <?php endif; ?>
        </div>
    </div>

</main>

<?php get_footer(); ?>