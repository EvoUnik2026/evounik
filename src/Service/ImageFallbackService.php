<?php

namespace App\Service;

use Symfony\Component\Asset\Packages;

class ImageFallbackService
{
    public function __construct(private readonly Packages $packages)
    {
    }

    public function getFallbackImageUrl(): string
    {
        return $this->packages->getUrl('images/evouniek.png');
    }
}
