<?php

namespace Gini\ORM;

/**
    * @brief 后台命令行程序执行日志
 */
class CLILog extends Object
{
    public $user = 'object:user'; // 实际操作人
    public $group = 'object:group'; // 所属的组
    public $ctime = 'datetime'; // 时间
    public $pid = 'int'; // pid
    public $total = 'int'; // 总共需要处理的记录数 = finished + failed
    public $finished = 'int'; // 已成功完成的记录数
    public $failed = 'int'; // 已经失败的记录数
    public $status = 'int,default:0'; // 命令的执行状态

    const STATUS_PENDING = 0; // 等待命令执行
    const STATUS_DOING= 1; // 命令执行中
    const STATUS_DONE = 2; // 命令执行结束

    protected static $db_index = [
        'group,user'
    ];
}
