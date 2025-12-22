jQuery(document).ready(function($) {
    const ajaxUrl = dw_ojek_data.ajax_url;
    const nonce = dw_ojek_data.nonce;

    // Toggle Status
    $('#btn-toggle-status').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const textSpan = $('#status-text');
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'dw_toggle_ojek_status',
                nonce: nonce
            },
            success: function(response) {
                btn.prop('disabled', false);
                if (response.success) {
                    const status = response.data.status;
                    if (status === 'active') {
                        btn.removeClass('btn-secondary').addClass('btn-success');
                        textSpan.text('Aktif (Siap Antar)');
                    } else {
                        btn.removeClass('btn-success').addClass('btn-secondary');
                        textSpan.text('Tidak Aktif');
                    }
                } else {
                    alert('Gagal mengubah status: ' + response.data.message);
                }
            },
            error: function() {
                btn.prop('disabled', false);
                alert('Koneksi error.');
            }
        });
    });

    // Handle Ambil Order (Simulasi)
    $('.btn-ambil-order').on('click', function() {
        const orderId = $(this).data('id');
        if(confirm('Ambil order #' + orderId + '?')) {
            alert('Fitur Ambil Order akan dihubungkan ke Plugin Logic (dw_assign_order).');
            // Di sini nanti panggil AJAX untuk assign order ke user ID
        }
    });
});