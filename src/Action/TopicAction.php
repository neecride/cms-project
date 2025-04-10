<?php

namespace Action;

use Framework;
use App;

Class TopicAction{
	
	public  $errors;
	private $app;
	private $cnx;
	private $router;
	private $session;
	private $validator;
	private $tagsAction;
	private $offet;

	public function __construct($offset = null)
	{
		$this->app 			= new App\App;
		$this->cnx 			= new App\Database;
		$this->router 		= new Framework\Router;
		$this->session 		= new App\Session;
		$this->validator 	= new App\Validator;
		$this->tagsAction   = new TagAction;
		$this->offet 		= $offset;
	}

	/**
	 * Ofset
	 *
	 * @return float
	 */
	public function Offset()
	{
		return	$this->offet;
	}

	public function checkError()
	{
		if(!is_null($this->errors)){
			return "<div class=\"notify notify-rouge\"><div class=\"notify-box-content\"><li class=\"errmode\">". implode("</li><li class=\"errmode\">",$this->errors) ."</li></div></div>";
		}
	}

	/**
	 * firstTopic affiche le premier topic
	 *
	 * @return mixed
	 */
	public function firstTopic()
	{
		$id = (int) $this->router->matchRoute()['params']['id'];
		return $this->cnx->Request("SELECT
			f_topics.id AS topicsid,
			f_topics.f_topic_content,
			f_topics.f_topic_name,
			f_topics.f_user_id,
			f_topics.f_topic_date,
			f_topics.f_topic_vu,
			f_topics.topic_lock,
			f_topics.f_topic_message_date,
			f_topics.sticky,
				users.id AS usersid,
				users.username,
				users.description,
				users.authorization,
				users.avatar,
				users.email,
				users.date_inscription,
				users.slug,
				users.userurl
		FROM f_topics
		
		LEFT JOIN users ON users.id = f_topics.f_user_id

		WHERE f_topics.id = ?

		",[$id],1);
	}

	/**
	 * getEditReponseReq sort les réponse lié a l'id réponse en GET
	 * utile pour afficher la réponse dans les input
	 * @return mixed
	 */
	public function getEditReponseReq()
	{
		$id = (int) $this->router->matchRoute()['params']['id'];
		return $this->cnx->Request("SELECT 
				f_topics_reponse.id AS topicsrep,
				f_topics_reponse.f_topic_reponse,
				f_topics_reponse.f_rep_name,
				f_topics_reponse.f_topic_id,
				f_topics_reponse.f_user_id,
				users.slug,
				users.date_inscription,
				f_topic_name

		FROM f_topics_reponse 

		LEFT JOIN f_topics on f_topics_reponse.f_topic_id = f_topics.id

		LEFT JOIN users ON users.id = f_topics_reponse.f_user_id

		WHERE f_topics_reponse.id = ?",[$id],1);
	}

	/**
     * getId vérifie si l'ID exist
     *
     * @return self
     */
    public function getId(bool $id = null): self
    {
        if(is_null($id))
        {
            $this->app->setFlash('Un problème est survenue aucun sujet avec cet ID','orange');
            $this->app->redirect($this->router->routeGenerate('forum'));
        }
		return $this;
    }
	
	/**
	 * getTopicExist permet de vérifié si un topic existe avec idget
	 *
	 * @return self-
	 */
	public function getTopicExist(): self
	{
		$match = $this->router->matchRoute();
		if($this->firstTopic($match['params']['id']) == null){
			$this->app->setFlash("Il n'y a pas de topic avec cette id",'orange');
			$this->app->redirect($this->router->routeGenerate('forum'));
		}
		return $this;
	}

	/**
	 * viewTopicRep affiche les nouvelles réponses 
	 *
	 * @return mixed
	 */
	public function viewTopicRep()
	{
		$id = (int) $this->router->matchRoute()['params']['id'];
		return $this->cnx->Request("SELECT

			f_topics_reponse.id AS topicsrep,
			f_topics_reponse.f_topic_reponse,
			f_topics_reponse.f_topic_id,
			f_topics_reponse.id AS repid,
			f_topics_reponse.f_user_id,
			f_topics_reponse.f_topic_rep_date AS rep_date,
				users.id AS usersrep,
				users.username,
				users.description,
				users.authorization,
				users.avatar,
				users.email,
				users.date_inscription,
				users.slug

		FROM f_topics_reponse

		LEFT JOIN users ON users.id = f_topics_reponse.f_user_id

		WHERE f_topics_reponse.f_topic_id = ?

		GROUP BY f_topics_reponse.id

		ORDER BY f_topic_rep_date ASC LIMIT {$this->Offset()}",[$id]);
	}
	
	/**
	 * creatTopic permet de créer un topic
	 *
	 * @return self
	 */
	public function creatTopic(): self
	{
		$this->app->isNotConnect('forum');
		if(isset($_POST['topics']))
		{
			$this->validator->methodPostValid('POST');
			$this->session->checkCsrf();
			$topic_name = strip_tags(trim($_POST['f_topic_name']));
			$content = strip_tags(trim($_POST['f_topic_content']));
			$userid = (int) $_SESSION['auth']->id;
			$tags = isset($_POST['tags']) ? $_POST['tags'] : ['empty'] ; /* entre 1 et 4 tags requis Choices */
			$sticky = isset($_POST['sticky']) ? (int) trim($_POST['sticky']) : 0 ;
			$this->validator->minLength($content, 100,'topic contenu')
							->itemsCountArray($tags, 4 ,'tags')
							->optionValidation($sticky,'1|0','sticky')
							->betweenLength($topic_name, 6,50,'topic name')
							->validTtitle($topic_name, 'topic name')
							->postExistTags($tags,'tags');
			if($this->validator->isValid())
			{
				$this->cnx->Request("INSERT 
					INTO f_topics SET f_topic_name = ?, f_user_id = ?, f_topic_content = ?, sticky = ?, f_topic_date = NOW()",[$topic_name, $userid ,$content,$sticky]);
				$lastid = $this->cnx->lastInsertId();
				sleep(1);
				$this->tagsAction->insertTagsOnNewTopic($tags,$lastid);
				$this->app->setFlash('Votre topic a bien étais poster');
				$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $lastid.'#topic-'.$lastid]));
			}
			$this->errors = $this->validator->getErrors();
		}
		return $this;
	}

	/**
	 * editTopic permet d'édité un topic
	 *
	 * @return self
	 */
	public function editTopic(): self
	{
		$this->app->isNotConnect('forum');
		$this->getId($this->firstTopic()->topicsid);
		//vérification des autorisation d'édition
		$this->validator->validUserEdit($this->firstTopic()->f_user_id,$this->firstTopic()->slug);
			if(isset($_POST['topics']))
			{
				$this->validator->methodPostValid('POST');
				$this->session->checkCsrf();
				$topic_name = strip_tags(trim($_POST['f_topic_name']));
				$content = strip_tags(trim($_POST['f_topic_content']));
				$getID = (int) $this->router->matchRoute()['params']['id'];
				$userID = (int) $this->getEditReponseReq()->f_user_id;
 				$tags = isset($_POST['tags']) ? $_POST['tags'] : ['empty'] ; /* entre 1 et 4 tags requis Choices */
				$this->validator->minLength($content,100,'response content')
								->itemsCountArray($tags,4,'tags')
								->betweenLength($topic_name,6,50,'topic name')
								->validTtitle($topic_name,'topic name')
								->postExistTags($tags,'tags');
				if($this->validator->isValid())
				{
					die('ok');
					$this->tagsAction->updateTopicTags($getID,$tags);
					$this->cnx->Request("UPDATE f_topics SET f_topic_name = ?, f_topic_content = ? WHERE id = ?",[$topic_name,$content, $getID]);
					$this->app->setFlash('Votre méssage a bien été modifier');
					if(isset($_GET['page'])){
						$page = (int) $_GET['page'];
						$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID . '?page='.$page.'#topic-'.$getID]));
					}
					$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID.'#topic-'.$getID]));
				}
				$this->errors = $this->validator->getErrors();
			}
		return $this;
	}
	
    /**
     * postResponses post une nouvelle réponse et met a jour les dates topic et topic track 
     *
     * @param  int $page
     * @return self
     */
    public function postResponses(int $page):self
	{
		if(isset($_POST['topics'])){
			$this->app->isNotConnect('forum');
			$this->validator->methodPostValid('POST');
			$this->session->checkCsrf();
			$match = $this->router->matchRoute();
			$id = (int) $match['params']['id'];
			$userid = (int) $_SESSION['auth']->id;
			$content = strip_tags(trim($_POST['f_topic_content']));
			$this->validator->minLength($content, 100,'response content');
			if($this->validator->isValid())
			{
				$this->cnx->Request("INSERT INTO f_topics_reponse SET f_user_id = ?, f_topic_reponse = ?, f_topic_id = ?, f_topic_rep_date = NOW()", [$userid,$content, $id]);
				$lastid = $this->cnx->LastInsertID(); //pour la redirection
				//on met a jour la date du premier topic pour mettre en avant 
				$this->cnx->Request("UPDATE f_topics SET f_topic_message_date = NOW() WHERE id = ?",[$id]);
				//on update topic track en fonction de l'utilisateur
				sleep(1);
				//tester une redirection vers la page en court et redirigé dessus si une nouvelle page se créer
				$this->app->setFlash('Votre réponse a bien été poster');
				if($page > 1){
					$this->app->redirect($this->router->routeGenerate('viewtopic',['id' => $match['params']['id'] .'?page='.$page.'#rep-' . $lastid]));
				}
				$this->app->redirect($this->router->routeGenerate('viewtopic',['id' => $match['params']['id'] .'#rep-' . $lastid]));
			}
			$this->errors = $this->validator->getErrors();
		}
		return $this;
	}

	/**
	 * trackPostTopic 
	 *
	 * @param  mixed $userID
	 * @param  mixed $id
	 * @return self
	 */
	private function trackPost(int $userID, int $id): self
	{
		$views = $this->cnx->Request('SELECT * FROM f_topic_track WHERE user_id = ? AND topic_id = ?',[$userID,$id],1);
		//on update track en fonction de l'utilisateur
		if(!is_null($views)){ 
			$this->cnx->Request("UPDATE f_topic_track SET read_topic = NOW() WHERE user_id = ? AND topic_id = ?",[$userID,$id]);
		}else{
			$this->cnx->Request("INSERT INTO f_topic_track SET read_topic = NOW(), user_id = ?, topic_id = ?",[$userID,$id]);
		}
		return $this;
	}

	/**
	 * editResponse permet d'édité une réponse
	 *
	 * @return self
	 */
	public function editResponse(): self
	{
		$this->app->isNotConnect('forum');
		$this->getId($this->getEditReponseReq()->topicsrep);
		//vérification des autorisation d'édition
		$this->validator->validUserEdit($this->getEditReponseReq()->f_user_id,$this->getEditReponseReq()->slug,$this->getEditReponseReq()->f_topic_id);
			if(isset($_POST['topics']))
			{
				$this->validator->methodPostValid('POST');
				$this->session->checkCsrf();
				$content = strip_tags(trim($_POST['f_topic_content']));
				$getid = (int) $this->router->matchRoute()['params']['id'];
				$postId = (int) $this->getEditReponseReq()->f_topic_id;
				$name = strip_tags($this->getEditReponseReq()->f_topic_name);
				$this->validator->minLength($content, 100,'response content');
				if($this->validator->isValid())
				{
					$this->cnx->Request("UPDATE f_topics_reponse SET f_topic_reponse = ?, f_rep_name = ? WHERE id = ?",[$content ,$name,$getid]);
					$this->app->setFlash('Votre méssage a bien été modifier');
					if(isset($_GET['page'])){
						$page = (int) $_GET['page'];
						$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $postId . '?page='.$page.'#rep-'.$getid]));
					}
					$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $postId .'#rep-'. $getid]));
				}
				$this->errors = $this->validator->getErrors();
			}
			return $this;
	}


	/**
	 * resolved met le topic en résolue 
	 *
	 * @return self
	 */
	public function resolved(): self
	{
		if(isset($_SESSION['auth']) && isset($this->router->matchRoute()['params']['lock'])){
			$this->validator->updateTopicResolutionStatus($_SESSION['auth']->id,$this->firstTopic()->f_user_id);
			$this->validator->methodPostValid('GET');
			$this->session->checkCsrf();
			$lock = (int) $this->router->matchRoute()['params']['lock'];
			$getID = (int) $this->router->matchRoute()['params']['id'];
			if(!empty($lock) && !preg_match("#^(0|1)$#",$lock)){
				$this->app->setFlash('Seulement 0 ou 1 est possible','orange');
				$this->app->redirect($this->router->routeGenerate('viewtopic',['id' => $this->router->matchRoute()['params']['id']]));
			}
			if($lock == 1){
				$info = "résolu";
				$type = "success";
			}elseif($lock == 0){
				$info = "ouvert";
				$type = 'info';
			}
			$this->cnx->Request("UPDATE f_topics SET topic_lock = ? WHERE id = ?",[$lock,$getID]);
			$this->app->setFlash("Le topic a bien été $info",$type);
			if(isset($_GET['page'])){
				$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID.'?page='.$_GET['page']]));
			}
			$this->app->redirect($this->router->routeGenerate('viewtopic',['id' => $getID]));
		}
		return $this;
	}
	
	/**
	 * sticky met le topic en sticky
	 *
	 * @return self
	 */
	public function sticky(): self 
	{
		if(isset($_SESSION['auth']) && isset($this->router->matchRoute()['params']['sticky']) && !empty($this->router->matchRoute()['params']['sticky'] >= 0))
		{
			$this->validator->updateTopicStickyStatus($this->firstTopic()->f_user_id,$this->firstTopic()->authorization);
			$this->validator->methodPostValid('GET');
			$this->session->checkCsrf();
			$sticky = (int) $this->router->matchRoute()['params']['sticky'];
			$getID = (int) $this->router->matchRoute()['params']['id'];
			if(!preg_match("#^(0|1)$#",$sticky)){
				$this->app->setFlash("Ce champ sticky doit être un nombre 0 ou 1",'rouge');
				if(isset($_GET['page'])){
					$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID.'?page='.$_GET['page']]));
				}
				$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID]));
			}
			$this->cnx->Request("UPDATE f_topics SET sticky = ? WHERE id = ?",[$sticky, $getID]);
			if($sticky === 1){
				$this->app->setFlash('Le post a bien étais mis en sticky');
				if(isset($_GET['page'])){
					$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID.'?page='.$_GET['page']]));
				}
				$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID]));
			}elseif($sticky === 0){
				$this->app->setFlash('Le post a bien étais retiré des sticky','info');
				if(isset($_GET['page'])){
					$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID.'?page='.$_GET['page']]));
				}
				$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID]));
			}
		}
		return $this;
	}
	
	/**
	 * viewNotView met a jour les tracking si on a pas déjà vu le topic ou la réponse
	 *
	 * @return self
	 */
	public function viewNotView(): self
	{
		if(isset($_SESSION['auth']) && isset($this->router->matchRoute()['params']['id']))
		{
			$userid = (int) $_SESSION['auth']->id;
			$get = (int) $this->router->matchRoute()['params']['id'];
			$views = $this->cnx->Request('SELECT * FROM f_topic_track WHERE user_id = ? AND topic_id = ?',[$userid,$get],1);
			if($views != null){
				if($this->firstTopic()->f_topic_message_date >= $views->read_topic or $this->firstTopic()->f_topic_date >= $views->read_topic){
					$this->cnx->Request("UPDATE f_topic_track SET read_topic = NOW() WHERE user_id = ? AND topic_id = ?",[$userid,$get]);
				}
			}else{
				$this->cnx->Request("INSERT INTO f_topic_track SET read_topic = NOW(), user_id = ?, topic_id = ?",[$userid,$get]);
			}
		}
		return $this;
	}

	
	/**
	 * editBtn seul l'auteur peut voir son bouton
	 * si on est modo rang 2 on peut edité le topic d'un membre rang 1 sauf celui d'un admin rang 3
	 *
	 * @param  int $userAthor
	 * @param  int $auth
	 * @param  int $topicID
	 * @return void
	 */
	public function editBtnTopic(string $class)
	{
		if(isset($_SESSION['auth'])):
			if($_SESSION['auth']->id === $this->firstTopic()->usersid
			or in_array($_SESSION["auth"]->authorization, [2,3]) 
			&& !in_array($this->firstTopic()->authorization, [3])):
			$page = isset($_GET['page']) ? '?page='.$_GET['page'] : null ;
			$link = $this->router->routeGenerate('edittopic', ['id' => $this->firstTopic()->topicsid . $page]);
			return "<span data-toggle=\"tooltip\" data-placement=\"top\" title=\"Edit\"><a class=\"$class\" href=\"$link\"><i class=\"far fa-edit\"></i></a></span>";
			endif;
		endif;
	}

	/**
	 * editBtn seul l'auteur peut voir son bouton
	 * si on est modo rang 2 on peut edité le topic d'un membre rang 1 sauf celui d'un admin rang 3
	 *
	 * @param  int $userAthor
	 * @param  int $auth
	 * @param  int $responseID
	 * @return void
	 */
	public function editBtnResponse(int $userAthor, int $auth,int $responseID,string $class)
	{
		if(isset($_SESSION['auth'])):
			if($_SESSION['auth']->id === $userAthor 
			or in_array($_SESSION["auth"]->authorization, [2,3]) 
			&& !in_array($auth, [3])):
			$page = isset($_GET['page']) ? '?page='.$_GET['page'] : null ;
			$link = $this->router->routeGenerate('editrep', ['id' => $responseID . $page]);
			return "<span data-toggle=\"tooltip\" data-placement=\"top\" title=\"Edit\"><a class=\"$class\" href=\"$link\"><i class=\"far fa-edit\"></i></a></span>"; 
			endif;
		endif;
	}
	
	/**
	 * resolvedStatusBtn
	 *
	 * @return void
	 */
	public function resolvedStatusBtn(string $class)
	{
		if(isset($_SESSION['auth'])):
			if($_SESSION['auth']->id === $this->firstTopic()->usersid or in_array($_SESSION["auth"]->authorization, [2,3]) && !in_array($this->firstTopic()->authorization, [3])):
			$link0 = $this->router->routeGenerate('lock',['id' => $this->firstTopic()->topicsid, 'lock' => 0, 'getcsrf' =>  $this->session->csrf()]);
			$link1 = $this->router->routeGenerate('lock',['id' => $this->firstTopic()->topicsid, 'lock' => 1, 'getcsrf' =>  $this->session->csrf()]);	
				if ($this->firstTopic()->topic_lock === 0):
					return "<span data-toggle=\"tooltip\" data-placement=\"top\" title=\"mettre en résolu\"><a class=\"$class\" href=\"$link1\"><i class=\"fas fa-lock\"></i></a></span>";
				elseif ($this->firstTopic()->topic_lock === 1):
					return "<span data-toggle=\"tooltip\" data-placement=\"top\" title=\"topic résolu\"><a class=\"$class\" href=\"$link0\"><i class=\"fas fa-check-circle\"></i></a></span>";
				endif;
			endif;
		endif;
	}
	
	/**
	 * stickyStatusBtn seul les modo admin peuvent mettre sticky sauf un modo sur le topic admin 
	 * mais l'admin peut tout faire
	 * 
	 * @param  int $userAthor
	 * @param  int $auth
	 * @param  int $topicID
	 * @param  int $status
	 * @return void
	 */
	public function stickyStatusBtn(string $class)
	{
		if(isset($_SESSION['auth'])):
			$link0 = $this->router->routeGenerate('sticky',['id' => $this->firstTopic()->topicsid, 'sticky' => 0, 'getcsrf' =>  $this->session->csrf()]);
			$link1 = $this->router->routeGenerate('sticky',['id' => $this->firstTopic()->topicsid, 'sticky' => 1, 'getcsrf' =>  $this->session->csrf()]);
			if($_SESSION["auth"]->authorization > 1):
				if ($_SESSION['auth']->id == $this->firstTopic()->usersid || ($_SESSION["auth"]->authorization === 2 && $this->firstTopic()->authorization != 3)):
					if ($this->firstTopic()->sticky === 0):
						return "<span data-toggle=\"tooltip\" data-placement=\"top\" title=\"mettre en sticky\"><a class=\"$class\" href=\"$link1\"><i class=\"fas fa-thumbtack\"></i></a></span>";
					elseif ($this->firstTopic()->sticky === 1):
						return "<span data-toggle=\"tooltip\" data-placement=\"top\" title=\"retiré des sticky\"><a class=\"$class\" href=\"$link0\"><i class=\"fas fa-ban\"></i></a></span>";
					endif;
				elseif ($_SESSION["auth"]->authorization === 3 && $this->firstTopic()->authorization != 3):
					if ($this->firstTopic()->sticky === 0):
						return "<span data-toggle=\"tooltip\" data-placement=\"top\" title=\"mettre en sticky\"><a class=\"$class\" href=\"$link1\"><i class=\"fas fa-thumbtack\"></i></a></span>";
					elseif ($this->firstTopic()->sticky === 1):
						return "<span data-toggle=\"tooltip\" data-placement=\"top\" title=\"retiré des sticky\"><a class=\"$class\" href=\"$link0\"><i class=\"fas fa-ban\"></i></a></span>";
					endif;
				endif;
			endif;
		endif;
	}

	public function nbView(): self
	{
		if(isset($_SESSION['auth']) && isset($this->router->matchRoute()['params']['id'])){
			$vu = [intval($this->router->matchRoute()['params']['id'])];
			$this->cnx->Request("UPDATE f_topics SET f_topic_vu = f_topic_vu + 1 WHERE id = ?",$vu); 
		}
		return $this;
	}

}