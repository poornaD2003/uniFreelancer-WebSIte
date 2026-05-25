document.addEventListener('DOMContentLoaded', () => {
    const registerForm = document.querySelector('form[action="register.php"]');
    
    if (registerForm) {
        registerForm.addEventListener('submit', (e) => {
            let isValid = true;
            const password = registerForm.querySelector('input[name="password"]');
            const email = registerForm.querySelector('input[name="email"]');
            const fullname = registerForm.querySelector('input[name="fullname"]');

            // Simple Email Validation
            if (!email.value.includes('@') || !email.value.includes('.')) {
                showError(email, 'Please enter a valid email address');
                isValid = false;
            } else {
                hideError(email);
            }

            // Password Length Validation
            if (password.value.length < 6) {
                showError(password, 'Password must be at least 6 characters long');
                isValid = false;
            } else {
                hideError(password);
            }

            // Fullname validation
            if (fullname.value.trim() === '') {
                showError(fullname, 'Please enter your full name');
                isValid = false;
            } else {
                hideError(fullname);
            }

            if (!isValid) {
                e.preventDefault();
            }
        });
    }

    function showError(input, message) {
        const group = input.parentElement;
        let error = group.querySelector('.error-message');
        
        if (!error) {
            error = document.createElement('div');
            error.className = 'error-message';
            group.appendChild(error);
        }
        
        error.textContent = message;
        error.style.display = 'block';
        input.style.borderColor = '#ef4444';
    }

    function hideError(input) {
        const group = input.parentElement;
        const error = group.querySelector('.error-message');
        if (error) {
            error.style.display = 'none';
        }
        input.style.borderColor = '';
    }

    // Toggle for combined auth page (if still used)
    const showLoginBtn = document.getElementById('show-login');
    const showRegisterBtn = document.getElementById('show-register');
    const loginForm = document.getElementById('login-form');
    const registerFormToggle = document.getElementById('register-form');

    if (showLoginBtn && showRegisterBtn) {
        showLoginBtn.addEventListener('click', () => {
            loginForm.style.display = 'block';
            registerFormToggle.style.display = 'none';
            showLoginBtn.classList.add('btn-primary');
            showLoginBtn.classList.remove('btn-outline');
            showRegisterBtn.classList.add('btn-outline');
            showRegisterBtn.classList.remove('btn-primary');
        });

        showRegisterBtn.addEventListener('click', () => {
            loginForm.style.display = 'none';
            registerFormToggle.style.display = 'block';
            showRegisterBtn.classList.add('btn-primary');
            showRegisterBtn.classList.remove('btn-outline');
            showLoginBtn.classList.add('btn-outline');
            showLoginBtn.classList.remove('btn-primary');
        });
    }
});
