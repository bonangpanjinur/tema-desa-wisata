jQuery(document).ready(function($) {
    const ajaxUrl = dw_ajax.ajax_url;
    const nonce = dw_ajax.nonce;

    // Trigger saat pemilihan wilayah/kurir berubah
    $('#shipping-region').on('change', function() {
        const regionId = $(this).val();
        updateShippingCost(regionId);
    });

    function updateShippingCost(regionId) {
        if (!regionId) return;

        $('.shipping-loading').show();
        $('#shipping-cost-display').css('opacity', '0.5');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'dw_calculate_shipping_cost', // Pastikan hook ini ada di plugin core
                region_id: regionId,
                nonce: nonce
            },
            success: function(response) {
                $('.shipping-loading').hide();
                $('#shipping-cost-display').css('opacity', '1');

                if (response.success) {
                    // Update tampilan biaya
                    $('#shipping-cost-value').text(response.data.formatted_cost);
                    $('#total-payment-value').text(response.data.formatted_total);
                    
                    // Simpan nilai untuk form submission
                    $('input[name="shipping_cost"]').val(response.data.cost);
                } else {
                    alert('Gagal menghitung ongkir: ' + response.data.message);
                }
            },
            error: function() {
                $('.shipping-loading').hide();
                alert('Terjadi kesalahan koneksi.');
            }
        });
    }

    // Handle Form Submit Checkout
    $('#checkout-form').on('submit', function(e) {
        // Validasi sederhana
        if ($('#shipping-region').val() === '') {
            e.preventDefault();
            alert('Silakan pilih wilayah pengiriman terlebih dahulu.');
            return;
        }
    });
});