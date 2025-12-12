/**
 * AJAX Cart Handler
 * Menangani penambahan produk ke keranjang tanpa reload.
 */

jQuery(document).ready(function($) {
    
    // Saat tombol "Tambah ke Keranjang" diklik
    $('#btn-add-to-cart').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var productId = $button.data('product-id');
        var quantity = parseInt($('#qty').val());
        var $messageBox = $('#cart-message'); // Container pesan di single-dw_produk.php
        
        // Validasi input
        if (quantity < 1) {
            alert('Jumlah minimal 1');
            return;
        }

        // Matikan tombol sementara agar tidak double klik
        $button.prop('disabled', true).text('Memproses...');
        $messageBox.html('').hide();

        // Siapkan data untuk dikirim
        var data = {
            action: 'dw_theme_add_to_cart', // Action hook di functions.php
            nonce: dw_ajax_data.nonce,      // Nonce keamanan dari functions.php
            product_id: productId,
            quantity: quantity
        };

        // Kirim request AJAX ke WordPress
        $.ajax({
            url: dw_ajax_data.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    // Berhasil
                    $button.text('Berhasil!').addClass('btn-secondary').removeClass('btn-primary');
                    $messageBox.html('<div class="success">Produk berhasil masuk keranjang. <a href="' + dw_ajax_data.site_url + '/keranjang/">Lihat Keranjang</a></div>').fadeIn();
                    
                    // Reset tombol setelah 2 detik
                    setTimeout(function() {
                        $button.prop('disabled', false).text('Tambah ke Keranjang').addClass('btn-primary').removeClass('btn-secondary');
                    }, 3000);
                    
                    // Opsional: Update badge keranjang di header jika ada
                    // $('.cart-count').text(response.data.cart_count); 
                } else {
                    // Gagal (Error dari server)
                    $button.prop('disabled', false).text('Tambah ke Keranjang');
                    $messageBox.html('<div class="error">' + (response.data.message || 'Terjadi kesalahan') + '</div>').fadeIn();
                }
            },
            error: function() {
                // Error koneksi / Server mati
                $button.prop('disabled', false).text('Tambah ke Keranjang');
                $messageBox.html('<div class="error">Gagal menghubungi server.</div>').fadeIn();
            }
        });
    });

});