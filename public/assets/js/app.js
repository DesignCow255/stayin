/**
 * Shared icon affordances for the premium layout (inline SVG registry).
 * Consumed by the theme toggle; everything else renders server-side
 * through the `icon()` helper.
 */
window.StayInIcons = window.StayInIcons || {
  moon: '<svg class="si-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>',
  sun: '<svg class="si-icon" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>'
};

/**
 * StayIn — Vanilla JS application entry point.
 * Handles mobile nav toggle, theme toggle, live region announcements,
 * date defaults, guest counter, flash dismiss, lazy-load, service worker.
 * Progressive enhancement only — server-side functionality is primary.
 */
(() => {
  'use strict';
  const doc = document;

  // Theme manager — persists to the `theme` session key (server) with a
  // localStorage mirror for pre-paint resolution. Defaults to `light`;
  // `system` can be enabled by setting data-theme="system" on <html>.
  const Theme = {
    KEY: 'stayin_theme',
    root: doc.documentElement,
    toggleBtn: null,
    icons: { light: 'moon', dark: 'sun' },
    preferred() {
      try {
        const stored = localStorage.getItem(this.KEY);
        if (['light', 'dark', 'system'].includes(stored)) return stored;
      } catch (err) { /* private mode: fall through to server default */ }
      return 'light';
    },
    apply(t) {
      this.root.setAttribute('data-theme', t);
      if (t === 'system') {
        this.root.setAttribute('data-theme',
          window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
      }
      const label = t === 'dark' ? 'Switch to light theme' : 'Switch to dark theme';
      const iconName = t === 'dark' ? 'sun' : 'moon';
      this.renderIcon(this.toggleBtn, iconName);
      if (this.toggleBtn) this.toggleBtn.setAttribute('aria-label', label);
    },
    renderIcon(btn, name) {
      if (!btn) return;
      const icon = window.StayInIcons && window.StayInIcons[name];
      if (icon) {
        btn.innerHTML = icon;
      } else {
        btn.setAttribute('data-icon', name);
      }
    },
    toggle() {
      const next = this.preferred() === 'dark' ? 'light' : 'dark';
      try { localStorage.setItem(this.KEY, next); } catch (err) { /* private mode */ }
      this.apply(next);
      this.persist(next);
    },
    persist(theme) {
      const token = doc.querySelector('[data-csrf]')?.getAttribute('data-csrf') || '';
      if (!token) return;
      const body = new URLSearchParams({ _token: token, theme });
      const endpoint = new URL('preferences', document.baseURI).toString();
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
        credentials: 'same-origin',
      }).catch(() => { /* non-blocking preference sync */ });
    },
    init() {
      this.toggleBtn = doc.getElementById('themeToggle');
      this.apply(this.preferred());
      if (this.toggleBtn) {
        this.toggleBtn.addEventListener('click', () => this.toggle());
      }
      const media = window.matchMedia('(prefers-color-scheme: dark)');
      const onChange = () => {
        if (this.preferred() === 'system') this.apply('system');
      };
      if (typeof media.addEventListener === 'function') media.addEventListener('change', onChange);
    },
  };

  // Mobile drawer — purpose-built dialog (focus trap-lite: Escape + outside click + focus return).
  function initNavToggle() {
    const openBtn = doc.getElementById('navToggle');
    const drawer = doc.getElementById('mobileDrawer');
    const closeBtn = doc.getElementById('drawerClose');
    if (!openBtn || !drawer) return;

    let lastFocus = null;

    const open = () => {
      lastFocus = doc.activeElement;
      drawer.hidden = false;
      requestAnimationFrame(() => drawer.classList.add('is-open'));
      openBtn.setAttribute('aria-expanded', 'true');
      doc.body.style.overflow = 'hidden';
      if (closeBtn) closeBtn.focus();
    };

    const close = (restoreFocus = true) => {
      drawer.classList.remove('is-open');
      openBtn.setAttribute('aria-expanded', 'false');
      doc.body.style.overflow = '';
      const hide = () => { drawer.hidden = true; };
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) hide();
      else setTimeout(hide, 280);
      if (restoreFocus && lastFocus && typeof lastFocus.focus === 'function') lastFocus.focus();
    };

    openBtn.addEventListener('click', open);
    if (closeBtn) closeBtn.addEventListener('click', () => close());
    doc.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && drawer.classList.contains('is-open')) close();
    });
    doc.addEventListener('click', (e) => {
      if (drawer.classList.contains('is-open') && !drawer.contains(e.target) && !openBtn.contains(e.target)) {
        close(false);
      }
    });
  }

  // Live region for accessible announcements.
  let announcer = null;
  function announce(message) {
    if (announcer) announcer(message);
  }
  function initAnnouncer() {
    const region = doc.createElement('div');
    region.setAttribute('aria-live', 'polite');
    region.setAttribute('aria-atomic', 'true');
    region.className = 'sr-only';
    doc.body.appendChild(region);
    announcer = (m) => { region.textContent = m; setTimeout(() => region.textContent = '', 3000); };
    return { announce: announcer };
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

  // Flash message: CSP-safe dismiss + auto-dismiss.
  function initFlashDismiss() {
    doc.querySelectorAll('[data-dismiss-flash]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const alert = btn.closest('[data-flash-dismiss], .alert, .si-alert');
        if (alert) alert.remove();
      });
    });
    doc.querySelectorAll('[data-flash-dismiss]').forEach((el) => {
      setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 6000);
    });
  }

  // Image fallback: CSP-safe replacement for inline onerror handlers (B14).
  function initImageFallbacks() {
    doc.querySelectorAll('img[data-fallback-src]').forEach((img) => {
      img.addEventListener('error', () => {
        const fallback = img.getAttribute('data-fallback-src');
        if (fallback && img.src !== fallback) img.src = fallback;
      }, { once: true });
    });
  }

  // Sticky header elevation on scroll.
  function initHeaderElevation() {
    const header = doc.getElementById('siteHeader');
    if (!header) return;
    const update = () => {
      header.setAttribute('data-elevated', window.scrollY > 8 ? 'true' : 'false');
    };
    update();
    window.addEventListener('scroll', update, { passive: true });
  }

  // Scroll-reveal entrances (opacity 0→1, translateY 20→0, scale .98→1).
  function initReveal() {
    const els = doc.querySelectorAll('.si-reveal');
    if (!els.length) return;
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      els.forEach((el) => el.classList.add('is-visible'));
      return;
    }
    const obs = new IntersectionObserver((entries, o) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          o.unobserve(entry.target);
        }
      });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
    els.forEach((el) => obs.observe(el));
  }

  // Favourite buttons: micro-interaction + optimistic label, server stays source of truth.
  function initFavourites() {
    doc.querySelectorAll('[data-favourite-toggle]').forEach((btn) => {
      btn.addEventListener('click', () => {
        btn.classList.remove('is-popping');
        void btn.offsetWidth; // restart the pop animation
        btn.classList.add('is-popping');
      });
    });
  }

  // Dialogs: share sheet, photo lightbox. Focus is moved in and restored on close.
  function initDialogs() {
    const openers = doc.querySelectorAll('[data-dialog-open]');
    const closers = doc.querySelectorAll('[data-dialog-close]');

    openers.forEach((btn) => {
      btn.addEventListener('click', () => {
        const dialog = doc.getElementById(btn.getAttribute('data-dialog-open') || '');
        if (!dialog || typeof dialog.showModal !== 'function') return;
        dialog.dataset.opener = btn.id || '';
        dialog.showModal();
      });
    });

    closers.forEach((btn) => {
      btn.addEventListener('click', () => {
        const dialog = btn.closest('dialog');
        if (dialog && dialog.open) dialog.close();
      });
    });

    doc.querySelectorAll('dialog').forEach((dialog) => {
      dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close();
      });
      dialog.addEventListener('close', () => {
        const openerId = dialog.dataset.opener || '';
        const opener = openerId ? doc.getElementById(openerId) : null;
        if (opener) opener.focus();
      });
    });
  }

  // Photo lightbox: single image, next/prev via keyboard, Esc to close.
  function initLightbox() {
    const dialog = doc.getElementById('photoDialog');
    if (!dialog) return;
    const img = dialog.querySelector('img');
    const triggers = Array.from(doc.querySelectorAll('[data-gallery-image]'));
    if (!img || triggers.length === 0) return;

    let index = 0;

    const render = () => {
      const trigger = triggers[index];
      if (!trigger || !img) return;
      img.src = trigger.getAttribute('href') || '';
      img.alt = trigger.getAttribute('data-gallery-caption') || 'Property photograph';
    };

    const open = (position) => {
      index = Math.max(0, Math.min(position, triggers.length - 1));
      render();
      if (typeof dialog.showModal === 'function' && !dialog.open) dialog.showModal();
    };

    triggers.forEach((trigger) => {
      trigger.addEventListener('click', (e) => {
        e.preventDefault();
        open(triggers.indexOf(trigger));
      });
    });

    dialog.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') { index = (index + 1) % triggers.length; render(); }
      if (e.key === 'ArrowLeft') { index = (index - 1 + triggers.length) % triggers.length; render(); }
    });

    dialog.addEventListener('click', (e) => {
      if (e.target !== img) return;
      index = (index + 1) % triggers.length;
      render();
    });
  }

  // Sticky sub-nav: highlight the section currently in view.
  function initSubnav() {
    const links = Array.from(doc.querySelectorAll('.si-subnav a'));
    const sections = links
      .map((link) => doc.getElementById((link.getAttribute('href') || '').replace('#', '')))
      .filter((el) => el !== null);
    if (!links.length || !sections.length || !('IntersectionObserver' in window)) return;

    const obs = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        links.forEach((link) => link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id));
      });
    }, { rootMargin: '-45% 0px -50% 0px' });

    sections.forEach((section) => obs.observe(section));
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
      const swUrl = new URL('service-worker.js', document.baseURI).toString();
      navigator.serviceWorker.register(swUrl)
        .catch(() => { /* non-blocking progressive enhancement */ });
    });
  }

  // Copy-to-clipboard buttons (share sheet).
  function initCopyButtons() {
    doc.querySelectorAll('[data-copy]').forEach((btn) => {
      btn.addEventListener('click', async () => {
        const text = btn.getAttribute('data-copy') || '';
        try {
          await navigator.clipboard.writeText(text);
          const label = btn.querySelector('[data-copy-label]');
          if (label) {
            const original = label.textContent;
            label.textContent = 'Copied';
            setTimeout(() => { label.textContent = original; }, 2000);
          }
          announce('Link copied to clipboard.');
        } catch (err) { /* clipboard unavailable */ }
      });
    });
  }

    // Homepage hero carousel: rotates active slide, respects reduced motion.
  function initCarousel() {
    const root = doc.querySelector('.si-hero--carousel');
    if (!root) return { next: null, prev: null, go: () => {} };
    const slides = Array.from(root.querySelectorAll('.si-hero__slide'));
    if (slides.length <= 1) return { next: null, prev: null, go: () => {} };
    let index = 0;
    let timer = null;
    const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const show = (i) => {
      slides.forEach((s, n) => s.classList.toggle('is-active', n === i));
      slides[i].setAttribute('aria-hidden', 'false');
      slides.forEach((s, n) => { if (n !== i) s.setAttribute('aria-hidden', 'true'); });
    };
    const next = () => { index = (index + 1) % slides.length; show(index); };
    const prev = () => { index = (index - 1 + slides.length) % slides.length; show(index); };
    const go = (i) => { index = (i + slides.length) % slides.length; show(index); };

    slides.forEach((s, i) => s.classList.toggle('is-active', i === 0));
    if (!prefersReduced) {
      timer = window.setInterval(next, 6000);
    }
    const nextBtn = doc.getElementById('heroNext');
    const prevBtn = doc.getElementById('heroPrev');
    if (nextBtn) nextBtn.addEventListener('click', () => { next(); reset(); });
    if (prevBtn) prevBtn.addEventListener('click', () => { prev(); reset(); });
    const reset = () => { if (timer) window.clearInterval(timer); if (!prefersReduced) timer = window.setInterval(next, 6000); };
    return { next: nextBtn, prev: prevBtn, go };
  }

  function boot() {
    Theme.init(); initNavToggle(); initAnnouncer(); initDateFields();
    initGuestCounter(); initFlashDismiss(); initFormValidation();
    initImageLazyLoad(); initServiceWorker(); initImageFallbacks();
    initHeaderElevation(); initReveal(); initFavourites(); initCopyButtons();
    initDialogs(); initLightbox(); initSubnav(); initCarousel();
  }

  if (doc.readyState === 'loading') {
    doc.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }

  window.StayIn = { Theme, announce };
})();
