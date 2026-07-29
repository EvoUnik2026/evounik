<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MathExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('sin', 'sin'),
            new TwigFunction('cos', 'cos'),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'pi' => pi(),
        ];
    }
}
