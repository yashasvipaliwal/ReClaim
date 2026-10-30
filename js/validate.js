/**
 * js/validate.js
 * ─────────────────────────────────────────────────────────────
 * Client-side form validation for ReClaim.
 * NOTE: Client-side validation is only a UX convenience.
 * All inputs are also validated server-side (in PHP).
 *
 * Validates: login, register, report create/edit, claim forms.
 */

document.addEventListener('DOMContentLoaded', () => {

  // ── Helper: show/hide error ────────────────────────────────
  function showError(id, msg) {
    const el = document.getElementById(id);
    if (el) { el.textContent = msg; el.style.display = 'block'; }
    const input = document.getElementById(id.replace('Error', ''));
    if (input) input.classList.add('error');
  }

  function clearError(id) {
    const el = document.getElementById(id);
    if (el) { el.textContent = ''; el.style.display = 'none'; }
    const input = document.getElementById(id.replace('Error', ''));
    if (input) input.classList.remove('error');
  }

  function isEmail(str) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(str);
  }

  // ── Password strength indicator ────────────────────────────
  const passwordInput = document.getElementById('reg_password');
  const strengthBar   = document.getElementById('passwordStrength');

  if (passwordInput && strengthBar) {
    passwordInput.addEventListener('input', () => {
      const val = passwordInput.value;
      let score = 0;
      if (val.length >= 8)            score++;
      if (/[A-Z]/.test(val))          score++;
      if (/[0-9]/.test(val))          score++;
      if (/[^A-Za-z0-9]/.test(val))  score++;

      const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
      const colors = ['', 'var(--danger)', 'var(--warning)', 'var(--accent)', 'var(--success)'];

      strengthBar.className = 'password-strength strength-' + score;
      strengthBar.title = labels[score] || '';
      strengthBar.style.setProperty('--bar-color', colors[score] || '');
    });
  }

  // ── Inline validation on blur ──────────────────────────────
  document.querySelectorAll('.form-control[required]').forEach(input => {
    input.addEventListener('blur', () => {
      validateField(input);
    });
    input.addEventListener('input', () => {
      if (input.classList.contains('error')) validateField(input);
    });
  });

  function validateField(input) {
    const id  = input.id;
    const val = input.value.trim();

    if (!val && input.required) {
      showError(id + 'Error', 'This field is required.');
      return false;
    }

    if (input.type === 'email' && val && !isEmail(val)) {
      showError(id + 'Error', 'Please enter a valid email address.');
      return false;
    }

    if (input.minLength && val.length < input.minLength) {
      showError(id + 'Error', `Minimum ${input.minLength} characters required.`);
      return false;
    }

    clearError(id + 'Error');
    return true;
  }

  // ── Login Form Validation ──────────────────────────────────
  const loginForm = document.getElementById('loginForm');
  if (loginForm) {
    loginForm.addEventListener('submit', (e) => {
      let valid = true;

      const email    = document.getElementById('email');
      const password = document.getElementById('password');

      if (!email.value.trim()) {
        showError('emailError', 'Email is required.'); valid = false;
      } else if (!isEmail(email.value.trim())) {
        showError('emailError', 'Please enter a valid email address.'); valid = false;
      } else {
        clearError('emailError');
      }

      if (!password.value) {
        showError('passwordError', 'Password is required.'); valid = false;
      } else {
        clearError('passwordError');
      }

      if (!valid) e.preventDefault();
    });
  }

  // ── Register Form Validation ───────────────────────────────
  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    registerForm.addEventListener('submit', (e) => {
      let valid = true;

      // Name
      const name = document.getElementById('name');
      if (!name || name.value.trim().length < 2) {
        showError('nameError', 'Name must be at least 2 characters.'); valid = false;
      } else { clearError('nameError'); }

      // Email
      const email = document.getElementById('reg_email');
      if (!email || !isEmail(email.value.trim())) {
        showError('emailError', 'Please enter a valid email address.'); valid = false;
      } else { clearError('emailError'); }

      // Password
      const pw = document.getElementById('reg_password');
      if (!pw || pw.value.length < 8) {
        showError('reg_passwordError', 'Password must be at least 8 characters.'); valid = false;
      } else { clearError('reg_passwordError'); }

      // Confirm
      const cpw = document.getElementById('confirm_password');
      if (!cpw || cpw.value !== (pw ? pw.value : '')) {
        showError('confirmPasswordError', 'Passwords do not match.'); valid = false;
      } else { clearError('confirmPasswordError'); }

      if (!valid) e.preventDefault();
    });
  }

  // ── Report Form Validation ─────────────────────────────────
  const reportForm = document.getElementById('reportForm');
  if (reportForm) {
    reportForm.addEventListener('submit', (e) => {
      let valid = true;

      // Report type
      const typeInputs = reportForm.querySelectorAll('input[name=type]');
      const typeChecked = [...typeInputs].some(r => r.checked);
      if (!typeChecked) {
        showError('typeError', 'Please select Lost or Found.'); valid = false;
      } else { clearError('typeError'); }

      // Title
      const title = document.getElementById('title');
      if (!title || title.value.trim().length < 3) {
        showError('titleError', 'Title must be at least 3 characters.'); valid = false;
      } else { clearError('titleError'); }

      // Category
      const category = document.getElementById('category');
      if (!category || !category.value) {
        showError('categoryError', 'Please select a category.'); valid = false;
      } else { clearError('categoryError'); }

      // Date
      const dateField = document.getElementById('incident_date');
      if (!dateField || !dateField.value) {
        showError('incident_dateError', 'Please provide an incident date.'); valid = false;
      } else if (new Date(dateField.value) > new Date()) {
        showError('incident_dateError', 'Date cannot be in the future.'); valid = false;
      } else { clearError('incident_dateError'); }

      // Image file size (client-side pre-check)
      const imageInput = document.getElementById('image');
      if (imageInput && imageInput.files.length > 0) {
        const file = imageInput.files[0];
        if (file.size > 5 * 1024 * 1024) {
          showError('imageError', 'Image must be smaller than 5 MB.'); valid = false;
        } else if (!['image/jpeg','image/png','image/webp','image/gif'].includes(file.type)) {
          showError('imageError', 'Only JPEG, PNG, WebP, and GIF images are allowed.'); valid = false;
        } else { clearError('imageError'); }
      }

      if (!valid) {
        e.preventDefault();
        // Scroll to first error
        const firstError = reportForm.querySelector('.form-error:not(:empty)');
        if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    });
  }

  // ── Claim Form Validation ──────────────────────────────────
  const claimForm = document.getElementById('claimForm');
  if (claimForm) {
    claimForm.addEventListener('submit', (e) => {
      const detail = document.getElementById('verification_detail');
      if (!detail || detail.value.trim().length < 20) {
        showError('verification_detailError', 'Please provide at least 20 characters of ownership proof.');
        e.preventDefault();
      } else {
        clearError('verification_detailError');
      }
    });
  }

  // ── Disable submit button after click (prevent double submit) ─
  document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', () => {
      setTimeout(() => {
        const btn = form.querySelector('[type=submit]');
        if (btn) {
          btn.disabled = true;
          btn.textContent = '⏳ Submitting…';
        }
      }, 0);
    });
  });

});
