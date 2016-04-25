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
        $group = _G('GROUP');
        if (!$group->id) return false;

        $groups = (array)\Gini\Config::get('njust.group');
        $options = $groups[$group->id];
        if (empty($options)) return false;

        $organizations = $options['organizations'];
        if (empty($organizations)) {
            return true;
        }

        $code = $this->organization_code;
        if (in_array($code, $organizations)) {
            return true;
        }

        return false;
    }
}
