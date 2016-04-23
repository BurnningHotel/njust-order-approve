<?php

namespace Gini\Controller\API;

class Debade extends \Gini\Controller\API
{
    public function actionGetNotified($message)
    {
        $hash = $_SERVER['HTTP_X_DEBADE_TOKEN'];
        $secret = \Gini\Config::get('app.debade_secret');
        $str = file_get_contents('php://input');

        if ($hash != \Gini\DeBaDe::hash($str, $secret)) {
            return;
        }

        $id = $message['id'];
        $data = $message['data'];
        if (($id !== 'order') || !isset($data['voucher'])) {
            return;
        }
        // {{{
        // data [
        //  voucher
        //  requester && customer && vendor [
        //      id
        //      name
        //  ]
        //  address
        //  invoice_title
        //  phone
        //  postcode
        //  email
        //  note
        //  status
        //  payment_status
        //  deliver_status
        //  label
        //  node
        //  items [
        //      [
        //          product
        //          quantity
        //          unit_price
        //          total_price
        //          deliver_status
        //          name
        //          manufacturer
        //          catalog_no
        //          package
        //          cas_no
        //      ]
        //  ]
        // ]
        // }}}

        $voucher = $data['voucher'];
        $node = $data['node'];
        $followedNodes = (array)\Gini\Config::get('njust.followed_nodes');
        if (!in_array($node, $followedNodes)) return;
        $status = $data['status'];
        $followedStatus = \Gini\ORM\Order::STATUS_NEED_MANAGER_APPROVE;
        if ($status!=$followedStatus) return;

        $items = (array)$data['items'];
        $needApprove = false;
        $pNames = [];
        $pCASs = [];
        foreach ($items as $item) {
            $pCASs[] = $casNO = $item['cas_no'];
            $pNames[] = $item['name'];
            if (self::_isHazPro($casNO)) {
                $needApprove = true;
            }
        }

        if (!$needApprove) {
            return self::_approve($voucher);
        }

        $request = a('request', ['voucher'=> $voucher]);
        if ($request->id && $request->status==\Gini\ORM\Request::STATUS_UNIVERS_PASSED) {
            return self::_approve($voucher);
        }

        if ($request->id && in_array($request->status, [
            \Gini\ORM\Request::STATUS_COLLEGE_FAILED,
            \Gini\ORM\Request::STATUS_UNIVERS_FAILED,
        ])) {
            return self::_reject($voucher);
        }

        $request->voucher = $voucher;
        $request->status = \Gini\ORM\Request::STATUS_PENDING;
        $request->ctime = $request->ctime ?: date('Y-m-d H:i:s');
        $request->product_name = implode(',', array_unique($pNames));
        $request->product_cas_no = implode(',', array_unique($pCASs));
        $request->save();
    }

    private static function _approve($voucher)
    {
        $rpc = self::_getRPC('order');
        $bool = $rpc->mall->order->updateOrder($voucher, [
            'status' => \Gini\ORM\Order::STATUS_APPROVED,
        ], [
            'status' => \Gini\ORM\Order::STATUS_NEED_MANAGER_APPROVE,
        ]);
        return $bool;
    }

    private static function _reject($voucher)
    {
        $rpc = self::_getRPC('order');
        $bool = $rpc->mall->order->updateOrder($voucher, [
            'status' => \Gini\ORM\Order::STATUS_CANCELED,
        ], [
            'status' => \Gini\ORM\Order::STATUS_NEED_MANAGER_APPROVE,
        ]);
        return $bool;
    }

    private static function _isHazPro($casNO)
    {
        if (!$casNO) return;
        if (a('hazardous', ['cas_no'=> $casNO])->id) {
            return true;
        }
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
