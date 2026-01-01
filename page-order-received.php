<?php
/**
 * Template Name: Halaman Order Received
 * Description: Halaman sukses setelah checkout (Thank You Page).
 */

get_header();

// Ambil ID Transaksi dari URL (contoh: ?order_id=12345)
$order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';

// Jika tidak ada Order ID, lempar balik ke halaman Shop/Beranda
if ( empty($order_id) ) {
    echo '<script>window.location.href = "'.home_url('/').'";</script>';
    exit;
}

// (Opsional) Di sini Anda bisa menambahkan query ke database 
// untuk mengambil detail real transaksi berdasarkan $order_id
// $transaksi = get_post($order_id); 
?>

<div class="order-success-wrapper">
    <div class="order-success-card">
        
        <!-- Icon Sukses Animasi -->
        <div class="success-animation">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" />
                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
            </svg>
        </div>

        <div class="success-header">
            <h1>Pesanan Diterima!</h1>
            <p>Terima kasih telah berbelanja di Desa Wisata.</p>
        </div>

        <!-- Detail Singkat -->
        <div class="order-details-box">
            <div class="detail-row">
                <span class="label">Nomor Pesanan:</span>
                <span class="value order-id">#<?php echo esc_html($order_id); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Tanggal:</span>
                <span class="value"><?php echo date_i18n('d F Y'); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Status:</span>
                <span class="value status-pending">Menunggu Pembayaran</span>
            </div>
        </div>

        <!-- Instruksi -->
        <div class="instruction-box">
            <h3>Apa Selanjutnya?</h3>
            <p>Silakan lakukan pembayaran sesuai total tagihan. Admin atau Penjual akan memverifikasi pesanan Anda setelah bukti pembayaran diunggah.</p>
        </div>

        <!-- Tombol Aksi -->
        <div class="action-buttons">
            <!-- Arahkan ke halaman detail transaksi yang sudah Anda miliki -->
            <a href="<?php echo home_url('/transaksi/?id=' . $order_id); ?>" class="btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Lihat Detail & Bayar
            </a>
            
            <a href="<?php echo home_url('/produk'); ?>" class="btn-secondary">
                Lanjut Belanja
            </a>
        </div>

    </div>
</div>

<style>
    .order-success-wrapper {
        min-height: 80vh;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px 15px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    }

    .order-success-card {
        background: white;
        max-width: 500px;
        width: 100%;
        padding: 40px;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        text-align: center;
        border: 1px solid #edf2f7;
    }

    /* Animasi Checkmark */
    .success-animation { margin-bottom: 25px; }
    .checkmark { width: 80px; height: 80px; border-radius: 50%; display: block; stroke-width: 2; stroke: #4caf50; stroke-miterlimit: 10; margin: 0 auto; box-shadow: inset 0px 0px 0px #4caf50; animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both; }
    .checkmark__circle { stroke-dasharray: 166; stroke-dashoffset: 166; stroke-width: 2; stroke-miterlimit: 10; stroke: #4caf50; fill: none; animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards; }
    .checkmark__check { transform-origin: 50% 50%; stroke-dasharray: 48; stroke-dashoffset: 48; animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards; }
    @keyframes stroke { 100% { stroke-dashoffset: 0; } }
    @keyframes scale { 0%, 100% { transform: none; } 50% { transform: scale3d(1.1, 1.1, 1); } }
    @keyframes fill { 100% { box-shadow: inset 0px 0px 0px 50px #4caf50; stroke: #fff; } } /* Jika ingin fill penuh */

    .success-header h1 {
        color: #2d3748;
        font-size: 26px;
        margin: 0 0 10px;
        font-weight: 800;
    }
    .success-header p {
        color: #718096;
        margin: 0 0 30px;
    }

    .order-details-box {
        background: #f7fafc;
        border: 1px dashed #cbd5e0;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 14px;
    }
    .detail-row:last-child { margin-bottom: 0; }
    .detail-row .label { color: #718096; }
    .detail-row .value { font-weight: 600; color: #2d3748; }
    .detail-row .value.order-id { color: #1976D2; font-family: monospace; font-size: 16px; }
    .detail-row .value.status-pending { color: #ed8936; background: #fffaf0; padding: 2px 8px; border-radius: 4px; font-size: 12px; }

    .instruction-box {
        margin-bottom: 30px;
        text-align: left;
    }
    .instruction-box h3 {
        font-size: 16px;
        color: #2d3748;
        margin-bottom: 8px;
        font-weight: 700;
    }
    .instruction-box p {
        font-size: 13px;
        color: #718096;
        line-height: 1.6;
        margin: 0;
    }

    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .btn-primary, .btn-secondary {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.2s;
    }
    .btn-primary {
        background: #1976D2; /* Warna tema Anda */
        color: white;
        box-shadow: 0 4px 15px rgba(25, 118, 210, 0.2);
    }
    .btn-primary:hover {
        background: #1565c0;
        transform: translateY(-2px);
    }
    .btn-secondary {
        background: white;
        color: #718096;
        border: 1px solid #e2e8f0;
    }
    .btn-secondary:hover {
        background: #f7fafc;
        color: #2d3748;
    }

    /* Responsive */
    @media (max-width: 480px) {
        .order-success-card { padding: 30px 20px; }
    }
</style>

<?php get_footer(); ?>