<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\CommentReaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des reactions de commentaires.
 *
 * Il centralise les requetes qui retrouvent les reactions d'un utilisateur.
 * Cela evite de dupliquer ces findOneBy dans plusieurs classes.
 *
 * @extends ServiceEntityRepository<CommentReaction>
 */
class CommentReactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentReaction::class);
    }

    /** Retourne la reaction d'un utilisateur sur un commentaire, ou null. */
    public function findUserReaction(Comment $comment, User $user): ?CommentReaction
    {
        return $this->findOneBy([
            'comment' => $comment,
            'user' => $user,
        ]);
    }

    /**
     * Retourne toutes les reactions d'un utilisateur pour une liste de commentaires.
     *
     * Le resultat est indexe par l'identifiant du commentaire. Cela permet au
     * template de savoir instantanement si l'utilisateur courant a deja like ou
     * dislike chaque commentaire, sans requete supplementaire dans la boucle.
     *
     * @param User $user Utilisateur connecte.
     * @param array<int, Comment> $comments Liste de commentaires affiches.
     * @return array<int, int> Tableau [commentId => valeurReaction].
     */
    public function findUserReactionsForComments(User $user, array $comments): array
    {
        if ($comments === []) {
            return [];
        }

        $commentIds = array_map(static fn (Comment $comment): int => (int) $comment->getId(), $comments);

        $reactions = $this->createQueryBuilder('r')
            ->select('r.value', 'IDENTITY(r.comment) as commentId')
            ->where('r.user = :user')
            ->andWhere('r.comment IN (:comments)')
            ->setParameter('user', $user)
            ->setParameter('comments', $commentIds)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($reactions as $reaction) {
            $indexed[(int) $reaction['commentId']] = (int) $reaction['value'];
        }

        return $indexed;
    }
}
