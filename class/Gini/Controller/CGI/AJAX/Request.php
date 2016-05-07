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
    public function actionMore($page = 1, $type = 'pending')
    {
        $me = _G('ME');
        $group = _G('GROUP');
        if (!$me->id || !$group->id) {
            return;
        }

        $page = (int) max($page, 1);
        $form = $this->form();
        $q = $form['q'];
        list($total, $requests) = self::_getMoreRequest($page, $type, $q);

        if (!count($requests)) {
            return \Gini\IoC::construct('\Gini\CGI\Response\HTML', V('order/list-none'));
        }

        return \Gini\IoC::construct('\Gini\CGI\Response\HTML', V('order/list', [
            'requests'=> $requests,
            'type'=> $type,
            'page'=> $page,
            'total'=> $total
        ]));
    }

    private static function _getMoreRequest($page, $type, $querystring=null)
    {
        $me = _G('ME');
        $group = _G('GROUP');
        list($status, $codes) = ($type=='pending') ? \Gini\ORM\Request::getAllowedPendingStatus($me, $group) : \Gini\ORM\Request::getAllowedDoneStatus($me, $group);
        if (empty($status) || empty($codes)) {
            return [0, []];
        }

        $limit = 25;
        $start = ($page - 1) * $limit;
        $params = [];

        $sts = [];
        foreach ($status as $i=>$st) {
            $sts[":status{$i}"] = $st;
        }
        $params = array_merge($params, $sts);
        $sts = implode(',', array_keys($sts));
        $sql = "SELECT id FROM request WHERE status in ({$sts})";

        $ocs = [];
        foreach ($codes as $i=>$code) {
            $ocs[":oc{$i}"] = "{$code}%";
        }
        $params = array_merge($params, $ocs);
        $ocds = [];
        foreach ($ocs as $ock=>$ocv) {
            $ocds[] = "organization_code LIKE {$ock}";
        }
        $ocds = implode(' OR ', $ocds);
        $sql = "{$sql} AND ({$ocds})";

        if ($querystring) {
            $sql = "{$sql} AND (voucher=:voucher OR MATCH(product_name,product_cas_no) AGAINST(:querystring))";
            $params[':voucher'] = $params[':querystring'] = trim($querystring);
        }

        $sql = "{$sql} ORDER BY id DESC LIMIT {$start}, {$limit}";

        $requests = those('request')->query($sql, null, $params);
        $total = $requests->totalCount();

        foreach ($requests as $request) {
            $request->order = a('order', ['voucher'=> $request->voucher]);
        }
        return [ceil($total/$limit), $requests];
    }

    public function actionGetOPForm()
    {
        $me = _G('ME');
        $group = _G('GROUP');
        if (!$me->id || !$group->id) {
            return;
        }
        $form = $this->form();
        $key = $form['key'];
        $id = $form['id'];
        $request = a('request', $id);
        $allowedOperators = $request->getAllowedOperators();
        if (!isset($allowedOperators[$key])) return;
        $title = $allowedOperators[$key]['title'];
        return \Gini\IoC::construct('\Gini\CGI\Response\HTML', V('order/op-form', [
            'id'=> $id,
            'key'=> $key,
            'title'=> $title
        ]));
    }

    /**
     * @brief 允许管理方单个审批
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

        $form = $this->form('post');
        $key = $form['key'];
        $id = $form['id'];
        $note = $form['note'];
        $request = a('request', $id);
        $allowedOperators = $request->getAllowedOperators();
        if (!isset($allowedOperators[$key]) || $request->status!=$allowedOperators[$key]['from_status'] || !$request->isRW()) {
            return;
        }

        $operator = $allowedOperators[$key];
        $toStatus = $operator['to_status'];
        $bool = false;
        $message = '';

        $db = \Gini\Database::db();
        $db->beginTransaction();
        try {
            $rpc = self::_getRPC('order');
            $request->status = $toStatus;
            if ($note) {
                $request->log = array_merge((array)$request->log, [
                    [
                        'ctime'=> date('Y-m-d H:i:s'),
                        'operator'=> $me,
                        'note'=> $note ?: '--'
                    ]
                ]);
            }

            if ($request->save()) {
                if ($toStatus == \Gini\ORM\Request::STATUS_UNIVERS_PASSED) {
                    $bool = $rpc->mall->order->updateOrder($request->voucher, [
                        'status' => \Gini\ORM\Order::STATUS_APPROVED,
                        'mall_description'=> [
                            'a'=> H(T('订单已经被 :name(:group) 最终审核通过', [':name'=>$me->name, ':group'=>$group->title])),
                            't'=> date('Y-m-d H:i:s'),
                            'u'=> $me->id,
                            'd'=> $note ?: '--'
                        ]
                    ], [
                        'status' => \Gini\ORM\Order::STATUS_NEED_MANAGER_APPROVE,
                    ]);
                    if (!$bool) {
                        throw new \Exception();
                    }
                } 
                else if ($toStatus == \Gini\ORM\Request::STATUS_COLLEGE_PASSED) {
                    $bool = $rpc->mall->order->updateOrder($request->voucher, [
                        'mall_description'=> [
                            'a'=> H(T('订单已经被学院管理员 :name(:group) 审核通过', [':name'=>$me->name, ':group'=>$group->title])),
                            't'=> date('Y-m-d H:i:s'),
                            'u'=> $me->id,
                            'd'=> $note ?: '--'
                        ]
                    ], [
                        'status' => \Gini\ORM\Order::STATUS_NEED_MANAGER_APPROVE,
                    ]);
                    if (!$bool) {
                        throw new \Exception();
                    }
                } 
                elseif (in_array($toStatus, [
                    \Gini\ORM\Request::STATUS_UNIVERS_FAILED,
                    \Gini\ORM\Request::STATUS_COLLEGE_FAILED,
                ])) {
                    if (!$note) {
                        $message = T('请填写拒绝理由');
                        throw new \Exception();
                    }
                    $bool = $rpc->mall->order->updateOrder($request->voucher, [
                        'status' => \Gini\ORM\Order::STATUS_CANCELED,
                        'mall_description'=> [
                            'a'=> H(T('订单被 :name(:group) 拒绝', [':name'=>$me->name, ':group'=>$group->title])),
                            't'=> date('Y-m-d H:i:s'),
                            'u'=> $me->id,
                            'd'=> $note
                        ]
                    ], [
                        'status' => \Gini\ORM\Order::STATUS_NEED_MANAGER_APPROVE,
                    ]);
                    if (!$bool) {
                        throw new \Exception();
                    }
                }
                $bool = true;
                $db->commit();
            }
        } catch (\Exception $e) {
            $db->rollback();
        }

        return \Gini\IoC::construct('\Gini\CGI\Response\JSON', [
            'code' => $bool ? 0 : 1,
            'id'=> $id, // request->id
            'message' => $message ?: ($bool ? T('操作成功') : T('操作失败, 请您重试')),
        ]);
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
                \Gini\Logger::of('njust-order-approve')
                    ->error('Hub\\RObject getRPC: authorization failed with {client_id}/{client_secret} !',
                        ['client_id' => $client['id'], 'client_secret' => $client['secret']]);
            }
        }

        return self::$_RPCs[$type];
    }

}
