# MiniNews

MiniNews est un mini-blog Symfony pedagogique avec PostgreSQL. Le projet sert de support de lecture pour comprendre comment une application Symfony est organisee: routes, controleurs, formulaires, entites Doctrine, repositories, templates Twig, JavaScript, Bootstrap 5 et migrations. L'interface est responsive, animee, et propose des reactions (likes/dislikes) sur les commentaires.

## Pourquoi Symfony ?

Symfony est un framework PHP. PHP est le langage qui execute le code cote serveur; Symfony est une boite a outils organisee au-dessus de PHP pour construire des applications web plus lisibles, plus maintenables et plus securisees.

Sans framework, une application PHP grossit vite autour de fichiers qui melangent connexion a la base, HTML, securite, validation et logique metier. Symfony separe ces responsabilites. Un controleur gere la requete, une entite represente les donnees, un repository lit la base, un formulaire valide les saisies, Twig affiche le HTML, et la configuration relie tout cela.

L'avantage principal de Symfony est donc l'orchestration. Le framework fournit une structure stable, un conteneur de services, un routeur, un systeme de securite, un moteur de templates, une integration Doctrine pour la base de donnees, une console, des migrations et beaucoup de conventions. L'etudiant peut se concentrer sur le sens du code au lieu de reinventer toute l'infrastructure.

## Relation entre PHP et Symfony

PHP execute les classes qui se trouvent dans `src/`. Symfony n'est pas un autre langage: c'est du PHP organise autour de composants.

Quand une page est demandee, le navigateur envoie une requete HTTP vers `public/index.php`. Ce fichier demarre le noyau Symfony (`src/Kernel.php`). Symfony lit la route, choisit le controleur, injecte les services demandes dans les arguments de la methode, execute le code, puis transforme le resultat en reponse HTTP.

Dans MiniNews, par exemple:

1. Le visiteur ouvre `/article/mon-slug`.
2. Symfony trouve la route `news_show` dans `NewsController`.
3. Le controleur demande `ArticleRepository`, `Request` et `EntityManagerInterface`.
4. Le repository cherche l'article en base.
5. Le controleur prepare le formulaire de commentaire.
6. Twig affiche `templates/news/show.html.twig`.
7. Le navigateur recoit du HTML, du CSS et le JavaScript de `assets/app.js`.

## Comment Symfony orchestre les parties

Le coeur de Symfony est le conteneur de services. Un service est un objet reutilisable: repository, hasher de mot de passe, EntityManager, slugger, mailer, etc. Quand une methode de controleur declare un argument type, Symfony sait souvent quel service fournir automatiquement.

Le routeur associe les URL aux controleurs. Dans ce projet, les routes sont ecrites avec des attributs PHP comme `#[Route('/register', name: 'app_register')]`.

Doctrine fait le lien entre les objets PHP et PostgreSQL. Les classes dans `src/Entity/` decrivent les donnees; les migrations dans `migrations/` creent les tables SQL; les repositories dans `src/Repository/` centralisent les requetes de lecture.

Twig se charge du rendu HTML. Les controleurs lui passent des variables, puis les templates les affichent avec `{{ ... }}` et organisent les conditions/boucles avec `{% ... %}`.

Symfony Security gere l'authentification, les roles et les acces. MiniNews utilise `ROLE_USER` pour les lecteurs connectes et `ROLE_ADMIN` pour l'administration des articles.

## Installation locale avec PostgreSQL

Prerequis:

- PHP 8.2 ou plus.
- Composer.
- PostgreSQL.
- Extension PHP `pdo_pgsql` activee.

Installer les dependances PHP:

```bash
composer install
```

Configurer la base dans `.env`:

```env
DATABASE_URL="postgresql://boyekoli:boyekoli_secret@127.0.0.1:5432/mininews?serverVersion=16&charset=utf8"
```

Creer la base et lancer les migrations:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

Creer un administrateur:

```bash
php bin/console app:create-user admin@example.test SecretAdm1n! "Admin MiniNews" --admin
```

> Le nom affiche ne doit **jamais** etre identique au mot de passe, sinon il serait visible publiquement sous les articles et commentaires.

Creer un lecteur simple:

