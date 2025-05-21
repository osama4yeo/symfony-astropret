<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface; // NÉCESSAIRE
use Symfony\Component\Security\Core\User\UserInterface; // NÉCESSAIRE
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cet email')]
// --- MODIFICATION 1: Implémenter les interfaces UserInterface et PasswordAuthenticatedUserInterface ---
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // --- MODIFICATION 2: Augmenter la longueur pour l'email (standard) et ajouter des contraintes de validation ---
    #[ORM\Column(length: 180, unique: true)] // La propriété unique:true est redondante avec #[UniqueEntity] mais ne fait pas de mal
    #[Assert\NotBlank(message: "L'email ne peut pas être vide.")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas une adresse email valide.")]
    private ?string $email = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column(length: 255)] // La longueur de 255 est bien pour les mots de passe hachés
    private ?string $password = null;

    // --- MODIFICATION 3: Spécifier le type JSON pour les rôles ---
    #[ORM\Column(type: 'json')] // Les rôles sont stockés comme un tableau JSON
    private array $roles = [];

    #[ORM\Column(length: 100, nullable: true)] // Rendre nullable si ce n'est pas obligatoire à l'inscription
    #[Assert\Length(min: 2, max: 100, minMessage: "Le prénom doit comporter au moins 2 caractères.", maxMessage: "Le prénom ne peut pas dépasser 100 caractères.")]
    private ?string $prenom = null; // Prénom

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(min: 2, max: 100, minMessage: "Le nom doit comporter au moins 2 caractères.", maxMessage: "Le nom ne peut pas dépasser 100 caractères.")]
    private ?string $nom = null;     // Nom de famille

    #[ORM\Column(length: 20, nullable: true)] // Exemple: Numéro de téléphone
    private ?string $telephone = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)] // Exemple: Date de naissance
    private ?\DateTimeImmutable $dateNaissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    // --- MODIFICATION 4: Implémenter getUserIdentifier() (requise par UserInterface) ---
    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email; // Ou $this->username si tu en avais un
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // Garantit que chaque utilisateur a au moins ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string // La méthode doit retourner string, pas ?string
    {
        // Le mot de passe ne doit JAMAIS être null pour un utilisateur qui en a un.
        // S'il est null en BDD, cela causerait une erreur ici, ce qui est bien.
        return (string) $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    // --- MODIFICATION 5: Implémenter eraseCredentials() (requise par UserInterface) ---
    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Si tu stockes des données temporaires sensibles sur l'utilisateur (ex: un mot de passe en clair temporaire),
        // efface-les ici. Pour la plupart des cas, cette méthode peut rester vide.
        // $this->plainPassword = null;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeImmutable
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeImmutable $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }
}