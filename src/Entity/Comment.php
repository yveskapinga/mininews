<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entite Comment: message ecrit par un utilisateur sous un article.
 *
 * Cette classe montre une relation typique entre trois tables: un commentaire
 * appartient a un article et a un auteur. Doctrine s'occupe de transformer ces
 * proprietes PHP en colonnes article_id et author_id dans PostgreSQL.
 */
#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comments')]
class Comment
{
    /** Cle primaire technique, generee par la base au moment de l'insertion. */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Article commente.
     *
     * nullable: false rend la relation obligatoire: un commentaire sans article
     * n'a pas de sens dans ce domaine. onDelete CASCADE protege aussi la base si
     * un article est supprime directement cote SQL.
     */
    #[ORM\ManyToOne(targetEntity: Article::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Article $article = null;

    /** Auteur connecte qui a envoye le commentaire. */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    /**
     * Corps du commentaire.
     *
     * Les contraintes de validation sont appliquees quand CommentType est soumis:
     * le texte doit exister, contenir au moins 3 caracteres et rester raisonnable.
     */
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le commentaire ne peut pas être vide.')]
    #[Assert\Length(min: 3, max: 1000, minMessage: 'Le commentaire est trop court.', maxMessage: 'Le commentaire est trop long.')]
    private ?string $content = null;

    /**
     * Nombre de likes du commentaire.
     *
     * On denormalise ce compteur pour eviter de recompter toutes les reactions a
     * chaque affichage. La table CommentReaction garde l'historique utilisateur;
     * ces colonnes servent a afficher rapidement les totaux.
     */
    #[ORM\Column]
    private int $likeCount = 0;

    /** Nombre de dislikes du commentaire, gere de la meme maniere que likeCount. */
    #[ORM\Column]
    private int $dislikeCount = 0;

    /** Date de creation affichee dans la liste des commentaires. */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** Initialise la date des que l'objet PHP est cree. */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /** Identifiant du commentaire, null avant flush(). */
    public function getId(): ?int { return $this->id; }

    /** Article parent du commentaire. */
    public function getArticle(): ?Article { return $this->article; }

    /** Rattache le commentaire a l'article consulte. */
    public function setArticle(Article $article): self { $this->article = $article; return $this; }

    /** Auteur du commentaire. */
    public function getAuthor(): ?User { return $this->author; }

    /** Rattache le commentaire a l'utilisateur connecte. */
    public function setAuthor(User $author): self { $this->author = $author; return $this; }

    /** Texte saisi par l'utilisateur. */
    public function getContent(): ?string { return $this->content; }

    /** Nettoie legerement le texte avant stockage. */
    public function setContent(string $content): self { $this->content = trim($content); return $this; }

    /** Retourne le nombre de likes affiche sous le commentaire. */
    public function getLikeCount(): int { return $this->likeCount; }

    /** Retourne le nombre de dislikes affiche sous le commentaire. */
    public function getDislikeCount(): int { return $this->dislikeCount; }

    /** Ajoute un like au compteur denormalise. */
    public function incrementLikeCount(): self { ++$this->likeCount; return $this; }

    /** Ajoute un dislike au compteur denormalise. */
    public function incrementDislikeCount(): self { ++$this->dislikeCount; return $this; }

    /** Retire un like sans jamais descendre sous zero. */
    public function decrementLikeCount(): self { $this->likeCount = max(0, $this->likeCount - 1); return $this; }

    /** Retire un dislike sans jamais descendre sous zero. */
    public function decrementDislikeCount(): self { $this->dislikeCount = max(0, $this->dislikeCount - 1); return $this; }

    /** Date de publication du commentaire. */
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
