<?php

namespace App;

use Framework;

class Validator{

	/**
	 * errors
	 *
	 * @var string[]
	 */
	private $errors = [];

	private $app;
	private $router;
	private $parameters;
	private $cnx;

	public function __construct()
	{
		$this->app = new App;
		$this->router = new Framework\Router;
		$this->parameters = new Parameters;
		$this->cnx = new Database;
	}

	/**
	 * methodPostValid vérifie si on est bien dans une method POST|GET
	 *
	 * @param  mixed $key
	 * @return self
	 */
	public function methodPostValid(string $key): self
	{
		$method = mb_strtoupper($_SERVER['REQUEST_METHOD'] ?? $key);
		if ($method !== $key) {
			$this->app->setFlash("Le formulaire n'est pas un formulaire $key",'rouge');
			$this->app->redirect($this->router->routeGenerate('error'));
		}
		return $this;
	}

	/**
	 * required vérifie que les champ sont présent dans le tableau
	 *
	 * @param  mixed $keys
	 * @return self
	 */
	public function required(string $key, ?string $name = null): self
	{
		if(is_null($key))
		{
			$this->errors[] = "Tout les champs $name sont requis";
		}
		return $this;
	}

	/**
	 * isDiffent on vérifie si les valeurs sont différente
	 *
	 * @param  mixed $key
	 * @param  mixed $field
	 * @return self
	 */
	public function isDifferent(string $key,string $field, ?string $name = null): self 
	{
		if($key != $field)
		{
			$this->errors[] = "Les champ $name sont différent";
		}
		return $this;
	} 
	
	/**
	 * fileExist vérifie si le fichier exist bien
	 *
	 * @param  mixed $key
	 * @param  mixed $path
	 * @return self
	 */
	public function fileExist(string $key, string $path): self
	{
		if(!file_exists($path . $key))
		{
			$this->errors[] = "Le fichier n'existe pas";
		}
		return $this;
	}
	
	/**
	 * existFolderDestination vérifie que le dossier de destination exist
	 *
	 * @param  mixed $save_name
	 * @param  mixed $path
	 * @return self
	 */
	public function existFolderDestination($save_name,$path): self
	{
		if (is_dir($path)) {
			chmod($path.$save_name, 0644);
		}
		return $this;
	}

	/**
	 * extensionAllowed vérifie si le fichier est bien valid
	 *
	 * @param  mixed $key
	 * @param  mixed $ext_autorize
	 * @return self
	 */
	public function extensionAllowed($extention, $type ,$ext_autorize): self
	{
		//$extention = pathinfo($key, PATHINFO_EXTENSION);
		if(!array_key_exists($extention, $ext_autorize) || !in_array($type, $ext_autorize))
		{
			$this->errors[] = "le fichier n'est pas valide PNG/JPG uniquement";
		}
		return $this;
	}

	/**
	 * fileProblems s'assure qu'il n'y a pas eu de problème avec le fichier
	 *
	 * @param  mixed $key
	 * @return self
	 */
	public function fileProblems(mixed $key): self
	{
		if(!is_uploaded_file($key))
		{
			$this->app->setFlash("Un problème a eu lieu lors de l'upload",'orange');
			$this->app->redirect($this->router->routeGenerate('account-edit'));
		}
		return $this;
	}
	
	/**
	 * sizeFileUpload vérifie la taille du fichier
	 *
	 * @param  mixed $key
	 * @param  mixed $max_size
	 * @return self
	 */
	public function sizeFileUpload(string $key,int $max_size) :self
	{
		if($key > $max_size)
		{
			$this->errors[] = "le fichier est trop volumineux 40ko max";
		}
		return $this;
	}


	/**
	 * isReqExist vérifie si une valeur existe déjà en base de donnée
	 *
	 * @param  mixed $key
	 * @return self
	 */
	public function isReqExist($key, ?string $name = null): self
	{
		if($key)
		{
			$this->errors[] = "Le champ $name est déjà utiliser";
		}
		return $this;
	}
	
