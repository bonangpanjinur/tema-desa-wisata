jQuery(document).ready(function($) {
    
    // Fungsi umum untuk menambah ke keranjang
    function addToCart(productId, quantity, isBuyNow = false) {
        
        // Tampilkan loading state (opsional: ubah teks tombol)
        let btnSelector = isBuyNow ? '#buy-now' : '#add-to-cart';
        let originalText = $(btnSelector).html();
        $(btnSelector).html('<i class="fas fa-spinner fa-spin"></i> Proses...').prop('disabled', true);

        $.ajax({
            url: dw_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'dw_add_to_cart',
                product_id: productId,
                quantity: quantity,
                nonce: dw_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // 1. Update Badge Cart di Header (Cari elemen dengan class .cart-count)
                    $('.cart-count').text(response.data.cart_count).removeClass('hidden');

                    if (isBuyNow) {
                        // Jika Beli Langsung, redirect ke Checkout/Cart
                        window.location.href = dw_ajax.cart_url; // Atau checkout_url
                    } else {
                        // Jika Tambah Keranjang, tampilkan notifikasi sukses
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: response.data.message,
                                showConfirmButton: false,
                                timer: 1500
                            });
                        } else {
                            alert(response.data.message);
                        }
                    }
                } else {
                    // Error Handling (Stok habis, dll)
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.data.message
                        });
                    } else {
                        alert(response.data.message);
                    }
                }
            },
            error: function() {
                alert('Terjadi kesalahan koneksi. Silakan coba lagi.');
            },
            complete: function() {
                // Kembalikan tombol ke kondisi semula
                $(btnSelector).html(originalText).prop('disabled', false);
            }
        });
    }

    // 1. Event Listener: Tombol Tambah ke Keranjang (Halaman Single)
    $('#add-to-cart').on('click', function(e) {
        e.preventDefault();
        var product_id = $(this).data('id');
        var quantity = $('#quantity').val() || 1;
        
        addToCart(product_id, quantity, false);
    });

    // 2. Event Listener: Tombol Beli Langsung (Halaman Single)
    $('#buy-now').on('click', function(e) {
        e.preventDefault();
        var product_id = $(this).data('id');
        var quantity = $('#quantity').val() || 1;

        addToCart(product_id, quantity, true);
    });

    // 3. Event Listener: Tombol di Card Produk (Archive/Home)
    // Menggunakan delegation 'document' karena card mungkin dimuat via ajax load more
    $(document).on('click', '.add-to-cart-btn', function(e) {
        e.preventDefault();
        var product_id = $(this).data('id');
        // Untuk tombol di card list, qty biasanya 1
        addToCart(product_id, 1, false);
    });

});