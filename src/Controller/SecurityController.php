<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Controleur de connexion/deconnexion.
 *
 * Le vrai travail d'authentification est realise par Symfony Security selon la
 * configuration de config/packages/security.yaml. Ce controleur fournit surtout
 * la page Twig du formulaire et les informations d'erreur.
 */
class SecurityController extends AbstractController
{
    /**
     * Affiche le formulaire de connexion.
     *
     * AuthenticationUtils expose le dernier email tente et l'erreur produite par
     * le firewall si une connexion precedente a echoue.
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * Route de deconnexion.
     *
     * Cette methode n'est normalement jamais executee: le firewall intercepte la
     * route app_logout avant le controleur et detruit la session utilisateur.
     */
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Cette méthode est interceptée par Symfony Security.');
    }
}