	/**
	 * queryExistingTags vérifie que les tags Choices existe bien en bdd
	 *
	 * @param  mixed $keys
	 * @param  mixed $name
	 * @return self
	 */
	public function postExistTags($keys,$name): self
	{
		// Préparer la requête SQL
		$stmt = $this->cnx->Request("SELECT * FROM f_tags WHERE id IN (".implode(',', array_map('intval', $keys)).")");
		// Vérifier si des résultats ont été trouvés
		if (count($stmt) == 0) {
			$this->errors[] = "Les éléments $name n'existe pas";
		}
		return $this;
	}

	/**
	 * uniqTags vérifie si un tags est uniq
	 *
	 * @param  mixed $key
	 * @param  mixed $name
	 * @return self
	 */
	public function uniqTags($key, $name): self
	{
		if(!array_unique($key))
		{
			$this->errors[] = "Les $name doivent être unique";
		}
		return $this;
	}

	/**
	 * itemsCount vérifie si un tags est bien un int et qu'il y en a bien min 1 et max 4
	 *
	 * @param  mixed $key
	 * @param  int $max
	 * @param  string $name
	 * @return self
	 */
	public function itemsCountArray(array $key, int $max ,?string $name = null): self
	{
		if($key[0] === 'empty'){
			$this->errors[] = "Vous devez choisir au moin 1 $name";
		}
		if(!empty($key) && count($key) > 4){
			$this->errors[] = "Vous ne pouvez choisir que $max $name";
		}
		if (count($key) !== count(array_unique($key, SORT_NUMERIC))) 
		{
			$this->errors[] = "Les $name doivent être uniques";
		}
		foreach (array_slice($key , 0, 1) as $tag) 
		{
            // Vérifier que le tag est un entier
            if (!filter_var($tag, FILTER_VALIDATE_INT)) 
            {
				$this->errors[] = "Les $name doivent être que des int";
			}
		}
		return $this;
	}

	/**
	 * postOk vérifie si un utlisateur a le de posté un topic ou une réponse etc... en fonction de $_SESSION['auth']->authorization
	 * trois niveau par defaut 1|2|3 respectivement membre modo et admin
	 * @param  mixed $key
	 * @param  mixed $lvl
	 * @return self
	 */
	public function postOk(int $key, array $lvl = [1,2,3]): self
	{
		if(!in_array($key, $lvl))
		{
			$this->app->setFlash('Vous devez avoir le bon rang pour poster');
			$this->app->redirect($this->router->routeGenerate('home'));
		}
		return $this;
	}
	
	/**
	 * minLength valide le contenue du site limite de caractères
	 *
	 * @param  mixed $key
	 * @param  mixed $limit
	 * @return self
	 */
	public function minLength(string $key, int $limit, ?string $name = null): self
	{
		if(grapheme_strlen($key) <= $limit)
		{
			$this->errors[] = "Votre champ $name dois avoir au moins $limit caractères";
		}
		return $this;
	}
	
	/**
	 * maxLength 
	 *
	 * @param  mixed $key
	 * @param  mixed $max
	 * @return self
	 */
	public function maxLength(string $key,int $max, ?string $name = null): self
	{
		if(grapheme_strlen($key) >= $max)
		{
			$this->errors[] = "Le champ $name dois avoir max $max caractères";
		}
		return $this;
	}
	
	/**
	 * betweenLength
	 *
	 * @param  mixed $key
	 * @param  mixed $min
	 * @param  mixed $max
	 * @return self
	 */
	public function betweenLength(string $key, int $min, int $max, ?string $name = null): self
	{
		if(grapheme_strlen($key) <= $min || grapheme_strlen($key) >= $max)
		{
			$this->errors[] = "Le champ $name dois avoir min $min et max $max caractères";
		}
		return $this;
	}


	/**
	 * validName check si le username est bien valid
	 *
	 * @param  mixed $key
	 * @return self
	 */
	public function validName(string $key, ?string $name = null): self
	{
		if(!preg_match('/^[a-zA-Z0-9ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåæçèéêëìíîïñòóôõöøùúûüý_-]{3,20}$/', $key))
		{
			$this->errors[] = "Le champ $name doit contenir 3|20 caractères alphanuméric (accent compris) tirets (-) et underscores (_) pas d'espaces.";
		}
        return $this;
	}
	
