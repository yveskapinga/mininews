-- MiniNews - script SQL pour MySQL (XAMPP / phpMyAdmin)
-- J'ai adapté le schéma de la version Symfony (PostgreSQL) pour MySQL.
-- À importer dans phpMyAdmin : créer la base "mininews" puis exécuter ce fichier.

CREATE DATABASE IF NOT EXISTS mininews
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mininews;

-- Comptes utilisateurs (lecteurs + admins)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Articles du blog
CREATE TABLE article (
    id INT AUTO_INCREMENT PRIMARY KEY,
    author_id INT NOT NULL,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    excerpt VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    published_at DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_article_slug (slug),
    KEY idx_article_author (author_id),
    CONSTRAINT fk_article_author FOREIGN KEY (author_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Commentaires sous les articles
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    author_id INT NOT NULL,
    content TEXT NOT NULL,
    like_count INT NOT NULL DEFAULT 0,
    dislike_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    KEY idx_comments_article (article_id),
    KEY idx_comments_author (author_id),
    CONSTRAINT fk_comments_article FOREIGN KEY (article_id) REFERENCES article (id) ON DELETE CASCADE,
    CONSTRAINT fk_comments_author FOREIGN KEY (author_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Une seule réaction (like ou dislike) par utilisateur et par commentaire
CREATE TABLE comment_reaction (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    value INT NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE KEY uniq_reaction_user_comment (comment_id, user_id),
    KEY idx_reaction_comment (comment_id),
    KEY idx_reaction_user (user_id),
    CONSTRAINT fk_reaction_comment FOREIGN KEY (comment_id) REFERENCES comments (id) ON DELETE CASCADE,
    CONSTRAINT fk_reaction_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comptes de démo (mots de passe hashés avec password_hash en PHP)
-- Admin : admin@example.test / SecretAdm1n!
-- Lecteur : user@example.test / MonMotDePasse!
INSERT INTO users (email, roles, password, display_name, created_at) VALUES
(
    'admin@example.test',
    '["ROLE_ADMIN"]',
    '$2y$10$26T4xGR.9OUpXYyULJGt2OB5ODVMOXdh99Msol01CgE9IJdVcgLUG',
    'Admin MiniNews',
    NOW()
),
(
    'user@example.test',
    '["ROLE_USER"]',
    '$2y$10$HVEY0FXLD3w/Gms8S2GdXOdox2z98X0gVhcdc3uoi4.AncGGQEv9q',
    'Lecteur MiniNews',
    NOW()
);
