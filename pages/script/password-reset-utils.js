/**
 * Password Reset Utilities
 * Handles password visibility toggles and form interactions
 */

// Toggle password visibility
function togglePasswordVisibility(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    
    if (!input || !button) return;
    
    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = '<i class="fas fa-eye-slash"></i>';
    } else {
        input.type = 'password';
        button.innerHTML = '<i class="fas fa-eye"></i>';
    }
}

// Show/hide loading state
function setLoadingState(buttonId, isLoading, loadingText = 'Loading...', originalText = 'Send Reset Link') {
    const button = document.getElementById(buttonId);
    if (!button) return;
    
    button.disabled = isLoading;
    button.innerHTML = isLoading ? `<span class="loader show"></span>${loadingText}` : originalText;
}

// Show/hide message
function showMessage(messageId, messageText, type = 'info') {
    const messageDiv = document.getElementById(messageId);
    if (!messageDiv) return;
    
    messageDiv.className = `firebase-message ${type}`;
    messageDiv.querySelector('#messageText').textContent = messageText;
    messageDiv.style.display = 'block';
}

// Hide message
function hideMessage(messageId) {
    const messageDiv = document.getElementById(messageId);
    if (!messageDiv) return;
    
    messageDiv.style.display = 'none';
}

// Validate email format
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Validate password strength
function validatePasswordStrength(password) {
    const requirements = {
        length: password.length >= 8,
        uppercase: /[A-Z]/.test(password),
        lowercase: /[a-z]/.test(password),
        number: /[0-9]/.test(password)
    };
    
    return requirements;
}

// Update requirement display
function updatePasswordRequirement(elementId, isMet) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    if (isMet) {
        element.classList.remove('unmet');
        element.classList.add('met');
        element.querySelector('.requirement-icon').textContent = '✓';
    } else {
        element.classList.remove('met');
        element.classList.add('unmet');
        element.querySelector('.requirement-icon').textContent = '○';
    }
}

// Check all password requirements
function checkAllPasswordRequirements(password) {
    const reqs = validatePasswordStrength(password);
    const requirementElements = [
        { id: 'lengthReq', met: reqs.length },
        { id: 'uppercaseReq', met: reqs.uppercase },
        { id: 'lowercaseReq', met: reqs.lowercase },
        { id: 'numberReq', met: reqs.number }
    ];
    
    requirementElements.forEach(req => {
        updatePasswordRequirement(req.id, req.met);
    });
    
    return Object.values(reqs).every(req => req === true);
}

// Debounce function for input events
function debounce(func, wait) {
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

// Export functions for use in other scripts
window.passwordResetUtils = {
    togglePasswordVisibility,
    setLoadingState,
    showMessage,
    hideMessage,
    isValidEmail,
    validatePasswordStrength,
    updatePasswordRequirement,
    checkAllPasswordRequirements,
    debounce
};
