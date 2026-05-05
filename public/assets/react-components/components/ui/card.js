/**
 * WordPress-compatible Card Component
 * Works with both React and plain JavaScript
 */

class BSRCard {
  constructor(element, options = {}) {
    this.element = element;
    this.options = {
      hover: false,
      ...options
    };
    
    this.init();
  }

  init() {
    this.setupClasses();
    this.setupStructure();
  }

  setupClasses() {
    const baseClass = 'bsr-card';
    const hoverClass = this.options.hover ? 'bsr-card--hover' : '';
    
    this.element.className = `${baseClass} ${hoverClass}`.trim();
  }

  setupStructure() {
    // If element is empty, create default structure
    if (this.element.children.length === 0) {
      this.createDefaultStructure();
    }
  }

  createDefaultStructure() {
    const header = document.createElement('div');
    header.className = 'bsr-card__header';
    
    const title = document.createElement('h3');
    title.className = 'bsr-card__title';
    title.textContent = this.options.title || 'Card Title';
    
    const description = document.createElement('p');
    description.className = 'bsr-card__description';
    description.textContent = this.options.description || '';
    
    const content = document.createElement('div');
    content.className = 'bsr-card__content';
    content.innerHTML = this.options.content || '';
    
    header.appendChild(title);
    if (this.options.description) {
      header.appendChild(description);
    }
    
    this.element.appendChild(header);
    this.element.appendChild(content);
    
    if (this.options.footer) {
      const footer = document.createElement('div');
      footer.className = 'bsr-card__footer';
      footer.innerHTML = this.options.footer;
      this.element.appendChild(footer);
    }
  }

  // Static method to create cards programmatically
  static create(options = {}) {
    const card = document.createElement('div');
    return new BSRCard(card, options);
  }

  // Method to update card content
  update(newOptions) {
    this.options = { ...this.options, ...newOptions };
    this.setupClasses();
    
    // Update content if provided
    if (newOptions.title) {
      const titleElement = this.element.querySelector('.bsr-card__title');
      if (titleElement) titleElement.textContent = newOptions.title;
    }
    
    if (newOptions.description !== undefined) {
      const descElement = this.element.querySelector('.bsr-card__description');
      if (descElement) {
        if (newOptions.description) {
          descElement.textContent = newOptions.description;
          descElement.style.display = 'block';
        } else {
          descElement.style.display = 'none';
        }
      }
    }
    
    if (newOptions.content !== undefined) {
      const contentElement = this.element.querySelector('.bsr-card__content');
      if (contentElement) contentElement.innerHTML = newOptions.content;
    }
  }

  // Method to destroy the component
  destroy() {
    // Clean up any event listeners if needed
  }
}

// Auto-initialize cards with data attributes
document.addEventListener('DOMContentLoaded', () => {
  const cards = document.querySelectorAll('[data-bsr-card]');
  cards.forEach(card => {
    new BSRCard(card, {
      hover: card.dataset.hover === 'true',
      title: card.dataset.title,
      description: card.dataset.description,
      content: card.dataset.content
    });
  });
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = BSRCard;
} else if (typeof window !== 'undefined') {
  window.BSRCard = BSRCard;
}
