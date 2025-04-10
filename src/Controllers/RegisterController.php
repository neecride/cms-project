<?php

namespace Controllers;

use Action\RegisterAction;
use App\App;
use App\Renderer;

class RegisterController extends Renderer
{

    public function register()
    {
        $app = new App;
        $app->isLogged();
        $register = (new RegisterAction)->register();
        $this->render('register', compact('register'));
    }

    public function remember()
    {
        $app = new App;
        $app->isLogged();
        (new RegisterAction())->remember();
        $this->render('remember');
    }

    public function reset()
    {
        $app = new App;
        $app->isLogged();
        $reset = (new RegisterAction())->resetAccount();
        $this->render('reset', compact('reset'));
    }

    public function confirm()
    {
        $app = new App;
        $app->isLogged();
        (new RegisterAction)->confirAccount();
        $this->render('confirm');
    }


}