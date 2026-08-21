<?php
require __DIR__.'/vendor/autoload.php';
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$router = $container->get('router');
$generator = $container->get('router');

// Check the admin route
try {
    $url = $router->generate('admin');
    echo "Admin URL: " . $url . "\n";
} catch (\Throwable $e) {
    echo "Route error: " . $e->getMessage() . "\n";
}

// Check if locales are set on the dashboard
try {
    $dashboard = $container->get(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContextInterface::class);
    echo "Dashboard locales: " . json_encode($dashboard->getDashboardLocales()) . "\n";
} catch (\Throwable $e) {
    echo "Context error: " . $e->getMessage() . "\n";
}

// Try to render the admin template
try {
    $twig = $container->get('twig');
    echo "Twig loaded.\n";
} catch (\Throwable $e) {
    echo "Twig error: " . $e->getMessage() . "\n";
}

// Check DashboardDto methods
echo "Checking Dashboard methods...\n";
echo "Has setLocales: " . (method_exists(\EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard::class, 'setLocales') ? 'yes' : 'no') . "\n";
echo "Has setLocalesSwitcherFormat: " . (method_exists(\EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard::class, 'setLocalesSwitcherFormat') ? 'yes' : 'no') . "\n";
