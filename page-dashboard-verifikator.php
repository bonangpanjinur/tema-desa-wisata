<?php
/**
 * Template Name: Dashboard Verifikator
 * Description: Dashboard khusus untuk role Verifikator UMKM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Pastikan user adalah verifikator
$current_user = wp_get_current_user();
if ( ! in_array( 'verifikator_umkm', $current_user->roles ) && ! in_array( 'administrator', $current_user->roles ) ) {
    wp_redirect( home_url( '/dashboard' ) );
    exit;
}

get_header();

global $wpdb;
$user_id = get_current_user_id();

// Ambil Data Verifikator dari Tabel Khusus
$table_verifikator = $wpdb->prefix . 'dw_verifikator';
$verifikator = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_verifikator WHERE id_user = %d", $user_id ) );

// Jika data belum ada di tabel dw_verifikator (tapi role sudah ada)
if ( ! $verifikator ) {
    echo '<div class="dw-container" style="padding:40px; text-align:center;"><h2>Akun Anda belum terhubung dengan Data Verifikator.</h2><p>Silakan hubungi Admin Desa.</p></div>';
    get_footer();
    exit;
}

// Ambil Statistik
$saldo = $verifikator->saldo_saat_ini;
$total_pendapatan = $verifikator->total_pendapatan_komisi;
$kode_referral = $verifikator->kode_referral;

// Ambil Daftar Pedagang Binaan
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$pedagang_binaan = $wpdb->get_results( $wpdb->prepare( 
    "SELECT * FROM $table_pedagang WHERE id_verifikator = %d ORDER BY created_at DESC", 
    $verifikator->id 
) );
?>

<div class="dw-dashboard-wrapper">
    <div class="dw-container">
        
        <!-- Header Dashboard -->
        <div class="dw-dash-header">
            <div class="welcome-text">
                <h1>Halo, <?php echo esc_html($verifikator->nama_lengkap); ?></h1>
                <span class="role-badge">Verifikator UMKM</span>
            </div>
            <div class="header-actions">
                <a href="<?php echo wp_logout_url( home_url() ); ?>" class="btn btn-outline-danger btn-sm">Keluar</a>
            </div>
        </div>

        <!-- Statistik Cards -->
        <div class="dw-stats-grid">
            <!-- Card Saldo -->
            <div class="dw-stat-card card-primary">
                <div class="stat-icon"><i class="dashicons dashicons-wallet"></i></div>
                <div class="stat-content">
                    <h3>Saldo Komisi</h3>
                    <div class="stat-value">Rp <?php echo number_format($saldo, 0, ',', '.'); ?></div>
                    <p class="stat-desc">Belum dicairkan</p>
                </div>
            </div>

            <!-- Card Total Pendapatan -->
            <div class="dw-stat-card card-success">
                <div class="stat-icon"><i class="dashicons dashicons-chart-line"></i></div>
                <div class="stat-content">
                    <h3>Total Pendapatan</h3>
                    <div class="stat-value">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
                    <p class="stat-desc">Akumulasi seumur hidup</p>
                </div>
            </div>

            <!-- Card Kode Referral -->
            <div class="dw-stat-card card-info">
                <div class="stat-icon"><i class="dashicons dashicons-admin-links"></i></div>
                <div class="stat-content">
                    <h3>Kode Referral</h3>
                    <div class="stat-value referral-code" onclick="copyToClipboard('<?php echo esc_js($kode_referral); ?>')">
                        <?php echo esc_html($kode_referral); ?> <span class="dashicons dashicons-clipboard" style="font-size:16px; cursor:pointer;"></span>
                    </div>
                    <p class="stat-desc">Bagikan ke calon pedagang</p>
                </div>
            </div>
            
            <!-- Card Total Binaan -->
            <div class="dw-stat-card card-warning">
                <div class="stat-icon"><i class="dashicons dashicons-store"></i></div>
                <div class="stat-content">
                    <h3>Pedagang Binaan</h3>
                    <div class="stat-value"><?php echo count($pedagang_binaan); ?></div>
                    <p class="stat-desc">Toko terdaftar</p>
                </div>
            </div>
        </div>

        <!-- Konten Utama: Daftar Pedagang -->
        <div class="dw-content-section" style="margin-top: 30px;">
            <div class="dw-card">
                <div class="dw-card-header">
                    <h3>Daftar Pedagang Binaan</h3>
                </div>
                <div class="dw-card-body table-responsive">
                    <?php if ( empty($pedagang_binaan) ) : ?>
                        <div class="empty-state">
                            <p>Belum ada pedagang yang mendaftar menggunakan kode referral Anda.</p>
                        </div>
                    <?php else : ?>
                        <table class="dw-table">
                            <thead>
                                <tr>
                                    <th>Nama Toko</th>
                                    <th>Pemilik</th>
                                    <th>WhatsApp</th>
                                    <th>Status Akun</th>
                                    <th>Terdaftar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $pedagang_binaan as $p ) : ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($p->nama_toko); ?></strong></td>
                                        <td><?php echo esc_html($p->nama_pemilik); ?></td>
                                        <td>
                                            <?php if($p->nomor_wa): ?>
                                                <a href="https://wa.me/<?php echo preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $p->nomor_wa)); ?>" target="_blank" class="text-success">
                                                    <i class="dashicons dashicons-whatsapp"></i> Hubungi
                                                </a>
                                            <?php else: ?> - <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            $badge_class = ($p->status_akun == 'aktif') ? 'badge-success' : 'badge-warning';
                                            echo '<span class="dw-badge ' . $badge_class . '">' . ucfirst($p->status_akun) . '</span>';
                                            ?>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($p->created_at)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Kode Referral disalin: ' + text);
    }, function(err) {
        console.error('Gagal menyalin: ', err);
    });
}
</script>

<style>
/* CSS Spesifik Dashboard Verifikator (Bisa dipindah ke style.css nanti) */
.dw-dashboard-wrapper { padding: 40px 0; background-color: #f8f9fa; min-height: 80vh; }
.dw-dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.dw-dash-header h1 { font-size: 24px; margin: 0; color: #333; }
.role-badge { background: #e2e6ea; color: #495057; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; vertical-align: middle; margin-left: 10px; }

/* Stats Grid */
.dw-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
.dw-stat-card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; align-items: center; transition: transform 0.2s; }
.dw-stat-card:hover { transform: translateY(-5px); }
.dw-stat-card .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-size: 24px; }
.dw-stat-card .stat-content h3 { margin: 0 0 5px; font-size: 14px; color: #6c757d; font-weight: 600; text-transform: uppercase; }
.dw-stat-card .stat-value { font-size: 20px; font-weight: 700; color: #333; }
.dw-stat-card .stat-desc { margin: 5px 0 0; font-size: 12px; color: #adb5bd; }

/* Card Colors */
.card-primary .stat-icon { background: #e8f2ff; color: #0d6efd; }
.card-success .stat-icon { background: #e6f8f0; color: #00a32a; }
.card-info .stat-icon { background: #e0f7fa; color: #00bcd4; }
.card-warning .stat-icon { background: #fff8e1; color: #ffc107; }

/* Table Style */
.dw-card { background: #fff; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); overflow: hidden; }
.dw-card-header { padding: 20px; border-bottom: 1px solid #eee; }
.dw-card-header h3 { margin: 0; font-size: 18px; }
.dw-card-body { padding: 20px; }
.dw-table { width: 100%; border-collapse: collapse; }
.dw-table th { text-align: left; padding: 12px; border-bottom: 2px solid #f1f1f1; color: #666; font-size: 14px; }
.dw-table td { padding: 12px; border-bottom: 1px solid #f1f1f1; font-size: 14px; color: #333; }
.dw-badge { padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-success { background: #d4edda; color: #155724; }
.badge-warning { background: #fff3cd; color: #856404; }
.referral-code { cursor: pointer; border: 1px dashed #ccc; padding: 2px 8px; border-radius: 4px; display: inline-block; background: #fafafa; }
.referral-code:hover { background: #eee; border-color: #999; }
</style>

<?php get_footer(); ?>