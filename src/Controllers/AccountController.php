<?php

namespace Controllers;

use Action\AccountAction;
use Action\ForumAction;
use App\Pagination;
use App\Renderer;

class AccountController extends Renderer
{
    public function account()
    {
        $this->thisApp()->isNotConnect();
        $forum = new ForumAction($this->thisPDO(), $this->thisApp(), $this->thisRoute());
        $pagination = new Pagination(
            $this->thisPDO(),
            $this->thisRoute(),
            'SELECT COUNT(id) FROM f_topics WHERE f_user_id = ?', 
            $_SESSION['auth']->id,
            $this->thisParams()->GetParam(2),
            $this->thisApp()
        );
        $errMode = $this->thisValidator();
        $user = (new AccountAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession(),$errMode))
                ->desactivAccount()
                ->editEmail()
                ->postAvatar()
                ->delAvatar()
                ->postDescription()
                ->editMdp();
        $this->render('account', compact('user','forum','pagination','errMode'));
    }

}
