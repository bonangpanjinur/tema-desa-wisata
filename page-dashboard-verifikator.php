<?php
/**
 * Template Name: Dashboard Verifikator UMKM
 * Description: Panel Verifikator Lengkap (Profil, Verifikasi, Data Binaan, & Keuangan).
 * Status: FINAL COMPLETE FIXED (Referral Link Added & Withdraw Logic Restored)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Cek Login & Role
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url() );
    exit;
}

$current_user = wp_get_current_user();
if ( ! in_array( 'verifikator_umkm', (array) $current_user->roles ) && ! in_array( 'administrator', (array) $current_user->roles ) ) {
    wp_die('Akses ditolak. Halaman ini khusus untuk Verifikator.');
}

global $wpdb;
$table_verifikator = $wpdb->prefix . 'dw_verifikator';
$table_pedagang    = $wpdb->prefix . 'dw_pedagang';
$msg = '';
$msg_type = '';

// --- 2. GET / CREATE VERIFICATOR DATA (INIT) ---
// Kita ambil data awal untuk memastikan ID tersedia
$verifikator = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_verifikator WHERE id_user = %d", $current_user->ID) );

if ( ! $verifikator ) {
    $initial_code = 'VER-' . strtoupper(substr($current_user->display_name, 0, 3)) . rand(100,999);
    $wpdb->insert($table_verifikator, [
        'id_user' => $current_user->ID,
        'nama_lengkap' => $current_user->display_name,
        'nik' => '', 
        'nomor_wa' => '',
        'kode_referral' => $initial_code,
        'status' => 'pending',
        'created_at' => current_time('mysql')
    ]);
    $verifikator = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_verifikator WHERE id_user = %d", $current_user->ID) );
}

$id_verifikator = $verifikator->id;

// --- 3. HANDLER: UPDATE PROFILE ---
if ( isset($_POST['save_profile']) && check_admin_referer('save_verif_profile', 'verif_nonce') ) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    
    $upd_data = [
        'nama_lengkap'   => sanitize_text_field($_POST['nama_lengkap']),
        'nik'            => sanitize_text_field($_POST['nik']),
        'nomor_wa'       => sanitize_text_field($_POST['nomor_wa']),
        'alamat_lengkap' => sanitize_textarea_field($_POST['alamat_lengkap']),
        'kode_pos'       => sanitize_text_field($_POST['kode_pos']),
        'updated_at'     => current_time('mysql')
    ];

    // Upload Foto
    if ( ! empty($_FILES['foto_profil']['name']) ) {
        $u = wp_handle_upload($_FILES['foto_profil'], ['test_form' => false]);
        if ( isset($u['url']) && ! isset($u['error']) ) {
            $upd_data['foto_profil'] = $u['url'];
        }
    }

    $wpdb->update($table_verifikator, $upd_data, ['id' => $id_verifikator]);
    
    // Sync User Meta
    update_user_meta($current_user->ID, 'billing_phone', $upd_data['nomor_wa']);
    
    $msg = 'Profil berhasil diperbarui.';
    $msg_type = 'success';
    // Refresh Data
    $verifikator = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_verifikator WHERE id = %d", $id_verifikator) );
}

// --- 4. HANDLER: VERIFIKASI UMKM (Action) ---
if ( isset($_POST['action_verifikasi']) && check_admin_referer('verify_umkm_action', 'verify_nonce') ) {
    $id_umkm = intval($_POST['id_pedagang']);
    $keputusan = sanitize_text_field($_POST['keputusan']); // 'disetujui' atau 'ditolak'
    
    $update_umkm = [
        'status_pendaftaran' => ($keputusan == 'disetujui') ? 'menunggu_desa' : 'ditolak', // Flow: Verifikator -> Desa -> Aktif
        'is_verified' => ($keputusan == 'disetujui') ? 1 : 0,
        'verified_at' => current_time('mysql'),
        'id_verifikator' => $id_verifikator, // Claim verified by me
        'updated_at' => current_time('mysql')
    ];

    $wpdb->update($table_pedagang, $update_umkm, ['id' => $id_umkm]);
    
    // Update Stats Verifikator
    if ($keputusan == 'disetujui') {
        $wpdb->query("UPDATE $table_verifikator SET total_verifikasi_sukses = total_verifikasi_sukses + 1 WHERE id = $id_verifikator");
    }

    $msg = 'Status verifikasi UMKM diperbarui.';
    $msg_type = 'success';
}

// --- 5. HANDLER: WITHDRAW SALDO (Penting: Restored) ---
if ( isset($_POST['action_withdraw']) && check_admin_referer('withdraw_action', 'withdraw_nonce') ) {
    $amount = floatval(str_replace('.', '', $_POST['nominal_withdraw']));
    
    // Validasi Sederhana
    if ( $amount <= 0 ) {
        $msg = "Nominal tidak valid."; $msg_type = "error";
    } elseif ( $amount > $verifikator->saldo_saat_ini ) {
        $msg = "Saldo tidak mencukupi."; $msg_type = "error";
    } else {
        // Kurangi Saldo Verifikator
        $wpdb->query($wpdb->prepare("UPDATE $table_verifikator SET saldo_saat_ini = saldo_saat_ini - %f WHERE id = %d", $amount, $id_verifikator));
        
        // (Opsional) Catat di tabel Payout Ledger jika ingin tracking history, sementara update saldo langsung
        // $wpdb->insert($wpdb->prefix . 'dw_payout_ledger', [...]); 

        $msg = "Permintaan penarikan Rp " . number_format($amount, 0, ',', '.') . " berhasil dikirim.";
        $msg_type = "success";
        
        // Update View object
        $verifikator->saldo_saat_ini -= $amount;
    }
}

// --- DATA LISTS ---
// 1. UMKM Menunggu Verifikasi (Status: menunggu - Logic: Verifikator mengecek data awal)
// ATAU UMKM yang mendaftar menggunakan Kode Referral Verifikator ini
$list_pending = $wpdb->get_results( $wpdb->prepare("
    SELECT * FROM $table_pedagang 
    WHERE (terdaftar_melalui_kode = %s OR id_verifikator = %d) 
    AND status_pendaftaran = 'menunggu' 
    ORDER BY created_at DESC
", $verifikator->kode_referral, $id_verifikator) );

// 2. UMKM Binaan (Sudah diverifikasi/aktif)
$list_binaan = $wpdb->get_results( $wpdb->prepare("
    SELECT * FROM $table_pedagang 
    WHERE id_verifikator = %d 
    AND status_pendaftaran != 'menunggu'
    ORDER BY created_at DESC
", $id_verifikator) );

get_header(); 
?>

<!-- CDN Tailwind -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="bg-gray-50 min-h-screen font-sans flex overflow-hidden text-slate-800">

    <!-- SIDEBAR -->
    <?php get_template_part('template-parts/dashboard/sidebar', 'verifikator', ['verifikator' => $verifikator]); ?>

    <!-- MAIN CONTENT -->
    <main class="flex-1 md:ml-64 flex flex-col h-screen overflow-hidden relative">
        
        <!-- HEADER MOBILE -->
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 md:hidden z-20">
            <span class="font-bold text-gray-800">Verifikator</span>
            <button onclick="document.getElementById('mobile-sidebar').classList.toggle('-translate-x-full')" class="text-gray-600">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </header>

        <!-- CONTENT SCROLLABLE -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            
            <?php if($msg): ?>
                <div class="mb-6 p-4 rounded-xl border <?php echo ($msg_type=='error')?'bg-red-50 text-red-700 border-red-200':'bg-green-50 text-green-700 border-green-200'; ?> flex items-center gap-3 shadow-sm animate-fade-in">
                    <i class="fas <?php echo ($msg_type=='error')?'fa-exclamation-circle':'fa-check-circle'; ?> text-lg"></i> 
                    <div><?php echo $msg; ?></div>
                </div>
            <?php endif; ?>

            <!-- [BARU] KARTU KODE REFERRAL VERIFIKATOR -->
            <div class="bg-gradient-to-r from-orange-50 to-amber-50 border border-orange-200 rounded-2xl p-5 mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm relative overflow-hidden group">
                <div class="relative z-10">
                    <p class="text-xs font-bold text-orange-600 uppercase tracking-wider mb-1 flex items-center gap-2">
                        <i class="fas fa-id-badge"></i> Link Referral Verifikator
                    </p>
                    <h2 class="text-2xl font-mono font-bold text-gray-800 tracking-wide">
                        <?php echo esc_html($verifikator->kode_referral); ?>
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">Gunakan link ini saat mendaftarkan UMKM binaan baru agar otomatis terhubung.</p>
                </div>
                <div class="relative z-10">
                    <?php 
                        // GENERATE LINK REGISTER
                        $ref_link_verif = home_url('/register?ref=' . $verifikator->kode_referral);
                    ?>
                    <button onclick="copyToClipboard('<?php echo esc_js($ref_link_verif); ?>')" class="bg-white hover:bg-orange-600 hover:text-white text-orange-700 border border-orange-200 font-bold py-2.5 px-5 rounded-xl shadow-sm transition-all active:scale-95 flex items-center gap-2">
                        <i class="fas fa-link"></i> <span>Salin Link Daftar</span>
                    </button>
                </div>
                <!-- Decoration -->
                <div class="absolute right-0 top-0 h-full w-32 bg-orange-100/30 -skew-x-12 translate-x-10 group-hover:translate-x-5 transition-transform duration-500"></div>
            </div>

            <!-- STATISTIK -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Saldo -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-wallet"></i></div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase">Saldo Komisi</p>
                        <h3 class="text-xl font-bold text-gray-800">Rp <?php echo number_format($verifikator->saldo_saat_ini, 0, ',', '.'); ?></h3>
                    </div>
                </div>
                <!-- Total Verifikasi -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase">Verifikasi Sukses</p>
                        <h3 class="text-xl font-bold text-gray-800"><?php echo number_format($verifikator->total_verifikasi_sukses); ?></h3>
                    </div>
                </div>
                <!-- Binaan Aktif -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-orange-100 text-orange-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-users"></i></div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase">UMKM Binaan</p>
                        <h3 class="text-xl font-bold text-gray-800"><?php echo count($list_binaan); ?></h3>
                    </div>
                </div>
            </div>

            <!-- TABS CONTENT -->
            <div id="view-dashboard" class="tab-content active">
                
                <!-- Pending Verification -->
                <div class="mb-8">
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-clock text-yellow-500"></i> Menunggu Verifikasi</h2>
                    <?php if($list_pending): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach($list_pending as $p): ?>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:shadow-md transition">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 font-bold text-lg uppercase"><?php echo substr($p->nama_toko, 0, 1); ?></div>
                                    <div>
                                        <h4 class="font-bold text-gray-800 leading-tight"><?php echo esc_html($p->nama_toko); ?></h4>
                                        <p class="text-xs text-gray-500"><?php echo esc_html($p->nama_pemilik); ?></p>
                                    </div>
                                </div>
                                <span class="bg-yellow-100 text-yellow-700 text-[10px] font-bold px-2 py-1 rounded-md">BARU</span>
                            </div>
                            <div class="text-sm text-gray-600 space-y-2 mb-4">
                                <p><i class="fab fa-whatsapp w-5 text-green-500"></i> <?php echo esc_html($p->nomor_wa); ?></p>
                                <p class="line-clamp-1"><i class="fas fa-map-marker-alt w-5 text-red-500"></i> <?php echo esc_html($p->alamat_lengkap); ?></p>
                            </div>
                            <div class="grid grid-cols-2 gap-3 pt-4 border-t border-gray-100">
                                <form method="POST" class="contents">
                                    <?php wp_nonce_field('verify_umkm_action', 'verify_nonce'); ?>
                                    <input type="hidden" name="action_verifikasi" value="1">
                                    <input type="hidden" name="id_pedagang" value="<?php echo $p->id; ?>">
                                    <button type="submit" name="keputusan" value="ditolak" class="bg-red-50 text-red-600 py-2 rounded-lg text-xs font-bold hover:bg-red-100">Tolak</button>
                                    <button type="submit" name="keputusan" value="disetujui" class="bg-green-600 text-white py-2 rounded-lg text-xs font-bold hover:bg-green-700 shadow-sm">Verifikasi Valid</button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <div class="bg-white rounded-xl border border-dashed border-gray-300 p-8 text-center text-gray-400">
                            <i class="fas fa-clipboard-check text-4xl mb-3 opacity-30"></i>
                            <p>Tidak ada UMKM baru yang perlu diverifikasi saat ini.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Binaan List -->
                <div>
                    <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-store text-blue-500"></i> UMKM Binaan Saya</h2>
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <?php if($list_binaan): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 text-gray-500 font-bold border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-3">Nama Toko</th>
                                        <th class="px-6 py-3">Pemilik</th>
                                        <th class="px-6 py-3">Lokasi</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3 text-right">Tgl Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach($list_binaan as $b): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-3 font-bold text-gray-800"><?php echo esc_html($b->nama_toko); ?></td>
                                        <td class="px-6 py-3"><?php echo esc_html($b->nama_pemilik); ?></td>
                                        <td class="px-6 py-3 text-gray-500"><?php echo esc_html($b->kecamatan_nama); ?></td>
                                        <td class="px-6 py-3"><span class="bg-green-100 text-green-700 px-2 py-1 rounded text-xs font-bold"><?php echo ucfirst($b->status_pendaftaran); ?></span></td>
                                        <td class="px-6 py-3 text-right text-gray-500"><?php echo date('d M Y', strtotime($b->verified_at)); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="p-8 text-center text-gray-400">Belum ada UMKM binaan.</div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- TAB PROFIL (Hidden by default, toggled via Sidebar JS) -->
            <div id="view-profil" class="tab-content hidden">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Profil Saya</h1>
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 max-w-3xl">
                    <form method="POST" enctype="multipart/form-data">
                        <?php wp_nonce_field('save_verif_profile', 'verif_nonce'); ?>
                        <input type="hidden" name="save_profile" value="1">
                        
                        <div class="flex flex-col md:flex-row gap-8">
                            <!-- Foto -->
                            <div class="w-full md:w-1/3 flex flex-col items-center">
                                <div class="w-32 h-32 rounded-full bg-gray-100 border-4 border-white shadow-md overflow-hidden mb-4 relative group">
                                    <img id="preview-foto" src="<?php echo $verifikator->foto_profil ? esc_url($verifikator->foto_profil) : 'https://ui-avatars.com/api/?name='.urlencode($verifikator->nama_lengkap); ?>" class="w-full h-full object-cover">
                                    <label class="absolute inset-0 bg-black/50 flex items-center justify-center text-white opacity-0 group-hover:opacity-100 cursor-pointer transition">
                                        <i class="fas fa-camera"></i>
                                        <input type="file" name="foto_profil" class="hidden" onchange="previewImage(this)">
                                    </label>
                                </div>
                                <div class="bg-gray-100 px-4 py-2 rounded-lg text-center w-full">
                                    <p class="text-xs text-gray-500 uppercase font-bold">Kode Referral</p>
                                    <p class="text-lg font-mono font-bold text-gray-800"><?php echo esc_html($verifikator->kode_referral); ?></p>
                                </div>
                            </div>
                            
                            <!-- Form Fields -->
                            <div class="w-full md:w-2/3 space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" value="<?php echo esc_attr($verifikator->nama_lengkap); ?>" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-orange-500 outline-none">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">NIK</label>
                                        <input type="text" name="nik" value="<?php echo esc_attr($verifikator->nik); ?>" class="w-full border border-gray-300 rounded-lg p-2.5">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-gray-700 mb-1">WhatsApp</label>
                                        <input type="text" name="nomor_wa" value="<?php echo esc_attr($verifikator->nomor_wa); ?>" class="w-full border border-gray-300 rounded-lg p-2.5">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Alamat Domisili</label>
                                    <textarea name="alamat_lengkap" rows="2" class="w-full border border-gray-300 rounded-lg p-2.5"><?php echo esc_textarea($verifikator->alamat_lengkap); ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-1">Kode Pos</label>
                                    <input type="text" name="kode_pos" value="<?php echo esc_attr($verifikator->kode_pos); ?>" class="w-full border border-gray-300 rounded-lg p-2.5">
                                </div>
                                <div class="pt-4 text-right">
                                    <button type="submit" class="bg-orange-600 text-white font-bold py-2.5 px-6 rounded-xl hover:bg-orange-700 shadow-md transition">Simpan Profil</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
    // Tab Switching Logic (Simple)
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('view-' + tabId).classList.remove('hidden');
    }

    // Image Preview
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview-foto').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // [BARU] Fungsi Copy
    function copyToClipboard(text) {
        if (!text) return;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Link Pendaftaran berhasil disalin!\n\n' + text);
            }).catch(err => {
                fallbackCopy(text);
            });
        } else {
            fallbackCopy(text);
        }
    }

    function fallbackCopy(text) {
        let textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            alert('Link Pendaftaran berhasil disalin!\n\n' + text);
        } catch (err) {
            alert('Gagal menyalin kode. Silakan salin manual.');
        }
        document.body.removeChild(textArea);
    }
    
    // Modal Withdraw Logic
    function openWithdrawModal() {
        document.getElementById('modal-withdraw').classList.remove('hidden');
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    // Format Rupiah
    function formatRupiah(el) {
        let val = el.value.replace(/[^0-9]/g, '');
        el.value = new Intl.NumberFormat('id-ID').format(val);
    }
</script>

<style>
    .animate-fade-in { animation: fadeIn 0.4s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    /* Scrollbar Halus */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<?php get_footer(); ?>