	/**
	 * validNumbers vérifie su un champ est bien un int
	 *
	 * @param int $key
	 * @return self
	 */
	public function validNumbers(int $key, ?string $name = null): self
	{
		if(filter_var($key, FILTER_VALIDATE_INT) === false)
		{
			$this->errors[] = "Le champ $name ne dois contenir que des int";
		}
		return $this;
	}
	
	/**
	 * validUserEdit vérification des autorisation d'édition
	 *
	 * @param  int $authId
	 * @param  int $userRepId
	 * @param  int $authorization
	 * @param  string $slug
	 * @param  int $topicid
	 * @return void
	 */
	public function validUserEdit(int $userRepId,string $slug, ?int $topicid = null)
	{
		$getID = (int) $this->router->matchRoute()['params']['id'];
		if ($_SESSION['auth']->id == $userRepId || ($_SESSION["auth"]->authorization == 2 && $slug != 'admin')) {
			// L'utilisateur est l'auteur ou un modérateur qui n'essaye pas d'éditer le topic de l'administrateur
			return true;
		} elseif ($_SESSION["auth"]->authorization == 3 && $slug != 'admin') {
			// L'utilisateur est un administrateur qui n'essaye pas d'éditer son propre topic
			return true;
		} else {
			// L'utilisateur n'a pas les autorisations nécessaires pour éditer ce topic
			$this->app->setFlash("Vous n'avez pas le bon rang pour editer ce post ou ce post n'est pas le votre",'orange');
			// si topic id est définie ça veut dire que c'est une réponse et on redirige vers le bon message
			if($topicid != null){
				if(!is_null($_GET['page'])){
					$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $topicid . '?page='. $_GET['page'] .'#rep-'. $getID]));
				}
				$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $topicid .'#rep-'. $getID]));
			}
			if(!is_null($_GET['page'])){
				$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID. '?page='. $_GET['page'] .'#topic-'.$getID]));
			}
			$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID.'#topic-'.$getID]));
		}
	}
	
	/**
	 * updateTopicStatus vérifie le rang de ceux qui peuvent mettre un sticky
	 *
	 * @param  int $authId
	 * @param  int $userID
	 * @param  int $authorization
	 * @param  string $slug
	 * @return void
	 */
	public function updateTopicStickyStatus(int $userID , string $auth)
	{
		$getID = (int) $this->router->matchRoute()['params']['id'];
		if($_SESSION["auth"]->authorization > 1){
			if ($_SESSION['auth']->id == $userID || ($_SESSION["auth"]->authorization == 2 && $auth != 3)) {
				// L'utilisateur est l'auteur ou un modérateur qui n'essaye pas d'éditer le sticky de l'administrateur
				return true;
			}elseif ($_SESSION["auth"]->authorization == 3 && $auth != 3) {
				// L'utilisateur est un administrateur qui n'essaye pas d'éditer son propre sticky
				return true;
			}else {
				// L'utilisateur n'a pas les autorisations nécessaires pour éditer ce sticky
				$this->app->setFlash("Vous ne pouvez pas mettre ou retiré un sticky sur le topic d'un admin",'orange');
				// si topic id est définie ça veut dire que c'est une réponse et on redirige vers le bon message
				if(!is_null($_GET['page'])){
					$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID. '?page='. $_GET['page'] .'#topic-'.$getID]));
				}
				$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID.'#topic-'.$getID]));
			}
		}else{
			$this->app->setFlash("Il faut être admin ou modo pour mettre un sticky",'orange');
			$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID.'#topic-'.$getID]));
		}
	}
	
	/**
	 * updateTopicResolutionStatus
	 *
	 * @param  mixed $AuthUserID
	 * @param  mixed $authorID
	 * @return void
	 */
	public function updateTopicResolutionStatus(int $AuthUserID,int $authorID)
	{
		if (in_array($_SESSION["auth"]->authorization, [3])) {
			// L'admin peut tout modifier
			return true;
		} else {
			// Vérifier si l'utilisateur est l'auteur du topic
			$isTopicAuthor = ($AuthUserID === $authorID);
			// Vérifier si l'utilisateur est un modérateur
			$isModerator = in_array($_SESSION["auth"]->authorization, [2]);
			if ($isTopicAuthor || $isModerator) {
				// L'auteur du topic et les modérateurs peuvent marquer un topic comme résolu
				//sauf celui de l'admin
				return true;
			} else {
				// Les autres utilisateurs n'ont pas le droit de marquer un topic comme résolu
				$getID = (int) $this->router->matchRoute()['params']['id'];
				$this->app->setFlash("Vous n'avez pas le bon rang pour editer ce post ou ce post n'est pas le votre",'orange');
				if(!is_null($_GET['page'])){
					$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID. '?page='. $_GET['page'] .'#topic-'.$getID]));
				}
				$this->app->redirect($this->router->routeGenerate('viewtopic', ['id' => $getID.'#topic-'.$getID]));
			}
		}
	}

	/**
	 * validSlug vérifie si le slug est valide
	 *
	 * @param  mixed $key
	 * @return self
	 */
	public function validSlug(string $key):self
	{
		if(!preg_match('/^[a-z0-9\-]{3,20}$/', $key))
		{
			$this->errors[] = "Le slug doit contenir 3|20 caractères minuscul alphanuméric et des tirets (-)";
		}
        return $this;
	}

	/**
	 * validTtitle validation pour les tritre
	 *
	 * @param  mixed $key
	 * @return self
	 */
	public function validTtitle(string $key, ?string $name = null): self
	{	
		if(!preg_match('/^[a-zA-Z0-9ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåæçèéêëìíîïñòóôõöøùúûüý\-\'\,.!?\s]{5,50}$/',$key))
		{
			$this->errors[] = "Le $name dois contenir 5|50 caractère (accent compris et espaces) et des (,|.|!|?)";
		}
		return $this;
	}

	
	/**
	 * optionValidation validation
	 * exemple regex 1|2|3
	 * @param  mixed $key
	 * @return self
	 */
	public function optionValidation(string $key,string $regex, ?string $name = null): self
	{
		if(!preg_match("#^($regex)$#",$key))
		{
			$this->errors[] = "Le champ $name n'est pas valide seulement $regex sont requis";
		}
		return $this;
	}

	/**
	 * validThemeName le nom de theme
	 *
	 * @param  mixed $key
	 * @return self
	 */
	public function validThemeName(string $key, ?string $name = null): self
	{
		if(!preg_match('#^[a-z]{4,10}$#',$key)){
			$this->errors[] = "Le champ $name n'est pas valide [a-z]{4,10} caractère minuscule uniquement";
		}
		return $this;
	}
	
	/**
	 * validEmail filtre et valide les emails
	 *
	 * @param  mixed $key
	 * @return self
	 */
	public function validEmail(string $key, ?string $name = null): self
	{
		if(filter_var($key, FILTER_VALIDATE_EMAIL) === false)
		{
			$this->errors[] = "Le champ $name n'est pas valide";
		}
		return $this;
	}

	/**
	 * validMdp valid les mots de pass
	 *
	 * @param  mixed $key
	 * @return self
	 */
	public function validMdp(string $key): self
	{
		if(!preg_match('/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$ %^&*-]).{8,50}$/', $key)) 
		{
			$this->errors[] = "Le mots de pass doit être composé de 8|50 caractères, de minuscules, une majuscule de chiffres et d’au moins un caractère spécial";
		}
		return $this;
	}

	/**
	 * notEmpty vérifie que le champ n'est pas vide
	 *
	 * @param  mixed $keys
	 * @return self
	 */
	public function notEmpty(string ...$keys): self
	{
		foreach($keys as $key){
			if(is_null($key) || empty($key))
			{
				$this->errors[] = "Les champs ne doivent pas être vide";
			}
		}
		return $this;
	}
	
	/**
	 * getErrors récupère les erreurs
	 *
	 * @return array
	 */
	public function getErrors(): array
	{
		return $this->errors;
	}

	/**
	 * isValid vérifie si il n'y a pas d'erreur de validation
	 *
	 * @return bool
	 */
	public function isValid(): bool
	{
		return empty($this->errors);
	}

} 