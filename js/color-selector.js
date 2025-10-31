/**
 * Color Selector Functionality - FIXED VERSION
 * Handles color selection and image switching with proper Buy Now button updates
 */

class ColorSelector {
    constructor(container) {
        this.container = container;
        this.productId = container.dataset.productId;
        this.colorOptions = container.querySelectorAll('.color-option');
        this.selectedColorName = container.querySelector('#selected-color-name');
        this.selectedColorId = container.querySelector('#selected-color-id');
        this.mainImage = document.querySelector('.main-product-image');
        this.buyNowBtn = document.getElementById('buy-now-btn');
        this.addToCartBtn = document.querySelector('.add-to-cart');
        
        this.init();
    }
    
    init() {
        console.log(`🎨 Initializing color selector for product: ${this.productId}`);
        
        // Add click events to color options
        this.colorOptions.forEach(option => {
            option.addEventListener('click', () => {
                this.selectColor(option);
            });
        });
        
        // Restore saved color from session storage
        this.restoreSavedColor();
        
        // Initialize first color if none active
        if (!this.container.querySelector('.color-option.active') && this.colorOptions.length > 0) {
            this.selectColor(this.colorOptions[0]);
        }
        
        console.log('✅ Color selector initialized');
    }
    
    restoreSavedColor() {
        const savedColorId = sessionStorage.getItem(`selected_color_${this.productId}`);
        if (savedColorId) {
            const savedOption = this.container.querySelector(`.color-option[data-color-id="${savedColorId}"]`);
            if (savedOption) {
                console.log(`🎯 Restoring saved color: ${savedOption.dataset.colorName}`);
                this.selectColor(savedOption, true); // true = silent update (no event)
            }
        }
    }
    
    selectColor(selectedOption, silent = false) {
        console.log(`🎨 Color selected: ${selectedOption.dataset.colorName} (ID: ${selectedOption.dataset.colorId})`);
        
        // Remove active class from all options
        this.colorOptions.forEach(option => {
            option.classList.remove('active');
        });
        
        // Add active class to selected option
        selectedOption.classList.add('active');
        
        // Update selected color name display
        const colorName = selectedOption.dataset.colorName;
        this.selectedColorName.textContent = colorName;
        
        // Update hidden input value
        const colorId = selectedOption.dataset.colorId;
        if (this.selectedColorId) {
            this.selectedColorId.value = colorId;
        }
        
        // ✅ CRITICAL FIX: Update Buy Now button with the ACTUAL selected color
        this.updateBuyNowButton(colorId);
        
        // Change product image if available
        const colorImage = selectedOption.dataset.colorImage;
        this.changeProductImage(colorImage);
        
        // Update any add to cart buttons
        this.updateAddToCartData(colorId, colorName);
        
        // Save to session storage
        sessionStorage.setItem(`selected_color_${this.productId}`, colorId);
        
        // Update URL without page reload
        this.updateUrl(colorId);
        
        if (!silent) {
            // Trigger custom event
            this.triggerColorChangeEvent(colorId, colorName);
        }
    }
    
    // ✅ NEW METHOD: Specifically update Buy Now button
    updateBuyNowButton(colorId) {
        if (this.buyNowBtn) {
            this.buyNowBtn.dataset.colorId = colorId;
            console.log(`✅ Buy Now button updated with color ID: ${colorId}`);
        } else {
            console.warn('⚠️ Buy Now button not found');
        }
    }
    
    changeProductImage(imageData) {
        if (!this.mainImage || !imageData) return;
        
        // Add loading effect
        this.mainImage.classList.add('color-loading');
        
        // Create new image for smooth transition
        const newImage = new Image();
        newImage.onload = () => {
            // Smooth transition
            this.mainImage.style.opacity = '0.7';
            setTimeout(() => {
                this.mainImage.src = imageData;
                this.mainImage.style.opacity = '1';
                this.mainImage.classList.remove('color-loading');
            }, 200);
        };
        
        newImage.onerror = () => {
            console.error('Failed to load color image');
            this.mainImage.classList.remove('color-loading');
            // Fallback to default product image
            const fallback = this.mainImage.dataset.fallback || '<?= SITE_URL ?>uploads/sample1.jpg';
            this.mainImage.src = fallback;
        };
        
        newImage.src = imageData;
    }
    
    updateAddToCartData(colorId, colorName) {
        // Update all add to cart buttons on the page
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn, .buy-now-btn, .add-to-cart');
        addToCartButtons.forEach(button => {
            button.dataset.colorId = colorId;
            button.dataset.colorName = colorName;
        });
        
        // Update Add to Cart button specifically
        if (this.addToCartBtn) {
            this.addToCartBtn.dataset.id = colorId;
        }
        
        // Update forms that might have color selection
        const colorInputs = document.querySelectorAll('input[name="color_id"], input[name="selected_color"]');
        colorInputs.forEach(input => {
            if (input) input.value = colorId;
        });
    }
    
    updateUrl(colorId) {
        try {
            const newUrl = `${window.location.protocol}//${window.location.host}${window.location.pathname}?id=${colorId}`;
            window.history.replaceState({}, '', newUrl);
            console.log(`🔗 URL updated to: ${newUrl}`);
        } catch (error) {
            console.warn('Could not update URL:', error);
        }
    }
    
    triggerColorChangeEvent(colorId, colorName) {
        // Dispatch custom event for other scripts to listen to
        const event = new CustomEvent('colorChanged', {
            detail: {
                productId: this.productId,
                colorId: colorId,
                colorName: colorName,
                container: this.container
            }
        });
        document.dispatchEvent(event);
    }
    
    // Debug method
    debug() {
        console.log('🎯 COLOR SELECTION DEBUG:');
        console.log('   - Product ID:', this.productId);
        console.log('   - Hidden field:', this.selectedColorId?.value);
        console.log('   - Buy Now button:', this.buyNowBtn?.dataset.colorId);
        console.log('   - Active color:', this.container.querySelector('.color-option.active')?.dataset.colorName);
    }
}

// Initialize all color selectors on page load
document.addEventListener('DOMContentLoaded', () => {
    const colorSelectors = document.querySelectorAll('.color-selector');
    
    colorSelectors.forEach(container => {
        new ColorSelector(container);
    });
    
    console.log(`🎨 Initialized ${colorSelectors.length} color selector(s)`);
    
    // Add global debug function
    window.debugColorSelection = function() {
        const selectors = document.querySelectorAll('.color-selector');
        selectors.forEach(container => {
            const selector = new ColorSelector(container);
            selector.debug();
        });
    };
});

// Session storage for color persistence
document.addEventListener('DOMContentLoaded', () => {
    // This ensures colors are remembered across page navigation
    const colorOptions = document.querySelectorAll('.color-option');
    colorOptions.forEach(option => {
        option.addEventListener('click', () => {
            const productId = option.closest('.color-selector')?.dataset.productId;
            const colorId = option.dataset.colorId;
            if (productId && colorId) {
                sessionStorage.setItem(`selected_color_${productId}`, colorId);
                console.log(`💾 Saved color ${colorId} for product ${productId}`);
            }
        });
    });
});