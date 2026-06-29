<?php
/**
 * Déconnexion — on vide la session et on retourne à l'accueil.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

logout_user();
flash_add('success', 'Vous êtes déconnecté.');

redirect('index.php');
