<?php
/**
 * Petites fonctions utilitaires partagées entre les pages.
 * Plutôt que de tout recopier, je les mets ici.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** Échappe le HTML pour éviter les failles XSS quand on affiche des données utilisateur */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Construit une URL relative à partir du chemin de base du projet */
function url(string $path = ''): string
{
    $base = rtrim(BASE_PATH, '/');
    $path = ltrim($path, '/');

    return $path === '' ? $base . '/' : $base . '/' . $path;
}

/** Formate une date MySQL pour l'affichage (ex: 29/06/2026 14:30) */
function format_datetime(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $datetime);

    return $dt ? $dt->format('d/m/Y H:i') : e($datetime);
}

function format_date(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return '';
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $datetime);

    return $dt ? $dt->format('d/m/Y') : e($datetime);
}

/** Transforme les retours à la ligne en <br> (comme nl2br de Twig) */
function nl2br_safe(?string $text): string
{
    return nl2br(e($text));
}

/** Messages flash : stockés en session puis affichés une fois dans header.php */
function flash_add(string $type, string $message): void
{
    $_SESSION['flashes'][] = ['type' => $type, 'message' => $message];
}

/** @return list<array{type: string, message: string}> */
function flash_consume(): array
{
    $flashes = $_SESSION['flashes'] ?? [];
    unset($_SESSION['flashes']);

    return $flashes;
}

/** Token CSRF simple — le prof en parle souvent en cours de sécurité web */
function csrf_token(string $key): string
{
    if (!isset($_SESSION['csrf_tokens'][$key])) {
        $_SESSION['csrf_tokens'][$key] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_tokens'][$key];
}

function csrf_verify(string $key, ?string $token): bool
{
    if ($token === null || $token === '') {
        return false;
    }

    $expected = $_SESSION['csrf_tokens'][$key] ?? '';

    return $expected !== '' && hash_equals($expected, $token);
}

/**
 * Génère un slug à partir du titre (pour l'URL de l'article).
 * J'ai fait une version simple sans lib externe : accents enlevés, espaces → tirets.
 */
function slugify(string $title): string
{
    $slug = mb_strtolower(trim($title));
    $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug) ?: $slug;
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'article';
}

/** Vérifie que le slug n'existe pas déjà (ou qu'on modifie le même article) */
function make_unique_slug(PDO $pdo, string $title, ?int $excludeId = null): string
{
    $base = slugify($title);
    $slug = $base;
    $counter = 2;

    while (true) {
        if ($excludeId !== null) {
            $stmt = $pdo->prepare('SELECT id FROM article WHERE slug = ? AND id != ? LIMIT 1');
            $stmt->execute([$slug, $excludeId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM article WHERE slug = ? LIMIT 1');
            $stmt->execute([$slug]);
        }

        if ($stmt->fetch() === false) {
            return $slug;
        }

        $slug = $base . '-' . $counter;
        ++$counter;
    }
}

/** Redirection HTTP classique */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Vérifie si un utilisateur a un rôle donné (roles stockés en JSON en base) */
function user_has_role(array $user, string $role): bool
{
    $roles = json_decode($user['roles'] ?? '[]', true);
    if (!is_array($roles)) {
        $roles = [];
    }

    // ROLE_USER est toujours implicite, comme dans la version Symfony
    $roles[] = 'ROLE_USER';

    return in_array($role, array_unique($roles), true);
}

const REACTION_LIKE = 1;
const REACTION_DISLIKE = -1;
