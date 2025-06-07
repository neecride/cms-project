<?php

namespace Controllers;

use Action\LoginAction;
use Action\AccountAction;
use App\Renderer;

class SiteController extends Renderer
{

    public function error()
    {
        $this->render('error');
    }

    public function logout()
    {
        $this->render('logout');
    }

    public function login()
    {
        $this->thisApp()->isLogged();
        (new LoginAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession()))->login();
        $this->render('login');
    }

}
