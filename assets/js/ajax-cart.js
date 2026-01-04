jQuery(document).ready(function($) {
    
    // --- 1. CONFIGURATION ---
    // Menggabungkan konfigurasi dari dw_global (WP localize) atau dwCartConfig (Inline Script)
    var config = (typeof dw_global !== 'undefined') ? dw_global : ((typeof dwCartConfig !== 'undefined') ? dwCartConfig : {});
    
    // Normalisasi variable (ajax_url vs ajaxUrl) agar support kedua sumber config
    var ajaxUrl = config.ajax_url || config.ajaxUrl || '/wp-admin/admin-ajax.php';
    var nonce = config.nonce || config.security || ''; // Support 'nonce' atau 'security' key

    if (!ajaxUrl) {
        console.warn('DW Core: AJAX URL config not found. Pastikan wp_localize_script atau inline script dimuat.');
    }

    // --- 2. GLOBAL TOAST NOTIFICATION ---
    function showGlobalToast(message, type = 'success') {
        let $toast = $('#global-toast');
        
        // Buat elemen jika belum ada
        if ($toast.length === 0) {
             $('body').append(`
                <div id="global-toast" class="fixed top-5 right-5 z-[9999] transform transition-all duration-300 translate-y-[-150%] opacity-0">
                    <div class="bg-white rounded-xl shadow-2xl border-l-4 p-4 flex items-center gap-3 min-w-[300px]">
                        <div id="toast-icon"></div>
                        <div class="text-sm font-bold text-gray-700" id="toast-msg"></div>
                    </div>
                </div>
            `);
            $toast = $('#global-toast');
        }

        const iconHtml = (type === 'success') 
            ? '<i class="fas fa-check-circle text-green-500 text-xl"></i>' 
            : '<i class="fas fa-exclamation-circle text-red-500 text-xl"></i>';
        
        const borderClass = (type === 'success') ? 'border-green-500' : 'border-red-500';

        $toast.find('.bg-white').removeClass('border-green-500 border-red-500').addClass(borderClass);
        $('#toast-icon').html(iconHtml);
        $('#toast-msg').text(message);

        // Animate In
        $toast.removeClass('translate-y-[-150%] opacity-0').addClass('translate-y-0 opacity-100');

        // Auto Hide
        setTimeout(() => {
            $toast.removeClass('translate-y-0 opacity-100').addClass('translate-y-[-150%] opacity-0');
        }, 3000);
    }

    // ============================================================
    // BAGIAN A: ADD TO CART & BUY NOW (Halaman Single Produk)
    // ============================================================
    
    // Fungsi reusable untuk Add to Cart / Beli Langsung
    function handleCartAction(e, isBuyNow = false) {
        e.preventDefault();

        // Ambil elemen form dan button
        // Mengutamakan ID baru (#dw-add-to-cart-form), fallback ke selector lama jika perlu
        var $form = $('#dw-add-to-cart-form');
        if ($form.length === 0) $form = $('#form-add-to-cart'); 

        var $btn  = isBuyNow ? $('#btn-buy-now') : $form.find('button[type="submit"]');
        
        // Validasi Login
        if (!$('body').hasClass('logged-in')) {
            showGlobalToast('Silakan login untuk berbelanja', 'error');
            // Opsional: Redirect ke login
            // window.location.href = '/login'; 
            return;
        }

        // Loading State
        $btn.addClass('btn-loading').prop('disabled', true);

        // Siapkan Data
        var formData = new FormData($form[0]);
        if(!formData.has('action')) formData.append('action', 'dw_add_to_cart');
        // Inject nonce jika belum ada di form tapi ada di config
        if(!formData.has('dw_cart_nonce') && nonce) formData.append('dw_cart_nonce', nonce);

        // AJAX Request
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                var res = (typeof response === 'object') ? response : JSON.parse(response);

                if (res.success) {
                    if (isBuyNow) {
                        // Redirect ke checkout jika Beli Langsung
                        window.location.href = '/checkout'; 
                    } else {
                        showGlobalToast(res.data.message || 'Berhasil masuk keranjang!', 'success');
                        
                        // Update Badge Keranjang di Header (support class lama dan baru)
                        if (res.data && res.data.cart_count) {
                            $('.cart-count, .dw-cart-count').text(res.data.cart_count).removeClass('hidden');
                            // Efek bounce
                            $('.cart-count, .dw-cart-count').parent().addClass('animate-bounce');
                            setTimeout(() => $('.cart-count, .dw-cart-count').parent().removeClass('animate-bounce'), 1000);
                        }
                    }
                } else {
                    showGlobalToast(res.data || res.data.message || 'Gagal menambahkan produk.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showGlobalToast('Terjadi kesalahan koneksi.', 'error');
            },
            complete: function() {
                $btn.removeClass('btn-loading').prop('disabled', false);
            }
        });
    }

    // Event Listener: Submit Form "Masuk Keranjang"
    $(document).on('submit', '#dw-add-to-cart-form', function(e) {
        handleCartAction(e, false);
    });

    // Event Listener: Klik Tombol "Beli Langsung"
    $(document).on('click', '#btn-buy-now', function(e) {
        handleCartAction(e, true);
    });

    // ============================================================
    // BAGIAN B: ADVANCED CART LOGIC (Halaman Keranjang)
    // ============================================================
    
    const cartForm = document.getElementById('cart-form');
    
    if (cartForm) {
        
        // Cache Elements
        const checkAll = document.getElementById('check-all');
        const storeChecks = document.querySelectorAll('.check-store');
        const itemChecks = document.querySelectorAll('.check-item');
        const qtyButtons = document.querySelectorAll('.btn-qty');
        
        // Summary Elements
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

            itemChecks.forEach(item => {
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
                storeChecks.forEach(el => el.checked = isChecked);
                itemChecks.forEach(el => {
                    if(!el.disabled) el.checked = isChecked;
                });
                recalculateTotal();
            });
        }

        // B. Toggle Select Store (Toko)
        storeChecks.forEach(storeCheck => {
            storeCheck.addEventListener('change', function() {
                const targetClass = this.dataset.target; 
                const isChecked = this.checked;
                const itemsInStore = document.querySelectorAll(`.${targetClass}-item .check-item`);
                itemsInStore.forEach(item => {
                    if(!item.disabled) item.checked = isChecked;
                });
                
                checkAllStatus(); 
                recalculateTotal();
            });
        });

        // C. Toggle Single Item
        itemChecks.forEach(item => {
            item.addEventListener('change', function() {
                const storeId = this.dataset.store;
                checkStoreStatus(storeId); 
                checkAllStatus(); 
                recalculateTotal();
            });
        });

        function checkAllStatus() {
            if(!checkAll) return;
            const totalItems = Array.from(itemChecks).filter(i => !i.disabled);
            const checkedItems = Array.from(itemChecks).filter(i => i.checked && !i.disabled);
            checkAll.checked = (totalItems.length > 0 && totalItems.length === checkedItems.length);
        }

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
                const action = this.dataset.action; 
                const cartId = this.dataset.id;
                
                const input = this.parentElement.querySelector('.input-qty');
                const storeGroup = this.closest('.cart-store-group');
                const checkbox = this.closest(`div[class*="store-"]`).querySelector('.check-item');
                
                let currentQty = parseInt(input.value);
                const maxStock = parseInt(input.dataset.stock);

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

                // Optimistic UI
                input.value = currentQty;
                checkbox.dataset.qty = currentQty;
                recalculateTotal(); 

                // AJAX Debounce
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    updateServerQty(cartId, currentQty);
                }, 500);
            });
        });

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
                }
            })
            .catch(err => console.error(err));
        }

        // --- 4. DELETE ITEM LOGIC ---
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
                    location.reload(); 
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
             
             alert("Fitur bulk delete akan segera hadir. Silakan hapus per item.");
        };
    }

});