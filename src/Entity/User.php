<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection; // <--- AJOUTÉ
use Doctrine\Common\Collections\Collection;    // <--- AJOUTÉ
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cet email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email ne peut pas être vide.")]
    #[Assert\Email(message: "L'email '{{ value }}' n'est pas une adresse email valide.")]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(min: 2, max: 100, minMessage: "Le prénom doit comporter au moins 2 caractères.", maxMessage: "Le prénom ne peut pas dépasser 100 caractères.")]
    private ?string $prenom = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(min: 2, max: 100, minMessage: "Le nom doit comporter au moins 2 caractères.", maxMessage: "Le nom ne peut pas dépasser 100 caractères.")]
    private ?string $nom = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateNaissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    // --- NOUVELLE PROPRIÉTÉ POUR LA RELATION INVERSE AVEC RESERVATION ---
    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reservation::class, orphanRemoval: false)] // orphanRemoval: false ou true selon ta logique
    private Collection $reservations;
    // --- FIN DE LA NOUVELLE PROPRIÉTÉ ---

    public function __construct()
    {
        // Initialise la collection de réservations
        $this->reservations = new ArrayCollection();
    }

    // ... (tous tes getters et setters existants pour id, email, password, roles, prenom, nom, telephone, dateNaissance, avatar) ...
    // ... (méthodes getUserIdentifier, eraseCredentials) ...

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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return (string) $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
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

    // --- GETTERS ET MÉTHODES ADD/REMOVE POUR LA PROPRIÉTÉ RESERVATIONS ---
    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setUser($this); // Met à jour le côté propriétaire de la relation
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // Important : Définit le côté propriétaire à null (sauf s'il a déjà été changé)
            // pour maintenir la cohérence de la relation bidirectionnelle.
            if ($reservation->getUser() === $this) {
                $reservation->setUser(null);
            }
        }
        return $this;
    }
    // --- FIN DES MÉTHODES POUR RESERVATIONS ---
}