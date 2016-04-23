<?php

namespace Gini\ORM;

class Order extends Hub\RObject
{
    public $voucher = 'string:120';
    public $price = 'double';
    public $ctime = 'datetime';
    public $status = 'int';
    public $group = 'object:group'; // 所属组
    public $vendor = 'object:vendor';
    public $user = 'object:user'; // 组负责人
    public $items = 'array';

    const STATUS_NEED_VENDOR_APPROVE = 0;
    const STATUS_READY_TO_ORDER = 1;
    const STATUS_APPROVED = 2;
    const STATUS_RETURNING = 3;
    const STATUS_PENDING_TRANSFER = 4;
    const STATUS_TRANSFERRED = 5;
    const STATUS_PENDING_PAYMENT = 6;
    const STATUS_PAID = 7;
    const STATUS_CANCELED = 8;
    const STATUS_REQUESTING = 9;
    const STATUS_RETURN_REJECTED = 10;
    const STATUS_NEED_CUSTOMER_APPROVE = 11;
    const STATUS_DRAFT = 12;
    # 请供应商先确认这个状态不会被存在数据库
    # 仅为了实现功能而设
    const STATUS_NEED_VENDOR_APPROVE_FIRST = 13;

    const STATUS_NEED_MANAGER_APPROVE = 14; // 等待管理方确认

    const DELIVER_STATUS_NOT_DELIVERED = 0;
    const DELIVER_STATUS_DELIVERED = 1;
    const DELIVER_STATUS_RECEIVED = 2;

    const PAYMENT_STATUS_UNABLE = 0;
    const PAYMENT_STATUS_PENDING = 1;

    public static $status_title = [
        self::STATUS_REQUESTING => '申购中',
        self::STATUS_NEED_CUSTOMER_APPROVE => '待买方确认',
        self::STATUS_NEED_VENDOR_APPROVE => '待供应商确认',
        self::STATUS_NEED_VENDOR_APPROVE_FIRST => '待供应商先确认',
        self::STATUS_READY_TO_ORDER => '已确认',
        self::STATUS_APPROVED => '待付款',
        self::STATUS_RETURNING => '退货中',
        self::STATUS_PENDING_TRANSFER => '付款中',
        self::STATUS_TRANSFERRED => '已付款',
        self::STATUS_PENDING_PAYMENT => '已付款',
        self::STATUS_PAID => '已付款',
        self::STATUS_CANCELED => '已取消',
        self::STATUS_RETURN_REJECTED => '拒绝退货',
        self::STATUS_DRAFT => '已驳回',
    ];

    public static $deliver_status_title = [
        self::DELIVER_STATUS_NOT_DELIVERED => '未发货',
        self::DELIVER_STATUS_DELIVERED => '已发货',
        self::DELIVER_STATUS_RECEIVED => '已到货',
    ];

    private static $_chemdbRPC;
    public function convertRPCData(array $rdata)
    {
        // TODO 需要确保hub-order API返回的数据符合要求
        $data = [];
        $data['voucher'] = $rdata['voucher'];
        $data['price'] = (float) $rdata['price'];
        $data['ctime'] = date('Y-m-d H:i:s', (int)$rdata['ctime']); // datetime
        $data['status'] = (int)$rdata['status'];
        $data['group_id'] = (int)$rdata['customer'];
        $data['vendor_id'] = (int)$rdata['vendor'];
        $data['user_id'] = (int)$rdata['customer_owner'];
        $items = (array) $rdata['items'];

        $casNOs = [];
        foreach ($items as $item) {
            if (!$item['cas_no']) continue;
            $casNOs[] = $item['cas_no'];
        }
        $casNOs = array_unique($casNOs);

        if (!empty($casNOs)) {
            if (!self::$_chemdbRPC) {
                $conf = \Gini\Config::get('cheml-db.rpc');
                $url = $conf['url'];
                self::$_chemdbRPC = \Gini\IoC::construct('\Gini\RPC', $url);
            }

            $rpc = self::$_chemdbRPC;
            $cTypes = (array)$rpc->product->chem->getTypes($casNOs);
        }

        if (!empty($cTypes)) {
            foreach ($items as &$item) {
                if (!$item['cas_no']) continue;
                if (!isset($cTypes[$item['cas_no']])) continue;
                $item['product_types'] = array_unique($cTypes[$item['cas_no']]);
            }
        }

        $data['items'] = json_encode($items);

        $data['_extra'] = J(array_diff_key($rdata, $data));

        return $data;
    }

    protected function fetchRPC($criteria)
    {
        $rpc = self::getRPC('order');
        // 因为mall-old的getOrderAPI只支持传一个voucher
        if (is_array($criteria) && isset($criteria['voucher'])) {
            $criteria = $criteria['voucher'];
        }
        return $rpc->mall->order->getOrder($criteria);
    }

    public function save()
    {
        return parent::save();
        // TODO 
        $args = func_get_args();
        $params = $args[0];
        if (empty($params)) return false;
        $rpc = self::getRPC('order');
        $bool = $rpc->mall->order->updateOrder($this->voucher, $params);
        if ($bool) {
            $key = $this->name().'#'.$this->id;
            $cacher = \Gini\Cache::of('orm');
            $cacher->remove($key);
        }
        return !!$bool;
    }

}
