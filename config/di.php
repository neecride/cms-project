<?php

use DI\ContainerBuilder;
use App\Renderer;
use App\Database;
use App\Session;
use App\Parameters;
use App\App;
use App\Parsing;
use App\Validator;
use Framework\Router;
use Action\AccountAction;

$builder = new ContainerBuilder();
$builder->addDefinitions([
    Database::class => DI\autowire(Database::class),
    Session::class => DI\autowire(Session::class),
    Parameters::class => DI\autowire(Parameters::class),
    App::class => DI\autowire(App::class),
    Parsing::class => DI\autowire(Parsing::class),
    Validator::class => DI\autowire(Validator::class),
    Router::class => DI\autowire(Router::class),
    AccountAction::class => DI\autowire(AccountAction::class),
    Renderer::class => DI\autowire(Renderer::class),

    // Tous tes contrôleurs ici
    \Controllers\HomeController::class => DI\autowire(),
    \Controllers\ForumController::class => DI\autowire(),
    // etc.
]);

return $builder->build();
