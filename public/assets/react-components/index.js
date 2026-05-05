/**
 * BSR React Components - WordPress Integration
 * Main entry point for all BSR UI components
 */

// Import UI components
import './components/ui/button.css';
import './components/ui/card.css';
import './components/ui/input.css';
import './components/ui/form.css';
import './components/staff/report-form.css';

// Import component classes
import './components/ui/button.js';
import './components/ui/card.js';
import './components/ui/input.js';
import './components/ui/form.js';
import './components/staff/report-form.js';

// Make components available globally
window.BSRComponents = {
  Button: window.BSRButton,
  Card: window.BSRCard,
  Input: window.BSRInput,
  Form: window.BSRForm,
  ReportForm: window.BSRReportForm
};

// Initialize all components when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  console.log('BSR React Components initialized for WordPress');
  
  // Auto-initialize all data-attribute components
  const components = ['button', 'card', 'input', 'form'];
  components.forEach(component => {
    const elements = document.querySelectorAll(`[data-bsr-${component}]`);
    console.log(`Found ${elements.length} ${component} elements to initialize`);
  });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
  module.exports = window.BSRComponents;
}
