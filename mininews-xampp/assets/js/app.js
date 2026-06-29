/**
 * JavaScript de MiniNews (version XAMPP).
 * Même comportement que assets/app.js en Symfony, mais sans importmap.
 */

function attachDeleteConfirmation(form) {
    form.addEventListener('submit', (event) => {
        if (!window.confirm('Confirmer la suppression de cet article ?')) {
            event.preventDefault();
        }
    });
}

function attachSmoothScroll(link) {
    link.addEventListener('click', (event) => {
        const target = document.querySelector(link.getAttribute('href'));
        if (!target) return;
        event.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function attachInfoCard(card, allCards) {
    card.addEventListener('click', () => {
        allCards.forEach((item) => item.classList.remove('is-active'));
        card.classList.add('is-active');
    });
}

// Les messages flash disparaissent tout seuls après 5 secondes
function autoDismissFlash(flash) {
    setTimeout(() => {
        flash.classList.add('mn-flash-fade-out');
        flash.addEventListener('transitionend', () => flash.remove(), { once: true });
    }, 5000);
}

function animateOnLoad() {
    document.querySelectorAll('[data-animate="fade-up"]').forEach((element, index) => {
        setTimeout(() => element.classList.add('is-visible'), index * 80);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm-delete]').forEach(attachDeleteConfirmation);
    document.querySelectorAll('a[data-smooth-scroll]').forEach(attachSmoothScroll);

    const infoCards = document.querySelectorAll('[data-info-card]');
    infoCards.forEach((card) => attachInfoCard(card, infoCards));

    document.querySelectorAll('.mn-flash').forEach(autoDismissFlash);
    animateOnLoad();
});
