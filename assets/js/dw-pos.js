/**
 * POS (Point of Sale) Frontend Logic
 */

let cart = [];

function addToCart(product) {
    const existing = cart.find(item => item.id === product.id);
    if (existing) {
        if (existing.qty < product.stok) {
            existing.qty++;
        } else {
            alert('Stok tidak mencukupi!');
            return;
        }
    } else {
        if (product.stok > 0) {
            cart.push({ ...product, qty: 1 });
        } else {
            alert('Stok habis!');
            return;
        }
    }
    renderCart();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    renderCart();
}

function updateQty(productId, delta) {
    const item = cart.find(i => i.id === productId);
    if (item) {
        const newQty = item.qty + delta;
        if (newQty > 0 && newQty <= item.stok) {
            item.qty = newQty;
        } else if (newQty <= 0) {
            removeFromCart(productId);
            return;
        } else {
            alert('Stok tidak mencukupi!');
        }
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('pos-cart-items');
    const emptyState = document.getElementById('pos-cart-empty');
    const subtotalEl = document.getElementById('pos-subtotal');
    const totalEl = document.getElementById('pos-total');
    const btnCheckout = document.getElementById('btn-checkout');

    if (cart.length === 0) {
        container.innerHTML = '';
        container.appendChild(emptyState);
        emptyState.style.display = 'flex';
        subtotalEl.innerText = 'Rp 0';
        totalEl.innerText = 'Rp 0';
        btnCheckout.disabled = true;
        return;
    }

    emptyState.style.display = 'none';
    container.innerHTML = cart.map(item => `
        <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-xl border border-gray-100">
            <div class="flex-1">
                <h4 class="text-xs font-bold text-gray-800 line-clamp-1">${item.nama}</h4>
                <p class="text-[10px] text-gray-500">Rp ${new Intl.NumberFormat('id-ID').format(item.harga)}</p>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="updateQty(${item.id}, -1)" class="w-6 h-6 flex items-center justify-center bg-white border border-gray-200 rounded-md text-gray-500 hover:bg-gray-100"><i class="fas fa-minus text-[10px]"></i></button>
                <span class="text-xs font-bold w-4 text-center">${item.qty}</span>
                <button onclick="updateQty(${item.id}, 1)" class="w-6 h-6 flex items-center justify-center bg-white border border-gray-200 rounded-md text-gray-500 hover:bg-gray-100"><i class="fas fa-plus text-[10px]"></i></button>
            </div>
            <button onclick="removeFromCart(${item.id})" class="text-red-400 hover:text-red-600 ml-1"><i class="fas fa-trash-alt text-xs"></i></button>
        </div>
    `).join('');

    const total = cart.reduce((sum, item) => sum + (item.harga * item.qty), 0);
    const formatted = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
    subtotalEl.innerText = formatted;
    totalEl.innerText = formatted;
    btnCheckout.disabled = false;
}

function clearCart() {
    if (confirm('Kosongkan keranjang?')) {
        cart = [];
        renderCart();
    }
}

async function processCheckout() {
    const customerName = document.getElementById('pos-customer-name').value;
    const tableNo = document.getElementById('pos-table-no').value;
    const btn = document.getElementById('btn-checkout');

    if (cart.length === 0) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses...';

    const formData = new FormData();
    formData.append('action', 'dw_ajax_pos_transaction');
    formData.append('customer_name', customerName);
    formData.append('table_no', tableNo);
    formData.append('total_amount', cart.reduce((sum, item) => sum + (item.harga * item.qty), 0));
    
    cart.forEach((item, index) => {
        formData.append(`items[${index}][id]`, item.id);
        formData.append(`items[${index}][qty]`, item.qty);
        formData.append(`items[${index}][harga]`, item.harga);
    });

    try {
        const response = await fetch(ajaxurl, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            alert(result.data.message);
            cart = [];
            document.getElementById('pos-customer-name').value = '';
            document.getElementById('pos-table-no').value = '';
            renderCart();
            // Optional: Reload page or update revenue display
            location.reload(); 
        } else {
            alert('Error: ' + result.data.message);
        }
    } catch (error) {
        console.error('POS Error:', error);
        alert('Terjadi kesalahan sistem.');
    } finally {
        btn.disabled = false;
        btn.innerText = 'Bayar Sekarang';
    }
}

// Search Functionality
document.getElementById('pos-search').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.pos-product-card');
    
    cards.forEach(card => {
        const name = card.getAttribute('data-nama').toLowerCase();
        if (name.includes(query)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
});
