<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * ImageService
 *
 * A lightweight, GD-backed service for handling images: existence checks,
 * resizing, thumbnail creation, cropping, rotation, flipping, format
 * conversion, optimization, metadata extraction and on-the-fly display.
 *
 * All "write" operations produce a cached derivative on disk (under
 * image.cache_dir) and return its absolute filesystem path, so the same
 * transformation is never processed twice.
 */
class ImageService
{
    /** MIME type => PHP GD "imagecreatefrom*" factory method name. */
    private const MIME_FACTORIES = [
        'image/jpeg' => 'imagecreatefromjpeg',
        'image/png'  => 'imagecreatefrompng',
        'image/gif'  => 'imagecreatefromgif',
        'image/webp' => 'imagecreatefromwebp',
        'image/bmp'  => 'imagecreatefrombmp',
        'image/avif' => 'imagecreatefromavif',
    ];

    /**
     * MIME type of the image currently being processed.
     * Used by supportsAlpha() so it has no dependency on GD state.
     */
    private string $currentMime = '';

    private string $cacheDir;
    private string $publicDir;
    private string $projectDir;
    private int $defaultWidth;
    private int $defaultHeight;
    private int $quality;
    private string $fallbackPath;

    /** @var string[] */
    private array $allowedMimeTypes;

