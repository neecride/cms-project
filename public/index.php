<?php
if(session_status() === PHP_SESSION_NONE)
{
    session_start();
}
session_regenerate_id();
date_default_timezone_set('Europe/Paris');
define            ('DS', DIRECTORY_SEPARATOR);
define            ('RACINE', dirname(__DIR__));
require           (RACINE.DS.'vendor'.DS.'autoload.php');
//require-dev
ini_set('SMTP', "localhost");
ini_set('smtp_port', "1025");
ini_set('sendmail_from', "admin@wampserver.com");
//require-dev
$builder          = new \DI\ContainerBuilder();
$router           = new Framework\Router;
$app              = new App\App;
$builder->addDefinitions(RACINE.DS.'config'.DS.'config.php');
$container = $builder->build();

$match = $router->matchRoute();

// Reconnexion utilisateur depuis le cookie
$app->reconnectFromCookie();

// Redirection pour page=1
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);

if ($page === 1) {
    $uri = explode('?', $_SERVER['REQUEST_URI'])[0];
    $query = http_build_query(array_filter($_GET, fn($key) => $key !== 'page', ARRAY_FILTER_USE_KEY));
    $uri .= (!empty($query)) ? '?' . $query : '';
    header('Location: ' . $uri, true, 301);
    exit();
}

// Gestion des routes
if (is_array($match)) {
    try {
        $router->handleRoute($match, $router, $app);
    } catch (\Exception $e) {
        error_log("Erreur lors de la gestion de la route : " . $e->getMessage());
        http_response_code(500);
        $app->redirect($router->routeGenerate('error'));
    }
} else {
    http_response_code(404);
    try {
        $app->redirect($router->routeGenerate('error'));
    } catch (\Exception $e) {
        require_once RACINE . DS . 'public' . DS . 'templates' . DS . 'error.php';
    }
    exit();
}
