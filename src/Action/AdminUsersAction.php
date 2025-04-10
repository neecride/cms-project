<?php

namespace Action;

use App;
use Framework;

class AdminUsersAction{


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


    /**
	 * checkError affiche les erreurs dans la vue
	 *
	 * @return mixed
	 */
	public function checkError()
	{
		if(!is_null($this->errors))
		{
			return "<div class=\"notify notify-rouge\"><div class=\"notify-box-content\"><li class=\"errmode\">". implode("</li><li class=\"errmode\">",$this->errors) ."</li></div></div>";
		}
	}

    /**
     * getUsers retourne tout les utlisateurs de la bdd
     *
     * @return mixed
     */
    public function getUsers()
    {
        return $this->cnx->Request("SELECT * FROM users ORDER BY date_inscription");
    }

    /**
     * getUser retourne 1 utilisateur
     *
     * @return mixed
     */
    public function getUser()
    {
        if(!empty($this->router->matchRoute()['params']['id']))
        {
            $id = (int) $this->router->matchRoute()['params']['id'];
            return $this->cnx->Request("SELECT * FROM users WHERE id = ?",[$id],1);
        }
    }

    /**
     * getId vérifie si on a bien un id en GET et qu'il correspond a la bdd
     *
     * @return self
     */
    public function getId(): self
    {
        if(is_null($this->getUser()->id))
        {
            $this->app->setFlash('Un problème est survenue <strong> aucun utilisateurs avec cet ID </strong>','orange');
            $this->app->redirect($this->router->routeGenerate('user'));
        }
        return $this;
    }

    /**
     * userEdit edite un utilisateur 
     *
     * @return self
     */
    public function userEdit(): self
    {
        if(isset($_POST['users']) && isset($this->router->matchRoute()['params']['id']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $slug = strip_tags(trim($_POST['slug']));
            $username = strip_tags(trim($_POST['name']));
            $this->validator->validName($username,'username')
                            ->optionValidation($slug,'admin|modo|membre');
            if($this->validator->isValid()){
                    //on transforme le slug en int
                    if($slug === "admin"){
                        $authorization = (int) 3;
                    }elseif($slug === "modo"){
                        $authorization = (int) 2;
                    }elseif($slug === "membre"){
                        $authorization = (int) 1;
                    }
                    $id = (int) $this->router->matchRoute()['params']['id'];
                    $this->cnx->Request("UPDATE users SET username = ?, slug = ?, authorization = ? WHERE id = ?",[$username,$slug,$authorization,$id]);
                    $this->app->setFlash('Votre utilisateur a bien étais modifier');
                    $this->app->redirect($this->router->routeGenerate('user'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }

    /**
     * activUser active un utilisateur après un ban par exemple
     *
     * @return self
     */
    public function activUser(): self
    {
        if(isset($this->router->matchRoute()['params']['activ']))
        {
            $this->validator->methodPostValid('GET');
            $this->session->checkCsrf();
            if($this->router->matchRoute()['params']['rank'] == 3){
                $this->app->setFlash('On ne peut pas désactivé ou supprimé un admin','rouge');
                $this->app->redirect($this->router->routeGenerate('user'));
            }else{
                $id = (int) $this->router->matchRoute()['params']['activ'];
                $this->cnx->Request("UPDATE users 
                                    SET slug = 'membre', activation = '1', authorization = '1' ,confirmed_token = null, confirmed_at = NOW() 
                                    WHERE id = ?",[$id]);
                $this->app->setFlash("L'utilisateur a bien étais mis a jour");
                $this->app->redirect($this->router->routeGenerate('user'));
            }
        }
        return $this;
    }

    /**
     * unactivUser désactive un utilisateur avec un ban par exemple
     *
     * @return self
     */
    public function unactivUser(): self
    {
        if(isset($this->router->matchRoute()['params']['unactiv']))
        {
            $this->validator->methodPostValid('GET');
            $this->session->checkCsrf();
            if($this->router->matchRoute()['params']['rank'] == 3){
                $this->app->setFlash('On ne peut pas désactivé ou supprimé un admin','rouge');
                $this->app->redirect($this->router->routeGenerate('user'));
            }else{
                $id = (int) $this->router->matchRoute()['params']['unactiv'];
                $this->cnx->Request("UPDATE users SET activation = 0 WHERE id = ?",[$id]);
                $this->app->setFlash("L'utilisateur a bien étais mis a jour");
                $this->app->redirect($this->router->routeGenerate('user'));
            }
        }
        return $this;
    }
    
    /**
     * deleteUser permet de supprimé un utilisateur
     *
     * @return self
     */
    public function deleteUser(): self
    {
        /*
        if(isset($match['params']['del'])){
        die('fonction non fini');
        checkCsrf();

        if($match['params']['rank'] == 3){

            setFlash('On ne peut pas désactivé ou supprimé un admin','rouge');

            redirect($router->routeGenerate('user'));

        }else{

            $id = (int) $match['params']['delid'];

            $u = [$id];

            $reqdelete = $db->prepare("SELECT email FROM users WHERE id = ?");
            
            $reqdelete->execute([$id]);

            $rowdel = $reqdelete->fetch();
            
            $header="MIME-Version: 1.0\r\n";
            $header.='From:"'.$_SERVER['HTTP_HOST'].'"<support@'.$_SERVER['HTTP_HOST'].'.com>'."\n";
            $header.='Content-Type:text/html; charset="uft-8"'."\n";
            $header.='Content-Transfer-Encoding: 8bit';

            $message = "
            <html>
                <body>
                    <div align='center'>
                        <p>Vous recevez ce mail car vous avez demander la destruction de votre compte avec toutes ses données.</p> 
                        <p>C'est chose faite.</p>
                    </div>
                </body>
            </html>
            ";

            mail($rowdel->email, 'Confirmation de suppression',$message,$header);

            $deltopic = $db->prepare('DELETE FROM f_topics WHERE f_user_id = ?')->execute($u);
            
            $delrep = $db->prepare('DELETE FROM f_topics_reponse WHERE f_user_id = ?')->execute($u); 
            
            $deltheme = $db->prepare('DELETE FROM users_themes WHERE user_id = ?')->execute($u); 
            
            $delttopictags = $db->prepare('DELETE FROM f_topic_tags WHERE user_id = ?')->execute($u); 
            
            $deltrack =  $db->prepare('DELETE FROM f_topic_track WHERE user_id = ?')->execute($u);
            
            $req = $db->prepare("DELETE FROM users WHERE id = ?")->execute($u);
            
            setFlash("L'utilisateur a bien étais supprimé avec toutes ses donnée");

            redirect($router->routeGenerate('user'));
        }
    }
        */
        return $this;
    }


}