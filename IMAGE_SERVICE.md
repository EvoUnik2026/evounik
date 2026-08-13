# ImageService

A lightweight, **GD-backed** service for handling images in Evounik. It is
registered as a service (autowired) and serves images on the fly via a
controller, with results cached on disk so no image is ever processed twice.

> GD is already compiled into the Docker PHP image (`docker/php/Dockerfile`).
> No extra dependencies are required.

## Configuration

Add to `.env.local` to override the defaults:

| Variable            | Default | Purpose                                              |
|---------------------|---------|------------------------------------------------------|
| `IMAGE_DEFAULT_WIDTH`  | `800`   | Default display width when none is given.            |
| `IMAGE_DEFAULT_HEIGHT` | `600`  | Default display height when none is given.           |
| `IMAGE_QUALITY`        | `85`   | JPEG/WebP/AVIF compression quality (1–100).          |

The remaining options live in `config/packages/image.yaml`
(`image.cache_dir`, `image.public_dir`, `image.fallback_path`,
`image.allowed_mime_types`) and can be overridden there if needed.

## The controller (serving images over HTTP)

Images are served through `ImageController` at:

```
/image/{path}?w=WIDTH&h=HEIGHT&crop=1
```

- `?w=` / `?h=` optional dimensions (default to `IMAGE_DEFAULT_WIDTH` / `...HEIGHT`).
- `?crop=1` produces a center‑cropped thumbnail (cover); otherwise the image is
  *contained* within the box (aspect ratio preserved).
- The first request for a given variant is processed with GD and cached under
  `var/image_cache/`; every subsequent request is served from disk
  (1 week browser cache).

Example: `/image/images/welcome.jpg?w=300&h=300&crop=1`

## Twig helpers

The `ImageExtension` exposes three Twig functions (auto‑registered, no setup):

```twig
{# URL only #}
<img src="{{ image_src(topic.image, 400, 300) }}" alt="…">

{# Full <img> tag with default width/height + onerror fallback #}
{{ image_tag(topic.image, 200, 200, { alt: 'Evounik', class: 'img-fluid' }) }}

{# Responsive srcset #}
<img src="{{ image_src(topic.image, 800, 600) }}"
     srcset="{{ image_srcset(topic.image, [400, 800, 1200]) }}" alt="…">

{# A thumbnail (center‑cropped to an exact box) #}
{{ image_tag(topic.image, 100, 100, { alt: 'Thumb', thumbnail: true }) }}
```

`image_tag` automatically:
- applies the **default width/height** when you omit them;
- falls back to the configured fallback image (`images/evouniek.png`) via
  `onerror` when the source image is missing.

External URLs (`http://…` / `https://…`) are passed straight through untouched.

## PHP API

```php
use App\Service\ImageService;

public function __construct(private readonly ImageService $imageService) {}

public function build(): Response
{
    $this->imageService->exists('images/welcome.jpg');         // local or remote?
    $path  = $this->imageService->resize('images/welcome.jpg', 800, 600);
    $thumb = $this->imageService->thumbnail('images/welcome.jpg', 200, 200);
    $crop  = $this->imageService->crop('images/welcome.jpg', 400, 400, 'top');
    $dim   = $this->imageService->getDimensions('images/welcome.jpg');

    $this->imageService->convert('images/welcome.jpg', 'webp');
    $this->imageService->optimize('images/welcome.jpg', 75);
    $this->imageService->grayscale('images/welcome.jpg');
    $this->imageService->rotate('images/welcome.jpg', 90);
    $this->imageService->flip('images/welcome.jpg', 'horizontal');

    return $this->imageService->display('images/welcome.jpg');
}
```

| Method | Returns | Notes |
|--------|---------|-------|
| `exists($path)` | `bool` | Local file or remote URL (HEAD). |
| `resolvePublicPath($path)` | `?string` | Absolute FS path; rejects `..` traversal. |
| `getImageUrl($path)` | `string` | Public URL or fallback URL. |
| `getDimensions($path)` | `array{width,height,mime,size}` | |
| `getMime($path)` | `?string` | |
| `validate($path, $allowed=null)` | `bool` | MIME‑type whitelist check. |
| `resize($p,$w,$h,$preserve=true)` | `string` | Cached derivative path. |
| `thumbnail($p,$w,$h,$mode='crop')` | `string` | `crop`=exact cover; `fit`=contain. |
| `crop($p,$w,$h,$gravity='center')` | `string` | Gravity: center/top/bottom/left/right/… |
| `rotate($p,$deg,$bg=null)` | `string` | Clockwise degrees. |
| `flip($p,$mode='horizontal')` | `string` | |
| `grayscale($p)` | `string` | |
| `optimize($p,$quality=null)` | `string` | Re‑encode, same size. |
| `convert($p,$format,$quality=null)` | `string` | jpg/png/gif/webp/bmp/avif. |
| `dataUri($p,$w=null,$h=null)` | `string` | `data:image/...` base64. |
| `srcset($p,$widths)` | `string` | `url 300w, url 600w`. |
| `processedPath($p,$w,$h,$mode='resize')` | `string` | Internal path the controller uses. |
| `response($p,$w,$h,$mode='resize')` | `BinaryFileResponse` | Streamed cached image. |
| `display($p,$w=null,$h=null)` | `BinaryFileResponse` | Convenience: applies defaults. |

## Console

```bash
php bin/console image:clear-cache
php bin/console image:clear-cache --dry-run
```

## Notes

- Source images are expected under `public/images/...` (referenced as `images/...`).
  Missing sources transparently fall back to the configured fallback image
  (or a generated placeholder if that file is absent).
- EXIF orientation is auto‑corrected on load, so photos are never sideways.
- WebP/AVIF/BMP are used when the GD build supports them, otherwise the service
  gracefully falls back to JPEG.

