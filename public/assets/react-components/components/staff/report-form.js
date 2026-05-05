/**
 * Staff Report Form Component
 * Integrates with WordPress plugin functionality
 */

class BSRReportForm {
  constructor(element, options = {}) {
    this.element = element;
    this.options = {
      userId: null,
      apiUrl: '/wp-json/bsr/v1',
      onSuccess: null,
      onError: null,
      autoSave: true,
      ...options
    };
    
    this.tasks = [];
    this.taskCounter = 0;
    this.init();
  }

  init() {
    this.setupClasses();
    this.render();
    this.setupEventListeners();
    this.loadDraft();
  }

  setupClasses() {
    this.element.classList.add('bsr-report-form');
  }

  render() {
    const today = new Date().toISOString().split('T')[0];
    
    this.element.innerHTML = `
      <div class="bsr-report-form__header">
        <h1 class="bsr-report-form__title">Daily Staff Report</h1>
        <p class="bsr-report-form__subtitle">Submit your daily work activities and progress</p>
      </div>
      
      <form class="bsr-form" data-bsr-form data-auto-save="true">
        <div class="bsr-form__row bsr-form__row--3">
          <div class="bsr-form__group">
            <label class="bsr-label">Employee</label>
            <input type="text" class="bsr-input" value="${this.options.userName || 'Current User'}" readonly />
          </div>
          <div class="bsr-form__group">
            <label class="bsr-label">Role</label>
            <input type="text" class="bsr-input" value="${this.options.userRole || 'Staff Member'}" readonly />
          </div>
          <div class="bsr-form__group">
            <label class="bsr-label">Date</label>
            <input type="date" class="bsr-input" value="${today}" readonly />
          </div>
        </div>
        
        <div class="bsr-form__separator"></div>
        
        <div id="bsr-tasks-container">
          <!-- Tasks will be dynamically added here -->
        </div>
        
        <div class="bsr-form-actions">
          <div class="bsr-form-actions__left">
            <button type="button" class="bsr-button bsr-button--outline" id="bsr-add-task">
              + Add another task
            </button>
          </div>
          <div class="bsr-form-actions__right">
            <span class="bsr-auto-save-indicator" id="bsr-auto-save-indicator">
              Auto-saved
            </span>
            <button type="submit" class="bsr-button bsr-button--primary" id="bsr-submit-report">
              Submit report
            </button>
          </div>
        </div>
      </form>
    `;
    
    // Add first task
    this.addTask();
  }

  addTask() {
    const taskId = this.taskCounter++;
    const taskData = {
      id: taskId,
      category: '',
      description: '',
      status: 'Completed',
      nextAction: '',
      managerAssigned: false,
      notes: ''
    };
    
    this.tasks.push(taskData);
    this.renderTask(taskData);
  }

  renderTask(task) {
    const container = document.getElementById('bsr-tasks-container');
    const taskElement = document.createElement('div');
    taskElement.className = 'bsr-task-card';
    taskElement.id = `bsr-task-${task.id}`;
    
    taskElement.innerHTML = `
      <div class="bsr-task-card__header">
        <h3 class="bsr-task-card__title">Task #${task.id + 1}</h3>
        ${this.tasks.length > 1 ? `
          <button type="button" class="bsr-task-card__remove" onclick="bsrReportForm.removeTask(${task.id})">
            Remove
          </button>
        ` : ''}
      </div>
      
      <div class="bsr-form__row bsr-form__row--2">
        <div class="bsr-form__group">
          <label class="bsr-label bsr-label--required">Task category</label>
          <select class="bsr-input" name="task_${task.id}_category" required>
            <option value="">Choose category</option>
            <option value="Admission">Admission</option>
            <option value="Customer Service">Customer Service</option>
            <option value="Marketing">Marketing</option>
            <option value="Visa Processing">Visa Processing</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="bsr-form__group">
          <label class="bsr-label">Next action</label>
          <input type="text" class="bsr-input" name="task_${task.id}_nextAction" placeholder="e.g. Follow up tomorrow" />
        </div>
      </div>
      
      <div class="bsr-form__group">
        <label class="bsr-label bsr-label--required">Task description</label>
        <textarea class="bsr-input bsr-textarea" name="task_${task.id}_description" rows="3" required placeholder="What did you accomplish on this task?"></textarea>
      </div>
      
      <div class="bsr-form__group">
        <label class="bsr-label">Completion status</label>
        <div class="bsr-status-buttons">
          <button type="button" class="bsr-status-button bsr-status-button--completed" data-status="Completed" data-task-id="${task.id}">Completed</button>
          <button type="button" class="bsr-status-button" data-status="In Progress" data-task-id="${task.id}">In Progress</button>
          <button type="button" class="bsr-status-button" data-status="Not Completed" data-task-id="${task.id}">Not Completed</button>
        </div>
      </div>
      
      <div class="bsr-form__group">
        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
          <input type="checkbox" name="task_${task.id}_managerAssigned" style="margin: 0;" />
          <span class="bsr-label" style="margin: 0; font-weight: normal;">This was a manager-assigned task</span>
        </label>
      </div>
      
      <div class="bsr-form__group">
        <label class="bsr-label">Additional notes</label>
        <textarea class="bsr-input bsr-textarea" name="task_${task.id}_notes" rows="2" placeholder="Anything to add for context?"></textarea>
      </div>
    `;
    
    container.appendChild(taskElement);
    
    // Set initial status button state
    const statusButton = taskElement.querySelector(`[data-status="${task.status}"]`);
    if (statusButton) {
      statusButton.classList.add('bsr-status-button--active', `bsr-status-button--${task.status.toLowerCase().replace(' ', '-')}`);
    }
  }

