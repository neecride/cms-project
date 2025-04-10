<?php

namespace App;

use Framework;

class Session{

	private $router;
	private $app;

	public function __construct()
	{
		$this->router = new Framework\Router;
		$this->app = new App;
	}

	/**
	 * ExistCsrf création du jeton csrf
	 *
	 * @return void
	 */
	private function ExistCsrf()
	{
		if(!isset($_SESSION['csrf'])){
			$_SESSION['csrf'] = md5(time() + mt_rand());
		}
	}

	/**
	 * flash affiche le message flash en session puis l'efface - unset
	 *
	 * @return void
	 */
	public function flash(){
		if(isset($_SESSION['Flash']))
		{
			extract($_SESSION['Flash']);
			unset($_SESSION['Flash']);
			return "<div class='notify notify-$type'><div class='notify-box-content'>$message</div></div>";
		}
	}

	/**
	 * setFlash créer un message flash en session
	 *
	 * @param  mixed $message
	 * @param  mixed $type
	 * @return void
	 */
	public function setFlash(string $message,string $type = 'vert'){
		$_SESSION['Flash']['message'] = $message;
		$_SESSION['Flash']['type'] = $type;
	}

	/**
	 * csrf jeton sécurité pour les liens edite|delete etc...
	 *
	 * @return void
	 */
	public function csrf()
	{
		$this->ExistCsrf();
	    return $_SESSION['csrf'];
	}

	/**
	 * csrfInput input hidden avec jeton de sécurité
	 *
	 * @return void
	 */
	public function csrfInput()
	{
		return '<input type="hidden" value="' . $this->csrf() . '" name="csrf">';
	}

	/**
	 * checkCsrf jeton de validation de formulaire 
	 *
	 * @return void
	 */
	public function checkCsrf()
	{
		$match = $this->router->matchRoute();
	    if(
	        (isset($_POST['csrf']) && $_POST['csrf'] == $this->csrf()) 
	        ||
	        (isset($match['params']['getcsrf']) && $match['params']['getcsrf'] == $this->csrf())
	      )
	    {
	      return true;
	    }
	    $this->app->setFlash("C'est pas bien ! <strong> :( Faille CSRF </strong>",'rouge');
	    $this->app->redirect($this->router->routeGenerate('error'));
	}


}