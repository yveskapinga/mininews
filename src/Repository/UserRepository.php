<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository Doctrine des utilisateurs.
 *
 * Symfony Security l'utilise indirectement pour retrouver un compte par email,
 * car le provider entity est configure dans security.yaml. L'interface
 * PasswordUpgraderInterface permet aussi de moderniser un hash de mot de passe
 * lorsque l'algorithme configure evolue.
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /** Associe ce repository a l'entite User. */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


    /**
     * Retourne les utilisateurs pour l'ecran d'administration.
     *
     * On trie par date de creation recente afin que les nouveaux comptes soient
     * faciles a retrouver quand un administrateur veut les promouvoir.
     *
     * @return list<User>
     */
    public function findForAdmin(): array
    {
        return $this->createQueryBuilder('user')
            ->orderBy('user.createdAt', 'DESC')
            ->addOrderBy('user.email', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Met a jour le hash du mot de passe d'un utilisateur.
     *
     * Symfony peut appeler cette methode apres une connexion reussie si l'ancien
     * hash est juge trop faible ou produit par un ancien algorithme.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances de %s non supportées.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }
}
