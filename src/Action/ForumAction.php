<?php 

namespace Action;

use App;
use Framework;

class ForumAction{

	private $app;
	private $cnx;
	private $router;
	private $offset;

	public function __construct($offset = null)
	{
		$this->app 			= new App\App;
		$this->cnx 			= new App\Database;
		$this->router 		= new Framework\Router;
		$this->offset 		= $offset;
	}

	/**
	 * Ofset
	 *
	 * @return float
	 */
	public function Offset()
	{
		return	$this->offset;
	}


	/**
	 * getForumDataForAuthenticatedUser si connecté
	 *
	 * @return void
	 */
	private function getForumDataForAuthenticatedUser()
	{
		$userid = (int)$_SESSION['auth']->id;
		$sql = "SELECT
					/* Vos colonnes sélectionnées */
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
							et si on a une réponse on passe au dessu du dernier topic
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
				/* Vos jointures */
				LEFT JOIN users ON users.id = f_topics.f_user_id
				LEFT JOIN f_topic_track ON f_topic_track.topic_id = f_topics.id AND f_topic_track.user_id = ?
				GROUP BY f_topics.id
				ORDER BY sticky DESC, Lastdate DESC LIMIT {$this->Offset()}";

		return $this->cnx->Request($sql, [$userid]);
	}

		
	/**
	 * getForumDataForGuest si non connecté
	 *
	 * @return void
	 */
	private function getForumDataForGuest()
	{
		$sql = "SELECT
					/* Vos colonnes sélectionnées */
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
				/* Vos jointures */
				LEFT JOIN users ON users.id = f_topics.f_user_id
				LEFT JOIN f_topic_track ON f_topic_track.topic_id = f_topics.id AND f_topic_track.user_id = f_topics.f_user_id
				GROUP BY f_topics.id
				ORDER BY sticky DESC, Lastdate DESC LIMIT {$this->Offset()}";

		return $this->cnx->Request($sql);
	}
	
	/**
	 * homePageForum 
	 *
	 * @return void
	 */
	public function homePageForum()
	{
		if (isset($_SESSION['auth'])) {
			return $this->getForumDataForAuthenticatedUser();
		} else {
			return $this->getForumDataForGuest();
		}
	}
	
