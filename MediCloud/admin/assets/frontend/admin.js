const fieldRules = new WeakMap();

document.addEventListener('DOMContentLoaded', function() {
    console.log('MediCloud Admin Panel - Initializing...');
    initAdminFeatures();
    initFormValidation();
    initInteractiveElements();
    console.log('MediCloud Admin Panel - Ready!');
});

function initAdminFeatures() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    if (dateInputs.length > 0) {
        const today = new Date().toISOString().split('T')[0];
        dateInputs.forEach(input => {
            if (!input.value) {
                input.value = today;
            }
        });
    }
    
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', handleSearch);
    });
    
    const filterSelects = document.querySelectorAll('.filter-select');
    filterSelects.forEach(select => {
        select.addEventListener('change', handleFilter);
    });
}

function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                validateField(this);
            });
            
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
        
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showMessage('Veuillez corriger les erreurs dans le formulaire', 'error');
            }
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.required || field.hasAttribute('data-required');
    
    field.classList.remove('validation-error');
    const existingError = field.parentElement.querySelector('.validation-message');
    if (existingError) {
        existingError.remove();
    }
    
    if (required && !value) {
        addFieldError(field, 'Ce champ est requis');
        return false;
    }
    
    if (value) {
        switch(type) {
            case 'email':
                if (!validateEmail(value)) {
                    addFieldError(field, 'Email invalide');
                    return false;
                }
                break;
            case 'tel':
                if (!validatePhone(value)) {
                    addFieldError(field, 'Numéro de téléphone invalide');
                    return false;
                }
                break;
            case 'number':
                const min = field.getAttribute('min');
                const max = field.getAttribute('max');
                const num = parseFloat(value);
                if (min && num < parseFloat(min)) {
                    addFieldError(field, `La valeur doit être au moins ${min}`);
                    return false;
                }
                if (max && num > parseFloat(max)) {
                    addFieldError(field, `La valeur ne peut pas dépasser ${max}`);
                    return false;
                }
                break;
        }
    }
    
    return true;
}

function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function addFieldError(field, message) {
    field.classList.add('validation-error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'validation-message';
    errorDiv.textContent = message;
    
    field.parentElement.appendChild(errorDiv);
}

function validateEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

function validatePhone(phone) {
    const regex = /^(\+213|0)[1-9]\d{8}$/;
    const cleaned = phone.replace(/\s+/g, '');
    return regex.test(cleaned);
}

function initInteractiveElements() {
    const planInputs = document.querySelectorAll('.plan input[type="radio"]');
    planInputs.forEach(input => {
        input.addEventListener('change', function() {
            document.querySelectorAll('.plan').forEach(plan => {
                plan.classList.remove('selected');
            });
            if (this.checked) {
                this.closest('.plan').classList.add('selected');
            }
        });
    });
    
    const cabinetCards = document.querySelectorAll('.cabinet-card');
    cabinetCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.closest('button')) {
                window.location.href = this.href;
            }
        });
    });
    
    document.querySelectorAll('.stat').forEach(stat => {
        if (!stat.classList.contains('hover-raise')) {
            stat.classList.add('hover-raise');
        }
    });
}

