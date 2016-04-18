<?php

namespace Gini\Controller\CLI;

class Order extends \Gini\Controller\CLI
{
    public function __index($args)
    {
        echo "Available commands:\n";
        echo "  gini order update-all LOGID KEY NOTE\n";
        echo "  gini order update LOGID KEY VOUCHER,VOUCHER.. NOTE\n";
    }

    public function actionUpdateAll($argv)
    {
        // TODO
        return;
        if (count($argv)!=3) return;
        list($logID, $key, $note) = $argv;
        $pid = getmypid();

        $log = a('clilog', $logID);
        if (!$log->id) return;
        $log->pid = $pid;
        $log->status = \Gini\ORM\CLILog::STATUS_DOING;
        $log->save();

        try {
            $allowedOperators = \Gini\Config::get('order.operator');
            $keys = array_keys($allowedOperators);
            if (!in_array($key, $keys)) throw new \Exception();

            $from = [
                'status'=> \Gini\Config::get('order.status'),
                'review_status'=> \Gini\Config::get('order.review_status')
            ];
            $operator = $allowedOperators[$key];
            $to = [
                'status'=> $operator['status'],
                'review_status'=> $operator['review_status']
            ];

            $confs = \Gini\Config::get('hub.rpc');
            $conf = $confs['order'];
            $rpc = \Gini\IoC::construct('\Gini\RPC', $conf['url']);
            $client = \Gini\Config::get('hub.client');
            if (!$rpc->mall->authorize($client['id'], $client['secret'])) {
                throw new \Exception();
            }

            $criteria = [
                'status'=> $from['status'],
                'review_status'=> $from['review_status'],
                // TODO 需要制定一个时间点，因为cli的执行可能会比较耗时
                // 避免这个时间点之后的订单也被审核
            ];
            list($token, $total) = $rpc->mall->order->searchOrders($criteria);
            $log->total = (int)$total;
            $start = 0;
            $perpage = 20;
            while ($total) {
                $orders = $rpc->mall->order->getOrders($token, $start, $perpage);
                if (!count($orders)) break;
                $start += $perpage;
                foreach ($orders as $order) {
                    $voucher = $order['voucher'];
                    $bool = $rpc->mall->order->updateOrder($voucher, $to, $from);
                    if ($bool) {
                        $log->finished += 1;
                    }
                    else {
                        $log->failed += 1;
                    }
                    $log->save();
                }
                usleep(500);
            }
        }
        catch (\Exception $e) {
        }
        $log->status = \Gini\ORM\CLILog::STATUS_DONE;
        $log->save();
    }

    public function actionUpdate($argv)
    {
        // TODO
        return
        if (count($argv)!=4) return;
        $pid = getmypid();
        list($logID, $key, $vouchers, $note) = $argv;

        $log = a('clilog', $logID);
        if ($log->id) return;
        $log->pid = $pid;
        $log->status = \Gini\ORM\CLILog::STATUS_DOING;
        $log->save();

        try {
            $vouchers = explode(',', $vouchers);
            if (empty($vouchers)) throw new \Exception();
            $allowedOperators = \Gini\Config::get('order.operator');
            $keys = array_keys($allowedOperators);
            if (!in_array($key, $keys)) throw new \Exception();

            $from = [
                'status'=> \Gini\Config::get('order.status'),
                'review_status'=> \Gini\Config::get('order.review_status')
            ];
            $operator = $allowedOperators[$key];
            $to = [
                'status'=> $operator['status'],
                'review_status'=> $operator['review_status']
            ];

            $log->total = count($vouchers);
            $log->save();

            $i = 0;
            foreach ($vouchers as $voucher) {
                $order = a('order', ['voucher'=> $voucher]);
                $bool = false;
                if ($order->id) {
                    $bool = $order->save($to);
                }
                if ($bool) {
                    $log->finished += 1;
                }
                else {
                    $log->failed += 1;
                }
                $log->save();
                if ($i%20==0) {
                    usleep(500);
                }
                $i++;
            }
        }
        catch (\Exception $e) {
        }
        $log->status = \Gini\ORM\CLILog::STATUS_DONE;
        $log->save();
    }
}
