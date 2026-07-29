<?php

namespace App\Twig;

use App\Service\ImageFallbackService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(private readonly ImageFallbackService $imageFallbackService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('fallback_image_url', [$this->imageFallbackService, 'getFallbackImageUrl']),
        ];
    }
}