function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.cabinet-card, .notification-item, .invoice-item');
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    const cabinetRows = document.querySelectorAll('.cabinet-row:not(.cabinet-row-header)');
    

    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    

    cabinetRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    


    tableRows.forEach(row => {

        if (row.cells.length === 0) return;
        
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function handleFilter(e) {
    const filterValue = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.cabinet-card, .notification-item, .invoice-item');
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    const cabinetRows = document.querySelectorAll('.cabinet-row:not(.cabinet-row-header)');
    

    if (filterValue === 'all' || !filterValue) {
        cards.forEach(card => card.style.display = '');
        tableRows.forEach(row => row.style.display = '');
        cabinetRows.forEach(row => row.style.display = '');
        return;
    }
    

    cabinetRows.forEach(row => {
        const badges = row.querySelectorAll('.badge');
        const text = row.textContent.toLowerCase();
        let shouldShow = false;
        
        badges.forEach(badge => {
            if (badge.textContent.toLowerCase().includes(filterValue) ||
                badge.className.toLowerCase().includes(filterValue)) {
                shouldShow = true;
            }
        });
        

        if (text.includes(filterValue)) {
            shouldShow = true;
        }
        
        row.style.display = shouldShow ? '' : 'none';
    });
    

    cards.forEach(card => {
        const badges = card.querySelectorAll('.badge');
        let shouldShow = false;
        
        badges.forEach(badge => {
            if (badge.textContent.toLowerCase().includes(filterValue) ||
                badge.className.toLowerCase().includes(filterValue)) {
                shouldShow = true;
            }
        });
        
        card.style.display = shouldShow ? '' : 'none';
    });
    

    tableRows.forEach(row => {
        const badges = row.querySelectorAll('.badge');
        let shouldShow = false;
        
        badges.forEach(badge => {
            if (badge.textContent.toLowerCase().includes(filterValue) ||
                badge.className.toLowerCase().includes(filterValue)) {
                shouldShow = true;
            }
        });
        
        row.style.display = shouldShow ? '' : 'none';
    });
}

function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.className = `alert ${type}`;
    messageDiv.textContent = message;
    messageDiv.style.position = 'fixed';
    messageDiv.style.top = '100px';
    messageDiv.style.right = '20px';
    messageDiv.style.zIndex = '1000';
    messageDiv.style.animation = 'slideInRight 0.3s ease';
    
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => messageDiv.remove(), 300);
    }, 3000);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-DZ', {
        style: 'decimal',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount) + ' DA';
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('fr-DZ', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(date);
}

function calculateProgress(startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const now = new Date();
    
    const total = end - start;
    const elapsed = now - start;
    
    return Math.round((elapsed / total) * 100);
}

function clearForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('validation-error');
        });
        const errors = form.querySelectorAll('.validation-message');
        errors.forEach(error => error.remove());
    }
}

function validateRequired(value) {
    if (value === null || value === undefined) return false;
    if (typeof value === 'string') return value.trim().length > 0;
    return true;
}

function validateNumberRange(value, min, max) {
    if (value === null || value === undefined || value === '') return false;
    const n = Number(value);
    if (Number.isNaN(n)) return false;
    if (min !== undefined && n < min) return false;
    if (max !== undefined && n > max) return false;
    return true;
}

function parseDateValue(raw) {
    if (!raw) return null;
    const s = String(raw).trim();
    let m = s.match(/^\s*(\d{4})-(\d{1,2})-(\d{1,2})\s*$/);
    if (m) {
        const y = Number(m[1]), mm = Number(m[2]), d = Number(m[3]);
        const dt = new Date(y, mm - 1, d);
        if (dt && dt.getFullYear() === y && dt.getMonth() === mm - 1 && dt.getDate() === d) return dt;
        return null;
    }
    m = s.match(/^\s*(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})\s*$/);
    if (m) {
        const d = Number(m[1]), mm = Number(m[2]), y = Number(m[3]);
        const dt = new Date(y, mm - 1, d);
        if (dt && dt.getFullYear() === y && dt.getMonth() === mm - 1 && dt.getDate() === d) return dt;
        return null;
    }
    const f = new Date(s);
    if (f.toString() === 'Invalid Date') return null;
    return f;
}

function showError(el, msg) {
    clearError(el);
    el.classList.add('validation-error');
    const span = document.createElement('span');
    span.className = 'validation-message';
    span.textContent = msg;
    span.style.color = 'var(--danger, red)';
    span.style.fontSize = '0.875rem';
    span.style.display = 'block';
    span.style.marginTop = '0.25rem';
    el.parentNode && el.parentNode.appendChild(span);
    try {
        console.warn('Validation failed on', el, msg);
    } catch (e) {}
}