```bash
php bin/console app:create-user user@example.test MonMotDePasse! "Lecteur MiniNews"
```

Lancer le serveur Symfony:

```bash
symfony server:start
```

Routes utiles:

- `/` : liste publique des articles publies.
- `/article/{slug}` : detail d'un article et commentaires.
- `/login` : connexion.
- `/register` : inscription lecteur.
- `/admin/articles` : administration des articles, reservee a `ROLE_ADMIN`.

## Roadmap de lecture pour l'etudiant

Commence par lire `README.md`, puis suis le trajet d'une requete simple.

1. `public/index.php`

   Point d'entree HTTP. Toutes les requetes web arrivent ici. Le fichier demarre Symfony et transmet la requete au noyau.

2. `config/routes.yaml` et les attributs `#[Route]`

   Les routes peuvent etre configurees en YAML, mais MiniNews utilise surtout les attributs directement dans les controleurs. Cherche `#[Route(...)]` dans `src/Controller/`.

3. `src/Controller/NewsController.php`

   Controleur public. Il affiche la liste des articles, le detail d'un article, les commentaires et les reactions (likes/dislikes). C'est le meilleur fichier pour comprendre le trajet lecture/commentaire.

4. `src/Controller/Admin/ArticleController.php`

   Controleur admin. Il montre la creation, la modification, la suppression, la protection par role et la generation d'un slug.

5. `src/Form/`

   Les formulaires decrivent les champs affiches et la facon dont les donnees sont rattachees aux entites.

6. `src/Entity/`

   Les entites representent les donnees metier: `User`, `Article`, `Comment`. Lis les attributs `#[ORM\...]` pour comprendre la traduction vers PostgreSQL.

7. `src/Repository/`

   Les repositories contiennent les requetes de lecture reutilisables. `ArticleRepository::findPublished()` explique pourquoi seuls certains articles apparaissent sur la page publique.

8. `templates/`

   Twig affiche les donnees. `base.html.twig` est le squelette commun; les autres templates heritent de lui.

9. `assets/app.js`

   Point d'entree JavaScript. Il importe Bootstrap (CSS + JS), notre theme personnalise, et initialise les confirmations de suppression, le defilement doux, les animations d'apparition et la disparition automatique des messages flash.

10. `migrations/Version20260612000100.php`

    Traduction SQL du modele Doctrine vers PostgreSQL. C'est le fichier qui cree les tables.

11. `config/packages/security.yaml`

    Configuration de la connexion, des roles et des acces.

12. `config/packages/doctrine.yaml`

    Configuration du lien entre Doctrine et la base indiquee par `DATABASE_URL`.

## Catalogue des repertoires et fichiers

### `src/Controller/`

- `NewsController.php` : routes publiques `/` et `/article/{slug}`. A consulter pour comprendre l'affichage des articles et la publication d'un commentaire.
- `Admin/ArticleController.php` : routes d'administration `/admin/articles`. A modifier si tu veux changer le flux de creation, modification, suppression ou publication d'un article.
- `RegistrationController.php` : route `/register`. A consulter pour comprendre l'inscription d'un lecteur.
- `SecurityController.php` : routes `/login` et `/logout`. Fonctionne avec `security.yaml`.

### `src/Entity/`

- `Article.php` : champs d'un article, relation avec l'auteur, relation avec les commentaires, dates, publication et slug.
- `Comment.php` : contenu d'un commentaire, article commente, auteur, date de creation, compteurs de likes/dislikes.
- `User.php` : compte utilisateur, email, roles, mot de passe hache, nom affiche (controle de securite : il ne peut pas etre identique au mot de passe), relations avec articles et commentaires.

### `src/Form/`

- `ArticleType.php` : champs du formulaire admin d'article. Pour ajouter un champ a un article, commence ici apres avoir ajoute la propriete dans `Article.php`.
- `CommentType.php` : champ du formulaire de commentaire. Pour ajouter un champ de formulaire de commentaire, c'est le premier fichier a ouvrir.
- `RegistrationFormType.php` : champs du formulaire d'inscription.

### `src/Repository/`

