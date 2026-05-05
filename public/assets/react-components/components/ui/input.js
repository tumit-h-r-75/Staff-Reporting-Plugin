/**
 * WordPress-compatible Input Component
 * Works with both React and plain JavaScript
 */

class BSRInput {
  constructor(element, options = {}) {
    this.element = element;
    this.options = {
      type: 'text',
      size: 'default',
      error: false,
      placeholder: '',
      value: '',
      onChange: null,
      ...options
    };
    
    this.init();
  }

  init() {
    this.setupElement();
    this.setupClasses();
    this.setupEventListeners();
  }

  setupElement() {
    if (this.element.tagName !== 'INPUT' && this.element.tagName !== 'TEXTAREA') {
      // Convert to input if it's not already
      const isTextarea = this.options.type === 'textarea';
      const newElement = document.createElement(isTextarea ? 'textarea' : 'input');
      
      // Copy existing attributes
      for (let attr of this.element.attributes) {
        newElement.setAttribute(attr.name, attr.value);
      }
      
      // Replace element
      this.element.parentNode.replaceChild(newElement, this.element);
      this.element = newElement;
    }
    
    // Set basic attributes
    this.element.type = this.options.type;
    this.element.placeholder = this.options.placeholder;
    if (this.options.value) {
      this.element.value = this.options.value;
    }
  }

  setupClasses() {
    const baseClass = this.options.type === 'textarea' ? 'bsr-textarea' : 'bsr-input';
    const sizeClass = this.options.size !== 'default' ? `${baseClass}--${this.options.size}` : '';
    const errorClass = this.options.error ? `${baseClass}--error` : '';
    
    this.element.className = `${baseClass} ${sizeClass} ${errorClass}`.trim();
  }

  setupEventListeners() {
    if (this.options.onChange && typeof this.options.onChange === 'function') {
      this.element.addEventListener('input', (e) => {
        this.options.onChange(e.target.value, e);
      });
      
      this.element.addEventListener('change', (e) => {
        this.options.onChange(e.target.value, e);
      });
    }
  }

  // Static method to create inputs programmatically
  static create(options = {}) {
    const isTextarea = options.type === 'textarea';
    const input = document.createElement(isTextarea ? 'textarea' : 'input');
    return new BSRInput(input, options);
  }

  // Method to get value
  getValue() {
    return this.element.value;
  }

  // Method to set value
  setValue(value) {
    this.element.value = value;
    this.options.value = value;
  }

  // Method to set error state
  setError(hasError) {
    this.options.error = hasError;
    this.setupClasses();
  }

  // Method to update input
  update(newOptions) {
    this.options = { ...this.options, ...newOptions };
    this.setupElement();
    this.setupClasses();
  }

  // Method to destroy the component
  destroy() {
    if (this.options.onChange) {
      this.element.removeEventListener('input', this.options.onChange);
      this.element.removeEventListener('change', this.options.onChange);
    }
  }
}

// Auto-initialize inputs with data attributes
document.addEventListener('DOMContentLoaded', () => {
  const inputs = document.querySelectorAll('[data-bsr-input]');
  inputs.forEach(input => {
    new BSRInput(input, {
      type: input.dataset.type || 'text',
      size: input.dataset.size || 'default',
      error: input.dataset.error === 'true',
      placeholder: input.dataset.placeholder || ''
    });
  });
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = BSRInput;
} else if (typeof window !== 'undefined') {
  window.BSRInput = BSRInput;
}
