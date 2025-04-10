<?php

namespace App;

use Framework\Router;

class App{
	
	private $router;
	private $parameters;
	private $cnx;
	private $parsing;


	public function __construct()
    {
		$this->router 		= new Router;
		$this->parameters   = new Parameters;
		$this->cnx 			= new Database;
		$this->parsing 		= new Parsing;
    }

    public static function DD($dd)
	{
		echo '<pre style="background:#fff;">';
		print_r($dd);
		echo '</pre>';
    }

	public function StrRandom($length)
	{
	    $alphabet = "0123456789azertyuiopqsdfghjklmwxcvbnAZERTYUIOPQSDFGHJKLMWXCVBN";
	    return substr(str_shuffle(str_repeat($alphabet, $length)), 0,$length);
	}

	public function reconnectFromCookie():self
	{
		if(isset($_COOKIE['remember']) && !isset($_SESSION['auth']))
		{
			$remember_token = $_COOKIE['remember'];
			$parts = explode('==', $remember_token);
			$user_id = $parts[0];
			$user = $this->cnx->Request('SELECT * FROM users WHERE id = ?',[$user_id],1);
			$key = sha1($user->id . 'ratonlaveurs' . $_SERVER['REMOTE_ADDR']);
			$usersession = $this->cnx->Request('SELECT 
			id,username,email,description,date_inscription,lastconect,activation,authorization,slug,avatar 
			FROM users 
			WHERE id = ?',[$user->id],1);
			if($_SESSION['auth'] = $usersession)
			{
				$expected = $user_id . '==' . $user->remember_token . $key;
				if($expected == $remember_token)
				{
					$_SESSION['auth'] = $usersession;
					setcookie('remember', $remember_token, time() + 3600 * 24 * 3, '/', $_SERVER['HTTP_HOST'], false,true);
					$this->setFlash('Vous avez été reconnectez automatiquement grace au cookies');
				} else{
					setcookie('remember', null, -1);
				}
			}else{
				setcookie('remember', null, -1);
			}
		}
		return $this;
	}

    /*************
	* trucast long titre
	*************/
	public function trunque($str, int $nb) 
	{
		if (strlen($str) > $nb) 
		{
			$str = substr($str, 0, $nb);
			$str = $str."...";
		}
		return $str;
	}

	function isNotConnect()
	{
		if(empty($_SESSION['auth']))
		{
			$this->setFlash('Vous devez être connecter pour acceder a cette page','orange');
			$this->redirect($this->router->routeGenerate('home'));
		}
	}

	public function isLogged()
	{
	    if(!empty($_SESSION['auth']))
		{
	        $this->setFlash('Vous êtes déjà connecter','orange');
	        $this->redirect($this->router->routeGenerate('home'));
	    } 
	}

	public function isAdmin()
	{
	    if(empty($_SESSION['auth']) or !in_array($_SESSION["auth"]->authorization, [3]))
		{
	        $this->setFlash("Vous n'avez pas acces a cette page <strong> réserver au admin </strong>",'orange');
	        $this->redirect($this->router->routeGenerate('home'));
	    } 
	}

	public function isModo()
	{
	    if(!in_array($_SESSION["auth"]->authorization, [2]))
		{
	        $this->setFlash("Vous n'avez pas acces a cette page <strong> réserver au admin </strong>",'orange');
	        $this->redirect($this->router->routeGenerate('home'));
	    }
	}

	public function widgetAlert()
	{
		$GetParams      = $this->parameters;
		$trunc 			= $this;
		$Parsing 		= $this->parsing;
		$page_activ = $this->parameters->GetParam(4, 'page_activ');
		if($page_activ != null):
			$pages = explode(',', $page_activ);
			$pages_quoted = array_map(function ($page) {
			  return $page;
			}, $pages);
			//activation du widget
			$activeHeader = $this->parameters->GetParam(4, 'param_activ');
			//page sur lesquelles le widget sera visible
			$inpage = in_array($this->router->matchRoute()['target'], $pages_quoted);
			if($activeHeader == "oui" && $inpage): 
				require_once RACINE.DS.'public'.DS.'templates'.DS.$this->parameters->themeForLayout().DS.'parts'.DS.'widgets'.DS.'top'.DS.'widgetAlert.php';
			endif;
		endif;
	}
	
	/**
     * widget retourne des widgets 
     *
     * @return mixed
     */
    public function widget($active = 1,$col = 3)
    {
		$router             = $this->router;
        $match              = $this->router->matchRoute();
        $folderLayout       = $this->parameters->themeForLayout();
		$app      			= $this;
		(int) $titleDiv = 1; 
		(int) $activeWidget = 1;
		$wherePage = ['home','forum','viewtopic','viewforums'];
        $inpage = in_array($match['target'], $wherePage);
		$files = glob(RACINE.DS.'public'.DS.'templates'.DS.$folderLayout.DS.'parts'.DS.'widgets'.DS.'*.php');
		if($activeWidget == 1 && $inpage){
            echo '<div class="col-md-'.$col.'">';
			//si on veut pas du titre on met le param a false
			if($titleDiv == $active){
				echo '<div class="section-title-nav">';
				echo '<h5>Widget</h5>';
				echo '</div>';
			}
			foreach($files as $file)
			{
				require($file);
			}
            echo '</div>';
        }
    }

	/*************
	* redirection
	**************/
	public function redirect($location_page): void
	{
		header("location:".$location_page);
		exit();
	}


	/*************
	* flash message
	**************/
	public function flash(){
	    if(isset($_SESSION['Flash'])){
		    extract($_SESSION['Flash']);
			unset($_SESSION['Flash']);
	        return "<div class='notify notify-$type'><div class='notify-box-content'>$message</div></div>";
		}
	}

	public function setFlash($message,$type = 'vert'){
		$_SESSION['Flash']['message'] = $message;
		$_SESSION['Flash']['type'] = $type;
	}

}