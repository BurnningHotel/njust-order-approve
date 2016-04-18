<?php

namespace Gini\ORM;

class Order extends Hub\RObject
{
    public $vendor = 'object:vendor';
    public $group = 'object:group';
    public $requester = 'object:user';
    public $request_date = 'datetime';
    public $transferred_date = 'datetime';
    public $invoice_title = 'string:120';
    public $address = 'string:120';
    public $phone = 'string:120';
    public $postcode = 'string:120';
    public $email = 'string:120';
    public $note = 'string:250';
    public $voucher = 'string:120';
    public $price = 'double';
    public $status = 'int';
    public $review_status = 'int';
    public $payment_status = 'int';
    public $deliver_status = 'int';
    public $customized = 'int';
    public $synced = 'int';
    public $items = 'array';
    public $ctime = 'datetime';
    public $mtime = 'datetime';
    public $operator = 'object:user';
    public $label = 'string:8';
    public $hash = 'string:40';

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

    public function convertRPCData(array $rdata)
    {
        // TODO 需要确保hub-order API返回的数据符合要求
        $data = [];
        $data['vendor_id'] = (int)$rdata['vendor'];
        $data['group_id'] = (int)$rdata['group'];
        $data['requester_id'] = (int)$rdata['requester'];
        $data['request_date'] = $rdata['request_data']; // datetime
        $data['transferred_date'] = $rdata['transferred_date']; // datetime
        $data['invoice_title'] = $rdata['invoice_title'];
        $data['address'] = $rdata['address'];
        $data['phone'] = $rdata['phone'];
        $data['postcode'] = $rdata['postcode'];
        $data['email'] = $rdata['email'];
        $data['note'] = $rdata['note'];
        $data['voucher'] = $rdata['voucher'];
        $data['price'] = (float) $rdata['price'];
        $data['status'] = (int)$rdata['status'];
        $data['review_status'] = (int)$rdata['review_status'];
        $data['payment_status'] = (int)$rdata['payment_status'];
        $data['deliver_status'] = (int)$rdata['deliver_status'];
        $data['customized'] = (int)$rdata['customized'];
        $data['synced'] = (int)$rdata['synced'];
        $data['items'] = $rdata['items'];
        $data['ctime'] = $rdata['ctime']; // datetime
        $data['mtime'] = $rdata['mtime']; // datetime
        $data['operator_id'] = (int)$rdata['operator'];
        $data['label'] = $rdata['label'];
        $data['hash'] = $rdata['hash'];

        $data['_extra'] = J(array_diff_key($rdata, $data));

        return $data;
    }

    protected function fetchRPC($criteria)
    {
        // TODO 需要确认mall-old提供了对应的API
        $rpc = self::getRPC('order');
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
