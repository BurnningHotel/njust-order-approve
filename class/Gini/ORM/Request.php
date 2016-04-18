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

    protected static $db_index = [
        'unique:voucher',
        'status',
        'ctime',
        'fulltext:product_name,product_cas_no'
    ];

    const STATUS_PENDING = 0; // 待处理
    const STATUS_COLLEGE_PASSED = 1; // 学院管理方审核通过
    const STATUS_COLLEGE_FAILED = 2; // 学院管理方审核拒绝
    const STATUS_UNIVERS_PASSED = 3; // 学校管理方审核通过
    const STATUS_UNIVERS_FAILED = 4; // 学校管理方审核拒绝

}
