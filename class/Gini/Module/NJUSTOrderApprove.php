<?php

namespace Gini\Module;

class NJUSTOrderApprove
{
    public static function setup()
    {
        date_default_timezone_set(\Gini\Config::get('system.timezone') ?: 'Asia/Shanghai');

        class_exists('\Gini\Those');
        class_exists('\Gini\ThoseIndexed');

        \Gini\Gapper\Client::init();

        $me = a('user', ['username' => \Gini\Gapper\Client::getUserName()]);
        _G('ME', $me);
        $me->id or \Gini\Gapper\Client::logout();

        $group = a('group', $me->id ? \Gini\Gapper\Client::getGroupID() : null);
        if ($group->id) {
            $allowedGroups = (array) \Gini\Config::get('njust.group');
            if (isset($allowedGroups[$group->id])) {
                _G('GROUP', $group);
            }
            else {
                \Gini\Gapper\Client::resetGroup();
            }
        }

        isset($_GET['locale']) and $_SESSION['locale'] = $_GET['locale'];
        isset($_SESSION['locale']) and \Gini\Config::set('system.locale', $_SESSION['locale']);
        \Gini\I18N::setup();

        setlocale(LC_MONETARY, (\Gini\Config::get('system.locale') ?: 'en_US').'.UTF-8');
    }

    public static function diagnose()
    {
        $errors = [];
        $followedNodes = (array)\Gini\Config::get('njust.followed_nodes');
        if (empty($followedNodes)) {
            $errors[] = 'njust.followed_nodes is empty';
        }

        $groups = (array)\Gini\Config::get('njust.group');
        if (empty($groups)) {
            $errors[] = 'njust.groups is empty';
        }

        if (!empty($errors)) {
            return $errors;
        }
    }

}


