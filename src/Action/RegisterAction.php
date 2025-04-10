<?php

namespace Action;

use App;
use Framework;

Class RegisterAction{

	public  $errors;
	private $app;
	private $cnx;
	private $validator;
	private $router;
	private $session;

	public function __construct()
	{
		$this->app 			= new App\App;
		$this->cnx 			= new App\Database;
		$this->router 		= new Framework\Router;
		$this->session 		= new App\Session;
		$this->validator 	= new App\Validator;
 	}
	
	public function checkError()
	{
		if(!is_null($this->errors))
		{
			return "<div class=\"notify notify-rouge\"><div class=\"notify-box-content\"><li class=\"errmode\">". implode("</li><li class=\"errmode\">",$this->errors) ."</li></div></div>";
		}
	}

    /**
     * register permet d'incrire un utilisateur
     *
     * @return self
     */
    public function register(): self
    {
        if(isset($_POST['register']))
        {
            $this->validator->methodPostValid('POST');
			$this->session->checkCsrf();
            $username = strip_tags(trim($_POST['name']));
            $pass = strip_tags(trim($_POST['pass']));
            $password_confirm = strip_tags(trim($_POST['pass_confirm']));
            $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
            $captcha = (int) trim($_POST['captcha']);
            $existMail = $this->cnx->request('SELECT id FROM users WHERE email = ?',[$email],1);
            $existUsername = $this->cnx->request('SELECT id FROM users WHERE username = ?',[$username],1);
            $this->validator
                ->notEmpty($pass,$email,$captcha)
                ->validMdp($pass,'mots de pass')
                ->validName($username,'username')
                ->validEmail($email, 'email')
                ->isReqExist($existUsername,'username')
                ->isReqExist($existMail, 'email')
                ->isDifferent($pass,$password_confirm,'mots de pass')
                ->isDifferent($captcha,$_SESSION['captcha'], 'captcha');
            if($this->validator->isValid()){
                $token = strtolower($this->app->StrRandom(60));
                $password = password_hash($pass, PASSWORD_BCRYPT);

                $this->cnx->Request("INSERT INTO users 
                            SET username = ?, password = ?, email = ?, confirmed_token = ?, date_inscription = now()",[$username,  $password, $email, $token]);
                $header="MIME-Version: 1.0\r\n";
                $header.='From:"'.$_SERVER['HTTP_HOST'].'"<support@'.$_SERVER['SERVER_ADMIN'].'>'."\n";
                $header.='Content-Type:text/html; charset="utf-8"'."\n";
                $header.='Content-Transfer-Encoding: 8bit';

                $message = '
                <html>
                    <body>
                        <div align="center">
                            Pour valider votre compte merci de cliquer sur ce >> <a href="http://'.$_SERVER['HTTP_HOST'].'/confirm-'.urlencode($username).'-'.$token.'" target="_blank" >LIEN</a> <<
                        </div>
                    </body>
                </html>
                ';
                mail($_POST['email'], 'Confirmation de votre inscription',$message,$header);
                $this->app->setFlash('Vous êtes bien inscrit reste a valider votre compte ! par Email');
                $this->app->redirect($this->router->routeGenerate('home'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }

    /**
     * confirAccount permet de confirmer le compte d'un utilisateur avec le email reçu dans sa boite email
     *
     * @return self
     */
    public function confirAccount(): self
    {
        $this->validator->methodPostValid('GET');
        $user_name = strip_tags($this->router->matchRoute()['params']['username']);
        $token = strip_tags($this->router->matchRoute()['params']['token']);
        $user = $this->cnx->Request('SELECT * FROM users WHERE username = ?',[$user_name],1);
        if($user && $user->confirmed_token == $token ){
            $this->cnx->Request('UPDATE users SET slug = "membre", activation = 1, confirmed_token = null, confirmed_at = NOW() WHERE username = ?',[$user_name]);
            $usersession = $this->cnx->Request('SELECT id,username,email,description,date_inscription,lastconect,activation,authorization,slug,avatar FROM users WHERE id = ?',[$user->id],1);
            $_SESSION['auth'] = $usersession;
            $this->app->setFlash('Votre compte a bien étais valider');
            $this->app->redirect($this->router->routeGenerate('account'));
        }else{
            $this->app->setFlash('Ce token n\'est pas ou plus valide <strong>Logger vous ou inscriver vous</strong>','rouge');
            $this->app->redirect($this->router->routeGenerate('error'));
        }
        return $this;
    }
    
    /**
     * remember permet d'envoyer un lien de réinitalisation du mots de passe
     *
     * @return self
     */
    public function remember(): self
    {
        if(!empty($_POST) && !empty(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)))
        {
            $this->validator->methodPostValid('POST');
			$this->session->checkCsrf();
            $exist = $this->cnx->Request('SELECT * FROM users WHERE email = ? AND confirmed_at IS NOT NULL',[$_POST['email']],1);
            if($exist->activation == 0)
            {
                $this->app->setFlash("Vous ne pouvez pas demander de réinitialisation si votre compte n'a pas été activer lors de votre inscription ou si vous avez un ban",'orange');
                $this->app->redirect($this->router->routeGenerate('home'));
            } 
            if($exist)
            {
                $reset_token = strtolower($this->app->StrRandom(60));
                $this->cnx->Request('UPDATE users SET reset_token = ?, reset_at = NOW() WHERE id = ?',[$reset_token, $exist->id]);
                $header="MIME-Version: 1.0\r\n";
                $header.='From:"'.$_SERVER['HTTP_HOST'].'"<support@'.$_SERVER['SERVER_ADMIN'].'>'."\n";
                $header.='Content-Type:text/html; charset="utf-8"'."\n";
                $header.='Content-Transfer-Encoding: 8bit';
                $message = '
                <html>
                    <body>
                        <div align="center">
                            Pour réinitialiser votre mots de pass merci de cliquer sur ce >> <a href="http://'.$_SERVER['HTTP_HOST'].'/reset-'.urlencode($exist->username).'-'.$reset_token.'" target="_blank">LIEN</a> <<
                        </div>
                    </body>
                </html>
                ';
                mail($_POST['email'], 'Réinitialisation de votre mots de pass',$message,$header);
                $this->app->setFlash('Lien de restauration de mots de pass a bien étais envoyez');
                $this->app->redirect($this->router->routeGenerate('home'));
            }else{
                $this->app->setFlash('Aucun compte ne correspond a cette email','rouge');
                $this->app->redirect($this->router->routeGenerate('home'));
            }
        }
        return $this;
    }

    /**
     * resetAccount permet de mettre a jour le mots de passe si le token est valide
     *
     * @return self
     */
    public function resetAccount(): self
    {
        if(isset($this->router->matchRoute()['params']['username']) && isset($this->router->matchRoute()['params']['token']))
        {
            $user_name = strip_tags($this->router->matchRoute()['params']['username']);
            $token = strip_tags($this->router->matchRoute()['params']['token']);
            $user = $this->cnx->Request("SELECT * FROM users 
            WHERE username = ? 
            AND reset_token IS NOT NULL 
            AND reset_token = ? 
            AND reset_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)",[$user_name, $token],1);
            if($user){
                if(isset($_POST['pwd']))
                {
                    $usersession = $this->cnx->Request('SELECT id,username,email,description,date_inscription,lastconect,activation,authorization,slug,avatar FROM users WHERE id = ?',[$user->id],1);
                    $this->validator->methodPostValid('POST');
                    $this->session->checkCsrf();
                    $pass = strip_tags(trim($_POST['password']));
                    $password_confirm = strip_tags(trim($_POST['password_confirm']));

                    $this->validator->isDifferent($pass, $password_confirm, 'mots de pass')
                                    ->notEmpty($pass,$password_confirm)
                                    ->validMdp($pass);
                    if($this->validator->isValid())
                    {
                        $password = password_hash($pass, PASSWORD_BCRYPT);
                        $this->cnx->Request("UPDATE users SET password = ?, reset_at = NULL, reset_token = NULL",[$password]);
                        $_SESSION['auth'] = $usersession;
                        $this->app->setFlash('Votre mots de pass a bien étais modifier');
                        $this->app->redirect($this->router->routeGenerate('home'));
                    }
                    $this->errors = $this->validator->getErrors();
                }
            }else{
                $this->app->setFlash('<strong>Ho ho!</strong> mauvaise URL <strong>Ce token n\'est pas valide</strong>','rouge');
                $this->app->redirect($this->router->routeGenerate('home'));
            }

        }else{
            $this->app->setFlash('<strong>Ho ho!</strong> mauvaise URL <strong>Vous n\'avez pas le droit d\'être sur cette page </strong>','rouge');
            $this->app->redirect($this->router->routeGenerate('home'));
        }
        return $this;
    }

}