    /**
     * @param string[] $allowedMimeTypes
     */
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly ?HttpClientInterface $httpClient = null,
        ?int $defaultWidth = null,
        ?int $defaultHeight = null,
        ?int $quality = null,
        ?string $cacheDir = null,
        ?string $publicDir = null,
        ?string $fallbackPath = null,
        ?string $projectDir = null,
        ?array $allowedMimeTypes = null,
    ) {
        $this->defaultWidth = $defaultWidth ?? (int) $parameterBag->get('image.default_width');
        $this->defaultHeight = $defaultHeight ?? (int) $parameterBag->get('image.default_height');
        $this->quality = $quality ?? (int) $parameterBag->get('image.quality');
        $this->cacheDir = $cacheDir ?? $parameterBag->get('image.cache_dir');
        $this->publicDir = $publicDir ?? $parameterBag->get('image.public_dir');
        $this->projectDir = $projectDir ?? $parameterBag->get('kernel.project_dir');
        $this->fallbackPath = $fallbackPath ?? $parameterBag->get('image.fallback_path');
        $this->allowedMimeTypes = $allowedMimeTypes ?? $parameterBag->get('image.allowed_mime_types');
    }

    // ---------------------------------------------------------------------
    // Existence / resolution helpers
    // ---------------------------------------------------------------------

    /**
     * Does an image exist?
     *
     * - Relative/absolute web paths (images/foo.jpg, /images/foo.jpg) are
     *   resolved against the public directory.
     * - Full URLs (http(s)://...) are checked with a HEAD request.
     */
    public function exists(string $path): bool
    {
        if ($this->isExternalUrl($path)) {
            if (null === $this->httpClient) {
                return false;
            }

            $status = $this->httpClient->request('HEAD', $path)->getStatusCode();

            return $status >= 200 && $status < 400;
        }

        $resolved = $this->resolvePublicPath($path);

        return null !== $resolved && is_file($resolved);
    }

    /**
     * Resolve a "public" image path to an absolute filesystem path.
     * Returns null when the path is external or escapes the public dir.
     *
     * Resolution order (first match wins):
     *  1. Source assets directory: <project>/assets/<relative>
     *     — when the path has no folder prefix (e.g. "energy.jpg") it is
     *     resolved as assets/images/energy.jpg by convention.
     *  2. Public directory: <public>/<relative>
     *  3. Asset Mapper compiled assets: <public>/assets/<relative>
     */
    public function resolvePublicPath(string $path): ?string
    {
        if ($this->isExternalUrl($path)) {
            return null;
        }

        $relative = ltrim($path, '/');

        // Block path traversal: never allow ".." segments.
        if (str_contains($relative, '..')) {
            return null;
        }

        // When the path has no folder prefix, images live in assets/images/.
        if (!str_contains($relative, '/')) {
            $relative = 'images/'.$relative;
        }

        // 1. Source assets directory (project root): assets/<relative>
        $source = $this->projectDir.'/assets/'.$relative;
        if (is_file($source)) {
            return $source;
        }

        // 2. Public directory: public/<relative>
        $candidate = $this->publicDir.'/'.$relative;
        if (is_file($candidate)) {
            return $candidate;
        }

        // 3. Asset Mapper publishes compiled assets to public/assets/.
        $candidateAlt = $this->publicDir.'/assets/'.$relative;
        if (is_file($candidateAlt)) {
            return $candidateAlt;
        }

        return null;
    }

    public function isExternalUrl(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    // ---------------------------------------------------------------------
    // Fallback
    // ---------------------------------------------------------------------

    public function getFallbackUrl(): string
    {
        // Route the fallback through the image controller so it goes through
        // the same resolution + caching pipeline as any other image.
        // This guarantees the fallback is found in assets/images/ even when
        // the Packages/AssetMapper URL lookup would fail.
        return '/image/'.ltrim($this->fallbackPath, '/');
    }

    /**
     * Absolute filesystem path of the fallback image file.
     */
    public function getFallbackPath(): string
    {
        $direct = $this->publicDir.'/'.$this->fallbackPath;
        if (is_file($direct)) {
            return $direct;
        }

        $alt = $this->publicDir.'/assets/'.$this->fallbackPath;
        if (is_file($alt)) {
            return $alt;
        }

        // Asset Mapper source dir (compiled assets are served from public/assets/).
        $src = $this->projectDir.'/assets/'.$this->fallbackPath;
        if (is_file($src)) {
            return $src;
        }

        // Last resort: generate a placeholder on the fly.
        return $this->generatePlaceholder();
    }

    // ---------------------------------------------------------------------
    // Metadata
    // ---------------------------------------------------------------------

    /**
     * @return array{width:int, height:int, mime:string, size:int}
     *
     * @throws \InvalidArgumentException when the image cannot be found/read
     */
    public function getDimensions(string $path): array
    {
        $resolved = $this->resolvePublicPath($path) ?? $this->getFallbackPath();

        /** @var array{0:int,1:int,2:int,3:string}|false $info */
        $info = @getimagesize($resolved);

        if (false === $info) {
            throw new \InvalidArgumentException(sprintf('Unable to read image metadata for "%s".', $path));
        }

        return [
            'width'  => $info[0],
            'height' => $info[1],
            'mime'   => $info['mime'],
            'size'   => filesize($resolved) ?: 0,
        ];
    }

    public function getMime(string $path): ?string
    {
        $resolved = $this->resolvePublicPath($path);

        if (null === $resolved || !is_file($resolved)) {
            return null;
        }

        /** @var array{0:int,1:int,2:int,3:string}|false $info */
        $info = @getimagesize($resolved);

        return false === $info ? null : $info['mime'];
    }

    /**
     * Validate that a path points to a real image of an allowed type.
     *
     * @param string[]|null $allowedMimeTypes
     */
    public function validate(string $path, ?array $allowedMimeTypes = null): bool
    {
        $mime = $this->getMime($path);

        if (null === $mime) {
            return false;
        }

        $allowed = $allowedMimeTypes ?? $this->allowedMimeTypes;

                return in_array($mime, $allowed, true);
    }

    // ---------------------------------------------------------------------
    // Manipulation — each returns the absolute path of the cached derivative
    // ---------------------------------------------------------------------

    /**
     * Resize an image to fit within $width × $height (preserving aspect ratio
     * by default). Pass $preserveAspectRatio=false for an exact box.
     */
    public function resize(string $path, int $width, int $height, bool $preserveAspectRatio = true): string
    {
        $key = $preserveAspectRatio ? 'resize_p' : 'resize_e';
        $width = max(1, $width);
        $height = max(1, $height);

        return $this->createVariant($path, "{$key}_{$width}x{$height}", function (\GdImage $image) use ($width, $height, $preserveAspectRatio): \GdImage {
            [$w, $h] = $this->calculateContainedSize((int) imagesx($image), (int) imagesy($image), $width, $height, $preserveAspectRatio);

            return $this->doResize($image, $w, $h);
        });
    }

    /**
     * Create a thumbnail.
     *
     * mode = 'crop'  (default): cover the box, then center-crop to exact dimensions.
     * mode = 'fit'  : contain within the box (same as resize with aspect preserved).
     */
    public function thumbnail(string $path, int $width, int $height, string $mode = 'crop'): string
    {
        $width = max(1, $width);
        $height = max(1, $height);

        if ('fit' === $mode) {
            return $this->resize($path, $width, $height, true);
        }

        return $this->createVariant($path, "thumb_crop_{$width}x{$height}", function (\GdImage $image) use ($width, $height): \GdImage {
            return $this->doCrop($image, $width, $height);
        });
    }

    /**
     * Center-crop an image to the exact dimensions.
     *
     * @param string $gravity One of: center, top, bottom, left, right,
     *                        top_left, top_right, bottom_left, bottom_right
     */
    public function crop(string $path, int $width, int $height, string $gravity = 'center'): string
    {
        $width = max(1, $width);
        $height = max(1, $height);
        $key = "crop_{$width}x{$height}_{$gravity}";

        return $this->createVariant($path, $key, function (\GdImage $image) use ($width, $height, $gravity): \GdImage {
            return $this->doCrop($image, $width, $height, $gravity);
        });
    }

    /**
     * Rotate an image by $degrees (clockwise).
     *
     * @param array{r,int,g,int,b:int,a?:int}|null $backgroundColor Transparent black by default
     */
    public function rotate(string $path, float $degrees, ?array $backgroundColor = null): string
    {
        $key = 'rotate_'.((string) $degrees);

        return $this->createVariant($path, $key, function (\GdImage $image) use ($degrees, $backgroundColor): \GdImage {
            $bg = $backgroundColor ?? [0, 0, 0, 0];
            $color = $this->allocateColor($image, $bg);

            return imagerotate($image, -$degrees, $color) ?: $image;
        });
    }

    /**
     * Flip an image horizontally or vertically.
     *
     * @param string $mode 'horizontal' | 'vertical'
     */
    public function flip(string $path, string $mode = 'horizontal'): string
    {
        $key = "flip_{$mode}";

        return $this->createVariant($path, $key, function (\GdImage $image) use ($mode): \GdImage {
            imageflip($image, 'vertical' === $mode ? IMG_FLIP_VERTICAL : IMG_FLIP_HORIZONTAL);

            return $image;
        });
    }

    /**
     * Convert an image to grayscale.
     */
    public function grayscale(string $path): string
    {
        return $this->createVariant($path, 'grayscale', function (\GdImage $image): \GdImage {
            imagefilter($image, IMG_FILTER_GRAYSCALE);

            return $image;
        });
    }

    /**
     * Re-encode an image at the given quality (same dimensions & format).
     */
    public function optimize(string $path, ?int $quality = null): string
    {
        $quality = $quality ?? $this->quality;
        $key = "optimize_{$quality}";

        return $this->createVariant($path, $key, function (\GdImage $image): \GdImage {
            return $image; // no-op transform: the save step applies the quality
        }, null, $quality);
    }

    /**
     * Convert an image to another format (jpg, png, gif, webp).
     */
    public function convert(string $path, string $format, ?int $quality = null): string
    {
        $targetMime = $this->formatToMime($format);
        $key = 'convert_'.$format;

        return $this->createVariant($path, $key, function (\GdImage $image): \GdImage {
            return $image; // format applied during save
        }, $targetMime, $quality ?? $this->quality);
    }

    // ---------------------------------------------------------------------
    // Output helpers
    // ---------------------------------------------------------------------

    /**
     * Return a data-URI (base64) representation of an image, optionally resized.
     */
    public function dataUri(string $path, ?int $width = null, ?int $height = null): string
    {
        if ($width > 0 && $height > 0) {
            $file = $this->resize($path, $width, $height);
        } else {
            $file = $this->resolvePublicPath($path) ?? $this->getFallbackPath();
        }

        $mime = $this->guessMimeFromFile($file);
        $data = (string) file_get_contents($file);

        return 'data:'.$mime.';base64,'.base64_encode($data);
    }

    /**
     * Build a srcset attribute value for responsive images.
     *
     * @param int[] $widths List of widths (e.g. [300, 600, 1200])
     */
    public function srcset(string $path, array $widths): string
    {
        $parts = [];

        foreach ($widths as $width) {
            $url = $this->getUrlForVariant($path, (int) $width);
            $parts[] = sprintf('%s %dw', $url, (int) $width);
        }

        return implode(', ', $parts);
    }

        /**
     * Resolve the absolute filesystem path of the cached derivative to serve.
     *
     * Falls back to the default image when the source is missing.
     *
     * @param string $mode 'resize' (contain) | 'crop' (cover + center crop) | 'fit'
     */
    public function processedPath(string $path, int $width, int $height, string $mode = 'resize'): string
    {
        $resolved = $this->resolvePublicPath($path);

        if (null === $resolved || !is_file($resolved)) {
            return $this->getFallbackPath();
        }

        $width = max(1, $width);
        $height = max(1, $height);

        return match ($mode) {
            'crop' => $this->thumbnail($path, $width, $height, 'crop'),
            'fit'  => $this->resize($path, $width, $height, true),
            default => $this->resize($path, $width, $height),
        };
    }

    /**
     * Build an HTTP response that streams the requested derivative.
     */
    public function response(string $path, int $width, int $height, string $mode = 'resize'): BinaryFileResponse
    {
        return $this->responseForFile($this->processedPath($path, $width, $height, $mode));
    }

    /**
     * Serve an image over HTTP with a default width & height, using the
     * cache when available and falling back to the default image otherwise.
     */
    public function display(string $path, ?int $width = null, ?int $height = null): BinaryFileResponse
    {
        return $this->response($path, $width ?? $this->defaultWidth, $height ?? $this->defaultHeight);
    }


    /**
     * Generate a small "image not found" placeholder on the fly.
     */
    private function generatePlaceholder(int $width = 800, int $height = 600): string
    {
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 230, 230, 230);
        $fg = imagecolorallocate($image, 120, 120, 120);
        imagefill($image, 0, 0, $bg);
        $text = 'Image not found';
        imagestring($image, 5, (int) round(($width - strlen($text) * 6) / 2), (int) round($height / 2) - 8, $text, $fg);
        $file = $this->cacheDir.'/placeholder.png';
        $this->ensureDirectory(dirname($file));
                imagepng($image, $file);
        imagedestroy($image);

        return $file;
    }

    // ---------------------------------------------------------------------
    // Accessors
    // ---------------------------------------------------------------------

    public function getDefaultWidth(): int
    {
        return $this->defaultWidth;
    }

    public function getDefaultHeight(): int
    {
        return $this->defaultHeight;
    }

    public function getCacheDir(): string
    {
        return $this->cacheDir;
    }

    public function getPublicDir(): string
    {
        return $this->publicDir;
    }

    // ---------------------------------------------------------------------
    // Internal: caching + GD pipeline
    // ---------------------------------------------------------------------

    /**
     * Load the source, run the transform and persist the result in the cache.
     *
     * @param callable(\GdImage): \GdImage $transform
     *
     * @throws \InvalidArgumentException when the source image is missing/invalid
     */
    private function createVariant(string $path, string $variantKey, callable $transform, ?string $targetMime = null, ?int $quality = null): string
    {
        $sourcePath = $this->resolvePublicPath($path);

        if (null === $sourcePath || !is_file($sourcePath)) {
            throw new \InvalidArgumentException(sprintf('Source image "%s" does not exist.', $path));
        }

        /** @var array{0:int,1:int,2:int,3:string}|false $info */
        $info = @getimagesize($sourcePath);

        if (false === $info) {
            throw new \InvalidArgumentException(sprintf('Source "%s" is not a valid image.', $path));
        }

        $sourceMime = $info['mime'];
        $this->currentMime = $sourceMime;
        $outputMime = $targetMime ?? $sourceMime;
        $extension = $this->mimeToExtension($outputMime);
        $sourceMtime = filemtime($sourcePath) ?: 0;

        $hash = sha1($this->toPublicRelative($sourcePath).'|'.$variantKey);
        $cacheFile = $this->cacheDir.DIRECTORY_SEPARATOR.substr($hash, 0, 2).DIRECTORY_SEPARATOR.$hash.'.'.$extension;

        // Reuse a freshly-cached derivative as long as the source is older.
        if (is_file($cacheFile) && (filemtime($cacheFile) ?: 0) >= $sourceMtime) {
            return $cacheFile;
        }

        $image = $this->loadGdImage($sourcePath, $sourceMime);
        $image = $this->fixOrientation($image, $sourcePath);
        $image = $transform($image);

        $this->ensureDirectory(dirname($cacheFile));
        $this->saveGdImage($image, $outputMime, $cacheFile, $quality);
        imagedestroy($image);

        return $cacheFile;
    }

    /**
     * @return \GdImage
     */
    private function loadGdImage(string $path, string $mime)
    {
        $factory = self::MIME_FACTORIES[$mime] ?? null;

        if (null === $factory || !function_exists($factory)) {
            throw new \InvalidArgumentException(sprintf('Unsupported image type "%s" (missing GD driver).', $mime));
        }

        /** @var \GdImage|false $image */
        $image = @$factory($path);

        if (false === $image) {
            throw new \InvalidArgumentException(sprintf('Failed to load image "%s".', $path));
        }

        return $image;
    }

    private function saveGdImage(\GdImage $image, string $mime, string $file, ?int $quality = null): void
    {
        $quality = $quality ?? $this->quality;

        $ok = match ($mime) {
            'image/jpeg' => imagejpeg($image, $file, $quality),
            'image/png'  => imagepng($image, $file, $this->pngCompressionLevel($quality)),
            'image/gif'  => imagegif($image, $file),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $file, $quality) : imagejpeg($image, $file, $quality),
            'image/bmp'  => function_exists('imagebmp') ? imagebmp($image, $file) : imagejpeg($image, $file, $quality),
            'image/avif' => function_exists('imageavif') ? imageavif($image, $file, $quality) : imagejpeg($image, $file, $quality),
            default      => imagejpeg($image, $file, $quality),
        };

        if (!$ok) {
            throw new \RuntimeException(sprintf('Failed to save image to "%s".', $file));
        }
    }

    /**
     * @return array{width:int, height:int}
     */
    private function calculateContainedSize(int $srcWidth, int $srcHeight, int $maxWidth, int $maxHeight, bool $preserveAspectRatio): array
    {
        if (!$preserveAspectRatio) {
            return [$maxWidth, $maxHeight];
        }

        $ratio = min($maxWidth / $srcWidth, $maxHeight / $srcHeight);
        $ratio = min($ratio, 1.0); // never upscale a smaller source

        return [max(1, (int) round($srcWidth * $ratio)), max(1, (int) round($srcHeight * $ratio))];
    }

    /**
     * @return \GdImage
     */
    private function doResize($image, int $width, int $height)
    {
        $dest = $this->createTrueColor($image, $width, $height);

        imagecopyresampled(
            $dest,
            $image,
            0, 0, 0, 0,
            $width,
            $height,
            imagesx($image),
            imagesy($image)
        );

        return $dest;
    }

    /**
     * Cover the box with the source, then crop to exactly $width × $height.
     *
     * The source is scaled (uniform) so it fully covers the target; the
     * excess is then trimmed according to $gravity.
     *
     * @return \GdImage
     */
    private function doCrop($image, int $width, int $height, string $gravity = 'center')
    {
        $srcW = imagesx($image);
        $srcH = imagesy($image);

        // Uniform scale that fully covers the target box.
        $scale = max($width / $srcW, $height / $srcH);

        // Source region (in original pixels) that maps onto the target.
        $cropW = max(1, (int) round($width / $scale));
        $cropH = max(1, (int) round($height / $scale));

        // Top-left corner of the source region, positioned by gravity.
        [$srcX, $srcY] = $this->gravityOffsets(0, 0, $srcW, $srcH, $cropW, $cropH, $gravity);

        $dest = $this->createTrueColor($image, $width, $height);

        imagecopyresampled($dest, $image, 0, 0, $srcX, $srcY, $width, $height, $cropW, $cropH);

        return $dest;
    }

    /**
     * Apply EXIF orientation so photos are never sideways.
     *
     * @return \GdImage
     */
    private function fixOrientation($image, string $path)
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        /** @var array|false $exif */
        $exif = @exif_read_data($path);

        if (!is_array($exif) || !isset($exif['Orientation']) || 1 === (int) $exif['Orientation']) {
            return $image;
        }

        $oriented = imagerotate($image, $this->orientationToDegrees((int) $exif['Orientation']), 0);

        if (false !== $oriented) {
            imagedestroy($image);

            return $oriented;
        }

        return $image;
    }

        private function orientationToDegrees(int $orientation): int
    {
        return match ($orientation) {
            3, 4 => 180,
            5, 6 => 90,
            7, 8 => -90,
            default => 0,
        };
    }

    private function allocateColor(\GdImage $image, array $rgba): int
    {
        if ($this->supportsAlpha($image)) {
            return imagecolorallocatealpha($image, $rgba[0], $rgba[1], $rgba[2], $rgba[3] ?? 127);
        }

        return imagecolorallocate($image, $rgba[0], $rgba[1], $rgba[2]);
    }

    private function createTrueColor(\GdImage $src, int $width, int $height): \GdImage
    {
        $dest = imagecreatetruecolor($width, $height);

        if ($this->supportsAlpha($src)) {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
            $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
            imagefilledrectangle($dest, 0, 0, $width, $height, $transparent);
            imagealphablending($dest, true);
        } else {
            $bg = imagecolorallocate($dest, 255, 255, 255);
            imagefilledrectangle($dest, 0, 0, $width, $height, $bg);
        }

        return $dest;
    }

    private function supportsAlpha(\GdImage $image): bool
    {
        return in_array($this->currentMime, ['image/png', 'image/webp', 'image/gif'], true);
    }

    /**
     * @return array{x:int, y:int} offset inside the source where the crop window starts
     */
    private function gravityOffsets(int $srcX, int $srcY, int $srcW, int $srcH, int $dstW, int $dstH, string $gravity): array
    {
        return match (strtolower($gravity)) {
            'top'          => [$srcX + (int) round(($srcW - $dstW) / 2), $srcY],
            'bottom'       => [$srcX + (int) round(($srcW - $dstW) / 2), $srcH - $dstH],
            'left'         => [$srcX, $srcY + (int) round(($srcH - $dstH) / 2)],
            'right'        => [$srcW - $dstW, $srcY + (int) round(($srcH - $dstH) / 2)],
            'top_left'     => [$srcX, $srcY],
            'top_right'    => [$srcW - $dstW, $srcY],
            'bottom_left'  => [$srcX, $srcH - $dstH],
            'bottom_right' => [$srcW - $dstW, $srcH - $dstH],
            'center'       => [$srcX + (int) round(($srcW - $dstW) / 2), $srcY + (int) round(($srcH - $dstH) / 2)],
            default        => [$srcX, $srcY],
        };
    }

    private function pngCompressionLevel(int $quality): int
    {
        // imagepng expects 0 (no compression) .. 9 (max compression).
        return max(0, min(9, (int) round((100 - $quality) / 100 * 9)));
    }

    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0o775, true);
        }
    }

    private function mimeToExtension(string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            'image/bmp'  => 'bmp',
            'image/avif' => 'avif',
            default      => 'jpg',
        };
    }

    private function formatToMime(string $format): string
    {
        return match (strtolower($format)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'bmp'         => 'image/bmp',
            'avif'        => 'image/avif',
            default       => throw new \InvalidArgumentException(sprintf('Unsupported output format "%s".', $format)),
        };
    }

    private function guessMimeFromFile(string $file): string
    {
        /** @var array{0:int,1:int,2:int,3:string}|false $info */
        $info = @getimagesize($file);

        return false === $info ? 'application/octet-stream' : $info['mime'];
    }

    /**
     * The public-relative path of a resolved filesystem image (stable cache key).
     *
     * Strips either the public directory or the project directory prefix so
     * that images resolved from assets/ (source) and public/ (compiled) both
     * produce a consistent, stable cache key.
     */
    private function toPublicRelative(string $absolutePath): string
    {
        foreach ([$this->publicDir, $this->projectDir] as $basePath) {
            if (str_starts_with($absolutePath, $basePath)) {
                $relative = ltrim(str_replace($basePath, '', $absolutePath), DIRECTORY_SEPARATOR);

                return str_replace(DIRECTORY_SEPARATOR, '/', $relative);
            }
        }

        // Fallback: normalise separators on whatever remains.
        return str_replace(DIRECTORY_SEPARATOR, '/', $absolutePath);
    }

    /**
     * Public web URL for a width-based variant (used by srcset).
     */
    private function getUrlForVariant(string $path, int $width): string
    {
        if ($this->isExternalUrl($path)) {
            return $path;
        }

        return '/image/'.ltrim($path, '/').'?w='.$width;
    }

    private function responseForFile(string $file): BinaryFileResponse
    {
        $response = new BinaryFileResponse($file);
        $response->headers->set('Content-Type', $this->guessMimeFromFile($file));
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($file));

        return $response;
    }
}

