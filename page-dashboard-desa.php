<?php
/**
 * Template Name: Dashboard Desa
 */

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url('/login') );
    exit;
}

$user = wp_get_current_user();
if ( ! in_array( 'admin_desa', (array) $user->roles ) && ! in_array( 'administrator', (array) $user->roles ) ) {
    wp_redirect( home_url() );
    exit;
}

get_header(); 
?>

<div class="bg-gray-100 min-h-screen">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="container mx-auto px-4 h-16 flex items-center justify-between">
            <div class="font-bold text-lg text-gray-800 flex items-center gap-2">
                <i class="ph-fill ph-house-line text-primary text-xl"></i> 
                <span>Portal Desa</span>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm font-bold hidden sm:block"><?php echo esc_html($user->display_name); ?></span>
                <img src="<?php echo get_avatar_url($user->ID); ?>" class="w-9 h-9 rounded-full border border-gray-200">
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm overflow-hidden p-2 space-y-1">
                    <a href="#ringkasan" class="dash-link active flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50 hover:text-primary transition bg-green-50 text-primary">
                        <i class="ph-bold ph-squares-four text-lg"></i> Ringkasan
                    </a>
                    <a href="#pedagang" class="dash-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-50 hover:text-primary transition">
                        <i class="ph-bold ph-storefront text-lg"></i> Validasi Pedagang
                        <span class="bg-red-500 text-white text-[10px] px-2 rounded-full ml-auto hidden" id="badge-pedagang">0</span>
                    </a>
                    <a href="#wisata" class="dash-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-50 hover:text-primary transition">
                        <i class="ph-bold ph-map-trifold text-lg"></i> Kelola Wisata
                    </a>
                    <a href="#profil" class="dash-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold text-gray-600 hover:bg-gray-50 hover:text-primary transition">
                        <i class="ph-bold ph-user-circle text-lg"></i> Profil Desa
                    </a>
                    <div class="border-t border-gray-100 my-2"></div>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold text-red-600 hover:bg-red-50 transition">
                        <i class="ph-bold ph-sign-out text-lg"></i> Keluar
                    </a>
                </div>
            </div>

            <!-- Content -->
            <div class="lg:col-span-3">
                
                <!-- VIEW: RINGKASAN -->
                <div id="view-ringkasan" class="dash-view space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                            <p class="text-xs font-bold text-gray-400 uppercase">Total Wisata</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1">12</h3>
                        </div>
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                            <p class="text-xs font-bold text-gray-400 uppercase">Pedagang Aktif</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1">24</h3>
                        </div>
                        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                            <p class="text-xs font-bold text-gray-400 uppercase">Menunggu Validasi</p>
                            <h3 class="text-2xl font-bold text-yellow-600 mt-1" id="count-pending">0</h3>
                        </div>
                    </div>
                </div>

                <!-- VIEW: VALIDASI PEDAGANG -->
                <div id="view-pedagang" class="dash-view hidden">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <h2 class="text-lg font-bold text-gray-800 mb-6 pb-4 border-b border-gray-100">Permintaan Pendaftaran</h2>
                        <div id="pending-pedagang-list" class="space-y-4">
                            <div class="text-center text-gray-400 py-8">Memuat data...</div>
                        </div>
                    </div>
                </div>

                <!-- VIEW: KELOLA WISATA -->
                <div id="view-wisata" class="dash-view hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-bold text-gray-800">Daftar Wisata</h2>
                        <button class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold shadow hover:bg-secondary">+ Tambah Wisata</button>
                    </div>
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <!-- List Wisata akan di-load via AJAX -->
                        <div class="p-8 text-center text-gray-400">Belum ada data wisata.</div>
                    </div>
                </div>

                <!-- VIEW: PROFIL DESA -->
                <div id="view-profil" class="dash-view hidden">
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                            <h2 class="text-lg font-bold text-gray-800">Profil Desa</h2>
                            <button onclick="enableEditProfil()" id="btn-edit-profil" class="text-primary text-sm font-bold hover:underline">Edit Profil</button>
                        </div>
                        
                        <form id="form-profil-desa" class="space-y-6">
                            <!-- Foto Profil Desa -->
                            <div class="flex flex-col items-center sm:flex-row gap-6">
                                <div class="relative group">
                                    <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-100 border-2 border-white shadow-md">
                                        <img id="preview-foto-desa" src="https://via.placeholder.com/150" class="w-full h-full object-cover">
                                    </div>
                                    <label for="foto_desa" class="absolute bottom-0 right-0 bg-white p-1.5 rounded-full shadow-sm cursor-pointer border border-gray-200 hover:bg-gray-50 hidden group-hover:block" title="Ganti Foto">
                                        <i class="ph-bold ph-camera text-gray-600"></i>
                                    </label>
                                    <input type="file" id="foto_desa" name="foto" accept="image/*" class="hidden" disabled onchange="previewImage(this, 'preview-foto-desa')">
                                </div>
                                <div class="flex-1 text-center sm:text-left">
                                    <h3 class="font-bold text-lg text-gray-900" id="display-nama-desa">Nama Desa</h3>
                                    <p class="text-sm text-gray-500">Ubah foto dan informasi dasar desa Anda.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi Desa</label>
                                    <textarea name="deskripsi" id="deskripsi_desa" rows="4" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring-primary disabled:bg-gray-50 disabled:text-gray-500" disabled></textarea>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Rekening Desa</label>
                                        <input type="text" name="no_rekening_desa" id="no_rekening_desa" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring-primary disabled:bg-gray-50 disabled:text-gray-500" disabled>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bank</label>
                                        <input type="text" name="nama_bank_desa" id="nama_bank_desa" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring-primary disabled:bg-gray-50 disabled:text-gray-500" disabled>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Atas Nama Rekening</label>
                                        <input type="text" name="atas_nama_rekening_desa" id="atas_nama_rekening_desa" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary focus:ring-primary disabled:bg-gray-50 disabled:text-gray-500" disabled>
                                    </div>
                                </div>
                            </div>

                            <div id="action-buttons" class="hidden justify-end gap-3 pt-4 border-t border-gray-100">
                                <button type="button" onclick="cancelEditProfil()" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-50">Batal</button>
                                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg text-sm font-bold hover:bg-secondary shadow-md flex items-center gap-2">
                                    <span id="btn-save-text">Simpan Perubahan</span>
                                    <i id="btn-save-loader" class="ph-bold ph-spinner animate-spin hidden"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const API_BASE = dwData.api_url;
    const JWT_TOKEN = localStorage.getItem('dw_jwt_token');
    const authHeaders = { 'Authorization': 'Bearer ' + JWT_TOKEN };

    // Tab Switcher
    $('.dash-link').click(function(e) {
        if($(this).attr('href').startsWith('#')) {
            e.preventDefault();
            $('.dash-link').removeClass('active bg-green-50 text-primary').addClass('text-gray-600 hover:bg-gray-50');
            $(this).addClass('active bg-green-50 text-primary').removeClass('text-gray-600 hover:bg-gray-50');
            
            $('.dash-view').addClass('hidden');
            const target = $(this).attr('href').replace('#', 'view-');
            $('#' + target).removeClass('hidden');

            if(target === 'view-pedagang') loadPendingPedagang();
            if(target === 'view-profil') loadProfilDesa();
        }
    });

    // --- PROFIL DESA FUNCTIONS ---

    // Load Profil Desa
    window.loadProfilDesa = function() {
        $.ajax({
            url: API_BASE + 'admin-desa/profile/me',
            type: 'GET',
            headers: authHeaders,
            success: function(res) {
                // Populate Form
                $('#display-nama-desa').text(res.nama_desa || 'Nama Desa');
                $('#deskripsi_desa').val(res.deskripsi || '');
                $('#no_rekening_desa').val(res.no_rekening_desa || '');
                $('#nama_bank_desa').val(res.nama_bank_desa || '');
                $('#atas_nama_rekening_desa').val(res.atas_nama_rekening_desa || '');
                
                if(res.foto) {
                    $('#preview-foto-desa').attr('src', res.foto);
                }
            },
            error: function() {
                alert('Gagal memuat profil desa.');
            }
        });
    }

    // Enable Edit Mode
    window.enableEditProfil = function() {
        $('#form-profil-desa input, #form-profil-desa textarea').prop('disabled', false);
        $('#btn-edit-profil').addClass('hidden');
        $('#action-buttons').removeClass('hidden').addClass('flex');
        $('#foto_desa').prop('disabled', false); // Enable file input
    }

    // Cancel Edit Mode
    window.cancelEditProfil = function() {
        $('#form-profil-desa input, #form-profil-desa textarea').prop('disabled', true);
        $('#btn-edit-profil').removeClass('hidden');
        $('#action-buttons').addClass('hidden').removeClass('flex');
        loadProfilDesa(); // Reset values
    }

    // Preview Image Helper
    window.previewImage = function(input, previewId) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#' + previewId).attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Submit Profil Desa
    $('#form-profil-desa').on('submit', function(e) {
        e.preventDefault();
        
        const $btnText = $('#btn-save-text');
        const $loader = $('#btn-save-loader');
        
        // Prepare FormData (termasuk file upload jika ada)
        // Catatan: Jika API belum support file upload via endpoint profile update, 
        // Anda mungkin perlu upload file terpisah dulu ke endpoint media.
        // Di sini kita asumsikan endpoint support JSON update dulu.
        // Untuk upload gambar, logika idealnya sama seperti produk (upload -> dapat URL -> kirim URL).
        
        // Sederhana: Kirim data teks dulu
        const payload = {
            deskripsi: $('#deskripsi_desa').val(),
            no_rekening_desa: $('#no_rekening_desa').val(),
            nama_bank_desa: $('#nama_bank_desa').val(),
            atas_nama_rekening_desa: $('#atas_nama_rekening_desa').val()
        };
        
        // TODO: Handle Foto Upload Logic Here (jika ada file baru)
        
        $btnText.text('Menyimpan...');
        $loader.removeClass('hidden');

        $.ajax({
            url: API_BASE + 'admin-desa/profile/me',
            type: 'POST',
            headers: authHeaders,
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function() {
                alert('Profil berhasil diperbarui!');
                cancelEditProfil();
            },
            error: function() {
                alert('Gagal menyimpan profil.');
            },
            complete: function() {
                $btnText.text('Simpan Perubahan');
                $loader.addClass('hidden');
            }
        });
    });


    // --- PEDAGANG & LAINNYA ---

    // Load Pending Pedagang
    function loadPendingPedagang() {
        $.ajax({
            url: API_BASE + 'admin-desa/pedagang/pending',
            type: 'GET',
            headers: authHeaders,
            success: function(res) {
                const $list = $('#pending-pedagang-list');
                
                if(res.length === 0) {
                    $list.html('<div class="text-center text-gray-500 py-4">Tidak ada permintaan baru.</div>');
                    $('#count-pending').text(0);
                    $('#badge-pedagang').addClass('hidden');
                    return;
                }

                $('#count-pending').text(res.length);
                $('#badge-pedagang').text(res.length).removeClass('hidden');

                let html = '';
                res.forEach(p => {
                    html += `
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-primary shadow-sm">
                                <i class="ph-bold ph-storefront text-xl"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900">${p.nama_toko}</h4>
                                <p class="text-xs text-gray-500">${p.nama_pemilik} • ${p.nomor_wa}</p>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="rejectPedagang(${p.id})" class="px-3 py-1.5 bg-white border border-gray-300 text-gray-600 text-xs font-bold rounded-lg hover:bg-red-50 hover:text-red-600 hover:border-red-200">Tolak</button>
                            <button onclick="approvePedagang(${p.id})" class="px-3 py-1.5 bg-primary text-white text-xs font-bold rounded-lg hover:bg-secondary shadow-sm">Setujui</button>
                        </div>
                    </div>`;
                });
                $list.html(html);
            }
        });
    }

    // Aksi Approve
    window.approvePedagang = function(id) {
        if(!confirm('Setujui pedagang ini?')) return;
        $.ajax({
            url: API_BASE + 'admin-desa/pedagang/' + id + '/approve',
            type: 'POST',
            headers: authHeaders,
            success: function() {
                alert('Pedagang disetujui!');
                loadPendingPedagang();
            }
        });
    }
    
    // Aksi Reject (Placeholder)
    window.rejectPedagang = function(id) {
        if(!confirm('Tolak pedagang ini?')) return;
         $.ajax({
            url: API_BASE + 'admin-desa/pedagang/' + id + '/reject',
            type: 'POST',
            headers: authHeaders,
             data: { reason: 'Ditolak oleh admin desa' }, // Simplifikasi
            success: function() {
                alert('Pedagang ditolak.');
                loadPendingPedagang();
            }
        });
    }

    // Load awal
    loadPendingPedagang();
});
</script>

<?php get_footer(); ?>