/**
 * Color Selector Functionality
 * Handles color selection and image switching
 */

class ColorSelector {
    constructor(container) {
        this.container = container;
        this.productId = container.dataset.productId;
        this.colorOptions = container.querySelectorAll('.color-option');
        this.selectedColorName = container.querySelector('#selected-color-name');
        this.selectedColorId = container.querySelector('#selected-color-id');
        this.mainImage = document.querySelector('.main-product-image');
        
        this.init();
    }
    
    init() {
        // Add click events to color options
        this.colorOptions.forEach(option => {
            option.addEventListener('click', () => {
                this.selectColor(option);
            });
        });
        
        // Initialize first color if none active
        if (!this.container.querySelector('.color-option.active') && this.colorOptions.length > 0) {
            this.selectColor(this.colorOptions[0]);
        }
    }
    
    selectColor(selectedOption) {
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
        
        // Change product image if available
        const colorImage = selectedOption.dataset.colorImage;
        this.changeProductImage(colorImage);
        
        // Update any add to cart buttons
        this.updateAddToCartData(colorId, colorName);
        
        // Trigger custom event
        this.triggerColorChangeEvent(colorId, colorName);
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
            // Fallback to default product image - FIXED PATH
            const fallback = this.mainImage.dataset.fallback || 'http://localhost/JDSystem/uploads/sample1.jpg';
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
        
        // Update forms that might have color selection
        const colorInputs = document.querySelectorAll('input[name="color_id"], input[name="selected_color"]');
        colorInputs.forEach(input => {
            if (input) input.value = colorId;
        });
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
}

// Initialize all color selectors on page load
document.addEventListener('DOMContentLoaded', () => {
    const colorSelectors = document.querySelectorAll('.color-selector');
    
    colorSelectors.forEach(container => {
        new ColorSelector(container);
    });
    
    console.log(`🎨 Initialized ${colorSelectors.length} color selector(s)`);
});