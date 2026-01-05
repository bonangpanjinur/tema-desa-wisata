jQuery(document).ready(function($) {
    const ajaxUrl = dw_pedagang_data.ajax_url;
    const nonce = dw_pedagang_data.nonce;

    // --- 1. Preview Gambar ---
    $('#gambar_produk').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').html('<img src="' + e.target.result + '" class="img-fluid" style="max-height: 100%;">');
            }
            reader.readAsDataURL(file);
        }
    });

    // --- 2. Submit Form Produk (Tambah/Edit) ---
    $('#form-produk-pedagang').on('submit', function(e) {
        e.preventDefault();

        // Validasi Sederhana
        if (!$('#judul').val() || !$('#harga').val()) {
            alert('Judul dan Harga wajib diisi!');
            return;
        }

        const btn = $('#btn-save-produk');
        const spinner = btn.find('.spinner-border');
        const formData = new FormData(this);

        // UI Loading
        btn.prop('disabled', true);
        spinner.removeClass('d-none');

        // Tentukan endpoint (Create atau Update)
        // Catatan: Di REST API plugin, kita perlu endpoint 'POST /produk' yang handle multipart/form-data
        // Endpoint ini harus sesuai dengan yang didaftarkan di api-pedagang.php plugin
        formData.append('action', 'dw_save_produk_ajax');
        formData.append('security', nonce);

        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false, // Wajib false untuk upload file
            contentType: false, // Wajib false untuk upload file,
            success: function(response) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');

                if (response.success) {
                    alert('Produk berhasil disimpan!');
                    window.location.href = '?tab=produk'; // Redirect ke list
                } else {
                    alert('Gagal: ' + (response.message || 'Terjadi kesalahan.'));
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
                let errMsg = "Terjadi kesalahan server.";
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errMsg = xhr.responseJSON.message;
                }
                alert('Error: ' + errMsg);
            }
        });
    });

    // --- 3. Hapus Produk ---
    $('.btn-delete-produk').on('click', function(e) {
        e.preventDefault();
        const produkId = $(this).data('id');
        
        if (confirm('Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.')) {
            const btn = $(this);
            btn.prop('disabled', true);

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dw_delete_produk_ajax',
                    id: produkId,
                    security: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#produk-row-' + produkId).fadeOut(300, function() { $(this).remove(); });
                    } else {
                        alert('Gagal menghapus: ' + (response.message || 'Error'));
                        btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert('Gagal menghubungi server.');
                    btn.prop('disabled', false);
                }
            });
        }
    });
});