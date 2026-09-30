const form = document.getElementById('preferencesForm');
const resultsSection = document.getElementById('results');
const recommendedCabinets = document.getElementById('recommendedCabinets');
const criteriaGroup = document.getElementById('criteria-group');
const criteriaWarning = document.getElementById('criteria-warning');
const criteriaCounter = document.getElementById('criteria-counter');
const weightsContainer = document.getElementById('weights-container');

const criteriaLabels = {
    'localisation': 'Localisation',
    'disponibilite': 'Disponibilité',
    'accueil': 'Accueil',
    'competence': 'Compétence',
    'experience': 'Expérience',
    'equipement': 'Équipement',
    'hygiene': 'Hygiène',
    'securite': 'Sécurité',
    'frais': 'Frais',
    'ponctualite': 'Ponctualité'
};

criteriaGroup.addEventListener('change', (e) => {
    if (e.target.type !== 'checkbox') return;
    
    const checked = criteriaGroup.querySelectorAll('input[type="checkbox"]:checked');
    const counterValue = criteriaCounter.querySelector('.counter-value');
    counterValue.textContent = checked.length;

    if (checked.length >= 2) {
        counterValue.style.color = '#10b981';
        criteriaCounter.classList.add('complete');
    } else if (checked.length > 0) {
        counterValue.style.color = 'var(--primary)';
        criteriaCounter.classList.remove('complete');
    } else {
        counterValue.style.color = 'var(--text-secondary)';
        criteriaCounter.classList.remove('complete');
    }

    criteriaWarning.style.display = checked.length < 2 ? 'flex' : 'none';
    updateWeights(checked);
});

function updateWeights(checkedBoxes) {
    weightsContainer.innerHTML = '';
    
    if (checkedBoxes.length < 2) {
        weightsContainer.innerHTML = '<p class="weights-empty">Sélectionnez au moins 2 critères pour configurer leurs poids</p>';
        return;
    }
    
    const weightsGrid = document.createElement('div');
    weightsGrid.className = 'weights-grid';
    
    checkedBoxes.forEach((checkbox) => {
        const value = checkbox.value;
        const label = criteriaLabels[value] || value;
        
        const weightItem = document.createElement('div');
        weightItem.className = 'weight-item';
        weightItem.innerHTML = `
            <label for="weight-${value}" class="weight-label">${label}</label>
            <div class="weight-input-group">
                <input type="range" id="weight-${value}" name="weight-${value}" class="weight-slider" min="1" max="10" value="5" data-criteria="${value}">
                <input type="number" class="weight-value" min="1" max="10" value="5" data-criteria="${value}">
                <span class="weight-unit">/10</span>
            </div>
        `;
        weightsGrid.appendChild(weightItem);
    });
    
    weightsContainer.appendChild(weightsGrid);
    syncWeightInputs();
}

function syncWeightInputs() {
    const sliders = weightsContainer.querySelectorAll('.weight-slider');
    const numberInputs = weightsContainer.querySelectorAll('.weight-value');
    
    sliders.forEach(slider => {
        slider.addEventListener('input', (e) => {
            const criteria = e.target.dataset.criteria;
            const numberInput = weightsContainer.querySelector(`input[data-criteria="${criteria}"][type="number"]`);
            if (numberInput) numberInput.value = e.target.value;
        });
    });
    
    numberInputs.forEach(input => {
        input.addEventListener('input', (e) => {
            let value = parseInt(e.target.value) || 5;
            value = Math.max(1, Math.min(10, value));
            e.target.value = value;
            const slider = weightsContainer.querySelector(`input[data-criteria="${e.target.dataset.criteria}"][type="range"]`);
            if (slider) slider.value = value;
        });
    });
}

