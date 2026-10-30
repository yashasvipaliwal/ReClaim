/**
 * js/main.js
 * ─────────────────────────────────────────────────────────────
 * Main JavaScript for ReClaim.
 * Handles: navigation, dropdown, AJAX live search,
 *          upload preview, char counter, type-toggle,
 *          filter panel (mobile).
 *
 * No external dependencies — plain Vanilla JS.
 */

document.addEventListener('DOMContentLoaded', () => {

  // ── 1. Mobile Hamburger Menu ──────────────────────────────
  const hamburger  = document.getElementById('hamburgerBtn');
  const navLinks   = document.getElementById('navLinks');

  if (hamburger && navLinks) {
    hamburger.addEventListener('click', () => {
      const open = navLinks.classList.toggle('open');
      hamburger.setAttribute('aria-expanded', open);
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
        navLinks.classList.remove('open');
        hamburger.setAttribute('aria-expanded', false);
      }
    });
  }

  // ── 2. User Dropdown ─────────────────────────────────────
  const dropdownBtn  = document.getElementById('userDropdownBtn');
  const dropdownMenu = document.getElementById('userDropdownMenu');

  if (dropdownBtn && dropdownMenu) {
    dropdownBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdownMenu.classList.toggle('open');
    });

    document.addEventListener('click', () => {
      dropdownMenu.classList.remove('open');
    });
  }

  // ── 3. Sticky Navbar Shadow ───────────────────────────────
  const navbar = document.getElementById('navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      navbar.style.boxShadow = window.scrollY > 10
        ? '0 4px 20px rgba(0,0,0,.25)'
        : '0 2px 16px rgba(0,0,0,.18)';
    }, { passive: true });
  }

  // ── 4. Report Type Toggle (visual radio buttons) ──────────
  document.querySelectorAll('.type-option input[type=radio]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.querySelectorAll('.type-option').forEach(opt => opt.classList.remove('active'));
      if (radio.checked) radio.closest('.type-option').classList.add('active');
    });
  });

  // On page load, mark the checked radio option as active
  document.querySelectorAll('.type-option input[type=radio]:checked').forEach(radio => {
    radio.closest('.type-option').classList.add('active');
  });

  // ── 5. Image Upload Preview ───────────────────────────────
  const uploadInput     = document.getElementById('image');
  const uploadPreview   = document.getElementById('imagePreview');
  const uploadArea      = document.getElementById('uploadArea');
  const uploadPlaceholder = document.getElementById('uploadPlaceholder');

  if (uploadInput && uploadPreview) {
    uploadInput.addEventListener('change', () => {
      const file = uploadInput.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = (e) => {
        uploadPreview.src = e.target.result;
        uploadPreview.classList.add('visible');
        if (uploadPlaceholder) uploadPlaceholder.style.display = 'none';
      };
      reader.readAsDataURL(file);
    });

    // Drag and drop
    if (uploadArea) {
      uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
      });
      uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
      uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
          const dt = new DataTransfer();
          dt.items.add(file);
          uploadInput.files = dt.files;
          uploadInput.dispatchEvent(new Event('change'));
        }
      });
    }
  }

  // ── 6. Character Counter ──────────────────────────────────
  document.querySelectorAll('.char-counter[data-target]').forEach(counter => {
    const target = document.getElementById(counter.dataset.target);
    if (!target) return;

    const maxLen = parseInt(target.getAttribute('maxlength') || 0, 10);

    const update = () => {
      const len  = target.value.length;
      counter.textContent = `${len} / ${maxLen} characters`;
      counter.style.color = len > maxLen * 0.9 ? 'var(--warning)' : '';
      if (len >= maxLen) counter.style.color = 'var(--danger)';
    };

    target.addEventListener('input', update);
    update(); // initial
  });

  // ── 7. Password Toggle ────────────────────────────────────
  document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.target);
      if (!input) return;
      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';
      btn.textContent = isPassword ? '🙈' : '👁';
    });
  });

  // ── 8. Hero Live Search (AJAX) ────────────────────────────
  const heroSearch  = document.getElementById('heroSearch');
  const searchResults = document.getElementById('heroSearchResults');

  if (heroSearch && searchResults) {
    let debounceTimer;

    heroSearch.addEventListener('input', () => {
      const q = heroSearch.value.trim();
      clearTimeout(debounceTimer);

      if (q.length < 2) {
        searchResults.classList.remove('open');
        searchResults.innerHTML = '';
        return;
      }

      // Debounce: wait 300ms after user stops typing
      debounceTimer = setTimeout(() => {
        fetchSearchResults(q);
      }, 300);
    });

    heroSearch.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        searchResults.classList.remove('open');
      }
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!heroSearch.contains(e.target) && !searchResults.contains(e.target)) {
        searchResults.classList.remove('open');
      }
    });
  }

  /**
   * Fetch search results from the AJAX endpoint and render them.
   * Uses the Fetch API (XMLHttpRequest alternative).
   */
  function fetchSearchResults(query) {
    const url = `/ReClaim/actions/ajax-search.php?q=${encodeURIComponent(query)}&limit=8`;

    fetch(url)
      .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json();
      })
      .then(results => {
        renderSearchResults(results, query);
      })
      .catch(() => {
        searchResults.innerHTML = '<div class="search-result-item" style="color:var(--text-muted)">Search unavailable.</div>';
        searchResults.classList.add('open');
      });
  }

  function renderSearchResults(results, query) {
    if (results.length === 0) {
      searchResults.innerHTML = `
        <div class="search-result-item" style="flex-direction:column;align-items:flex-start;gap:.25rem">
          <span style="font-weight:600">No results for "<em>${escapeHtml(query)}</em>"</span>
          <a href="/ReClaim/report-create.php" style="font-size:.85rem;color:var(--primary-light)">
            + Submit a new report →
          </a>
        </div>`;
      searchResults.classList.add('open');
      return;
    }

    let html = '';
    results.forEach(r => {
      const typeEmoji = r.type === 'lost' ? '😟' : '🎉';
      const img = r.image
        ? `<img src="${escapeHtml(r.image)}" alt="" class="sri-img">`
        : `<div class="sri-placeholder">${typeEmoji}</div>`;

      html += `
        <a href="${escapeHtml(r.url)}" class="search-result-item">
          ${img}
          <div class="sri-info">
            <div class="sri-title">${escapeHtml(r.title)}</div>
            <div class="sri-meta">
              ${r.type.toUpperCase()} · ${escapeHtml(r.category)}
              ${r.location ? '· ' + escapeHtml(r.location) : ''}
            </div>
          </div>
        </a>`;
    });

    html += `<a href="/ReClaim/report-list.php?q=${encodeURIComponent(query)}"
              class="search-result-item" style="justify-content:center;color:var(--primary-light);font-weight:600;font-size:.875rem">
               See all results for "${escapeHtml(query)}" →
             </a>`;

    searchResults.innerHTML = html;
    searchResults.classList.add('open');
  }

  // ── 9. Mobile Filter Panel Toggle ────────────────────────
  const filterToggleBtn = document.getElementById('filterToggleBtn');
  const filtersPanel    = document.getElementById('filtersPanel');

  if (filterToggleBtn && filtersPanel) {
    filterToggleBtn.addEventListener('click', () => {
      filtersPanel.classList.toggle('open');
    });

    // Close on outside click (mobile)
    document.addEventListener('click', (e) => {
      if (window.innerWidth <= 768 &&
          !filterToggleBtn.contains(e.target) &&
          !filtersPanel.contains(e.target)) {
        filtersPanel.classList.remove('open');
      }
    });
  }

  // ── 10. Filter radio buttons → submit form ────────────────
  document.querySelectorAll('#searchForm input[type=radio]').forEach(radio => {
    radio.addEventListener('change', () => {
      radio.closest('form').submit();
    });
  });

  // ── 11. Flash message auto-dismiss ───────────────────────
  const flash = document.getElementById('flashMessage');
  if (flash) {
    setTimeout(() => {
      flash.style.transition = 'opacity .5s';
      flash.style.opacity    = '0';
      setTimeout(() => flash.remove(), 500);
    }, 5000);
  }

  // ── 12. Admin: auto-submit sort/filter forms ──────────────
  document.querySelectorAll('[data-auto-submit]').forEach(el => {
    el.addEventListener('change', () => el.closest('form').submit());
  });

  // ── Utility: safe HTML escape ─────────────────────────────
  function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

});
