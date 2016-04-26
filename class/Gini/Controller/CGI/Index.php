<?php

namespace Gini\Controller\CGI;

class Index extends Layout\Common
{
    public function __index()
    {
        return $this->redirect('order');
    }

    public function actionWarnGroup()
    {
        $group = _G('GROUP');
        if (!$group->id) {
            return $this->redirect('error/401');
        }
        \Gini\Gapper\Client::logout();
        if (\Gini\Config::get('njust.need_access_control')) {
            $groups = (array)\Gini\Config::get('njust.group');
            if (in_array($group->id, $groups)) {
                return $this->redirect('home');
            }
            $this->view->body = V('warn-group', [
                'group'=> $group
            ]);
        }
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
        $group = _G('GROUP');
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
            'operators'=> self::_getAllowedOperators($group->id, $request->status)
        ]);
    }

    public function actionLogout()
    {
        \Gini\Gapper\Client::logout();
        $this->redirect('/');
    }

    private static function _getAllowedOperators($groupID, $status)
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
                if ($status==$operators[$key]['from_status']) {
                    $result[$key] = $operators[$key];
                }
            }
        }

        return $result;
    }

}

