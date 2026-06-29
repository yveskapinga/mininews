<?php
/**
 * Gestion de la connexion avec les sessions PHP ($_SESSION).
 *
 * En Symfony c'était Security qui s'en occupait ; ici je le fais à la main
 * parce que le TP demande du PHP procédural.
 */

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/** Charge l'utilisateur connecté depuis la base (ou null si pas connecté) */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $cached = null;
    static $cachedId = null;

    if ($cached !== null && $cachedId === $_SESSION['user_id']) {
        return $cached;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();

    if ($user === false) {
        // Session invalide (compte supprimé ?) → on nettoie
        unset($_SESSION['user_id']);
        return null;
    }

    $cached = $user;
    $cachedId = (int) $user['id'];

    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    $user = current_user();

    return $user !== null && user_has_role($user, 'ROLE_ADMIN');
}

/** Bloque l'accès si pas connecté */
function require_login(): void
{
    if (!is_logged_in()) {
        flash_add('error', 'Vous devez être connecté pour accéder à cette page.');
        redirect('login.php');
    }
}

/** Bloque l'accès si pas admin */
function require_admin(): void
{
    require_login();

    if (!is_admin()) {
        flash_add('error', 'Accès réservé aux administrateurs.');
        redirect('index.php');
    }
}

/** Tente une connexion avec email + mot de passe */
function attempt_login(string $email, string $password): bool
{
    $email = mb_strtolower(trim($email));

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user === false) {
        return false;
    }

    if (!password_verify($password, $user['password'])) {
        return false;
    }

    // On ne garde que l'id en session — pas le mot de passe !
    $_SESSION['user_id'] = (int) $user['id'];

    return true;
}

function logout_user(): void
{
    unset($_SESSION['user_id']);
    // Je régénère l'id de session par sécurité (vu en cours)
    session_regenerate_id(true);
}
