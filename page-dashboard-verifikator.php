<?php
/**
 * Template Name: Dashboard Verifikator UMKM
 * Description: Panel khusus untuk memverifikasi pendaftaran pedagang baru.
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
$table_pedagang = $wpdb->prefix . 'dw_pedagang';
$msg = '';
$msg_type = '';

// --- HANDLER: PROSES VERIFIKASI ---
if ( isset($_POST['action_verify']) && check_admin_referer('verify_umkm_action', 'verify_nonce') ) {
    $id_pedagang = intval($_POST['id_pedagang']);
    $keputusan   = sanitize_text_field($_POST['keputusan']); // 'disetujui' atau 'ditolak'
    $alasan      = sanitize_textarea_field($_POST['alasan_penolakan']);
    
    // Data Update
    $update_data = [
        'status_pendaftaran' => $keputusan,
        'approved_by'        => $current_user->ID, // ID Verifikator
        'verified_at'        => current_time('mysql'),
        'updated_at'         => current_time('mysql')
    ];

    if ( $keputusan == 'disetujui' ) {
        $update_data['status_akun'] = 'aktif';
        $update_data['is_verified'] = 1;
        // Opsional: Beri kuota awal gratis jika ada di settings
        $settings = get_option('dw_settings', []);
        $update_data['sisa_transaksi'] = isset($settings['kuota_gratis_default']) ? intval($settings['kuota_gratis_default']) : 0;
    } else {
        $update_data['status_akun'] = 'nonaktif';
        // Simpan alasan penolakan (bisa dikirim via email nanti atau disimpan di meta, disini kita skip meta dulu untuk simplifikasi)
    }

    $wpdb->update($table_pedagang, $update_data, ['id' => $id_pedagang]);
    
    $msg = ($keputusan == 'disetujui') ? "UMKM berhasil disetujui dan diaktifkan." : "UMKM telah ditolak.";
    $msg_type = ($keputusan == 'disetujui') ? "success" : "warning";
}

// --- DATA QUERY ---
// 1. Antrean (Menunggu)
$pending_list = $wpdb->get_results("SELECT * FROM $table_pedagang WHERE status_pendaftaran = 'menunggu' ORDER BY created_at ASC");

// 2. Riwayat (Diproses oleh saya)
$history_list = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_pedagang WHERE approved_by = %d ORDER BY verified_at DESC LIMIT 20", $current_user->ID));

// 3. Statistik
$stats_pending  = count($pending_list);
$stats_approved = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_pedagang WHERE approved_by = %d AND status_pendaftaran = 'disetujui'", $current_user->ID));
$stats_rejected = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table_pedagang WHERE approved_by = %d AND status_pendaftaran = 'ditolak'", $current_user->ID));

get_header();
?>

<!-- CDN Assets -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script>tailwind.config = { theme: { extend: { colors: { primary: '#4f46e5', secondary: '#1e293b' } } } }</script>

<div class="bg-gray-50 min-h-screen font-sans flex flex-col md:flex-row pt-16 md:pt-0 relative">

    <!-- SIDEBAR -->
 <!-- SIDEBAR -->
<?php get_template_part('template-parts/dashboard/sidebar', 'verifikator'); ?>

    <!-- MOBILE HEADER -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-30 flex items-center justify-between px-4 shadow-sm">
        <div class="flex items-center gap-3">
            <button onclick="document.getElementById('sidebar').classList.toggle('-translate-x-full')" class="text-gray-600 p-2 rounded hover:bg-gray-100"><i class="fas fa-bars text-xl"></i></button>
            <span class="font-bold text-gray-800">Verifikator</span>
        </div>
        <div class="w-8 h-8 bg-gray-200 rounded-full overflow-hidden">
            <img src="<?php echo get_avatar_url($current_user->ID); ?>" class="w-full h-full object-cover">
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-6 md:p-8 overflow-y-auto h-screen bg-gray-50">
        
        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl border <?php echo ($msg_type=='warning')?'bg-yellow-50 text-yellow-800 border-yellow-200':'bg-green-50 text-green-800 border-green-200'; ?> flex items-center gap-3 shadow-sm animate-fade-in">
                <i class="fas <?php echo ($msg_type=='warning')?'fa-exclamation-triangle':'fa-check-circle'; ?>"></i> 
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- TAB: DASHBOARD -->
        <div id="view-dashboard" class="tab-content block">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Ringkasan Kinerja</h1>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Card Pending -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Perlu Tindakan</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats_pending); ?></h3>
                    </div>
                </div>
                <!-- Card Approved -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Disetujui</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats_approved); ?></h3>
                    </div>
                </div>
                <!-- Card Rejected -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-xl"><i class="fas fa-times-circle"></i></div>
                    <div>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ditolak</p>
                        <h3 class="text-2xl font-bold text-gray-800"><?php echo number_format($stats_rejected); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: ANTREAN -->
        <div id="view-antrean" class="tab-content hidden">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Antrean Verifikasi</h1>
            
            <?php if($pending_list): ?>
                <div class="grid grid-cols-1 gap-4">
                    <?php foreach($pending_list as $p): ?>
                        <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div class="flex items-center gap-4 w-full md:w-auto">
                                <div class="w-14 h-14 bg-gray-100 rounded-xl overflow-hidden shrink-0 border border-gray-200">
                                    <img src="<?php echo $p->foto_profil ? esc_url($p->foto_profil) : 'https://ui-avatars.com/api/?name='.urlencode($p->nama_toko); ?>" class="w-full h-full object-cover">
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800 text-lg"><?php echo esc_html($p->nama_toko); ?></h3>
                                    <div class="flex items-center gap-3 text-sm text-gray-500 mt-1">
                                        <span><i class="fas fa-user text-gray-400"></i> <?php echo esc_html($p->nama_pemilik); ?></span>
                                        <span class="hidden md:inline text-gray-300">|</span>
                                        <span><i class="fas fa-phone text-gray-400"></i> <?php echo esc_html($p->nomor_wa); ?></span>
                                    </div>
                                    <div class="text-xs text-gray-400 mt-1"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html(substr($p->alamat_lengkap, 0, 50)) . '...'; ?></div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-3 w-full md:w-auto mt-2 md:mt-0">
                                <button onclick='openDetailModal(<?php echo json_encode($p); ?>)' class="flex-1 md:flex-none px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-bold hover:bg-gray-50 transition"><i class="fas fa-eye"></i> Detail</button>
                                <button onclick="processItem(<?php echo $p->id; ?>, '<?php echo esc_js($p->nama_toko); ?>')" class="flex-1 md:flex-none px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-bold hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition"><i class="fas fa-gavel"></i> Proses</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-3xl border-2 border-dashed border-gray-200">
                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300 text-3xl"><i class="fas fa-clipboard-check"></i></div>
                    <h3 class="text-gray-800 font-bold text-lg">Kerja Bagus!</h3>
                    <p class="text-gray-500">Tidak ada antrean pedagang saat ini.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: RIWAYAT -->
        <div id="view-riwayat" class="tab-content hidden">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Riwayat Verifikasi Anda</h1>
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                        <tr>
                            <th class="p-4">Tanggal</th>
                            <th class="p-4">Nama Toko</th>
                            <th class="p-4">Pemilik</th>
                            <th class="p-4 text-center">Keputusan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php if($history_list): foreach($history_list as $h): 
                            $status_badge = ($h->status_pendaftaran == 'disetujui') 
                                ? 'bg-green-100 text-green-700' 
                                : 'bg-red-100 text-red-700';
                        ?>
                        <tr class="hover:bg-gray-50 transition">
                            <td class="p-4 text-gray-500"><?php echo date('d M Y, H:i', strtotime($h->verified_at)); ?></td>
                            <td class="p-4 font-bold text-gray-800"><?php echo esc_html($h->nama_toko); ?></td>
                            <td class="p-4 text-gray-600"><?php echo esc_html($h->nama_pemilik); ?></td>
                            <td class="p-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase <?php echo $status_badge; ?>">
                                    <?php echo ucfirst($h->status_pendaftaran); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; else: ?>
                        <tr><td colspan="4" class="p-8 text-center text-gray-400">Belum ada riwayat.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<!-- MODAL DETAIL -->
<div id="modal-detail" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal('modal-detail')"></div>
    <div class="absolute inset-y-0 right-0 w-full max-w-xl bg-white shadow-2xl overflow-y-auto transform transition-transform translate-x-full duration-300 flex flex-col" id="modal-detail-panel">
        
        <!-- Header -->
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10">
            <h2 class="text-xl font-bold text-gray-800">Detail Pedagang</h2>
            <button onclick="closeModal('modal-detail')" class="text-gray-400 hover:text-gray-600 transition"><i class="fas fa-times text-xl"></i></button>
        </div>
        
        <!-- Content -->
        <div class="p-6 flex-1 space-y-6">
            <!-- Profil Header -->
            <div class="flex items-center gap-4">
                <img id="d-foto" src="" class="w-20 h-20 rounded-2xl object-cover border border-gray-200 shadow-sm">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900" id="d-toko">-</h3>
                    <p class="text-gray-500" id="d-pemilik">-</p>
                    <a id="d-wa-link" href="#" target="_blank" class="inline-flex items-center gap-1 text-green-600 text-sm font-bold hover:underline mt-1"><i class="fab fa-whatsapp"></i> Hubungi via WA</a>
                </div>
            </div>

            <!-- Informasi Dasar -->
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-500">NIK</span>
                    <span class="font-mono font-bold text-gray-800" id="d-nik">-</span>
                </div>
                <div class="flex justify-between border-b border-gray-200 pb-2">
                    <span class="text-gray-500">Tanggal Daftar</span>
                    <span class="font-bold text-gray-800" id="d-date">-</span>
                </div>
                <div>
                    <span class="text-gray-500 block mb-1">Alamat Lengkap</span>
                    <p class="text-gray-800 leading-relaxed" id="d-alamat">-</p>
                </div>
                <div id="d-gmaps-container" class="hidden pt-2">
                    <a id="d-gmaps" href="#" target="_blank" class="text-blue-600 hover:underline flex items-center gap-1"><i class="fas fa-map-marker-alt"></i> Lihat di Google Maps</a>
                </div>
            </div>

            <!-- Dokumen -->
            <div>
                <h4 class="font-bold text-gray-800 mb-3 flex items-center gap-2"><i class="fas fa-id-card"></i> Dokumen Pendukung</h4>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Foto KTP</p>
                        <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-pointer group relative" onclick="viewImage('d-ktp-img')">
                            <img id="d-ktp-img" src="" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition"><i class="fas fa-search-plus text-white"></i></div>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">Foto Sampul Toko</p>
                        <div class="aspect-video bg-gray-100 rounded-lg overflow-hidden border border-gray-200 cursor-pointer group relative" onclick="viewImage('d-sampul-img')">
                            <img id="d-sampul-img" src="" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition"><i class="fas fa-search-plus text-white"></i></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Action -->
        <div class="p-6 border-t border-gray-100 bg-gray-50 sticky bottom-0 z-10">
            <button id="btn-process-modal" class="w-full bg-indigo-600 text-white font-bold py-3.5 rounded-xl hover:bg-indigo-700 shadow-lg transition flex items-center justify-center gap-2">
                <i class="fas fa-gavel"></i> Proses Verifikasi
            </button>
        </div>
    </div>
</div>

<!-- MODAL PROSES (TERIMA/TOLAK) -->
<div id="modal-process" class="fixed inset-0 z-[60] hidden">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="closeModal('modal-process')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-2xl transform scale-100 transition-all">
            <h3 class="text-xl font-bold text-gray-800 mb-2">Keputusan Verifikasi</h3>
            <p class="text-gray-500 text-sm mb-6">Tentukan status untuk toko <strong id="proc-name" class="text-gray-800"></strong>.</p>
            
            <form method="POST">
                <?php wp_nonce_field('verify_umkm_action', 'verify_nonce'); ?>
                <input type="hidden" name="action_verify" value="1">
                <input type="hidden" name="id_pedagang" id="proc-id">
                
                <div class="flex gap-3 mb-4">
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="keputusan" value="disetujui" class="peer hidden" checked onchange="toggleReason(false)">
                        <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-green-500 peer-checked:bg-green-50 text-center transition">
                            <div class="text-2xl mb-1 text-gray-400 peer-checked:text-green-600"><i class="fas fa-check-circle"></i></div>
                            <span class="font-bold text-gray-600 peer-checked:text-green-800 text-sm">Setujui</span>
                        </div>
                    </label>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="keputusan" value="ditolak" class="peer hidden" onchange="toggleReason(true)">
                        <div class="p-4 rounded-xl border-2 border-gray-200 peer-checked:border-red-500 peer-checked:bg-red-50 text-center transition">
                            <div class="text-2xl mb-1 text-gray-400 peer-checked:text-red-600"><i class="fas fa-times-circle"></i></div>
                            <span class="font-bold text-gray-600 peer-checked:text-red-800 text-sm">Tolak</span>
                        </div>
                    </label>
                </div>

                <div id="reason-box" class="hidden mb-6 animate-fade-in">
                    <label class="block text-xs font-bold text-gray-600 mb-1">Alasan Penolakan <span class="text-red-500">*</span></label>
                    <textarea name="alasan_penolakan" rows="3" class="w-full border border-gray-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-red-500 outline-none" placeholder="Jelaskan alasan penolakan..."></textarea>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeModal('modal-process')" class="flex-1 py-3 text-gray-600 font-bold hover:bg-gray-100 rounded-xl transition">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-gray-900 text-white font-bold rounded-xl hover:bg-black transition shadow-lg">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .tab-content { display: none; }
    .tab-content.block { display: block; animation: fadeIn 0.3s ease-out; }
    .nav-item.active { background-color: #eef2ff; color: #4f46e5; border-right: 4px solid #4f46e5; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in { animation: fadeIn 0.3s ease-out; }
</style>

<script>
    // Tab Switching
    function switchTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('block', 'hidden'));
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('view-' + tabName).classList.remove('hidden');
        document.getElementById('view-' + tabName).classList.add('block');
        
        document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
        document.getElementById('nav-' + tabName).classList.add('active');
    }

    // Init Default Tab
    switchTab('dashboard');

    // Modals
    function openDetailModal(data) {
        // Populate Data
        document.getElementById('d-toko').textContent = data.nama_toko;
        document.getElementById('d-pemilik').textContent = data.nama_pemilik;
        document.getElementById('d-nik').textContent = data.nik || '-';
        document.getElementById('d-date').textContent = new Date(data.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
        document.getElementById('d-alamat').textContent = data.alamat_lengkap;
        
        // Images
        document.getElementById('d-foto').src = data.foto_profil || 'https://ui-avatars.com/api/?name='+encodeURIComponent(data.nama_toko);
        
        const ktp = document.getElementById('d-ktp-img');
        if (data.url_ktp) { ktp.src = data.url_ktp; ktp.parentElement.classList.remove('hidden'); } else { ktp.parentElement.classList.add('hidden'); }
        
        const sampul = document.getElementById('d-sampul-img');
        if (data.foto_sampul) { sampul.src = data.foto_sampul; sampul.parentElement.classList.remove('hidden'); } else { sampul.parentElement.classList.add('hidden'); }

        // Links
        const waLink = 'https://wa.me/' + (data.nomor_wa ? data.nomor_wa.replace(/^0/, '62').replace(/\D/g,'') : '');
        document.getElementById('d-wa-link').href = waLink;

        if (data.url_gmaps) {
            document.getElementById('d-gmaps').href = data.url_gmaps;
            document.getElementById('d-gmaps-container').classList.remove('hidden');
        } else {
            document.getElementById('d-gmaps-container').classList.add('hidden');
        }

        // Setup Process Button in Detail Modal
        const btnProc = document.getElementById('btn-process-modal');
        btnProc.onclick = function() { closeModal('modal-detail'); processItem(data.id, data.nama_toko); };

        // Show Modal
        document.getElementById('modal-detail').classList.remove('hidden');
        setTimeout(() => document.getElementById('modal-detail-panel').classList.remove('translate-x-full'), 10);
    }

    function processItem(id, name) {
        document.getElementById('proc-id').value = id;
        document.getElementById('proc-name').textContent = name;
        toggleReason(false); // Reset to approve default
        document.querySelector('input[name="keputusan"][value="disetujui"]').checked = true;
        
        document.getElementById('modal-process').classList.remove('hidden');
    }

    function closeModal(id) {
        if(id === 'modal-detail') {
            document.getElementById('modal-detail-panel').classList.add('translate-x-full');
            setTimeout(() => document.getElementById(id).classList.add('hidden'), 300);
        } else {
            document.getElementById(id).classList.add('hidden');
        }
    }

    function toggleReason(show) {
        const box = document.getElementById('reason-box');
        const area = box.querySelector('textarea');
        if (show) {
            box.classList.remove('hidden');
            area.required = true;
        } else {
            box.classList.add('hidden');
            area.required = false;
        }
    }

    function viewImage(imgId) {
        const src = document.getElementById(imgId).src;
        window.open(src, '_blank');
    }
</script>

<?php get_footer(); ?>