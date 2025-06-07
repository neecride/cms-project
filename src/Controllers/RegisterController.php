<?php

namespace Controllers;

use Action\RegisterAction;
use App\App;
use App\Renderer;

class RegisterController extends Renderer
{

    public function register()
    {
        $errMode = $this->thisValidator();
        $this->thisApp()->isLogged();
        (new RegisterAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession(),$errMode))->register();
        $this->render('register', compact('errMode'));
    }

    public function remember()
    {
        $errMode = $this->thisValidator();
        $this->thisApp()->isLogged();
        (new RegisterAction( $this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession()))->remember();
        $this->render('remember', compact('errMode'));
    }

    public function reset()
    {
        $errMode = $this->thisValidator();
        $this->thisApp()->isLogged();
        (new RegisterAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession(),$errMode))->resetAccount();
        $this->render('reset', compact('errMode'));
    }

    public function confirm()
    {
        $errMode = $this->thisValidator();
        $this->thisApp()->isLogged();
        (new RegisterAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession()))->confirAccount();
        $this->render('confirm',compact('errMode'));
    }


}
