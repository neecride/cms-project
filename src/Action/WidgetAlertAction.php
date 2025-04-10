<?php

namespace Action;

use App;
use Framework;

class WidgetAlertAction {

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
     * alertForm modifie le contenu|titre de l'alert
     *
     * @return self
     */
    public function alertForm(): self
    {
        if(isset($_POST['btnAlertForm']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $name = strip_tags(trim($_POST['alertTitle']));
            $value = strip_tags(trim($_POST['alertContent']));
            $edit = (int) trim(filter_var($this->parameters->GetParam(4,'param_id'),FILTER_SANITIZE_NUMBER_INT));
            $this->validator->validTtitle($name ,'titre')
                            ->maxLength($name,50,'titre')
                            ->betweenLength($value,30 , 500,'contenue');
            if($this->validator->isValid())
            {
                $this->cnx->Request("UPDATE parameters SET param_name = ?, param_value = ? WHERE param_id = ?",[$name, $value, $edit]);
                $this->app->setFlash('Le titre et le contenue du widget alert a bien été modifier');
                $this->app->redirect($this->router->routeGenerate('widget-alert'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }

    /**
     * alerColor change la couleur de l'alert
     *
     * @return self
     */
    public function alerColor(): self
    {
        if(isset($_POST['btnAlertColor']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $name = strip_tags(trim($_POST['alertColor']));
            $edit = (int) trim(filter_var($this->parameters->GetParam(4,'param_id'),FILTER_SANITIZE_NUMBER_INT));
            $this->validator->optionValidation($name,'turquoise|rouge|orange|bleu|violet|vert', 'alert color');
            if($this->validator->isValid())
            {
                $u = [$name, $edit];
                $this->cnx->Request("UPDATE parameters SET param_color = ? WHERE param_id = ?",$u);
                $this->app->setFlash('La couleur a bien été pris en compte');
                $this->app->redirect($this->router->routeGenerate('widget-alert'));
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
    public function activWidget(): self
    {
        if(isset($_POST['btnActivAlert']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $name = strip_tags(trim($_POST['activAlert']));
            $edit = (int) trim(filter_var($this->parameters->GetParam(4,'param_id'),FILTER_SANITIZE_NUMBER_INT));
            $this->validator->optionValidation($name,'oui|non', 'alert activation');
            if($this->validator->isValid())
            {
                $u = [$name, $edit];
                $this->cnx->Request("UPDATE parameters SET param_activ = ? WHERE param_id = ?",$u);
                $this->app->setFlash("Le widget Alert a bien été acitver|desactiver");
                $this->app->redirect($this->router->routeGenerate('widget-alert'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }

}