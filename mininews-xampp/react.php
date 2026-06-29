<?php
/**
 * Traitement des likes / dislikes sur un commentaire.
 * Fichier séparé parce que c'est une action POST qui redirige ensuite.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

require_login();

$commentId = (int) ($_POST['comment_id'] ?? 0);
$type = (string) ($_POST['type'] ?? 'like');
$slug = trim((string) ($_POST['slug'] ?? ''));
$newValue = $type === 'dislike' ? REACTION_DISLIKE : REACTION_LIKE;

$user = current_user();

if (!csrf_verify('react_comment_' . $commentId, $_POST['_token'] ?? null)) {
    flash_add('error', 'Token CSRF invalide : réaction refusée.');
    redirect('article.php?slug=' . rawurlencode($slug));
}

// Récupérer le commentaire + vérifier qu'il existe
$stmt = db()->prepare(
    'SELECT c.*, a.slug AS article_slug
     FROM comments c
     INNER JOIN article a ON a.id = c.article_id
     WHERE c.id = ?
     LIMIT 1'
);
$stmt->execute([$commentId]);
$comment = $stmt->fetch();

if ($comment === false) {
    flash_add('error', 'Commentaire introuvable.');
    redirect('index.php');
}

$slug = $comment['article_slug'];

// On ne peut pas voter sur son propre commentaire (règle métier du TP)
if ((int) $comment['author_id'] === (int) $user['id']) {
    flash_add('error', 'Vous ne pouvez pas réagir à votre propre commentaire.');
    redirect('article.php?slug=' . rawurlencode($slug));
}

$pdo = db();

// Y a-t-il déjà une réaction de cet utilisateur sur ce commentaire ?
$existingStmt = $pdo->prepare(
    'SELECT * FROM comment_reaction WHERE comment_id = ? AND user_id = ? LIMIT 1'
);
$existingStmt->execute([$commentId, (int) $user['id']]);
$existing = $existingStmt->fetch();

try {
    $pdo->beginTransaction();

    if ($existing !== false && (int) $existing['value'] === $newValue) {
        // Même bouton recliqué → on retire la réaction
        $pdo->prepare('DELETE FROM comment_reaction WHERE id = ?')->execute([(int) $existing['id']]);

        if ($newValue === REACTION_DISLIKE) {
            $pdo->prepare('UPDATE comments SET dislike_count = GREATEST(dislike_count - 1, 0) WHERE id = ?')
                ->execute([$commentId]);
        } else {
            $pdo->prepare('UPDATE comments SET like_count = GREATEST(like_count - 1, 0) WHERE id = ?')
                ->execute([$commentId]);
        }
    } elseif ($existing !== false) {
        // Changement d'avis : like → dislike ou l'inverse
        $pdo->prepare('UPDATE comment_reaction SET value = ? WHERE id = ?')
            ->execute([$newValue, (int) $existing['id']]);

        if ((int) $existing['value'] === REACTION_DISLIKE) {
            $pdo->prepare('UPDATE comments SET dislike_count = GREATEST(dislike_count - 1, 0), like_count = like_count + 1 WHERE id = ?')
                ->execute([$commentId]);
        } else {
            $pdo->prepare('UPDATE comments SET like_count = GREATEST(like_count - 1, 0), dislike_count = dislike_count + 1 WHERE id = ?')
                ->execute([$commentId]);
        }
    } else {
        // Première réaction
        $pdo->prepare(
            'INSERT INTO comment_reaction (comment_id, user_id, value, created_at) VALUES (?, ?, ?, NOW())'
        )->execute([$commentId, (int) $user['id'], $newValue]);

        if ($newValue === REACTION_DISLIKE) {
            $pdo->prepare('UPDATE comments SET dislike_count = dislike_count + 1 WHERE id = ?')->execute([$commentId]);
        } else {
            $pdo->prepare('UPDATE comments SET like_count = like_count + 1 WHERE id = ?')->execute([$commentId]);
        }
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    flash_add('error', 'Erreur lors de l\'enregistrement de la réaction.');
}

redirect('article.php?slug=' . rawurlencode($slug) . '#commentaires');
