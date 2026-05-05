/**
 * WordPress-compatible Form Component
 * Works with both React and plain JavaScript
 */

class BSRForm {
  constructor(element, options = {}) {
    this.element = element;
    this.options = {
      onSubmit: null,
      autoSave: false,
      validate: null,
      ...options
    };
    
    this.fields = new Map();
    this.init();
  }

  init() {
    this.setupClasses();
    this.setupEventListeners();
    this.setupFields();
  }

  setupClasses() {
    if (!this.element.classList.contains('bsr-form')) {
      this.element.classList.add('bsr-form');
    }
  }

  setupEventListeners() {
    if (this.options.onSubmit && typeof this.options.onSubmit === 'function') {
      this.element.addEventListener('submit', (e) => {
        e.preventDefault();
        this.handleSubmit();
      });
    }

    // Auto-save functionality
    if (this.options.autoSave) {
      this.element.addEventListener('input', this.debounce(() => {
        this.saveToLocalStorage();
      }, 1000));
    }
  }

  setupFields() {
    // Find all form fields and initialize them
    const inputs = this.element.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
      const name = input.name || input.id;
      if (name) {
        this.fields.set(name, input);
        
        // Initialize with BSR components if applicable
        if (input.dataset.bsrInput) {
          new BSRInput(input);
        }
      }
    });

    // Load from localStorage if auto-save is enabled
    if (this.options.autoSave) {
      this.loadFromLocalStorage();
    }
  }

  handleSubmit() {
    const formData = this.getFormData();
    
    // Validate if validation function is provided
    if (this.options.validate && typeof this.options.validate === 'function') {
      const errors = this.options.validate(formData);
      if (errors) {
        this.showErrors(errors);
        return;
      }
    }
    
    // Clear any existing errors
    this.clearErrors();
    
    // Call submit handler
    if (this.options.onSubmit) {
      this.options.onSubmit(formData);
    }
  }

  getFormData() {
    const formData = {};
    this.fields.forEach((field, name) => {
      if (field.type === 'checkbox') {
        formData[name] = field.checked;
      } else if (field.type === 'radio') {
        if (field.checked) {
          formData[name] = field.value;
        }
      } else {
        formData[name] = field.value;
      }
    });
    return formData;
  }

  setFormData(data) {
    Object.keys(data).forEach(key => {
      const field = this.fields.get(key);
      if (field) {
        if (field.type === 'checkbox') {
          field.checked = data[key];
        } else {
          field.value = data[key];
        }
      }
    });
  }

  showErrors(errors) {
    this.clearErrors();
    
    Object.keys(errors).forEach(fieldName => {
      const field = this.fields.get(fieldName);
      if (field) {
        // Add error class to field
        field.classList.add('bsr-input--error');
        
        // Create or update error message
        let errorElement = field.parentNode.querySelector('.bsr-form__error');
        if (!errorElement) {
          errorElement = document.createElement('div');
          errorElement.className = 'bsr-form__error';
          field.parentNode.appendChild(errorElement);
        }
        errorElement.textContent = errors[fieldName];
      }
    });
  }

  clearErrors() {
    this.fields.forEach(field => {
      field.classList.remove('bsr-input--error');
      const errorElement = field.parentNode.querySelector('.bsr-form__error');
      if (errorElement) {
        errorElement.remove();
      }
    });
  }

  saveToLocalStorage() {
    const formData = this.getFormData();
    const formId = this.element.id || 'bsr-form-' + Date.now();
    localStorage.setItem(`bsr-form-${formId}`, JSON.stringify(formData));
  }

  loadFromLocalStorage() {
    const formId = this.element.id || 'bsr-form-' + Date.now();
    const saved = localStorage.getItem(`bsr-form-${formId}`);
    if (saved) {
      try {
        const formData = JSON.parse(saved);
        this.setFormData(formData);
      } catch (e) {
        console.warn('Failed to load form data from localStorage');
      }
    }
  }

  // Utility function for debouncing
  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Static method to create forms programmatically
  static create(options = {}) {
    const form = document.createElement('form');
    return new BSRForm(form, options);
  }

  // Method to destroy the component
  destroy() {
    // Clean up event listeners
    this.element.removeEventListener('submit', this.handleSubmit);
    
    // Clear fields
    this.fields.clear();
  }
}

// Auto-initialize forms with data attributes
document.addEventListener('DOMContentLoaded', () => {
  const forms = document.querySelectorAll('[data-bsr-form]');
  forms.forEach(form => {
    new BSRForm(form, {
      autoSave: form.dataset.autoSave === 'true'
    });
  });
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = BSRForm;
} else if (typeof window !== 'undefined') {
  window.BSRForm = BSRForm;
}
