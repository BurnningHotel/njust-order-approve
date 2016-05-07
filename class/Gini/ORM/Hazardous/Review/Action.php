<?php

namespace Gini\ORM\Hazardous\Review;

class Action extends \Gini\ORM\Object
{
    // hazardous 命名空间下的对象动与hazardous-control共用数据库
    protected static $db_name = 'hazardous';

    public $type = 'int'; // action 类型：一级审查，二级审查；院级审查，校级审查
    public $user = 'object:user'; // 动作的执行者
    public $group = 'object:group'; // user 所属的组
    public $code = 'string:10'; // 组织机构的代码

    const TYPE_STEP_COLLEGE = 1;
    const TYPE_STEP_UNIVERS = 2;

    protected static $db_index = [
        'unique:group,user,code,type'
    ];

    private static $collegeOperators = [
        'college_pass'=> [
            'title'=> '通过',
            'from_status'=> \Gini\ORM\Request::STATUS_PENDING,
            'to_status'=> \Gini\ORM\Request::STATUS_COLLEGE_PASSED
        ],
        'college_fail'=> [
            'title'=> '拒绝',
            'from_status'=> \Gini\ORM\Request::STATUS_PENDING,
            'to_status'=> \Gini\ORM\Request::STATUS_COLLEGE_FAILED
        ]
    ];
    private static $universOperators = [
        'univers_pass'=> [
            'title'=> '通过',
            'from_status'=> \Gini\ORM\Request::STATUS_COLLEGE_PASSED,
            'to_status'=> \Gini\ORM\Request::STATUS_UNIVERS_PASSED
        ],
        'univers_fail'=> [
            'title'=> '拒绝',
            'from_status'=> \Gini\ORM\Request::STATUS_COLLEGE_PASSED,
            'to_status'=> \Gini\ORM\Request::STATUS_UNIVERS_FAILED
        ],
    ];

    public function getOperators() {
        if ($this->type == self::TYPE_STEP_COLLEGE) {
            return self::$collegeOperators;
        }
        if ($this->type == self::TYPE_STEP_UNIVERS) {
            return self::$universOperators;
        }
    }

}

