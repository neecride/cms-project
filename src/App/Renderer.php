<?php

namespace App;

use Framework\Router;
use Action\AccountAction;

class Renderer {

    private App $app;
    private Router $router;
    private $match;
    private Parsing $parsing;
    private Parameters $params;
    private Session $session;
    private AccountAction $user;

    public function __construct()
    {
        $this->app = new App();
        $this->router = new Router();
        $this->match = $this->router->matchRoute();
        $this->parsing = new Parsing();
        $this->params = new Parameters();
        $this->session = new Session();
        $this->user = new AccountAction();
    }

    /**
     * app 
     *
     * @return mixed
     */
    public function app()
    {
        return $this->app;
    }

    /**
     * PerPage
     *
     * @return mixed
     */
    public function Params()
    {
        return $this->params;
    }

    /**
     * ThisRoute retourn une instance de routeur
     *
     * @return string
     */
    public function ThisRoute()
    {
        return $this->router;
    }

    public function isNotExistPage()
    {
        if(isset($_GET['page']) && $_GET['page'] === '1'){

            $uri = explode('?',$_SERVER['REQUEST_URI'])[0];
            $get = $_GET;
            unset($get['page']);
            $query = http_build_query($get);
            if(!empty($query)){
                $uri = $uri . '?' . $query;
            }
            header('Location:' . $uri);
            http_response_code(301);
            exit();
        }
    }
  
    private function getViewPath(string $type, string $fichier): string {
        $base = RACINE . DS . 'public';
        $folder = ($type === 'admin') ? 'admin' : 'templates';
        $theme = $this->Params()->themeForLayout();
        $path = $base . DS . $folder . DS . $theme . DS . 'parts' . DS . $fichier . '.php';
    
        if (!file_exists($path)) {
            throw new \RuntimeException("Le fichier de vue '{$fichier}.php' est introuvable dans '{$path}'.");
        }
    
        return $path;
    }


    /**
     * render rend les vues utilisateurs
     *
     * @param  mixed $fichier
     * @param  mixed $data
     * @return void
     */
    public function render(string $fichier, array $data = []) {
        $app = $this->app();
        $params = $this->Params();

        $app              = $this->app;
        $router           = $this->router;
        $match            = $this->match;
        $Parsing          = $this->parsing;
        $GetParams        = $this->params;
        $session          = $this->session;
        $user             = $this->user;
    
        // Vérifie si le fichier de vue existe
        try {
            $viewPath = $this->getViewPath('user', $fichier);
        } catch (\RuntimeException $e) {
            // Si le fichier n'existe pas, redirige vers une page 404 ou affiche un message d'erreur
            http_response_code(404);
            die("Page introuvable : {$e->getMessage()}");
        }

        // Extraction des données
        extract($data);
    
        // Rendu de la vue
        ob_start();
        require_once $viewPath;
        $contentForLayout = ob_get_clean();
    
        // Chargement du layout principal
        require_once RACINE . DS . 'public' . DS . 'templates' . DS . $params->themeForLayout() . DS . 'theme.php';
    }
  /**
   * render rend les vues utilisateurs
   *
   * @param  mixed $fichier
   * @param  mixed $data
   * @return void
   */
    /*public function render(string $fichier,array $data = [])
    {
        //bug avec les références a testé
        //call_user_func_array($match['target'], [&$match['params']]);
        $app              = $this->app();
        $router           = new Router;
        $match            = $router->matchRoute();
        $Parsing          = new Parsing;
        $GetParams        = $this->Params();
        $session          = new Session;
        $user             = new AccountAction;

        // on extrait les données des contoller
        $data = array_combine(
            array_map(fn($k) => "__" . $k, array_keys($data)),
            $data
        );
        extract($data);

        ob_start();
        require_once (RACINE.DS.'public'.DS.'templates'.DS.$GetParams->themeForLayout().DS.'parts'.DS.$fichier.'.php');
        $contentForLayout = ob_get_clean();
        require_once (RACINE.DS.'public'.DS.'templates'.DS.$GetParams->themeForLayout().DS.'theme.php');
    }*/
  
  /**
   * renderAdmin rend les vues Administrateurs
   *
   * @param  mixed $fichier
   * @param  mixed $data
   * @return void
   */
    public function renderAdmin(string $fichier,array $data = [])
    {

        $app              = $this->app();
        $router           = new Router;
        $match            = $router->matchRoute();
        $Parsing          = new Parsing;
        $GetParams        = $this->Params();
        $session          = new Session;

        extract($data);

        $getUri = explode('/', $_SERVER['REQUEST_URI']);
        if($getUri[1] == 'admin'){
        $app->isAdmin();
        }
        ob_start();
        require_once (RACINE.DS.'public'.DS.'admin'.DS.'parts'.DS.$fichier.'.php');
        $contentForLayout = ob_get_clean();
        require_once (RACINE.DS.'public'.DS.'admin'.DS.'theme.php');
    }

}