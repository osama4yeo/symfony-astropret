<?php

namespace App\Entity;

use App\Repository\MaterielRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types; // <--- VOICI L'AJOUT IMPORTANT

#[ORM\Entity(repositoryClass: MaterielRepository::class)]
class Materiel
{
    public const ETAT_LIBRE = 'libre';
    public const ETAT_LOUE = 'loue';
    // public const ETAT_MAINTENANCE = 'maintenance'; // Si vous l'ajoutez plus tard

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 20)] // Pourrait être plus spécifique si vous avez plus d'états, ex: 50
    private ?string $etat = self::ETAT_LIBRE; // Mettre une valeur par défaut est une bonne pratique

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)] // Maintenant Types::STRING sera reconnu
    private ?string $imageFilename = null;

    /**
     * @var Collection<int, Reservation>
     */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'materiel', orphanRemoval: true)] // orphanRemoval=true est souvent utile
    private Collection $reservations; // Nom de propriété corrigé de 'reservation' à 'reservations' (pluriel)

    public function __construct()
    {
        $this->reservations = new ArrayCollection(); // Correspond au nom de propriété corrigé
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        // Optionnel : Valider que l'état est l'un des états permis
        // if (!in_array($etat, [self::ETAT_LIBRE, self::ETAT_LOUE /*, self::ETAT_MAINTENANCE */])) {
        //     throw new \InvalidArgumentException("Invalid materiel state provided: " . $etat);
        // }
        $this->etat = $etat;
        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection // Nom de méthode corrigé
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) { // Utilise la propriété corrigée
            $this->reservations->add($reservation);
            $reservation->setMateriel($this);
        }
        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) { // Utilise la propriété corrigée
            // set the owning side to null (unless already changed)
            if ($reservation->getMateriel() === $this) {
                $reservation->setMateriel(null);
            }
        }
        return $this;
    }
}