<?php

namespace App\Entity;

use App\Repository\CommentReactionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Reaction d'un utilisateur a un commentaire.
 *
 * Cette entite existe pour resoudre un probleme metier important: un utilisateur
 * ne doit pas pouvoir liker cent fois le meme commentaire. La contrainte unique
 * comment_id + user_id garantit une seule reaction par couple commentaire/auteur.
 */
#[ORM\Entity(repositoryClass: CommentReactionRepository::class)]
#[ORM\Table(name: 'comment_reaction')]
#[ORM\UniqueConstraint(name: 'UNIQ_COMMENT_REACTION_USER_COMMENT', fields: ['comment', 'user'])]
class CommentReaction
{
    public const LIKE = 1;
    public const DISLIKE = -1;

    /** Identifiant technique de la reaction. */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Commentaire concerne par le like ou dislike. */
    #[ORM\ManyToOne(targetEntity: Comment::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Comment $comment = null;

    /** Utilisateur connecte qui a reagi. */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /**
     * Valeur de la reaction.
     *
     * 1 signifie like, -1 signifie dislike. Ce choix simplifie les comparaisons
     * dans le controleur et evite deux colonnes booleennes contradictoires.
     */
    #[ORM\Column]
    private int $value = self::LIKE;

    /** Date de creation de la reaction pour audit et exploration pedagogique. */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getComment(): ?Comment { return $this->comment; }
    public function setComment(Comment $comment): self { $this->comment = $comment; return $this; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getValue(): int { return $this->value; }
    public function setValue(int $value): self { $this->value = $value === self::DISLIKE ? self::DISLIKE : self::LIKE; return $this; }
    public function isLike(): bool { return $this->value === self::LIKE; }
    public function isDislike(): bool { return $this->value === self::DISLIKE; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
