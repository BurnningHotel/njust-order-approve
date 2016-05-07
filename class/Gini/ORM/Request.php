<?php

/**
* @file Request.php
* @brief 申请
* @author PiHiZi <pihizi@msn.com>
* @version 0.1.0
* @date 2016-04-16
 */

namespace Gini\ORM;

class Request extends Object
{
    public $voucher = 'string:120'; // 关联的订单voucher
    public $status = 'int,default:0'; // 申请单的当前状态
    public $ctime = 'datetime'; // 创建时间
    public $log = 'array'; // 管理操作日志
                           // [
                           //  [ctime=>,operator=>,note=>]
                           // ]
    public $product_name = 'string';
    public $product_cas_no = 'string';
    public $organization_code = 'string:10';
    public $organization_name = 'string:120';

    protected static $db_index = [
        'unique:voucher',
        'organization_code',
        'status',
        'ctime',
        'fulltext:product_name,product_cas_no'
    ];

    const STATUS_PENDING = 0; // 待处理
    // WARN
    //  通过的操作状态应该是奇数
    //  拒绝的操作状态应该是偶数
    const STATUS_COLLEGE_PASSED = 1; // 学院管理方审核通过
    const STATUS_COLLEGE_FAILED = 2; // 学院管理方审核拒绝
    const STATUS_UNIVERS_PASSED = 3; // 学校管理方审核通过
    const STATUS_UNIVERS_FAILED = 4; // 学校管理方审核拒绝

    public static $status_titles = [
        self::STATUS_PENDING=> '待处理',
        self::STATUS_COLLEGE_PASSED=> '院系审核通过',
        self::STATUS_COLLEGE_FAILED=> '院系已拒绝',
        self::STATUS_UNIVERS_PASSED=> '学校审核通过',
        self::STATUS_UNIVERS_FAILED=> '学校已拒绝',
    ];

    public function isRW()
    {
        $me = _G('ME');
        $group = _G('GROUP');
        $actions = those('hazardous/review/action')->whose('user')->is($me)
                    ->andWhose('group')->is($group)
                    ->andWhose('code')->is(substr($this->organization_code, 0, 2));
        if (count($actions)) {
            return true;
        }
        return false;
    }

    public function getAllowedOperators()
    {
        $result = [];
        $me = _G('ME');
        $group = _G('GROUP');

        $actions = those('hazardous/review/action')->whose('user')->is($me)
                    ->andWhose('group')->is($group)
                    ->andWhose('code')->is(substr($this->organization_code, 0, 2));
        if (!count($actions)) return $result;

        foreach ($actions as $action) {
            foreach ((array)$action->getOperators() as $key=>$op) {
                if ((string)$this->status===(string)$op['from_status']) {
                    $result[$key] = $op;
                }
            }
        }

        return $result;
    }

    public static function getAllowedPendingStatus($user, $group)
    {
        $result = [];
        $codes = [];
        $actions = those('hazardous/review/action')->whose('user')->is($user)->andWhose('group')->is($group);
        foreach ($actions as $action) {
            if ($action->type==\Gini\ORM\Hazardous\Review\Action::TYPE_STEP_COLLEGE) {
                $result[] = self::STATUS_PENDING;
                $codes[] = $action->code;
            }
            else if ($action->type==\Gini\ORM\Hazardous\Review\Action::TYPE_STEP_UNIVERS) {
                $result[] = self::STATUS_COLLEGE_PASSED;
                $codes[] = $action->code;
            }
        }
        return [$result, $codes];
    }

    public static function getAllowedDoneStatus($user, $group)
    {
        $result = [];
        $codes = [];
        $actions = those('hazardous/review/action')->whose('user')->is($user)->andWhose('group')->is($group);
        foreach ($actions as $action) {
            if ($action->type==\Gini\ORM\Hazardous\Review\Action::TYPE_STEP_COLLEGE) {
                $result[] = self::STATUS_COLLEGE_PASSED;
                $result[] = self::STATUS_COLLEGE_FAILED;
                $result[] = self::STATUS_UNIVERS_PASSED;
                $result[] = self::STATUS_UNIVERS_FAILED;
                $codes[] = $action->code;
            }
            else if ($action->type==\Gini\ORM\Hazardous\Review\Action::TYPE_STEP_UNIVERS) {
                $result[] = self::STATUS_UNIVERS_PASSED;
                $result[] = self::STATUS_UNIVERS_FAILED;
                $codes[] = $action->code;
            }
        }
        return [$result, $codes];
    }

}
