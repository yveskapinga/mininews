<?php
/**
 * Création ou modification d'un article (formulaire admin).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

$pdo = db();
$user = current_user();
$articleId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$isEdit = $articleId !== null && $articleId > 0;

$article = [
    'title' => '',
    'excerpt' => '',
    'content' => '',
    'is_published' => 0,
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM article WHERE id = ? LIMIT 1');
    $stmt->execute([$articleId]);
    $found = $stmt->fetch();
    if ($found === false) {
        flash_add('error', 'Article introuvable.');
        redirect('admin/index.php');
    }
    $article = $found;
}

$pageTitle = ($isEdit ? 'Modifier l\'article' : 'Nouvel article') . ' - MiniNews';
$buttonLabel = $isEdit ? 'Enregistrer' : 'Créer';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenKey = $isEdit ? 'edit_article_' . $articleId : 'new_article';
    if (!csrf_verify($tokenKey, $_POST['_token'] ?? null)) {
        $errors[] = 'Formulaire invalide.';
    }

    $title = trim((string) ($_POST['title'] ?? ''));
    $excerpt = trim((string) ($_POST['excerpt'] ?? ''));
    $content = trim((string) ($_POST['content'] ?? ''));
    $isPublished = isset($_POST['is_published']) ? 1 : 0;

    if ($title === '') {
        $errors[] = 'Le titre est obligatoire.';
    } elseif (mb_strlen($title) > 180) {
        $errors[] = 'Le titre est trop long.';
    }

    if ($excerpt === '') {
        $errors[] = 'Le résumé est obligatoire.';
    } elseif (mb_strlen($excerpt) > 255) {
        $errors[] = 'Le résumé est trop long.';
    }

    if ($content === '') {
        $errors[] = 'Le contenu est obligatoire.';
    }

    if ($errors === []) {
        $slug = make_unique_slug($pdo, $title, $isEdit ? $articleId : null);
        $now = date('Y-m-d H:i:s');

        if ($isEdit) {
            // Gestion de published_at : on la pose à la première publication
            $publishedAt = $article['published_at'];
            if ($isPublished && empty($publishedAt)) {
                $publishedAt = $now;
            } elseif (!$isPublished) {
                $publishedAt = null;
            }

            $update = $pdo->prepare(
                'UPDATE article SET title = ?, slug = ?, excerpt = ?, content = ?,
                 is_published = ?, updated_at = ?, published_at = ? WHERE id = ?'
            );
            $update->execute([
                $title, $slug, $excerpt, $content, $isPublished, $now, $publishedAt, $articleId,
            ]);
            flash_add('success', 'Article mis à jour.');
        } else {
            $publishedAt = $isPublished ? $now : null;
            $insert = $pdo->prepare(
                'INSERT INTO article (author_id, title, slug, excerpt, content, is_published, created_at, updated_at, published_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insert->execute([
                (int) $user['id'], $title, $slug, $excerpt, $content, $isPublished, $now, $now, $publishedAt,
            ]);
            flash_add('success', 'Article créé avec succès.');
        }

        redirect('admin/index.php');
    }

    // En cas d'erreur on garde les valeurs saisies
    $article['title'] = $title;
    $article['excerpt'] = $excerpt;
    $article['content'] = $content;
    $article['is_published'] = $isPublished;
}

$tokenKey = $isEdit ? 'edit_article_' . $articleId : 'new_article';

require __DIR__ . '/../includes/header.php';
?>

<section class="mn-form-page" data-animate="fade-up">
    <a class="mn-back-link" href="<?= e(url('admin/index.php')) ?>">← Retour à l'administration</a>
    <h1><?= e($isEdit ? 'Modifier l\'article' : 'Nouvel article') ?></h1>

    <?php if ($errors !== []): ?>
        <div class="alert alert-danger mt-3">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-4">
        <div class="mb-3">
            <label for="title" class="form-label">Titre</label>
            <input type="text" class="form-control" id="title" name="title"
                   value="<?= e($article['title']) ?>" required maxlength="180">
        </div>

        <div class="mb-3">
            <label for="excerpt" class="form-label">Résumé</label>
            <input type="text" class="form-control" id="excerpt" name="excerpt"
                   value="<?= e($article['excerpt']) ?>" required maxlength="255">
        </div>

        <div class="mb-3">
            <label for="content" class="form-label">Contenu</label>
            <textarea class="form-control" id="content" name="content" rows="8" required><?= e($article['content']) ?></textarea>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_published" name="is_published" value="1"
                <?= (int) $article['is_published'] === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_published">Publier l'article</label>
        </div>

        <input type="hidden" name="_token" value="<?= e(csrf_token($tokenKey)) ?>">
        <button class="btn mn-btn mn-btn-primary" type="submit"><?= e($buttonLabel) ?></button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
