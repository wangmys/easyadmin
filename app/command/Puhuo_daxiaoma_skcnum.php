<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\model\bi\SpLypPuhuoConfigModel;
//可以凌晨 03:30开始跑(在Puhuo_spsk_stock后面跑)（预计15分钟跑完）
class Puhuo_daxiaoma_skcnum extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('Puhuo_daxiaoma_skcnum')
            ->setDescription('the Puhuo_daxiaoma_skcnum command');
    }

    protected function execute(Input $input, Output $output) {

        ini_set('memory_limit','200M');
        $db = Db::connect("mysql");
        
        $data = $this->get_daxiaoma_goods_data();
        // print_r($data);die;
        
        if ($data) {
            //先清空旧数据再跑
            $db->Query("truncate table sp_lyp_puhuo_daxiaoma_skcnum;");

            $add_data = [];
            $puhuo_config = SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->find();
            foreach ($data as $v_data) {
                // print_r($v_data);die;

                //店铺库存skc各个尺码数
                $sql1 = "select stock_00_goods_str,stock_29_goods_str,stock_34_goods_str,stock_35_goods_str,stock_36_goods_str,stock_38_goods_str,stock_40_goods_str from sp_lyp_puhuo_spsk_stock where yuncang='{$v_data['WarehouseName']}' and category1='{$v_data['CategoryName1']}' and category2='{$v_data['CategoryName2']}' and year='{$v_data['TimeCategoryName1']}' and season='{$v_data['season']}' and style='{$v_data['StyleCategoryName']}'";
                $goods_str = $db->Query($sql1);
                $stock_00_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_00_goods_str')) : '';
                $stock_29_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_29_goods_str')) : '';
                $stock_34_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_34_goods_str')) : '';
                $stock_35_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_35_goods_str')) : '';
                $stock_36_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_36_goods_str')) : '';
                $stock_38_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_38_goods_str')) : '';
                $stock_40_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_40_goods_str')) : '';


                $stock_00_goods_str = $stock_00_goods_str ? explode(',', $stock_00_goods_str) : [];
                $stock_29_goods_str = $stock_29_goods_str ? explode(',', $stock_29_goods_str) : [];
                $stock_34_goods_str = $stock_34_goods_str ? explode(',', $stock_34_goods_str) : [];
                $stock_35_goods_str = $stock_35_goods_str ? explode(',', $stock_35_goods_str) : [];
                $stock_36_goods_str = $stock_36_goods_str ? explode(',', $stock_36_goods_str) : [];
                $stock_38_goods_str = $stock_38_goods_str ? explode(',', $stock_38_goods_str) : [];
                $stock_40_goods_str = $stock_40_goods_str ? explode(',', $stock_40_goods_str) : [];
                // print_r($goods_str);die;

                $Stock_00_goods_str = count(array_unique(array_merge( $stock_00_goods_str, $v_data['Stock_00_goods_str'] ? explode(',', $v_data['Stock_00_goods_str']) : [] )));
                $Stock_29_goods_str = count(array_unique(array_merge( $stock_29_goods_str, $v_data['Stock_29_goods_str'] ? explode(',', $v_data['Stock_29_goods_str']) : [] )));
                $Stock_34_goods_str = count(array_unique(array_merge( $stock_34_goods_str, $v_data['Stock_34_goods_str'] ? explode(',', $v_data['Stock_34_goods_str']) : [] )));
                $Stock_35_goods_str = count(array_unique(array_merge( $stock_35_goods_str, $v_data['Stock_35_goods_str'] ? explode(',', $v_data['Stock_35_goods_str']) : [] )));
                $Stock_36_goods_str = count(array_unique(array_merge( $stock_36_goods_str, $v_data['Stock_36_goods_str'] ? explode(',', $v_data['Stock_36_goods_str']) : [] )));
                $Stock_38_goods_str = count(array_unique(array_merge( $stock_38_goods_str, $v_data['Stock_38_goods_str'] ? explode(',', $v_data['Stock_38_goods_str']) : [] )));
                $Stock_40_goods_str = count(array_unique(array_merge( $stock_40_goods_str, $v_data['Stock_40_goods_str'] ? explode(',', $v_data['Stock_40_goods_str']) : [] )));

                $add = $add_new = [];
                $add['WarehouseName'] = $v_data['WarehouseName'];
                $add['TimeCategoryName1'] = $v_data['TimeCategoryName1'];
                $add['season'] = $v_data['season'];
                $add['CategoryName1'] = $v_data['CategoryName1'];
                $add['CategoryName2'] = $v_data['CategoryName2'];
                $add['StyleCategoryName'] = $v_data['StyleCategoryName'];
                $add['Stock_00_skcnum'] = $Stock_00_goods_str;
                $add['Stock_00_skcnum_small'] = round( ($Stock_00_goods_str*$puhuo_config['smallsize_small'])/100, 0 );
                $add['Stock_00_skcnum_normal'] = round( ($Stock_00_goods_str*$puhuo_config['smallsize_normal'])/100, 0 );
                $add['Stock_00_skcnum_big'] = $Stock_00_goods_str-$add['Stock_00_skcnum_small']-$add['Stock_00_skcnum_normal'];//round( ($Stock_00_goods_str*$puhuo_config['smallsize_big'])/100, 0 );

                $add['Stock_29_skcnum'] = $Stock_29_goods_str;
                $add['Stock_29_skcnum_small'] = round( ($Stock_29_goods_str*$puhuo_config['smallsize_small'])/100, 0 );
                $add['Stock_29_skcnum_normal'] = round( ($Stock_29_goods_str*$puhuo_config['smallsize_normal'])/100, 0 );
                $add['Stock_29_skcnum_big'] = $Stock_29_goods_str-$add['Stock_29_skcnum_small']-$add['Stock_29_skcnum_normal'];//round( ($Stock_29_goods_str*$puhuo_config['smallsize_big'])/100, 0 );

                //大码：
                $add['Stock_34_skcnum'] = $Stock_34_goods_str;
                $add['Stock_34_skcnum_big'] = round( ($Stock_34_goods_str*$puhuo_config['bigsize_big'])/100, 0 );
                $add['Stock_34_skcnum_normal'] = round( ($Stock_34_goods_str*$puhuo_config['bigsize_normal'])/100, 0 );
                $add['Stock_34_skcnum_small'] = $Stock_34_goods_str-$add['Stock_34_skcnum_big']-$add['Stock_34_skcnum_normal'];//round( ($Stock_34_goods_str*$puhuo_config['bigsize_small'])/100, 0 );

                $add['Stock_35_skcnum'] = $Stock_35_goods_str;
                $add['Stock_35_skcnum_big'] = round( ($Stock_35_goods_str*$puhuo_config['bigsize_big'])/100, 0 );
                $add['Stock_35_skcnum_normal'] = round( ($Stock_35_goods_str*$puhuo_config['bigsize_normal'])/100, 0 );
                $add['Stock_35_skcnum_small'] = $Stock_35_goods_str-$add['Stock_35_skcnum_big']-$add['Stock_35_skcnum_normal'];//round( ($Stock_35_goods_str*$puhuo_config['bigsize_small'])/100, 0 );

                $add['Stock_36_skcnum'] = $Stock_36_goods_str;
                $add['Stock_36_skcnum_big'] = round( ($Stock_36_goods_str*$puhuo_config['bigsize_big'])/100, 0 );
                $add['Stock_36_skcnum_normal'] = round( ($Stock_36_goods_str*$puhuo_config['bigsize_normal'])/100, 0 );
                $add['Stock_36_skcnum_small'] = $Stock_36_goods_str-$add['Stock_36_skcnum_big']-$add['Stock_36_skcnum_normal'];//round( ($Stock_36_goods_str*$puhuo_config['bigsize_small'])/100, 0 );

                $add['Stock_38_skcnum'] = $Stock_38_goods_str;
                $add['Stock_38_skcnum_big'] = round( ($Stock_38_goods_str*$puhuo_config['bigsize_big'])/100, 0 );
                $add['Stock_38_skcnum_normal'] = round( ($Stock_38_goods_str*$puhuo_config['bigsize_normal'])/100, 0 );
                $add['Stock_38_skcnum_small'] = $Stock_38_goods_str-$add['Stock_38_skcnum_big']-$add['Stock_38_skcnum_normal'];//round( ($Stock_38_goods_str*$puhuo_config['bigsize_small'])/100, 0 );

                $add['Stock_40_skcnum'] = $Stock_40_goods_str;
                $add['Stock_40_skcnum_big'] = round( ($Stock_40_goods_str*$puhuo_config['bigsize_big'])/100, 0 );
                $add['Stock_40_skcnum_normal'] = round( ($Stock_40_goods_str*$puhuo_config['bigsize_normal'])/100, 0 );
                $add['Stock_40_skcnum_small'] = $Stock_40_goods_str-$add['Stock_40_skcnum_big']-$add['Stock_40_skcnum_normal'];//round( ($Stock_40_goods_str*$puhuo_config['bigsize_small'])/100, 0 );

                //重整字段顺序，方便批量插入
                $add_new['WarehouseName'] = $add['WarehouseName'];
                $add_new['TimeCategoryName1'] = $add['TimeCategoryName1'];
                $add_new['season'] = $add['season'];
                $add_new['CategoryName1'] = $add['CategoryName1'];
                $add_new['CategoryName2'] = $add['CategoryName2'];
                $add_new['StyleCategoryName'] = $add['StyleCategoryName'];

                $add_new['Stock_00_skcnum'] = $add['Stock_00_skcnum'];
                $add_new['Stock_00_skcnum_small'] = $add['Stock_00_skcnum_small'];
                $add_new['Stock_00_skcnum_normal'] = $add['Stock_00_skcnum_normal'];
                $add_new['Stock_00_skcnum_big'] = $add['Stock_00_skcnum_big'];

                $add_new['Stock_29_skcnum'] = $add['Stock_29_skcnum'];
                $add_new['Stock_29_skcnum_small'] = $add['Stock_29_skcnum_small'];
                $add_new['Stock_29_skcnum_normal'] = $add['Stock_29_skcnum_normal'];
                $add_new['Stock_29_skcnum_big'] = $add['Stock_29_skcnum_big'];

                $add_new['Stock_34_skcnum'] = $add['Stock_34_skcnum'];
                $add_new['Stock_34_skcnum_small'] = $add['Stock_34_skcnum_small'];
                $add_new['Stock_34_skcnum_normal'] = $add['Stock_34_skcnum_normal'];
                $add_new['Stock_34_skcnum_big'] = $add['Stock_34_skcnum_big'];

                $add_new['Stock_35_skcnum'] = $add['Stock_35_skcnum'];
                $add_new['Stock_35_skcnum_small'] = $add['Stock_35_skcnum_small'];
                $add_new['Stock_35_skcnum_normal'] = $add['Stock_35_skcnum_normal'];
                $add_new['Stock_35_skcnum_big'] = $add['Stock_35_skcnum_big'];

                $add_new['Stock_36_skcnum'] = $add['Stock_36_skcnum'];
                $add_new['Stock_36_skcnum_small'] = $add['Stock_36_skcnum_small'];
                $add_new['Stock_36_skcnum_normal'] = $add['Stock_36_skcnum_normal'];
                $add_new['Stock_36_skcnum_big'] = $add['Stock_36_skcnum_big'];

                $add_new['Stock_38_skcnum'] = $add['Stock_38_skcnum'];
                $add_new['Stock_38_skcnum_small'] = $add['Stock_38_skcnum_small'];
                $add_new['Stock_38_skcnum_normal'] = $add['Stock_38_skcnum_normal'];
                $add_new['Stock_38_skcnum_big'] = $add['Stock_38_skcnum_big'];

                $add_new['Stock_40_skcnum'] = $add['Stock_40_skcnum'];
                $add_new['Stock_40_skcnum_small'] = $add['Stock_40_skcnum_small'];
                $add_new['Stock_40_skcnum_normal'] = $add['Stock_40_skcnum_normal'];
                $add_new['Stock_40_skcnum_big'] = $add['Stock_40_skcnum_big'];

                //42码，先手动填0
                $add_new['Stock_42_skcnum'] = 0;
                $add_new['Stock_42_skcnum_small'] = 0;
                $add_new['Stock_42_skcnum_normal'] = 0;
                $add_new['Stock_42_skcnum_big'] = 0;

                $add_data[] = $add_new;

            }

            // print_r($add_data);die;

            $chunk_list = array_chunk($add_data, 1000);
            foreach($chunk_list as $key => $val) {
                $insert = $db->table('sp_lyp_puhuo_daxiaoma_skcnum')->strict(false)->insertAll($val);
            }

        }
        echo 'okk';die;
        
    }

    //大小码skc数获取（云仓）
    protected function get_daxiaoma_goods_data() {

        $autumn_config = config('puhuo.autumn');
        $winter_config = config('puhuo.winter');
        
        $sql = "select WarehouseName, TimeCategoryName1, 
        case when TimeCategoryName2 like '%{$autumn_config['name']}%' then '{$autumn_config['fullname']}' when TimeCategoryName2 like '%{$winter_config['name']}%' then '{$winter_config['fullname']}' end as season, 
        CategoryName1,
        CategoryName2,
        StyleCategoryName,
        sum(case when Stock_00>0 then 1 else 0 end) as Stock_00_skcnum,  
        sum(case when Stock_29>0 then 1 else 0 end) as Stock_29_skcnum, 
        sum(case when Stock_34>0 then 1 else 0 end) as Stock_34_skcnum,
        sum(case when Stock_35>0 then 1 else 0 end) as Stock_35_skcnum,
        sum(case when Stock_36>0 then 1 else 0 end) as Stock_36_skcnum,
        sum(case when Stock_38>0 then 1 else 0 end) as Stock_38_skcnum,
        sum(case when Stock_40>0 then 1 else 0 end) as Stock_40_skcnum,
        sum(case when Stock_42>0 then 1 else 0 end) as Stock_42_skcnum,
        
        GROUP_CONCAT(case when Stock_00>0 then GoodsNo else null end) as Stock_00_goods_str,  
        GROUP_CONCAT(case when Stock_29>0 then GoodsNo else null end) as Stock_29_goods_str,  
        GROUP_CONCAT(case when Stock_34>0 then GoodsNo else null end) as Stock_34_goods_str,  
        GROUP_CONCAT(case when Stock_35>0 then GoodsNo else null end) as Stock_35_goods_str,  
        GROUP_CONCAT(case when Stock_36>0 then GoodsNo else null end) as Stock_36_goods_str,  
        GROUP_CONCAT(case when Stock_38>0 then GoodsNo else null end) as Stock_38_goods_str,  
        GROUP_CONCAT(case when Stock_40>0 then GoodsNo else null end) as Stock_40_goods_str,  
        GROUP_CONCAT(case when Stock_42>0 then GoodsNo else null end) as Stock_42_goods_str,  
        
        GROUP_CONCAT(GoodsNo) as goods_str
         from sp_lyp_puhuo_wait_goods 
        where  (TimeCategoryName2 like '%{$autumn_config['name']}%' or TimeCategoryName2 like '%{$winter_config['name']}%') 
         group by WarehouseName, TimeCategoryName1, season, CategoryName1, CategoryName2, StyleCategoryName;";

         return Db::connect("mysql")->Query($sql);

    }

}
