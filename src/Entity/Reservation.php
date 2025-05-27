<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Materiel; // Assure-toi que ce use est présent
use App\Entity\User;    // <--- AJOUTÉ : Use statement pour l'entité User

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $latitude = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7, nullable: true)]
    private ?string $longitude = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)] // Rendre nullable si ce n'est pas toujours défini à la création
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)] // Rendre nullable si ce n'est pas toujours défini à la création
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\ManyToOne(targetEntity: Materiel::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)] // Une réservation doit avoir un matériel
    private ?Materiel $materiel = null;

    // --- NOUVELLE PROPRIÉTÉ ET RELATION AVEC USER ---
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservations')] // 'reservations' sera la propriété Collection dans User
    #[ORM\JoinColumn(nullable: true)] // Mettre à false si une réservation DOIT être liée à un utilisateur
    private ?User $user = null;
    // --- FIN DE LA NOUVELLE PROPRIÉTÉ ---

    // Optionnel: si tu as un champ texte pour un nom de locataire non enregistré
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomLocataire = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): static
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): static
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;
        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;
        return $this;
    }

    public function getMateriel(): ?Materiel
    {
        return $this->materiel;
    }

    public function setMateriel(?Materiel $materiel): static
    {
        $this->materiel = $materiel;
        return $this;
    }

    // --- GETTER ET SETTER POUR LA PROPRIÉTÉ USER ---
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
    // --- FIN GETTER ET SETTER USER ---

    public function getNomLocataire(): ?string
    {
        return $this->nomLocataire;
    }

    public function setNomLocataire(?string $nomLocataire): static
    {
        $this->nomLocataire = $nomLocataire;
        return $this;
    }
}