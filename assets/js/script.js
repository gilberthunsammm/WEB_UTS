/**
 * Digital Library — Premium Interactive Script v3.0
 * 
 * Modules:
 * 1. Navbar (scroll state, hamburger)
 * 2. Reading Progress Bar
 * 3. Book 3D Tilt Effect
 * 4. Category Filter Chips
 * 5. Live Search with Empty State
 * 6. Book Drawer (Quick View)
 * 7. Toast Notification System
 * 8. Alert Auto-Dismiss
 * 9. Toggle Password Visibility
 * 10. Form Validation (Register + Login)
 * 11. Stat Counter Animation
 * 12. prefers-reduced-motion guard
 */

/* eslint-disable */
(function () {
  'use strict';

  // ── Reduced Motion Guard ──────────────────────────────────
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ── 1. Navbar ─────────────────────────────────────────────
  const navbar      = document.querySelector('.navbar');
  const hamburger   = document.getElementById('hamburgerBtn');
  const mobileMenu  = document.getElementById('mobileMenu');

  if (navbar) {
    window.addEventListener('scroll', function () {
      navbar.classList.toggle('scrolled', window.scrollY > 10);
    }, { passive: true });
  }

  if (hamburger && mobileMenu) {
    hamburger.addEventListener('click', function () {
      const isOpen = mobileMenu.classList.toggle('open');
      hamburger.classList.toggle('open', isOpen);
      hamburger.setAttribute('aria-expanded', isOpen);
    });

    document.addEventListener('click', function (e) {
      if (!hamburger.contains(e.target) && !mobileMenu.contains(e.target)) {
        mobileMenu.classList.remove('open');
        hamburger.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
      }
    });

    mobileMenu.querySelectorAll('a, button').forEach(function (el) {
      el.addEventListener('click', function () {
        mobileMenu.classList.remove('open');
        hamburger.classList.remove('open');
      });
    });
  }

  // ── 2. Reading Progress Bar ───────────────────────────────
  const progressBar = document.getElementById('readingProgress');

  if (progressBar && !prefersReducedMotion) {
    window.addEventListener('scroll', function () {
      const scrollTop  = window.scrollY;
      const docHeight  = document.documentElement.scrollHeight - window.innerHeight;
      const pct        = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
      progressBar.style.width = Math.min(pct, 100) + '%';
    }, { passive: true });
  }

  // ── 3. Book 3D Tilt Effect ────────────────────────────────
  if (!prefersReducedMotion && !('ontouchstart' in window)) {

    document.querySelectorAll('.book-card').forEach(function (card) {
      const coverWrap = card.querySelector('.book-cover-wrap');
      if (!coverWrap) return;

      card.addEventListener('mousemove', function (e) {
        const rect   = card.getBoundingClientRect();
        const cx     = rect.left + rect.width  / 2;
        const cy     = rect.top  + rect.height / 2;
        const dx     = (e.clientX - cx) / (rect.width  / 2);   // -1 to 1
        const dy     = (e.clientY - cy) / (rect.height / 2);   // -1 to 1
        const tiltX  = -(dy * 6);   // max 6°
        const tiltY  =  (dx * 8);   // max 8°

        card.style.transform = `
          perspective(900px)
          translateY(-6px)
          rotateX(${tiltX}deg)
          rotateY(${tiltY}deg)
        `;
        card.style.boxShadow = `
          ${-dx * 8}px ${12 + Math.abs(dy) * 6}px 40px rgba(28,25,23,.14),
          ${-dx * 4}px ${6 + Math.abs(dy) * 3}px 16px rgba(28,25,23,.07)
        `;
      });

      card.addEventListener('mouseleave', function () {
        card.style.transform = '';
        card.style.boxShadow = '';
      });
    });
  }

  // ── 4. Category Filter Chips ─────────────────────────────
  const filterRow = document.getElementById('filterRow');

  if (filterRow) {
    filterRow.addEventListener('click', function (e) {
      const chip = e.target.closest('.filter-chip');
      if (!chip) return;

      filterRow.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');

      const filter = chip.dataset.filter;
      const books  = document.querySelectorAll('.book-card');
      let visible  = 0;

      books.forEach(function (book) {
        const cat = book.dataset.category || '';
        const show = filter === 'all' || cat === filter;

        if (prefersReducedMotion) {
          book.style.display = show ? '' : 'none';
        } else {
          if (show) {
            book.style.display = '';
            requestAnimationFrame(() => {
              book.style.opacity  = '1';
              book.style.transform = '';
            });
          } else {
            book.style.opacity  = '0';
            book.style.transform = 'scale(0.95)';
            setTimeout(() => { if (book.style.opacity === '0') book.style.display = 'none'; }, 200);
          }
        }
        if (show) visible++;
      });

      triggerEmptyState(visible === 0 && filter !== 'all');
    });

    // Apply transition style to all cards
    document.querySelectorAll('.book-card').forEach(function (card) {
      if (!prefersReducedMotion) {
        card.style.transition = 'opacity .2s ease, transform .2s ease, box-shadow .2s ease';
      }
    });
  }

  // ── 5. Live Search ───────────────────────────────────────
  const searchInput = document.getElementById('searchInput');
  const bookGrid    = document.getElementById('bookGrid');
  const emptySearch = document.getElementById('emptySearch');

  if (searchInput && bookGrid) {
    searchInput.addEventListener('input', debounce(function () {
      const kw      = this.value.trim().toLowerCase();
      const cards   = bookGrid.querySelectorAll('.book-card');
      let   visible = 0;

      cards.forEach(function (card) {
        const title    = (card.dataset.title   || '').toLowerCase();
        const author   = (card.dataset.author  || '').toLowerCase();
        const category = (card.dataset.category || '').toLowerCase();
        const match    = !kw || title.includes(kw) || author.includes(kw) || category.includes(kw);

        card.style.display = match ? '' : 'none';
        if (match) visible++;
      });

      triggerEmptyState(visible === 0 && kw !== '');

      // Reset filter chips when searching
      if (kw && filterRow) {
        filterRow.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        const allChip = filterRow.querySelector('[data-filter="all"]');
        if (allChip) allChip.classList.add('active');
      }
    }, 150));
  }

  function triggerEmptyState(show) {
    if (!emptySearch) return;
    emptySearch.style.display = show ? 'block' : 'none';
    if (bookGrid) bookGrid.style.display = show ? 'none' : '';
  }

  // ── 6. Book Drawer ───────────────────────────────────────
  const backdrop      = document.getElementById('drawerBackdrop');
  const drawer        = document.getElementById('bookDrawer');
  const drawerClose   = document.getElementById('drawerClose');

  // Elements populated by JS
  const dCover        = document.getElementById('dCover');
  const dCoverEyebrow = document.getElementById('dCoverEyebrow');
  const dCoverTitle   = document.getElementById('dCoverTitle');
  const dCoverAuthor  = document.getElementById('dCoverAuthor');
  const dTitle        = document.getElementById('dTitle');
  const dAuthor       = document.getElementById('dAuthor');
  const dCategory     = document.getElementById('dCategory');
  const dStatus       = document.getElementById('dStatus');
  const dActions      = document.getElementById('dActions');

  function openDrawer(data) {
    if (!drawer || !backdrop) return;

    // Set cover palette
    if (dCover) {
      dCover.className = 'drawer-cover-art palette-' + (data.palette || '1') + ' cover-title-art';
    }

    // Populate content
    if (dCoverEyebrow)  dCoverEyebrow.textContent = data.category || '';
    if (dCoverTitle)    dCoverTitle.textContent    = data.title    || '';
    if (dCoverAuthor)   dCoverAuthor.textContent   = data.author   || '';
    if (dTitle)         dTitle.textContent         = data.title    || '';
    if (dAuthor)        dAuthor.textContent        = data.author   || '';
    if (dCategory)      dCategory.textContent      = data.category || '';

    // Status badge
    if (dStatus) {
      const avail = data.status === 'Tersedia';
      dStatus.innerHTML = `<span class="badge ${avail ? 'badge-available' : 'badge-borrowed'}">${data.status}</span>`;
    }

    // Borrow action form
    if (dActions && data.bookId) {
      const avail = data.status === 'Tersedia';
      if (avail) {
        dActions.innerHTML = `
          <form method="POST" action="index.php#katalog">
            <input type="hidden" name="action" value="borrow">
            <input type="hidden" name="book_id" value="${escHtml(data.bookId)}">
            <button type="submit" class="btn btn-primary btn-lg btn-full">
              📖 Pinjam Buku Ini
            </button>
          </form>`;
      } else {
        dActions.innerHTML = `
          <div class="alert alert-warning">
            <span class="alert-icon">⏳</span>
            <span>Buku ini sedang dipinjam oleh member lain.</span>
          </div>`;
      }
    }

    // Open
    document.body.style.overflow = 'hidden';
    backdrop.classList.add('open');
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    drawerClose && drawerClose.focus();
  }

  function closeDrawer() {
    if (!drawer || !backdrop) return;
    document.body.style.overflow = '';
    backdrop.classList.remove('open');
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
  }

  // Trigger open from "Lihat Detail" buttons
  document.querySelectorAll('.btn-quick-view').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      openDrawer({
        bookId:   btn.dataset.bookId,
        title:    btn.dataset.title,
        author:   btn.dataset.author,
        category: btn.dataset.category,
        status:   btn.dataset.status,
        palette:  btn.dataset.palette,
      });
    });
  });

  if (drawerClose)  drawerClose.addEventListener('click',  closeDrawer);
  if (backdrop)     backdrop.addEventListener('click',     closeDrawer);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && drawer && drawer.classList.contains('open')) {
      closeDrawer();
    }
  });

  // ── 7. Toast Notification System ─────────────────────────
  const toastContainer = document.getElementById('toastContainer');

  window.showToast = function (message, type, duration) {
    if (!toastContainer) return;
    type     = type     || 'default';
    duration = duration || 4000;

    const icons = { success: '✓', error: '✕', warning: '⚠', default: 'ℹ' };
    const icon  = icons[type] || icons.default;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.setAttribute('role', 'status');
    toast.innerHTML = `
      <span class="toast-icon">${icon}</span>
      <span class="toast-text">${escHtml(message)}</span>
      <div class="toast-progress"></div>`;

    toastContainer.appendChild(toast);

    setTimeout(function () {
      toast.classList.add('hiding');
      toast.addEventListener('animationend', () => toast.remove(), { once: true });
    }, duration);
  };

  // ── 8. Alert Auto-Dismiss ────────────────────────────────
  document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alert) {
    const bar = document.createElement('div');
    bar.className = 'alert-dismiss-bar';
    alert.appendChild(bar);
    setTimeout(function () {
      alert.style.transition = 'opacity .4s ease, max-height .4s ease';
      alert.style.opacity = '0';
      alert.style.maxHeight = '0';
      setTimeout(() => alert.remove(), 400);
    }, 5000);
  });

  // Convert existing PHP flash messages to toasts (optional)
  document.querySelectorAll('.alert[data-toast]').forEach(function (alert) {
    const type = alert.dataset.toast;
    const text = alert.textContent.trim();
    alert.remove();
    window.showToast && window.showToast(text, type);
  });

  // ── 9. Toggle Password Visibility ────────────────────────
  document.querySelectorAll('.pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const targetId = btn.dataset.target;
      const input    = document.getElementById(targetId);
      if (!input) return;
      const isText   = input.type === 'text';
      input.type     = isText ? 'password' : 'text';
      btn.textContent = isText ? '👁' : '🙈';
      btn.setAttribute('aria-label', isText ? 'Tampilkan password' : 'Sembunyikan password');
    });
  });

  // ── 10. Form Validation ───────────────────────────────────

  // Register form
  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    const fName    = document.getElementById('name');
    const fEmail   = document.getElementById('email');
    const fPass    = document.getElementById('password');
    const fConfirm = document.getElementById('confirm_password');

    function getErr(id) { return document.getElementById(id + 'Error'); }

    function markField(input, errEl, msg) {
      if (msg) {
        input && input.classList.add('error');
        input && input.classList.remove('valid');
        errEl && (errEl.textContent = msg);
      } else {
        input && input.classList.remove('error');
        input && input.classList.add('valid');
        errEl && (errEl.textContent = '');
      }
    }

    // Real-time confirm password check
    if (fConfirm && fPass) {
      fConfirm.addEventListener('input', function () {
        if (!this.value) { markField(fConfirm, getErr('confirm_password'), ''); return; }
        markField(fConfirm, getErr('confirm_password'),
          this.value !== fPass.value ? 'Password tidak cocok.' : '');
      });

      fPass.addEventListener('input', function () {
        if (fConfirm.value) {
          markField(fConfirm, getErr('confirm_password'),
            fConfirm.value !== this.value ? 'Password tidak cocok.' : '');
        }
        markField(fPass, getErr('password'),
          this.value.length > 0 && this.value.length < 6 ? 'Minimal 6 karakter.' : '');
      });
    }

    registerForm.addEventListener('submit', function (e) {
      let ok = true;
      const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!fName || !fName.value.trim()) {
        markField(fName, getErr('name'), 'Nama wajib diisi.');
        ok = false;
      } else {
        markField(fName, getErr('name'), '');
      }

      if (!fEmail || !emailRe.test(fEmail.value.trim())) {
        markField(fEmail, getErr('email'), fEmail && fEmail.value.trim() ? 'Format email tidak valid.' : 'Email wajib diisi.');
        ok = false;
      } else {
        markField(fEmail, getErr('email'), '');
      }

      if (!fPass || fPass.value.length < 6) {
        markField(fPass, getErr('password'), fPass && fPass.value ? 'Minimal 6 karakter.' : 'Password wajib diisi.');
        ok = false;
      } else {
        markField(fPass, getErr('password'), '');
      }

      if (!fConfirm || !fConfirm.value) {
        markField(fConfirm, getErr('confirm_password'), 'Konfirmasi password wajib diisi.');
        ok = false;
      } else if (fPass && fConfirm.value !== fPass.value) {
        markField(fConfirm, getErr('confirm_password'), 'Password tidak cocok.');
        ok = false;
      } else {
        markField(fConfirm, getErr('confirm_password'), '');
      }

      if (!ok) {
        e.preventDefault();
        const firstError = registerForm.querySelector('.error');
        firstError && firstError.focus();
      }
    });
  }

  // Login form
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    const lEmail = document.getElementById('email');
    const lPass  = document.getElementById('password');

    loginForm.addEventListener('submit', function (e) {
      let ok = true;
      const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      const getErr  = id => document.getElementById(id + 'Error');

      function markF(input, errEl, msg) {
        input && input.classList.toggle('error', !!msg);
        errEl  && (errEl.textContent = msg || '');
      }

      if (!lEmail || !emailRe.test(lEmail.value.trim())) {
        markF(lEmail, getErr('email'), lEmail && lEmail.value.trim() ? 'Format email tidak valid.' : 'Email wajib diisi.');
        ok = false;
      } else {
        markF(lEmail, getErr('email'), '');
      }

      if (!lPass || !lPass.value) {
        markF(lPass, getErr('password'), 'Password wajib diisi.');
        ok = false;
      } else {
        markF(lPass, getErr('password'), '');
      }

      if (!ok) e.preventDefault();
    });
  }

  // ── 11. Animated Stat Counter ─────────────────────────────
  if (!prefersReducedMotion) {
    const counters = document.querySelectorAll('[data-count]');
    if (counters.length) {
      const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          const el     = entry.target;
          const target = parseInt(el.dataset.count, 10);
          const dur    = 800;
          const start  = performance.now();

          function step(now) {
            const pct  = Math.min((now - start) / dur, 1);
            const ease = 1 - Math.pow(1 - pct, 3);
            el.textContent = Math.round(ease * target);
            if (pct < 1) requestAnimationFrame(step);
          }
          requestAnimationFrame(step);
          observer.unobserve(el);
        });
      }, { threshold: .5 });

      counters.forEach(c => observer.observe(c));
    }
  }

  // ── Utilities ─────────────────────────────────────────────
  function debounce(fn, wait) {
    let t;
    return function () {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, arguments), wait);
    };
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }

}());
