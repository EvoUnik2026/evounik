<?php

namespace App\Twig;

use App\Service\ImageService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Twig helpers for rendering images served by ImageController.
 *
 *   {{ image_src(topic.image, 400, 300) }}            -> URL string
 *   {{ image_tag(topic.image, 400, 300, {alt: '…'}) }} -> <img> with default fallback
 *   {{ image_srcset(topic.image, [300, 600, 1200]) }} -> "url 300w, url 600w, ..."
 */
class ImageExtension extends AbstractExtension
{
    public function __construct(
        private readonly ImageService $imageService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('image_src', [$this, 'src']),
            new TwigFunction('image_tag', [$this, 'tag'], ['is_safe' => ['html']]),
            new TwigFunction('image_srcset', [$this, 'srcset']),
        ];
    }

    /**
     * Build the public URL for an image variant.
     *
     * External URLs are returned untouched. Internal paths are routed through
     * ImageController (on‑the‑fly resize + disk cache).
     */
    public function src(string $path, ?int $width = null, ?int $height = null, bool $thumbnail = false): string
    {
        if ($this->imageService->isExternalUrl($path)) {
            return $path;
        }

        if (!$this->imageService->exists($path)) {
            return $this->imageService->getFallbackUrl();
        }

        $params = ['path' => ltrim($path, '/')];

        if (null !== $width && $width > 0) {
            $params['w'] = $width;
        }

        if (null !== $height && $height > 0) {
            $params['h'] = $height;
        }

        if ($thumbnail) {
            $params['crop'] = 1;
        }

        return $this->urlGenerator->generate('app_image_serve', $params);
    }

    /**
     * Render a complete <img> tag.
     *
     * Uses the service's default width/height when none are given and wires up
     * an onerror handler that falls back to the default image.
     *
     * @param array<string,string|int|bool> $attributes Extra <img> attributes (alt, class, …)
     */
    public function tag(string $path, ?int $width = null, ?int $height = null, array $attributes = []): string
    {
        $width = $width ?? $this->imageService->getDefaultWidth();
        $height = $height ?? $this->imageService->getDefaultHeight();

        $src = $this->src($path, $width, $height, $attributes['thumbnail'] ?? false);
        unset($attributes['thumbnail']);

        // Wire up an onerror fallback for ALL images (internal and external).
        // External URLs are returned directly by src() — the onerror ensures
        // that if a remote image fails to load the local fallback is shown.
        $onerror = sprintf(
            "this.onerror=null;this.src='%s';",
            $this->imageService->getFallbackUrl()
        );

        $html = sprintf('<img src="%s" width="%d" height="%d"', $src, $width, $height);

        foreach ($attributes as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html .= ' '.htmlspecialchars($key, ENT_QUOTES);
                }
                continue;
            }

            $html .= sprintf(' %s="%s"', htmlspecialchars((string) $key, ENT_QUOTES), htmlspecialchars((string) $value, ENT_QUOTES));
        }

        if ('' !== $onerror) {
            $html .= sprintf(' onerror="%s"', $onerror);
        }

        $html .= '>';

        return $html;
    }

    /**
     * Build a srcset string: "url1 300w, url2 600w, url3 1200w".
     *
     * @param int[] $widths
     */
    public function srcset(string $path, array $widths): string
    {
        $parts = [];

        foreach ($widths as $width) {
            $parts[] = sprintf('%s %dw', $this->src($path, (int) $width, null), (int) $width);
        }

        return implode(', ', $parts);
    }
}
