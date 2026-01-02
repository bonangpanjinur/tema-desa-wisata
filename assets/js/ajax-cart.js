jQuery(document).ready(function($) {
    
    // --- 1. CONFIGURATION ---
    // Menggabungkan konfigurasi dari dw_global (WP localize) atau dwCartConfig (Inline Script)
    var config = (typeof dw_global !== 'undefined') ? dw_global : ((typeof dwCartConfig !== 'undefined') ? dwCartConfig : {});
    
    // Normalisasi variable (ajax_url vs ajaxUrl) agar support kedua sumber config
    var ajaxUrl = config.ajax_url || config.ajaxUrl;
    var nonce = config.nonce || config.security; // Support 'nonce' atau 'security' key

    if (!ajaxUrl) {
        console.warn('DW Core: AJAX URL config not found. Pastikan wp_localize_script atau inline script dimuat.');
    }

    // --- 2. GLOBAL TOAST NOTIFICATION ---
    // Fungsi notifikasi melayang yang konsisten untuk semua aksi cart
    function showGlobalToast(message, type = 'success') {
        // Cek apakah elemen toast sudah ada, jika tidak buat baru
        let $toast = $('#cart-toast'); // ID dari page-cart.php
        
        if ($toast.length === 0) {
            $toast = $('#global-toast'); // ID alternatif
            if ($toast.length === 0) {
                 $('body').append(`
                    <div id="global-toast" class="fixed top-5 right-5 z-[9999] transform transition-all duration-300 translate-y-[-150%] opacity-0">
                        <div class="bg-gray-800 text-white px-6 py-4 rounded-xl shadow-2xl flex items-center gap-4 border border-gray-700">
                            <i class="toast-icon fas fa-check-circle text-2xl text-green-400"></i>
                            <div>
                                <h4 class="font-bold text-sm text-gray-200 toast-title">Notifikasi</h4>
                                <p class="toast-message text-sm text-gray-400"></p>
                            </div>
                        </div>
                    </div>
                `);
                $toast = $('#global-toast');
            }
        }

        const $icon = $toast.find('.toast-icon, #toast-icon');
        const $msg = $toast.find('.toast-message, #toast-message');
        const $title = $toast.find('.toast-title');

        $msg.text(message);
        
        // Reset state animasi
        $toast.removeClass('translate-y-[-150%] opacity-0');
        
        // Atur Icon & Warna berdasarkan tipe
        if (type === 'error') {
            if($title.length) $title.text('Gagal');
            $icon.attr('class', 'toast-icon fas fa-times-circle text-2xl text-red-500');
        } else {
            if($title.length) $title.text('Berhasil');
            $icon.attr('class', 'toast-icon fas fa-check-circle text-2xl text-green-400');
        }

        // Sembunyikan otomatis setelah 3 detik
        setTimeout(() => {
            $toast.addClass('translate-y-[-150%] opacity-0');
        }, 3000);
    }

    // ============================================================
    // BAGIAN A: ADD TO CART (Halaman Single Produk)
    // ============================================================
    
    $('#form-add-to-cart').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var originalText = $button.html();

        // Ambil data input
        var qty = $form.find('input[name="quantity"]').val();
        var pid = $form.find('input[name="product_id"]').val();
        var vid = $form.find('input[name="variation_id"]').val() || 0; 
        var type = $form.find('input[name="type"]').val() || 'produk';

        // Validasi sederhana
        if(qty < 1) {
            showGlobalToast("Jumlah minimal 1", 'error');
            return;
        }

        // UI Loading
        $button.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin"></i> Memproses...');

        // AJAX Request
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'dw_add_to_cart', 
                security: nonce, 
                product_id: pid,
                variation_id: vid,
                quantity: qty,
                type: type
            },
            success: function(response) {
                if (response.success) {
                    showGlobalToast(response.data.message || 'Produk masuk keranjang!', 'success');
                    
                    // Update counter cart di header jika ada
                    if($('.dw-cart-count').length) {
                        var $count = $('.dw-cart-count');
                        $count.text(response.data.cart_count);
                        // Efek bounce kecil
                        $count.parent().addClass('animate-bounce');
                        setTimeout(() => $count.parent().removeClass('animate-bounce'), 1000);
                    }
                } else {
                    showGlobalToast(response.data.message || 'Gagal menambahkan', 'error');
                }
            },
            error: function() {
                showGlobalToast('Terjadi kesalahan koneksi server.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });

    // ============================================================
    // BAGIAN B: ADVANCED CART LOGIC (Halaman Keranjang)
    // ============================================================
    
    // Cek apakah kita ada di halaman cart (dengan mencari form cart)
    const cartForm = document.getElementById('cart-form');
    
    if (cartForm) {
        
        // Cache Elements
        const checkAll = document.getElementById('check-all');
        const storeChecks = document.querySelectorAll('.check-store');
        const itemChecks = document.querySelectorAll('.check-item');
        const qtyButtons = document.querySelectorAll('.btn-qty');
        
        // Summary Elements (Desktop & Mobile)
        const summaryTotalItem = document.getElementById('summary-total-item');
        const summaryTotalWeight = document.getElementById('summary-total-weight');
        const summaryGrandTotal = document.getElementById('summary-grand-total');
        const mobileGrandTotal = document.getElementById('mobile-grand-total');
        const btnCheckoutDesktop = document.getElementById('btn-checkout-desktop');
        const btnCheckoutMobile = document.getElementById('btn-checkout-mobile');
        const btnCountDesktop = document.getElementById('btn-count-desktop');
        const btnCountMobile = document.getElementById('btn-count-mobile');
        
        let debounceTimer;

        // Formatter Rupiah
        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(number);
        }

        // --- 1. LOGIKA KALKULASI REAL-TIME ---
        function recalculateTotal() {
            let totalItem = 0;
            let totalPrice = 0;
            let totalWeight = 0;

            // Loop semua item checkbox
            itemChecks.forEach(item => {
                // Hanya hitung yang dicentang dan tidak disabled (stok habis)
                if (item.checked && !item.disabled) {
                    const qty = parseInt(item.dataset.qty);
                    const price = parseFloat(item.dataset.price);
                    const weight = parseFloat(item.dataset.weight) || 0;

                    totalItem += qty;
                    totalPrice += (qty * price);
                    totalWeight += (qty * weight);
                }
            });

            // Update UI Text Ringkasan
            if(summaryTotalItem) summaryTotalItem.innerText = totalItem + ' pcs';
            if(summaryTotalWeight) summaryTotalWeight.innerText = (totalWeight / 1000).toFixed(2) + ' kg';
            if(summaryGrandTotal) summaryGrandTotal.innerText = formatRupiah(totalPrice);
            if(mobileGrandTotal) mobileGrandTotal.innerText = formatRupiah(totalPrice);
            
            // Update Text Tombol
            if(btnCountDesktop) btnCountDesktop.innerText = totalItem;
            if(btnCountMobile) btnCountMobile.innerText = totalItem;

            // Enable/Disable Tombol Checkout
            const isDisabled = totalItem === 0;
            if(btnCheckoutDesktop) btnCheckoutDesktop.disabled = isDisabled;
            if(btnCheckoutMobile) btnCheckoutMobile.disabled = isDisabled;
        }

        // --- 2. CHECKBOX EVENT LISTENERS ---
        
        // A. Toggle Select All
        if(checkAll) {
            checkAll.addEventListener('change', function() {
                const isChecked = this.checked;
                // Centang semua toko
                storeChecks.forEach(el => el.checked = isChecked);
                // Centang semua item aktif
                itemChecks.forEach(el => {
                    if(!el.disabled) el.checked = isChecked;
                });
                recalculateTotal();
            });
        }

        // B. Toggle Select Store (Toko)
        storeChecks.forEach(storeCheck => {
            storeCheck.addEventListener('change', function() {
                const targetClass = this.dataset.target; // misal: store-5
                const isChecked = this.checked;
                
                // Cari item spesifik milik toko ini
                const itemsInStore = document.querySelectorAll(`.${targetClass}-item .check-item`);
                itemsInStore.forEach(item => {
                    if(!item.disabled) item.checked = isChecked;
                });
                
                checkAllStatus(); // Cek status 'Select All' global
                recalculateTotal();
            });
        });

        // C. Toggle Single Item
        itemChecks.forEach(item => {
            item.addEventListener('change', function() {
                const storeId = this.dataset.store;
                checkStoreStatus(storeId); // Cek status checkbox toko induk
                checkAllStatus(); // Cek status global
                recalculateTotal();
            });
        });

        // Helper: Update status checkbox 'Select All'
        function checkAllStatus() {
            if(!checkAll) return;
            const totalItems = Array.from(itemChecks).filter(i => !i.disabled);
            const checkedItems = Array.from(itemChecks).filter(i => i.checked && !i.disabled);
            // Jika semua item aktif tercentang, maka checkAll true
            checkAll.checked = (totalItems.length > 0 && totalItems.length === checkedItems.length);
        }

        // Helper: Update status checkbox Toko
        function checkStoreStatus(storeId) {
            const storeCheck = document.querySelector(`.check-store[data-target="store-${storeId}"]`);
            if(!storeCheck) return;
            
            const itemsInStore = document.querySelectorAll(`.store-${storeId}-item .check-item`);
            const totalItems = Array.from(itemsInStore).filter(i => !i.disabled);
            const checkedItems = Array.from(itemsInStore).filter(i => i.checked && !i.disabled);
            
            storeCheck.checked = (totalItems.length > 0 && totalItems.length === checkedItems.length);
        }

        // --- 3. QUANTITY UPDATE LOGIC (+/- Buttons) ---
        qtyButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.dataset.action; // 'increase' atau 'decrease'
                const cartId = this.dataset.id;
                
                // Cari elemen input terkait tombol ini
                const input = this.parentElement.querySelector('.input-qty');
                // Cari checkbox terkait item ini (untuk update dataset)
                // Logic: Tombol > Wrapper > Div Item > Checkbox
                // Kita gunakan closest class toko untuk mencari item spesifik agar aman
                const storeGroup = this.closest('.cart-store-group');
                const checkbox = this.closest(`div[class*="store-"]`).querySelector('.check-item');
                
                let currentQty = parseInt(input.value);
                const maxStock = parseInt(input.dataset.stock);

                // Logic Tambah/Kurang
                if (action === 'increase') {
                    if (currentQty >= maxStock) {
                        showGlobalToast('Stok maksimal tercapai', 'error');
                        return;
                    }
                    currentQty++;
                } else {
                    if (currentQty <= 1) return;
                    currentQty--;
                }

                // A. Optimistic UI (Update Tampilan Dulu)
                input.value = currentQty;
                checkbox.dataset.qty = currentQty; // Update data di checkbox agar kalkulasi harga benar
                recalculateTotal(); // Hitung ulang total harga langsung

                // B. AJAX Debounce (Kirim ke Server Background)
                // Tunggu 500ms sebelum kirim request, cegah spam request
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    updateServerQty(cartId, currentQty);
                }, 500);
            });
        });

        // Fungsi Update ke Server
        function updateServerQty(cartId, newQty) {
            const formData = new FormData();
            formData.append('action', 'dw_update_cart_qty');
            formData.append('nonce', nonce);
            formData.append('cart_id', cartId);
            formData.append('qty', newQty);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showGlobalToast(data.data.message || 'Gagal update stok', 'error');
                    // Opsional: Revert value jika gagal
                }
            })
            .catch(err => console.error(err));
        }

        // --- 4. DELETE ITEM LOGIC ---
        // Dipasang di window agar bisa dipanggil via onclick="" di HTML PHP
        window.deleteCartItem = function(cartId) {
            if(!confirm("Yakin ingin menghapus produk ini?")) return;

            const formData = new FormData();
            formData.append('action', 'dw_remove_cart_item');
            formData.append('nonce', nonce);
            formData.append('cart_id', cartId);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Reload agar item hilang bersih dari DOM
                } else {
                    showGlobalToast('Gagal menghapus item', 'error');
                }
            });
        };

        // Bulk Delete Placeholder
        window.bulkDelete = function() {
             const checkedItems = Array.from(itemChecks).filter(i => i.checked).map(i => i.value);
             
             if(checkedItems.length === 0) {
                 showGlobalToast('Pilih produk yang ingin dihapus', 'error');
                 return;
             }
             
             if(!confirm(`Hapus ${checkedItems.length} produk terpilih?`)) return;
             
             // TODO: Implementasi endpoint bulk delete di PHP
             alert("Fitur bulk delete akan segera hadir. Silakan hapus per item.");
        };
    }

});