- `ArticleRepository.php` : requetes de lecture des articles. A modifier si tu veux changer l'ordre d'affichage ou le filtre des articles publics.
- `CommentRepository.php` : emplacement prevu pour les futures requetes sur les commentaires.
- `UserRepository.php` : repository utilise par Symfony Security pour les utilisateurs et la mise a jour des hashes de mots de passe.

### `templates/`

- `base.html.twig` : structure HTML commune, navigation, messages flash, chargement JavaScript.
- `news/index.html.twig` : page d'accueil publique avec les cartes d'articles.
- `news/show.html.twig` : detail d'un article, formulaire de commentaire et liste des commentaires.
- `admin/article/index.html.twig` : tableau d'administration des articles.
- `admin/article/form.html.twig` : formulaire commun creation/modification d'article.
- `registration/register.html.twig` : formulaire d'inscription.
- `security/login.html.twig` : formulaire de connexion attendu par Symfony Security.

### `assets/`

- `app.js` : point d'entree JavaScript. Importe Bootstrap 5, le CSS thematique, et branche les interactions (flash auto-disparaissants, animations, confirmation de suppression).
- `styles/app.css` : styles complementaires au theme Bootstrap (palette etudiante, animations, composants MiniNews).
- `stimulus_bootstrap.js` : demarrage de Stimulus/Symfony UX.
- `controllers/hello_controller.js` : exemple de controller Stimulus.
- `controllers/csrf_protection_controller.js` : controller Symfony UX pour la protection CSRF.
- `controllers.json` : active ou desactive certains controllers Symfony UX.

### `config/`

- `packages/security.yaml` : connexion, firewall, roles, acces admin.
- `packages/doctrine.yaml` : configuration Doctrine et lecture de `DATABASE_URL`.
- `packages/twig.yaml` : configuration Twig.
- `packages/framework.yaml` : configuration centrale du framework.
- `routes.yaml` : configuration de routes globale, meme si les routes principales sont dans les attributs PHP.

### `migrations/`

- `Version20260612000100.php` : migration initiale PostgreSQL pour creer `users`, `article` et `comments`.

### `bin/`

- `console` : outil de ligne de commande Symfony. Exemples: migrations, creation d'utilisateur, debug de routes.
- `phpunit` : lance les tests si le projet en contient.

### Racine du projet

- `.env` : variables d'environnement par defaut, notamment `DATABASE_URL`.
- `.env.dev` : valeurs propres a l'environnement de developpement.
- `.env.test` : valeurs propres aux tests.
- `composer.json` : dependances PHP et scripts Composer.
- `importmap.php` : dependances JavaScript chargees par AssetMapper.
- `symfony.lock` : recettes Symfony Flex installees.

## Exemples de modifications guidees

### Ajouter un champ au formulaire de commentaire

1. Ajouter la propriete dans `src/Entity/Comment.php`, par exemple `rating` ou `status`.
2. Ajouter la colonne correspondante via une migration Doctrine.
3. Ajouter le champ dans `src/Form/CommentType.php`.
4. Afficher la nouvelle valeur dans `templates/news/show.html.twig`.
5. Si la logique de sauvegarde change, verifier `src/Controller/NewsController.php`.

### Ajouter un champ a un article

1. Modifier `src/Entity/Article.php`.
2. Generer ou ecrire une migration dans `migrations/`.
3. Ajouter le champ dans `src/Form/ArticleType.php`.
4. Afficher le champ dans `templates/news/index.html.twig`, `templates/news/show.html.twig` ou `templates/admin/article/index.html.twig`.

### Changer qui peut acceder a l'administration

1. Lire `config/packages/security.yaml`.
2. Verifier `#[IsGranted('ROLE_ADMIN')]` dans `src/Controller/Admin/ArticleController.php`.
3. Verifier la creation des utilisateurs dans `src/Command/CreateUserCommand.php`.

### Changer l'ordre des articles publics

1. Ouvrir `src/Repository/ArticleRepository.php`.
2. Modifier `findPublished()`.
3. Recharger `/` pour voir l'effet.

## Commandes utiles pour explorer

```bash
php bin/console debug:router
php bin/console debug:container
php bin/console doctrine:migrations:list
php bin/console doctrine:schema:validate
php bin/console cache:clear
```

Ces commandes montrent respectivement les routes, les services, les migrations, la coherence Doctrine et le nettoyage du cache.
