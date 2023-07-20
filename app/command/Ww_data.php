<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\model\wwdata\LypWwDataModel;

class Ww_data extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Ww_data')
            ->setDescription('the Ww_data command');
    }

    protected function execute(Input $input, Output $output)
    {
        ini_set('memory_limit','1000M');

		//生成json文件
		$data = Db::connect("sqlsrv")->Query($this->get_sql());
        $db = Db::connect("mysql");
        if ($data) {
            //先清空旧数据再跑
            $db->Query("truncate table ea_lyp_ww_data;");
            $chunk_list = array_chunk($data, 500);
            foreach($chunk_list as $key => $val) {
                // 基础结果 
                $insert = $db->table('ea_lyp_ww_data')->strict(false)->insertAll($val);
            }
        }
        echo 'okk';die;
    }

    protected function get_sql() {

        return "SELECT 
        CONVERT(VARCHAR(10),ER.RetailDate,23) 日期,
        EC.State 省份,
        EC.CustomerName 店铺名称,
        EM.Mathod 经营模式,
        ERG.Status 销售方式,
        EG.CategoryName1 一级分类,
        SUM(ERG.Quantity) 数量,
        SUM(ERG.Quantity * ERG.DiscountPrice) 金额
    FROM ErpCustomer EC
    LEFT JOIN ErpRetail ER ON EC.CustomerId=ER.CustomerId
    LEFT JOIN ErpRetailGoods ERG ON ER.RetailID=ERG.RetailID
    LEFT JOIN ErpGoods EG ON ERG.GoodsId=EG.GoodsId
    LEFT JOIN ErpBaseCustomerMathod EM ON EC.MathodId=EM.MathodId
    WHERE ER.RetailDate>DATEADD(month, DATEDIFF(month, 0, GETDATE())-2, 0)
        AND ERG.Status IN ('售','促')
        AND ER.CodingCodeText='已审结'
    GROUP BY 
        CONVERT(VARCHAR(10),ER.RetailDate,23),
        EC.State,
        EC.CustomerName,
        EM.Mathod,
        ERG.Status,
        EG.CategoryName1
    ORDER BY 
     CONVERT(VARCHAR(10),ER.RetailDate,23),
     EC.State,
     EM.Mathod,
     ERG.Status,
     EG.CategoryName1
    ;";

    }

}
