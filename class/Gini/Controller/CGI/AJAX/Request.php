<?php
/**
* @file Request.php
* @brief 申请单列表和处理
*
* @author PiHiZi <pihizi@msn.com>
*
* @version 0.1.0
* @date 2016-04-16
 */

namespace Gini\Controller\CGI\AJAX;

class Request extends \Gini\Controller\CGI
{
    public function actionMore($start = 0, $type = 'pending')
    {
        $me = _G('ME');
        $group = _G('GROUP');
        if (!$me->id || !$group->id) {
            return;
        }
        list($pendingStatus, $finishedStatus) = self::_getListStatus($group->id);
        $start = (int) max($start, 0);
        $form = $this->form();
        $q = $form['q'];
        if ($type=='pending') {
            $requests = self::_getMoreRequest($start, $pendingStatus, $q);
        }
        else {
            $requests = self::_getMoreRequest($start, $finishedStatus, $q);
        }

        return \Gini\IoC::construct('\Gini\CGI\Response\HTML', V('order/list', [
            'requests'=> $requests
        ]));
    }

    private static function _getMoreRequest($start, $status, $querystring=null)
    {
        $limit = 25;
        if ($querystring) {
            $sql = "SELECT id FROM request WHERE status=:status LIMIT {$start}, {$limit}";
            $params = [
                ':status'=> implode(',', $status)
            ];
        }
        else {
            $sql = "SELECT id FROM request WHERE status=:status AND (voucher=:voucher OR MATCH(product_name,product_cas_no) AGAINST(:querystring)) LIMIT {$start}, {$limit}";
            $params = [
                ':status'=> implode(',', $status),
                ':voucher'=> $querystring,
                ':querystring'=> $querystring,
            ];
        }
        $requests = those('request')->query($sql);
        return $requests;
    }

    private static function _getListStatus($groupID)
    {
        $allowedOperators = self::_getAllowedOperators($groupID);
        $pendingStatus = [];
        $finishedStatus = [];
        foreach ($allowedOperators as $operator) {
            $pendingStatus[] = $operator['from_status'];
            $finishedStatus[] = $operator['to_status'];
        }
        $pendingStatus = array_unique($pendingStatus);
        $finishedStatus = array_unique($finishedStatus);
        $finishedStatus = array_diff($finishedStatus, $pendingStatus);
        $new = [];
        $all = [
            \Gini\ORM\Request::STATUS_PENDING,
            \Gini\ORM\Request::STATUS_COLLEGE_FAILED,
            \Gini\ORM\Request::STATUS_COLLEGE_PASSED,
            \Gini\ORM\Request::STATUS_UNIVERS_FAILED,
            \Gini\ORM\Request::STATUS_UNIVERS_PASSED,
        ];
        if (!empty($finishedStatus)) {
            $minFinished = min($finishedStatus);
            foreach ($all as $st) {
                if ($st>=$minFinished) {
                    $new[] = $st;
                }
            }
        }

        return [$pendingStatus, $new];
    }

    /**
     * @brief 允许管理方单个审批、批量审批和全部审批
     *
     * @return 
     */
    public function actionPost()
    {
        $me = _G('ME');
        $group = _G('GROUP');
        if (!$me->id || !$group->id) {
            return;
        }

        $allowedOperators = self::_getAllowedOperators($group->id);
        $form = $this->form('post');
        $key = $form['key'];
        $vouchers = $form['vouchers'];
        $all = $form['all'];
        $voucher = $form['voucher'];
        $note = $form['note'];
        if (!isset($allowedOperators[$key])) {
            return;
        }

        if ($all) {
            return $this->_execCLI($key, $note);
        } elseif (count($vouchers) > 1) {
            return $this->_execCLI($key, $note, $vouchers);
        } elseif (count($vouchers) == 1) {
            $voucher = array_pop($vouchers);
        }

        $operator = $allowedOperators[$key];
        $fromStatus = $operator['from_status'];
        $toStatus = $operator['to_status'];
        $bool = false;
        if ($voucher && in_array($toStatus, [
            \Gini\ORM\Request::STATUS_COLLEGE_FAILED,
            \Gini\ORM\Request::STATUS_UNIVERS_FAILED,
            \Gini\ORM\Request::STATUS_UNIVERS_PASSED,
        ])) {
            $db = \Gini\Database::db();
            $db->beginTransaction();
            try {
                $request = a('request', ['voucher' => $voucher]);
                if (!$request->id || $request->status != $fromStatus) {
                    throw new \Exception();
                }
                $rpc = self::_getRPC('order');
                $request->status = $toStatus;
                if ($note) {
                    $request->log = array_merge((array)$request->log, [
                        [
                            'ctime'=> date('Y-m-d H:i:s'),
                            'operator'=> $me,
                            'note'=> $note
                        ]
                    ]);
                }
                if ($request->save()) {
                    if ($toStatus == \Gini\ORM\Request::STATUS_UNIVERS_PASSED) {
                        $bool = $rpc->mall->order->updateOrder($voucher, [
                            'status' => \Gini\ORM\Order::STATUS_APPROVED,
                        ], [
                            'status' => \Gini\ORM\Order::STATUS_NEED_MANAGER_APPROVE,
                        ]);
                    } elseif (in_array($toStatus, [
                        \Gini\ORM\Request::STATUS_UNIVERS_FAILED,
                        \Gini\ORM\Request::STATUS_COLLEGE_FAILED,
                    ])) {
                        $bool = $rpc->mall->order->updateOrder($voucher, [
                            'status' => \Gini\ORM\Order::STATUS_CANCELED,
                        ], [
                            'status' => \Gini\ORM\Order::STATUS_NEED_MANAGER_APPROVE,
                        ]);
                    }
                    if (!$bool) {
                        throw new \Exception();
                    }
                    $db->commit();
                }
            } catch (\Exception $e) {
                $db->rollback();
            }
        }

        return \Gini\IoC::construct('\Gini\CGI\Response\JSON', [
            'code' => $bool ? 0 : 1,
            'message' => $bool ? T('操作成功') : T('操作失败, 请您重试'),
        ]);
    }

