<?php
/**
 * Template Name: Halaman Detail Transaksi
 */

if ( ! is_user_logged_in() || ! isset($_GET['id']) ) {
    wp_redirect( home_url('/transaksi') );
    exit;
}

get_header(); 
$order_id = intval($_GET['id']);
?>

<div class="bg-gray-50 min-h-screen pb-24">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-30">
        <div class="container mx-auto px-4 h-16 flex items-center gap-4">
            <a href="<?php echo home_url('/transaksi'); ?>" class="text-gray-500 hover:text-gray-900"><i class="ph-bold ph-arrow-left text-xl"></i></a>
            <h1 class="font-bold text-lg text-gray-800">Detail Pesanan</h1>
        </div>
    </div>

    <div id="order-detail-container" class="container mx-auto px-4 py-6 max-w-2xl">
        <!-- Loading State -->
        <div class="animate-pulse space-y-4">
            <div class="h-24 bg-white rounded-xl"></div>
            <div class="h-40 bg-white rounded-xl"></div>
        </div>
    </div>
</div>

<!-- Modal Upload Bukti -->
<div id="modal-upload" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl w-full max-w-sm p-6">
        <h3 class="font-bold text-lg mb-4">Upload Bukti Transfer</h3>
        <form id="form-upload-bukti">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-700 mb-2">Pilih Foto</label>
                <input type="file" name="file" accept="image/*" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary file:text-white hover:file:bg-secondary">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="$('#modal-upload').addClass('hidden')" class="flex-1 py-2 border border-gray-300 rounded-lg text-sm font-bold">Batal</button>
                <button type="submit" class="flex-1 py-2 bg-primary text-white rounded-lg text-sm font-bold">Upload</button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const API_BASE = dwData.api_url;
    const JWT_TOKEN = localStorage.getItem('dw_jwt_token');
    const ORDER_ID = <?php echo $order_id; ?>;

    function loadDetail() {
        $.ajax({
            url: API_BASE + 'pembeli/orders/' + ORDER_ID,
            type: 'GET',
            headers: { 'Authorization': 'Bearer ' + JWT_TOKEN },
            success: function(res) {
                const order = res; // Sesuaikan struktur response API
                const $cont = $('#order-detail-container');
                
                // Status Logic
                let statusBadge = `<span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-bold">${order.status_transaksi}</span>`;
                let actionButton = '';

                if(order.status_transaksi === 'menunggu_pembayaran') {
                    statusBadge = `<span class="bg-orange-100 text-orange-700 px-2 py-1 rounded text-xs font-bold">Belum Bayar</span>`;
                    actionButton = `<button onclick="$('#modal-upload').removeClass('hidden')" class="w-full bg-primary text-white py-3 rounded-xl font-bold shadow-lg mb-3">Upload Bukti Bayar</button>`;
                }

                // Render HTML
                let itemsHtml = '';
                if(order.sub_pesanan) {
                    order.sub_pesanan.forEach(sub => {
                        sub.items.forEach(item => {
                            itemsHtml += `
                            <div class="flex gap-4 py-3 border-b border-gray-50 last:border-0">
                                <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                    <!-- Placeholder img, idealnya API return image url -->
                                    <div class="w-full h-full flex items-center justify-center text-gray-400"><i class="ph-fill ph-image"></i></div>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-sm font-bold text-gray-800 line-clamp-1">${item.nama_produk}</h4>
                                    <p class="text-xs text-gray-500">${item.jumlah} x Rp ${new Intl.NumberFormat('id-ID').format(item.harga_satuan)}</p>
                                </div>
                                <div class="text-sm font-bold text-gray-900">
                                    Rp ${new Intl.NumberFormat('id-ID').format(item.total_harga)}
                                </div>
                            </div>`;
                        });
                    });
                }

                const html = `
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-4">
                        <div class="flex justify-between items-center mb-4">
                            <div class="text-sm text-gray-500">Kode: <span class="font-bold text-gray-900">#${order.kode_unik}</span></div>
                            ${statusBadge}
                        </div>
                        <div class="flex justify-between items-center py-3 border-t border-dashed border-gray-200">
                            <span class="text-sm text-gray-600">Total Tagihan</span>
                            <span class="text-lg font-bold text-primary">Rp ${new Intl.NumberFormat('id-ID').format(order.total_transaksi)}</span>
                        </div>
                    </div>

                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-4">
                        <h3 class="font-bold text-sm text-gray-900 mb-3">Rincian Barang</h3>
                        ${itemsHtml}
                    </div>

                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 mb-6">
                        <h3 class="font-bold text-sm text-gray-900 mb-3">Info Pengiriman</h3>
                        <div class="text-sm text-gray-600 space-y-1">
                            <p><span class="font-semibold">Penerima:</span> ${order.alamat_pengiriman.nama_penerima}</p>
                            <p><span class="font-semibold">No HP:</span> ${order.alamat_pengiriman.no_hp}</p>
                            <p><span class="font-semibold">Alamat:</span> ${order.alamat_pengiriman.alamat_lengkap}</p>
                        </div>
                    </div>

                    <div class="fixed bottom-0 left-0 right-0 bg-white p-4 border-t border-gray-100">
                        <div class="container mx-auto max-w-2xl">
                            ${actionButton}
                            <a href="https://wa.me/?text=Halo%20saya%20butuh%20bantuan%20pesanan%20${order.kode_unik}" target="_blank" class="block w-full text-center py-3 border border-gray-200 rounded-xl text-sm font-bold text-gray-600 hover:bg-gray-50">
                                Bantuan
                            </a>
                        </div>
                    </div>
                `;
                $cont.html(html);
            }
        });
    }

    loadDetail();

    // Handle Upload
    $('#form-upload-bukti').on('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        // ... Logic upload ke API dw_api_upload_media & dw_api_confirm_payment ...
        // Simplifikasi:
        alert('Fitur upload akan diaktifkan segera.');
        $('#modal-upload').addClass('hidden');
    });
});
</script>

<?php get_footer(); ?>