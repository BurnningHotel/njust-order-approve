<?php

namespace Gini\ORM;

class Product extends Hub\RObject
{

    // 待审核
    const STATUS_PENDING = 1;
    // 销售中
    const STATUS_ON_SALE = 2;
    // 审核失败
    const STATUS_FAILED  = 3;

    const STOCK_STATUS_IN_STOCK = 0;
    const STOCK_STATUS_BOOKABLE = 1;
    const STOCK_STATUS_NO_STOCK = 2;
    const STOCK_STATUS_STOP_SUPPLY = 3;

    const RGT_TYPE_NORMAL = 1;
    const RGT_TYPE_HAZARDOUS = 2;
    const RGT_TYPE_DRUG_PRECURSOR = 3;
    const RGT_TYPE_HIGHLY_TOXIC = 4;

    public static $STOCK_STATUS = [
        self::STOCK_STATUS_IN_STOCK => '现货',
        self::STOCK_STATUS_NO_STOCK => '暂无存货',
        self::STOCK_STATUS_STOP_SUPPLY => '停止供货',
        self::STOCK_STATUS_BOOKABLE => '可预订',
    ];

    protected $cacheTimeout = 900;

    public $name             = 'string:120';
    public $manufacturer    = 'string:120';
    public $brand            = 'string:120';
    public $catalog_no      = 'string:120';
    public $package         = 'string:120';
    public $vendor_abbr     = 'string:120';
    public $vendor           = 'object:vendor';
    public $price           = 'double';
    public $icon            = 'string:120';

    private function _imgRootPath($id = null)
    {
        return APP_PATH.'/'.DATA_DIR.'/orm/images/product/'.$this->id.'/';
    }

    public function convertRPCData(array $rdata)
    {
        $data = [];
        $data['name'] = $rdata['name'];
        $data['manufacturer'] = $rdata['manufacturer'];
        $data['brand'] = $rdata['brand'];
        $data['catalog_no'] = $rdata['catalog_no'];
        $data['package'] = $rdata['package'];
        $data['price'] = (float) $rdata['price'];
        $data['vendor_id'] = (int)$rdata['vendor'];
        $data['icon'] = $rdata['icon'];
        $data['stock_status'] = $rdata['stock_status'];
        $data['status'] = $rdata['status'];
        $data['selling'] = $rdata['selling'];
        $data['tags'] = $rdata['tags'];
        $data['_extra'] = J(array_diff_key($rdata, array_flip(['id', 'name', 'manufacturer', 'brand', 'catalog_no', 'package', 'price', 'vendor'])));

        return $data;
    }

    public function fetch($force = false)
    {
        if ($force || $this->_db_time == 0) {
            if (is_array($this->_criteria) && count($this->_criteria) > 0) {
                $criteria = $this->normalizeCriteria($this->_criteria);

                $id = $criteria['id'] ?: null;
                if ($id) {
                    $data = null;
                    $key = $this->name().'#'.$id;

                    if (isset($criteria['version'])) {
                        $key .= '-'.$criteria['version'];
                    }

                    $cacher = \Gini\Cache::of('orm');
                    $data = $cacher->get($key);

                    if (is_array($data)) {
                        \Gini\Logger::of('orm')->debug("cache hits on {key}", ['key'=>$key]);
                    } else {
                        \Gini\Logger::of('orm')->debug("cache missed on {key}", ['key'=>$key]);
                        $rdata = $this->fetchRPC($criteria);
                        if (is_array($rdata) && count($rdata) > 0) {
                            $data = $this->convertRPCData($rdata);
                            $cacher->set($key, $data, $this->cacheTimeout);
                        }
                    }

                    // 确认数据有效再进行id赋值
                    if (is_array($data) && count($data) > 0) {
                        $data['id'] = $id;
                    }
                }
            }

            $this->setData((array) $data);
        }
    }

