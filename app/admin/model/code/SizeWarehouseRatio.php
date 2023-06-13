<?php

namespace app\admin\model\code;


use app\common\model\TimeModel;
use think\facade\Db;
use app\admin\model\code\SizeShopEstimatedStock;
use app\admin\model\code\SizeAccumulatedSale;
use app\admin\model\code\SizeWarehouseAvailableStock;
use app\admin\model\code\SizeWarehouseTransitStock;
use app\admin\model\code\SizeRanking;

class SizeWarehouseRatio extends TimeModel
{
    // 表名
    protected $name = 'ea_size_warehouse_ratio';

    /**
     * 保存云仓偏码数据
     * @param string $goodsno
     */
    public static function saveData($goodsno = 'B31101236')
    {
        // 云仓
        $warehouse = [
            '广州云仓',
            '南昌云仓',
            '长沙云仓',
            '武汉云仓',
            '贵阳云仓'
        ];

        // 尺码
        $size = [
            '库存_00/28/37/44/100/160/S',
            '库存_29/38/46/105/165/M',
            '库存_30/39/48/110/170/L',
            '库存_31/40/50/115/175/XL',
            '库存_32/41/52/120/180/2XL',
            '库存_33/42/54/125/185/3XL',
            '库存_34/43/56/190/4XL',
            '库存_35/44/58/195/5XL',
            '库存_36/6XL',
            '库存_38/7XL',
            '库存_40/8XL'
        ];

        $fieldStr = "sum(`Quantity`) Quantity,
            sum(`库存_00/28/37/44/100/160/S`) `库存_00/28/37/44/100/160/S`,
            sum(`库存_29/38/46/105/165/M`) `库存_29/38/46/105/165/M`,
            sum(`库存_30/39/48/110/170/L`) `库存_30/39/48/110/170/L`,
            sum(`库存_31/40/50/115/175/XL`) `库存_31/40/50/115/175/XL`,
            sum(`库存_32/41/52/120/180/2XL`) `库存_32/41/52/120/180/2XL`,
            sum(`库存_33/42/54/125/185/3XL`) `库存_33/42/54/125/185/3XL`,
            sum(`库存_34/43/56/190/4XL`) `库存_34/43/56/190/4XL`,
            sum(`库存_35/44/58/195/5XL`) `库存_35/44/58/195/5XL`,
            sum(`库存_36/6XL`) `库存_36/6XL`,
            sum(`库存_38/7XL`) `库存_38/7XL`,
            sum(`库存_40/8XL`) `库存_40/8XL`
        ";

        $fieldStr2 = "
            sum(CASE WHEN `Quantity` > 0 THEN 1 ELSE 0 END ) `Quantity`,
            sum(CASE WHEN `库存_00/28/37/44/100/160/S` > 0 THEN 1 ELSE 0 END ) `库存_00/28/37/44/100/160/S`,
            sum(CASE WHEN `库存_29/38/46/105/165/M` > 0 THEN 1 ELSE 0 END ) `库存_29/38/46/105/165/M`,
            sum(CASE WHEN `库存_30/39/48/110/170/L` > 0 THEN 1 ELSE 0 END ) `库存_30/39/48/110/170/L`,
            sum(CASE WHEN `库存_31/40/50/115/175/XL` > 0 THEN 1 ELSE 0 END ) `库存_31/40/50/115/175/XL`,
            sum(CASE WHEN `库存_32/41/52/120/180/2XL` > 0 THEN 1 ELSE 0 END ) `库存_32/41/52/120/180/2XL`,
            sum(CASE WHEN `库存_33/42/54/125/185/3XL` > 0 THEN 1 ELSE 0 END ) `库存_33/42/54/125/185/3XL`,
            sum(CASE WHEN `库存_34/43/56/190/4XL` > 0 THEN 1 ELSE 0 END ) `库存_34/43/56/190/4XL`,
            sum(CASE WHEN `库存_35/44/58/195/5XL` > 0 THEN 1 ELSE 0 END ) `库存_35/44/58/195/5XL`,
            sum(CASE WHEN `库存_36/6XL` > 0 THEN 1 ELSE 0 END ) `库存_36/6XL`,
            sum(CASE WHEN `库存_38/7XL` > 0 THEN 1 ELSE 0 END ) `库存_38/7XL`,
            sum(CASE WHEN `库存_40/8XL` > 0 THEN 1 ELSE 0 END ) `库存_40/8XL`
        ";

        // 商品信息
        $info = SizeRanking::where([
            '货号' => $goodsno
        ])->find();

        // 货品等级
        $config = sysconfig('site');
        // 单码缺量判断数值
        $level_rate = 0;
        if($info['货品等级']=='B级'){
            $level_rate = $config['level_b'];
        }else{
            $level_rate = $config['level_other'];
        }

        // 单款云仓在途库存
        $transit_stock_total = SizeWarehouseTransitStock::where(['GoodsNo' => $goodsno])->group('WarehouseName')->column($fieldStr,'WarehouseName');
        // 单款云仓可用库存
        $available_stock_total = SizeWarehouseAvailableStock::where(['GoodsNo' => $goodsno])->group('WarehouseName')->column($fieldStr,'WarehouseName');
        // 单款店铺预计库存
        $shop_stock_total = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno])->group('CustomItem15')->column($fieldStr,'CustomItem15');
        // 单款周销
        $day7_total = Size7DaySale::where(['货号' => $goodsno])->group('云仓')->column($fieldStr,'云仓');
        // 单款累销
        $sale_total = SizeAccumulatedSale::where(['货号' => $goodsno])->group('云仓')->column($fieldStr,'云仓');
        // 单码上柜数
        $size_up_total = SizeShopEstimatedStock::where(['GoodsNo' => $goodsno])->group('CustomItem15')->column($fieldStr2,'CustomItem15');

        // 数据
        $data = [];

        foreach ($warehouse as $k => $v){

            // 单款云仓在途库存
            $all_transit_stock = 0;
            if(!empty($transit_stock_total[$v])){
                $all_transit_stock = $transit_stock_total[$v]['Quantity']??0;
            }

            // 单款云仓库存
            $all_available_stock = 0;
            if(!empty($available_stock_total[$v])){
                $all_available_stock = $available_stock_total[$v]['Quantity']??0;
            }

            // 单款店铺预计库存
            $all_shop_stock = 0;
            if(!empty($shop_stock_total[$v])){
                $all_shop_stock = $shop_stock_total[$v]['Quantity']??0;
            }

            // 单款周销
            $all_day7_sale = 0;
            if(!empty($day7_total[$v])){
                $all_day7_sale = $day7_total[$v]['Quantity']??0;
            }

            // 单款当前总库存量 = 单款云仓在途 + 单款云仓库存 + 单款店铺预计库存
            $all_total_stock = $all_transit_stock + $all_available_stock + $all_shop_stock;

            // 周转 = 单款当前总库存量 / 周销
            $all_turnover = 0;
            if($all_total_stock > 0 && $all_day7_sale > 0){
                $all_turnover = bcadd($all_total_stock / $all_day7_sale,0,2);
            }

            // 单款累销
            $sale_total_sum = 0;
            if(!empty($sale_total[$v])){
                $sale_total_sum = $sale_total[$v]['Quantity']??0;
            }

            // 单款售罄 = 单款累销 / (单款当前总库存量 + 单款累销)
            $all_size_sell_out = 0;
            if($sale_total_sum > 0 && ($all_total_stock + $sale_total_sum) > 0){
                $size_sell_out = bcadd($sale_total_sum / ($all_total_stock + $sale_total_sum),0,2);
            }

            // 单款上柜数
            $all_size_up_total = 0;
            if(!empty($size_up_total[$v])){
                $all_size_up_total = $size_up_total[$v]['Quantity']??0;
            }
            // 单款当前单店均深 = 当前总库存量 / 上柜家数
            $all_shop_mean = 0;
            if($all_total_stock > 0 && $all_size_up_total > 0){
                $all_shop_mean = bcadd($all_total_stock / $all_size_up_total,0,2);
            }

            $total_item = [
                '排名' => $info['排名'],
                '风格' => $info['风格'],
                '一级分类' => $info['一级分类'],
                '二级分类' => $info['二级分类'],
                '领型' => $info['领型'],
                '近三天折率' => $info['近三天折率'],
                'GoodsNo' => $goodsno,
                '货品等级' => $info['货品等级'],
                '单码售罄' => $all_size_sell_out,
                '周转' => $all_turnover,
                '当前总库存量' => $all_total_stock,
                '单码上柜数' => '',
                '累销' => $sale_total_sum,
                '周销' => $all_day7_sale,
                '店铺库存' => $all_shop_stock,
                '云仓在途' => $all_transit_stock,
                '当前单店均深' => $all_shop_mean
            ];
            foreach ($size as $kk => $vv){

                // 单码云仓在途
                $transit_stock = 0;
                if(!empty($transit_stock_total[$v])){
                    // 云仓在途某个云仓的某个尺码
                    $transit_stock = $transit_stock_total[$v][$vv]??0;
                }

                // 单码云仓库存
                $available_stock = 0;
                if(!empty($available_stock_total[$v])){
                    // 云仓库存某个云仓的某个尺码
                    $available_stock = $available_stock_total[$v][$vv]??0;
                }

                // 单码店铺预计库存
                $shop_stock = 0;
                if(!empty($shop_stock_total[$v])){
                    // 云仓库存某个云仓的某个尺码
                    $shop_stock = $shop_stock_total[$v][$vv]??0;
                }

                // 单码当前总库存量 = 单码云仓在途 + 单码云仓库存 + 单码店铺预计库存
                $this_total_stock = $transit_stock + $available_stock + $shop_stock;

                // 周销
                $day7_sale = 0;
                if(!empty($day7_total[$v])){
                    $day7_sale = $day7_total[$v][$vv]??0;
                }
                // 周转 = 当前总库存量 / 周销
                $turnover = 0;
                if($this_total_stock > 0 && $day7_sale > 0){
                   $turnover = bcadd($this_total_stock / $day7_sale,0,2) ;
                }

                // 单码累销
                $size_sale_total = 0;
                if(!empty($sale_total[$v])){
                    $size_sale_total = $sale_total[$v][$vv]??0;
                }

                // 单码售罄 = 单码累销 / (单码当前总库存量 + 单码累销)
                $size_sell_out = 0;
                if($size_sale_total > 0 && ($this_total_stock + $size_sale_total) > 0){
                    $size_sell_out = bcadd($size_sale_total / ($this_total_stock + $size_sale_total),0,2);
                }

                // 累销尺码比 = (单码累销 / 单款累销)
                $sale_total_ratio = 0;
                if($size_sale_total > 0 && $sale_total_sum > 0){
                    $sale_total_ratio = bcadd($size_sale_total / $sale_total_sum,0,2);
                }

                // 当前库存尺码比 = 单码当前总库存量 / 单款当前总库存量
                $total_stock_ratio = 0;
                if($this_total_stock > 0 && $all_total_stock > 0){
                    $total_stock_ratio = bcadd($this_total_stock / $all_total_stock,0,2);
                }

                // 单码售罄比 = 单码售罄 - 单款售罄
                $size_sell_out_ratio = 0;
                if($size_sell_out > 0 && $all_size_sell_out > 0){
                    $size_sell_out_ratio = $size_sell_out - $all_size_sell_out;
                }

                // 单码缺量 = 如果
                if($size_sell_out_ratio > $level_rate){
                    $total_item['单码售罄比'] = "单码缺量";
                }

                // 单码当前单店均深 = 当前总库存量 / 上柜家数
                $shop_mean = 0;
                if($this_total_stock > 0 && $all_size_up_total > 0){
                    $shop_mean = bcadd($all_total_stock / $all_size_up_total,0,2);
                }

                $data['当前单店均深'][$vv] =
            }

            $data[] = $total_item;

        }



        // 货号
        $arr = [$goodsno];
        foreach ($arr as $key => $val){
             // 商品信息
                $info = Db::connect('sqlsrv')->table('ErpGoods eg')->leftJoin('ErpGoodsImg egi','eg.GoodsId = egi.GoodsId')
                        ->field('eg.GoodsId,eg.GoodsNo,
                        eg.GoodsName,
                        eg.UnitPrice,
                        eg.CategoryName,
                        eg.CategoryName1,
                        eg.CategoryName2,
                        eg.TimeCategoryName1,
                        eg.TimeCategoryName2,
                        eg.StyleCategoryName,
                        eg.StyleCategoryName2,
                        LEFT(eg.CategoryName,2) as Collar,
                        egi.Img')
                    ->where([
                    'GoodsNo' => $val
                ])->find();

        }
    }
}