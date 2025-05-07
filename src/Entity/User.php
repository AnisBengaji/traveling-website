<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\Length(max: 100, maxMessage: "Le nom ne peut pas dépasser 100 caractères.")]
    private string $nom;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\Length(max: 100, maxMessage: "Le prénom ne peut pas dépasser 100 caractères.")]
    private ?string $prenom = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire.")]
    #[Assert\Positive(message: "Le numéro de téléphone doit être un entier positif.")]
    private int $num_tel;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\Email(message: "Veuillez entrer un email valide.")]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $mdp;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: "Le rôle est obligatoire.")]
    #[Assert\Choice(choices: ['admin', 'client', 'fournisseur'], message: "Le rôle doit être 'admin', 'client' ou 'fournisseur'.")]
    private string $role;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Wallet::class, cascade: ['persist', 'remove'])]
    private ?Wallet $wallet = null;
  

    // -------------------- Getters & Setters --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom ?? '';
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): self
    {
        $this->prenom = $prenom ?? '';
        return $this;
    }

    public function getNumTel(): int
    {
        return $this->num_tel;
    }

    public function setNumTel(?int $num_tel): self
    {
        $this->num_tel = $num_tel ?? 0;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email ?? '';
        return $this;
    }

    public function getMdp(): string
    {
        return $this->mdp;
    }

    public function setMdp(string $mdp): self
    {
        $this->mdp = $mdp;
        return $this;
    }
    public function getWallet(): ?Wallet
    {
        return $this->wallet;
    }
    
    public function setWallet(Wallet $wallet): self
    {
        $this->wallet = $wallet;
        return $this;
    }

    public function setPassword(string $password): self
    {
        // Utilise la même propriété que getPassword()
        $this->mdp = $password;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->mdp;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getRoles(): array
    {
        return match ($this->role) {
            'admin' => ['ROLE_ADMIN'],
            'client' => ['ROLE_CLIENT'],
            'fournisseur' => ['ROLE_FOURNISSEUR'],
            default => ['ROLE_USER'],
        };
    }

    public function eraseCredentials(): void
    {
        // Si tu stockes des infos sensibles, les effacer ici.
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }
}