	/**
	 * viewLastReponse la dernière réponse a un topic 
	 *
	 * @param  mixed $id
	 * @return void
	 */
	public function viewLastReponse($id)
	{

		return $this->cnx->Request("SELECT

		f_topics_reponse.id AS idrep,
		f_topics_reponse.f_topic_rep_date,
		f_topics_reponse.f_topic_id,
		f_topics_reponse.f_user_id,
		f_topics_reponse.f_rep_name,
			users.id AS usersrep,
			users.username,
			users.description,
			users.authorization,
			users.avatar,
			users.email,
			users.slug,
			users.userurl

		FROM f_topics_reponse

		LEFT JOIN users ON users.id = f_topics_reponse.f_user_id

		WHERE f_topic_id = ?

		GROUP BY f_topics_reponse.id

		ORDER BY f_topic_rep_date DESC",[intval($id)],1);
	}
	
	/**
	 * Tags affiche les tags lier a un topic
	 *
	 * @param  mixed $id
	 * @return mixed
	 */
	public function Tags($id)
	{
        return $this->cnx->Request("SELECT
        f_topic_tags.topic_id,
        f_topic_tags.tag_id,
            f_tags.id AS tagid,
            f_tags.name,
            f_tags.slug
        FROM f_topic_tags LEFT JOIN f_tags ON f_tags.id = f_topic_tags.tag_id WHERE topic_id = ? ORDER BY ordre",[intval($id)]);
	}
	
	/**
	 * CountRep
	 *
	 * @param  int $id
	 * @return mixed
	 */
	public function CountRep(int $id)
	{
        return $this->cnx->CountObj("SELECT COUNT(id) AS countid FROM f_topics_reponse WHERE f_topic_id = ?",[$id]);
    }

	/**
	 * CounterTag affiche un compteur du total de tags disponible 
	 *
	 * @param  int $id
	 * @return mixed
	 */
	public function CounterTag(int $id)
	{
		return $this->cnx->CountObj("SELECT COUNT(f_tags.id) AS nbid FROM f_topic_tags LEFT JOIN f_tags on f_tags.id = f_topic_tags.tag_id WHERE f_tags.id = ? ",[$id]);
	}

	/**
	 * choicesTagsSelected retourn selected si les tags correponde bien au topic a édité
	 *
	 * @param  mixed $id
	 * @return void
	 */
	public function choicesTagsSelected(int $id)
	{
		foreach ($this->Tags($this->router->matchRoute()['params']['id']) as $tags) {
			if ($id == $tags->tag_id) {
				return ' selected';
			}
		}
		return null;
	}
	
	/**
	 * queryTags affiche la liste des tags principalement pour la navigation et le formulaire Choices
	 *
	 * @return mixed
	 */
	public function queryTags(): mixed
	{
		return $this->cnx->Request("SELECT * FROM f_tags ORDER BY ordre");
	}
	
	/**
	 * viewForumTags
	 *
	 * @param  mixed $id
	 * @return mixed
	 */
	public function viewForumTags()
	{
		//si connecter
		if(isset($_SESSION['auth']))
		{ 
			$id = (int) $this->router->matchRoute()['params']['id'];
			$userid = (int) $_SESSION['auth']->id;

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
				f_topic_tags.topic_id,
				f_topic_tags.tag_id,
					f_tags.id AS tagid,
					f_tags.name,
					f_tags.slug,
					/*
					CASE - si on a un nouveau topic on le met au dessu
					et si on a une réponse on passe au dessu du dernier topic
					*/
					CASE

					WHEN f_topic_date < f_topic_message_date THEN f_topic_message_date

					WHEN f_topic_date > f_topic_message_date THEN f_topic_date

					ELSE f_topic_date

					END AS Lastdate,
					/*
					view not view
					*/
					CASE

					WHEN read_topic < f_topic_date THEN f_topic_date

					WHEN read_topic > f_topic_date THEN read_topic

					END AS read_last

			FROM f_topics

			LEFT JOIN f_topic_tags ON f_topics.id = f_topic_tags.topic_id

			LEFT JOIN f_tags ON f_topic_tags.tag_id = f_tags.id

			LEFT JOIN users ON users.id = f_topics.f_user_id

			LEFT JOIN f_topic_track ON f_topic_track.topic_id = f_topics.id AND f_topic_track.user_id = ?

			WHERE f_tags.id = ?

			GROUP BY f_topics.id

			ORDER BY sticky DESC, Lastdate DESC LIMIT {$this->Offset()}
			",[$userid,$id]);

		}else{ // si non connecter
			$id = (int) $this->router->matchRoute()['params']['id'];
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
				f_topic_tags.topic_id,
				f_topic_tags.tag_id,
					f_tags.id AS tagid,
					f_tags.name,
					f_tags.slug,
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

				LEFT JOIN f_topic_tags ON f_topics.id = f_topic_tags.topic_id

				LEFT JOIN f_tags ON f_topic_tags.tag_id = f_tags.id

				LEFT JOIN users ON users.id = f_topics.f_user_id

				LEFT JOIN f_topic_track ON f_topic_track.topic_id = f_topics.id AND f_topic_track.user_id = f_topics.f_user_id

				WHERE f_tags.id = ?

				GROUP BY f_topics.id

				ORDER BY sticky DESC, Lastdate DESC LIMIT {$this->Offset()}
			",[$id]); 

		}

	}

	public function getViewForumExist()
	{
		$match = $this->router->matchRoute();
		if($this->viewForumTags($match['params']['id']) == null){
			$this->app->setFlash("Il n'y a pas de topic avec cette id",'orange');
			$this->app->redirect($this->router->routeGenerate('forum'));
		}
	}

	public function homePage(int $limit=6)
	{

		return $this->cnx->Request("SELECT

			f_topics.id AS topicid,
			f_topics.f_topic_name,
			f_topics.f_topic_content,
			f_topics.f_topic_date,
				users.id AS usersid,
				users.username,
				users.avatar,
				users.email,
				users.slug AS userslug,
				users.date_inscription,
				users.description

			FROM f_topics

			LEFT JOIN users ON users.id = f_topics.f_user_id

			WHERE sticky = 1

			GROUP BY f_topics.id

			ORDER BY f_topics.f_topic_date DESC LIMIT $limit");
	}

	/**
	 * flag
	 *
	 * @param  int $id
	 * @param  int $limit
	 * @return mixed
	 */
	public function flag(int $id,int $limit = 10, ?string $class = "hot")
	{
		if($id >= $limit):
			return "<div class=".$class." data-toggle=\"tooltip\" data-placement=\"right\" title=\"Sujet brulant\"><i class=\"far fa-folder\"></i></div>";
		else:
			return "<div class=\"postFlag\"><i class=\"far fa-folder\"></i></div>";
		endif;
	}

	/**
	 * new vérifie que $readLast et supérieur a $lastDate
	 *
	 * @param  mixed $readLast
	 * @param  mixed $lastDate
	 * @return mixed
	 */
	public function isNew($readLast,$lastDate,$class = "new")
	{
		if(isset($_SESSION['auth'])):
			if(isset($readLast, $lastDate) && $readLast > $lastDate):
				return null;
			else:
				return "<div class=".$class." data-toggle=\"tooltip\" data-placement=\"right\" title=\"Sujet non lu\"></div>";
			endif;
		endif;
	}
    
    /**
     * renderAvatar
     *
     * @param  mixed $file
     * @param  mixed $class
     * @return mixed
     */
    public function renderAvatar(?string $file, ?string $class = null)
    {
        if(!is_null($file)){
            return "<img class='$class' src='" . $this->router->webroot() . "inc/img/avatars/" . $file . "' draggable='false' alt='' />";
        }else{
            return "<img class='$class' src='" . $this->router->webroot() . "inc/img/avatars/default.png' draggable='false' alt='' />";
        }
    }

}