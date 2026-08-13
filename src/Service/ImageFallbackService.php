<?php

namespace App\Service;

class ImageFallbackService
{
    public function __construct(private readonly ImageService $imageService)
    {
    }

    public function getFallbackImageUrl(): string
    {
        return $this->imageService->getFallbackUrl();
    }
}
