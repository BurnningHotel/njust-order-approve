<?php

namespace Gini\ORM\Hub;

/**
 * Robject 是用于数据获取的特殊类：用于数据模型对象远程rpc获取相应信息的底层支持类.
 **/
abstract class RObject extends \Gini\ORM\Object
{
    //缓存时间
    protected $cacheTimeout = 5;

    /**
     * 获取默认指定API路径的RPC对象
     *
     * @return new RPC
     **/
    protected static $_RPCs = [];
    protected static function getRPC($type='default')
    {
        $confs = \Gini\Config::get('hub.rpc');
        if (!isset($confs[$type])) $type = 'default';
        $conf = $confs[$type] ?: [];
        if (!self::$_RPCs[$type]) {
            $rpc = \Gini\IoC::construct('\Gini\RPC', $conf['url']);
            self::$_RPCs[$type] = $rpc;
            $client = \Gini\Config::get('hub.client');
            $token = $rpc->mall->authorize($client['id'], $client['secret']);
            if (!$token) {
                \Gini\Logger::of('lab-orders')
                    ->error('Hub\\RObject getRPC: authorization failed with {client_id}/{client_secret} !',
                        [ 'client_id' => $client['id'], 'client_secret' => $client['secret']]);
            }
        }

        return self::$_RPCs[$type];
    }

    /**
     * 按照配置设定的path 和 method 来进行RPC远程数据抓取.
     *
     * @return mixed
     **/
    protected function fetchRPC($id)
    {
        return false;
    }

    public function db()
    {
        return false;
    }

    public function fetch($force = false)
    {
        if ($force || $this->_db_time == 0) {
            $criteria = $this->criteria();
            if (count($criteria) > 0) {
                $criteria = $this->normalizeCriteria($criteria);

                $id = $criteria['id'] ?: md5(J($criteria));
                if ($id) {
                    $key = $this->name().'#'.$id;
                    $cacher = \Gini\Cache::of('orm');
                    $data = $cacher->get($key);
                    if (is_array($data)) {
                        \Gini\Logger::of('orm')->debug("cache hits on {key}", ['key'=>$key]);
                    } else {
                        \Gini\Logger::of('orm')->debug("cache missed on {key}", ['key'=>$key]);
                        $rdata = $this->fetchRPC($criteria['id'] ? $id : $criteria);
                        if (is_array($rdata) && count($rdata) > 0) {
                            $data = $this->convertRPCData($rdata);
                            // set ttl to cacheTimeout sec
                            $cacher->set($key, $data, $this->cacheTimeout);
                        }
                    }

                    // 确认数据有效再进行id赋值
                    if (is_array($data) && count($data) > 0) {
                        $data['id'] = (int)$data['id'] ?: (int)$criteria['id'];
                    }
                }
            }

            $this->setData((array) $data);
        }
    }

    public function delete()
    {
        return false;
    }

    public function save()
    {
        return false;
    }

    // 为了提高效率和稳定性, 如果访问的是id, 我们就直接返回目前已有的值, 而不直接实例化
    public function &__get($name)
    {
        // 1. 还没有调用 fetch
        // 2. 访问的是 id
        // 3. 初始化条件中有且仅有 id, (因为有别的条件的时候推测是可能期望通过抓取来筛选的)
        if ($this->_db_time == 0 && $name == 'id') {
            $criteria = $this->criteria();
            if (count($criteria)==1 && isset($criteria['id'])) {
                return $criteria['id'];
            }
        }
        return parent::__get($name);
    }
}

