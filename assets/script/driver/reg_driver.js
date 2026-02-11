document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('driverRegistrationForm');
    const steps = document.querySelectorAll('.step-content');
    const stepItems = document.querySelectorAll('.step-item');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    let currentStep = 1;
    const totalSteps = steps.length;

    // Initialize form
    function initForm() {
        showStep(1);
        updateStepIndicator();
        updateButtons();
    }

    // Show specific step
    function showStep(step) {
        steps.forEach((stepContent, index) => {
            if (index + 1 === step) {
                stepContent.classList.add('active');
            } else {
                stepContent.classList.remove('active');
            }
        });
        currentStep = step;
        updateStepIndicator();
        updateButtons();
        
        // Scroll to top of form
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Update step indicator
    function updateStepIndicator() {
        stepItems.forEach((item, index) => {
            const stepNum = index + 1;
            item.classList.remove('active', 'completed');
            
            if (stepNum < currentStep) {
                item.classList.add('completed');
            } else if (stepNum === currentStep) {
                item.classList.add('active');
            }
        });
    }

    // Update navigation buttons
    function updateButtons() {
        if (currentStep === 1) {
            prevBtn.style.display = 'none';
        } else {
            prevBtn.style.display = 'inline-block';
        }

        if (currentStep === totalSteps) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
        } else {
            nextBtn.style.display = 'inline-block';
            submitBtn.style.display = 'none';
        }
    }

    // Validate current step
    function validateStep(step) {
        const currentStepContent = steps[step - 1];
        const requiredFields = currentStepContent.querySelectorAll('[required]');
        let isValid = true;
        const firstInvalidField = [];

        requiredFields.forEach(field => {
            // Handle checkbox validation
            if (field.type === 'checkbox') {
                if (!field.checked) {
                    isValid = false;
                    field.classList.add('error');
                    if (firstInvalidField.length === 0) {
                        firstInvalidField.push(field);
                    }
                } else {
                    field.classList.remove('error');
                }
                return;
            }

            // Handle file input validation
            if (field.type === 'file') {
                if (field.files.length === 0) {
                    isValid = false;
                    field.classList.add('error');
                    if (firstInvalidField.length === 0) {
                        firstInvalidField.push(field);
                    }
                } else {
                    field.classList.remove('error');
                }
                return;
            }

            // Handle text, email, password, tel, etc.
            if (!field.value || !field.value.trim()) {
                isValid = false;
                field.classList.add('error');
                if (firstInvalidField.length === 0) {
                    firstInvalidField.push(field);
                }
            } else {
                field.classList.remove('error');
            }

            // Special validation for password confirmation
            if (field.id === 'confirm_password' && step === 1) {
                const password = document.getElementById('password').value;
                if (field.value !== password) {
                    isValid = false;
                    field.classList.add('error');
                    alert('Passwords do not match!');
                    if (firstInvalidField.length === 0) {
                        firstInvalidField.push(field);
                    }
                }
            }
        });

        // Focus first invalid field
        if (firstInvalidField.length > 0) {
            firstInvalidField[0].focus();
            firstInvalidField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return isValid;
    }

    // Next button click
    nextBtn.addEventListener('click', function() {
        if (validateStep(currentStep)) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }
    });

    // Previous button click
    prevBtn.addEventListener('click', function() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        // Validate all steps before submission
        let allValid = true;
        for (let i = 1; i <= totalSteps; i++) {
            if (!validateStep(i)) {
                allValid = false;
                showStep(i);
                break;
            }
        }

        if (!allValid) {
            e.preventDefault();
            return false;
        }

        // Password confirmation validation
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match!');
            showStep(1);
            return false;
        }

        // Validate license images
        const licenseFront = document.getElementById('license_image').files.length;
        const licenseBack = document.getElementById('license_back_image').files.length;
        
        if (!licenseFront || !licenseBack) {
            e.preventDefault();
            alert('Please upload both front and back license images!');
            showStep(4);
            return false;
        }
    });

    // Remove error class on input
    form.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('error');
        });
    });

    // Initialize form
    initForm();
});
