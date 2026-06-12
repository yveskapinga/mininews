// Point d'entree JavaScript principal de MiniNews.
//
// Symfony AssetMapper lit importmap.php, trouve l'entree "app", puis charge ce
// fichier dans base.html.twig via {{ importmap('app') }}. Les imports ci-dessous
// remplacent donc l'ancien modele ou l'on ajoutait manuellement des balises
// <script> et <link> dans chaque page.

import './stimulus_bootstrap.js';

// Bootstrap 5 est installe via AssetMapper. On importe le CSS et le JS bundle
// pour disposer de la grille, des composants responsive et des interactions
// (dropdown, collapse, etc.) sans ecrire de CSS pur a la main.
import 'bootstrap/dist/css/bootstrap.min.css';
import * as bootstrap from 'bootstrap';

// Notre couche de styles supplementaires: palette etudiante, animations,
// ajustements specifiques a MiniNews.
import './styles/app.css';

// On expose Bootstrap au navigateur pour le debugging pedagogique. Cela permet
// d'ouvrir la console du navigateur et de taper `bootstrap.Toast` par exemple.
window.bootstrap = bootstrap;

/**
 * Branche une confirmation avant la suppression d'un article.
 *
 * Ce comportement est volontairement simple et pedagogique: le template admin
 * place l'attribut data-confirm-delete sur les formulaires de suppression, puis
 * ce JavaScript detecte ces formulaires et intercepte leur evenement submit.
 *
 * @param {HTMLFormElement} form Formulaire de suppression a proteger.
 * @returns {void}
 */
function attachDeleteConfirmation(form) {
    form.addEventListener('submit', (event) => {
        // window.confirm retourne true si l'utilisateur accepte, false sinon.
        const confirmed = window.confirm('Confirmer la suppression de cet article ?');

        // preventDefault annule l'envoi du formulaire. Le controleur Symfony ne
        // recevra donc pas la requete POST de suppression.
        if (!confirmed) {
            event.preventDefault();
        }
    });
}

/**
 * Ajoute un defilement doux vers une section de la page.
 *
 * Le lien doit pointer vers une ancre, par exemple href="#articles", et porter
 * l'attribut data-smooth-scroll. Cette approche garde le HTML fonctionnel meme si
 * JavaScript est desactive: le navigateur ira quand meme a l'ancre.
 *
 * @param {HTMLAnchorElement} link Lien d'ancrage a ameliorer.
 * @returns {void}
 */
function attachSmoothScroll(link) {
    link.addEventListener('click', (event) => {
        const targetSelector = link.getAttribute('href');
        const target = targetSelector ? document.querySelector(targetSelector) : null;

        if (!target) {
            return;
        }

        event.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

/**
 * Rend les cartes de presentation legerement interactives.
 *
 * Cliquer une carte ajoute une classe CSS active. Les autres cartes sont remises
 * au repos pour montrer clairement quel bloc l'utilisateur explore.
 *
 * @param {HTMLElement} card Carte explicative de la page d'accueil.
 * @param {NodeListOf<HTMLElement>} allCards Toutes les cartes du meme groupe.
 * @returns {void}
 */
function attachInfoCard(card, allCards) {
    card.addEventListener('click', () => {
        allCards.forEach((item) => item.classList.remove('is-active'));
        card.classList.add('is-active');
    });
}

/**
 * Fait disparaitre automatiquement les messages flash apres quelques secondes.
 *
 * Les flashes Symfony sont deja consommes apres une requete; cote client, on
 * ajoute une animation de fondu pour eviter qu'un message reste visible en
 * permanence sur la page. Chaque flash porte la classe .flash.
 *
 * @param {HTMLElement} flash Element de message flash a fermer.
 * @returns {void}
 */
function autoDismissFlash(flash) {
    // 5000 ms = 5 secondes : suffisant pour lire un message court.
    setTimeout(() => {
        flash.classList.add('flash-fade-out');

        // Apres la transition CSS (300 ms), on retire l'element du DOM pour
        // liberer l'espace et eviter un message fige invisible mais cliquable.
        flash.addEventListener('transitionend', () => {
            flash.remove();
        }, { once: true });
    }, 5000);
}

/**
 * Animate l'apparition progressive des elements au chargement.
 *
 * Les elements marques data-animate="fade-up" commencent invisibles puis
 * apparaissent les uns apres les autres avec un leger decalage. Cela rend
 * l'interface plus vivante sans perturber la comprehension du code.
 *
 * @returns {void}
 */
function animateOnLoad() {
    const animatedElements = document.querySelectorAll('[data-animate="fade-up"]');

    animatedElements.forEach((element, index) => {
        // setTimeout decale chaque element de 80 ms par rapport au precedent.
        setTimeout(() => {
            element.classList.add('is-visible');
        }, index * 80);
    });
}

/**
 * Initialise les comportements propres a MiniNews quand le DOM est pret.
 *
 * Chaque querySelectorAll cherche un attribut HTML pedagogique: data-confirm-delete,
 * data-smooth-scroll, data-info-card ou data-animate. Le comportement JavaScript
 * reste donc facile a relier au template Twig correspondant.
 *
 * @returns {void}
 */
function bootMiniNews() {
    document.querySelectorAll('form[data-confirm-delete]').forEach((form) => {
        attachDeleteConfirmation(form);
    });

    document.querySelectorAll('a[data-smooth-scroll]').forEach((link) => {
        attachSmoothScroll(link);
    });

    const infoCards = document.querySelectorAll('[data-info-card]');
    infoCards.forEach((card) => {
        attachInfoCard(card, infoCards);
    });

    document.querySelectorAll('.flash').forEach((flash) => {
        autoDismissFlash(flash);
    });

    animateOnLoad();
}

// DOMContentLoaded garantit que les elements existent dans la page avant de
// chercher a leur attacher des ecouteurs d'evenements.
document.addEventListener('DOMContentLoaded', bootMiniNews);
