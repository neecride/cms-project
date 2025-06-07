<?php

namespace App;

use Framework\Router;
use Action\AccountAction;
use App\Database;
use App\Validator;

class Renderer {

    public function __construct()
    {

    }

	public function thisPDO()
	{
		return new Database;
	}

    public function thisPasing()
    {
        return new Parsing;
    }

    public function thisSession()
	{
		return new Session;
	}

    public function thisValidator()
	{
		return new Validator;
	}

    public function thisApp()
    {
        return new App;
    }

    public function thisParams()
    {
        return new Parameters;
    }

    public function thisRoute()
    {
        return new Router;
    }
    
    public function thisUser()
    {
        return new AccountAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession(),$this->thisValidator());
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
        $theme = $this->thisParams()->themeForLayout();
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
        // global au site
        $user             = $this->thisUser();
        $session          = $this->thisSession();
        $app              = $this->thisApp();
        $router           = $this->thisRoute();
        $Parsing          = $this->thisPasing();
        $GetParams        = $this->thisParams();
    
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
        require_once RACINE . DS . 'public' . DS . 'templates' . DS . $GetParams->themeForLayout() . DS . 'theme.php';
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

        $app              = $this->thisApp();
        $router           = $this->thisRoute();
        $Parsing          = $this->thisPasing();
        $GetParams        = $this->thisParams();
        $session          = $this->thisSession();

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
