/* ==========================================================================
   TurneroYa · Landing JS — interacciones de impacto visual.
   Vanilla, sin librerías. Idempotente: si un elemento no existe, no rompe.
   --------------------------------------------------------------------------
   - Spotlight cursor en hero
   - Spotlight cursor en feature cards (.spot-card)
   - Reveal on scroll (IntersectionObserver) sobre [data-reveal]
   - Count-up sobre [data-countup="<n>"]
   - Scroll progress bar (.scroll-progress)
   - Killer card aurora pointer tracking (opcional)
   ========================================================================== */
(() => {
    'use strict';

    // Activa el modo "JS-on" (las animaciones de reveal solo se aplican con JS).
    document.documentElement.classList.add('js-on');

    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Hero spotlight ---------- */
    const hero = document.querySelector('.hero-shell');
    if (hero && !reduceMotion) {
        let raf = null;
        hero.addEventListener('pointermove', (e) => {
            const rect = hero.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            if (raf) cancelAnimationFrame(raf);
            raf = requestAnimationFrame(() => {
                hero.style.setProperty('--mx', x + '%');
                hero.style.setProperty('--my', y + '%');
            });
        }, { passive: true });
    }

    /* ---------- Feature cards spotlight ---------- */
    document.querySelectorAll('.spot-card').forEach((card) => {
        if (reduceMotion) return;
        card.addEventListener('pointermove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            card.style.setProperty('--mx', x + '%');
            card.style.setProperty('--my', y + '%');
        }, { passive: true });
    });

    /* ---------- Reveal on scroll ---------- */
    const revealEls = document.querySelectorAll('[data-reveal]');
    if (revealEls.length && 'IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        revealEls.forEach((el) => io.observe(el));
    } else {
        revealEls.forEach((el) => el.classList.add('is-visible'));
    }

    /* ---------- Count-up ---------- */
    const countEls = document.querySelectorAll('[data-countup]');
    const formatNum = (n, suffix) => {
        const rounded = Number.isInteger(n) ? n : Math.round(n * 10) / 10;
        return rounded.toLocaleString('es-AR') + (suffix || '');
    };
    const animateCount = (el) => {
        const target = parseFloat(el.dataset.countup);
        if (Number.isNaN(target)) return;
        const suffix = el.dataset.countupSuffix || '';
        const duration = parseInt(el.dataset.countupDuration || '1400', 10);
        const start = performance.now();
        const ease = (t) => 1 - Math.pow(1 - t, 3);
        const tick = (now) => {
            const t = Math.min(1, (now - start) / duration);
            const v = target * ease(t);
            el.textContent = formatNum(v, suffix);
            if (t < 1) requestAnimationFrame(tick);
            else el.textContent = formatNum(target, suffix);
        };
        if (reduceMotion) {
            el.textContent = formatNum(target, suffix);
        } else {
            requestAnimationFrame(tick);
        }
    };
    if (countEls.length && 'IntersectionObserver' in window) {
        const cio = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    animateCount(entry.target);
                    cio.unobserve(entry.target);
                }
            });
        }, { threshold: 0.4 });
        countEls.forEach((el) => {
            el.textContent = formatNum(0, el.dataset.countupSuffix || '');
            cio.observe(el);
        });
    } else {
        countEls.forEach(animateCount);
    }

    /* ---------- Scroll progress bar ---------- */
    const bar = document.querySelector('.scroll-progress');
    if (bar) {
        const onScroll = () => {
            const h = document.documentElement;
            const scrolled = h.scrollTop;
            const max = h.scrollHeight - h.clientHeight;
            const pct = max > 0 ? (scrolled / max) * 100 : 0;
            bar.style.setProperty('--p', pct + '%');
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }
})();
