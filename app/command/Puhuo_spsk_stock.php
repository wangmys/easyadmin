<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;
use app\admin\model\bi\SpLypPuhuoTiGoodsModel;
use app\admin\model\bi\SpLypPuhuoTiGoodsTypeModel;
use app\admin\model\bi\SpLypPuhuoConfigModel;

//每天凌晨03:30跑，预计10分钟跑完
//1.sp_lyp_puhuo_spsk_stock  2.sp_lyp_puhuo_daxiaoma_skcnum   3.sp_lyp_puhuo_ti_goods
class Puhuo_spsk_stock extends Command
{
    protected $db_easy;
    protected function configure()
    {
        // 指令配置
        $this->setName('Puhuo_spsk_stock')
            ->setDescription('the Puhuo_spsk_stock command');
        $this->db_easy = Db::connect("mysql");
    }

    protected function execute(Input $input, Output $output) {

        //test... 配合雅婷调试数据使用.
        // $this->deal_daxiaoma_skcnum();die;
        // $this->deal_ti_goods();die;


        ini_set('memory_limit','200M');
        
        $data = $this->get_spsk_stock_data();
        // print_r($data);die;
        if ($data) {
            
            //先清空旧数据再跑
            $this->db_easy->Query("truncate table sp_lyp_puhuo_spsk_stock;");
            $chunk_list = array_chunk($data, 500);
            foreach($chunk_list as $key => $val) {
                $insert = $this->db_easy->table('sp_lyp_puhuo_spsk_stock')->strict(false)->insertAll($val);
            }

            ###############处理大小码skc数(云仓+店铺库存skc)##############################################
            $this->deal_daxiaoma_skcnum();


            #################剔除 指定款处理##################
            $this->deal_ti_goods();


        }
        echo 'okk';die;
        
    }

    protected function get_spsk_stock_data() {

        //剔除的货品
        $ti_goods = SpLypPuhuoTiGoodsModel::where([])->column('GoodsNo');
        $ti_goods = get_goods_str($ti_goods);

        $sql = "select 云仓 as yuncang,店铺名称 as store_name,sum(预计库存数量) as yuji_stock_num,count(货号) as skc_num,年份 as year,
        (CASE WHEN 季节 like '%春%' THEN '春季' WHEN 季节 like '%夏%' THEN '夏季' WHEN 季节 like '%秋%' THEN '秋季' WHEN 季节 like '%冬%' THEN '冬季' ELSE NULL END) as season,
        一级分类 as category1,二级分类 as category2,风格 as style
        ,group_concat(货号) as goods_str 
        ,group_concat(case when (`预计00/28/37/44/100/160/S`>0) then 货号 else null end) as stock_00_goods_str 
        ,group_concat(case when (`预计29/38/46/105/165/M`>0) then 货号 else null end) as stock_29_goods_str 
        ,group_concat(case when (`预计34/43/56/190/4XL`>0) then 货号 else null end) as stock_34_goods_str 
        ,group_concat(case when (`预计35/44/58/195/5XL`>0) then 货号 else null end) as stock_35_goods_str 
        ,group_concat(case when (`预计36/6XL`>0) then 货号 else null end) as stock_36_goods_str 
        ,group_concat(case when (`预计38/7XL`>0) then 货号 else null end) as stock_38_goods_str 
        ,group_concat(case when (`预计_40`>0) then 货号 else null end) as stock_40_goods_str
        from sp_sk  
        where 预计库存数量>0 and 货号 not in ($ti_goods) 
        group by yuncang,store_name,year,season,category1,category2,style;";

        return $this->db_easy->Query($sql);

    }