    /**
     * @brief 对于批量审批和全部审批，管理方可能想实时看到进度
     *        管理方可以通过异步的方式实时获取进度
     *
     * @return 
     */
    public function actionGetBatchInfo()
    {
        $me = _G('ME');
        $group = _G('GROUP');
        if (!$me->id || !$group->id) {
            return;
        }
        $form = $this->form();
        $id = $form['id'];
        $log = a('clilog', $id);
        if (!$log->id) {
            return \Gini\IoC::construct('\Gini\CGI\Response\JSON', [
                'code' => 1,
                'message' => T('请求的数据不存在或者已经被删除'),
            ]);
        }
        switch ($log->status) {
        case \Gini\ORM\CLILog::STATUS_DOING:
            $message = T('正在执行批量审批任务');
            break;
        case \Gini\ORM\CLILog::STATUS_DONE:
            $message = T('审批任务已经完成');
            break;
        case \Gini\ORM\CLILog::STATUS_PENDING:
        default:
            $message = T('正在等待执行批量审批任务');
        }

        return \Gini\IoC::construct('\Gini\CGI\Response\JSON', [
            'code' => 0,
            'message' => $message,
            'data' => [
                'status' => $log->status,
                'total' => $log->total,
                'finished' => $log->finished,
                'failed' => $log->failed,
            ],
        ]);
    }

    private function _execCLI($key, $note, $vouchers = null)
    {
        $me = _G('ME');
        $group = _G('GROUP');
        $log = a('clilog');
        $log->user = $me;
        $log->group = $group;
        $log->ctime = date('Y-m-d H:i:s');
        $log->save();
        if (!$log->id) {
            return \Gini\IoC::construct('\Gini\CGI\Response\JSON', [
                'code' => 1,
                'message' => T('操作失败, 请您重试或者联系系统管理员'),
            ]);
        }
        $giniFullName = $_SERVER['GINI_SYS_PATH'].'/bin/gini';
        $note = escapeshellarg($note);
        $key = escapeshellarg($key);
        if (!empty($vouchers)) {
            $vouchers = escapeshellarg(implode(',', $vouchers));
            exec("{$giniFullName} order update {$log->id} {$key} {$vouchers} {$note} > /dev/null 2>&1 &");
        } else {
            exec("{$giniFullName} order update-all {$log->id} {$key} {$note} > /dev/null 2>&1 &");
        }

        return \Gini\IoC::construct('\Gini\CGI\Response\JSON', [
            'code' => 0,
            'message' => T('您的批量处理请求已经在后台执行'),
            'id' => $log->id,
        ]);
    }

    private static function _getAllowedOperators($groupID)
    {
        $result = [];
        if (!$groupID) {
            return $result;
        }
        $allowedOperators = (array) \Gini\Config::get('njust.group');
        if (!isset($allowedOperators[$groupID])) {
            return $result;
        }
        $operators = (array) \Gini\Config::get('njust.operators');
        foreach ((array) $allowedOperators[$groupID]['operators'] as $key) {
            if (isset($operators[$key])) {
                $result[$key] = $operators[$key];
            }
        }

        return $result;
    }

    private static $_RPCs = [];
    private static function _getRPC($type)
    {
        $confs = \Gini\Config::get('hub.rpc');
        if (!isset($confs[$type])) {
            $type = 'default';
        }
        $conf = $confs[$type] ?: [];
        if (!self::$_RPCs[$type]) {
            $rpc = \Gini\IoC::construct('\Gini\RPC', $conf['url']);
            self::$_RPCs[$type] = $rpc;
            $client = \Gini\Config::get('hub.client');
            $token = $rpc->mall->authorize($client['id'], $client['secret']);
            if (!$token) {
                \Gini\Logger::of('lab-orders')
                    ->error('Hub\\RObject getRPC: authorization failed with {client_id}/{client_secret} !',
                        ['client_id' => $client['id'], 'client_secret' => $client['secret']]);
            }
        }

        return self::$_RPCs[$type];
    }
}
