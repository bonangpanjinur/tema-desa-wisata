jQuery(document).ready(function($) {
    
    // --- 1. CONFIGURATION ---
    var config = (typeof dw_global !== 'undefined') ? dw_global : ((typeof dwCartConfig !== 'undefined') ? dwCartConfig : {});
    
    // Fallback jika localization belum sempurna
    var ajaxUrl = config.ajax_url || '/wp-admin/admin-ajax.php';
    var nonce = config.nonce || ''; 

    // --- 2. GLOBAL TOAST NOTIFICATION ---
    function showGlobalToast(message, type = 'success') {
        let $toast = $('#global-toast');
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

        // Animate
        $toast.removeClass('translate-y-[-150%] opacity-0').addClass('translate-y-0 opacity-100');
        setTimeout(() => {
            $toast.removeClass('translate-y-0 opacity-100').addClass('translate-y-[-150%] opacity-0');
        }, 3000);
    }

    // ============================================================
    // BAGIAN A: ADD TO CART & BUY NOW (Single Product)
    // ============================================================
    
    function handleCartAction(e, isBuyNow = false) {
        e.preventDefault(); // Mencegah reload halaman/submit standar

        var $form = $('#dw-add-to-cart-form');
        var $btn  = isBuyNow ? $('#btn-buy-now') : $('#btn-add-cart');
        
        // Cek Login
        if (!$('body').hasClass('logged-in')) {
            showGlobalToast('Silakan login terlebih dahulu', 'error');
            // window.location.href = '/login'; 
            return;
        }

        // Loading UI
        $btn.addClass('btn-loading').prop('disabled', true);

        // Siapkan Data
        var formData = new FormData($form[0]);
        if(!formData.has('action')) formData.append('action', 'dw_add_to_cart');
        
        // AJAX Request
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Parsing response (kadang WP mengembalikan string 0/1)
                var res = response;
                if (typeof response !== 'object') {
                    try { res = JSON.parse(response); } catch(e) {}
                }

                if (res.success) {
                    if (isBuyNow) {
                        // Redirect ke checkout
                        window.location.href = '/checkout'; 
                    } else {
                        showGlobalToast(res.data.message || 'Berhasil masuk keranjang!', 'success');
                        
                        // Update Badge Header
                        if (res.data && res.data.cart_count) {
                            $('.cart-count, .dw-cart-count').text(res.data.cart_count).removeClass('hidden');
                        }
                    }
                } else {
                    showGlobalToast(res.data.message || 'Gagal menambahkan produk.', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error(error);
                showGlobalToast('Terjadi kesalahan koneksi.', 'error');
            },
            complete: function() {
                $btn.removeClass('btn-loading').prop('disabled', false);
            }
        });
    }

    // Listener 1: Form Submit (Tombol Keranjang)
    $(document).on('submit', '#dw-add-to-cart-form', function(e) {
        handleCartAction(e, false);
    });

    // Listener 2: Klik Tombol Beli Langsung
    $(document).on('click', '#btn-buy-now', function(e) {
        handleCartAction(e, true);
    });

    // ============================================================
    // BAGIAN B: HALAMAN KERANJANG (Logic Lama Anda)
    // ============================================================
    
    const cartForm = document.getElementById('cart-form');
    if (cartForm) {
        const checkAll = document.getElementById('check-all');
        const storeChecks = document.querySelectorAll('.check-store');
        const itemChecks = document.querySelectorAll('.check-item');
        const qtyButtons = document.querySelectorAll('.btn-qty');
        
        // Elemen Ringkasan
        const summaryTotalItem = document.getElementById('summary-total-item');
        const summaryGrandTotal = document.getElementById('summary-grand-total');
        const mobileGrandTotal = document.getElementById('mobile-grand-total');
        const btnCheckoutDesktop = document.getElementById('btn-checkout-desktop');
        const btnCheckoutMobile = document.getElementById('btn-checkout-mobile');
        
        let debounceTimer;

        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);
        }

        function recalculateTotal() {
            let totalItem = 0;
            let totalPrice = 0;

            itemChecks.forEach(item => {
                if (item.checked && !item.disabled) {
                    const qty = parseInt(item.dataset.qty);
                    const price = parseFloat(item.dataset.price);
                    totalItem += qty;
                    totalPrice += (qty * price);
                }
            });

            if(summaryTotalItem) summaryTotalItem.innerText = totalItem + ' pcs';
            if(summaryGrandTotal) summaryGrandTotal.innerText = formatRupiah(totalPrice);
            if(mobileGrandTotal) mobileGrandTotal.innerText = formatRupiah(totalPrice);
            
            const isDisabled = totalItem === 0;
            if(btnCheckoutDesktop) btnCheckoutDesktop.disabled = isDisabled;
            if(btnCheckoutMobile) btnCheckoutMobile.disabled = isDisabled;
        }

        // Event Listeners (Simplified from your code)
        if(checkAll) {
            checkAll.addEventListener('change', function() {
                storeChecks.forEach(el => el.checked = this.checked);
                itemChecks.forEach(el => { if(!el.disabled) el.checked = this.checked; });
                recalculateTotal();
            });
        }

        storeChecks.forEach(storeCheck => {
            storeCheck.addEventListener('change', function() {
                const target = this.dataset.target; 
                document.querySelectorAll(`.${target}-item .check-item`).forEach(item => {
                    if(!item.disabled) item.checked = this.checked;
                });
                recalculateTotal();
            });
        });

        itemChecks.forEach(item => {
            item.addEventListener('change', recalculateTotal);
        });

        qtyButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.dataset.action; 
                const cartId = this.dataset.id;
                const input = this.parentElement.querySelector('.input-qty');
                const checkbox = this.closest(`div[class*="store-"]`).querySelector('.check-item');
                
                let currentQty = parseInt(input.value);
                const maxStock = parseInt(input.dataset.stock);

                if (action === 'increase') {
                    if (currentQty >= maxStock) { showGlobalToast('Stok maksimal', 'error'); return; }
                    currentQty++;
                } else {
                    if (currentQty <= 1) return;
                    currentQty--;
                }

                input.value = currentQty;
                checkbox.dataset.qty = currentQty;
                recalculateTotal(); 

                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    const fd = new FormData();
                    fd.append('action', 'dw_update_cart_qty');
                    fd.append('cart_id', cartId);
                    fd.append('qty', currentQty);
                    fd.append('nonce', nonce); // Using config nonce
                    fetch(ajaxUrl, { method: 'POST', body: fd });
                }, 500);
            });
        });

        window.deleteCartItem = function(cartId) {
            if(!confirm("Hapus produk ini?")) return;
            const fd = new FormData();
            fd.append('action', 'dw_remove_cart_item');
            fd.append('cart_id', cartId);
            fd.append('nonce', nonce);
            
            fetch(ajaxUrl, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => { if(d.success) location.reload(); });
        };
    }
});