    protected function fetchRPC($criteria)
    {
            $rpc = self::getRPC('product')->mall->product;

            return $rpc->getProduct($criteria);
    }

    public function getGroupedProducts()
    {
        $rdata = (array) self::getRPC('product')->mall->product->getGroupedProducts($this->id);
        $products = [];

        array_walk($rdata, function ($d) use (&$products) {
            $product = a('product');
            $product->setData(['id' => $d['id']] + $product->convertRPCData($d));
            $products[$product->id] = $product;
        });

        return $products;
    }

    public function getIntro($version = 0)
    {
        if ($version) {
            $criteria = [];
            $criteria['id'] = $this->id;
            $criteria['version'] = $version;
        } else {
            $criteria = ['id' => $this->id];
        }

        return self::getRPC('product')->mall->product->getProductIntro($criteria);
    }

    public function getRatings()
    {
        return self::getRPC('product')->mall->product->getProductRatings($this->id);
    }

    public function getComments($start, $num)
    {
        return self::getRPC('product')->mall->product->getProductComments($this->id, $start, $num);
    }

    /**
     * getCriteriaOptionAZ 返回对应.
     *
     * @return array 字母数组
     */
    public static function getCriteriaOptionAZ($template, $key)
    {
        $rpc = self::getRPC('product');
        return $rpc->mall->product->getCriteriaOptionAZ($template, $key);
    }

    /**
     * getCriteriaOptions 根据首字母得到条件内容.
     *
     * @return array 首字母对应的内容数组
     */
    public static function getCriteriaOptions($template, $key, $form)
    {
        $start = isset($form['start']) ? (int) $form['start'] : 0;
        $num = \Gini\Module\LabOrders::getOption('per_page', 'criteria_options');

        $rpc = self::getRPC('product');
        $result = $rpc->mall->product->searchCriteriaOptions($template, $key, $form);
        return $rpc->mall->product->getCriteriaOptions($result['token'], $start, $num);
    }

    public function icon($size = 64, $index = 0)
    {
        $url = $this->icon;
        $type = $this->type;

        $scheme = parse_url($url)['scheme'];

        if (!$url || ($scheme != 'http')) {
            switch ($type) {
                case 'bio_reagent':
                    $product_img = 'bio_product.png';
                    break;
                case 'consumable':
                    $product_img = 'con_product.png';
                    break;
                case 'chem_reagent':
                default:
                    $product_img = 'che_product.png';
            }
            return '/assets/img/product/' . $product_img;
            //return (new \Identicon\Identicon())->getImageDataUri(md5(strtolower(trim($this->name.$this->id))), $size);
        }

        return \Gini\ImageCache::makeURL($url, $size);
    }

    public function fieldHTML($key, $fieldTemplate)
    {
        $type = isset($fieldTemplate['type']) ? $fieldTemplate['type'] : 'text';
        switch ($type) {
        case 'text_array':
            $v = $this->$key;
            $jv = json_decode($v, true);
            if (is_array($jv)) {
                $v = implode(', ', $jv);
            }

            return H($v);
        default:
            return H($this->$key);
        }
    }

    private static $_chemdbRPC;
    public static function getHazTypes($casNO)
    {
        if (!$casNO) return;
        if (!self::$_chemdbRPC) {
            $conf = \Gini\Config::get('cheml-db.rpc');
            $url = $conf['url'];
            self::$_chemdbRPC = \Gini\IoC::construct('\Gini\RPC', $url);
        }

        $hazArr = [
            'hazardous', //=> T('危险品'),
            'drug_precursor', //=> T('易制毒'),
            'highly_toxic', //=> T('剧毒品'),
        ];

        $rpc = self::$_chemdbRPC;
        $types = (array)$rpc->product->chem->getTypes($casNO);

        if (!empty($types)) {
            $types = (array)array_pop($types);
            return array_diff($hazArr, array_diff($hazArr, $types));
        }
    }
}

