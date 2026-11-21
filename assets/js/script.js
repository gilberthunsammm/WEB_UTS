/**
 * Digital Library — Global Script
 * Mencakup: hamburger menu, pencarian buku, validasi form, toggle password
 */

/* ==========================================================
   1. HAMBURGER MENU (Mobile Navigation)
   ========================================================== */
(function () {
    const btn     = document.getElementById('hamburgerBtn');
    const navMenu = document.getElementById('navLinks');

    if (!btn || !navMenu) return;

    btn.addEventListener('click', function () {
        const isOpen = navMenu.classList.toggle('is-open');
        btn.classList.toggle('is-active', isOpen);
        btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Tutup menu jika klik di luar navbar
    document.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !navMenu.contains(e.target)) {
            navMenu.classList.remove('is-open');
            btn.classList.remove('is-active');
            btn.setAttribute('aria-expanded', 'false');
        }
    });

    // Tutup menu saat link navigasi diklik
    navMenu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            navMenu.classList.remove('is-open');
            btn.classList.remove('is-active');
            btn.setAttribute('aria-expanded', 'false');
        });
    });
}());


/* ==========================================================
   2. PENCARIAN BUKU REAL-TIME
   ========================================================== */
(function () {
    const searchInput = document.getElementById('searchInput');
    const bookGrid    = document.getElementById('bookGrid');
    const searchEmpty = document.getElementById('searchEmpty');

    if (!searchInput || !bookGrid) return;

    searchInput.addEventListener('input', function () {
        const keyword = this.value.trim().toLowerCase();
        const cards   = bookGrid.querySelectorAll('.book-card');
        let visibleCount = 0;

        cards.forEach(function (card) {
            const title  = card.dataset.title  || card.querySelector('h3')?.textContent.toLowerCase() || '';
            const author = card.dataset.author  || card.querySelector('.author')?.textContent.toLowerCase() || '';

            const matches = title.includes(keyword) || author.includes(keyword);
            card.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        // Tampilkan pesan kosong jika tidak ada hasil
        if (searchEmpty) {
            searchEmpty.style.display = (keyword !== '' && visibleCount === 0) ? 'block' : 'none';
        }
    });
}());


/* ==========================================================
   3. TOGGLE TAMPILKAN / SEMBUNYIKAN PASSWORD
   ========================================================== */
(function () {
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const targetId = this.dataset.target;
            const input    = document.getElementById(targetId);
            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                this.textContent = '🙈';
                this.setAttribute('aria-label', 'Sembunyikan password');
            } else {
                input.type = 'password';
                this.textContent = '👁';
                this.setAttribute('aria-label', 'Tampilkan password');
            }
        });
    });
}());


/* ==========================================================
   4. VALIDASI FORM REGISTRASI (Client-Side JavaScript)
   ========================================================== */
