<?php
/**
* @file Chem.php
* @brief 危化品的商品数据导入
*        正常情况先，初始化方法只在部署时执行
* @author Jinlin Li <jinlin.li@geneegroup.com>
* @version 0.1.0
* @date 2016-01-13
 */

namespace Gini\Controller\CLI\Hazardous;

class Chem extends \Gini\Controller\CLI
{
    /**
        * @brief 命令help
        *
        * @return
     */
    public function __index($params)
    {
        echo "Available commands:\n";
        echo "  gini hazardous chem init: 初始化危化品商品名录表\n";
    }

	/*
	 ** gini hazardous chem init
	 ** 生成危化品商品名录
	 */
	public function actionInit()
	{
		$db = \Gini\Database::db();
        $db->query('truncate hazardous');
        $types = ['drug_precursor', 'highly_toxic', 'hazardous'];
        foreach ($types as $type) {
            $csv = new \Gini\CSV(APP_PATH.'/'.DATA_DIR.'/hazardous/'.$type.'.csv', 'r');
            $csv->read();
            while ($row = $csv->read()) {
                $cas_no = trim($row[1]);
                if (strstr($cas_no,'；')) {
                    $cas_nos = explode('；', $cas_no);
                    foreach ($cas_nos as $cas_no) {
                        if (!$cas_no) continue;
                        $sql = "INSERT INTO `hazardous` (`name`, `cas_no`, `type`) values (".$db->quote($row[0]).",".$db->quote($cas_no).", ".$db->quote($type).")";
                        if (!$db->query($sql)) {
                            var_dump($sql);
                            echo $type."初始化失败 中断\n";
                            die;
                        }
                    }
                }
                else {
                    if (!$cas_no) continue;
                    $sql = "INSERT INTO `hazardous` (`name`, `cas_no`, `type`) values (".$db->quote($row[0]).",".$db->quote($cas_no).", ".$db->quote($type).")";
                    if (!$db->query($sql)) {
                        var_dump($sql);
                        echo $type."初始化失败 中断\n";
                        die;
                    }
                }
            }
            echo $type."初始化完毕\n";
        }
	}
}
