<?php
/**
 * En-tête HTML commun à toutes les pages.
 * Comme base.html.twig en Symfony, mais en PHP + HTML mélangés.
 */

declare(strict_types=1);

/** @var string $pageTitle */
$pageTitle = $pageTitle ?? 'MiniNews';
$user = current_user();
$flashes = flash_consume();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="<?= e(url('assets/favicon.svg')) ?>">
    <title><?= e($pageTitle) ?></title>

    <!-- Bootstrap 5 via CDN : pas besoin de Composer ni npm pour le TP -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>">

    <noscript>
        <style>
            [data-animate="fade-up"] { opacity: 1 !important; transform: none !important; }
        </style>
    </noscript>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg mn-navbar" aria-label="Navigation principale">
            <div class="container">
                <a class="navbar-brand" href="<?= e(url('index.php')) ?>">
                    <img src="<?= e(url('assets/logo.svg')) ?>" alt="" width="34" height="34">
                    MiniNews
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                        aria-controls="mainNav" aria-expanded="false" aria-label="Ouvrir le menu">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="mainNav">
                    <ul class="navbar-nav ms-auto align-items-lg-center">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= e(url('index.php')) ?>">Actualités</a>
                        </li>

                        <?php if (is_admin()): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= e(url('admin/index.php')) ?>">Articles admin</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= e(url('admin/users.php')) ?>">Utilisateurs</a>
                            </li>
                        <?php endif; ?>

                        <?php if ($user): ?>
                            <li class="nav-item">
                                <span class="mn-nav-user">Bonjour <?= e($user['display_name']) ?></span>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= e(url('logout.php')) ?>">Déconnexion</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= e(url('login.php')) ?>">Connexion</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= e(url('register.php')) ?>">Inscription</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main class="mn-page-container container">
        <div aria-live="polite" aria-atomic="true">
            <?php foreach ($flashes as $flash): ?>
                <div class="mn-flash mn-flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endforeach; ?>
        </div>
