<?php

namespace Gini\ORM\Hazardous\Review;

class Type extends \Gini\ORM\Object
{
    // hazardous 命名空间下的对象动与hazardous-control共用数据库
    protected static $db_name = 'hazardous';

    public $key = 'string:20';  // 类型名称: highly_toxic, drug_precursor, hazardous

    protected static $db_index = [
        'unique:key'
    ];
}
