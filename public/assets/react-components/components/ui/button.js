/**
 * WordPress-compatible Button Component
 * Works with both React and plain JavaScript
 */

class BSRButton {
  constructor(element, options = {}) {
    this.element = element;
    this.options = {
      variant: 'primary',
      size: 'default',
      disabled: false,
      onClick: null,
      ...options
    };
    
    this.init();
  }

  init() {
    this.setupClasses();
    this.setupEventListeners();
  }

  setupClasses() {
    const baseClass = 'bsr-button';
    const variantClass = `bsr-button--${this.options.variant}`;
    const sizeClass = this.options.size !== 'default' ? `bsr-button--${this.options.size}` : '';
    
    this.element.className = `${baseClass} ${variantClass} ${sizeClass}`.trim();
    
    if (this.options.disabled) {
      this.element.disabled = true;
    }
  }

  setupEventListeners() {
    if (this.options.onClick && typeof this.options.onClick === 'function') {
      this.element.addEventListener('click', this.options.onClick);
    }
  }

  // Static method to create buttons programmatically
  static create(text, options = {}) {
    const button = document.createElement('button');
    button.textContent = text;
    return new BSRButton(button, options);
  }

  // Method to update button state
  update(newOptions) {
    this.options = { ...this.options, ...newOptions };
    this.setupClasses();
  }

  // Method to destroy the component
  destroy() {
    if (this.options.onClick) {
      this.element.removeEventListener('click', this.options.onClick);
    }
  }
}

// Auto-initialize buttons with data attributes
document.addEventListener('DOMContentLoaded', () => {
  const buttons = document.querySelectorAll('[data-bsr-button]');
  buttons.forEach(button => {
    new BSRButton(button, {
      variant: button.dataset.variant || 'primary',
      size: button.dataset.size || 'default',
      disabled: button.dataset.disabled === 'true'
    });
  });
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = BSRButton;
} else if (typeof window !== 'undefined') {
  window.BSRButton = BSRButton;
}
