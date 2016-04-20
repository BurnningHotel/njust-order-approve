<?php

namespace Gini\ORM;

class Vendor extends Hub\RObject
{
    public $name         = 'string:120';
    public $abbr         = 'string:120';
    public $summary      = 'string:500';
    public $icon         = 'string:250';

    //缓存15分钟
    protected $cacheTimeout = 900;

    public function convertRPCData(array $rdata)
    {
        $data = [];
        $data['name'] = $rdata['name'];
        $data['abbr'] = $rdata['abbr'];
        $data['summary'] = $rdata['summary'];
        $data['icon'] = $rdata['icon'];
        $data['_extra'] = J(array_diff_key($rdata, array_flip(['id', 'name', 'abbr', 'summary', 'icon'])));

        return $data;
    }

    protected function fetchRPC($id)
    {
        $rpc = $this->getRPC('vendor')->mall->vendor;
        return $rpc->getVendor($id);
    }

    /**
     * 获得供应商的简称 (如无简称, 使用全称).
     *
     * @return string
     */
    public function abbr()
    {
        return $this->abbr ?: $this->name;
    }

    public function icon($size = null)
    {
        $url = $this->icon;
        $scheme = parse_url($url)['scheme'];

        if (!$url || ($scheme != 'http')) {
            return (new \Identicon\Identicon())->getImageDataUri(md5(strtolower(trim($this->name.$this->id))), $size);
        }

        return \Gini\ImageCache::makeURL($url, $size);
    }

}
