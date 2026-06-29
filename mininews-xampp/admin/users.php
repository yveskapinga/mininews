<?php
/**
 * Liste des utilisateurs + bouton pour promouvoir un lecteur en admin.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

$pageTitle = 'Administration des utilisateurs - MiniNews';

$users = db()->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();

require __DIR__ . '/../includes/header.php';
?>

<section class="mn-admin-header" data-animate="fade-up">
    <div>
        <p class="mn-eyebrow">Administration</p>
        <h1>Utilisateurs</h1>
    </div>
    <a class="btn mn-btn mn-btn-light" href="<?= e(url('admin/index.php')) ?>">Retour aux articles</a>
</section>

<div class="table-responsive mn-admin-table" data-animate="fade-up">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Nom affiché</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Créé le</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($users) === 0): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Aucun utilisateur pour le moment.</td></tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($u['display_name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td>
                            <?php if (user_has_role($u, 'ROLE_ADMIN')): ?>
                                <span class="mn-badge mn-badge-success">Administrateur</span>
                            <?php else: ?>
                                <span class="mn-badge mn-badge-muted">Lecteur</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(format_date($u['created_at'])) ?></td>
                        <td class="mn-actions">
                            <?php if (user_has_role($u, 'ROLE_ADMIN')): ?>
                                <span class="text-muted">Déjà admin</span>
                            <?php else: ?>
                                <form method="post" action="<?= e(url('admin/user_promote.php')) ?>">
                                    <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                    <input type="hidden" name="_token" value="<?= e(csrf_token('promote_user_' . $u['id'])) ?>">
                                    <button type="submit" class="btn mn-btn mn-btn-sm mn-btn-primary">Promouvoir admin</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
