<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controleur d'inscription publique.
 *
 * Il montre un flux classique Symfony: creer une entite, creer un formulaire,
 * traiter la requete, valider, completer les champs sensibles puis enregistrer.
 */
class RegistrationController extends AbstractController
{
    /**
     * Cree un compte lecteur ROLE_USER.
     *
     * Les administrateurs sont crees via la commande console afin d'eviter qu'un
     * visiteur puisse s'attribuer ROLE_ADMIN depuis un formulaire public.
     */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // plainPassword n'est pas mappe sur l'entite. On le recupere, on le
            // hache, puis on stocke uniquement le hash dans User::$password.
            $plainPassword = (string) $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte créé. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