  removeTask(taskId) {
    this.tasks = this.tasks.filter(task => task.id !== taskId);
    const taskElement = document.getElementById(`bsr-task-${taskId}`);
    if (taskElement) {
      taskElement.remove();
    }
    
    // Re-number remaining tasks
    this.updateTaskNumbers();
  }

  updateTaskNumbers() {
    const taskCards = this.element.querySelectorAll('.bsr-task-card');
    taskCards.forEach((card, index) => {
      const titleElement = card.querySelector('.bsr-task-card__title');
      if (titleElement) {
        titleElement.textContent = `Task #${index + 1}`;
      }
    });
  }

  setupEventListeners() {
    // Form submission
    const form = this.element.querySelector('form');
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      this.submitReport();
    });

    // Add task button
    const addTaskBtn = document.getElementById('bsr-add-task');
    addTaskBtn.addEventListener('click', () => {
      this.addTask();
    });

    // Status buttons
    this.element.addEventListener('click', (e) => {
      if (e.target.classList.contains('bsr-status-button')) {
        const taskId = parseInt(e.target.dataset.taskId);
        const status = e.target.dataset.status;
        this.setTaskStatus(taskId, status);
      }
    });

    // Keyboard shortcut for submission (Ctrl/Cmd + S)
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        this.submitReport();
      }
    });
  }

  setTaskStatus(taskId, status) {
    const task = this.tasks.find(t => t.id === taskId);
    if (task) {
      task.status = status;
    }

    // Update button states
    const taskElement = document.getElementById(`bsr-task-${taskId}`);
    const statusButtons = taskElement.querySelectorAll('.bsr-status-button');
    
    statusButtons.forEach(button => {
      button.classList.remove('bsr-status-button--active', 'bsr-status-button--completed', 'bsr-status-button--in-progress', 'bsr-status-button--not-completed');
      
      if (button.dataset.status === status) {
        button.classList.add('bsr-status-button--active', `bsr-status-button--${status.toLowerCase().replace(' ', '-')}`);
      }
    });
  }

  getFormData() {
    const formData = new FormData(this.element.querySelector('form'));
    const tasks = [];

    this.tasks.forEach(task => {
      const taskData = {
        category: formData.get(`task_${task.id}_category`) || '',
        description: formData.get(`task_${task.id}_description`) || '',
        status: task.status,
        nextAction: formData.get(`task_${task.id}_nextAction`) || '',
        managerAssigned: formData.get(`task_${task.id}_managerAssigned`) === 'on',
        notes: formData.get(`task_${task.id}_notes`) || ''
      };
      tasks.push(taskData);
    });

    return tasks;
  }

  validateForm(tasks) {
    const errors = {};
    
    tasks.forEach((task, index) => {
      if (!task.category) {
        errors[`task_${index}_category`] = 'Task category is required';
      }
      if (!task.description.trim()) {
        errors[`task_${index}_description`] = 'Task description is required';
      }
    });

    return Object.keys(errors).length > 0 ? errors : null;
  }

  async submitReport() {
    const tasks = this.getFormData();
    const errors = this.validateForm(tasks);

    if (errors) {
      this.showErrors(errors);
      return;
    }

    // Show loading state
    this.element.classList.add('bsr-report-form--loading');
    const submitBtn = document.getElementById('bsr-submit-report');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="bsr-loading-spinner"></span> Submitting...';

    try {
      const response = await fetch(`${this.options.apiUrl}/reports`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': this.options.nonce || ''
        },
        body: JSON.stringify({
          tasks,
          user_id: this.options.userId,
          date: new Date().toISOString().split('T')[0]
        })
      });

      if (!response.ok) {
        throw new Error('Failed to submit report');
      }

      const result = await response.json();
      
      // Success
      this.clearDraft();
      this.element.classList.remove('bsr-report-form--loading');
      submitBtn.innerHTML = originalText;
      
      if (this.options.onSuccess) {
        this.options.onSuccess(result);
      } else {
        this.showSuccessMessage(`${tasks.length} task${tasks.length > 1 ? 's' : ''} submitted successfully!`);
      }

      // Reset form
      this.resetForm();

    } catch (error) {
      this.element.classList.remove('bsr-report-form--loading');
      submitBtn.innerHTML = originalText;
      
      if (this.options.onError) {
        this.options.onError(error);
      } else {
        this.showErrorMessage('Failed to submit report. Please try again.');
      }
    }
  }

  showErrors(errors) {
    // Clear previous errors
    this.clearErrors();

    Object.keys(errors).forEach(fieldName => {
      const field = this.element.querySelector(`[name="${fieldName}"]`);
      if (field) {
        field.classList.add('bsr-input--error');
        
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
    this.element.querySelectorAll('.bsr-input--error').forEach(field => {
      field.classList.remove('bsr-input--error');
    });
    
    this.element.querySelectorAll('.bsr-form__error').forEach(error => {
      error.remove();
    });
  }

  showSuccessMessage(message) {
    // Create success notification
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #10b981;
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
      z-index: 1000;
      animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.remove();
    }, 3000);
  }

  showErrorMessage(message) {
    // Create error notification
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #ef4444;
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 0.5rem;
      box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
      z-index: 1000;
      animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.remove();
    }, 3000);
  }

  resetForm() {
    this.tasks = [];
    this.taskCounter = 0;
    document.getElementById('bsr-tasks-container').innerHTML = '';
    this.addTask();
  }

  saveDraft() {
    const tasks = this.getFormData();
    localStorage.setItem('bsr-report-draft', JSON.stringify(tasks));
    
    // Show auto-save indicator
    const indicator = document.getElementById('bsr-auto-save-indicator');
    if (indicator) {
      indicator.style.display = 'block';
      indicator.textContent = 'Auto-saved';
      
      setTimeout(() => {
        indicator.style.display = 'none';
      }, 2000);
    }
  }

  loadDraft() {
    const draft = localStorage.getItem('bsr-report-draft');
    if (draft) {
      try {
        const tasks = JSON.parse(draft);
        if (tasks.length > 0) {
          // Clear existing tasks
          this.tasks = [];
          this.taskCounter = 0;
          document.getElementById('bsr-tasks-container').innerHTML = '';
          
          // Load draft data
          tasks.forEach(taskData => {
            const task = {
              id: this.taskCounter++,
              ...taskData
            };
            this.tasks.push(task);
            this.renderTask(task);
          });
        }
      } catch (e) {
        console.warn('Failed to load draft data');
      }
    }
  }

  clearDraft() {
    localStorage.removeItem('bsr-report-draft');
  }

  // Auto-save functionality
  setupAutoSave() {
    if (this.options.autoSave) {
      let saveTimeout;
      this.element.addEventListener('input', () => {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
          this.saveDraft();
        }, 2000);
      });
    }
  }

  // Static method to create report forms programmatically
  static create(element, options = {}) {
    return new BSRReportForm(element, options);
  }

  // Method to destroy the component
  destroy() {
    // Clean up event listeners
    this.element.removeEventListener('submit', this.submitReport);
    document.removeEventListener('keydown', this.submitReport);
    
    // Clear tasks
    this.tasks = [];
  }
}

// Auto-initialize report forms with data attributes
document.addEventListener('DOMContentLoaded', () => {
  const reportForms = document.querySelectorAll('[data-bsr-report-form]');
  reportForms.forEach(form => {
    window.bsrReportForm = new BSRReportForm(form, {
      userId: form.dataset.userId,
      userName: form.dataset.userName,
      userRole: form.dataset.userRole,
      apiUrl: form.dataset.apiUrl || '/wp-json/bsr/v1',
      nonce: form.dataset.nonce
    });
  });
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = BSRReportForm;
} else if (typeof window !== 'undefined') {
  window.BSRReportForm = BSRReportForm;
}
