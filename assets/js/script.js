// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            navLinks.classList.toggle('active');
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(e) {
        if (navLinks && mobileMenuBtn && !mobileMenuBtn.contains(e.target) && !navLinks.contains(e.target)) {
            navLinks.classList.remove('active');
        }
    });
    
    // Add active class to current navigation link
    const currentLocation = window.location.pathname;
    const navLinksItems = document.querySelectorAll('.nav-link');
    
    navLinksItems.forEach(link => {
        const linkPath = link.getAttribute('href');
        if (linkPath && currentLocation.includes(linkPath) && linkPath !== '/electrastore/index.php') {
            link.classList.add('active');
        } else if (linkPath === '/electrastore/index.php' && currentLocation === '/electrastore/index.php') {
            link.classList.add('active');
        }
    });
});

// Add to cart with AJAX
function addToCart(productId) {
    const addButton = event?.target;
    if (addButton) {
        addButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
        addButton.disabled = true;
    }
    
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
                cartBadge.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    cartBadge.style.transform = 'scale(1)';
                }, 300);
            } else if (data.cart_count > 0) {
                const cartLink = document.querySelector('.cart-link');
                if (cartLink) {
                    const badge = document.createElement('span');
                    badge.className = 'cart-badge';
                    badge.textContent = data.cart_count;
                    cartLink.appendChild(badge);
                }
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
    })
    .finally(() => {
        if (addButton) {
            addButton.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
            addButton.disabled = false;
        }
    });
}

// Update cart quantity
function updateQuantity(cartItemId, quantity) {
    if (quantity < 1) {
        removeItem(cartItemId);
        return;
    }
    
    const quantityInput = event?.target;
    if (quantityInput) {
        quantityInput.disabled = true;
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
            if (quantityInput) {
                quantityInput.value = quantity;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Error updating cart', 'error');
    })
    .finally(() => {
        if (quantityInput) {
            quantityInput.disabled = false;
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
                showMessage('Item removed successfully!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showMessage(data.message || 'Error removing item', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error removing item', 'error');
        });
    }
}

// Show message with animation
function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = type === 'success' ? 'success-message' : 'error-message';
    messageDiv.style.animation = 'slideInDown 0.3s ease';
    messageDiv.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        <span>${message}</span>
    `;
    
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(messageDiv, container.firstChild);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            messageDiv.style.animation = 'slideOutUp 0.3s ease';
            setTimeout(() => {
                messageDiv.remove();
            }, 300);
        }, 3000);
    }
}

// Search functionality
function searchProducts() {
    const searchTerm = document.getElementById('search-input')?.value;
    if (searchTerm && searchTerm.trim()) {
        window.location.href = '/electrastore/products/search.php?q=' + encodeURIComponent(searchTerm);
    }
    return false;
}

// Search with enter key
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchProducts();
            }
        });
    }
});

// Confirm delete with enhanced UI
function confirmDelete() {
    return confirm('Are you sure you want to delete this item? This action cannot be undone.');
}

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.style.borderColor = '#f94144';
            isValid = false;
            
            // Show error message
            const errorMsg = document.createElement('small');
            errorMsg.className = 'error-text';
            errorMsg.style.color = '#f94144';
            errorMsg.textContent = 'This field is required';
            
            const parent = input.parentElement;
            const existingError = parent.querySelector('.error-text');
            if (!existingError) {
                parent.appendChild(errorMsg);
            }
            
            // Remove error on input
            input.addEventListener('input', function() {
                input.style.borderColor = '#e2e8f0';
                const error = parent.querySelector('.error-text');
                if (error) error.remove();
            });
        }
    });
    
    return isValid;
}

// Password strength checker
function checkPasswordStrength(password) {
    const strengthBar = document.getElementById('password-strength');
    if (!strengthBar) return;
    
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.length >= 10) strength++;
    if (password.match(/[a-z]+/)) strength++;
    if (password.match(/[A-Z]+/)) strength++;
    if (password.match(/[0-9]+/)) strength++;
    if (password.match(/[$@#&!]+/)) strength++;
    
    const percentage = (strength / 6) * 100;
    strengthBar.style.width = percentage + '%';
    
    if (percentage < 30) {
        strengthBar.style.background = '#f94144';
        strengthBar.style.backgroundColor = '#f94144';
    } else if (percentage < 60) {
        strengthBar.style.background = '#f9c74f';
        strengthBar.style.backgroundColor = '#f9c74f';
    } else {
        strengthBar.style.background = '#06ffa5';
        strengthBar.style.backgroundColor = '#06ffa5';
    }
}

// Image lazy loading
document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Cart quantity increment/decrement
function incrementQuantity(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        const newValue = parseInt(input.value) + 1;
        input.value = newValue;
        updateQuantity(input.dataset.cartItemId, newValue);
    }
}

function decrementQuantity(inputId) {
    const input = document.getElementById(inputId);
    if (input) {
        const newValue = parseInt(input.value) - 1;
        if (newValue >= 1) {
            input.value = newValue;
            updateQuantity(input.dataset.cartItemId, newValue);
        }
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutUp {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(-100%);
            opacity: 0;
        }
    }
    
    .cart-badge {
        transition: transform 0.3s ease;
    }
    
    .nav-link.active {
        background: rgba(67, 97, 238, 0.1);
        color: #4361ee;
    }
    
    .error-text {
        font-size: 0.75rem;
        margin-top: 0.25rem;
        display: block;
    }
    
    #password-strength {
        height: 4px;
        border-radius: 2px;
        margin-top: 0.5rem;
        transition: all 0.3s ease;
        background: #e2e8f0;
    }
`;

document.head.appendChild(style);