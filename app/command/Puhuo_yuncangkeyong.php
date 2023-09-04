<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\model\bi\SpLypPuhuoYuncangkeyongModel;
use app\admin\model\bi\SpLypPuhuoConfigModel;
//可以凌晨 00:01开始跑（预计10分钟跑完）
class Puhuo_yuncangkeyong extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Puhuo_yuncangkeyong')
            ->setDescription('the Puhuo_yuncangkeyong command');
    }

    protected function execute(Input $input, Output $output) {

        ini_set('memory_limit','500M');
        $db = Db::connect("mysql");
        $db->Query("truncate table sp_lyp_puhuo_yuncangkeyong;");
        
        $data = $this->get_kl_data();
        if ($data) {

            $puhuo_config = SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->field('warehouse_reserve_smallsize,warehouse_reserve_mainsize,warehouse_reserve_bigsize,yuliu_num')->find();
            foreach ($data as $v_data) {
                
                $v_data['Lingxing'] = $v_data['CategoryName'] ? mb_substr($v_data['CategoryName'], 0, 2) : '';
                if ($v_data['Stock_Quantity'] >= $puhuo_config['yuliu_num']) {//大于200件的才作预留

                    $warehouse_reserve_smallsize = $puhuo_config['warehouse_reserve_smallsize']/100;
                    $warehouse_reserve_mainsize = $puhuo_config['warehouse_reserve_mainsize']/100;
                    $warehouse_reserve_bigsize = $puhuo_config['warehouse_reserve_bigsize']/100;
                    if (in_array($v_data['CategoryName1'], ['内搭', '外套', '鞋履']) || ($v_data['CategoryName1']=='下装' && strstr($v_data['CategoryName2'], '松紧'))) {

                        //小码
                        $v_data['Stock_00_yuliu'] = round($warehouse_reserve_smallsize*$v_data['Stock_00'], 0);
                        $v_data['Stock_00_puhuo'] = $v_data['Stock_00']-$v_data['Stock_00_yuliu'];
                        $v_data['Stock_29_yuliu'] = round($warehouse_reserve_smallsize*$v_data['Stock_29'], 0);
                        $v_data['Stock_29_puhuo'] = $v_data['Stock_29']-$v_data['Stock_29_yuliu'];

                        //主码
                        $v_data['Stock_30_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_30'], 0);
                        $v_data['Stock_30_puhuo'] = $v_data['Stock_30']-$v_data['Stock_30_yuliu'];
                        $v_data['Stock_31_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_31'], 0);
                        $v_data['Stock_31_puhuo'] = $v_data['Stock_31']-$v_data['Stock_31_yuliu'];
                        $v_data['Stock_32_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_32'], 0);
                        $v_data['Stock_32_puhuo'] = $v_data['Stock_32']-$v_data['Stock_32_yuliu'];
                        $v_data['Stock_33_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_33'], 0);
                        $v_data['Stock_33_puhuo'] = $v_data['Stock_33']-$v_data['Stock_33_yuliu'];

                        //大码
                        $v_data['Stock_34_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_34'], 0);
                        $v_data['Stock_34_puhuo'] = $v_data['Stock_34']-$v_data['Stock_34_yuliu'];
                        $v_data['Stock_35_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_35'], 0);
                        $v_data['Stock_35_puhuo'] = $v_data['Stock_35']-$v_data['Stock_35_yuliu'];

                        $v_data['Stock_Quantity_yuliu'] = $v_data['Stock_00_yuliu'] + $v_data['Stock_29_yuliu'] + $v_data['Stock_30_yuliu'] + $v_data['Stock_31_yuliu'] + $v_data['Stock_32_yuliu'] + $v_data['Stock_33_yuliu'] + $v_data['Stock_34_yuliu'] + $v_data['Stock_35_yuliu'];

                    } else {

                        //小码
                        $v_data['Stock_00_yuliu'] = round($warehouse_reserve_smallsize*$v_data['Stock_00'], 0);
                        $v_data['Stock_00_puhuo'] = $v_data['Stock_00']-$v_data['Stock_00_yuliu'];
                        
                        //主码
                        $v_data['Stock_29_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_29'], 0);
                        $v_data['Stock_29_puhuo'] = $v_data['Stock_29']-$v_data['Stock_29_yuliu'];
                        $v_data['Stock_30_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_30'], 0);
                        $v_data['Stock_30_puhuo'] = $v_data['Stock_30']-$v_data['Stock_30_yuliu'];
                        $v_data['Stock_31_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_31'], 0);
                        $v_data['Stock_31_puhuo'] = $v_data['Stock_31']-$v_data['Stock_31_yuliu'];
                        $v_data['Stock_32_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_32'], 0);
                        $v_data['Stock_32_puhuo'] = $v_data['Stock_32']-$v_data['Stock_32_yuliu'];
                        $v_data['Stock_33_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_33'], 0);
                        $v_data['Stock_33_puhuo'] = $v_data['Stock_33']-$v_data['Stock_33_yuliu'];
                        $v_data['Stock_34_yuliu'] = round($warehouse_reserve_mainsize*$v_data['Stock_34'], 0);
                        $v_data['Stock_34_puhuo'] = $v_data['Stock_34']-$v_data['Stock_34_yuliu'];

                        //大码
                        $v_data['Stock_35_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_35'], 0);
                        $v_data['Stock_35_puhuo'] = $v_data['Stock_35']-$v_data['Stock_35_yuliu'];
                        $v_data['Stock_36_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_36'], 0);
                        $v_data['Stock_36_puhuo'] = $v_data['Stock_36']-$v_data['Stock_36_yuliu'];
                        $v_data['Stock_38_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_38'], 0);
                        $v_data['Stock_38_puhuo'] = $v_data['Stock_38']-$v_data['Stock_38_yuliu'];
                        $v_data['Stock_40_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_40'], 0);
                        $v_data['Stock_40_puhuo'] = $v_data['Stock_40']-$v_data['Stock_40_yuliu'];
                        $v_data['Stock_42_yuliu'] = round($warehouse_reserve_bigsize*$v_data['Stock_42'], 0);
                        $v_data['Stock_42_puhuo'] = $v_data['Stock_42']-$v_data['Stock_42_yuliu'];
                        
                        $v_data['Stock_Quantity_yuliu'] = $v_data['Stock_00_yuliu'] + $v_data['Stock_29_yuliu'] + $v_data['Stock_30_yuliu'] + $v_data['Stock_31_yuliu'] + $v_data['Stock_32_yuliu'] + $v_data['Stock_33_yuliu'] + $v_data['Stock_34_yuliu'] + $v_data['Stock_35_yuliu'] + $v_data['Stock_36_yuliu'] + $v_data['Stock_38_yuliu'] + $v_data['Stock_40_yuliu'] + $v_data['Stock_42_yuliu'];
                    }

                    $v_data['Stock_Quantity_puhuo'] = $v_data['Stock_Quantity']-$v_data['Stock_Quantity_yuliu'];

                } else {//小于200件的，全铺
                    $v_data['Stock_00_puhuo'] = $v_data['Stock_00'];
                    $v_data['Stock_29_puhuo'] = $v_data['Stock_29'];
                    $v_data['Stock_30_puhuo'] = $v_data['Stock_30'];
                    $v_data['Stock_31_puhuo'] = $v_data['Stock_31'];
                    $v_data['Stock_32_puhuo'] = $v_data['Stock_32'];
                    $v_data['Stock_33_puhuo'] = $v_data['Stock_33'];
                    $v_data['Stock_34_puhuo'] = $v_data['Stock_34'];
                    $v_data['Stock_35_puhuo'] = $v_data['Stock_35'];
                    $v_data['Stock_36_puhuo'] = $v_data['Stock_36'];
                    $v_data['Stock_38_puhuo'] = $v_data['Stock_38'];
                    $v_data['Stock_40_puhuo'] = $v_data['Stock_40'];
                    $v_data['Stock_42_puhuo'] = $v_data['Stock_42'];
                    $v_data['Stock_Quantity_puhuo'] = $v_data['Stock_Quantity'];
                }
                // print_r($v_data);die;
                SpLypPuhuoYuncangkeyongModel::create($v_data);

            }
            
            //生成铺货 货品数据
            $data = $this->get_wait_goods_data();

            $chunk_list = $data ? array_chunk($data, 500) : [];
            if ($chunk_list) {
                Db::startTrans();
                try {
                    //先清空旧数据再跑
                    $db->Query("truncate table sp_lyp_puhuo_wait_goods;");
                    foreach($chunk_list as $key => $val) {
                        $insert = $db->table('sp_lyp_puhuo_wait_goods')->strict(false)->insertAll($val);
                    }
                    Db::commit();
                } catch (\Exception $e) {
                    Db::rollback();
                }
            }


        }
        echo 'okk';die;
        
    }

    protected function get_kl_data() {

        $sql = "SELECT 

        T.WarehouseName,
    
        -- EG.GoodsNo,
    
        EG.TimeCategoryName1,
    
        EG.TimeCategoryName2,
    
        EG.CategoryName1,
    
        EG.CategoryName2,
    
        EG.CategoryName,
    
        EG.GoodsName,
    
        EG.StyleCategoryName,
    
        EG.GoodsNo,
    
        EG.StyleCategoryName1,
    
        EG.StyleCategoryName2,
        
        EGC.ColorDesc,
        
        EGPT.UnitPrice,
    
        SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END) AS [Stock_00],
    
        SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0 END) AS [Stock_29],
    
        SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0 END) AS [Stock_30],
    
        SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0 END) AS [Stock_31],
    
        SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0 END) AS [Stock_32],
    
        SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0 END) AS [Stock_33],
    
        SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0 END) AS [Stock_34],
    
        SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0 END) AS [Stock_35],
    
        SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0 END) AS [Stock_36],
    
        SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END) AS [Stock_38],
    
        SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END) AS [Stock_40],
        
        SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END) AS [Stock_42],
    
        SUM(T.Quantity) AS Stock_Quantity,
    
        CASE WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%111111111111%' THEN 12 
        
                WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%11111111111%' THEN 11 
    
                WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%1111111111%' THEN 10 
    
                WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%111111111%' THEN 9
    
                WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%11111111%' THEN 8
    
                WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%1111111%' THEN 7
    
             WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%111111%' THEN 6
    
                WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%11111%' THEN 5
    
                 WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%1111%' THEN 4
    
                 WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%111%' THEN 3
    
                 WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%11%' THEN 2
    
                 WHEN CONCAT(CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=1 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=2 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=3 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=4 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=5 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=6 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=7 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=8 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=9 THEN T.Quantity ELSE 0  END)>0 THEN 1 ELSE 0 END , 
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=10 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
    
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=11 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END ,
                     CASE WHEN SUM(CASE WHEN EBGS.ViewOrder=12 THEN T.Quantity ELSE 0 END)>0 THEN 1 ELSE 0 END 
                     
                     ) LIKE '%1%' THEN 1
    
                 ELSE 0
    
            END AS qima 
    
    FROM 
    
    (
    
    SELECT 
    
        EW.WarehouseName,
    
        EWS.GoodsId,
    
        EWSD.SizeId,
    
        SUM(EWSD.Quantity) AS Quantity
    
    FROM ErpWarehouseStock EWS
    
    LEFT JOIN ErpWarehouseStockDetail EWSD ON EWS.StockId=EWSD.StockId
    
    LEFT JOIN ErpWarehouse EW ON EWS.WarehouseId=EW.WarehouseId
    
    LEFT JOIN ErpGoods EG ON EWS.GoodsId=EG.GoodsId
    
    WHERE EG.TimeCategoryName1>2022
    
        AND EG.CategoryName1 NOT IN ('物料','人事物料')
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
    
    GROUP BY  
    
        EW.WarehouseName,
    
        EWS.GoodsId,
    
        EWSD.SizeId
    
    
    
    UNION ALL 
    
    --出货指令单占用库存
    
    SELECT
    
        EW.WarehouseName,
    
        ESG.GoodsId,
    
        ESGD.SizeId,
    
        -SUM ( ESGD.Quantity ) AS SumQuantity
    
    FROM ErpSorting ES
    
    LEFT JOIN ErpSortingGoods ESG ON ES.SortingID= ESG.SortingID
    
    LEFT JOIN ErpSortingGoodsDetail ESGD ON ESG.SortingGoodsID=ESGD.SortingGoodsID
    
    LEFT JOIN ErpWarehouse EW ON ES.WarehouseId=EW.WarehouseId
    
    LEFT JOIN ErpGoods EG ON ESG.GoodsId=EG.GoodsId
    
    WHERE	 (ES.CodingCode= 'StartNode1'
    
                        OR (ES.CodingCode= 'EndNode2' AND ES.IsCompleted= 0 )
    
                    ) 
    
        AND EG.TimeCategoryName1>2022
    
        AND EG.CategoryName1 NOT IN ('物料','人事物料')
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
    
    GROUP BY
    
        EW.WarehouseName,
    
        ESG.GoodsId,
    
        ESGD.SizeId
    
    
    
    UNION ALL
    
        --仓库出货单占用库存
    
    SELECT
    
        EW.WarehouseName,
    
        EDG.GoodsId,
    
        EDGD.SizeId,
    
        -SUM ( EDGD.Quantity ) AS SumQuantity
    
    FROM ErpDelivery ED
    
    LEFT JOIN ErpDeliveryGoods EDG ON ED.DeliveryID= EDG.DeliveryID
    
    LEFT JOIN ErpDeliveryGoodsDetail EDGD ON EDG.DeliveryGoodsID=EDGD.DeliveryGoodsID
    
    LEFT JOIN ErpWarehouse EW ON ED.WarehouseId=EW.WarehouseId
    
    LEFT JOIN ErpGoods EG ON EDG.GoodsId=EG.GoodsId
    
    WHERE ED.CodingCode= 'StartNode1' 
    
        AND EDG.SortingID IS NULL
    
        AND EG.TimeCategoryName1>2022
    
        AND EG.CategoryName1 NOT IN ('物料','人事物料')
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
    
    GROUP BY
    
        EW.WarehouseName,
    
        EDG.GoodsId,
    
        EDGD.SizeId
    
    
    
    UNION ALL
    
        --采购退货指令单占用库存
    
    SELECT
    
        EW.WarehouseName,
    
        EPRNG.GoodsId,
    
        EPRNGD.SizeId,
    
        -SUM ( EPRNGD.Quantity ) AS SumQuantity
    
    FROM ErpPuReturnNotice EPRN
    
    LEFT JOIN ErpPuReturnNoticeGoods EPRNG ON EPRN.PuReturnNoticeId= EPRNG.PuReturnNoticeId
    
    LEFT JOIN ErpPuReturnNoticeGoodsDetail EPRNGD ON EPRNG.PuReturnNoticeGoodsId=EPRNGD.PuReturnNoticeGoodsId
    
    LEFT JOIN ErpWarehouse EW ON EPRN.WarehouseId=EW.WarehouseId
    
    LEFT JOIN ErpGoods EG ON EPRNG.GoodsId=EG.GoodsId
    
    WHERE (EPRN.IsCompleted = 0 OR EPRN.IsCompleted IS NULL) 
    
        AND EG.TimeCategoryName1>2022
    
        AND EG.CategoryName1 NOT IN ('物料','人事物料')
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
    
    GROUP BY
    
        EW.WarehouseName,
    
        EPRNG.GoodsId,
    
        EPRNGD.SizeId
    
    
    
    UNION ALL
    
        --采购退货单占用库存
    
    SELECT
    
        EW.WarehouseName,
    
        EPCRG.GoodsId,
    
        EPCRGD.SizeId,
    
        -SUM ( EPCRGD.Quantity ) AS SumQuantity
    
    FROM ErpPurchaseReturn EPCR
    
    LEFT JOIN ErpPurchaseReturnGoods EPCRG ON EPCR.PurchaseReturnId= EPCRG.PurchaseReturnId
    
    LEFT JOIN ErpPurchaseReturnGoodsDetail EPCRGD ON EPCRG.PurchaseReturnGoodsId=EPCRGD.PurchaseReturnGoodsId
    
    LEFT JOIN ErpWarehouse EW ON EPCR.WarehouseId=EW.WarehouseId
    
    LEFT JOIN ErpGoods EG ON EPCRG.GoodsId=EG.GoodsId
    
    WHERE EPCR.CodingCode= 'StartNode1'
    
        AND EG.TimeCategoryName1>2022
    
        AND EG.CategoryName1 NOT IN ('物料','人事物料')
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
    
    GROUP BY
    
        EW.WarehouseName,
    
        EPCRG.GoodsId,
    
        EPCRGD.SizeId
    
    
    
    UNION ALL
    
        --仓库调拨占用库存
    
    SELECT
    
        EW.WarehouseName,
    
        EIG.GoodsId,
    
        EIGD.SizeId,
    
        -SUM ( EIGD.Quantity ) AS SumQuantity
    
    FROM ErpInstruction EI
    
    LEFT JOIN ErpInstructionGoods EIG ON EI.InstructionId= EIG.InstructionId
    
    LEFT JOIN ErpInstructionGoodsDetail EIGD ON EIG.InstructionGoodsId=EIGD.InstructionGoodsId
    
    LEFT JOIN ErpWarehouse EW ON EI.OutItemId=EW.WarehouseId
    
    LEFT JOIN ErpGoods EG ON EIG.GoodsId=EG.GoodsId
    
    WHERE EI.Type= 1
    
        AND (EI.CodingCode= 'StartNode1' OR (EI.CodingCode= 'EndNode2' AND EI.IsCompleted=0 ))
    
        AND EG.TimeCategoryName1>2022
    
        AND EG.CategoryName1 NOT IN ('物料','人事物料')
    
        AND EW.WarehouseName IN ('广州云仓','长沙云仓','南昌云仓','武汉云仓','贵阳云仓')
    
    GROUP BY
    
        EW.WarehouseName,
    
        EIG.GoodsId,
    
        EIGD.SizeId
    
    
    
    ) T
    
    LEFT JOIN ErpBaseGoodsSize EBGS ON T.SizeId=EBGS.SizeId 
    
    LEFT JOIN ErpGoods EG ON T.GoodsId=EG.GoodsId 
    
    LEFT JOIN ErpGoodsColor EGC ON EG.GoodsId=EGC.GoodsId   
    
    LEFT JOIN (SELECT 
                                    EGPT.GoodsId, 
                                    SUM(CASE WHEN EGPT.PriceName='零售价' THEN EGPT.UnitPrice ELSE NULL END) AS UnitPrice,
                                    SUM(CASE WHEN EGPT.PriceName='成本价' THEN EGPT.UnitPrice ELSE NULL END) AS CostPrice
                                FROM ErpGoodsPriceType EGPT
                                GROUP BY EGPT.GoodsId ) EGPT ON EG.GoodsId=EGPT.GoodsId 
    
    GROUP BY 
    
        T.WarehouseName,
    
        EG.GoodsNo,
    
        EG.TimeCategoryName1,
    
        EG.TimeCategoryName2,
    
        EG.CategoryName1,
    
        EG.CategoryName2,
    
        EG.CategoryName,
    
        EG.GoodsName,
    
        EG.StyleCategoryName,
    
        EG.GoodsNo,
    
        EG.StyleCategoryName1,
    
        EG.StyleCategoryName2,
        
        EGC.ColorDesc,
        
        EGPT.UnitPrice 
    HAVING  SUM(T.Quantity) >0
    
    ;";

        return Db::connect("sqlsrv")->Query($sql);

    }

    //可以铺货的货品数据
    protected function get_wait_goods_data() {

        $puhuo_config = SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->find();
        $warehouse_qima_nd = $puhuo_config ? $puhuo_config['warehouse_qima_nd'] : 0;
        $warehouse_qima_xz = $puhuo_config ? $puhuo_config['warehouse_qima_xz'] : 0;
        $store_puhuo_lianma_nd = $puhuo_config ? $puhuo_config['store_puhuo_lianma_nd'] : 0;//中间码连码个数 （有可能为：2，3，4）
        $store_puhuo_lianma_xz = $puhuo_config ? $puhuo_config['store_puhuo_lianma_xz'] : 0;//中间码连码个数（有可能为：2，3，4，5，6）

        $sql_store_puhuo_lianma_nd = $sql_store_puhuo_lianma_xz = '';
        //内搭、外套、鞋履、松紧裤
        if ($store_puhuo_lianma_nd == 2) {//中间码，2连码

            $sql_store_puhuo_lianma_nd = " 
            or (CategoryName1 in ('内搭', '外套', '鞋履') and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0) or  (Stock_32_puhuo >0 and Stock_33_puhuo >0) )) 
            or (CategoryName1 = '下装' and CategoryName2 like '%松紧%' and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0) or  (Stock_32_puhuo >0 and Stock_33_puhuo >0) )) ";

        } elseif ($store_puhuo_lianma_nd == 3) {

            $sql_store_puhuo_lianma_nd = " 
            or (CategoryName1 in ('内搭', '外套', '鞋履') and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) )) 
            or (CategoryName1 = '下装' and CategoryName2 like '%松紧%' and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) )) ";

        } elseif ($store_puhuo_lianma_nd == 4) {

            $sql_store_puhuo_lianma_nd = " 
            or (CategoryName1 in ('内搭', '外套', '鞋履') and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0)  )) 
            or (CategoryName1 = '下装' and CategoryName2 like '%松紧%' and (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) ) ";

        } else {//其他默认使用3 

            $sql_store_puhuo_lianma_nd = " 
            or (CategoryName1 in ('内搭', '外套', '鞋履') and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) )) 
            or (CategoryName1 = '下装' and CategoryName2 like '%松紧%' and ( (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0)  or  (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) )) ";

        }

        //下装
        if ($store_puhuo_lianma_xz == 2) {

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( 
                (Stock_29_puhuo >0 and Stock_30_puhuo >0) or 
                (Stock_30_puhuo >0 and Stock_31_puhuo >0) or 
                (Stock_31_puhuo >0 and Stock_32_puhuo >0) or 
                (Stock_32_puhuo >0 and Stock_33_puhuo >0) or   
                (Stock_33_puhuo >0 and Stock_34_puhuo >0)   
                )
                )  ";

        } elseif ($store_puhuo_lianma_xz == 3) {

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( 
                (Stock_29_puhuo >0 and Stock_30_puhuo >0 and Stock_31_puhuo >0) or 
                (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0) or 
                (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) or 
                (Stock_32_puhuo >0 and Stock_33_puhuo >0 and Stock_34_puhuo >0)  
                )
                )  ";

        } elseif ($store_puhuo_lianma_xz == 4) {

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( 
                (Stock_29_puhuo >0 and Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0) or 
                (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) or 
                (Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0 and Stock_34_puhuo >0)  
                )
                )  ";

        } elseif ($store_puhuo_lianma_xz == 5) {

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( (Stock_29_puhuo >0 and Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) or 
                (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0 and Stock_34_puhuo >0) )
                )  ";

        } elseif ($store_puhuo_lianma_xz == 6) {

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( (Stock_29_puhuo >0 and Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0 and Stock_34_puhuo >0) )
                )  ";

        } else {//其他默认使用5

            $sql_store_puhuo_lianma_xz = " or (
                CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and 
                ( (Stock_29_puhuo >0 and Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0) or 
                (Stock_30_puhuo >0 and Stock_31_puhuo >0 and Stock_32_puhuo >0 and Stock_33_puhuo >0 and Stock_34_puhuo >0) )
                )  ";

        }

        $sql = "select WarehouseName,TimeCategoryName1,TimeCategoryName2,CategoryName1,CategoryName2, CategoryName, GoodsName, StyleCategoryName, GoodsNo, StyleCategoryName1, StyleCategoryName2, Lingxing, UnitPrice, ColorDesc, Stock_00_puhuo, Stock_00_puhuo as Stock_00, Stock_29_puhuo, Stock_29_puhuo as Stock_29, Stock_30_puhuo, Stock_30_puhuo as Stock_30, Stock_31_puhuo, Stock_31_puhuo as Stock_31, Stock_32_puhuo, Stock_32_puhuo as Stock_32, Stock_33_puhuo, Stock_33_puhuo as Stock_33, Stock_34_puhuo, Stock_34_puhuo as Stock_34, Stock_35_puhuo, Stock_35_puhuo as Stock_35, Stock_36_puhuo, Stock_36_puhuo as Stock_36, Stock_38_puhuo, Stock_38_puhuo as Stock_38, Stock_40_puhuo, Stock_40_puhuo as Stock_40, Stock_42_puhuo, Stock_42_puhuo as Stock_42, Stock_Quantity_puhuo, Stock_Quantity_puhuo as Stock_Quantity, qima, (case when 
        (CategoryName1 in ('内搭', '外套', '鞋履') and qima>=$warehouse_qima_nd) 
        or (CategoryName1 = '下装' and CategoryName2 like '%松紧%' and qima>=$warehouse_qima_nd) 
        or (CategoryName1 = '下装' and CategoryName2 not like '%松紧%' and qima>=$warehouse_qima_xz)  

        $sql_store_puhuo_lianma_nd 

        $sql_store_puhuo_lianma_xz 

        then 1 else 0 end) as can_puhuo from sp_lyp_puhuo_yuncangkeyong where 1 having  can_puhuo=1;";
        // echo $sql;die;

        return Db::connect("mysql")->Query($sql);

    }

}
