// Handle gender selection
document.querySelectorAll('.gender-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.gender-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        this.classList.add('selected');
        document.getElementById('joinGender').value = this.dataset.gender;
    });
});

// Phone number validation
function validatePhoneNumber(phoneNumber) {
    return /^[0-9]{10}$/.test(phoneNumber);
}

function showPhoneValidation(inputId, validationId, isValid) {
    const validationElement = document.getElementById(validationId);
    if (!validationElement) return;

    if (isValid) {
        validationElement.textContent = 'Valid phone number';
        validationElement.className = 'phone-validation valid';
        validationElement.style.display = 'block';
    } else {
        validationElement.textContent = 'Enter a valid 10-digit mobile number';
        validationElement.className = 'phone-validation invalid';
        validationElement.style.display = 'block';
    }

    setTimeout(() => {
        validationElement.style.display = 'none';
    }, 3000);
}

// Word count functions
function countWords(text) {
    text = text.trim();
    if (text === '') return 0;
    return text.split(/\s+/).filter(word => word.length > 0).length;
}

function updateWordCount() {
    const textarea = document.getElementById('userOpinion');
    const wordCountElement = document.getElementById('wordCount');
    const wordLimitMessage = document.getElementById('wordLimitMessage');
    const submitBtn = document.getElementById('submitOpinionBtn');

    if (!textarea || !wordCountElement) return;

    const wordCount = countWords(textarea.value);
    wordCountElement.textContent = `Words: ${wordCount}/20`;

    if (wordCount > 20) {
        wordCountElement.classList.add('limit-reached');
        wordCountElement.classList.remove('warning', 'valid');
        wordLimitMessage.textContent = 'Maximum 20 words allowed!';
        wordLimitMessage.style.color = '#c0392b';
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.6';
        submitBtn.style.cursor = 'not-allowed';
    } else if (wordCount > 15) {
        wordCountElement.classList.add('warning');
        wordCountElement.classList.remove('limit-reached', 'valid');
        wordLimitMessage.textContent = `${20 - wordCount} words remaining`;
        wordLimitMessage.style.color = '#e74c3c';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    } else {
        wordCountElement.classList.add('valid');
        wordCountElement.classList.remove('warning', 'limit-reached');
        wordLimitMessage.textContent = wordCount === 0 ? 'Enter your opinion' : `${20 - wordCount} words remaining`;
        wordLimitMessage.style.color = wordCount === 0 ? '#666' : '#27ae60';
        submitBtn.disabled = false;
        submitBtn.style.opacity = '1';
        submitBtn.style.cursor = 'pointer';
    }
}

function validateOpinionForm(event) {
    const textarea = document.getElementById('userOpinion');
    const wordCount = countWords(textarea.value);

    if (wordCount > 20) {
        event.preventDefault();
        alert('Please limit your opinion to maximum 20 words. You have entered ' + wordCount + ' words.');
        textarea.focus();
        return false;
    }

    if (wordCount === 0) {
        event.preventDefault();
        alert('Please enter your opinion.');
        textarea.focus();
        return false;
    }

    return true;
}

function validateJoinForm(event) {
    const phoneInput = document.getElementById('joinPhone');
    const phoneNumber = phoneInput.value.trim();

    if (!validatePhoneNumber(phoneNumber)) {
        event.preventDefault();
        showPhoneValidation('joinPhone', 'joinPhoneValidation', false);
        phoneInput.focus();
        return false;
    }

    return true;
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Opinion textarea word count
    const opinionTextarea = document.getElementById('userOpinion');
    if (opinionTextarea) {
        opinionTextarea.addEventListener('input', updateWordCount);
        opinionTextarea.addEventListener('keyup', updateWordCount);
        opinionTextarea.addEventListener('paste', function() {
            setTimeout(updateWordCount, 10);
        });
        updateWordCount();

        const opinionForm = document.getElementById('opinionForm');
        if (opinionForm) {
            opinionForm.addEventListener('submit', validateOpinionForm);
        }
    }

    // Join form phone validation
    const joinPhoneInput = document.getElementById('joinPhone');
    if (joinPhoneInput) {
        joinPhoneInput.addEventListener('input', function() {
            if (this.value.trim().length === 10) {
                showPhoneValidation('joinPhone', 'joinPhoneValidation', validatePhoneNumber(this.value.trim()));
            }
        });
        joinPhoneInput.addEventListener('blur', function() {
            if (this.value.trim()) {
                showPhoneValidation('joinPhone', 'joinPhoneValidation', validatePhoneNumber(this.value.trim()));
            }
        });

        const joinForm = document.getElementById('joinForm');
        if (joinForm) {
            joinForm.addEventListener('submit', validateJoinForm);
        }
    }

    // Opinion form phone validation
    const opinionPhoneInput = document.getElementById('userPhone');
    if (opinionPhoneInput) {
        opinionPhoneInput.addEventListener('input', function() {
            if (this.value.trim().length === 10) {
                showPhoneValidation('userPhone', 'opinionPhoneValidation', validatePhoneNumber(this.value.trim()));
            }
        });
        opinionPhoneInput.addEventListener('blur', function() {
            if (this.value.trim()) {
                showPhoneValidation('userPhone', 'opinionPhoneValidation', validatePhoneNumber(this.value.trim()));
            }
        });
    }

    // Only allow numbers in phone fields
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });

    // Modal body scroll fix
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            document.body.classList.add('modal-open');
        });
        modal.addEventListener('hidden.bs.modal', function() {
            document.body.classList.remove('modal-open');
        });
    });

    // Auto remove alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert-message').forEach(alert => {
            alert.classList.remove('show');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        });
    }, 5000);
});
