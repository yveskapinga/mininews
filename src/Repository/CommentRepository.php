<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des commentaires.
 *
 * Il ne contient pas encore de methode metier specifique, car les commentaires
 * d'un article sont recuperes via la relation Article::$comments. Il existe tout
 * de meme pour accueillir de futures requetes, par exemple "derniers commentaires".
 *
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    /** Associe ce repository a l'entite Comment. */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }
}
