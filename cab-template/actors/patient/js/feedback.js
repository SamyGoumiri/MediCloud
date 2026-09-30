// Rating buttons functionality
document.addEventListener('DOMContentLoaded', function() {
    const ratingGroups = document.querySelectorAll('.rating-buttons');
    
    ratingGroups.forEach(group => {
        const buttons = group.querySelectorAll('button[type="button"]');
        const ratingName = group.getAttribute('data-rating-group');
        const hiddenInput = document.querySelector(`input[name="${ratingName}"]`);
        
        buttons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons in this group
                buttons.forEach(btn => btn.classList.remove('active'));
                
                // Add active class to clicked button
                this.classList.add('active');
                
                // Set value in hidden input
                const value = this.getAttribute('data-value');
                hiddenInput.value = value;
            });
        });
    });
    
    // Form submission
    const form = document.getElementById('feedbackForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate all ratings are filled
        const requiredInputs = form.querySelectorAll('input[required]');
        let allFilled = true;
        
        requiredInputs.forEach(input => {
            if (!input.value) {
                allFilled = false;
            }
        });
        
        if (!allFilled) {
            alert('Please rate all aspects before submitting.');
            return;
        }
        
        // Collect form data
        const formData = new FormData(form);
        
        // Submit via AJAX
        fetch('../../DB/save_feedback.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Thank you for your feedback!');
                window.location.href = 'patient_consult.php';
            } else {
                alert('Error saving feedback: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error submitting feedback. Please try again.');
        });
    });
});
