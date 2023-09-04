<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use app\admin\model\bi\SpLypPuhuoTiGoodsModel;
use app\admin\model\bi\SpLypPuhuoTiGoodsTypeModel;
use think\facade\Db;
//可以凌晨 00:01开始跑（预计5分钟跑完）
//1.sp_lyp_puhuo_shangshiday  
class Puhuo_shangshiday extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Puhuo_shangshiday')
            ->setDescription('the Puhuo_shangshiday command');
    }

    protected function execute(Input $input, Output $output) {

        ini_set('memory_limit','200M');
        $db = Db::connect("mysql");
        
        $data = $this->get_kl_data();
        if ($data) {
            
            //先清空旧数据再跑
            $db->Query("truncate table sp_lyp_puhuo_shangshiday;");
            $chunk_list = array_chunk($data, 1000);
            foreach($chunk_list as $key => $val) {
                $insert = $db->table('sp_lyp_puhuo_shangshiday')->strict(false)->insertAll($val);
            }

        }
        echo 'okk';die;
        
    }

    protected function get_kl_data() {

        $sql = "select EC.CustomerId, EC.CustomerName, EG.GoodsNo, MIN(ECS.StockDate) AS StockDate 
        FROM ErpCustomerStock ECS 
        LEFT JOIN ErpGoods EG ON ECS.GoodsId=EG.GoodsId 
        LEFT JOIN ErpCustomer EC ON ECS.CustomerId=EC.CustomerId  
        where EG.TimeCategoryName1>2022 and (EG.TimeCategoryName2 LIKE '%秋%' OR EG.TimeCategoryName2 LIKE '%冬%') 
        group by EC.CustomerId, EC.CustomerName, EG.GoodsNo";

        return Db::connect("sqlsrv")->Query($sql);

    }

}
