<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\CommentReaction;
use App\Entity\User;
use App\Form\CommentType;
use App\Repository\ArticleRepository;
use App\Repository\CommentReactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controleur public de MiniNews.
 *
 * Un controleur Symfony recoit une requete HTTP, appelle les services utiles
 * (repository, formulaire, EntityManager), puis retourne une Response. Ici, il
 * gere la page d'accueil, la page de detail d'un article et les reactions aux
 * commentaires.
 */
class NewsController extends AbstractController
{
    /**
     * Page d'accueil publique.
     *
     * #[Route] relie l'URL / a cette methode. Le type ArticleRepository dans les
     * arguments active l'injection de dependance: Symfony cree ou recupere le
     * repository et le passe automatiquement a la methode.
     */
    #[Route('/', name: 'news_index', methods: ['GET'])]
    public function index(ArticleRepository $articles): Response
    {
        // render() execute le template Twig et lui fournit les variables utiles.
        return $this->render('news/index.html.twig', [
            'articles' => $articles->findPublished(),
        ]);
    }

    /**
     * Detail d'un article + traitement du formulaire de commentaire.
     *
     * La route contient {slug}: Symfony extrait cette partie de l'URL et la place
     * dans l'argument $slug. Comme la methode accepte GET et POST, le meme code
     * affiche le formulaire et traite sa soumission.
     */
    #[Route('/article/{slug}', name: 'news_show', methods: ['GET', 'POST'])]
    public function show(
        string $slug,
        Request $request,
        ArticleRepository $articles,
        CommentReactionRepository $reactions,
        EntityManagerInterface $entityManager,
    ): Response {
        // On cherche l'article par son slug, car l'URL publique ne contient pas l'id.
        $article = $articles->findOneBy(['slug' => $slug]);

        // Un brouillon ne doit pas etre visible publiquement, meme si quelqu'un
        // devine son slug. createNotFoundException produit une erreur HTTP 404.
        if ($article === null || !$article->isPublished()) {
            throw $this->createNotFoundException('Article introuvable ou non publié.');
        }

        // Le formulaire est construit autour d'un nouvel objet Comment. Si la
        // requete est POST, handleRequest() copie les donnees envoyees dans l'objet.
        $comment = new Comment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        // isSubmitted() detecte l'envoi du formulaire. isValid() declenche les
        // contraintes placees dans l'entite Comment et dans CommentType.
        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            // Meme si le formulaire n'est affiche qu'aux utilisateurs connectes,
            // on garde un controle cote serveur: le navigateur n'est jamais fiable.
            $this->denyAccessUnlessGranted('ROLE_USER');

            $user = $this->getUser();
            if (!$user instanceof User) {
                throw $this->createAccessDeniedException('Vous devez être connecté pour commenter.');
            }

            // Les champs article et author ne viennent pas du formulaire: le
            // serveur les impose pour eviter qu'un utilisateur falsifie la cible.
            $comment->setArticle($article);
            $comment->setAuthor($user);

            // persist() signale a Doctrine qu'un nouvel objet doit etre insere.
            // flush() execute reellement les requetes SQL en base de donnees.
            $entityManager->persist($comment);
            $entityManager->flush();

            // Flash message standard Symfony: il survit a la redirection, puis est
            // consomme par Twig. assets/app.js le fait ensuite disparaitre apres
            // 5 secondes cote navigateur.
            $this->addFlash('success', 'Votre commentaire a été publié.');

            // Redirection apres POST: evite de reposter le commentaire si l'on
            // rafraichit la page dans le navigateur.
            return $this->redirectToRoute('news_show', ['slug' => $article->getSlug()]);
        }

        // On prepare un dictionnaire des reactions de l'utilisateur courant pour
        // que le template puisse mettre en evidence les boutons deja actives.
        $userReactions = [];
        $user = $this->getUser();
        if ($user instanceof User) {
            $userReactions = $reactions->findUserReactionsForComments($user, $article->getComments()->toArray());
        }

        return $this->render('news/show.html.twig', [
            'article' => $article,
            'commentForm' => $commentForm,
            'userReactions' => $userReactions,
        ]);
    }

    /**
     * Ajoute, remplace ou retire une reaction sur un commentaire.
     *
     * La route est en POST car elle modifie la base. Elle redirige ensuite vers
     * l'article parent pour garder une navigation simple et compatible sans AJAX.
     */
    #[Route('/comment/{id}/react/{type}', name: 'comment_react', methods: ['POST'])]
    public function react(Comment $comment, string $type, Request $request, CommentReactionRepository $reactions, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour réagir.');
        }

        $article = $comment->getArticle();
        if ($article === null) {
            throw $this->createNotFoundException('Article du commentaire introuvable.');
        }

        if (!$this->isCsrfTokenValid('react_comment_' . $comment->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide: réaction refusée.');

            return $this->redirectToRoute('news_show', ['slug' => $article->getSlug()]);
        }

        // L'expression "commentaires des autres" est appliquee cote serveur: meme
        // si un utilisateur modifie le HTML, il ne peut pas voter sur son message.
        if ($comment->getAuthor()?->getId() === $user->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas réagir à votre propre commentaire.');

            return $this->redirectToRoute('news_show', ['slug' => $article->getSlug()]);
        }

        $newValue = $type === 'dislike' ? CommentReaction::DISLIKE : CommentReaction::LIKE;
        $reaction = $reactions->findUserReaction($comment, $user);

        if ($reaction instanceof CommentReaction && $reaction->getValue() === $newValue) {
            // Meme bouton clique deux fois: on retire la reaction existante.
            $this->decrementCounter($comment, $reaction->getValue());
            $entityManager->remove($reaction);
        } elseif ($reaction instanceof CommentReaction) {
            // L'utilisateur change d'avis: on retire l'ancien compteur puis on
            // ajoute le nouveau sans creer une deuxieme ligne en base.
            $this->decrementCounter($comment, $reaction->getValue());
            $reaction->setValue($newValue);
            $this->incrementCounter($comment, $newValue);
        } else {
            // Premiere reaction de cet utilisateur sur ce commentaire.
            $reaction = (new CommentReaction())
                ->setComment($comment)
                ->setUser($user)
                ->setValue($newValue);

            $entityManager->persist($reaction);
            $this->incrementCounter($comment, $newValue);
        }

        $entityManager->flush();

        return $this->redirectToRoute('news_show', ['slug' => $article->getSlug()]);
    }

    /** Incremente le bon compteur selon la reaction. */
    private function incrementCounter(Comment $comment, int $value): void
    {
        $value === CommentReaction::DISLIKE ? $comment->incrementDislikeCount() : $comment->incrementLikeCount();
    }

    /** Decremente le bon compteur selon la reaction precedente. */
    private function decrementCounter(Comment $comment, int $value): void
    {
        $value === CommentReaction::DISLIKE ? $comment->decrementDislikeCount() : $comment->decrementLikeCount();
    }
}
