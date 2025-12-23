jQuery(document).ready(function($) {
    
    // Konfigurasi Selector (Sesuaikan ID di HTML Form)
    const els = {
        prov: { sel: '#dw_provinsi', hid: '#input_provinsi_nama' },
        city: { sel: '#dw_kota', hid: '#input_kabupaten_nama' },
        dist: { sel: '#dw_kecamatan', hid: '#input_kecamatan_nama' },
        vill: { sel: '#dw_desa', hid: '#input_kelurahan_nama' }
    };

    /**
     * Fungsi Utama Fetch API
     * Menggunakan endpoint 'dw_fetch_...' milik plugin desa-wisata-core
     */
    function fetchRegion(action, paramKey, paramValue, targetObj, nextTrigger = null) {
        const $target = $(targetObj.sel);
        const savedValue = $target.attr('data-selected'); // Ambil value lama dari DB

        // Reset & Loading State
        $target.html('<option value="">Memuat...</option>').prop('disabled', true);
        $(targetObj.hid).val(''); // Reset hidden name

        // Siapkan Data AJAX
        let ajaxData = { action: action };
        if (paramKey) ajaxData[paramKey] = paramValue;

        $.ajax({
            url: dwRegionVars.ajax_url, // URL admin-ajax.php dari functions.php
            type: 'GET',
            dataType: 'json',
            data: ajaxData,
            success: function(response) {
                let html = '<option value="">Pilih...</option>';
                
                // Cek format response (Plugin menggunakan wp_send_json_success)
                let data = response.success ? response.data : [];

                if (data && data.length > 0) {
                    $.each(data, function(i, item) {
                        // Cek apakah ini value yang tersimpan (untuk Edit Mode)
                        let isSelected = (item.id == savedValue) ? 'selected' : '';
                        html += `<option value="${item.id}" ${isSelected}>${item.name}</option>`;
                    });
                    $target.html(html).prop('disabled', false);

                    // UPDATE HIDDEN NAME SAAT LOAD (JIKA ADA YG TERPILIH)
                    if (savedValue && $target.val() == savedValue) {
                        let text = $target.find('option:selected').text();
                        $(targetObj.hid).val(text);
                        
                        // Trigger change untuk memuat level di bawahnya (Waterfall effect)
                        if (nextTrigger) $target.trigger('change');
                    }
                } else {
                    $target.html('<option value="">Data Kosong</option>');
                }
            },
            error: function() {
                $target.html('<option value="">Gagal Memuat</option>');
            }
        });
    }

    // --- EVENT LISTENERS ---

    // 1. Load PROVINSI saat halaman siap
    if ($(els.prov.sel).length) {
        // Param null karena provinsi tidak butuh parent ID
        fetchRegion('dw_fetch_provinces', null, null, els.prov, true);
    }

    // 2. Change PROVINSI -> Load KOTA
    $(document).on('change', els.prov.sel, function() {
        let id = $(this).val();
        let name = $(this).find('option:selected').text();
        
        // Simpan Nama Provinsi ke Hidden Input
        $(els.prov.hid).val(id ? name : '');

        // Reset anak-anaknya
        $(els.city.sel).html('<option value="">Pilih Kota/Kab</option>').prop('disabled', true);
        $(els.dist.sel).html('<option value="">Pilih Kecamatan</option>').prop('disabled', true);
        $(els.vill.sel).html('<option value="">Pilih Desa</option>').prop('disabled', true);

        if (id) fetchRegion('dw_fetch_regencies', 'province_id', id, els.city, true);
    });

    // 3. Change KOTA -> Load KECAMATAN
    $(document).on('change', els.city.sel, function() {
        let id = $(this).val();
        let name = $(this).find('option:selected').text();
        
        $(els.city.hid).val(id ? name : '');

        $(els.dist.sel).html('<option value="">Pilih Kecamatan</option>').prop('disabled', true);
        $(els.vill.sel).html('<option value="">Pilih Desa</option>').prop('disabled', true);

        if (id) fetchRegion('dw_fetch_districts', 'regency_id', id, els.dist, true);
    });

    // 4. Change KECAMATAN -> Load DESA
    $(document).on('change', els.dist.sel, function() {
        let id = $(this).val();
        let name = $(this).find('option:selected').text();
        
        $(els.dist.hid).val(id ? name : '');

        $(els.vill.sel).html('<option value="">Pilih Desa</option>').prop('disabled', true);

        if (id) fetchRegion('dw_fetch_villages', 'district_id', id, els.vill, false);
    });

    // 5. Change DESA -> Simpan Nama
    $(document).on('change', els.vill.sel, function() {
        let id = $(this).val();
        let name = $(this).find('option:selected').text();
        $(els.vill.hid).val(id ? name : '');
    });

});