<?php

namespace Action;

use App;
use Framework;
use Ausi\SlugGenerator\SlugGenerator;

class AdminTagsAction {

    public  $errors;
	private $app;
	private $cnx;
	private $validator;
	private $router;

    /*
    * @var \Ausi\SlugGenerator\SlugGenerator $generator
    */
    private SlugGenerator $generator;
	private App\Session $session;

	public function __construct()
	{
		$this->app 			= new App\App;
		$this->cnx 			= new App\Database;
		$this->router 		= new Framework\Router;
		$this->session 		= new App\Session;
		$this->validator 	= new App\Validator;
        $this->generator    = new SlugGenerator;
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
     * getTags retourne tous les tags en bdd
     *
     * @return mixed
     */
    public function getTags()
    {
        return $this->cnx->Request("SELECT * FROM f_tags ORDER BY ordre ASC");
    }

    /**
     * getTag retourn 1 tag
     *
     * @return mixed
     */
    public function getTag()
    {
        if(isset($this->router->matchRoute()['params']['editid'])){
            $id = (int) $this->router->matchRoute()['params']['editid'];
            return $this->cnx->Request("SELECT * FROM f_tags WHERE id = ?",[$id],1);
        }
    }

    /**
     * getId vérifie si l'ID exist
     *
     * @return self
     */
    public function getId(): self
    {
        if(isset($this->router->matchRoute()['params']['editid']) && is_null($this->getTag()->id))
        {
            $this->app->setFlash('Un problème est survenue aucun tags avec cet ID','orange');
            $this->app->redirect($this->router->routeGenerate('tags'));
        }
        return $this;
    }
    
    /**
     * addTag Ajoute un tag
     *
     * @return self
     */
    public function addTag(): self
    {
        if(isset($_POST['tagAdd']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $name = strip_tags(trim($_POST['name']));
            $slug = $this->generator->generate($name);
            $ordre = (int) trim(filter_var($_POST['ordre'],FILTER_SANITIZE_NUMBER_INT));
            $this->validator->validSlug($name,'tag')
                            ->validNumbers($ordre,'ordre')
                            ->betweenLength($name, 3, 15,'tag');
            if($this->validator->isValid()){
                $this->cnx->Request("INSERT INTO f_tags(name, slug, ordre) VALUE (?,?,?)",[$name ,$slug,$ordre]);
                $this->app->setFlash('Votre tag a bien été ajouter');
                $this->app->redirect($this->router->routeGenerate('tags'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }

    /**
     * editTags edite un tag
     *
     * @return self
     */
    public function editTags(): self
    {
        if(isset($_POST['tagEdit']))
        {
            $this->validator->methodPostValid('POST');
            $this->session->checkCsrf();
            $name = strip_tags(trim($_POST['name']));
            $slug = $this->generator->generate($name);
            $ordre = (int) trim(filter_var($_POST['ordre'],FILTER_SANITIZE_NUMBER_INT));
            $this->validator->validSlug($name,'tag')
                            ->validNumbers($ordre,'ordre')
                            ->betweenLength($name, 3, 15,'tag');
            if($this->validator->isValid()){
                $id = (int) $this->router->matchRoute()['params']['editid'];
                $this->cnx->Request("UPDATE f_tags SET name = ?, slug = ?, ordre = ? WHERE id = ?",[$name,$slug,$ordre,$id]);
                $this->app->setFlash('Votre tag a bien été modifier');
                $this->app->redirect($this->router->routeGenerate('tags'));
            }
            $this->errors = $this->validator->getErrors();
        }
        return $this;
    }

}