function clearError(el) {
    el.classList.remove('validation-error');
    if (el.parentNode) {
        const existing = el.parentNode.querySelector('.validation-message');
        if (existing) existing.remove();
    }
}

function attachValidation(root) {
    root = root || document;
    const inputs = root.querySelectorAll('input,select,textarea');
    inputs.forEach(input => {
        const explicitRules = input.getAttribute('data-validate');
        if (explicitRules) {
            fieldRules.set(input, explicitRules);
        } else {
            const inferred = inferRules(input);
            if (inferred && inferred.length > 0) {
                fieldRules.set(input, inferred.join(','));
            }
        }
        input.addEventListener('blur', () => runValidationOn(input));
        input.addEventListener('input', () => clearError(input));
    });

    const forms = root.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let valid = true;
            const toValidate = form.querySelectorAll('input,select,textarea');
            toValidate.forEach(el => {
                const ok = runValidationOn(el);
                if (!ok) valid = false;
            });
            if (!valid) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });
}

function runValidationOn(input) {
    clearError(input);
    const storedRules = fieldRules.get(input);
    let parts = [];
    if (storedRules) {
        parts = storedRules.split(',').map(s => s.trim());
    } else {
        return true;
    }

    for (let r of parts) {
        if (r === 'required') {
            if (!validateRequired(input.value)) {
                showError(input, 'Champ requis');
                return false;
            }
        } else if (r === 'email') {
            if (input.value && !validateEmail(input.value)) {
                showError(input, 'Adresse email invalide');
                return false;
            }
        } else if (r === 'phone') {
            if (input.value && !validatePhone(input.value)) {
                showError(input, 'Numéro de téléphone invalide');
                return false;
            }
        } else if (r.startsWith('minlen:')) {
            const partsMin = r.split(':');
            const min = partsMin[1] ? Number(partsMin[1]) : 0;
            if (input.value.length < min) {
                showError(input, `Doit contenir au moins ${min} caractères`);
                return false;
            }
        } else if (r.startsWith('match:')) {
            const target = r.slice(6);
            let other = null;
            if (!target) {
                showError(input, 'Règle match mal configurée');
                return false;
            }
            if (target[0] === '#') {
                other = document.querySelector(target);
            } else {
                other = document.querySelector(`[name="${target}"]`);
            }
            if (!other) {
                showError(input, 'Champ à comparer introuvable');
                return false;
            }
            if (input.value !== other.value) {
                showError(input, 'Les champs ne correspondent pas');
                return false;
            }
        } else if (r.startsWith('not-equal:')) {
            const target = r.slice(10);
            let other = null;
            if (!target) {
                showError(input, 'Règle not-equal mal configurée');
                return false;
            }
            if (target[0] === '#') other = document.querySelector(target);
            else other = document.querySelector(`[name="${target}"]`);
            if (!other) {
                showError(input, 'Champ de comparaison introuvable');
                return false;
            }
            if (input.value && other.value && input.value === other.value) {
                showError(input, 'Le nouveau mot de passe doit être différent de l\'ancien');
                return false;
            }
        } else if (r === 'date-not-future') {
            if (input.value) {
                const d = parseDateValue(input.value);
                if (!d) {
                    showError(input, 'Date invalide');
                    return false;
                }
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                d.setHours(0, 0, 0, 0);
                if (d.getTime() > today.getTime()) {
                    showError(input, 'La date ne peut pas être dans le futur');
                    return false;
                }
            }
        } else if (r === 'date-not-past') {
            if (input.value) {
                const d = parseDateValue(input.value);
                if (!d) {
                    showError(input, 'Date invalide');
                    return false;
                }
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                d.setHours(0, 0, 0, 0);
                if (d.getTime() < today.getTime()) {
                    showError(input, 'La date ne peut pas être dans le passé');
                    return false;
                }
            }
        } else if (r.startsWith('date-after:')) {
            const target = r.split(':')[1];
            if (target && input.value) {
                let other = null;
                if (target[0] === '#') other = document.querySelector(target);
                else other = document.querySelector(`[name="${target}"]`);
                if (other && other.value) {
                    const d1 = parseDateValue(input.value);
                    const d2 = parseDateValue(other.value);
                    if (!d1 || !d2) {
                        showError(input, 'Date invalide');
                        return false;
                    }
                    d1.setHours(0, 0, 0, 0);
                    d2.setHours(0, 0, 0, 0);
                    if (d1.getTime() < d2.getTime()) {
                        showError(input, 'La date doit être postérieure ou égale à la date de référence');
                        return false;
                    }
                }
            }
        } else if (r.startsWith('range:')) {
            const toks = r.split(':');
            const min = toks[1] ? Number(toks[1]) : undefined;
            const max = toks[2] ? Number(toks[2]) : undefined;
            if (!validateNumberRange(input.value, min, max)) {
                showError(input, `Valeur doit être entre ${min} et ${max}`);
                return false;
            }
        }
    }
    return true;
}