(function () {
    const form = document.getElementById('registerForm');
    if (!form) return;

    const nameInput     = document.getElementById('name');
    const emailInput    = document.getElementById('email');
    const passInput     = document.getElementById('password');
    const confirmInput  = document.getElementById('confirm_password');

    const nameError     = document.getElementById('nameError');
    const emailError    = document.getElementById('emailError');
    const passError     = document.getElementById('passwordError');
    const confirmError  = document.getElementById('confirmPasswordError');

    // Fungsi pembantu tampilkan / hapus error per field
    function showError(input, errorEl, msg) {
        if (!errorEl) return;
        errorEl.textContent = msg;
        if (input) {
            input.classList.add('input-error');
            input.classList.remove('input-valid');
        }
    }

    function clearError(input, errorEl) {
        if (!errorEl) return;
        errorEl.textContent = '';
        if (input) {
            input.classList.remove('input-error');
            input.classList.add('input-valid');
        }
    }

    // Validasi real-time konfirmasi password
    if (confirmInput && passInput && confirmError) {
        confirmInput.addEventListener('input', function () {
            if (this.value === '') {
                clearError(null, confirmError);
                confirmInput.classList.remove('input-error', 'input-valid');
                return;
            }
            if (this.value !== passInput.value) {
                showError(confirmInput, confirmError, 'Password tidak cocok.');
            } else {
                clearError(confirmInput, confirmError);
            }
        });

        passInput.addEventListener('input', function () {
            if (confirmInput.value !== '') {
                if (confirmInput.value !== this.value) {
                    showError(confirmInput, confirmError, 'Password tidak cocok.');
                } else {
                    clearError(confirmInput, confirmError);
                }
            }
            if (this.value.length > 0 && this.value.length < 6) {
                showError(passInput, passError, 'Password minimal 6 karakter.');
            } else if (this.value.length >= 6) {
                clearError(passInput, passError);
            } else {
                clearError(null, passError);
                passInput.classList.remove('input-error', 'input-valid');
            }
        });
    }

    // Validasi saat submit
    form.addEventListener('submit', function (e) {
        let valid = true;

        // Validasi nama
        if (nameInput && nameInput.value.trim() === '') {
            showError(nameInput, nameError, 'Nama lengkap wajib diisi.');
            valid = false;
        } else {
            clearError(nameInput, nameError);
        }

        // Validasi email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailInput) {
            if (emailInput.value.trim() === '') {
                showError(emailInput, emailError, 'Email wajib diisi.');
                valid = false;
            } else if (!emailRegex.test(emailInput.value.trim())) {
                showError(emailInput, emailError, 'Format email tidak valid.');
                valid = false;
            } else {
                clearError(emailInput, emailError);
            }
        }

        // Validasi password
        if (passInput) {
            if (passInput.value === '') {
                showError(passInput, passError, 'Password wajib diisi.');
                valid = false;
            } else if (passInput.value.length < 6) {
                showError(passInput, passError, 'Password minimal 6 karakter.');
                valid = false;
            } else {
                clearError(passInput, passError);
            }
        }

        // Validasi konfirmasi password
        if (confirmInput && passInput) {
            if (confirmInput.value === '') {
                showError(confirmInput, confirmError, 'Konfirmasi password wajib diisi.');
                valid = false;
            } else if (confirmInput.value !== passInput.value) {
                showError(confirmInput, confirmError, 'Password dan konfirmasi password tidak cocok.');
                valid = false;
            } else {
                clearError(confirmInput, confirmError);
            }
        }

        if (!valid) {
            e.preventDefault();
            // Gulir ke error pertama
            const firstError = form.querySelector('.input-error');
            if (firstError) firstError.focus();
        }
    });
}());


/* ==========================================================
   5. VALIDASI FORM LOGIN (Client-Side JavaScript)
   ========================================================== */
(function () {
    const form       = document.getElementById('loginForm');
    if (!form) return;

    const emailInput = document.getElementById('email');
    const passInput  = document.getElementById('password');
    const emailError = document.getElementById('emailError');
    const passError  = document.getElementById('passwordError');

    function showError(input, errorEl, msg) {
        if (!errorEl) return;
        errorEl.textContent = msg;
        if (input) input.classList.add('input-error');
    }

    function clearError(input, errorEl) {
        if (!errorEl) return;
        errorEl.textContent = '';
        if (input) input.classList.remove('input-error');
    }

    form.addEventListener('submit', function (e) {
        let valid = true;

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (emailInput) {
            if (emailInput.value.trim() === '') {
                showError(emailInput, emailError, 'Email wajib diisi.');
                valid = false;
            } else if (!emailRegex.test(emailInput.value.trim())) {
                showError(emailInput, emailError, 'Format email tidak valid.');
                valid = false;
            } else {
                clearError(emailInput, emailError);
            }
        }

        if (passInput && passInput.value === '') {
            showError(passInput, passError, 'Password wajib diisi.');
            valid = false;
        } else if (passInput) {
            clearError(passInput, passError);
        }

        if (!valid) e.preventDefault();
    });
}());
