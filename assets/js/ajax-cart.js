jQuery(document).ready(function($) {
    
    /**
     * Handler untuk Form Add to Cart
     * Digunakan di single-dw_produk.php dan single-dw_wisata.php
     */
    $('#form-add-to-cart').on('submit', function(e) {
        e.preventDefault();

        // Ambil elemen form dan tombol
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $message = $('#cart-message');
        
        // Simpan teks asli tombol
        var originalText = $button.html();

        // 1. Validasi Input
        var qty = $form.find('input[name="quantity"]').val();
        if(qty < 1) {
            alert("Jumlah minimal 1");
            return;
        }

        // 2. Siapkan Data untuk dikirim ke Backend Plugin
        var formData = {
            // 'action' harus sesuai dengan add_action('wp_ajax_dw_add_to_cart', ...) di plugin
            action: 'dw_add_to_cart', 
            
            // Ambil nonce dari localize script functions.php untuk keamanan
            security: dw_global.nonce, 
            
            // Data produk
            product_id: $form.find('input[name="product_id"]').val(),
            quantity: qty,
            type: $form.find('input[name="type"]').val() || 'produk'
        };

        // 3. UI Loading State
        $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
        $message.hide().removeClass('text-green-600 text-red-600');

        // 4. Kirim Request ke Plugin via wp-admin/admin-ajax.php
        $.ajax({
            url: dw_global.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                // Respons dari wp_send_json_success() di plugin
                if (response.success) {
                    $message.addClass('text-green-600').html('<i class="fas fa-check-circle"></i> ' + response.data.message).fadeIn();
                    
                    // Update counter cart di header jika ada elemennya
                    if($('.dw-cart-count').length) {
                        $('.dw-cart-count').text(response.data.cart_count);
                    }

                    // Opsional: Redirect ke halaman cart
                    // window.location.href = dw_global.cart_url;
                } else {
                    // Respons dari wp_send_json_error() di plugin
                    $message.addClass('text-red-600').html('<i class="fas fa-exclamation-circle"></i> ' + (response.data.message || 'Gagal menambahkan ke keranjang')).fadeIn();
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", error);
                $message.addClass('text-red-600').html('Terjadi kesalahan server. Silakan coba lagi.').fadeIn();
            },
            complete: function() {
                // Kembalikan tombol ke kondisi semula
                $button.prop('disabled', false).html(originalText);
            }
        });
    });

    /**
     * Handler untuk Update Cart (jika ada halaman keranjang di tema)
     */
    $('.dw-cart-update-qty').on('change', function() {
        var cartItemId = $(this).data('cart-item-id');
        var newQty = $(this).val();

        $.ajax({
            url: dw_global.ajax_url,
            type: 'POST',
            data: {
                action: 'dw_update_cart_qty',
                security: dw_global.nonce,
                cart_item_key: cartItemId,
                quantity: newQty
            },
            success: function(response) {
                if(response.success) {
                    location.reload(); // Reload untuk update total harga
                }
            }
        });
    });

});