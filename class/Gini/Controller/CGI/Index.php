<?php

namespace Gini\Controller\CGI;

class Index extends Layout\Common
{
    public function __index()
    {
        return $this->redirect('home');
    }

    public function actionOrder()
    {
        $this->view->body = V('order');
    }

    public function actionLogout()
    {
        \Gini\Gapper\Client::logout();
        $this->redirect('/');
    }
}

