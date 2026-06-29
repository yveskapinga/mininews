<?php
/**
 * Page d'accueil — liste des articles publiés.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Accueil - MiniNews';

// Seuls les articles publiés apparaissent ici (comme findPublished() en Symfony)
$stmt = db()->query(
    'SELECT a.*, u.display_name AS author_name
     FROM article a
     INNER JOIN users u ON u.id = a.author_id
     WHERE a.is_published = 1
     ORDER BY a.published_at DESC, a.created_at DESC'
);
$articles = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<section class="mn-hero" data-animate="fade-up">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <p class="mn-eyebrow">Travail pratique de programmation</p>
            <h1>MiniNews, un mini-blog d'actualités en PHP et MySQL</h1>
            <p class="mn-hero-text">
                Cette application simule un petit système de News : les administrateurs
                publient des articles, tandis que les utilisateurs connectés peuvent
                les lire, commenter et réagir aux commentaires.
            </p>

            <div class="mn-hero-actions">
                <?php if (is_logged_in()): ?>
                    <a class="btn mn-btn mn-btn-primary" href="#articles" data-smooth-scroll>Voir les actualités</a>
                    <?php if (is_admin()): ?>
                        <a class="btn mn-btn mn-btn-light" href="<?= e(url('admin/index.php')) ?>">Gérer les articles</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a class="btn mn-btn mn-btn-primary" href="<?= e(url('login.php')) ?>">Se connecter</a>
                    <a class="btn mn-btn mn-btn-light" href="<?= e(url('register.php')) ?>">Créer un compte</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <aside class="mn-hero-panel" aria-label="Technologies utilisées">
                <strong>Stack du TP</strong>
                <ul>
                    <li>PHP procédural pour la logique serveur</li>
                    <li>MySQL pour la base de données</li>
                    <li>PDO pour les requêtes préparées</li>
                    <li>HTML + CSS dans les fichiers .php</li>
                    <li>Bootstrap 5 pour le responsive</li>
                    <li>JavaScript pour les petites interactions</li>
                </ul>
            </aside>
        </div>
    </div>
</section>

<section class="row g-4 mb-5" aria-label="Fonctionnement de MiniNews">
    <div class="col-md-4" data-animate="fade-up">
        <article class="mn-feature-card" data-info-card>
            <span class="mn-feature-number">01</span>
            <h2>Administrateurs</h2>
            <p>Ils créent, modifient, publient ou suppriment les articles depuis l'espace d'administration.</p>
        </article>
    </div>
    <div class="col-md-4" data-animate="fade-up">
        <article class="mn-feature-card" data-info-card>
            <span class="mn-feature-number">02</span>
            <h2>Lecteurs connectés</h2>
            <p>Ils consultent les actualités publiées et participent à la discussion avec des commentaires.</p>
        </article>
    </div>
    <div class="col-md-4" data-animate="fade-up">
        <article class="mn-feature-card" data-info-card>
            <span class="mn-feature-number">03</span>
            <h2>Objectif du TP</h2>
            <p>Comprendre comment PHP, MySQL et les sessions permettent de faire une appli web complète.</p>
        </article>
    </div>
</section>

<section id="articles" class="mb-4" data-animate="fade-up">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <p class="mn-eyebrow">Actualités</p>
            <h2>Articles publiés</h2>
        </div>
        <?php if (!is_logged_in()): ?>
            <a class="btn mn-btn mn-btn-light" href="<?= e(url('login.php')) ?>">Connexion pour commenter</a>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <?php if (count($articles) === 0): ?>
            <div class="col-12">
                <div class="mn-empty-state">
                    <h2>Aucune actualité publiée</h2>
                    <p>Connectez-vous en administrateur et publiez le premier article du TP.</p>
                    <?php if (is_admin()): ?>
                        <a class="btn mn-btn mn-btn-primary" href="<?= e(url('admin/article_form.php')) ?>">Publier un article</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($articles as $article): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="mn-article-card">
                        <p class="mn-meta">
                            Publié le <?= e(format_datetime($article['published_at'] ?? $article['created_at'])) ?>
                            <span class="d-block">Par <?= e($article['author_name']) ?></span>
                        </p>
                        <h2>
                            <a href="<?= e(url('article.php?slug=' . rawurlencode($article['slug']))) ?>">
                                <?= e($article['title']) ?>
                            </a>
                        </h2>
                        <p><?= e($article['excerpt']) ?></p>
                        <a class="btn mn-btn mn-btn-light mt-auto"
                           href="<?= e(url('article.php?slug=' . rawurlencode($article['slug']))) ?>">
                            Lire l'article
                        </a>
                    </article>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
