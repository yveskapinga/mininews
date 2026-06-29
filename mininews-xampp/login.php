<?php
/**
 * Page de connexion.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$pageTitle = 'Connexion - MiniNews';
$error = null;
$lastEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastEmail = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!csrf_verify('login', $_POST['_token'] ?? null)) {
        $error = 'Formulaire invalide.';
    } elseif ($lastEmail === '' || $password === '') {
        $error = 'Email et mot de passe obligatoires.';
    } elseif (attempt_login($lastEmail, $password)) {
        flash_add('success', 'Bienvenue !');
        redirect('index.php');
    } else {
        $error = 'Identifiants incorrects.';
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="mn-auth-card" data-animate="fade-up">
    <h1>Connexion</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" value="<?= e($lastEmail) ?>" name="email" id="email"
                   class="form-control" autocomplete="email" required autofocus>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input type="password" name="password" id="password" class="form-control"
                   autocomplete="current-password" required>
        </div>

        <input type="hidden" name="_token" value="<?= e(csrf_token('login')) ?>">

        <div class="d-grid">
            <button class="btn mn-btn mn-btn-primary" type="submit">Se connecter</button>
        </div>
    </form>

    <p class="text-center mt-3 mb-0">
        Pas encore de compte ? <a href="<?= e(url('register.php')) ?>">Créer un compte</a>
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
