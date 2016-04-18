<?php

namespace Gini\Controller\CGI\Layout;

abstract class Common extends \Gini\Controller\CGI\Layout
{

    protected static $layout_name = 'layout/common';

    public function __preAction($action, &$params)
    {
        /*
        // 对于已经登录但是没有选组的用户, 需要强制去选择组
        if (\Gini\Gapper\Client::getLoginStep() !== \Gini\Gapper\Client::STEP_DONE) {
            return \Gini\Gapper\Client::goLogin();
        }

        // 如果所属的组没有访问应用的权限直接登出
        if (!in_array(_G('GROUP')->id, \Gini\Config::get('order.group'))) {
            \Gini\Gapper\Client::logout();
            return \Gini\Gapper\Client::goLogin();
        }
         */

        return parent::__preAction($action, $params);
    }

    public function __postAction($action, &$params, $response)
    {
        $class = [];
        $args = explode('/', $this->env['route']);
        if (count($args) == 0) {
            $args = ['home'];
        }
        while (count($args) > 0) {
            $class[] = 'layout-'.implode('-', $args);
            array_pop($args);
        }
        $this->view->layout_class = implode(' ', array_reverse($class));

        $this->view->header = \Gini\CGI::request('ajax/layout/header', $this->env)->execute()->content();
        $this->view->sidebar = \Gini\CGI::request('ajax/layout/sidebar', $this->env)->execute()->content();
        $this->view->footer = \Gini\CGI::request('ajax/layout/footer', $this->env)->execute()->content();
        $this->view->back_to_top = \Gini\CGI::request('ajax/layout/back-to-top', $this->env)->execute()->content();

        return parent::__postAction($action, $params, $response);
    }
}
