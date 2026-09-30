const validationRules = {
    text: (value) => value.trim().length > 0,
    email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
    phone: (value) => /^[\d\s\-\+\(\)]{7,}$/.test(value.replace(/\s/g, '')),
    date: (value) => {
        if (!value) return false;
        const date = new Date(value);
        return date instanceof Date && !isNaN(date);
    },
    time: (value) => {
        if (!value) return false;
        return /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(value);
    },
    password: (value) => value.length >= 6,
    number: (value) => !isNaN(value) && value.trim().length > 0,
    postal_code: (value) => /^[a-zA-Z0-9\s\-]{2,}$/.test(value)
};

const fieldTypeMap = {
    last_name: 'text',
    first_name: 'text',
    phone: 'phone',
    email: 'email',
    password: 'password',
    birth_date: 'date',
    national_id: 'text',
    street: 'text',
    city: 'text',
    region: 'text',
    postal_code: 'postal_code',
    country: 'text',
    emergency_last_name: 'text',
    emergency_first_name: 'text',
    emergency_phone: 'phone',
    emergency_relation: 'text',
    time: 'time',
    date: 'date',
    notes: 'text',
    diagnosis: 'text',
    treatment: 'text',
    medication: 'text',
    dosage: 'text',
    duration: 'text',
    symptoms: 'text',
    blood_group: 'text',
    allergies: 'text'
};

const errorMessages = {
    text: 'This field cannot be empty',
    email: 'Please enter a valid email address',
    phone: 'Please enter a valid phone number',
    date: 'Please enter a valid date',
    time: 'Please enter a valid time (HH:MM)',
    password: 'Password must be at least 6 characters',
    number: 'Please enter a valid number',
    postal_code: 'Please enter a valid postal code'
};

function validateField(input) {
    const value = input.value.trim();
    const fieldType = fieldTypeMap[input.name] || 'text';
    const isRequired = input.hasAttribute('required');

    if (!isRequired && value === '') {
        clearFieldError(input);
        return true;
    }

    if (isRequired && value === '') {
        showFieldError(input, 'This field is required');
        return false;
    }

    if (validationRules[fieldType]) {
        if (!validationRules[fieldType](value)) {
            showFieldError(input, errorMessages[fieldType] || 'Invalid input');
            return false;
        }
    }

    clearFieldError(input);
    return true;
}

function showFieldError(input, message) {
    clearFieldError(input);
    input.classList.add('input-error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    input.parentNode.appendChild(errorDiv);
}

function clearFieldError(input) {
    input.classList.remove('input-error');
    const errorDiv = input.parentNode.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function validateForm(form) {
    const inputs = form.querySelectorAll('input, textarea, select');
    let isValid = true;

    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });

    return isValid;
}

function initFormValidation(form) {
    if (!form) return;

    const inputs = form.querySelectorAll('input, textarea, select');

    inputs.forEach(input => {
        input.addEventListener('blur', () => {
            validateField(input);
        });

        input.addEventListener('input', () => {
            if (input.classList.contains('input-error')) {
                validateField(input);
            }
        });
    });

    form.addEventListener('submit', (e) => {
        if (!validateForm(form)) {
            e.preventDefault();
            const firstError = form.querySelector('.input-error');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        initFormValidation(form);
    });
});
