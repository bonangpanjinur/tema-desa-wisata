<?php
/**
 * Template Name: Halaman Ojek Desa
 * * Halaman khusus untuk listing dan pendaftaran Ojek.
 */

get_header();

global $wpdb;

// 1. Ambil Data Ojek Aktif
$table_ojek = $wpdb->prefix . 'dw_ojek';
$table_desa = $wpdb->prefix . 'dw_desa';

// Default kosong jika tabel belum ada
$list_ojek = [];

// Cek keberadaan tabel untuk menghindari error
if ($wpdb->get_var("SHOW TABLES LIKE '$table_ojek'") == $table_ojek) {
    $query = "
        SELECT o.*, d.nama_desa 
        FROM $table_ojek o
        LEFT JOIN $table_desa d ON o.id_desa = d.id
        WHERE o.status = 'aktif'
        ORDER BY o.is_online DESC, o.created_at DESC
    ";
    $list_ojek = $wpdb->get_results($query);
}
?>

<style>
    /* Gunakan variabel yang sama dengan Front Page agar konsisten */
    :root {
        --primary: #1976D2; /* Biru Ojek */
        --accent: #F57C00;
        --text-main: #212121;
        --text-sub: #757575;
        --bg-body: #F5F7FA;
        --white: #FFFFFF;
        --radius-md: 12px;
        --header-height: 70px;
        --safe-top-padding: calc(var(--header-height) + 20px);
    }

    body {
        background-color: var(--bg-body);
        color: var(--text-main);
    }

    .app-wrapper {
        max-width: 600px; /* Layout satu kolom fokus seperti aplikasi Gojek */
        margin: 0 auto;
        padding: 0 16px;
        padding-top: var(--safe-top-padding);
        padding-bottom: 80px;
        min-height: 100vh;
    }

    /* HEADER KHUSUS HALAMAN INI */
    .page-header {
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .back-btn {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        color: var(--text-main);
        text-decoration: none;
    }
    .page-title h1 { margin: 0; font-size: 20px; font-weight: 800; }
    .page-title p { margin: 0; font-size: 12px; color: var(--text-sub); }

    /* TAB NAVIGASI */
    .tab-nav {
        display: flex;
        background: #e0e0e0;
        border-radius: 25px;
        padding: 4px;
        margin-bottom: 24px;
    }
    .tab-btn {
        flex: 1;
        text-align: center;
        padding: 10px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.3s;
    }
    .tab-btn.active {
        background: var(--white);
        color: var(--primary);
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    /* SEARCH BAR */
    .search-box {
        position: relative;
        margin-bottom: 20px;
    }
    .search-input {
        width: 100%;
        padding: 14px 16px 14px 45px;
        border-radius: 12px;
        border: 1px solid #ddd;
        background: #fff;
        font-size: 14px;
        outline: none;
        transition: 0.2s;
    }
    .search-input:focus { border-color: var(--primary); }
    .search-icon {
        position: absolute;
        left: 16px; top: 50%;
        transform: translateY(-50%);
        color: #999;
    }

    /* DRIVER CARD */
    .driver-card {
        background: var(--white);
        border-radius: var(--radius-md);
        padding: 16px;
        margin-bottom: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s;
    }
    .driver-card:active { transform: scale(0.98); }

    .driver-img-box {
        position: relative;
        width: 60px; height: 60px;
        flex-shrink: 0;
    }
    .driver-img {
        width: 100%; height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #f0f0f0;
    }
    .status-dot {
        width: 12px; height: 12px;
        background: #ccc;
        border: 2px solid #fff;
        border-radius: 50%;
        position: absolute;
        bottom: 0; right: 0;
    }
    .status-online { background: #4CAF50; }
    .status-busy { background: #F44336; }

    .driver-info { flex-grow: 1; }
    .driver-name { font-weight: 700; font-size: 15px; margin-bottom: 2px; color: var(--text-main); }
    .driver-meta { font-size: 11px; color: var(--text-sub); display: flex; flex-direction: column; gap: 2px; }
    
    .btn-wa {
        background: #25D366;
        color: white;
        width: 40px; height: 40px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        text-decoration: none;
        font-size: 20px;
        box-shadow: 0 4px 10px rgba(37, 211, 102, 0.3);
    }

    /* FORM REGISTRASI */
    .reg-form { display: none; background: #fff; padding: 20px; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 12px; font-weight: 600; margin-bottom: 6px; color: #555; }
    .form-control {
        width: 100%; padding: 12px;
        border: 1px solid #ddd; border-radius: 8px;
        font-size: 14px;
    }
    .btn-submit {
        width: 100%; padding: 14px;
        background: var(--primary); color: white;
        border: none; border-radius: 10px;
        font-weight: 700; cursor: pointer;
    }

    /* Helper Classes */
    .hidden { display: none; }
</style>

<main class="app-wrapper">

    <!-- HEADER -->
    <div class="page-header">
        <a href="<?php echo home_url('/'); ?>" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <div class="page-title">
            <h1>Ojek Desa</h1>
            <p>Antar jemput & pengiriman paket</p>
        </div>
    </div>

    <!-- TABS -->
    <div class="tab-nav">
        <div class="tab-btn active" onclick="switchTab('list')">Cari Driver</div>
        <div class="tab-btn" onclick="switchTab('register')">Daftar Jadi Ojek</div>
    </div>

    <!-- TAB CONTENT: LIST DRIVER -->
    <div id="tab-list">
        <!-- Search -->
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="search-driver" class="search-input" placeholder="Cari nama driver atau desa...">
        </div>

        <!-- Driver Container -->
        <div id="driver-container">
            <?php if (!empty($list_ojek)) : ?>
                <?php foreach ($list_ojek as $ojek) : 
                    $foto = !empty($ojek->foto_profil) ? $ojek->foto_profil : 'https://placehold.co/100x100?text=Driver';
                    $status_class = ($ojek->is_online == 1) ? 'status-online' : 'status-busy';
                    $status_text = ($ojek->is_online == 1) ? 'Online' : 'Offline';
                    
                    // Format Link WA
                    $no_hp = preg_replace('/^0/', '62', $ojek->no_hp);
                    $wa_msg = "Halo *{$ojek->nama}*, saya ingin memesan ojek/kirim paket. Apakah bisa?";
                    $wa_link = "https://wa.me/{$no_hp}?text=" . urlencode($wa_msg);
                ?>
                
                <div class="driver-card" data-search="<?php echo strtolower($ojek->nama . ' ' . $ojek->nama_desa); ?>">
                    <div class="driver-img-box">
                        <img src="<?php echo esc_url($foto); ?>" class="driver-img" alt="Foto">
                        <div class="status-dot <?php echo $status_class; ?>"></div>
                    </div>
                    
                    <div class="driver-info">
                        <div class="driver-name"><?php echo esc_html($ojek->nama); ?></div>
                        <div class="driver-meta">
                            <span><i class="fas fa-motorcycle text-xs"></i> <?php echo esc_html($ojek->merk_motor . ' - ' . $ojek->plat_nomor); ?></span>
                            <span><i class="fas fa-map-marker-alt text-xs"></i> <?php echo esc_html($ojek->nama_desa); ?></span>
                        </div>
                    </div>

                    <a href="<?php echo $wa_link; ?>" target="_blank" class="btn-wa">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>

                <?php endforeach; ?>
            <?php else : ?>
                <div style="text-align:center; padding: 40px 0; color:#999;">
                    <i class="fas fa-motorcycle" style="font-size: 40px; margin-bottom:10px; opacity:0.3;"></i>
                    <p>Belum ada driver ojek yang terdaftar.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB CONTENT: REGISTER FORM -->
    <div id="tab-register" class="hidden">
        <div class="reg-form">
            <h3 style="margin-top:0; margin-bottom:15px; font-size:18px;">Form Pendaftaran</h3>
            <p style="font-size:13px; color:#666; margin-bottom:20px; line-height:1.5;">
                Ingin mendapatkan penghasilan tambahan? Daftarkan diri Anda sebagai mitra Ojek Desa. Admin kami akan menghubungi Anda untuk verifikasi.
            </p>

            <form id="form-daftar-ojek">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required placeholder="Sesuai KTP">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nomor WhatsApp</label>
                    <input type="tel" name="no_hp" class="form-control" required placeholder="Contoh: 0812...">
                </div>

                <div class="form-group">
                    <label class="form-label">Desa Domisili</label>
                    <select name="id_desa" class="form-control" required>
                        <option value="">Pilih Desa</option>
                        <?php 
                        $desas = $wpdb->get_results("SELECT id, nama_desa FROM $table_desa ORDER BY nama_desa ASC");
                        foreach($desas as $d) {
                            echo "<option value='{$d->id}'>{$d->nama_desa}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Jenis Motor & Plat Nomor</label>
                    <input type="text" name="motor" class="form-control" required placeholder="Cth: Honda Beat - B 1234 ABC">
                </div>

                <button type="submit" class="btn-submit">Kirim Pendaftaran</button>
            </form>
        </div>
    </div>

</main>

<script>
    // 1. Logic Tab Switch
    function switchTab(tabName) {
        // Hide all tabs
        document.getElementById('tab-list').classList.add('hidden');
        document.getElementById('tab-register').classList.add('hidden');
        
        // Reset Nav Buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

        // Show selected tab
        document.getElementById('tab-' + tabName).classList.remove('hidden');
        
        // Highlight Button
        // Cari tombol yang di-klik secara manual via event (sederhana: pakai index)
        const buttons = document.querySelectorAll('.tab-btn');
        if(tabName === 'list') buttons[0].classList.add('active');
        else buttons[1].classList.add('active');
    }

    // 2. Logic Search Filter
    const searchInput = document.getElementById('search-driver');
    if(searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            const term = e.target.value.toLowerCase();
            const cards = document.querySelectorAll('.driver-card');
            
            cards.forEach(card => {
                const data = card.getAttribute('data-search');
                if(data.includes(term)) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // 3. Logic Form Submit (Simulasi ke WA Admin karena API Endpoint belum fixed)
    document.getElementById('form-daftar-ojek').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Ambil data form
        const nama = this.querySelector('[name="nama"]').value;
        const hp = this.querySelector('[name="no_hp"]').value;
        const motor = this.querySelector('[name="motor"]').value;
        
        // Nomor Admin Desa (Ganti dengan nomor admin sebenarnya)
        const adminPhone = '6281234567890'; 
        
        const msg = `Halo Admin, saya ingin mendaftar Ojek Desa.%0A%0A*Nama:* ${nama}%0A*No HP:* ${hp}%0A*Motor:* ${motor}%0A%0AMohon info selanjutnya.`;
        
        // Redirect ke WA Admin
        window.open(`https://wa.me/${adminPhone}?text=${msg}`, '_blank');
    });
</script>

<?php get_footer(); ?>