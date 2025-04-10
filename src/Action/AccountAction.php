<?php

namespace Action;

use App\App;
use App\Database;
use App\Session;
use App\Validator;
use Framework\Router;

Class AccountAction{

	public  $errors;
	private $app;
	private $cnx;
	private $validator;
	private $router;
	private $session;

	public function __construct()
	{
		$this->app 			= new App;
		$this->cnx 			= new Database;
		$this->router 		= new Router;
		$this->session 		= new Session;
		$this->validator 	= new Validator;
 	}

	/**
	 * userAccount affiche tout les utilisateurs
	 *
	 * @return mixed
	 */
	public function userAccount()
	{	
		if(!empty($_SESSION['auth'])){
			return $this->cnx->Request('SELECT * FROM users WHERE id = ?',[intval($_SESSION['auth']->id)],1);
		}
		return false;
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
	 * editMdp permet d'édité le mot de pass
	 *
	 * @return self
	 */
	public function editMdp(): self
	{
		if(isset($_POST['pwd']))
		{
			$this->validator->methodPostValid('POST');
			$this->session->checkCsrf();
			$pass = trim($_POST['password']);
			$password_confirm = trim($_POST['password_confirm']);
			$this->validator
				 ->notEmpty($pass,$password_confirm)
				 ->isDifferent($pass,$password_confirm, 'mots de pass')
				 ->validMdp($pass);
			if($this->validator->isValid())
			{
				$user_id = (int) $_SESSION['auth']->id;
				$password = trim(password_hash($pass, PASSWORD_BCRYPT));
				$this->cnx->Request("UPDATE users SET password = ? WHERE id = ?",[$password, $user_id]);
				$_SESSION['auth']->password = $password;
				$this->app->setFlash('Votre mots de pass a bien étais modifier');
				$this->app->redirect($this->router->routeGenerate('account'));
			}
			$this->errors = $this->validator->getErrors();

		}
		return $this;
	}
	
	/**
	 * editEmail permet d'édité l'adresse email
	 *
	 * @return self
	 */
	public function editEmail(): self
	{
		if(isset($_POST['edit-email']))
		{
			$this->validator->methodPostValid('POST');
			$this->session->checkCsrf();
			$profil_id = (int) $_SESSION['auth']->id;
			$email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
			$email_confirm = trim(filter_var($_POST['emailConfirm'], FILTER_SANITIZE_EMAIL));
			$exist = $this->cnx->request('SELECT email FROM users WHERE email = ?',[$email],1);
			$this->validator
				 ->notEmpty($email,$email_confirm)
				 ->isDifferent($email,$email_confirm,'email')
				 ->validEmail($email_confirm,'email confirm')
				 ->validEmail($email,'email')
				 ->isReqExist($exist,'email');
			if($this->validator->isValid())
			{
				$this->cnx->Request("UPDATE users SET email = ? WHERE id = ?",[$email, $profil_id]);
				$this->app->setFlash('Votre email a bien étais modifier');
				$this->app->redirect($this->router->routeGenerate('account'));
			}
			$this->errors = $this->validator->getErrors();
		}
		return $this;
	}

	/**
	 * delAvatar permet de supprimé l'avatar
	 *
	 * @return self
	 */
	public function delAvatar(): self
	{
		if(isset($_POST['delete-avatar']))
		{
			$this->validator->methodPostValid('POST');
			$this->session->checkCsrf();
			$file = $_SESSION['auth']->avatar;
			$this->validator
				 ->fileExist($file,'inc/img/avatars/');
			if($this->validator->isValid())
			{
				$profil_id = intval($_SESSION['auth']->id);	
				unlink('inc/img/avatars/' . $file);
				$this->cnx->Request("UPDATE users SET avatar = ? WHERE id = ?",[null,$profil_id]);
				$this->app->setFlash('L\'avatar a bien été supprimer');
				$this->app->redirect($this->router->routeGenerate('account'));
			}
			$this->errors = $this->validator->getErrors();
		}
		return $this;
	}
	
	/**
	 * postAvatar permet l'envoie d'un avatar
	 *
	 * @param  mixed $extensionAllowed
	 * @return self
	 */
	public function postAvatar($extensionAllowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png"]): self
	{
		if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0)
		{
			/*if (
				isset($_FILES['avatar']) 
				&& $_FILES['avatar']['error'] === 0 
				&& $_FILES['avatar']['type'] !== '' 
				&& $_FILES['avatar']['tmp_name'] !== '' 
				&& $_FILES['avatar']['size'] > 0 
				&& $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE 
				&& $_FILES['avatar']['error'] !== UPLOAD_ERR_INI_SIZE 
				&& $_FILES['avatar']['error'] !== UPLOAD_ERR_FORM_SIZE 
				&& $_FILES['avatar']['error'] !== UPLOAD_ERR_PARTIAL 
				&& $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_TMP_DIR 
				&& $_FILES['avatar']['error'] !== UPLOAD_ERR_CANT_WRITE 
				&& $_FILES['avatar']['error'] !== UPLOAD_ERR_EXTENSION
			)*/
			$this->validator->methodPostValid('POST');
			$this->session->checkCsrf();
			//on initialise l'id
			$profil_id = (int) filter_var($_SESSION['auth']->id,FILTER_SANITIZE_NUMBER_INT);
			$avatar = $_FILES['avatar'];
			//on definie l'extension
			$filename = $_FILES['avatar']['name'];
			$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			$save_name = md5(uniqid()).'.'.$extension;
			//taille du fichier envoyé
			$pdsfile = filesize($_FILES['avatar']['tmp_name']);
			$max_size = 40000; //30ko
			//on definie l'extension autorisée
			$this->validator
				 ->sizeFileUpload($pdsfile,$max_size)
				 ->extensionAllowed($extension,$avatar['type'],$extensionAllowed);
			if($this->validator->isValid())
			{
				if(!move_uploaded_file($avatar['tmp_name'], 'inc/img/avatars/'.$save_name))
				{
					$this->app->setFlash("L'upload du fichier a échoué",'rouge');
					$this->app->redirect($this->router->routeGenerate('account'));
				}
				$this->validator->existFolderDestination($save_name,'inc/img/avatars/');
				$this->cnx->Request("UPDATE users SET avatar = ? WHERE id = ?",[$save_name, $profil_id]);
				$_SESSION['auth']->avatar = $save_name;
				$this->app->setFlash('Votre avatar a bien été ajouté');
				$this->app->redirect($this->router->routeGenerate('account'));
			}
			$this->errors = $this->validator->getErrors();
		}
		return $this;
	}	
	
	/**
	 * postDescription met a jour la description
	 *
	 * @return self
	 */
	public function postDescription(): self
	{
		if(isset($_POST['edit-profil']))
		{
			$this->validator->methodPostValid('POST');
			$this->session->checkCsrf();
			$profil_id = (int) $_SESSION['auth']->id;
			$description = trim(strip_tags($_POST['description']));
			$this->validator
				 ->maxLength($description,200,'description');
			if($this->validator->isValid())
			{
				$this->cnx->Request("UPDATE users SET description = ? WHERE id = ?",[$description, $profil_id]);
				$this->app->setFlash('Votre profil a bien été modifier');
				$this->app->redirect($this->router->routeGenerate('account'));
			}
			$this->errors = $this->validator->getErrors();
		}
		return $this;
	}
	
	/**
	 * desactivAccount permet de desactiver le profil utilisateur 
	 *
	 * @return self
	 */
	public function desactivAccount(): self
	{
		if(isset($_POST['lock-account']))
		{
			$this->validator->methodPostValid('POST');
			$this->session->checkCsrf();
			if(!empty($_SESSION['auth']->authorization === 3))
			{
				$this->app->setFlash('Pas possible de supprimer un administrateur','orange');
				$this->app->redirect($this->router->routeGenerate('home'));
			} else {
			  $id = (int) $_SESSION['auth']->id;
			  $this->cnx->Request("UPDATE users SET activation = 0 WHERE id = ?",[$id]);
			  $_SESSION = array();
			  setcookie('remember', NULL, -1);
			  $this->app->setFlash('Votre compte a bien été désactiver');
			  $this->app->redirect($this->router->routeGenerate('home'));
			}
		}
		return $this;
	}
	
	/**
	 * accountLastTopic affiche les 10 dernier topic 
	 *
	 * @return void
	 */
	public function accountLastTopic()
	{

		$userid = $_SESSION['auth']->id;

		return $this->cnx->Request("SELECT

		f_topics.id AS topicid,
		f_topics.f_topic_name,
		f_topics.f_topic_content,
		f_topics.f_user_id,
		f_topics.f_topic_date,
		f_topics.f_topic_message_date,
		f_topics.sticky,
		f_topics.topic_lock,
		f_topics.f_topic_vu,
			users.id AS usersid,
			users.username,
			users.description,
			users.authorization,
			users.avatar,
			users.email,
			users.slug AS userslug,
			users.userurl,
				f_topic_track.read_topic,
		
					/*
					CASE - si on a un nouveau topic on le met au dessu
					et si on a une réponse au passe au dessu du dernier topic
					*/
					CASE
		
					  WHEN f_topic_date < f_topic_message_date THEN f_topic_message_date
		
					  WHEN f_topic_date > f_topic_message_date THEN f_topic_date
		
					  ELSE f_topic_date
		
					END AS Lastdate,
					/* view not view */
					CASE
		
					  WHEN read_topic < f_topic_date THEN f_topic_date
		
					  WHEN read_topic > f_topic_date THEN read_topic
		
					END AS read_last
		
		FROM f_topics
		
		LEFT JOIN f_topic_track ON f_topic_track.topic_id = f_topics.id AND f_topic_track.user_id = ?
		
		LEFT JOIN users ON users.id = f_topics.f_user_id
		
		WHERE users.id = ?
		
		GROUP BY f_topics.id
		
		ORDER BY Lastdate DESC LIMIT 10
		",[intval($userid),intval($userid)]);

	}

}