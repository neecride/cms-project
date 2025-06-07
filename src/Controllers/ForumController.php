<?php

namespace Controllers;

use App\Renderer;
use App\Pagination;
use Action\ForumAction;
use Action\TopicAction;
use Action\AccountAction;

class ForumController extends Renderer
{

    public function home()
    {
        $home = new ForumAction($this->thisPDO(),$this->thisApp(),$this->thisRoute());
        $this->render('home', compact('home'));
    }

    public function forum()
    {
        $pagination = new Pagination(
            $this->thisPDO(),
            $this->thisRoute(),
            'SELECT COUNT(id) FROM f_topics',
            null,
            $this->thisParams()->GetParam(2),
            $this->thisApp()
        );
        $forum = new ForumAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(), $pagination->setOffset());
        $pagination->isExistPage();

        $this->render('forum',compact('forum','pagination'));
    }

    public function viewtopic(int $id)
    {
        $errMode = $this->thisValidator();
        $pagination = new Pagination(
            $this->thisPDO(),
            $this->thisRoute(),
            'SELECT COUNT(id) FROM f_topics_reponse WHERE f_topic_id = ?',
            $id,
            $this->thisParams()->GetParam(2),
            $this->thisApp()
        );
        $forum      = new ForumAction($this->thisPDO(),$this->thisApp(),$this->thisRoute());
        $Response   = (new TopicAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession(),$errMode,$pagination->setOffset()))
                ->postResponses($pagination->PageTotal())
                ->viewNotView()
                ->resolved()
                ->sticky()
                ->nbView()
                ->getTopicExist();
        $pagination->isExistPage();
        
        $this->render('viewtopic',compact('forum','Response','pagination','errMode'));
    }

    public function viewforum(int $id)
    {
        $pagination = new Pagination(
            $this->thisPDO(),
            $this->thisRoute(),
            'SELECT COUNT(f_tags.id) 
                FROM f_topics LEFT JOIN f_topic_tags 
                ON f_topics.id = f_topic_tags.topic_id 
                LEFT JOIN f_tags ON f_topic_tags.tag_id = f_tags.id
                WHERE f_tags.id = ?',
            $id,
            $this->thisParams()->GetParam(2),
            $this->thisApp()
        );
        $viewforum  = new ForumAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$pagination->setOffset());
        $pagination->isExistPage();
        $viewforum->getViewForumExist();
        $this->render('viewforums', compact('viewforum', 'pagination'));
    }

    public function creatTopic()
    {
        $this->thisApp()->isNotConnect();
        $errMode = $this->thisValidator();
        $forum = new ForumAction($this->thisPDO(),$this->thisApp(),$this->thisRoute());
        $topic = (new TopicAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession(),$errMode))->creatTopic();
        $this->render('creattopic',compact('topic','forum','errMode'));
    }

    public function editTopic()
    {
        $this->thisApp()->isNotConnect();
        $errMode = $this->thisValidator();
        $forum = new ForumAction($this->thisPDO(),$this->thisApp(),$this->thisRoute());
        $topic = (new TopicAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession(),$errMode, null))->editTopic();
        $this->render('edittopic',compact('topic','forum','errMode'));
    }

    public function editRep()
    {
        $session = $this->thisSession();
        $this->thisApp()->isNotConnect();
        $errMode = $this->thisValidator();
        $response = (new TopicAction($this->thisPDO(),$this->thisApp(),$this->thisRoute(),$this->thisSession(),$errMode))->editResponse();
        $this->render('editrep', compact('response','errMode'));
    }

}
