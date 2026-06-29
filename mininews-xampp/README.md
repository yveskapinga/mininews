# MiniNews — version PHP / MySQL / XAMPP

Version **procédurale** du mini-blog MiniNews, adaptée aux exigences d'un TP (PHP + MySQL sous XAMPP, PDO, HTML dans les fichiers `.php`).

La version Symfony d'origine reste dans le dossier parent (`../`).

## Installation sous XAMPP

### 1. Copier le projet

Copie le dossier `mininews-xampp` dans `C:\xampp\htdocs\` (ou crée un lien symbolique).

### 2. Créer la base MySQL

1. Démarre **Apache** et **MySQL** dans le panneau XAMPP.
2. Ouvre **phpMyAdmin** : http://localhost/phpmyadmin
3. Importe le fichier `sql/schema.sql` (onglet Importer).

### 3. Configurer la connexion

Ouvre `includes/config.php` et vérifie :

```php
const DB_HOST = '127.0.0.1';
const DB_NAME = 'mininews';
const DB_USER = 'root';
const DB_PASS = '';          // mot de passe MySQL XAMPP (souvent vide)
const BASE_PATH = '/mininews-xampp';  // adapte si ton dossier a un autre nom
```

### 4. Tester

Ouvre : http://localhost/mininews-xampp/

## Comptes de démo (créés par schema.sql)

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | admin@example.test | SecretAdm1n! |
| Lecteur | user@example.test | MonMotDePasse! |

## Structure des fichiers

```
mininews-xampp/
├── index.php              Accueil (articles publiés)
├── article.php            Détail + commentaires
├── react.php              Likes / dislikes
├── login.php / register.php / logout.php
├── admin/
│   ├── index.php          Liste articles
│   ├── article_form.php   Créer / modifier
│   ├── article_delete.php Supprimer
│   ├── users.php          Gérer les rôles
│   └── user_promote.php
├── includes/
│   ├── config.php         PDO + session
│   ├── auth.php           Connexion / rôles
│   ├── functions.php      Utilitaires (CSRF, slug, flash…)
│   ├── header.php / footer.php
├── assets/                CSS, JS, images
└── sql/schema.sql         Structure MySQL
```

## Correspondance avec la version Symfony

| Symfony | Version XAMPP |
|---------|---------------|
| `NewsController` | `index.php`, `article.php`, `react.php` |
| `Admin\ArticleController` | `admin/*.php` |
| `RegistrationController` | `register.php` |
| `SecurityController` | `login.php`, `logout.php` |
| Doctrine ORM | Requêtes PDO préparées |
| Twig | HTML dans les `.php` |
| `security.yaml` | `includes/auth.php` + `$_SESSION` |

## Prérequis

- PHP 8.1+ (extension `pdo_mysql` activée)
- MySQL via XAMPP
- Aucun Composer requis
