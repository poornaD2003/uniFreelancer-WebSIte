document.addEventListener('DOMContentLoaded', () => {
    // ── Form Validation (Register Page) ──
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

    // ── Login / Register Forms Toggle (Combined Page) ──
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

    // ── Navbar Scroll Reveal ──
    const nav = document.querySelector('nav');
    if (nav) {
        const handleNavbarScroll = () => {
            if (window.scrollY > 30) {
                nav.classList.add('nav-scrolled');
            } else {
                nav.classList.remove('nav-scrolled');
            }
        };
        // Init on load
        handleNavbarScroll();
        window.addEventListener('scroll', handleNavbarScroll);
    }

    // ── Stats Count Up Logic ──
    const countUp = (element) => {
        const target = parseFloat(element.getAttribute('data-target'));
        const duration = 1500; // 1.5 seconds
        const startTime = performance.now();
        const isFloat = element.getAttribute('data-target').includes('.');
        const suffix = element.getAttribute('data-suffix') || '';

        const updateCount = (currentTime) => {
            const elapsedTime = currentTime - startTime;
            if (elapsedTime < duration) {
                const progress = elapsedTime / duration;
                // Ease out cubic
                const easeProgress = 1 - Math.pow(1 - progress, 3);
                const currentValue = easeProgress * target;
                
                if (isFloat) {
                    element.textContent = currentValue.toFixed(1) + suffix;
                } else {
                    element.textContent = Math.floor(currentValue) + suffix;
                }
                requestAnimationFrame(updateCount);
            } else {
                if (isFloat) {
                    element.textContent = target.toFixed(1) + suffix;
                } else {
                    element.textContent = target + suffix;
                }
            }
        };
        requestAnimationFrame(updateCount);
    };

    // ── Scroll Reveal Intersection Observer ──
    const observerOptions = {
        root: null,
        rootMargin: '0px -10% -10% 0px', // Slightly inset to ensure clean trigger
        threshold: 0.1
    };

    const scrollObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                
                // If this is a stat number and has not been counted yet
                if (entry.target.classList.contains('stat-number') && !entry.target.classList.contains('counted')) {
                    entry.target.classList.add('counted');
                    countUp(entry.target);
                }
                
                // Unobserve since animations should only fire once on scroll-down
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Register all reveal items to observer
    const animElements = document.querySelectorAll('.hidden-anim');
    animElements.forEach(el => scrollObserver.observe(el));
});