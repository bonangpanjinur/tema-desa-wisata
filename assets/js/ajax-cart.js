jQuery(document).ready(function($) {
    
    // 1. ADD TO CART (Dari Halaman Produk/Arsip)
    $('.btn-add-to-cart').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var originalHtml = btn.html();
        var productId = btn.data('product-id');
        
        // Loading state
        btn.addClass('opacity-50 cursor-not-allowed').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.ajax({
            url: dw_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dw_add_to_cart', // Pastikan action ini ada di ajax-handlers.php plugin
                nonce: dw_ajax.nonce,
                product_id: productId,
                qty: 1
            },
            success: function(response) {
                if(response.success) {
                    // Update Badge Cart di Header (jika ada elemen dengan ID ini)
                    $('.cart-count-badge').text(response.data.cart_count).removeClass('hidden');
                    
                    // Feedback Sukses
                    btn.html('<i class="fas fa-check"></i>').removeClass('bg-gray-50 text-gray-600').addClass('bg-green-600 text-white');
                    
                    // Reset tombol setelah 2 detik
                    setTimeout(function() {
                        btn.html(originalHtml).removeClass('bg-green-600 text-white opacity-50 cursor-not-allowed').addClass('bg-gray-50 text-gray-600').prop('disabled', false);
                    }, 2000);
                } else {
                    alert('Gagal: ' + response.data.message);
                    btn.html(originalHtml).removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
                }
            },
            error: function() {
                alert('Terjadi kesalahan koneksi.');
                btn.html(originalHtml).removeClass('opacity-50 cursor-not-allowed').prop('disabled', false);
            }
        });
    });

    // 2. UPDATE QUANTITY (Di Halaman Cart)
    $('.qty-btn').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var isPlus = btn.hasClass('plus');
        var input = btn.siblings('.qty-input');
        var currentVal = parseInt(input.val());
        var newVal = isPlus ? currentVal + 1 : currentVal - 1;
        var cartId = btn.closest('.cart-item-row').data('cart-id');

        if(newVal < 1) return;

        input.val(newVal); // Update visual dulu biar cepat

        // Debounce simple untuk mencegah spam request
        clearTimeout($.data(this, 'timer'));
        $.data(this, 'timer', setTimeout(function() {
            updateCartItem(cartId, newVal);
        }, 500));
    });

    // 3. DELETE ITEM
    $('.delete-cart-item').on('click', function(e) {
        e.preventDefault();
        if(!confirm('Hapus produk ini dari keranjang?')) return;

        var btn = $(this);
        var cartId = btn.closest('.cart-item-row').data('cart-id');

        $.ajax({
            url: dw_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dw_remove_cart_item',
                nonce: dw_ajax.nonce,
                cart_id: cartId
            },
            success: function(response) {
                if(response.success) {
                    location.reload(); // Reload untuk update total (paling aman)
                } else {
                    alert('Gagal menghapus item.');
                }
            }
        });
    });

    function updateCartItem(cartId, qty) {
        $.ajax({
            url: dw_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dw_update_cart_qty',
                nonce: dw_ajax.nonce,
                cart_id: cartId,
                qty: qty
            },
            success: function(response) {
                if(response.success) {
                    // Update Subtotal Row
                    var row = $('[data-cart-id="'+cartId+'"]');
                    row.find('.item-subtotal').text(response.data.item_subtotal_formatted);
                    
                    // Update Grand Total
                    $('.cart-grand-total').text(response.data.grand_total_formatted);
                }
            }
        });
    }
});