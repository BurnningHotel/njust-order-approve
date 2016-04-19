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
            'list'=> (string)\Gini\CGI::request("ajax/request/more/0/{$type}", $this->env)->execute()->content()
        ];
        $this->view->body = V('order', $vars);
    }

    public function actionOrderInfo($requestID)
    {
        $group = _G('GROUP');
        $request = a('request', $requestID);
        $order = a('order', $request->voucher);
        $this->view->body = V('order/info', [
            'request'=> $request,
            'order'=> $order,
            'operators'=> self::_getAllowedOperators($group->id)
        ]);
    }

    public function actionLogout()
    {
        \Gini\Gapper\Client::logout();
        $this->redirect('/');
    }

    private static function _getAllowedOperators($groupID)
    {
        $result = [];
        if (!$groupID) {
            return $result;
        }
        $allowedOperators = (array) \Gini\Config::get('njust.group');
        if (!isset($allowedOperators[$groupID])) {
            return $result;
        }
        $operators = (array) \Gini\Config::get('njust.operators');
        foreach ((array) $allowedOperators[$groupID]['operators'] as $key) {
            if (isset($operators[$key])) {
                $result[$key] = $operators[$key];
            }
        }

        return $result;
    }

}

