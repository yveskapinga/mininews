<?php
/**
 * Suppression d'un article (POST uniquement + confirmation JS côté client).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/index.php');
}

$articleId = (int) ($_POST['id'] ?? 0);

if ($articleId > 0 && csrf_verify('delete_article_' . $articleId, $_POST['_token'] ?? null)) {
    // Les commentaires partent en cascade grâce à la FK ON DELETE CASCADE
    $stmt = db()->prepare('DELETE FROM article WHERE id = ?');
    $stmt->execute([$articleId]);
    flash_add('success', 'Article supprimé.');
}

redirect('admin/index.php');
