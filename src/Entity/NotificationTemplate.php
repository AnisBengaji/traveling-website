<?php

namespace App\Entity;

use App\Repository\NotificationTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationTemplateRepository::class)]
class NotificationTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $emailContent = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $smsContent = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getEmailContent(): ?string
    {
        return $this->emailContent;
    }

    public function setEmailContent(?string $emailContent): static
    {
        $this->emailContent = $emailContent;
        return $this;
    }

    public function getSmsContent(): ?string
    {
        return $this->smsContent;
    }

    public function setSmsContent(?string $smsContent): static
    {
        $this->smsContent = $smsContent;
        return $this;
    }
} 