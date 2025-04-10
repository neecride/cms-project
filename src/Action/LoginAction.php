<?php

namespace Action;

use App;
use Framework;

Class LoginAction{

	public  $errors;
	private $app;
	private $cnx;
	private $router;
	private $session;

	public function __construct()
	{
		$this->app 			= new App\App;
		$this->cnx 			= new App\Database;
		$this->router 		= new Framework\Router;
		$this->session 		= new App\Session;
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
     * login permet de connecter un utilisateur avec script force brute en cas d'erreur de login
     *
     * @return void
     */
    public function login(): self
    {
        if(isset($_POST['login']) && $_SERVER['REQUEST_METHOD'] == 'POST'){
            if(!empty($_SESSION['login_time']) && $_SESSION['login_time'] < time()){
                unset($_SESSION['login_fail']);
                unset($_SESSION['login_time']);
            }
            if(!empty($_SESSION['login_fail']) && $_SESSION['login_fail'] >= 5){
                $this->app->setFlash('Vous avez entré de mauvais identifiants 10 fois de suite il vous faut attendre '. date('H\hi',$_SESSION['login_time']) .' pour réessayer','orange');
                $this->app->redirect($this->router->routeGenerate('login'));
            }
            else if(isset($_POST) && !empty($_POST))
            {
                $this->session->checkCsrf();
                $username = strip_tags(trim($_POST['username']));
                $pass = strip_tags(trim($_POST['password']));
                $user = $this->cnx->Request('SELECT *
                                            FROM users 
                                            WHERE (username = :username OR email = :username) 
                                            AND activation = 1 AND confirmed_at 
                                            IS NOT NULL',['username' => $username],1);
                if($user == null){
                    if(empty($_SESSION['login_fail'])){
                        $_SESSION['login_fail'] = 1;
                        $_SESSION['login_time'] = time()+ 60 * 3;
                    }else{
                        $_SESSION['login_fail']++;
                    }
                    $this->app->setFlash("Les données n'existe pas ou votre compte n'est pas actif",'orange');
                    $this->app->redirect('login');
                }
                if(password_verify($pass, $user->password)){
                    $this->cnx->Request('UPDATE users SET lastconect = NOW() WHERE id = ?',[$user->id]);
                    $usersession = $this->cnx->Request('SELECT id,username,email,description,date_inscription,lastconect,activation,authorization,slug,avatar FROM users WHERE id = ?',[$user->id],1);
                    $_SESSION['auth'] = $usersession;
                    if(isset($_POST['remember']) && !empty($_POST['remember'] == 1)){
                        $remember_token = $this->app->StrRandom(100);
                        $this->cnx->Request('UPDATE users SET remember_token = ?, lastconect = NOW() WHERE id = ?',[$remember_token, $user->id]);
                        setcookie('remember', $user->id . '==' . $remember_token . sha1($user->id . 'ratonlaveurs' . $_SERVER['REMOTE_ADDR']), time() + 3600 * 24 * 3, '/', $_SERVER['HTTP_HOST'], false,true);
                        $this->app->setFlash('Vous êtes bien connecter avec un cookie');
                        $this->app->redirect($this->router->routeGenerate('account'));
                    }else{
                        $this->app->setFlash('Vous êtes bien connecter');
                        $this->app->redirect($this->router->routeGenerate('account'));
                    }
                }else{
                    sleep(1);
                    if(empty($_SESSION['login_fail'])){
                        $_SESSION['login_fail'] = 1;
                        $_SESSION['login_time'] = time()+ 60 * 3;
                    }else{
                        $_SESSION['login_fail']++;
                    }
                    $this->app->setFlash('Formulaire incorect ! <strong>Identifiant non valide</strong>','orange');
                    $this->app->redirect($this->router->routeGenerate('login'));
                }
            }
        }
        return $this;
    }

}