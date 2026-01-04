<?php
/**
 * Template Name: Edit Profil
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_user_logged_in() ) {
    wp_safe_redirect( wp_login_url( get_permalink() ) );
    exit;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();
$pesan = '';
$msg_type = '';

global $wpdb;
$table_pedagang = $wpdb->prefix . 'dw_pedagang';

// --- LOGIC HANDLING ---
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['submit_profil'] ) ) {
    // 1. Verify Nonce
    if ( ! isset( $_POST['dw_profil_nonce'] ) || ! wp_verify_nonce( $_POST['dw_profil_nonce'], 'dw_action_update_profil' ) ) {
        wp_die( 'Security check failed.' );
    }

    // 2. Sanitasi Input
    $display_name = sanitize_text_field( $_POST['display_name'] );
    
    // Update WP User
    wp_update_user([
        'ID' => $user_id,
        'display_name' => $display_name
    ]);

    if ( in_array( 'pedagang', $user->roles ) || in_array( 'pedagang_toko', $user->roles ) ) {
        $nama_toko = sanitize_text_field( $_POST['nama_toko'] );
        $no_wa     = sanitize_text_field( $_POST['nomor_wa'] );
        $alamat    = sanitize_textarea_field( $_POST['alamat'] );
        // Validasi No Rekening (Hanya angka)
        $no_rek    = preg_replace('/[^0-9]/', '', $_POST['no_rekening'] ?? '');

        // 3. Update Custom Table
        $wpdb->update( 
            $table_pedagang, 
            array( 
                'nama_toko' => $nama_toko, 
                'nomor_wa' => $no_wa, 
                'alamat_lengkap' => $alamat,
                'no_rekening' => $no_rek 
            ), 
            array( 'id_user' => $user_id ), 
            array( '%s', '%s', '%s', '%s' ), 
            array( '%d' )
        );
    }

    $pesan = 'Profil berhasil disimpan.';
    $msg_type = 'success';
}

// Ambil data untuk value input
$data_pedagang = null;
if ( in_array( 'pedagang', $user->roles ) || in_array( 'pedagang_toko', $user->roles ) ) {
    $data_pedagang = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_pedagang WHERE id_user = %d", $user_id) );
}

get_header();
?>

<div class="bg-gray-50 min-h-screen py-10">
    <div class="container mx-auto px-4 max-w-2xl">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Edit Profil</h1>

        <?php if($pesan): ?>
            <div class="bg-green-100 text-green-700 p-4 rounded-xl mb-6 border border-green-200">
                <?php echo esc_html($pesan); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 space-y-5">
            <?php wp_nonce_field( 'dw_action_update_profil', 'dw_profil_nonce' ); ?>
            
            <div class="flex items-center gap-4 mb-4">
                <img src="<?php echo esc_url(get_avatar_url($user_id)); ?>" class="w-20 h-20 rounded-full border-4 border-gray-50">
                <div>
                    <p class="font-bold text-gray-800"><?php echo esc_html($user->user_login); ?></p>
                    <p class="text-sm text-gray-500">Role: <?php echo esc_html(ucfirst($user->roles[0] ?? 'User')); ?></p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
            </div>

            <?php if ( $data_pedagang ): ?>
            <div class="pt-4 border-t border-gray-100">
                <h3 class="font-bold text-primary mb-3">Info Toko (Pedagang)</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Nama Toko</label>
                        <input type="text" name="nama_toko" value="<?php echo esc_attr( $data_pedagang->nama_toko ?? '' ); ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">No. WhatsApp</label>
                        <input type="text" name="nomor_wa" value="<?php echo esc_attr( $data_pedagang->nomor_wa ?? '' ); ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">No. Rekening</label>
                        <input type="text" name="no_rekening" value="<?php echo esc_attr( $data_pedagang->no_rekening ?? '' ); ?>" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Alamat Toko</label>
                        <textarea name="alamat" rows="3" class="w-full border-gray-300 rounded-lg focus:ring-primary focus:border-primary"><?php echo esc_textarea( $data_pedagang->alamat_lengkap ?? '' ); ?></textarea>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="pt-4">
                <button type="submit" name="submit_profil" class="w-full bg-primary hover:bg-green-700 text-white font-bold py-3 rounded-xl transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<?php get_footer(); ?>
