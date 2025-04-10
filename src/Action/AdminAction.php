<?php

namespace Action;

use App;
use Framework;

class AdminAction {

    public  $errors;
	private $app;
	private $cnx;
	private $validator;
	private $router;
    private $parameters;
	private $session;

	public function __construct()
	{
		$this->app 			= new App\App;
		$this->cnx 			= new App\Database;
		$this->router 		= new Framework\Router;
		$this->session 		= new App\Session;
		$this->validator 	= new App\Validator;
        $this->parameters   = new App\Parameters;
 	}


    /**
	 * checkError affiche les erreurs dans la vue
	 *
	 * @return void
	 */
	public function checkError()
	{
		if(!is_null($this->errors))
		{
			return "<div class=\"notify notify-rouge\"><div class=\"notify-box-content\"><li class=\"errmode\">". implode("</li><li class=\"errmode\">",$this->errors) ."</li></div></div>";
		}
	}

    /**
     * fieldRequest affiche les valeurs de la bdd dans les inputs
     *
     * @return void
     */
    public function fieldRequest()
    {
        return $this->cnx->Request("SELECT * FROM parameters");
    }
    
    /**
     * siteSlogan modifie le block slogan du site
     *
     * @return self
     */
    public function siteSlogan(): self
    {
        if(isset($_POST['btnSiteSlogan']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $name = strip_tags(trim($_POST['siteName']));
            $value = strip_tags(trim($_POST['siteSlogan']));
            $edit = (int) trim(filter_var($this->parameters->GetParam(0,'param_id'),FILTER_SANITIZE_NUMBER_INT));
            $this->validator->validTtitle($name ,'titre')
                            ->maxLength($name,50,'titre')
                            ->betweenLength($value,15 , 500,'slogan');
            if($this->validator->isValid())
            {
                $this->cnx->Request("UPDATE parameters SET param_name = ?, param_value = ? WHERE param_id = ?",[$name, $value, $edit]);
                $this->app->setFlash('Le titre et le slogan du widget alert a bien été modifier');
                $this->app->redirect($this->router->routeGenerate('admin'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }

    /**
     * siteName modifie le nom du site
     *
     * @return self
     */
    /*public function siteName(): self
    {
        if(isset($_POST['btnNameSite']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $name = strip_tags(trim($_POST['sitename']));
            $edit = (int) trim(filter_var($this->parameters->GetParam(1,'param_id'),FILTER_SANITIZE_NUMBER_INT));
            $this->validator->validTtitle($name,'nom du site');
            if($this->validator->isValid())
            {
                $u = [$name, $edit];
                $this->cnx->Request("UPDATE parameters SET param_value = ? WHERE param_id = ?",$u);
                $this->app->setFlash('Le nom du site a bien été modifier');
                $this->app->redirect($this->router->routeGenerate('admin'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }*/

    /**
     * paginationPerPage modifie le nombre de page pour la pagination
     *
     * @return self
     */
    public function paginationPerPage(): self
    {
        if(isset($_POST['btnTopicPerPage']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $name = (int) trim(filter_var($_POST['forumpager'],FILTER_SANITIZE_NUMBER_INT));
            $edit = (int) trim(filter_var($this->parameters->GetParam(2,'param_id'),FILTER_SANITIZE_NUMBER_INT));
            $this->validator->optionValidation($name,'10|15|20', 'nombre de page par topic');
            if($this->validator->isValid())
            {
                $u = [$name, $edit];
                $this->cnx->Request("UPDATE parameters SET param_value = ? WHERE param_id = ?",$u);
                $this->app->setFlash('Le theme a bien été modifier');
                $this->app->redirect($this->router->routeGenerate('admin'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }
    
    /**
     * themeUpdate change le template 
     *
     * @return self
     */
    public function themeUpdate(): self
    {
        if(isset($_POST['btnThemeName']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $name = strip_tags(trim($_POST['themeforlayout']));
            $edit = (int) trim(filter_var($this->parameters->GetParam(3,'param_id'),FILTER_SANITIZE_NUMBER_INT));
            $this->validator->validThemeName($name, 'theme name');

            if($this->validator->isValid())
            {
                $u = [$name, $edit];
                $this->cnx->Request("UPDATE parameters SET param_value = ? WHERE param_id = ?",$u);
                $this->app->setFlash('Le theme a bien été modifier');
                $this->app->redirect($this->router->routeGenerate('admin'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }

    /**
     * activWidget active|desactive le widget aler
     *
     * @return self
     */
    public function activBlockSlogan(): self
    {
        if(isset($_POST['btnBlockSlogan']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $name = strip_tags(trim($_POST['activSlogan']));
            $edit = (int) trim(filter_var($this->parameters->GetParam(0,'param_id'),FILTER_SANITIZE_NUMBER_INT));
            $this->validator->optionValidation($name,'oui|non', 'alert activation');
            if($this->validator->isValid())
            {
                $u = [$name, $edit];
                $this->cnx->Request("UPDATE parameters SET param_activ = ? WHERE param_id = ?",$u);
                $this->app->setFlash("Le widget Alert a bien été acitver|desactiver");
                $this->app->redirect($this->router->routeGenerate('admin'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }

}