document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-verifikasi');
    const input = document.getElementById('kode_tiket');
    const btnText = document.getElementById('btn-text');
    const btnLoading = document.getElementById('btn-loading');
    const resultArea = document.getElementById('result-area');
    const historyList = document.getElementById('history-list');

    // Pastikan variabel global dw_ajax tersedia (biasanya dari localize script di functions.php)
    // Jika belum ada di main functions, kita asumsikan struktur standar:
    const ajaxUrl = dw_ajax.ajax_url || '/wp-admin/admin-ajax.php';
    const nonce = dw_ajax.nonce || '';

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // UI States
        const kode = input.value.trim();
        if (!kode) return;

        setLoading(true);
        resultArea.classList.add('hidden');
        resultArea.innerHTML = '';

        // Prepare Data
        const formData = new FormData();
        formData.append('action', 'dw_verifikasi_tiket'); // Action hook di functions.php
        formData.append('kode_tiket', kode);
        formData.append('security', nonce);

        // Fetch API
        fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            setLoading(false);
            showResult(data);
            if (data.success) {
                input.value = ''; // Clear input on success
                addToHistory(kode, data.data.wisata, data.data.pengunjung);
            }
        })
        .catch(error => {
            setLoading(false);
            showResult({ 
                success: false, 
                data: { message: 'Terjadi kesalahan jaringan. Coba lagi.' } 
            });
            console.error('Error:', error);
        });
    });

    function setLoading(isLoading) {
        const btn = document.querySelector('#btn-verifikasi');
        if (isLoading) {
            btn.disabled = true;
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            btn.classList.add('opacity-75', 'cursor-not-allowed');
        } else {
            btn.disabled = false;
            btnText.classList.remove('hidden');
            btnLoading.classList.add('hidden');
            btn.classList.remove('opacity-75', 'cursor-not-allowed');
        }
    }

    function showResult(response) {
        resultArea.classList.remove('hidden');
        
        let html = '';
        if (response.success) {
            // SUCCESS UI
            html = `
                <div class="rounded-md bg-green-50 p-4 border border-green-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-green-800">Verifikasi Berhasil!</h3>
                            <div class="mt-2 text-sm text-green-700">
                                <p class="font-bold text-lg">${response.data.wisata}</p>
                                <p>Pengunjung: ${response.data.pengunjung}</p>
                                <p>Tanggal: ${response.data.tanggal}</p>
                                <p class="mt-2 text-xs uppercase tracking-wide bg-green-200 inline-block px-2 py-1 rounded">Tiket Valid</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // ERROR UI
            html = `
                <div class="rounded-md bg-red-50 p-4 border border-red-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Verifikasi Gagal</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>${response.data.message}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
        resultArea.innerHTML = html;
    }

    function addToHistory(kode, wisata, pengunjung) {
        const item = `
            <li class="px-4 py-4 sm:px-6 hover:bg-gray-50 transition">
                <div class="flex items-center justify-between">
                    <div class="text-sm font-medium text-green-600 truncate">
                        ${kode}
                    </div>
                    <div class="ml-2 flex-shrink-0 flex">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            Verified
                        </span>
                    </div>
                </div>
                <div class="mt-2 sm:flex sm:justify-between">
                    <div class="sm:flex">
                        <p class="flex items-center text-sm text-gray-500">
                            ${wisata}
                        </p>
                    </div>
                    <div class="mt-2 flex items-center text-sm text-gray-500 sm:mt-0">
                        <p>${pengunjung}</p>
                    </div>
                </div>
            </li>
        `;
        
        // Hapus pesan "belum ada tiket" jika ada
        if(historyList.querySelector('.italic')) {
            historyList.innerHTML = '';
        }
        
        historyList.insertAdjacentHTML('afterbegin', item);
    }
});