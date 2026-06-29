<?php
/**
 * Promeut un lecteur en administrateur (ajoute ROLE_ADMIN dans le JSON roles).
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/users.php');
}

$userId = (int) ($_POST['user_id'] ?? 0);

if ($userId <= 0 || !csrf_verify('promote_user_' . $userId, $_POST['_token'] ?? null)) {
    flash_add('error', 'Token CSRF invalide : la promotion a été refusée.');
    redirect('admin/users.php');
}

$stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$target = $stmt->fetch();

if ($target === false) {
    flash_add('error', 'Utilisateur introuvable.');
    redirect('admin/users.php');
}

if (user_has_role($target, 'ROLE_ADMIN')) {
    flash_add('success', 'Cet utilisateur est déjà administrateur.');
    redirect('admin/users.php');
}

$roles = json_decode($target['roles'], true);
if (!is_array($roles)) {
    $roles = [];
}
$roles[] = 'ROLE_ADMIN';
$roles = array_values(array_unique($roles));

$update = db()->prepare('UPDATE users SET roles = ? WHERE id = ?');
$update->execute([json_encode($roles, JSON_THROW_ON_ERROR), $userId]);

flash_add('success', $target['email'] . ' est maintenant administrateur.');

redirect('admin/users.php');
