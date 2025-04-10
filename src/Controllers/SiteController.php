<?php

namespace Controllers;

use Action\LoginAction;
use App\Renderer;

class SiteController extends Renderer
{

    public function error()
    {
        $this->render('error');
    }

    public function logout()
    {
        $app = $this->app();
        $this->render('logout',compact('app'));
    }

    public function login()
    {
        $this->app()->isLogged();
        (new LoginAction())->login();
        $this->render('login');
    }

}