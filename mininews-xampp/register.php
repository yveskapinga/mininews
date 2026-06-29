<?php
/**
 * Inscription d'un nouveau lecteur (ROLE_USER uniquement).
 * Les admins ne peuvent pas s'inscrire ici — c'est volontaire pour la sécu du TP.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('index.php');
}

$pageTitle = 'Inscription - MiniNews';
$errors = [];
$displayName = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $displayName = trim((string) ($_POST['display_name'] ?? ''));
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if (!csrf_verify('register', $_POST['_token'] ?? null)) {
        $errors[] = 'Formulaire invalide.';
    }

    if ($displayName === '') {
        $errors[] = 'Le nom affiché est obligatoire.';
    } elseif (mb_strlen($displayName) > 100) {
        $errors[] = 'Le nom affiché est trop long.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }

    if (mb_strlen($password) < 6) {
        $errors[] = 'Le mot de passe doit faire au moins 6 caractères.';
    }

    if ($password !== $passwordConfirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    // Le prof a insisté : le nom affiché ne doit pas être le mot de passe
    // sinon il apparaîtrait partout publiquement sous les articles
    if ($displayName !== '' && $displayName === $password) {
        $errors[] = 'Le mot de passe ne doit pas être identique au nom affiché.';
    }

    // Vérifier que l'email n'existe pas déjà
    if ($errors === []) {
        $check = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch() !== false) {
            $errors[] = 'Un compte existe déjà avec cette adresse email.';
        }
    }

    if ($errors === []) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $roles = json_encode(['ROLE_USER'], JSON_THROW_ON_ERROR);

        $insert = db()->prepare(
            'INSERT INTO users (email, roles, password, display_name, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $insert->execute([$email, $roles, $hash, $displayName]);

        flash_add('success', 'Compte créé. Vous pouvez vous connecter.');
        redirect('login.php');
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="mn-auth-card" data-animate="fade-up">
    <h1>Créer un compte lecteur</h1>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3">
            <label for="display_name" class="form-label">Nom affiché</label>
            <input type="text" name="display_name" id="display_name" class="form-control"
                   value="<?= e($displayName) ?>" required maxlength="100">
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control"
                   value="<?= e($email) ?>" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input type="password" name="password" id="password" class="form-control" required minlength="6">
        </div>

        <div class="mb-3">
            <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
            <input type="password" name="password_confirm" id="password_confirm" class="form-control" required>
        </div>

        <input type="hidden" name="_token" value="<?= e(csrf_token('register')) ?>">

        <div class="d-grid">
            <button class="btn mn-btn mn-btn-primary" type="submit">Créer le compte</button>
        </div>
    </form>

    <p class="text-center mt-3 mb-0">
        Déjà inscrit ? <a href="<?= e(url('login.php')) ?>">Se connecter</a>
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
