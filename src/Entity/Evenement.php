<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'événement ne peut pas être vide.")]
    private $nom;

    #[ORM\Column(type: "date")]
    #[Assert\NotBlank(message: "La date de départ de l'événement est requise.")]
    #[Assert\Type("\DateTimeInterface", message: "La date doit être un objet DateTime valide.")]
    #[Assert\GreaterThan("today", message: "La date de départ doit être dans le futur.")]
    private $Date_EvenementDepart;
    
    #[ORM\Column(type: "date")]
    #[Assert\NotBlank(message: "La date d'arrivée de l'événement est requise.")]
    #[Assert\Type("\DateTimeInterface", message: "La date doit être un objet DateTime valide.")]
    #[Assert\GreaterThan(propertyPath: "Date_EvenementDepart", message: "La date d'arrivée doit être après la date de départ.")]
    private $Date_EvenementArriver;
    
    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Le lieu ne doit pas être vide.")]
#[Assert\Length(
    max: 255,
    maxMessage: "Le lieu ne doit pas dépasser {{ limit }} caractères."
)]
    private $lieu;

    #[ORM\Column(type: "text", nullable: true)]
    #[Assert\NotBlank(message: "Le description ne doit pas être vide.")]

    #[Assert\Length(
        max: 1000,
        maxMessage: "La description ne doit pas dépasser {{ limit }} caractères."
    )]
    private $Description;

    #[ORM\Column(type: "float")]
    #[Assert\NotBlank(message: "Le prix de l'événement ne peut pas être vide.")]
    #[Assert\GreaterThanOrEqual(value: 0, message: "Le prix ne peut pas être négatif.")]
    private $price;

    #[ORM\OneToMany(targetEntity: "App\Entity\Reservation", mappedBy: "evenement", cascade: ["persist", "remove"])]
    private $reservations;

    #[ORM\Column(type: "float")]
    #[Assert\NotBlank(message: "La latitude de l'événement est requise.")]
    #[Assert\Range(
        min: -90,
        max: 90,
        notInRangeMessage: "La latitude doit être comprise entre -90 et 90 degrés."
    )]
    private $latitude;

    #[ORM\Column(type: "float")]
    #[Assert\NotBlank(message: "La longitude de l'événement est requise.")]
    #[Assert\Range(
        min: -180,
        max: 180,
        notInRangeMessage: "La longitude doit être comprise entre -180 et 180 degrés."
    )]
    private $longitude;
    #[ORM\Column(type: "string", length: 255, nullable: true)]
private $imageUrl;

public function getImageUrl(): ?string
{
    return $this->imageUrl;
}

public function setImageUrl(?string $imageUrl): self
{
    $this->imageUrl = $imageUrl;
    return $this;
}


    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDateEvenementDepart(): ?\DateTimeInterface
    {
        return $this->Date_EvenementDepart;
    }

    public function setDateEvenementDepart(\DateTimeInterface $Date_EvenementDepart): self
    {
        $this->Date_EvenementDepart = $Date_EvenementDepart;
        return $this;
    }

    public function getDateEvenementArriver(): ?\DateTimeInterface
    {
        return $this->Date_EvenementArriver;
    }

    public function setDateEvenementArriver(\DateTimeInterface $Date_EvenementArriver): self
    {
        $this->Date_EvenementArriver = $Date_EvenementArriver;
        return $this;
    }

    public function getLieu(): ?string
    {
        return $this->lieu;
    }

    public function setLieu(string $lieu): self
    {
        $this->lieu = $lieu;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->Description;
    }

    public function setDescription(?string $Description): self
    {
        $this->Description = $Description;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return Collection|Reservation[]
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations[] = $reservation;
            $reservation->setEvenement($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getEvenement() === $this) {
                $reservation->setEvenement(null);
            }
        }

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }
}