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
        $perpage = $this->Params()->GetParam(2);
        $this->app()->isNotConnect();
        $forum = new ForumAction;
        $account = new AccountAction;
        $pagination = new Pagination(
            $this->ThisRoute(),
            'SELECT COUNT(id) FROM f_topics WHERE f_user_id = ?', 
            $_SESSION['auth']->id,
            $perpage,
            $this->app()
        );
        $user = (new AccountAction)->desactivAccount()->editEmail()->postAvatar()->delAvatar()->postDescription()->editMdp();
        $this->render('account', compact('user','account','forum','pagination'));
    }

}