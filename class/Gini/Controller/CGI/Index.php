<?php

namespace Gini\Controller\CGI;

class Index extends Layout\Common
{
    public function __index()
    {
        return $this->redirect('order');
    }

    public function actionOrder($type='pending')
    {
        $vars = [
            'list'=> (string)\Gini\CGI::request("ajax/request/more/0/{$type}", $this->env)->execute()->content(),
            'type'=> $type
        ];
        $this->view->body = V('order', $vars);
    }

    public function actionOrderInfo($requestID)
    {
        $request = a('request', $requestID);
        if (!$request->id) {
            return $this->redirect('error/404');
        }
        if (!$request->isRW()) {
            return $this->redirect('error/401');
        }

        $order = a('order', ['voucher'=> $request->voucher]);
        $this->view->body = V('order/info', [
            'request'=> $request,
            'order'=> $order,
            'operators'=> $request->getAllowedOperators()
        ]);
    }

    public function actionLogout()
    {
        \Gini\Gapper\Client::logout();
        $this->redirect('/');
    }

}

