/**
 * Animações GSAP (+ ScrollTrigger) do painel — Fase 8 de polimento (PRD).
 * Toda função verifica se o GSAP carregou antes de rodar: se o CDN falhar,
 * a página continua funcional e com os valores corretos, só sem animação.
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

    gsap.from('.kpi-card', { opacity: 0, y: 16, duration: 0.5, stagger: 0.08, ease: 'power2.out' });

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
