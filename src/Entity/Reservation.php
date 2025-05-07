<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: "Le statut est requis.")]
    #[Assert\Choice(
        choices: ['en_attente', 'confirmee', 'annulee'],
        message: "Le statut doit être 'en attente', 'confirmée' ou 'annulée'."
    )]
    private ?string $status = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank(message: "Le prix total est requis.")]
    #[Assert\PositiveOrZero(message: "Le prix total doit être positif ou zéro.")]
    private ?float $priceTotal = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: "Le mode de paiement est requis.")]
    #[Assert\Choice(
        choices: ['carte bancaire', 'paypal', 'virement'],
        message: "Mode de paiement invalide."
    )]
    private ?string $modePaiement = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservations')]
#[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evenement $evenement = null;

    // Getters/Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getPriceTotal(): ?float
    {
        return $this->priceTotal;
    }

    public function setPriceTotal(float $priceTotal): self
    {
        $this->priceTotal = $priceTotal;
        return $this;
    }

    public function getModePaiement(): ?string
    {
        return $this->modePaiement;
    }

    public function setModePaiement(string $modePaiement): self
    {
        $this->modePaiement = $modePaiement;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getEvenement(): ?Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(?Evenement $evenement): self
    {
        $this->evenement = $evenement;
        return $this;
    }
}