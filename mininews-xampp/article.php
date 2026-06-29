<?php
/**
 * Détail d'un article + formulaire de commentaire.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '') {
    flash_add('error', 'Article introuvable.');
    redirect('index.php');
}

// Jointure pour récupérer le nom de l'auteur en une seule requête
$stmt = db()->prepare(
    'SELECT a.*, u.display_name AS author_name
     FROM article a
     INNER JOIN users u ON u.id = a.author_id
     WHERE a.slug = ?
     LIMIT 1'
);
$stmt->execute([$slug]);
$article = $stmt->fetch();

// Un brouillon ne doit pas être visible même si on devine le slug dans l'URL
if ($article === false || (int) $article['is_published'] !== 1) {
    flash_add('error', 'Article introuvable ou non publié.');
    redirect('index.php');
}

// Traitement du formulaire de commentaire (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    require_login();

    if (!csrf_verify('comment_form', $_POST['_token'] ?? null)) {
        flash_add('error', 'Formulaire invalide, réessayez.');
        redirect('article.php?slug=' . rawurlencode($slug));
    }

    $content = trim((string) $_POST['comment_content']);
    $errors = [];

    if ($content === '') {
        $errors[] = 'Le commentaire ne peut pas être vide.';
    } elseif (mb_strlen($content) < 3) {
        $errors[] = 'Le commentaire est trop court (3 caractères minimum).';
    } elseif (mb_strlen($content) > 1000) {
        $errors[] = 'Le commentaire est trop long.';
    }

    if ($errors !== []) {
        flash_add('error', implode(' ', $errors));
    } else {
        $user = current_user();
        $insert = db()->prepare(
            'INSERT INTO comments (article_id, author_id, content, like_count, dislike_count, created_at)
             VALUES (?, ?, ?, 0, 0, NOW())'
        );
        $insert->execute([(int) $article['id'], (int) $user['id'], $content]);
        flash_add('success', 'Votre commentaire a été publié.');
    }

    // Redirection après POST pour éviter le double envoi au F5
    redirect('article.php?slug=' . rawurlencode($slug) . '#commentaires');
}

// Liste des commentaires du plus récent au plus ancien
$commentsStmt = db()->prepare(
    'SELECT c.*, u.display_name AS author_name, u.id AS author_user_id
     FROM comments c
     INNER JOIN users u ON u.id = c.author_id
     WHERE c.article_id = ?
     ORDER BY c.created_at DESC'
);
$commentsStmt->execute([(int) $article['id']]);
$comments = $commentsStmt->fetchAll();

// Réactions de l'utilisateur connecté (pour surligner like/dislike actifs)
$userReactions = [];
if (is_logged_in()) {
    $user = current_user();
    $reactStmt = db()->prepare(
        'SELECT cr.comment_id, cr.value
         FROM comment_reaction cr
         INNER JOIN comments c ON c.id = cr.comment_id
         WHERE c.article_id = ? AND cr.user_id = ?'
    );
    $reactStmt->execute([(int) $article['id'], (int) $user['id']]);
    foreach ($reactStmt->fetchAll() as $row) {
        $userReactions[(int) $row['comment_id']] = (int) $row['value'];
    }
}

$pageTitle = $article['title'] . ' - MiniNews';

require __DIR__ . '/includes/header.php';
?>

<article class="mn-article-detail" data-animate="fade-up">
    <a class="mn-back-link" href="<?= e(url('index.php')) ?>">← Retour aux actualités</a>
    <h1><?= e($article['title']) ?></h1>
    <p class="mn-meta">
        Par <?= e($article['author_name']) ?> ·
        <?= e(format_datetime($article['published_at'] ?? $article['created_at'])) ?>
    </p>
    <p class="mn-lead"><?= e($article['excerpt']) ?></p>
    <div class="mn-article-content"><?= nl2br_safe($article['content']) ?></div>
</article>

<section class="mn-comments" id="commentaires" data-animate="fade-up">
    <div class="mn-comments-title">
        <h2>Commentaires</h2>
        <span class="mn-comment-count"><?= count($comments) ?></span>
    </div>

    <?php if (is_logged_in()): ?>
        <div class="mn-comment-form">
            <form method="post" class="mb-0">
                <input type="hidden" name="_token" value="<?= e(csrf_token('comment_form')) ?>">
                <div class="mb-3">
                    <label for="comment_content" class="form-label">Votre commentaire</label>
                    <textarea class="form-control" id="comment_content" name="comment_content" rows="3"
                              placeholder="Écrivez un commentaire constructif..." required></textarea>
                </div>
                <button class="btn mn-btn mn-btn-primary" type="submit">Publier le commentaire</button>
            </form>
        </div>
    <?php else: ?>
        <div class="mn-login-notice mb-4">
            <a href="<?= e(url('login.php')) ?>">Connectez-vous</a> pour laisser un commentaire.
        </div>
    <?php endif; ?>

    <div class="mn-comment-list">
        <?php if (count($comments) === 0): ?>
            <div class="mn-empty-state">
                <p class="mb-0">Aucun commentaire pour le moment. Soyez le premier à réagir !</p>
            </div>
        <?php else: ?>
            <?php foreach ($comments as $comment): ?>
                <?php
                $isOwn = is_logged_in() && (int) current_user()['id'] === (int) $comment['author_user_id'];
                $isLiked = isset($userReactions[(int) $comment['id']]) && $userReactions[(int) $comment['id']] === REACTION_LIKE;
                $isDisliked = isset($userReactions[(int) $comment['id']]) && $userReactions[(int) $comment['id']] === REACTION_DISLIKE;
                $initial = mb_strtoupper(mb_substr($comment['author_name'], 0, 1));
                ?>
                <article class="mn-comment-card">
                    <div class="mn-comment-avatar" aria-hidden="true"><?= e($initial) ?></div>
                    <div class="mn-comment-body">
                        <div class="mn-comment-header">
                            <span class="mn-comment-author"><?= e($comment['author_name']) ?></span>
                            <time class="mn-comment-date"><?= e(format_datetime($comment['created_at'])) ?></time>
                        </div>
                        <p class="mn-comment-text"><?= nl2br_safe($comment['content']) ?></p>

                        <div class="mn-comment-actions">
                            <form method="post" action="<?= e(url('react.php')) ?>" class="d-inline">
                                <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                                <input type="hidden" name="type" value="like">
                                <input type="hidden" name="slug" value="<?= e($article['slug']) ?>">
                                <input type="hidden" name="_token" value="<?= e(csrf_token('react_comment_' . $comment['id'])) ?>">
                                <button type="submit" class="mn-reaction-btn <?= $isLiked ? 'is-active-like' : '' ?>"
                                    <?php if (!is_logged_in()): ?>disabled title="Connectez-vous pour réagir"<?php elseif ($isOwn): ?>disabled title="Vous ne pouvez pas réagir à votre propre commentaire"<?php endif; ?>>
                                    👍 <span><?= (int) $comment['like_count'] ?></span>
                                </button>
                            </form>

                            <form method="post" action="<?= e(url('react.php')) ?>" class="d-inline">
                                <input type="hidden" name="comment_id" value="<?= (int) $comment['id'] ?>">
                                <input type="hidden" name="type" value="dislike">
                                <input type="hidden" name="slug" value="<?= e($article['slug']) ?>">
                                <input type="hidden" name="_token" value="<?= e(csrf_token('react_comment_' . $comment['id'])) ?>">
                                <button type="submit" class="mn-reaction-btn <?= $isDisliked ? 'is-active-dislike' : '' ?>"
                                    <?php if (!is_logged_in()): ?>disabled title="Connectez-vous pour réagir"<?php elseif ($isOwn): ?>disabled title="Vous ne pouvez pas réagir à votre propre commentaire"<?php endif; ?>>
                                    👎 <span><?= (int) $comment['dislike_count'] ?></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
