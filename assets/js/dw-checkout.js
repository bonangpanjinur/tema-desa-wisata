jQuery(document).ready(function($) {
    // Menggunakan variabel global dw_ajax sesuai kode Anda
    // Pastikan wp_localize_script di functions.php menggunakan handle 'dw_ajax'
    const ajaxUrl = (typeof dw_ajax !== 'undefined') ? dw_ajax.ajax_url : dw_vars.ajax_url;
    const nonce = (typeof dw_ajax !== 'undefined') ? dw_ajax.nonce : '';

    const checkoutForm = $('#checkout-form'); // Menggunakan ID form sesuai kode Anda

    // 1. Trigger saat pemilihan wilayah/kurir berubah
    $('#shipping-region').on('change', function() {
        const regionId = $(this).val();
        updateShippingCost(regionId);
    });

    function updateShippingCost(regionId) {
        if (!regionId) return;

        // UI Feedback: Loading
        $('.shipping-loading').show();
        $('#shipping-cost-display').css('opacity', '0.5');

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'dw_calculate_shipping_cost', // Hook WordPress
                region_id: regionId,
                nonce: nonce
            },
            success: function(response) {
                // UI Feedback: Selesai
                $('.shipping-loading').hide();
                $('#shipping-cost-display').css('opacity', '1');

                if (response.success) {
                    // Update tampilan biaya ongkir dan total
                    $('#shipping-cost-value').text(response.data.formatted_cost);
                    $('#total-payment-value').text(response.data.formatted_total);
                    
                    // Simpan nilai ke input hidden untuk dikirim saat submit
                    $('input[name="shipping_cost"]').val(response.data.cost);
                } else {
                    alert('Gagal menghitung ongkir: ' + (response.data.message || 'Error tidak diketahui'));
                    // Reset selection jika gagal
                    $('#shipping-region').val('').trigger('change');
                }
            },
            error: function() {
                $('.shipping-loading').hide();
                $('#shipping-cost-display').css('opacity', '1');
                alert('Terjadi kesalahan koneksi. Silakan coba lagi.');
            }
        });
    }

    // 2. Handle Form Submit Checkout
    checkoutForm.on('submit', function(e) {
        let isValid = true;
        let errorMessage = '';
        const submitBtn = $(this).find('button[type="submit"]');

        // A. Validasi Wilayah (Wajib)
        if ($('#shipping-region').val() === '') {
            isValid = false;
            errorMessage = 'Silakan pilih wilayah pengiriman terlebih dahulu.';
        }

        // B. Validasi Tambahan (Opsional/Enhanced)
        // Cek input No HP jika ada
        const phoneInput = $('input[name="billing_phone"], input[name="phone"]');
        if (isValid && phoneInput.length > 0) {
            const phoneVal = phoneInput.val().replace(/\D/g, ''); // Ambil angka saja
            if (phoneVal.length < 10) {
                isValid = false;
                errorMessage = 'Nomor HP tidak valid. Mohon periksa kembali.';
            }
        }

        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
            // Scroll ke atas atau ke input yang error
            $('html, body').animate({ scrollTop: checkoutForm.offset().top - 50 }, 500);
            return;
        }

        // C. Loading State pada Tombol Submit
        // Agar user tidak klik berkali-kali (Double Order Prevention)
        submitBtn.prop('disabled', true).html('<span class="spin-loader"></span> Memproses...');
        
        // Form akan lanjut submit secara normal (PHP POST) 
        // kecuali Anda ingin mengubahnya menjadi AJAX submit sepenuhnya di sini.
    });
});