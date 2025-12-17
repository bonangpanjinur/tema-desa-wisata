jQuery(document).ready(function($) {
    
    // Helper: Format Rupiah
    function formatRupiah(number) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
    }

    // ========================================================================
    // 1. ADD TO CART (Dari Halaman Produk/Arsip)
    // ========================================================================
    $('.btn-add-to-cart').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var originalHtml = btn.html();
        var productId = btn.data('product-id');
        var isCustom = btn.data('is-custom') ? 1 : 0;
        
        // Loading state
        btn.addClass('opacity-50 cursor-not-allowed').prop('disabled', true).html('<i class="fas fa-spinner fa-spin text-xs"></i>');

        $.ajax({
            url: dw_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dw_theme_add_to_cart', // Pastikan nama action sesuai di ajax-handlers.php (dw_theme_add_to_cart atau dw_add_to_cart)
                nonce: dw_ajax.nonce,
                product_id: productId,
                qty: 1,
                is_custom_db: isCustom
            },
            success: function(response) {
                if(response.success) {
                    // Update Badge Cart di Header (Mobile & Desktop)
                    $('#header-cart-count, #header-cart-count-mobile, .cart-count-badge').text(response.data.cart_count).removeClass('hidden').addClass('flex');
                    
                    // Feedback Sukses Visual
                    btn.html('<i class="fas fa-check text-xs"></i>').removeClass('bg-gray-50 text-gray-600').addClass('bg-green-600 text-white');
                    
                    // Reset tombol setelah 2 detik
                    setTimeout(function() {
                        btn.html(originalHtml)
                           .removeClass('bg-green-600 text-white opacity-50 cursor-not-allowed')
                           .addClass('bg-gray-50 text-gray-600 hover:bg-orange-500')
                           .prop('disabled', false);
                    }, 2000);
                } else {
                    alert('Gagal: ' + (response.data.message || 'Error'));
                    btn.html(originalHtml).removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
                }
            },
            error: function() {
                alert('Terjadi kesalahan koneksi.');
                btn.html(originalHtml).removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
            }
        });
    });

    // ========================================================================
    // 2. UPDATE QUANTITY (Di Halaman Cart - Sesuai page-cart.php)
    // ========================================================================
    $('.btn-update-qty').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var action = btn.data('action'); // 'increase' or 'decrease'
        var input = btn.siblings('.input-qty');
        var currentVal = parseInt(input.val()) || 0;
        var maxVal = parseInt(input.data('max')) || 999;
        var productId = btn.data('id');
        var newVal = currentVal;

        // Logic Limitasi
        if (action === 'increase') {
            if (currentVal < maxVal) {
                newVal = currentVal + 1;
            } else {
                alert('Stok maksimal tercapai (' + maxVal + ')');
                return;
            }
        } else {
            if (currentVal > 1) {
                newVal = currentVal - 1;
            } else {
                return; // Batas minimal 1
            }
        }

        // Disable UI sementara
        btn.parent().find('button').prop('disabled', true);
        
        // AJAX Request
        $.post(dw_ajax.ajax_url, {
            action: 'dw_update_cart_qty', // Pastikan action ini ada di ajax-handlers.php
            nonce: dw_ajax.nonce,
            product_id: productId,
            quantity: newVal
        }, function(response) {
            if (response.success) {
                input.val(newVal);
                
                // Update Subtotal per Item
                var row = btn.closest('.cart-item-row');
                row.find('.subtotal-display').text(formatRupiah(response.data.item_subtotal));
                
                // Update Total Keranjang
                $('#cart-total').text(formatRupiah(response.data.cart_total));
                $('#cart-grand-total').text(formatRupiah(response.data.cart_total));
                
                // Update Count di Header
                $('#header-cart-count, .cart-count-badge').text(response.data.cart_count);
            } else {
                alert('Gagal mengupdate: ' + (response.data.message || 'Error'));
            }
            // Re-enable buttons
            btn.parent().find('button').prop('disabled', false);
        });
    });

    // ========================================================================
    // 3. REMOVE ITEM (Di Halaman Cart - Sesuai page-cart.php)
    // ========================================================================
    $('.btn-remove-item').on('click', function(e) {
        e.preventDefault();
        if (!confirm('Yakin ingin menghapus produk ini dari keranjang?')) return;

        var btn = $(this);
        var productId = btn.data('id');
        var row = btn.closest('.cart-item-row');

        $.post(dw_ajax.ajax_url, {
            action: 'dw_remove_cart_item',
            nonce: dw_ajax.nonce,
            product_id: productId
        }, function(response) {
            if (response.success) {
                // Animasi Hapus
                row.fadeOut(300, function() { $(this).remove(); });
                
                // Update Total
                $('#cart-total').text(formatRupiah(response.data.cart_total));
                $('#cart-grand-total').text(formatRupiah(response.data.cart_total));
                $('#header-cart-count, .cart-count-badge').text(response.data.cart_count);

                // Reload jika keranjang jadi kosong
                if (response.data.cart_count === 0) {
                    location.reload();
                }
            } else {
                alert('Gagal menghapus item');
            }
        });
    });

});