/**
 * Animações GSAP (+ ScrollTrigger) — dark navy/blue design system.
 * Toda função verifica se o GSAP carregou antes de rodar: se o CDN falhar,
 * a página continua funcional e com os valores corretos, só sem animação.
 * Este arquivo se auto-inicializa (fade de página + indicador da sidebar)
 * em toda página que o incluir via partials/head-assets.php.
 */

function formatCountValue(value, format) {
    if (format === 'currency') {
        return value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }
    if (format === 'percent') {
        var sign = value >= 0 ? '+' : '';
        return sign + value.toFixed(1).replace('.', ',') + '%';
    }
    return Math.round(value).toLocaleString('pt-BR');
}

function animateCountUps() {
    if (typeof gsap === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-countup]').forEach(function (el) {
        var end = parseFloat(el.getAttribute('data-countup'));
        if (isNaN(end)) {
            return;
        }
        var format = el.getAttribute('data-format') || 'number';
        var obj = { val: 0 };
        gsap.to(obj, {
            val: end,
            duration: 1,
            ease: 'power2.out',
            onUpdate: function () { el.textContent = formatCountValue(obj.val, format); },
        });
    });
}

function animateDashboardEntrance() {
    if (typeof gsap === 'undefined') {
        return;
    }

    // KPI cards keep their grid position during the entrance animation.
    gsap.from('.kpi-card', { opacity: 0, duration: 0.35, stagger: 0.04, ease: 'power2.out' });

    if (typeof ScrollTrigger !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);
        gsap.utils.toArray('.chart-card').forEach(function (card) {
            gsap.from(card, {
                opacity: 0,
                y: 24,
                duration: 0.6,
                ease: 'power2.out',
                scrollTrigger: { trigger: card, start: 'top 90%' },
            });
        });
    } else {
        gsap.from('.chart-card', { opacity: 0, y: 24, duration: 0.6, stagger: 0.15, ease: 'power2.out' });
    }

    animateCountUps();
}

function animateCardEntrance(selector) {
    if (typeof gsap === 'undefined') {
        return;
    }
    gsap.from(selector, { opacity: 0, y: 12, duration: 0.5, ease: 'power2.out' });
}

/** Banner de confirmação (ex: lançamento salvo com sucesso) — entra, pulsa e some sozinho. */
function animateSuccessBanner(selector, autoHideMs) {
    var el = document.querySelector(selector);
    if (!el) {
        return;
    }

    if (typeof gsap === 'undefined') {
        if (autoHideMs) {
            setTimeout(function () { el.style.display = 'none'; }, autoHideMs);
        }
        return;
    }

    gsap.from(el, { opacity: 0, y: -12, scale: 0.97, duration: 0.4, ease: 'back.out(1.7)' });

    if (autoHideMs) {
        gsap.to(el, {
            opacity: 0,
            y: -8,
            duration: 0.4,
            delay: autoHideMs / 1000,
            ease: 'power1.in',
            onComplete: function () { el.style.display = 'none'; },
        });
    }
}

/** Fade-out do conteúdo antes de navegar (troca de cliente/filtro) — cai de volta ao entrar na próxima página. */
function fadeNavigate(url) {
    if (typeof gsap === 'undefined') {
        window.location.href = url;
        return;
    }
    gsap.to('.content', {
        opacity: 0,
        y: 8,
        duration: 0.18,
        ease: 'power1.in',
        onComplete: function () { window.location.href = url; },
    });
}

/** Igual ao fadeNavigate, mas para envio de formulário (ex: filtro de competência). */
function fadeSubmit(form) {
    if (typeof gsap === 'undefined') {
        form.submit();
        return;
    }
    gsap.to('.content', {
        opacity: 0,
        y: 8,
        duration: 0.18,
        ease: 'power1.in',
        onComplete: function () { form.submit(); },
    });
}

/** Fade de entrada do conteúdo ao carregar qualquer página. */
function initPageFade() {
    if (typeof gsap === 'undefined') {
        return;
    }
    gsap.from('.content', { opacity: 0, y: 8, duration: 0.35, ease: 'power2.out' });
}

/**
 * Indicador do item ativo da sidebar: desliza suavemente da posição anterior
 * (lembrada via sessionStorage) até o item ativo atual, a cada navegação real.
 */
function initSidebarIndicator() {
    var nav = document.querySelector('.sidebar-nav');
    var indicator = document.getElementById('nav-indicator');
    var active = nav ? nav.querySelector('.nav-item.active') : null;

    if (!nav || !indicator || !active) {
        return;
    }

    var navRect = nav.getBoundingClientRect();
    var activeRect = active.getBoundingClientRect();
    var targetTop = activeRect.top - navRect.top + nav.scrollTop;
    var height = activeRect.height;
    var storeKey = 'sidebarIndicatorTop';
    var prevTop = sessionStorage.getItem(storeKey);

    indicator.style.height = height + 'px';
    indicator.style.opacity = '1';

    if (typeof gsap === 'undefined') {
        indicator.style.top = targetTop + 'px';
    } else if (prevTop !== null && parseFloat(prevTop) !== targetTop) {
        gsap.set(indicator, { top: parseFloat(prevTop) });
        gsap.to(indicator, { top: targetTop, duration: 0.4, ease: 'power2.out' });
    } else {
        gsap.set(indicator, { top: targetTop });
    }

    sessionStorage.setItem(storeKey, String(targetTop));
}

document.addEventListener('DOMContentLoaded', function () {
    initPageFade();
    initSidebarIndicator();
});
