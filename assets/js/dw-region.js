/**
 * DW Region Optimized
 * Fitur: 
 * 1. Client-Side Caching (LocalStorage) -> Hemat kuota user & server
 * 2. Select2 Integration -> Dropdown bisa dicari (Searchable)
 * 3. Security -> Menggunakan Nonce
 */

(function($) {
    'use strict';

    const CACHE_PREFIX = 'dw_region_cache_';
    const CACHE_EXPIRY = 24 * 60 * 60 * 1000; // 24 Jam

    // Inisialisasi Select2 pada dropdown
    function initSelect2() {
        if ($.fn.select2) {
            $('.dw-select2').select2({
                width: '100%',
                placeholder: 'Ketik untuk mencari...',
                language: {
                    noResults: function() {
                        return "Data tidak ditemukan";
                    }
                }
            });
        }
    }

    // Simpan ke LocalStorage
    function setCache(key, data) {
        const payload = {
            timestamp: new Date().getTime(),
            data: data
        };
        try {
            localStorage.setItem(CACHE_PREFIX + key, JSON.stringify(payload));
        } catch (e) {
            console.warn('LocalStorage full or disabled');
        }
    }

    // Ambil dari LocalStorage
    function getCache(key) {
        const raw = localStorage.getItem(CACHE_PREFIX + key);
        if (!raw) return null;

        try {
            const payload = JSON.parse(raw);
            const now = new Date().getTime();
            // Cek kadaluarsa
            if (now - payload.timestamp > CACHE_EXPIRY) {
                localStorage.removeItem(CACHE_PREFIX + key);
                return null;
            }
            return payload.data;
        } catch (e) {
            return null;
        }
    }

    // Render Opsi ke HTML Select
    function renderOptions($element, data, placeholder) {
        let html = `<option value="">${placeholder}</option>`;
        $.each(data, function(index, item) {
            // Sesuaikan key 'id' dan 'name' dengan respons JSON dari PHP Anda
            html += `<option value="${item.id}">${item.name}</option>`;
        });
        
        $element.html(html).prop('disabled', false);
        
        // Refresh Select2 agar opsi baru muncul
        if ($.fn.select2) {
            $element.trigger('change');
        }
    }

    // Fungsi Utama Load Data
    function loadRegion(type, parentId, $targetElement) {
        const cacheKey = type + '_' + parentId;
        const $spinner = $targetElement.next('.loading-spinner'); // Asumsi ada spinner (opsional)

        $targetElement.prop('disabled', true).html('<option>Memuat...</option>');
        if($spinner.length) $spinner.show();

        // 1. Cek Client Cache dulu
        const cachedData = getCache(cacheKey);
        if (cachedData) {
            console.log('Serving from cache:', type);
            renderOptions($targetElement, cachedData, 'Pilih ' + type);
            if($spinner.length) $spinner.hide();
            return;
        }

        // 2. Fetch Server jika tidak ada cache
        $.ajax({
            url: dw_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_region_data', // Sesuai action di functions.php
                type: type,
                id: parentId,
                nonce: dw_ajax.nonce // Security Token
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    // Simpan ke cache browser
                    setCache(cacheKey, response.data);
                    // Render
                    renderOptions($targetElement, response.data, 'Pilih ' + type);
                } else {
                    $targetElement.html('<option value="">Data kosong</option>');
                }
            },
            error: function() {
                alert('Gagal mengambil data wilayah. Periksa koneksi internet Anda.');
                $targetElement.html('<option value="">Error</option>');
            },
            complete: function() {
                $targetElement.prop('disabled', false);
                if($spinner.length) $spinner.hide();
            }
        });
    }

    // DOM Ready
    $(document).ready(function() {
        // Aktifkan Select2
        initSelect2();

        // Event Listeners
        // Ganti ID selector (#provinsi, #kabupaten) sesuai form HTML Anda
        
        $('#provinsi').on('change', function() {
            const id = $(this).val();
            // Reset anak-anaknya
            $('#kabupaten').html('<option value="">Pilih Provinsi Dulu</option>').prop('disabled', true).trigger('change');
            $('#kecamatan').html('<option value="">Pilih Kabupaten Dulu</option>').prop('disabled', true).trigger('change');
            $('#desa').html('<option value="">Pilih Kecamatan Dulu</option>').prop('disabled', true).trigger('change');

            if (id) {
                loadRegion('kabupaten', id, $('#kabupaten'));
            }
        });

        $('#kabupaten').on('change', function() {
            const id = $(this).val();
            $('#kecamatan').html('<option value="">Pilih Kabupaten Dulu</option>').prop('disabled', true).trigger('change');
            
            if (id) {
                loadRegion('kecamatan', id, $('#kecamatan'));
            }
        });

        $('#kecamatan').on('change', function() {
            const id = $(this).val();
            $('#desa').html('<option value="">Pilih Kecamatan Dulu</option>').prop('disabled', true).trigger('change');
            
            if (id) {
                loadRegion('desa', id, $('#desa'));
            }
        });

    });

})(jQuery);