// Add to cart with AJAX
function addToCart(productId) {
    fetch('/electrastore/cart/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId + '&quantity=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart badge
            const cartBadge = document.querySelector('.cart-badge');
            if (cartBadge) {
                cartBadge.textContent = data.cart_count;
            } else if (data.cart_count > 0) {
                const cartLink = document.querySelector('.cart-link');
                const badge = document.createElement('span');
                badge.className = 'cart-badge';
                badge.textContent = data.cart_count;
                cartLink.appendChild(badge);
            }
            
            // Show success message
            showMessage('Product added to cart successfully!', 'success');
        } else {
            showMessage(data.message || 'Error adding to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error adding to cart', 'error');
    });
}

// Update cart quantity
function updateQuantity(cartItemId, quantity) {
    if (quantity < 1) {
        removeItem(cartItemId);
        return;
    }
    
    fetch('/electrastore/cart/update.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'cart_item_id=' + cartItemId + '&quantity=' + quantity
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            showMessage(data.message, 'error');
        }
    });
}

// Remove item from cart
function removeItem(cartItemId) {
    if (confirm('Are you sure you want to remove this item?')) {
        fetch('/electrastore/cart/remove.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'cart_item_id=' + cartItemId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

// Show message
function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
    messageDiv.textContent = message;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(messageDiv, container.firstChild);
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }
}

// Search functionality
function searchProducts() {
    const searchTerm = document.getElementById('search-input').value;
    if (searchTerm.trim()) {
        window.location.href = '/electrastore/products/search.php?q=' + encodeURIComponent(searchTerm);
    }
    return false;
}

// Confirm delete
function confirmDelete() {
    return confirm('Are you sure you want to delete this item? This action cannot be undone.');
}