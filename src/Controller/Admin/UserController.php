<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Administration des utilisateurs.
 *
 * Cette partie montre qu'un role Symfony est une information metier stockee dans
 * l'entite User. Promouvoir un utilisateur consiste a ajouter ROLE_ADMIN dans la
 * colonne JSON roles, puis a laisser Symfony Security appliquer ce role.
 */
#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    /**
     * Liste les comptes et affiche un bouton de promotion pour les non-admins.
     */
    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $users): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $users->findForAdmin(),
        ]);
    }

    /**
     * Promeut un utilisateur en administrateur.
     *
     * La route est volontairement en POST, car elle modifie les donnees. Le token
     * CSRF protege l'action contre une soumission fabriquee par un autre site.
     */
    #[Route('/{id}/promote', name: 'admin_user_promote', methods: ['POST'])]
    public function promote(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('promote_user_' . $user->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide: la promotion a été refusée.');

            return $this->redirectToRoute('admin_user_index');
        }

        if ($user->hasRole('ROLE_ADMIN')) {
            $this->addFlash('success', 'Cet utilisateur est déjà administrateur.');

            return $this->redirectToRoute('admin_user_index');
        }

        $user->promoteToAdmin();
        $entityManager->flush();

        $this->addFlash('success', sprintf('%s est maintenant administrateur.', $user->getEmail()));

        return $this->redirectToRoute('admin_user_index');
    }
}
