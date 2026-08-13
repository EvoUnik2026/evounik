<?php

namespace App\Controller;

use App\Service\ImageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves images on the fly, resizing / cropping as requested, with the
 * result cached on disk for subsequent requests.
 *
 * Examples:
 *   /image/images/welcome.jpg                  -> 800x600 (defaults), aspect kept
 *   /image/images/welcome.jpg?w=400&h=300      -> contain within 400x300
 *   /image/images/welcome.jpg?w=300&crop=1     -> 300x300 center-cropped thumbnail
 */
#[Route('/image')]
class ImageController extends AbstractController
{
    public function __construct(private readonly ImageService $imageService)
    {
    }

    #[Route('/{path}', name: 'app_image_serve', requirements: ['path' => '.+'])]
    public function serve(string $path, Request $request): Response
    {
        $width = (int) ($request->query->get('w') ?: $this->imageService->getDefaultWidth());
        $height = (int) ($request->query->get('h') ?: $this->imageService->getDefaultHeight());
        $width = max(1, $width);
        $height = max(1, $height);

        // ?crop=1 (or thumbnail=true via Twig) switches to cover + center crop.
        $mode = $request->query->get('crop', '0') ? 'crop' : 'resize';

        /** @var BinaryFileResponse $response */
        $response = $this->imageService->response($path, $width, $height, $mode);
        $response->setPublic();
        $response->setMaxAge(60 * 60 * 24 * 7); // 1 week browser cache

        return $response;
    }
}
