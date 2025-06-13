<?php

namespace Framework;

use AltoRouter;

class Router
{
    /**
     * @var AltoRouter
     */
    private AltoRouter $router;

    public function __construct()
    {
        $this->router = new AltoRouter();
        $this->registerRoutes();
    }

    /**
     * Enregistre toutes les routes de l'application.
     *
     * @return void
     */
    private function registerRoutes(): void
    {
        $routes = require RACINE . DS . 'config' . DS . 'routes.php';
        foreach ($routes as $route) {
            [$method, $url, $target, $name] = array_pad($route, 4, null);
            $this->router->map($method, $url, $target, $name);
        }
    }

    /**
     * Redirige vers l'URL canonique si le paramètre `page=1` est présent dans l'URL.
     * Évite les contenus dupliqués pour le SEO.
     *
     * @return void
     */
    public function redirectIfFirstPage(): void
    {
      $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);

      if ($page === 1) {
          $uri = explode('?', $_SERVER['REQUEST_URI'])[0];
          $query = http_build_query(array_filter($_GET, fn($key) => $key !== 'page', ARRAY_FILTER_USE_KEY));
          $uri .= (!empty($query)) ? '?' . $query : '';
          header('Location: ' . $uri, true, 301);
          exit();
      }
    }

    /**
     * Gère la route correspondante ou affiche une page d'erreur.
     * @param object $app    Application principale (pour flash, redirection)
     *
     * @return void
     */
    public function handleRouting($app): void
    {
        if (is_array($this->matchRoute())) {
            try {
                $this->handleRoute($this->matchRoute(), $app);
            } catch (\Exception $e) {
                error_log("Erreur route : " . $e->getMessage());
                http_response_code(500);
                $app->redirect($this->routeGenerate('error'));
            }
        } else {
            http_response_code(404);
            try {
                $app->redirect($this->routeGenerate('error'));
            } catch (\Exception $e) {
                require_once RACINE . DS . 'public' . DS . 'templates' . DS . 'error.php';
            }
            exit();
        }
    }

    /**
     * Exécute la méthode correspondant à la route trouvée.
     * @param object $app   Instance de l'application
     *
     * @return void
     */
    public function handleRoute($app): void
    {
        $method = strtolower($this->matchRoute()['target']);

        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            $app->setFlash("Une erreur est survenue", 'orange');
            http_response_code(404);
            $app->redirect($this->routeGenerate('error'));
        }
    }

    /**
     * Génère une URL à partir d’un nom de route et de ses paramètres.
     *
     * @param string $name   Nom de la route
     * @param array|null $params Paramètres optionnels
     * @return string
     */
    public function routeGenerate(string $name, ?array $params = []): string
    {
        return $this->router->generate($name, $params ?? []);
    }

    /**
     * Tente de faire correspondre la requête HTTP actuelle à une route définie.
     *
     * @return mixed
     */
    public function matchRoute()
    {
        return $this->router->match();
    }

    /**
     * Retourne l’instance interne d’AltoRouter (utile si besoin avancé).
     *
     * @return AltoRouter
     */
    public function getRouter(): AltoRouter
    {
        return $this->router;
    }

    /**
     * webroot 
     *
     * @return void
     */
    public function webroot()
    {
        $path = dirname(dirname(__FILE__));

        $directory = basename($path);
        $url = explode($directory, $_SERVER['REQUEST_URI']);
        if(count($url) == 1){
            $absolute = DIRECTORY_SEPARATOR;
        }else{
            $absolute = $url[0] . $directory . DIRECTORY_SEPARATOR;
        }

        return $absolute;
    }


    /*
    * target === file name
    */
    public function home()
    {
        if($this->matchRoute()['target'] === 'home')
        {
          return (new \Controllers\HomeController())->home();
        }
    }

    public function forum()
    {
        if($this->matchRoute()['target'] === 'forum')
        {
          return (new \Controllers\ForumController)->forum();
        }
    }

    public function viewforums()
    {
        if($this->matchRoute()['target'] === 'viewforums')
        {
          return (new \Controllers\ViewforumController)->viewforum($this->matchRoute()['params']['id']);
        }
    }

    public function viewtopic()
    {
        if($this->matchRoute()['target'] === 'viewtopic')
        {
          return (new \Controllers\ViewtopicController)->viewtopic($this->matchRoute()['params']['id']);
        }
    }

    public function creatTopic()
    {
        if($this->matchRoute()['target'] === 'creattopic')
        {
          return (new \Controllers\CreattopicController)->creatTopic();
        }
    }

    public function editRep()
    {
        if($this->matchRoute()['target'] === 'editrep')
        {
          return (new \Controllers\EditreponseController)->editRep();
        }
    }

    public function editTopic()
    {
        if($this->matchRoute()['target'] === 'edittopic')
        {
          return (new \Controllers\EdittopicController)->editTopic();
        }
    }

    public function account()
    {
        if($this->matchRoute()['target'] === 'account')
        {
          return (new \Controllers\AccountController)->account();
        }
    }

    public function admin()
    {
        if($this->matchRoute()['target'] === 'admin')
        {
          return (new \Controllers\AdminController)->admin();
        }
    }

    public function widgetAlert()
    {
        if($this->matchRoute()['target'] === 'widgetalert')
        {
          return (new \Controllers\AdminController)->widgetAlert();
        }
    }

    public function tags()
    {
        if($this->matchRoute()['target'] === 'tags')
        {
          return (new \Controllers\AdminController)->tags();
        }
    }

    public function tagsEdit()
    {
        if($this->matchRoute()['target'] === 'tagsedit')
        {
          return (new \Controllers\AdminController)->tagsEdit();
        }
    }

    public function user()
    {
        if($this->matchRoute()['target'] === 'user')
        {
          return (new \Controllers\AdminController)->user();
        }
    }

    public function userEdit()
    {
        if($this->matchRoute()['target'] === 'useredit')
        {
          return (new \Controllers\AdminController)->userEdit();
        }
    }

    public function error()
    {
        if($this->matchRoute()['target'] === 'error')
        {
          return (new \Controllers\BaseController)->error();
        }
    }

    public function logout()
    {
        if($this->matchRoute()['target'] === 'logout')
        {
          return (new \Controllers\BaseController)->logout();
        }
    }

    public function login()
    {
        if($this->matchRoute()['target'] === 'login')
        {
          return (new \Controllers\BaseController)->login();
        }
    }

    public function register()
    {
        if($this->matchRoute()['target'] === 'register')
        {
          return (new \Controllers\RegisterController)->register();
        }
    }

    public function remember()
    {
        if($this->matchRoute()['target'] === 'remember')
        {
          return (new \Controllers\RegisterController)->remember();
        }
    }

    public function confirm()
    {
        if($this->matchRoute()['target'] === 'confirm')
        {
          return (new \Controllers\RegisterController)->confirm();
        }
    }

    public function reset()
    {
        if($this->matchRoute()['target'] === 'reset')
        {
          return (new \Controllers\RegisterController)->reset();
        }
    }

}
