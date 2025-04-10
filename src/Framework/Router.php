<?php

namespace Framework;

use AltoRouter;

class Router {

    private function Route()
    {
      //               action  >  route >  file >  get name
      //$router->map('GET|POST', '/home', 'home','home');
      $router = new AltoRouter();

      $router->map('GET|POST', '/', 'home');
      $router->map('GET|POST', '/home', 'home','home');
      $router->map('GET|POST', '/logout', 'logout','logout');
      $router->map('GET'     , '/error', 'error','error');
      $router->map('GET|POST', '/remember', 'remember','remember');
      $router->map('GET|POST', '/reset-[*:username]-[*:token]', 'reset','reset');
      $router->map('GET|POST', '/register', 'register','register');
      $router->map('GET|POST', '/confirm-[*:username]-[*:token]', 'confirm','confirm');
      $router->map('GET|POST', '/login', 'login','login');

      //user account
      $router->map('GET|POST', '/account', 'account','account');

      //forum
      $router->map('GET'     , '/forum', 'forum', 'forum');
      $router->map('GET'     , '/forum-viewforum-[*:slug]-[i:id]', 'viewforums','forum-tags');
      $router->map('GET|POST', '/forum-topic-[i:id]', 'viewtopic','viewtopic');
      $router->map('GET|POST', '/sticky-[i:id]-[i:sticky]-[*:getcsrf]', 'viewtopic','sticky');
      $router->map('GET|POST', '/lock-[i:id]-[i:lock]-[*:getcsrf]', 'viewtopic','lock');
      $router->map('GET|POST', '/unlock-[i:id]-[i:lock]-[*:getcsrf]', 'viewtopic','unlock');
      $router->map('GET|POST', '/creattopic', 'creattopic','creattopic');
      $router->map('GET|POST', '/edittopic-[i:id]', 'edittopic','edittopic');
      $router->map('GET|POST', '/editrep-[i:id]', 'editrep','editrep');

      //administration
      $router->map('GET|POST', '/admin/dashboard', 'admin','admin');
      $router->map('GET'     , '/admin/user', 'user','user');
      $router->map('GET|POST', '/admin/user-edit-[i:id]-[*:getcsrf]', 'useredit','user-edit');
      $router->map('GET|POST', '/admin/user-delete-[i:del]-[i:rank]-[*:getcsrf]', 'user','user-delete');
      $router->map('GET|POST', '/admin/user-active-[i:activ]-[i:rank]-[*:getcsrf]', 'user','user-active');
      $router->map('GET|POST', '/admin/user-desactive-[i:unactiv]-[i:rank]-[*:getcsrf]', 'user','user-desactive');
      $router->map('GET'     , '/admin/tags', 'tags','tags');
      $router->map('GET|POST', '/admin/tags-add', 'tagsedit','tags-add');
      $router->map('GET|POST', '/admin/tags-edit-[*:editid]-[*:getcsrf]', 'tagsedit','tags-edit');
      $router->map('GET|POST', '/admin/widget-alert', 'widgetalert','widget-alert');

      return $router;
    }
    
    /**
     * routeGenerate génére les liens
     *
     * @param  mixed $page
     * @param  mixed $params
     * @return void
     */
    public function routeGenerate(string $page , ?array $params = [])
    {
      return $this->Route()->generate($page, $params);
    }
    
    /**
     * matchRoute match les diférente route
     *
     * @return void
     */
    public function matchRoute()
    {
      return $this->Route()->match();
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

    public function handleRoute(array $match, $router, $app): void {
        $method = strtolower($match['target']);
    
        if (method_exists($router, $method)) {
            $router->$method();
        } else {
            $app->setFlash("Une erreur est survenue", 'orange');
            http_response_code(404);
            $app->redirect($router->routeGenerate('error'));
        }
    }


    /*
    * target === file name
    */

    public function home()
    {
        if($this->matchRoute()['target'] === 'home')
        {
          return (new \Controllers\ForumController)->home();
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
          return (new \Controllers\ForumController)->viewforum($this->matchRoute()['params']['id']);
        }
    }

    public function viewtopic()
    {
        if($this->matchRoute()['target'] === 'viewtopic')
        {
          return (new \Controllers\ForumController)->viewtopic($this->matchRoute()['params']['id']);
        }
    }

    public function creatTopic()
    {
        if($this->matchRoute()['target'] === 'creattopic')
        {
          return (new \Controllers\ForumController)->creatTopic();
        }
    }

    public function editRep()
    {
        if($this->matchRoute()['target'] === 'editrep')
        {
          return (new \Controllers\ForumController)->editRep();
        }
    }

    public function editTopic()
    {
        if($this->matchRoute()['target'] === 'edittopic')
        {
          return (new \Controllers\ForumController)->editTopic();
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
          return (new \Controllers\SiteController)->error();
        }
    }

    public function logout()
    {
        if($this->matchRoute()['target'] === 'logout')
        {
          return (new \Controllers\SiteController)->logout();
        }
    }

    public function login()
    {
        if($this->matchRoute()['target'] === 'login')
        {
          return (new \Controllers\SiteController)->login();
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