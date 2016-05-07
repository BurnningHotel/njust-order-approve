<?php

namespace Gini\Controller\CGI\AJAX;

class Layout extends \Gini\Controller\CGI
{
    public function actionHeader()
    {
        $vars = [
            'route' => $this->env['route'],
        ];

        return \Gini\IoC::construct('\Gini\CGI\Response\HTML', V('layout/header', $vars));
    }

    public function actionSidebar()
    {
        $apps = _G('GROUP')->getApps();
        unset($apps[\Gini\Gapper\Client::getId()]);

        $vars = [
            'route' => $this->env['route'],
            'apps' => $apps,
        ];

        return \Gini\IoC::construct('\Gini\CGI\Response\HTML', V('layout/sidebar', $vars));
    }

    public function actionFooter()
    {
        return \Gini\IoC::construct('\Gini\CGI\Response\HTML', V('layout/footer'));
    }

    public function actionBackToTop()
    {
        return \Gini\IoC::construct('\Gini\CGI\Response\HTML', V('layout/back-to-top'));
    }

}

