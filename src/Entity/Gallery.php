<?php

namespace App\Entity;

use App\Repository\GalleryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GalleryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Gallery
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est requis.")]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    // --- MODIFICATION IMPORTANTE ICI ---
    #[ORM\Column(length: 255, nullable: true)] // Rendre la colonne nullable en BDD
    // #[Assert\NotBlank(message: "Le nom du fichier image est requis.")] // SUPPRIMER ou COMMENTER CETTE LIGNE
    private ?string $imageFilename = null; // La propriété est déjà nullable en PHP
    // --- FIN DE LA MODIFICATION ---

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $ordreAffichage = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    // Modifier le setter pour accepter null, car la propriété est nullable
    public function setImageFilename(?string $imageFilename): static // <--- Accepter ?string (nullable)
    {
        $this->imageFilename = $imageFilename;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getOrdreAffichage(): ?int
    {
        return $this->ordreAffichage;
    }

    public function setOrdreAffichage(?int $ordreAffichage): static
    {
        $this->ordreAffichage = $ordreAffichage;
        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        if ($this->isActive === null) {
            $this->isActive = true;
        }
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}