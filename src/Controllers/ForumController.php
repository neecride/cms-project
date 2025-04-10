<?php

namespace Controllers;

use Action\ForumAction;
use Action\TopicAction;
use App\Pagination;
use App\Renderer;

class ForumController extends Renderer
{

    public function home()
    {
        $home = new ForumAction();
        $this->render('home', compact('home'));
    }

    public function forum()
    {
        $perpage = $this->Params()->GetParam(2);
        $pagination = new Pagination(
            $this->ThisRoute(),
            'SELECT COUNT(id) FROM f_topics',
            null,
            $perpage,
            $this->app()
        );
        $forum      = new ForumAction($pagination->setOffset());
        $pagination->isExistPage();

        $this->render('forum',compact('forum', 'pagination'));
    }

    public function viewtopic(int $id)
    {
        $perpage = $this->Params()->GetParam(2);
        $pagination = new Pagination(
            $this->ThisRoute(),
            'SELECT COUNT(id) FROM f_topics_reponse WHERE f_topic_id = ?',
            $id,
            $perpage,
            $this->app()
        );
        $forum      = new ForumAction;
        $pagination->isExistPage();
        $Response   = (new TopicAction($pagination->setOffset()))
                    ->postResponses($pagination->PageTotal())
                    ->viewNotView()
                    ->resolved()
                    ->sticky()
                    ->nbView()
                    ->getTopicExist();

        $this->render('viewtopic',compact('forum','Response','pagination'));
    }

    public function viewforum(int $id)
    {
        $perpage = $this->Params()->GetParam(2);
        $pagination = new Pagination(
            $this->ThisRoute(),
            'SELECT COUNT(f_tags.id) 
                FROM f_topics LEFT JOIN f_topic_tags 
                ON f_topics.id = f_topic_tags.topic_id 
                LEFT JOIN f_tags ON f_topic_tags.tag_id = f_tags.id
                WHERE f_tags.id = ?',
            $id,
            $perpage,
            $this->app()
        );
        $viewforum  = new ForumAction($pagination->setOffset());
        $pagination->isExistPage();
        $viewforum->getViewForumExist();
        $this->render('viewforums', compact('viewforum', 'pagination'));
    }

    public function creatTopic()
    {
        $this->app()->isNotConnect();
        $forum = new ForumAction;
        $topic = (new TopicAction())->creatTopic();
        $this->render('creattopic',compact('topic','forum'));
    }

    public function editTopic()
    {
        $this->app()->isNotConnect();
        $forum = new ForumAction;
        $topic = (new TopicAction())->editTopic();
        $this->render('edittopic',compact('topic','forum'));
    }

    public function editRep()
    {
        $this->app()->isNotConnect();
        $response = (new TopicAction())->editResponse();
        $this->render('editrep', compact('response'));
    }

}