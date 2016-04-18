<?php

namespace Gini\Controller\CGI;

class Index extends Layout\Common
{
    public function __index()
    {
        return $this->redirect('order');
    }

    public function actionOrder()
    {
        $vars = [
            'list'=> (string)\Gini\CGI::request('ajax/request/more')->execute()->content()
        ];
        $this->view->body = V('order', $vars);
    }

    public function actionOrderInfo($requestID)
    {
        $request = a('request', $requestID);
        $order = a('order', $request->voucher);
        $this->view->body = V('order/info', [
            'request'=> $request,
            'order'=> $order
        ]);
    }

    public function actionLogout()
    {
        \Gini\Gapper\Client::logout();
        $this->redirect('/');
    }
}

