<?php
/**
 * Fichier de configuration — connexion PDO à MySQL (XAMPP).
 *
 * J'ai regroupé ici les paramètres de la base pour ne pas les répéter
 * dans chaque page. Si ça ne marche pas chez toi, vérifie que MySQL
 * tourne bien dans le panneau XAMPP.
 */

declare(strict_types=1);

// Identifiants par défaut de XAMPP (root sans mot de passe)
const DB_HOST = '127.0.0.1';
const DB_NAME = 'mininews';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

// Chemin de base pour les liens (à adapter si le dossier n'est pas à la racine de htdocs)
// Exemple : si tu copies dans htdocs/mininews-xampp/, laisse '/mininews-xampp'
const BASE_PATH = '/mininews-xampp';

/**
 * Retourne une connexion PDO réutilisable.
 * J'utilise PDO parce que c'est ce qu'on voit en cours et que les requêtes préparées
 * évitent les injections SQL.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

// On démarre la session une seule fois pour toute l'appli
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
