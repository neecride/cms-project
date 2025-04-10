<?php

namespace Controllers;

use Action\AdminAction;
use Action\AdminTagsAction;
use Action\AdminUsersAction;
use Action\WidgetAlertAction;
use App\Renderer;

class AdminController extends Renderer
{
    
    /**
     * admin Controller view admin
     *
     * @return void
     */
    public function admin()
    {
        $Response = (new AdminAction())->siteSlogan()->themeUpdate()->activBlockSlogan()->paginationPerPage();
        $parameters = $Response->fieldRequest();
        $this->renderAdmin('admin',compact('Response','parameters'));
    }
    
    /**
     * tags Controller view Tags
     *
     * @return void
     */
    public function tags()
    {
        $tags = (new AdminTagsAction());
        $this->renderAdmin('tags', compact('tags'));
    }
    
    /**
     * tagsEdit Controller Tags Edition
     *
     * @return void
     */
    public function tagsEdit()
    {
        $tags = (new AdminTagsAction)->addTag()->editTags()->getId();
        $this->renderAdmin('tagsedit' , compact('tags'));
    }
    
    /**
     * user Controller view user
     *
     * @return void
     */
    public function user()
    {
        $User = (new AdminUsersAction())->activUser()->unactivUser();
        $this->renderAdmin('user',compact('User'));
    }
    
    /**
     * userEdit enfin vous avez pigé maintenant ?
     *
     * @return void
     */
    public function userEdit()
    {
        $User = (new AdminUsersAction())->userEdit()->getId();
        $this->renderAdmin('useredit',compact('User'));
    }

    /**
     * admin Controller view widget-alert
     *
     * @return void
     */
    public function widgetAlert()
    {
        $alert = (new WidgetAlertAction())->alertForm()->alerColor()->activWidget();
        $parameters = $alert->fieldRequest();
        $this->renderAdmin('widgetalert',compact('alert','parameters'));
    }


}