<?php
/**
 * Administration — liste de tous les articles (brouillons inclus).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

$pageTitle = 'Administration des articles - MiniNews';

$stmt = db()->query(
    'SELECT a.*, u.display_name AS author_name
     FROM article a
     INNER JOIN users u ON u.id = a.author_id
     ORDER BY a.created_at DESC'
);
$articles = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<section class="mn-admin-header" data-animate="fade-up">
    <div>
        <p class="mn-eyebrow">Administration</p>
        <h1>Articles</h1>
    </div>
    <a class="btn mn-btn mn-btn-primary" href="<?= e(url('admin/article_form.php')) ?>">Nouvel article</a>
</section>

<div class="table-responsive mn-admin-table" data-animate="fade-up">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Statut</th>
                <th>Auteur</th>
                <th>Créé le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($articles) === 0): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Aucun article pour le moment.</td></tr>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($article['title']) ?></td>
                        <td>
                            <?php if ((int) $article['is_published'] === 1): ?>
                                <span class="mn-badge mn-badge-success">Publié</span>
                            <?php else: ?>
                                <span class="mn-badge mn-badge-muted">Brouillon</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($article['author_name']) ?></td>
                        <td><?= e(format_date($article['created_at'])) ?></td>
                        <td class="mn-actions">
                            <?php if ((int) $article['is_published'] === 1): ?>
                                <a class="btn mn-btn mn-btn-sm mn-btn-light"
                                   href="<?= e(url('article.php?slug=' . rawurlencode($article['slug']))) ?>">Voir</a>
                            <?php endif; ?>
                            <a class="btn mn-btn mn-btn-sm mn-btn-light"
                               href="<?= e(url('admin/article_form.php?id=' . (int) $article['id'])) ?>">Modifier</a>
                            <form method="post" action="<?= e(url('admin/article_delete.php')) ?>" data-confirm-delete class="d-inline">
                                <input type="hidden" name="id" value="<?= (int) $article['id'] ?>">
                                <input type="hidden" name="_token" value="<?= e(csrf_token('delete_article_' . $article['id'])) ?>">
                                <button type="submit" class="mn-link-danger">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