    protected function deal_daxiaoma_skcnum() {

        $data = $this->get_daxiaoma_goods_data();
        // print_r($data);die;
        
        if ($data) {
            //先清空旧数据再跑
            $this->db_easy->Query("truncate table sp_lyp_puhuo_daxiaoma_skcnum;");

            $add_data = [];
            $puhuo_config = SpLypPuhuoConfigModel::where([['config_str', '=', 'puhuo_config']])->find();
            foreach ($data as $v_data) {
                // print_r($v_data);die;
                //test....
                // $v_data['WarehouseName'] = '贵阳云仓';
                // $v_data['CategoryName1'] = '内搭';
                // $v_data['CategoryName2'] = '休闲长衬';
                // $v_data['TimeCategoryName1'] = '2023';
                // $v_data['season'] = '秋季';
                // $v_data['StyleCategoryName'] = '基本款';
                // $v_data['Stock_00_goods_str'] = 'B52501001,B52501027,B62501001,B52501087,B62501003,B52501018,B62501006,B62501005,B52501239,B52501015,B52501002,B52501016,B52501012';
                // $v_data['Stock_29_goods_str'] = 'B62110268,B52110116,B52110227,B62110174,B52110115,B62110165,B62110167,B52110135';
                // $v_data['Stock_34_goods_str'] = 'B52106011,B52106054,B52106007,B52106008,B52106114,B52106009,B52106269,B52106179';
                // $v_data['Stock_35_goods_str'] = 'B52502009,B52502013,B61502093,B61502236,B62502184,B51502006,B52502001,B62502004,B62502005,B62502001,B52502002,B51502003';
                // $v_data['Stock_36_goods_str'] = 'B52502009,B52502013,B61502093,B61502236,B62502184,B51502006,B52502001,B62502004,B62502005,B62502001,B52502002,B51502003';
                // $v_data['Stock_38_goods_str'] = 'B52502013,B61502093,B61502236,B62502184,B51502006,B62502004,B62502005,B51502003';
                // $v_data['Stock_40_goods_str'] = null;


                //店铺库存skc各个尺码数
                $sql1 = "select stock_00_goods_str,stock_29_goods_str,stock_34_goods_str,stock_35_goods_str,stock_36_goods_str,stock_38_goods_str,stock_40_goods_str from sp_lyp_puhuo_spsk_stock where yuncang='{$v_data['WarehouseName']}' and category1='{$v_data['CategoryName1']}' and category2='{$v_data['CategoryName2']}' and year='{$v_data['TimeCategoryName1']}' and season='{$v_data['season']}' and style='{$v_data['StyleCategoryName']}'";
                // echo $sql1;die;
                $goods_str = $this->db_easy->Query($sql1);
                $stock_00_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_00_goods_str')) : '';
                $stock_29_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_29_goods_str')) : '';
                $stock_34_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_34_goods_str')) : '';
                $stock_35_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_35_goods_str')) : '';
                $stock_36_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_36_goods_str')) : '';
                $stock_38_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_38_goods_str')) : '';
                $stock_40_goods_str = $goods_str ? implode(',', array_column($goods_str, 'stock_40_goods_str')) : '';


                $stock_00_goods_str = $stock_00_goods_str ? array_filter(explode(',', $stock_00_goods_str)) : [];
                $stock_29_goods_str = $stock_29_goods_str ? array_filter(explode(',', $stock_29_goods_str)) : [];
                $stock_34_goods_str = $stock_34_goods_str ? array_filter(explode(',', $stock_34_goods_str)) : [];
                $stock_35_goods_str = $stock_35_goods_str ? array_filter(explode(',', $stock_35_goods_str)) : [];
                $stock_36_goods_str = $stock_36_goods_str ? array_filter(explode(',', $stock_36_goods_str)) : [];
                $stock_38_goods_str = $stock_38_goods_str ? array_filter(explode(',', $stock_38_goods_str)) : [];
                $stock_40_goods_str = $stock_40_goods_str ? array_filter(explode(',', $stock_40_goods_str)) : [];

                // print_r(array_values(   array_unique(array_merge( $stock_34_goods_str, $v_data['Stock_34_goods_str'] ? explode(',', $v_data['Stock_34_goods_str']) : [] ))     ));die;

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
                $add['Stock_00_skcnum_big'] = $Stock_00_goods_str-$add['Stock_00_skcnum_small']-$add['Stock_00_skcnum_normal'];

                $add['Stock_29_skcnum'] = $Stock_29_goods_str;
                $add['Stock_29_skcnum_small'] = round( ($Stock_29_goods_str*$puhuo_config['smallsize_small'])/100, 0 );
                $add['Stock_29_skcnum_normal'] = round( ($Stock_29_goods_str*$puhuo_config['smallsize_normal'])/100, 0 );
                $add['Stock_29_skcnum_big'] = $Stock_29_goods_str-$add['Stock_29_skcnum_small']-$add['Stock_29_skcnum_normal'];

                //大码：
                $add['Stock_34_skcnum'] = $Stock_34_goods_str;
                $add['Stock_34_skcnum_big'] = round( ($Stock_34_goods_str*$puhuo_config['bigsize_big'])/100, 0 );
                $add['Stock_34_skcnum_normal'] = round( ($Stock_34_goods_str*$puhuo_config['bigsize_normal'])/100, 0 );
                $add['Stock_34_skcnum_small'] = $Stock_34_goods_str-$add['Stock_34_skcnum_big']-$add['Stock_34_skcnum_normal'];

                $add['Stock_35_skcnum'] = $Stock_35_goods_str;
                $add['Stock_35_skcnum_big'] = round( ($Stock_35_goods_str*$puhuo_config['bigsize_big'])/100, 0 );
                $add['Stock_35_skcnum_normal'] = round( ($Stock_35_goods_str*$puhuo_config['bigsize_normal'])/100, 0 );
                $add['Stock_35_skcnum_small'] = $Stock_35_goods_str-$add['Stock_35_skcnum_big']-$add['Stock_35_skcnum_normal'];

                $add['Stock_36_skcnum'] = $Stock_36_goods_str;
                $add['Stock_36_skcnum_big'] = round( ($Stock_36_goods_str*$puhuo_config['bigsize_big'])/100, 0 );
                $add['Stock_36_skcnum_normal'] = round( ($Stock_36_goods_str*$puhuo_config['bigsize_normal'])/100, 0 );
                $add['Stock_36_skcnum_small'] = $Stock_36_goods_str-$add['Stock_36_skcnum_big']-$add['Stock_36_skcnum_normal'];

                $add['Stock_38_skcnum'] = $Stock_38_goods_str;
                $add['Stock_38_skcnum_big'] = round( ($Stock_38_goods_str*$puhuo_config['bigsize_big'])/100, 0 );
                $add['Stock_38_skcnum_normal'] = round( ($Stock_38_goods_str*$puhuo_config['bigsize_normal'])/100, 0 );
                $add['Stock_38_skcnum_small'] = $Stock_38_goods_str-$add['Stock_38_skcnum_big']-$add['Stock_38_skcnum_normal'];

                $add['Stock_40_skcnum'] = $Stock_40_goods_str;
                $add['Stock_40_skcnum_big'] = round( ($Stock_40_goods_str*$puhuo_config['bigsize_big'])/100, 0 );
                $add['Stock_40_skcnum_normal'] = round( ($Stock_40_goods_str*$puhuo_config['bigsize_normal'])/100, 0 );
                $add['Stock_40_skcnum_small'] = $Stock_40_goods_str-$add['Stock_40_skcnum_big']-$add['Stock_40_skcnum_normal'];

                // print_r($add);die;
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
                $insert = $this->db_easy->table('sp_lyp_puhuo_daxiaoma_skcnum')->strict(false)->insertAll($val);
            }

        }


    }

