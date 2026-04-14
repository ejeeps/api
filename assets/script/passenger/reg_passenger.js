document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('passengerRegistrationForm');
    const steps = document.querySelectorAll('.step-content');
    const stepItems = document.querySelectorAll('.step-item');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    const phoneInput = document.getElementById('phone_number');
    const phoneErrorEl = document.getElementById('phone_number_error');

    if (!form || !steps.length || !prevBtn || !nextBtn || !submitBtn) {
        return;
    }

    let currentStep = 1;
    const totalSteps = steps.length;
    const STEP_STORAGE_KEY = 'ejeep_passenger_reg_step';
    const DRAFT_STORAGE_KEY = 'ejeep_passenger_reg_draft';
    /** File inputs restored via draft.__bin (data URLs) after reload */
    const FILE_DRAFT_IDS = ['id_image_front', 'id_image_back', 'profile_image'];
    let draftSaveTimer = null;

    function persistStep(step) {
        try {
            sessionStorage.setItem(STEP_STORAGE_KEY, String(step));
        } catch (err) {
            /* private mode / quota */
        }
    }

    function readSavedStep() {
        try {
            const raw = sessionStorage.getItem(STEP_STORAGE_KEY);
            const n = parseInt(raw || '', 10);
            if (!isNaN(n) && n >= 1 && n <= totalSteps) {
                return n;
            }
        } catch (err) {
            /* ignore */
        }
        return 1;
    }

    function clearSavedStep() {
        try {
            sessionStorage.removeItem(STEP_STORAGE_KEY);
        } catch (err) {
            /* ignore */
        }
    }

    function loadDraftObject() {
        try {
            const raw = sessionStorage.getItem(DRAFT_STORAGE_KEY);
            if (!raw) {
                return {};
            }
            const o = JSON.parse(raw);
            return typeof o === 'object' && o !== null ? o : {};
        } catch (err) {
            return {};
        }
    }

    function clearDraft() {
        try {
            sessionStorage.removeItem(DRAFT_STORAGE_KEY);
        } catch (err) {
            /* ignore */
        }
    }

    function persistDraftPayload(data) {
        try {
            sessionStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(data));
        } catch (err) {
            if (data.__bin) {
                delete data.__bin;
                try {
                    sessionStorage.setItem(DRAFT_STORAGE_KEY, JSON.stringify(data));
                } catch (err2) {
                    /* private mode / quota */
                }
            }
        }
    }

    function saveDraftFromFormImmediate() {
        const data = {};
        form.querySelectorAll('input, select, textarea').forEach((el) => {
            const key = el.name || el.id;
            if (!key) {
                return;
            }
            if (el.type === 'file') {
                return;
            }
            if (el.type === 'checkbox') {
                data[key] = el.checked;
                return;
            }
            data[key] = el.value;
        });

        const readers = [];
        FILE_DRAFT_IDS.forEach((fid) => {
            const el = document.getElementById(fid);
            if (!el || el.type !== 'file' || !el.files || !el.files[0]) {
                return;
            }
            readers.push(
                new Promise((resolve) => {
                    const r = new FileReader();
                    r.onload = () => resolve({ fid, result: r.result });
                    r.onerror = () => resolve({ fid, result: null });
                    r.readAsDataURL(el.files[0]);
                })
            );
        });

        if (readers.length === 0) {
            delete data.__bin;
            persistDraftPayload(data);
            return;
        }

        Promise.all(readers).then((items) => {
            const bin = {};
            items.forEach(({ fid, result }) => {
                if (result) {
                    bin[fid] = result;
                }
            });
            if (Object.keys(bin).length) {
                data.__bin = bin;
            } else {
                delete data.__bin;
            }
            persistDraftPayload(data);
        });
    }

    function restoreDraftFiles(draft) {
        if (!draft || !draft.__bin || typeof draft.__bin !== 'object') {
            return Promise.resolve();
        }
        const bin = draft.__bin;
        const promises = [];
        FILE_DRAFT_IDS.forEach((fid) => {
            const dataUrl = bin[fid];
            if (!dataUrl || typeof dataUrl !== 'string' || dataUrl.indexOf('data:') !== 0) {
                return;
            }
            const el = document.getElementById(fid);
            if (!el || el.type !== 'file') {
                return;
            }
            promises.push(
                fetch(dataUrl)
                    .then((r) => r.blob())
                    .then((blob) => {
                        const ext = blob.type && blob.type.indexOf('png') !== -1 ? 'png' : 'jpg';
                        const file = new File([blob], fid + '-draft.' + ext, {
                            type: blob.type || 'image/jpeg',
                        });
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        el.files = dt.files;
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    })
                    .catch(() => {})
            );
        });
        return Promise.all(promises);
    }

    function scheduleDraftSave() {
        clearTimeout(draftSaveTimer);
        draftSaveTimer = setTimeout(saveDraftFromFormImmediate, 300);
    }

    function applyDraftFields(draft, options) {
        const opts = options || {};
        const skipProvinceCity = !!opts.skipProvinceCity;
        if (!draft || typeof draft !== 'object') {
            return;
        }
        form.querySelectorAll('input, select, textarea').forEach((el) => {
            const key = el.name || el.id;
            if (!key || !(key in draft)) {
                return;
            }
            if (skipProvinceCity && (key === 'province' || key === 'city')) {
                return;
            }
            if (el.type === 'file') {
                return;
            }
            if (el.type === 'checkbox') {
                el.checked = !!draft[key];
                return;
            }
            el.value = draft[key];
        });
    }

    /** Match PassengerRegController: strip non-digits, optional 63 / leading 0 */
    function normalizePhilippinesPhoneDigits(value) {
        let d = String(value || '').replace(/\D/g, '');
        if (d.length === 12 && d.slice(0, 2) === '63') {
            d = d.slice(2);
        }
        if (d.length >= 11 && d[0] === '0') {
            d = d.slice(1);
        }
        return d;
    }

    function getPhilippinesPhoneFormatMessage(value) {
        const raw = String(value || '').trim();
        if (!raw) {
            return null;
        }
        const d = normalizePhilippinesPhoneDigits(raw);
        if (d.length !== 10) {
            return 'Enter exactly 10 digits for your mobile number (after +63).';
        }
        if (d[0] !== '9') {
            return 'Philippines mobile numbers must start with 9.';
        }
        return null;
    }

    function setPhoneFieldError(message) {
        if (!phoneInput || !phoneErrorEl) {
            return;
        }
        if (message) {
            phoneErrorEl.textContent = message;
            phoneErrorEl.hidden = false;
            phoneInput.setAttribute('aria-invalid', 'true');
        } else {
            phoneErrorEl.textContent = '';
            phoneErrorEl.hidden = true;
            phoneInput.removeAttribute('aria-invalid');
        }
    }

    // Initialize form (restore last step + draft after reload)
    function initForm() {
        showStep(readSavedStep(), { scrollSmooth: false });
        const draft = loadDraftObject();
        applyDraftFields(draft, { skipProvinceCity: true });
        restoreDraftFiles(draft).finally(() => saveDraftFromFormImmediate());
    }

    // Show specific step
    function showStep(step, options) {
        const scrollSmooth = !options || options.scrollSmooth !== false;
        steps.forEach((stepContent, index) => {
            if (index + 1 === step) {
                stepContent.classList.add('active');
            } else {
                stepContent.classList.remove('active');
            }
        });
        currentStep = step;
        persistStep(step);
        updateStepIndicator();
        updateButtons();

        form.scrollIntoView({ behavior: scrollSmooth ? 'smooth' : 'auto', block: 'start' });
    }

    function navigateToStep(step, options) {
        showStep(step, options);
        saveDraftFromFormImmediate();
    }

    // Update step indicator
    function updateStepIndicator() {
        stepItems.forEach((item, index) => {
            const stepNum = index + 1;
            item.classList.remove('active', 'completed', 'step-item--navigable');
            
            if (stepNum < currentStep) {
                item.classList.add('completed');
            } else if (stepNum === currentStep) {
                item.classList.add('active');
            }
            if (stepNum <= currentStep) {
                item.classList.add('step-item--navigable');
                item.setAttribute('tabindex', '0');
            } else {
                item.setAttribute('tabindex', '-1');
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

        if (step === 1) {
            const emailField = document.getElementById('email');
            if (emailField && emailField.getAttribute('data-email-status') === 'taken') {
                isValid = false;
                emailField.classList.add('error');
                if (firstInvalidField.length === 0) {
                    firstInvalidField.push(emailField);
                }
            }
        }

        if (step === 2 && phoneInput) {
            const phoneMsg = getPhilippinesPhoneFormatMessage(phoneInput.value);
            if (phoneMsg) {
                isValid = false;
                phoneInput.classList.add('error');
                setPhoneFieldError(phoneMsg);
                if (firstInvalidField.length === 0) {
                    firstInvalidField.push(phoneInput);
                }
            } else {
                setPhoneFieldError('');
            }
        }

        // Focus first invalid field
        if (firstInvalidField.length > 0) {
            firstInvalidField[0].focus();
            firstInvalidField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        return isValid;
    }

    // Next button click
    nextBtn.addEventListener('click', async function() {
        if (currentStep === 1 && typeof window.ensureRegistrationEmailAvailable === 'function') {
            const emailEl = document.getElementById('email');
            const ok = await window.ensureRegistrationEmailAvailable(emailEl);
            if (!ok) {
                return;
            }
        }
        if (validateStep(currentStep)) {
            if (currentStep < totalSteps) {
                navigateToStep(currentStep + 1);
            }
        }
    });

    // Previous button click
    prevBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (currentStep > 1) {
            navigateToStep(currentStep - 1);
        }
    });

    // Click / keyboard on step indicator: jump to current or earlier steps only
    stepItems.forEach((item, index) => {
        const stepNum = index + 1;
        item.addEventListener('click', function() {
            if (stepNum <= currentStep) {
                navigateToStep(stepNum);
            }
        });
        item.addEventListener('keydown', function(e) {
            if (stepNum <= currentStep && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                navigateToStep(stepNum);
            }
        });
    });

    document.addEventListener('ejeep:passenger-provinces-ready', function(ev) {
        const draft = loadDraftObject();
        if (!draft || !draft.province) {
            return;
        }
        const detail = ev.detail || {};
        const provinceSel = detail.provinceSel;
        const citySel = detail.citySel;
        const loadCities = detail.loadCities;
        if (!provinceSel || !citySel || typeof loadCities !== 'function') {
            return;
        }
        let matched = false;
        for (let i = 0; i < provinceSel.options.length; i++) {
            if (provinceSel.options[i].value === draft.province) {
                provinceSel.selectedIndex = i;
                matched = true;
                break;
            }
        }
        if (!matched) {
            return;
        }
        const opt = provinceSel.selectedOptions[0];
        const code = opt ? opt.getAttribute('data-code') : '';
        if (!code) {
            return;
        }
        loadCities(code).then(function() {
            if (draft.city) {
                citySel.value = draft.city;
            }
        });
    }, { once: true });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        const emailEl = document.getElementById('email');
        if (emailEl && emailEl.getAttribute('data-email-status') === 'taken') {
            e.preventDefault();
            navigateToStep(1);
            emailEl.focus();
            return false;
        }

        // Validate all steps before submission
        let allValid = true;
        for (let i = 1; i <= totalSteps; i++) {
            if (!validateStep(i)) {
                allValid = false;
                navigateToStep(i);
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
            navigateToStep(1);
            return false;
        }

        // Validate password length
        if (password.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long!');
            navigateToStep(1);
            return false;
        }

        // Validate ID images
        const idFront = document.getElementById('id_image_front').files.length;
        const idBack = document.getElementById('id_image_back').files.length;
        
        if (!idFront || !idBack) {
            e.preventDefault();
            alert('Please upload both front and back ID images!');
            navigateToStep(4);
            return false;
        }

        clearSavedStep();
        clearDraft();
    });

    const cancelLink = form.querySelector('a.btn-cancel');
    if (cancelLink) {
        cancelLink.addEventListener('click', function() {
            clearSavedStep();
            clearDraft();
        });
    }

    form.addEventListener('input', scheduleDraftSave);
    form.addEventListener('change', scheduleDraftSave);

    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            clearTimeout(draftSaveTimer);
            saveDraftFromFormImmediate();
        }
    });

    if (phoneInput) {
        phoneInput.addEventListener('blur', function() {
            const msg = getPhilippinesPhoneFormatMessage(phoneInput.value);
            if (msg) {
                phoneInput.classList.add('error');
                setPhoneFieldError(msg);
            } else {
                phoneInput.classList.remove('error');
                setPhoneFieldError('');
            }
        });
    }

    // Remove error class on input
    form.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('error');
            if (this.id === 'phone_number') {
                setPhoneFieldError('');
            }
        });
    });

    // Initialize form
    initForm();
});
