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

$builder          = new DI\ContainerBuilder();
$builder->addDefinitions(RACINE.DS.'config'.DS.'di.php');
$container = $builder->build();

$app              = new App\App;
$router           = new Framework\Router($container);

// Reconnexion utilisateur depuis le cookie
$app->reconnectFromCookie();

$router->redirectIfFirstPage();

$router->handleRouting($app);
