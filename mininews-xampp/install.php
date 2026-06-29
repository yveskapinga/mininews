<?php
/**
 * Script d'installation one-shot pour le TP.
 * Ouvre http://localhost/mininews-xampp/install.php si la base n'est pas encore créée.
 * À supprimer après installation.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=utf-8');

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$schemaFile = __DIR__ . '/sql/schema.sql';
$mysqlBin = 'C:\\xampp\\mysql\\bin\\mysql.exe';

echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Installation MiniNews</title>';
echo '<style>body{font-family:sans-serif;max-width:640px;margin:2rem auto;padding:0 1rem}';
echo '.ok{color:#0f766e}.err{color:#b91c1c}code{background:#f3f4f6;padding:2px 6px;border-radius:4px}</style></head><body>';
echo '<h1>Installation MiniNews</h1>';

if (!is_readable($schemaFile)) {
    echo '<p class="err">Fichier sql/schema.sql introuvable.</p></body></html>';
    exit;
}

// Méthode 1 : client mysql XAMPP (le plus fiable pour un gros fichier SQL)
if (is_file($mysqlBin)) {
    $schemaPath = str_replace('\\', '/', $schemaFile);
    $cmd = '"' . $mysqlBin . '" -u root -e "SOURCE ' . $schemaPath . '" 2>&1';
    exec($cmd, $output, $code);

    if ($code === 0) {
        try {
            $pdo = new PDO('mysql:host=127.0.0.1;dbname=mininews;charset=utf8mb4', 'root', '');
            $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
            echo '<p class="ok"><strong>Installation terminée.</strong></p>';
            echo '<p>Utilisateurs en base : ' . $count . '</p>';
            echo '<ul><li><a href="index.php">Aller sur MiniNews</a></li>';
            echo '<li>Admin : <code>admin@example.test</code> / <code>SecretAdm1n!</code></li>';
            echo '<li>Lecteur : <code>user@example.test</code> / <code>MonMotDePasse!</code></li></ul>';
            echo '<p><em>Tu peux supprimer install.php maintenant.</em></p></body></html>';
            exit;
        } catch (Throwable $e) {
            echo '<p class="err">Import OK mais vérification échouée : <code>' . h($e->getMessage()) . '</code></p>';
        }
    } else {
        echo '<p class="err">Erreur mysql :</p><pre>' . h(implode("\n", $output)) . '</pre>';
    }
}

// Méthode 2 : PDO direct (si mysql.exe absent)
try {
    $pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('CREATE DATABASE IF NOT EXISTS mininews CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $pdo->exec('USE mininews');

    $sql = file_get_contents($schemaFile);
    $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', (string) $sql) ?? '';
    $sql = preg_replace('/USE\s+mininews\s*;/i', '', $sql) ?? '';

    foreach (preg_split('/;\r?\n/', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '' || str_starts_with($stmt, '--')) {
            continue;
        }
        try {
            $pdo->exec($stmt);
        } catch (PDOException $e) {
            if (!str_contains($e->getMessage(), 'already exists') && !str_contains($e->getMessage(), 'Duplicate')) {
                throw $e;
            }
        }
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo '<p class="ok"><strong>Installation terminée (via PDO).</strong></p>';
    echo '<p>Utilisateurs en base : ' . $count . '</p>';
    echo '<ul><li><a href="index.php">Aller sur MiniNews</a></li></ul></body></html>';
} catch (Throwable $e) {
    echo '<p class="err"><strong>Échec.</strong> Démarre MySQL dans XAMPP puis réessaie.</p>';
    echo '<p><code>' . h($e->getMessage()) . '</code></p></body></html>';
}
