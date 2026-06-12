<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entite Article: modele metier d'une actualite.
 *
 * Dans Symfony, une entite est une classe PHP ordinaire qui decrit un objet du
 * domaine. Doctrine lit les attributs #[ORM\...] pour savoir comment traduire
 * cette classe en table SQL, colonnes, relations et index.
 *
 * Ici, un article appartient a un auteur, peut etre publie ou rester brouillon,
 * et possede une collection de commentaires. Le controleur public n'affiche que
 * les articles publies, tandis que l'administration voit aussi les brouillons.
 */
#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug est déjà utilisé par un autre article.')]
class Article
{
    /**
     * Identifiant technique de l'article.
     *
     * #[ORM\Id] indique la cle primaire. #[ORM\GeneratedValue] demande a la base
     * de donnees de produire automatiquement la valeur au moment de l'INSERT.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Auteur de l'article.
     *
     * ManyToOne signifie: plusieurs articles peuvent etre rattaches a un meme
     * utilisateur. En base, Doctrine cree une colonne author_id et une cle
     * etrangere vers la table users.
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    /**
     * Titre affiche dans les listes, la page de detail et l'administration.
     *
     * Les contraintes #[Assert\...] sont verifiees par le composant Validator,
     * notamment quand un formulaire Symfony est soumis.
     */
    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 180, maxMessage: 'Le titre est trop long.')]
    private ?string $title = null;

    /**
     * Version lisible du titre pour l'URL publique.
     *
     * Exemple: "Mon premier article" devient "mon-premier-article". Le slug est
     * unique afin qu'une URL pointe toujours vers un seul article.
     */
    #[ORM\Column(length: 200, unique: true)]
    private ?string $slug = null;

    /**
     * Resume court affiche sur la page d'accueil.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le résumé est obligatoire.')]
    #[Assert\Length(max: 255, maxMessage: 'Le résumé est trop long.')]
    private ?string $excerpt = null;

    /**
     * Contenu complet de l'article.
     *
     * Types::TEXT demande une colonne texte longue cote SQL, adaptee aux textes
     * plus grands qu'un simple VARCHAR.
     */
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu est obligatoire.')]
    private ?string $content = null;

    /**
     * Drapeau de publication.
     *
     * false = brouillon; true = visible publiquement. Le repository public filtre
     * sur cette valeur dans findPublished().
     */
    #[ORM\Column]
    private bool $isPublished = false;

    /**
     * Date de creation de l'article.
     *
     * DateTimeImmutable evite les modifications accidentelles de la date apres sa
     * creation: si on veut changer la date, on cree un nouvel objet date.
     */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de derniere modification.
     *
     * Elle est rafraichie automatiquement par refreshUpdatedAt() avant chaque
     * UPDATE SQL grace au callback Doctrine #[ORM\PreUpdate].
     */
    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /**
     * Date de publication.
     *
     * null signifie que l'article n'a jamais ete publie ou qu'il a ete repasse en
     * brouillon. Cette date est geree dans setIsPublished().
     */
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    /**
     * Commentaires rattaches a l'article.
     *
     * OneToMany est le cote inverse de Comment::$article. Cette collection n'est
     * pas une simple liste PHP: Doctrine la charge et la synchronise avec la base.
     * cascade remove + orphanRemoval garantissent que les commentaires suivent la
     * suppression de leur article.
     *
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(mappedBy: 'article', targetEntity: Comment::class, cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $comments;

    /**
     * Constructeur appele quand on fait new Article().
     *
     * On y prepare les valeurs qui doivent toujours exister avant meme que
     * l'entite soit enregistree: dates et collection Doctrine.
     */
    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->comments = new ArrayCollection();
    }

    /**
     * Callback Doctrine execute juste avant un UPDATE.
     *
     * Le controleur n'a donc pas besoin de penser a mettre updatedAt a jour:
     * Doctrine le fait au bon moment pendant flush().
     */
    #[ORM\PreUpdate]
    public function refreshUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /** Retourne l'identifiant, null tant que l'article n'a pas encore ete persiste. */
    public function getId(): ?int { return $this->id; }

    /** Retourne l'utilisateur auteur de l'article. */
    public function getAuthor(): ?User { return $this->author; }

    /** Associe l'article a un auteur; indispensable avant l'enregistrement. */
    public function setAuthor(User $author): self { $this->author = $author; return $this; }

    /** Retourne le titre courant. */
    public function getTitle(): ?string { return $this->title; }

    /** Stocke un titre nettoye des espaces au debut et a la fin. */
    public function setTitle(string $title): self { $this->title = trim($title); return $this; }

    /** Retourne le slug utilise dans /article/{slug}. */
    public function getSlug(): ?string { return $this->slug; }

    /** Definit le slug genere par le controleur d'administration. */
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }

    /** Retourne le resume court de l'article. */
    public function getExcerpt(): ?string { return $this->excerpt; }

    /** Stocke le resume apres nettoyage leger. */
    public function setExcerpt(string $excerpt): self { $this->excerpt = trim($excerpt); return $this; }

    /** Retourne le contenu complet. */
    public function getContent(): ?string { return $this->content; }

    /** Stocke le contenu complet apres nettoyage leger. */
    public function setContent(string $content): self { $this->content = trim($content); return $this; }

    /** Convention Symfony: un getter booleen peut s'appeler isPublished(). */
    public function isPublished(): bool { return $this->isPublished; }

    /**
     * Change l'etat de publication et synchronise la date publishedAt.
     *
     * Publier pour la premiere fois pose la date. Repasser en brouillon remet la
     * date a null afin que l'etat de l'entite reste coherent.
     */
    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;
        $this->publishedAt = $isPublished ? ($this->publishedAt ?? new \DateTimeImmutable()) : null;

        return $this;
    }

    /** Date de creation affichee dans l'administration et les pages publiques. */
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** Date de derniere modification, maintenue par Doctrine. */
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    /** Date de publication, ou null si l'article est brouillon. */
    public function getPublishedAt(): ?\DateTimeImmutable { return $this->publishedAt; }

    /**
     * Retourne les commentaires de l'article, du plus recent au plus ancien.
     *
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }
}
