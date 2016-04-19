<?php

namespace Gini\ORM;

class Hazardous extends Object
{
    public $type = 'string:150'; // 化学试剂类别 易制毒、危险品、剧毒品、普通试剂
    public $name = 'string:150'; // 商品名称
    public $cas_no = 'string:150'; // 化学试剂CAS号
    protected static $db_index = [
        'cas_no',
    ];
}
