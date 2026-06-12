<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des articles.
 *
 * Un repository centralise les requetes de lecture d'une entite. Le controleur
 * lui demande une intention metier, par exemple "articles publies", au lieu de
 * construire la requete SQL lui-meme.
 *
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    /**
     * ManagerRegistry donne acces a la configuration Doctrine de l'application.
     * Le parent sait ensuite que ce repository travaille avec Article::class.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    /**
     * Retourne tous les articles visibles publiquement, du plus recent au plus ancien.
     *
     * QueryBuilder permet d'ecrire une requete orientee objet: article designe
     * l'alias de l'entite Article, pas directement le nom d'une table SQL.
     *
     * @return list<Article>
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('article')
            ->andWhere('article.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('article.publishedAt', 'DESC')
            ->addOrderBy('article.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne tous les articles pour l'administration, brouillons inclus.
     *
     * Cette requete est volontairement differente de findPublished(): l'admin doit
     * voir le contenu non publie pour le modifier ou le publier.
     *
     * @return list<Article>
     */
    public function findForAdmin(): array
    {
        return $this->createQueryBuilder('article')
            ->orderBy('article.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
