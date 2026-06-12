<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\User;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Controleur d'administration des articles.
 *
 * La route de classe #[Route('/admin/articles')] prefixe toutes les routes des
 * methodes. #[IsGranted('ROLE_ADMIN')] protege toutes les actions: Symfony
 * refuse l'acces si l'utilisateur connecte n'a pas le role administrateur.
 */
#[Route('/admin/articles')]
#[IsGranted('ROLE_ADMIN')]
class ArticleController extends AbstractController
{
    /**
     * Liste les articles pour l'administration.
     *
     * Contrairement a la page publique, cette liste affiche les brouillons et les
     * articles publies afin que l'admin puisse continuer son travail editorial.
     */
    #[Route('', name: 'admin_article_index', methods: ['GET'])]
    public function index(ArticleRepository $articles): Response
    {
        return $this->render('admin/article/index.html.twig', [
            'articles' => $articles->findForAdmin(),
        ]);
    }

    /**
     * Cree un nouvel article.
     *
     * Le formulaire ArticleType sait quels champs afficher. Le controleur reste
     * responsable de ce qui ne doit pas etre choisi par l'utilisateur: l'auteur et
     * le slug unique.
     */
    #[Route('/new', name: 'admin_article_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ArticleRepository $articles, SluggerInterface $slugger): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // getUser() retourne l'utilisateur connecte du firewall Security.
            $user = $this->getUser();
            if ($user instanceof User) {
                $article->setAuthor($user);
            }

            // Le slug depend du titre saisi, donc on le genere apres validation.
            $this->generateUniqueSlug($article, $articles, $slugger);

            // Nouvel objet: persist() puis flush().
            $entityManager->persist($article);
            $entityManager->flush();

            $this->addFlash('success', 'Article créé avec succès.');

            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('admin/article/form.html.twig', [
            'form' => $form,
            'title' => 'Nouvel article',
            'buttonLabel' => 'Créer',
        ]);
    }

    /**
     * Modifie un article existant.
     *
     * L'argument Article $article est resolu automatiquement par Symfony grace a
     * l'id present dans l'URL. C'est le ParamConverter/EntityValueResolver: il
     * evite d'ecrire manuellement find($id) dans chaque action.
     */
    #[Route('/{id}/edit', name: 'admin_article_edit', methods: ['GET', 'POST'])]
    public function edit(Article $article, Request $request, EntityManagerInterface $entityManager, ArticleRepository $articles, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->generateUniqueSlug($article, $articles, $slugger);

            // Objet deja connu de Doctrine: pas besoin de persist(), flush()
            // suffit pour synchroniser les changements avec PostgreSQL.
            $entityManager->flush();

            $this->addFlash('success', 'Article mis à jour.');

            return $this->redirectToRoute('admin_article_index');
        }

        return $this->render('admin/article/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier l’article',
            'buttonLabel' => 'Enregistrer',
        ]);
    }

    /**
     * Supprime un article apres verification CSRF.
     *
     * La suppression est en POST pour eviter qu'un simple lien GET declenche une
     * action destructive. Le token CSRF prouve que le formulaire vient bien de
     * notre application.
     */
    #[Route('/{id}/delete', name: 'admin_article_delete', methods: ['POST'])]
    public function delete(Article $article, Request $request, EntityManagerInterface $entityManager): Response
    {
        $token = (string) $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete_article_' . $article->getId(), $token)) {
            $entityManager->remove($article);
            $entityManager->flush();
            $this->addFlash('success', 'Article supprimé.');
        }

        return $this->redirectToRoute('admin_article_index');
    }

    /**
     * Genere un slug unique a partir du titre de l'article.
     *
     * SluggerInterface transforme une phrase en texte compatible URL. Ensuite,
     * on interroge le repository pour eviter les doublons: si un slug existe deja,
     * on essaye mon-titre-2, puis mon-titre-3, etc.
     */
    private function generateUniqueSlug(Article $article, ArticleRepository $articles, SluggerInterface $slugger): void
    {
        $baseSlug = mb_strtolower($slugger->slug((string) $article->getTitle())->toString()) ?: 'article';
        $slug = $baseSlug;
        $counter = 2;

        while (($existing = $articles->findOneBy(['slug' => $slug])) !== null && $existing !== $article) {
            $slug = sprintf('%s-%d', $baseSlug, $counter);
            ++$counter;
        }

        $article->setSlug($slug);
    }
}
