<?php
/**
 * Template Name: Dashboard Desa
 * Description: Panel untuk pengelola desa wisata dengan Integrasi API Wilayah.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Cek Login
if ( ! is_user_logged_in() ) {
    wp_redirect( wp_login_url() );
    exit;
}

$current_user_id = get_current_user_id();
global $wpdb;
$table_desa = $wpdb->prefix . 'dw_desa';

// 2. Handle Form Submit (Simpan Profil Desa)
$msg = '';
$msg_class = '';

if ( isset($_POST['save_profil_desa']) && wp_verify_nonce($_POST['desa_nonce'], 'save_desa_action') ) {
    
    // Siapkan Data
    $data = array(
        'nama_desa'         => sanitize_text_field($_POST['nama_desa']),
        'deskripsi'         => sanitize_textarea_field($_POST['deskripsi']),
        'alamat_lengkap'    => sanitize_textarea_field($_POST['alamat_lengkap']),
        
        // Data Wilayah (ID dari API)
        'api_provinsi_id'   => sanitize_text_field($_POST['api_provinsi_id']),
        'api_kabupaten_id'  => sanitize_text_field($_POST['api_kabupaten_id']),
        'api_kecamatan_id'  => sanitize_text_field($_POST['api_kecamatan_id']),
        'api_kelurahan_id'  => sanitize_text_field($_POST['api_kelurahan_id']),

        // Data Wilayah (Nama Text untuk Display)
        'provinsi'          => sanitize_text_field($_POST['provinsi_nama']),
        'kabupaten'         => sanitize_text_field($_POST['kabupaten_nama']),
        'kecamatan'         => sanitize_text_field($_POST['kecamatan_nama']),
        'kelurahan'         => sanitize_text_field($_POST['kelurahan_nama']),
        
        'updated_at'        => current_time('mysql')
    );

    // Cek Data Lama
    $exist_id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM $table_desa WHERE id_user_desa = %d", $current_user_id) );

    if ($exist_id) {
        // Update
        $wpdb->update($table_desa, $data, ['id' => $exist_id]);
        $msg = 'Profil Desa berhasil diperbarui!';
        $msg_class = 'bg-green-100 text-green-700 border-green-200';
    } else {
        // Insert Baru
        $data['id_user_desa'] = $current_user_id;
        $data['slug_desa']    = sanitize_title($_POST['nama_desa']);
        $data['created_at']   = current_time('mysql');
        
        $wpdb->insert($table_desa, $data);
        $msg = 'Profil Desa berhasil dibuat!';
        $msg_class = 'bg-green-100 text-green-700 border-green-200';
    }
}

// 3. Ambil Data Desa (Untuk Pre-fill Form)
$desa = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $table_desa WHERE id_user_desa = %d", $current_user_id) );

get_header(); 
?>

<div class="bg-gray-50 min-h-screen font-sans flex">
    
    <!-- SIDEBAR -->
    <aside class="w-64 bg-white border-r border-gray-200 hidden md:flex flex-col fixed h-full z-20">
        <div class="p-6 border-b border-gray-100 flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center text-xl shadow-lg shadow-blue-500/30">
                <i class="fas fa-landmark"></i>
            </div>
            <div>
                <h2 class="font-bold text-gray-800 leading-tight">Admin Desa</h2>
                <p class="text-[10px] text-gray-400">Pengelola Wisata</p>
            </div>
        </div>

        <nav class="flex-1 p-4 space-y-1">
            <button onclick="switchTab('ringkasan')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition active-tab" id="nav-ringkasan">
                <i class="fas fa-chart-line w-5 text-center"></i> Ringkasan
            </button>
            <button onclick="switchTab('wisata')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-wisata">
                <i class="fas fa-map-marked-alt w-5 text-center"></i> Kelola Wisata
            </button>
            <button onclick="switchTab('profil')" class="nav-item w-full flex items-center gap-3 px-4 py-3 text-sm font-medium text-gray-600 rounded-xl hover:bg-blue-50 hover:text-blue-600 transition" id="nav-profil">
                <i class="fas fa-user-edit w-5 text-center"></i> Profil Desa
            </button>
        </nav>

        <div class="p-4 border-t border-gray-100">
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-4 py-3 text-sm font-medium text-red-600 rounded-xl hover:bg-red-50 transition w-full">
                <i class="fas fa-sign-out-alt w-5 text-center"></i> Keluar
            </a>
        </div>
    </aside>

    <!-- MOBILE HEADER -->
    <div class="md:hidden fixed top-0 left-0 right-0 h-16 bg-white border-b border-gray-200 z-30 flex items-center justify-between px-4 shadow-sm">
        <span class="font-bold text-gray-800 flex items-center gap-2"><i class="fas fa-landmark text-blue-600"></i> Admin Desa</span>
        <button onclick="document.querySelector('aside').classList.toggle('hidden'); document.querySelector('aside').classList.toggle('flex');" class="text-gray-600 p-2">
            <i class="fas fa-bars text-xl"></i>
        </button>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-1 md:ml-64 p-4 md:p-8 pt-20 md:pt-8 overflow-y-auto h-screen pb-24">
        
        <!-- Notifikasi -->
        <?php if($msg): ?>
            <div class="mb-6 p-4 rounded-xl border <?php echo $msg_class; ?>">
                <i class="fas fa-info-circle mr-2"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <!-- TAB: RINGKASAN -->
        <div id="view-ringkasan" class="tab-content animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Statistik Desa</h1>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-xl"><i class="fas fa-map"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Total Wisata</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="stat-wisata">0</h3>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center text-xl"><i class="fas fa-star"></i></div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Rata-rata Rating</p>
                        <h3 class="text-2xl font-bold text-gray-800" id="stat-rating">0.0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: KELOLA WISATA -->
        <div id="view-wisata" class="tab-content hidden animate-fade-in">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Daftar Objek Wisata</h1>
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 shadow-lg shadow-blue-500/20">
                    <i class="fas fa-plus"></i> Tambah Wisata
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="desa-wisata-list">
                <!-- List Wisata akan diload via JS/AJAX di sini -->
                <div class="col-span-full py-12 text-center text-gray-400 bg-white rounded-xl border border-dashed border-gray-300">
                    <p>Belum ada data wisata.</p>
                </div>
            </div>
        </div>

        <!-- TAB: PROFIL DESA -->
        <div id="view-profil" class="tab-content hidden animate-fade-in">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Profil Desa Wisata</h1>
            
            <div class="max-w-4xl bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
                <form method="POST" action="" enctype="multipart/form-data">
                    <?php wp_nonce_field('save_desa_action', 'desa_nonce'); ?>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        <!-- KOLOM KIRI: INFO UMUM -->
                        <div>
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 border-b pb-2">
                                <i class="fas fa-info-circle text-blue-600"></i> Informasi Umum
                            </h3>
                            <div class="mb-5">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Nama Desa</label>
                                <input type="text" name="nama_desa" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-600 outline-none" 
                                       value="<?php echo isset($desa->nama_desa) ? esc_attr($desa->nama_desa) : ''; ?>" required>
                            </div>
                            <div class="mb-5">
                                <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi Lengkap</label>
                                <textarea name="deskripsi" rows="5" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-600 outline-none"><?php echo isset($desa->deskripsi) ? esc_textarea($desa->deskripsi) : ''; ?></textarea>
                            </div>
                        </div>

                        <!-- KOLOM KANAN: ALAMAT & WILAYAH -->
                        <div>
                            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2 border-b pb-2">
                                <i class="fas fa-map-marker-alt text-blue-600"></i> Lokasi & Alamat
                            </h3>

                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Provinsi</label>
                                <select name="api_provinsi_id" id="desa_provinsi" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-600 outline-none bg-white" required
                                        data-selected="<?php echo isset($desa->api_provinsi_id) ? esc_attr($desa->api_provinsi_id) : ''; ?>">
                                    <option value="">Memuat...</option>
                                </select>
                                <input type="hidden" name="provinsi_nama" id="input_provinsi_nama" value="<?php echo isset($desa->provinsi) ? esc_attr($desa->provinsi) : ''; ?>">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Kabupaten/Kota</label>
                                <select name="api_kabupaten_id" id="desa_kota" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-600 outline-none bg-white" required disabled
                                        data-selected="<?php echo isset($desa->api_kabupaten_id) ? esc_attr($desa->api_kabupaten_id) : ''; ?>">
                                    <option value="">Pilih Provinsi Dulu</option>
                                </select>
                                <input type="hidden" name="kabupaten_nama" id="input_kabupaten_nama" value="<?php echo isset($desa->kabupaten) ? esc_attr($desa->kabupaten) : ''; ?>">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Kecamatan</label>
                                <select name="api_kecamatan_id" id="desa_kecamatan" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-600 outline-none bg-white" required disabled
                                        data-selected="<?php echo isset($desa->api_kecamatan_id) ? esc_attr($desa->api_kecamatan_id) : ''; ?>">
                                    <option value="">Pilih Kota Dulu</option>
                                </select>
                                <input type="hidden" name="kecamatan_nama" id="input_kecamatan_nama" value="<?php echo isset($desa->kecamatan) ? esc_attr($desa->kecamatan) : ''; ?>">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Kelurahan/Desa</label>
                                <select name="api_kelurahan_id" id="desa_kelurahan" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-600 outline-none bg-white" required disabled
                                        data-selected="<?php echo isset($desa->api_kelurahan_id) ? esc_attr($desa->api_kelurahan_id) : ''; ?>">
                                    <option value="">Pilih Kecamatan Dulu</option>
                                </select>
                                <input type="hidden" name="kelurahan_nama" id="input_kelurahan_nama" value="<?php echo isset($desa->kelurahan) ? esc_attr($desa->kelurahan) : ''; ?>">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-bold text-gray-700 mb-1">Alamat Lengkap</label>
                                <textarea name="alamat_lengkap" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-600 outline-none" placeholder="Jalan, RT/RW, Patokan"><?php echo isset($desa->alamat_lengkap) ? esc_textarea($desa->alamat_lengkap) : ''; ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-100 text-right">
                        <button type="submit" name="save_profil_desa" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg flex items-center gap-2 ml-auto">
                            <i class="fas fa-save"></i> Simpan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </main>
</div>

<style>
.active-tab { background-color: #eff6ff; color: #2563eb; border-right: 3px solid #2563eb; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>

<script>
// Define Ajax URL for Frontend
var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active-tab'));
    
    document.getElementById('view-' + tabName).classList.remove('hidden');
    document.getElementById('nav-' + tabName).classList.add('active-tab');
}

// ------------------------------------------
// REGION DROPDOWN LOGIC (Frontend Version)
// ------------------------------------------
jQuery(document).ready(function($) {
    
    // Konfigurasi Selector ID untuk Dashboard Desa
    var els = {
        prov: $('#desa_provinsi'),
        kota: $('#desa_kota'),
        kec: $('#desa_kecamatan'),
        desa: $('#desa_kelurahan')
    };

    // Helper: Load Options via AJAX
    function loadRegionOptions(action, parentId, $target, selectedId) {
        $target.html('<option value="">Memuat...</option>').prop('disabled', true);
        
        var ajaxAction = '';
        var data = {};

        // Mapping Action ke AJAX Action Plugin
        if(action === 'get_provinces') ajaxAction = 'dw_fetch_provinces';
        if(action === 'get_regencies') { ajaxAction = 'dw_fetch_regencies'; data.province_id = parentId; }
        if(action === 'get_districts') { ajaxAction = 'dw_fetch_districts'; data.regency_id = parentId; }
        if(action === 'get_villages')  { ajaxAction = 'dw_fetch_villages'; data.district_id = parentId; }

        data.action = ajaxAction;

        $.ajax({
            url: ajaxurl,
            type: 'GET',
            dataType: 'json',
            data: data,
            success: function(res) {
                $target.empty();
                $target.append('<option value="">Pilih...</option>');
                $target.prop('disabled', false);

                if(res.success) {
                    var items = res.data;
                    if (items && items.data) items = items.data; 
                    
                    if (items && Array.isArray(items)) {
                        $.each(items, function(i, item) {
                            var val = item.id || item.code;
                            var txt = item.name || item.nama;
                            var isSelected = (selectedId && String(val) === String(selectedId)) ? 'selected' : '';
                            $target.append('<option value="' + val + '" ' + isSelected + '>' + txt + '</option>');
                        });
                        
                        // Jika ada data terpilih, trigger change untuk load level selanjutnya
                        if(selectedId && $target.val() == selectedId) {
                            $target.trigger('change'); 
                        }
                    } else {
                        $target.append('<option value="">Data kosong</option>');
                    }
                } else {
                    $target.html('<option value="">Gagal memuat</option>');
                }
            },
            error: function() {
                $target.html('<option value="">Error Jaringan</option>');
            }
        });
    }

    // A. Init Provinsi
    var curProv = els.prov.data('selected');
    if(els.prov.length > 0) {
        loadRegionOptions('get_provinces', null, els.prov, curProv);
    }

    // B. Change Provinsi -> Load Kota
    els.prov.on('change', function() {
        var id = $(this).val();
        var txt = $(this).find('option:selected').text();
        $('#input_provinsi_nama').val(txt);

        els.kota.empty().prop('disabled', true);
        els.kec.empty().prop('disabled', true);
        els.desa.empty().prop('disabled', true);

        if(id) {
            var curKota = els.kota.data('selected');
            // Reset jika user ubah manual
            if (String(id) !== String(curProv)) curKota = null; 
            
            loadRegionOptions('get_regencies', id, els.kota, curKota);
        }
    });

    // C. Change Kota -> Load Kecamatan
    els.kota.on('change', function() {
        var id = $(this).val();
        var txt = $(this).find('option:selected').text();
        $('#input_kabupaten_nama').val(txt);

        els.kec.empty().prop('disabled', true);
        els.desa.empty().prop('disabled', true);

        if(id) {
            var curKec = els.kec.data('selected');
            loadRegionOptions('get_districts', id, els.kec, curKec);
        }
    });

    // D. Change Kecamatan -> Load Desa
    els.kec.on('change', function() {
        var id = $(this).val();
        var txt = $(this).find('option:selected').text();
        $('#input_kecamatan_nama').val(txt);

        els.desa.empty().prop('disabled', true);

        if(id) {
            var curDesa = els.desa.data('selected');
            loadRegionOptions('get_villages', id, els.desa, curDesa);
        }
    });

    // E. Change Desa -> Set Text
    els.desa.on('change', function() {
        var txt = $(this).find('option:selected').text();
        $('#input_kelurahan_nama').val(txt);
    });

});
</script>

<?php wp_footer(); ?>