<?php

namespace App\Service;

use App\Entity\NotificationTemplate;
use App\Repository\NotificationTemplateRepository;

class NotificationTemplateService
{
    private NotificationTemplateRepository $templateRepository;

    public function __construct(NotificationTemplateRepository $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    public function getTemplate(string $type): ?NotificationTemplate
    {
        return $this->templateRepository->findOneByType($type);
    }

    public function getEmailContent(string $type, array $context = []): ?string
    {
        $template = $this->getTemplate($type);
        if (!$template) {
            return null;
        }

        $content = $template->getEmailContent();
        foreach ($context as $key => $value) {
            $content = str_replace("{{ $key }}", $value, $content);
        }

        return $content;
    }

    public function getSmsContent(string $type, array $context = []): ?string
    {
        $template = $this->getTemplate($type);
        if (!$template) {
            return null;
        }

        $content = $template->getSmsContent();
        foreach ($context as $key => $value) {
            $content = str_replace("{{ $key }}", $value, $content);
        }

        return $content;
    }
} 