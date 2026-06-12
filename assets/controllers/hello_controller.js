import { Controller } from '@hotwired/stimulus';

/*
 * Exemple de controller Stimulus.
 *
 * Un controller Stimulus s'active lorsqu'un element HTML porte l'attribut
 * data-controller="hello". Le nom "hello" vient du fichier:
 * hello_controller.js -> hello.
 *
 * MiniNews ne l'utilise pas dans les templates actuels, mais il reste utile pour
 * montrer aux etudiants ou ajouter un comportement JavaScript structure.
 */
export default class extends Controller {
    /**
     * connect() est appelee automatiquement quand l'element arrive dans le DOM.
     */
    connect() {
        this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.js';
    }
}
