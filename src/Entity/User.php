<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entite User: compte capable de se connecter a l'application.
 *
 * Symfony Security ne travaille pas directement avec une table SQL: il attend un
 * objet qui implemente UserInterface. Cette entite fait donc le pont entre la
 * base de donnees Doctrine et le systeme d'authentification Symfony.
 *
 * ROLE_USER donne acces aux commentaires. ROLE_ADMIN donne acces a la gestion
 * des articles, et herite de ROLE_USER dans config/packages/security.yaml.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'UNIQ_USERS_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cette adresse email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /** Cle primaire technique generee par PostgreSQL. */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Email de connexion.
     *
     * security.yaml configure le provider pour retrouver les utilisateurs par
     * cette propriete. La contrainte UniqueEntity evite deux comptes identiques.
     */
    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L’email est obligatoire.')]
    #[Assert\Email(message: 'L’email saisi n’est pas valide.')]
    private ?string $email = null;

    /**
     * Roles Symfony stockes en JSON.
     *
     * Exemple: ["ROLE_USER"] pour un lecteur, ["ROLE_ADMIN"] pour un admin.
     * getRoles() ajoute toujours ROLE_USER afin qu'un compte garde un droit de
     * base meme si le tableau stocke en base est vide.
     *
     * @var list<string>
     */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    /** Hash du mot de passe; le mot de passe en clair ne doit jamais etre stocke. */
    #[ORM\Column]
    private ?string $password = null;

    /** Nom public affiche dans la navigation et sous les commentaires. */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom affiché est obligatoire.')]
    #[Assert\Length(max: 100, maxMessage: 'Le nom affiché est trop long.')]
    private ?string $displayName = null;

    /** Date de creation du compte. */
    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /**
     * Articles ecrits par cet utilisateur lorsqu'il agit comme administrateur.
     *
     * @var Collection<int, Article>
     */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Article::class)]
    private Collection $articles;

    /**
     * Commentaires ecrits par cet utilisateur.
     *
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Comment::class)]
    private Collection $comments;

    /** Prepare les collections Doctrine et la date de creation du compte. */
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->articles = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    /** Retourne l'identifiant interne, null avant l'enregistrement en base. */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** Retourne l'email de connexion. */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /** Normalise l'email: minuscules et suppression des espaces inutiles. */
    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    /**
     * Identifiant officiel utilise par Symfony Security dans la session.
     *
     * Dans cette application, on choisit l'email comme identifiant utilisateur.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * Retourne les roles accordes a l'utilisateur.
     *
     * array_unique evite les doublons si ROLE_USER est deja present en base.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * Remplace les roles stockes en base.
     *
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    /**
     * Indique si l'utilisateur possede deja un role donne.
     *
     * Cette petite methode rend les templates et les controleurs plus lisibles:
     * au lieu de manipuler directement le tableau JSON des roles, on demande a
     * l'objet User s'il possede ROLE_ADMIN, ROLE_USER, etc.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * Promeut l'utilisateur en administrateur.
     *
     * Dans Symfony, un role n'est qu'une chaine de caracteres. Donner ROLE_ADMIN
     * a un utilisateur suffit pour lui ouvrir les routes protegees par
     * #[IsGranted('ROLE_ADMIN')] et access_control.
     */
    public function promoteToAdmin(): self
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_ADMIN';

        return $this->setRoles($roles);
    }

    /** Retourne le hash du mot de passe attendu par Symfony Security. */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /** Stocke le mot de passe deja hache par UserPasswordHasherInterface. */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Methode imposee par UserInterface.
     *
     * Elle sert a effacer des secrets temporaires. Ici aucun plainPassword n'est
     * stocke dans l'entite, donc il n'y a rien a nettoyer.
     */
    public function eraseCredentials(): void
    {
        // Exemple: si on stockait temporairement un plainPassword, on l'effacerait ici.
    }

    /** Retourne le nom affiche a l'ecran. */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /** Nettoie et stocke le nom affiche. */
    public function setDisplayName(string $displayName): self
    {
        $this->displayName = trim($displayName);

        return $this;
    }

    /** Date de creation du compte. */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