    //大小码skc数获取（云仓）
    protected function get_daxiaoma_goods_data() {

        //剔除的货品
        $ti_goods = SpLypPuhuoTiGoodsModel::where([])->column('GoodsNo');
        $ti_goods = get_goods_str($ti_goods);

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
        where  (TimeCategoryName2 like '%{$autumn_config['name']}%' or TimeCategoryName2 like '%{$winter_config['name']}%') and GoodsNo not in ($ti_goods)  
         group by WarehouseName, TimeCategoryName1, season, CategoryName1, CategoryName2, StyleCategoryName;";
        //  echo $sql;die;

         return $this->db_easy->Query($sql);

    }


    //剔除 指定款处理 sp_lyp_puhuo_ti_goods
    protected function deal_ti_goods() {

        $ti_GoodsLevel = SpLypPuhuoTiGoodsTypeModel::where([])->distinct(true)->column('GoodsLevel');
        if ($ti_GoodsLevel) {
            //先清空旧数据再跑
            $this->db_easy->Query("truncate table sp_lyp_puhuo_ti_goods;");
            foreach ($ti_GoodsLevel as $v_level) {
                $v_level = trim($v_level);
                $sql = "insert into sp_lyp_puhuo_ti_goods(GoodsLevel, GoodsNo) select '".$v_level."' as GoodsLevel, 货号 as GoodsNo from sjp_goods where 二级风格 in ('".$v_level."');";
                $this->db_easy->Query($sql);
            }
        }

    }


}
