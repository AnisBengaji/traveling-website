<?php

namespace App\Entity;

use App\Repository\WalletRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;  // Assure-toi d'importer l'entité User

#[ORM\Entity(repositoryClass: WalletRepository::class)]
class Wallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]  // Relation ManyToOne avec l'entité User
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id", nullable: false)] // Clé étrangère user_id
    private ?User $user = null;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $score = 0;  // Initialisation du score à 0 par défaut

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(?int $score): static
    {
        $this->score = $score;

        return $this;
    }

    // Ajouter des méthodes pour manipuler le score
    public function addScore(int $amount): static
    {
        $this->score += $amount;  // Ajouter des points
        return $this;
    }

    public function deductScore(int $amount): static
    {
        if ($amount <= $this->score) {
            $this->score -= $amount;  // Déduire des points
        } else {
            throw new \Exception("Insufficient points in wallet");
        }
        return $this;
    }

    // Getter et Setter pour la relation ManyToOne avec User
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }
}