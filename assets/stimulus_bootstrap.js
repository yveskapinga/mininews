// Demarrage de Stimulus, la petite librairie JavaScript utilisee par Symfony UX.
//
// Meme si MiniNews utilise tres peu de JavaScript specifique, Symfony installe ce
// point d'entree pour permettre d'ajouter facilement des controllers Stimulus:
// un controller est une classe JavaScript reliee a un element HTML par un
// attribut data-controller.

import { startStimulusApp } from '@symfony/stimulus-bundle';

// startStimulusApp lit assets/controllers.json et enregistre les controllers
// actives par Symfony UX, par exemple ceux de Turbo.
const app = startStimulusApp();

// Exemple pour plus tard:
// import MonController from './controllers/mon_controller.js';
// app.register('mon', MonController);
export { app };