function inferRules(input) {
    const rules = [];
    const val = (str) => (str || '').toString().toLowerCase();
    const id = val(input.id);
    const name = val(input.name);
    const ph = val(input.placeholder);
    const type = val(input.type);

    if (input.hasAttribute('required')) rules.push('required');

    if (id.includes('email') || name.includes('email') || ph.includes('@')) {
        rules.push('email');
    }

    if (id.includes('phone') || name.includes('phone') || name.includes('tel') || ph.includes('05') || ph.includes('06') || ph.includes('07') || id.includes('telephone') || name.includes('telephone')) {
        rules.push('phone');
    }

    if (type === 'date' || id.includes('date') || name.includes('date') || id.includes('birth') || name.includes('birth')) {
        if (id.includes('birth') || name.includes('birth') || ph.includes('naissance')) {
            rules.push('date-not-future');
        } else {
            if (input.hasAttribute('required')) rules.push('date-not-future');
        }
    }

    if (id.includes('password') || name.includes('password') || ph.includes('mot de passe')) {
        if (id.includes('confirm') || name.includes('confirm') || ph.includes('confirme')) {
            let target = null;
            target = document.querySelector('#new-password') || document.querySelector('[name="new-password"]') || document.querySelector('[id*="password"]:not([id*="confirm"])') || document.querySelector('[name*="password"]:not([name*="confirm"])');
            if (target) {
                rules.push('match:#' + (target.id || target.name));
            }
        } else {
            rules.push('minlen:8');
            const current = document.querySelector('#current-password') || document.querySelector('[name="current-password"]');
            if (current) {
                rules.push('not-equal:#' + (current.id || current.name));
            }
        }
    }

    return rules;
}

window.AdminJS = {
    validateEmail,
    validatePhone,
    formatCurrency,
    formatDate,
    calculateProgress,
    showMessage,
    clearForm,
    validateRequired,
    validateNumberRange,
    parseDateValue,
    attachValidation,
    runValidationOn
};

document.addEventListener('DOMContentLoaded', function() {
    attachValidation(document);
    initLoginPage();
});

function initLoginPage() {
    const form = document.getElementById('login-form');
    if (!form) return;
    const submitBtn = document.getElementById('login-submit');
    let submitting = false;
    let failCount = 0;

    form.addEventListener('submit', function(e) {
        if (submitting) {
            e.preventDefault();
            return;
        }
        const email = document.getElementById('email');
        const password = document.getElementById('password');
        const valid = runValidationOn(email) & runValidationOn(password);
        if (!valid) {
            e.preventDefault();
            failCount++;
            showMessage('Veuillez corriger les erreurs', 'error');
            if (failCount >= 3 && submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Trop d\'échecs';
                setTimeout(() => { submitBtn.disabled = false; submitBtn.textContent = 'Se connecter'; failCount = 0; }, 8000);
            }
            return;
        }
        submitting = true;
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Connexion...';
        }

    });
}
