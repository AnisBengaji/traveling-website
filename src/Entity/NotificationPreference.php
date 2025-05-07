<?php

namespace App\Entity;

use App\Repository\NotificationPreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationPreferenceRepository::class)]
class NotificationPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'notificationPreference')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?bool $emailEnabled = true;

    #[ORM\Column]
    private ?bool $smsEnabled = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function isEmailEnabled(): ?bool
    {
        return $this->emailEnabled;
    }

    public function setEmailEnabled(bool $emailEnabled): static
    {
        $this->emailEnabled = $emailEnabled;

        return $this;
    }

    public function isSmsEnabled(): ?bool
    {
        return $this->smsEnabled;
    }

    public function setSmsEnabled(bool $smsEnabled): static
    {
        $this->smsEnabled = $smsEnabled;

        return $this;
    }
} 