form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!form.specialty.value || !form.wilaya.value) {
        alert('Choisissez une specialite et une wilaya');
        return;
    }
    const selectedCriteria = Array.from(criteriaGroup.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb.value);

    if (selectedCriteria.length < 2) {
        alert('Veuillez sélectionner au moins 2 critères');
        return;
    }

    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) { 
        submitBtn.disabled = true; 
        submitBtn.textContent = 'Recherche...'; 
    }

    const formData = new FormData();
    formData.append('specialty', form.specialty.value);
    formData.append('wilaya', form.wilaya.value);
    formData.append('city', form.wilaya.value);
    formData.append('method', form.method.value);
    
    selectedCriteria.forEach(crit => {
        formData.append('criteria[]', crit);
        const weightInput = document.querySelector(`input[name="weight-${crit}"][type="range"]`);
        if (weightInput) {
            formData.append(`weight-${crit}`, weightInput.value);
        }
    });
    
    fetch('./assets/backend/search-api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.text())
        .then(html => {
            recommendedCabinets.innerHTML = html;
            resultsSection.classList.remove('hidden');
            resultsSection.scrollIntoView({ behavior: 'smooth' });
        })
        .catch(err => {
            console.error('Fetch error:', err);
            recommendedCabinets.innerHTML = '<p style="color: red; text-align: center;">Erreur: ' + err.message + '</p>';
            resultsSection.classList.remove('hidden');
        })
        .finally(() => {
            if (submitBtn) { 
                submitBtn.disabled = false; 
                submitBtn.textContent = '🔎 Trouver mon cabinet ideal'; 
            }
        });
});

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function displayResults(data) {
    const { method, criteria_count, results } = data;
    
    // Mise à jour du résumé
    const summaryDiv = document.getElementById('selection-summary');
    if (summaryDiv) {
        summaryDiv.innerHTML = '<div style="display: flex; gap: 15px; justify-content: center; margin-bottom: 20px; flex-wrap: wrap;">' +
            '<span style="background: #f0f0f0; padding: 8px 16px; border-radius: 20px; font-size: 14px;">Méthode: <strong>' + method + '</strong></span>' +
            '<span style="background: #f0f0f0; padding: 8px 16px; border-radius: 20px; font-size: 14px;">Critères: <strong>' + criteria_count + '</strong></span>' +
            '<span style="background: #f0f0f0; padding: 8px 16px; border-radius: 20px; font-size: 14px;">Résultats: <strong>' + results.length + '</strong></span>' +
            '</div>';
    }

    // Affichage des résultats
    if (!results || results.length === 0) {
        recommendedCabinets.innerHTML = '<p style="text-align: center; color: #666; padding: 40px 20px;">Aucun résultat trouvé.</p>';
        return;
    }
    
    let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; padding: 20px 0;">';
    
    results.forEach(cabinet => {
        const docInfo = cabinet.doctor ? 
            '👨‍⚕️ ' + escapeHtml(cabinet.doctor.name) + '<br>' +
            'Spécialité: ' + escapeHtml(cabinet.doctor.specialty) + '<br>' +
            'Expérience: ' + escapeHtml(String(cabinet.doctor.years_experience)) + ' ans'
            : '<em style="color: #999;">Aucun médecin de cette spécialité</em>';
        
        html += '<a href="' + cabinet.link + '" style="' +
            'display: block; ' +
            'text-decoration: none; ' +
            'color: inherit; ' +
            'padding: 20px; ' +
            'border: 1px solid #e0e0e0; ' +
            'border-radius: 12px; ' +
            'background: #f9f9f9; ' +
            'transition: all 0.3s ease; ' +
            'cursor: pointer; ' +
        '" onmouseover="' +
            'this.style.transform = \'translateY(-4px)\'; ' +
            'this.style.boxShadow = \'0 8px 16px rgba(0,0,0,0.1)\'; ' +
        '" onmouseout="' +
            'this.style.transform = \'translateY(0)\'; ' +
            'this.style.boxShadow = \'none\'; ' +
        '">' +
            '<div style="display: flex; gap: 10px; margin-bottom: 15px;">' +
                '<span style="display: inline-block; background: #2F81F7; color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: bold;">#' + cabinet.rank + '</span>' +
                '<span style="display: inline-block; background: #10b981; color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: bold;">' + cabinet.score + '%</span>' +
            '</div>' +
            '<h3 style="margin: 0 0 10px 0; font-size: 18px; color: #1a1a1a; font-weight: bold;">' + escapeHtml(cabinet.name) + '</h3>' +
            '<div style="font-size: 14px; color: #555; margin-bottom: 15px; line-height: 1.6;">' +
                '<p style="margin: 5px 0;">📍 ' + escapeHtml(cabinet.address) + '</p>' +
                '<p style="margin: 5px 0;">📞 ' + escapeHtml(cabinet.phone) + '</p>' +
                '<p style="margin: 5px 0;">💰 ' + (cabinet.price ? cabinet.price.toLocaleString() + ' DA' : 'Sur demande') + '</p>' +
            '</div>' +
            '<div style="padding: 12px; background: rgba(47, 129, 247, 0.08); border-left: 3px solid #2F81F7; border-radius: 6px; font-size: 13px; color: #333; line-height: 1.5;">' +
                docInfo +
            '</div>' +
        '</a>';
    });
    
    html += '</div>';
    recommendedCabinets.innerHTML = html;
}
