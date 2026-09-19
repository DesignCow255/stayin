/**
 * StayIn — Vanilla JS application entry point.
 * Handles mobile nav toggle, theme toggle, live region announcements,
 * date defaults, guest counter, flash dismiss, lazy-load, service worker.
 * Progressive enhancement only — server-side functionality is primary.
 */
(() => {
  'use strict';
  const doc = document;

  // Theme manager — prefers-color-scheme aware, persisted to localStorage.
  const Theme = {
    KEY: 'stayin_theme',
    root: doc.documentElement,
    preferred() {
      const stored = localStorage.getItem(this.KEY);
      if (['light', 'dark', 'system'].includes(stored)) return stored;
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    },
    apply(t) {
      this.root.setAttribute('data-theme', t);
      if (t === 'system') {
        this.root.setAttribute('data-theme',
          window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      }
      const icon = doc.querySelector('#themeToggle i');
      if (icon) icon.className = this.root.getAttribute('data-theme') === 'dark' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
    },
    toggle() {
      const next = this.preferred() === 'dark' ? 'light' : 'dark';
      localStorage.setItem(this.KEY, next);
      this.apply(next);
    },
    init() {
      this.apply(this.preferred());
      const btn = doc.getElementById('themeToggle');
      if (btn) {
        btn.addEventListener('click', () => this.toggle());
        btn.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.toggle(); }
        });
      }
      window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if (this.preferred() === 'system') this.apply('system');
      });
    },
  };

  // Mobile navigation toggle with ARIA state management.
  function initNavToggle() {
    const toggle = doc.getElementById('navToggle');
    const nav = doc.getElementById('siteNav');
    if (!toggle || !nav) return;
    toggle.addEventListener('click', () => {
      const expanded = toggle.getAttribute('aria-expanded') === 'true';
      toggle.setAttribute('aria-expanded', String(!expanded));
      nav.classList.toggle('is-open');
    });
    doc.addEventListener('click', (e) => {
      if (nav.classList.contains('is-open') && !nav.contains(e.target) && !toggle.contains(e.target)) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
    doc.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && nav.classList.contains('is-open')) {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // Live region for accessible announcements.
  function initAnnouncer() {
    const region = doc.createElement('div');
    region.setAttribute('aria-live', 'polite');
    region.setAttribute('aria-atomic', 'true');
    region.className = 'sr-only';
    doc.body.appendChild(region);
    return {
      announce(m) { region.textContent = m; setTimeout(() => region.textContent = '', 3000); },
    };
  }

  // Auto-populate minimum check-in date for booking forms.
  function initDateFields() {
    const ci = doc.querySelector('input[name="check_in"]');
    const co = doc.querySelector('input[name="check_out"]');
    if (!ci) return;
    const today = new Date().toISOString().split('T')[0];
    ci.setAttribute('min', today);
    ci.value = ci.value || today;
    if (co) {
      co.setAttribute('min', today);
      ci.addEventListener('change', () => {
        if (ci.value) co.setAttribute('min', ci.value);
      });
    }
  }

  // Guest counter widget for search forms.
  function initGuestCounter() {
    const root = doc.querySelector('[data-guest-counter]');
    if (!root) return;
    const dec = root.querySelector('[data-guest-decrease]');
    const inc = root.querySelector('[data-guest-increase]');
    const input = root.querySelector('[data-guest-input]');
    if (!dec || !inc || !input) return;
    const upd = (v) => { dec.disabled = (parseInt(v, 10) || 1) <= 1; };
    upd(input.value || 1);
    dec.addEventListener('click', () => {
      let v = parseInt(input.value, 10) || 1;
      if (v > 1) { v--; input.value = String(v); upd(v); }
    });
    inc.addEventListener('click', () => {
      let v = parseInt(input.value, 10) || 1; v++; input.value = String(v); upd(v);
    });
    input.addEventListener('change', () => upd(input.value));
  }

  // Flash message auto-dismiss.
  function initFlashDismiss() {
    doc.querySelectorAll('[data-flash-dismiss]').forEach((el) => {
      setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 5000);
    });
  }

  // Form validation (progressive enhancement — server is source of truth).
  function initFormValidation() {
    doc.querySelectorAll('form[data-validate]').forEach((form) => {
      form.addEventListener('submit', () => {
        const invalid = form.querySelectorAll('[required]:invalid, [type="email"]:invalid');
        if (invalid.length > 0) {
          invalid[0].focus();
          invalid[0].setAttribute('aria-invalid', 'true');
        }
      });
      form.querySelectorAll('input, select, textarea').forEach((f) => {
        f.addEventListener('blur', () => {
          f.setAttribute('aria-invalid', f.matches(':invalid') ? 'true' : 'false');
        });
      });
    });
  }

  // Lazy-loading for images.
  function initImageLazyLoad() {
    if (!('IntersectionObserver' in window)) return;
    const obs = new IntersectionObserver((entries, o) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const img = entry.target;
          img.src = img.dataset.src;
          img.removeAttribute('data-src');
          img.classList.remove('lazy');
          o.unobserve(img);
        }
      });
    }, { rootMargin: '50px' });
    doc.querySelectorAll('img[data-src]').forEach((img) => obs.observe(img));
  }

  // Service worker registration for PWA support.
  function initServiceWorker() {
    if (!('serviceWorker' in navigator)) return;
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('/assets/js/service-worker.js')
        .catch((err) => console.error('SW registration failed:', err));
    });
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', () => {
      Theme.init(); initNavToggle(); initAnnouncer(); initDateFields();
      initGuestCounter(); initFlashDismiss(); initFormValidation();
      initImageLazyLoad(); initServiceWorker();
    });
  } else {
    Theme.init(); initNavToggle(); initAnnouncer(); initDateFields();
    initGuestCounter(); initFlashDismiss(); initFormValidation();
    initImageLazyLoad(); initServiceWorker();
  }

  window.StayIn = { Theme };
})();
