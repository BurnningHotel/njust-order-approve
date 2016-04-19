<?php

namespace Gini;

class CSV
{
    private $fh;

    private $is_windows = false;

    private $is_stdout = false;

    private $writable = false;

    private $_delimiter = ',';

//csv中单行设置最大字符长度
    public static $line_max_length = 4096;

    public function __construct($filename, $mode, $alt_name = null)
    {
        if ($filename == 'php://output') {
            header('Content-type:text/x-csv');
            header('Content-Disposition: attachment; filename="'.($alt_name ?: Date::format(null, 'ymdhi')).'.csv"');
            header('Expires: Wed, 01 Jan 1977 00:00:00 GMT');
            header('Cache-Control: no-cache');
            $this->is_stdout = true;
        }

        $this->fh = fopen($filename, $mode);

        $this->writable = (false !== strpos($mode, 'w'));
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $this->is_windows = (false !== strpos($ua, 'Windows'));
        if ($this->is_windows) {
            $this->_delimiter = "\t";
        }
    }

    public function write(array $arr)
    {

        // BOM 头, 使 excel 可以读取 csv
        if (!$this->writable || !$this->fh) {
            return false;
        }

        if (ftell($this->fh) == 0 && $this->is_windows) {
            fwrite($this->fh, "\xFF\xFE");
        }

        if ($this->is_windows) {
            //windows下不会自动进行"转义为""

            $arr = array_map(function ($value) {
                //对于包含" \n 需要进行处理
                if (strpos($value, '"') === false && strpos($value, "\n") === false) {
                    return $value;
                } else {
                    return '"'.str_replace('"', '""', $value).'"';
                }
            }, $arr);

//转换编码
            foreach ($arr as &$v) {
                $v = iconv('UTF-8', 'UCS-2LE', $v);
            }
            $t = iconv('UTF-8', 'UCS-2LE', "\t");
            $n = iconv('UTF-8', 'UCS-2LE', "\r\n");
            fwrite($this->fh, implode($t, $arr).$n);
        } else {
            fputcsv($this->fh, $arr);
        }
    }

    //zhijie.li add $delimiter arg
    public function read()
    {
        $handle = $this->fh;

        if ($handle && !feof($handle)) {
            $str = $this->fgets();

            if ($str) {
                return $this->process($str);
            }
        }

        return;
    }

    //获取一行
    private function fgets()
    {
        $handle = $this->fh;

        //为文件开头, 需判断BOM
        if (ftell($handle) === 0) {
            $bom = fread($handle, 2);
            $str = fgets($handle);
            if ($bom != "\xFF\xFE") {
                $str = $bom.$str;
            } else {
                $this->is_windows = true;
            }
        } else {
            //正文进行正常fgets并获取
            $str = fgets($handle);
        }

        //转码
        if ($this->is_windows) {
            $str .= fread($handle, 1);
            $from_coding = array('UCS-2LE', 'GB10830');
            $str = @mb_convert_encoding($str, 'UTF-8', $from_coding);
        }

        $str = trim($str, "\n\t\r");

        //解决换行问题
        do {
            //进行"匹配，判断匹配个数，如果匹配到了"，说明可能有换行情况存在
            $o = substr_count($str, '"');
            //如果存在"，并且"为奇，则匹配下一行
            if ($o && ($o % 2)) {
                if ($this->is_windows) {
                    $next_line = @mb_convert_encoding(fgets($handle).fread($handle, 1), 'UTF-8', $from_coding);
                } else {
                    $next_line = fgets($handle);
                }

                $str = $str."\n".$next_line;

                //如果str长度超过最长的边界，直接跳出
                if (strlen($str) > self::$line_max_length) {
                    break;
                }
            } else {
                break;
            }
        } while (true);

        return $str;
    }

    //分析获取的数据
    private function process($str)
    {
        //说明该行没有"
        $delimiter = $this->_delimiter;

        if (strpos($str, '"') === false) {
            return explode($delimiter, $str);
        } else {
            $exp = explode($delimiter, $str);
            $count = count($exp);

            $ret = array();

            $i = 0;

            for ($i = 0; $i < $count; ++$i) {

                //存在奇数的"，说明未完结
                $o = substr_count($exp[$i], '"');

                //不存在"
                if (!$o) {
                    $ret[] = $exp[$i];
                } else {
                    if ($o % 2) {
                        $exp[$i + 1] = $exp[$i].$delimiter.$exp[$i + 1];
                    } else {
                        //存在多个"，需要进行简单转义
                        preg_match_all('/^"([\s\S]*)"$/', trim($exp[$i]), $output);
                        $ret[] = current(preg_replace('/""/u', '"', $output[1]));
                    }
                }
            }

            return $ret;
        }
    }

    public function close()
    {
        if ($this->fh) {
            fclose($this->fh);
        }
        if ($this->is_stdout) {
            exit();
        }
    }

    public function import()
    {
    }
}
/*
array(7) {
    [0]=>  string(6) "编号"
    [1]=>  string(6) "日期"
    [2]=>  string(6) "存款"
    [3]=>  string(6) "支出"
    [4]=>  string(6) "余额"
    [5]=>  string(6) "说明"
    [6]=>  string(0) ""
}
array(7) {
    [0]=>  string(6) "000001"
    [1]=>  string(18) "2010/01/21 17:33PM"
    [2]=>  string(11) "楼1,000.00"
    [3]=>  string(7) "楼0.00"
    [4]=>  string(11) "楼1,000.00"
    [5]=>  string(0) ""
    [6]=>  string(0) ""
}
*/
